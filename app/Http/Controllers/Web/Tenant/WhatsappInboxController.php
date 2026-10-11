<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\WhatsappConversation;
use App\Models\WhatsappLog;
use App\Models\WhatsappSetting;
use App\Services\WhatsappChatbotService;
use App\Services\WhatsappGatewayClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

// WhatsApp Team Inbox — the whole team reads and answers customer chats from
// one connected number. Gated by module:whatsapp_inbox + permission
// whatsapp.inbox (see routes/web.php).
class WhatsappInboxController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    private function conversation(int $id): WhatsappConversation
    {
        return WhatsappConversation::where('tenant_id', $this->tenantId())
            ->with(['lead:id,name,phone', 'contact:id,name,phone'])
            ->findOrFail($id);
    }

    // ── Inbox page ────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $filter = $request->input('filter', 'open'); // open | unread | resolved | all

        $conversations = $this->listQuery($filter, $request->input('search'))->limit(100)->get();

        $selected = $request->filled('c') ? $this->conversation((int) $request->input('c')) : null;
        $messages = collect();
        if ($selected) {
            $messages = $selected->messages()->orderBy('id')->limit(300)->get();
            if ($selected->unread_count > 0) {
                $selected->update(['unread_count' => 0]);
            }
        }

        $settings  = WhatsappSetting::forTenant($this->tenantId());
        $templates = $settings->exists && $settings->viaGateway() && $settings->is_connected
            ? WhatsappGatewayClient::approvedTemplates($settings->gateway_workspace_id)
            : [];

        $counts = [
            'open'     => WhatsappConversation::where('tenant_id', $this->tenantId())->where('status', 'open')->count(),
            'unread'   => WhatsappConversation::where('tenant_id', $this->tenantId())->where('unread_count', '>', 0)->count(),
            'resolved' => WhatsappConversation::where('tenant_id', $this->tenantId())->where('status', 'resolved')->count(),
        ];

        return view('tenant.whatsapp.inbox', [
            'conversations' => $conversations,
            'selected'      => $selected,
            'messages'      => $messages,
            'filter'        => $filter,
            'counts'        => $counts,
            'connected'     => (bool) $settings->is_connected,
            'templates'     => $templates,
            'canReply'      => auth()->user()->can('whatsapp.send'),
        ]);
    }

    private function listQuery(string $filter, ?string $search)
    {
        return WhatsappConversation::where('tenant_id', $this->tenantId())
            ->with(['lead:id,name', 'contact:id,name'])
            ->when($filter === 'open',     fn ($q) => $q->where('status', 'open'))
            ->when($filter === 'unread',   fn ($q) => $q->where('unread_count', '>', 0))
            ->when($filter === 'resolved', fn ($q) => $q->where('status', 'resolved'))
            ->when($search, function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('wa_id', 'like', "%{$search}%")->orWhere('contact_name', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('last_message_at');
    }

    // ── Live refresh (polled every few seconds by the page) ───────
    public function poll(Request $request): JsonResponse
    {
        $list = $this->listQuery($request->input('filter', 'open'), $request->input('search'))
            ->limit(100)->get()
            ->map(fn ($c) => $this->summary($c))->values();

        $new = [];
        if ($request->filled('c')) {
            $conv = $this->conversation((int) $request->input('c'));
            $new  = $conv->messages()->where('id', '>', (int) $request->input('after', 0))
                ->orderBy('id')->get()->map(fn ($m) => $this->bubble($m))->values();
            if ($conv->unread_count > 0) {
                $conv->update(['unread_count' => 0]);
            }
        }

        return response()->json(['conversations' => $list, 'messages' => $new]);
    }

    public function summary(WhatsappConversation $c): array
    {
        return [
            'id'      => $c->id,
            'name'    => $c->displayName(),
            'preview' => $c->last_message_preview,
            'unread'  => $c->unread_count,
            'time'    => $c->last_message_at?->diffForHumans(null, true, true),
            'status'  => $c->status,
        ];
    }

    public function bubble(WhatsappLog $m): array
    {
        return [
            'id'       => $m->id,
            'in'       => $m->status === 'received',
            'text'     => $m->message,
            'status'   => $m->status,
            'error'    => $m->error_message,
            'file'     => $m->attachment_name,
            'time'     => ($m->sent_at ?? $m->created_at)?->format('d M, h:i A'),
            'by'       => $m->sentBy?->name,
        ];
    }

    // ── Reply ─────────────────────────────────────────────────────
    public function reply(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'message'           => ['nullable', 'string', 'max:4096'],
            'attachment'        => ['nullable', 'file', 'max:16384', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx'],
            'meta_template'     => ['nullable', 'string', 'max:200'],
            'template_params'   => ['nullable', 'array'],
            'template_params.*' => ['nullable', 'string', 'max:1000'],
        ]);

        $conv = $this->conversation($id);

        $settings = WhatsappSetting::forTenant($this->tenantId());
        if (!$settings->exists || !$settings->is_connected) {
            return response()->json(['error' => 'WhatsApp is not connected. Connect it in WhatsApp API Settings first.'], 422);
        }

        $service = WhatsappChatbotService::forTenant($this->tenantId());

        $template = null;
        $text     = trim((string) $request->input('message'));
        $mediaType = $mediaId = $fileName = null;

        if ($request->filled('meta_template')) {
            foreach (WhatsappGatewayClient::approvedTemplates($settings->gateway_workspace_id ?? '') as $t) {
                if ($t['name'] . '|' . $t['language'] === $request->meta_template) {
                    $template = $t;
                    break;
                }
            }
            if (!$template) {
                return response()->json(['error' => 'That template is not available — it may not be approved yet.'], 422);
            }
            $params = [];
            for ($i = 0; $i < $template['params']; $i++) {
                $v = trim((string) ($request->input('template_params')[$i] ?? ''));
                if ($v === '') {
                    return response()->json(['error' => 'Fill in every template field.'], 422);
                }
                $params[] = $v;
            }
            $ok      = $service->sendTemplate($conv->wa_id, $template['name'], $template['language'], $params);
            $error   = $ok ? null : ($service->lastError ?? 'WhatsApp rejected the template message.');
            $logText = WhatsappGatewayClient::renderTemplateBody($template['body'], $params);
        } else {
            if (!$conv->windowOpen()) {
                return response()->json(['error' => 'The 24-hour reply window has closed. Send an approved template instead.', 'window_closed' => true], 422);
            }
            if ($text === '' && !$request->hasFile('attachment')) {
                return response()->json(['error' => 'Type a message or attach a file.'], 422);
            }

            if ($file = $request->file('attachment')) {
                $mediaType = WhatsappChatbotService::mediaTypeForMime($file->getMimeType());
                $fileName  = $file->getClientOriginalName();
                $mediaId   = $service->uploadMedia($file->getRealPath(), $file->getMimeType());
                $ok        = $mediaId ? $service->sendMediaMessage($conv->wa_id, $mediaId, $mediaType, $text ?: null, $fileName) : false;
                $error     = $ok ? null : ($service->lastError ?? 'WhatsApp rejected the attachment.');
            } else {
                $ok    = $service->sendMessage($conv->wa_id, $text);
                $error = $ok ? null : ($service->lastError ?? 'WhatsApp rejected the message.');
            }
            $logText = $text;
        }

        $log = WhatsappLog::create([
            'tenant_id'       => $this->tenantId(),
            'lead_id'         => $conv->lead_id,
            'contact_id'      => $conv->contact_id,
            'sent_by'         => auth()->id(),
            'to_phone'        => $conv->wa_id,
            'to_name'         => $conv->displayName(),
            'message'         => $logText,
            'status'          => $ok ? 'sent' : 'failed',
            'error_message'   => $error,
            'media_type'      => $mediaType,
            'media_id'        => $mediaId,
            'attachment_name' => $fileName,
            'wamid'           => $ok ? $service->lastMessageId : null,
            'sent_at'         => now(),
        ]);

        if ($ok) {
            $conv->update([
                'last_message_preview' => mb_substr(($fileName ? "📎 {$fileName} " : '') . $logText, 0, 140),
                'last_message_at'      => now(),
                'unread_count'         => 0,
            ]);
        }

        return response()->json([
            'ok'      => $ok,
            'error'   => $error,
            'message' => $this->bubble($log->load('sentBy')),
        ], $ok ? 200 : 422);
    }

    // ── Resolve / reopen ──────────────────────────────────────────
    public function toggleStatus(int $id): JsonResponse
    {
        $conv = $this->conversation($id);
        $conv->update(['status' => $conv->status === 'open' ? 'resolved' : 'open']);

        return response()->json(['status' => $conv->status]);
    }
}
