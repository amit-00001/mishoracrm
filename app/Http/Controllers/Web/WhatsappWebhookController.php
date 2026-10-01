<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\WhatsappSetting;
use App\Services\WhatsappInboundService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WhatsappWebhookController extends Controller
{
    // ── GET: Meta webhook verification ───────────────────────────
    public function verify(Request $request)
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode !== 'subscribe') {
            return response('Invalid mode', 403);
        }

        $setting = WhatsappSetting::where('webhook_verify_token', $token)->first();
        if (!$setting) {
            return response('Token mismatch', 403);
        }

        return response($challenge, 200)->header('Content-Type', 'text/plain');
    }

    // ── POST: Incoming WhatsApp Cloud API events ─────────────────
    public function handle(Request $request): Response
    {
        $payload = $request->all();

        if (($payload['object'] ?? '') !== 'whatsapp_business_account') {
            return response('ok', 200);
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            $wabaId = $entry['id'] ?? null;

            $setting = WhatsappSetting::where('waba_id', $wabaId)->first();
            if (!$setting) continue;

            foreach ($entry['changes'] ?? [] as $change) {
                if (($change['field'] ?? '') !== 'messages') continue;

                $value = $change['value'] ?? [];

                foreach ($value['messages'] ?? [] as $message) {
                    $this->processMessage($setting, $message, $value['contacts'] ?? []);
                }
            }
        }

        return response('EVENT_RECEIVED', 200);
    }

    private function processMessage(WhatsappSetting $setting, array $message, array $contacts): void
    {
        $messageType = $message['type'] ?? 'unknown';
        $waId        = $message['from'] ?? null;
        $messageId   = $message['id'] ?? null;

        if (!$waId) return;

        $contactName = null;
        foreach ($contacts as $contact) {
            if (($contact['wa_id'] ?? '') === $waId) {
                $contactName = $contact['profile']['name'] ?? null;
                break;
            }
        }

        $messageText = null;
        $buttonId    = null;
        if ($messageType === 'text') {
            $messageText = $message['text']['body'] ?? null;
        } elseif ($messageType === 'interactive') {
            // Tapping a reply button/list option feeds its title back in as
            // if the user had typed it (keyword-matching fallback); if the
            // button was explicitly linked to a next flow, $buttonId lets the
            // chatbot service jump straight there instead.
            $interactiveType = $message['interactive']['type'] ?? null;
            if ($interactiveType === 'button_reply') {
                $messageText = $message['interactive']['button_reply']['title'] ?? null;
                $buttonId    = $message['interactive']['button_reply']['id'] ?? null;
            } elseif ($interactiveType === 'list_reply') {
                $messageText = $message['interactive']['list_reply']['title'] ?? null;
                $buttonId    = $message['interactive']['list_reply']['id'] ?? null;
            }
        }

        (new WhatsappInboundService)->handle($setting, $waId, $messageType, $messageText, $buttonId, $contactName);
    }
}
