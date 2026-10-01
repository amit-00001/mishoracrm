<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\WhatsappLog;
use App\Models\WhatsappSetting;
use App\Services\WhatsappGatewayClient;
use App\Services\WhatsappInboundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

// Receives the signed events the WhatsApp Gateway (Milan CRM) POSTs to us:
// message.received, message.status, account.connected (+ template.status /
// account.update, which are only logged). Authenticated by the HMAC
// signature — no session, no CSRF (see bootstrap/app.php).
//
// The gateway retries any non-2xx answer (1m, 5m, 30m, 2h, 6h), so a bad
// signature or a processing failure is answered with an error and everything
// else — including events for workspaces we no longer know — with 200.
class WhatsappGatewayWebhookController extends Controller
{
    public function handle(Request $request): Response|JsonResponse
    {
        if (!WhatsappGatewayClient::enabled()) {
            return response('WhatsApp gateway is turned off.', 503);
        }

        $rawBody = $request->getContent();

        if (!WhatsappGatewayClient::verifySignature($rawBody, $request->header('X-Gateway-Timestamp'), $request->header('X-Gateway-Signature'))) {
            Log::warning('WhatsApp gateway webhook rejected: bad signature');
            return response('Invalid signature.', 401);
        }

        $event = json_decode($rawBody, true);
        if (!is_array($event)) {
            return response('Invalid payload.', 400);
        }

        // The same delivery can arrive twice (a retry after a slow 2xx) —
        // process each delivery id once.
        $deliveryId  = (string) ($request->header('X-Gateway-Delivery') ?: ($event['id'] ?? ''));
        $deliveryKey = "wa_gateway_delivery:{$deliveryId}";
        if ($deliveryId !== '' && !Cache::add($deliveryKey, true, now()->addDay())) {
            return response()->json(['ok' => true, 'duplicate' => true]);
        }

        try {
            $this->dispatchEvent($event);
        } catch (\Throwable $e) {
            // Let the gateway retry this delivery.
            Cache::forget($deliveryKey);
            Log::error('WhatsApp gateway event failed', ['event' => $event['event'] ?? null, 'error' => $e->getMessage()]);
            return response('Processing failed.', 500);
        }

        return response()->json(['ok' => true]);
    }

    private function dispatchEvent(array $event): void
    {
        $workspaceId = $event['workspace']['id'] ?? null;
        $setting     = $workspaceId ? WhatsappSetting::where('gateway_workspace_id', $workspaceId)->first() : null;

        if (!$setting) {
            Log::info('WhatsApp gateway event for an unknown workspace', ['workspace' => $workspaceId, 'event' => $event['event'] ?? null]);
            return;
        }

        $data = is_array($event['data'] ?? null) ? $event['data'] : [];

        match ($event['event'] ?? null) {
            'message.received'  => $this->messageReceived($setting, $data['message'] ?? []),
            'message.status'    => $this->messageStatus($setting, $data['message'] ?? []),
            'account.connected' => $this->accountConnected($setting, $data),
            // Meta approved / rejected a template — make the send forms re-read the list.
            'template.status'   => WhatsappGatewayClient::forgetTemplateCache($workspaceId),
            default             => Log::info('WhatsApp gateway event ignored', ['event' => $event['event'] ?? null, 'workspace' => $workspaceId]),
        };
    }

    // A customer messaged the tenant's number — same handling as a message
    // that came straight from Meta (log, quotation buttons, chatbot, loyalty).
    private function messageReceived(WhatsappSetting $setting, array $message): void
    {
        if (($message['direction'] ?? 'inbound') !== 'inbound') {
            return;
        }

        $waId = preg_replace('/\D/', '', (string) ($message['phone'] ?? ''));
        if ($waId === '') {
            return;
        }

        [$text, $buttonId] = $this->textAndButton($message);

        (new WhatsappInboundService)->handle(
            $setting,
            $waId,
            (string) ($message['type'] ?? 'unknown'),
            $text,
            $buttonId,
            $message['contact_name'] ?? null
        );
    }

    // Plain messages carry their text in "text". For a tapped reply button /
    // list row we also need the tapped option's id (it routes quotation
    // Accept/Reject and chatbot "next flow" buttons). The gateway docs don't
    // spell that shape out, so look wherever Meta's own payload would put it.
    private function textAndButton(array $message): array
    {
        $content = is_array($message['content'] ?? null) ? $message['content'] : [];
        $text    = $message['text'] ?? ($content['text'] ?? null);
        $text    = is_string($text) ? $text : null;

        $reply = $content['button_reply']
            ?? $content['list_reply']
            ?? $content['interactive']['button_reply']
            ?? $content['interactive']['list_reply']
            ?? (isset($content['id'], $content['title']) ? $content : null);

        if (is_array($reply)) {
            return [$reply['title'] ?? $text, $reply['id'] ?? null];
        }

        return [$text, null];
    }

    // Delivery receipt for a message we sent. Logs only know sent / delivered
    // / failed, so "read" counts as delivered. Receipts can arrive out of
    // order — a log is only ever moved forward, never back.
    private function messageStatus(WhatsappSetting $setting, array $message): void
    {
        $wamid  = $message['wamid'] ?? null;
        $status = $message['status'] ?? null;

        if (!$wamid) {
            return;
        }

        if (in_array($status, ['delivered', 'read'], true)) {
            $update = ['status' => 'delivered'];
        } elseif ($status === 'failed') {
            $update = ['status' => 'failed', 'error_message' => $this->errorText($message['error'] ?? null)];
        } else {
            return;
        }

        WhatsappLog::withoutGlobalScopes()
            ->where('tenant_id', $setting->tenant_id)
            ->where('wamid', $wamid)
            ->whereIn('status', ['pending', 'sent'])
            ->update($update);
    }

    // The tenant finished the hosted connect flow (or the number was unlinked).
    private function accountConnected(WhatsappSetting $setting, array $data): void
    {
        if (($data['connected'] ?? true) === false) {
            WhatsappGatewayClient::applyDisconnection($setting);
            return;
        }

        WhatsappGatewayClient::applyConnection($setting, $data);
    }

    private function errorText(mixed $error): ?string
    {
        if (is_array($error)) {
            $error = $error['message'] ?? json_encode($error);
        }

        return filled($error) ? mb_substr((string) $error, 0, 250) : null;
    }
}
