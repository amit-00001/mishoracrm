<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsappConversation;
use App\Models\WhatsappConversationNote;
use App\Models\WhatsappQuickReply;
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

    // Without whatsapp.view_all a person sees only the chats assigned to them
    // plus the unassigned ones (so new chats can still be picked up).
    private function scoped($query)
    {
        $query->where('tenant_id', $this->tenantId());

        if (!auth()->user()->can('whatsapp.view_all')) {
            $me = auth()->id();
            $query->where(fn ($q) => $q->where('assigned_to', $me)->orWhereNull('assigned_to'));
        }

        return $query;
    }

    private function conversation(int $id): WhatsappConversation
    {
        return $this->scoped(WhatsappConversation::query())
            ->with(['lead:id,name,phone', 'contact:id,name,phone', 'assignee:id,name'])
            ->findOrFail($id);
    }

    private function team(): \Illuminate\Support\Collection
    {
        return User::where('tenant_id', $this->tenantId())->where('is_active', true)
            ->orderBy('name')->get(['id', 'name']);
    }

    // ── Inbox page ────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $filter = $request->input('filter', 'open'); // open | mine | unassigned | unread | resolved | all

        $conversations = $this->listQuery($filter, $request->input('search'))->limit(100)->get();

        $selected = $request->filled('c') ? $this->conversation((int) $request->input('c')) : null;
        $messages = collect();
        if ($selected) {
            $messages = $selected->messages()->with('sentBy:id,name')->orderBy('id')->limit(300)->get();
            if ($selected->unread_count > 0) {
                $selected->update(['unread_count' => 0]);
            }
        }

        $settings  = WhatsappSetting::forTenant($this->tenantId());
        $templates = $settings->exists && $settings->viaGateway() && $settings->is_connected
            ? WhatsappGatewayClient::approvedTemplates($settings->gateway_workspace_id)
            : [];

        $counts = $this->counts();

        return view('tenant.whatsapp.inbox', [
            'conversations' => $conversations,
            'selected'      => $selected,
            'messages'      => $messages,
            'filter'        => $filter,
            'counts'        => $counts,
            'connected'     => (bool) $settings->is_connected,
            'templates'     => $templates,
            'canReply'      => auth()->user()->can('whatsapp.send'),
            'canAssign'     => auth()->user()->can('whatsapp.assign'),
            'canQuick'      => auth()->user()->can('whatsapp.quick_replies'),
            'team'          => $this->team()->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values(),
            'me'            => auth()->id(),
            'autoAssign'    => ['on' => (bool) $settings->inbox_auto_assign, 'users' => $settings->inbox_auto_assign_users ?: []],
            'quickReplies'  => WhatsappQuickReply::where('tenant_id', $this->tenantId())->orderBy('shortcut')->get(['id', 'shortcut', 'body']),
            'list'          => $conversations->map(fn ($c) => $this->summary($c))->values(),
            'head'          => $selected ? $this->head($selected) : null,
            'bubbles'       => $this->timeline($selected, $messages, 0),
        ]);
    }

    private function counts(): array
    {
        $base = fn () => $this->scoped(WhatsappConversation::query());

        return [
            'open'     => $base()->where('status', 'open')->count(),
            'mine'     => $base()->where('status', 'open')->where('assigned_to', auth()->id())->count(),
            'unassigned' => $base()->where('status', 'open')->whereNull('assigned_to')->count(),
            'unread'   => $base()->where('unread_count', '>', 0)->count(),
            'resolved' => $base()->where('status', 'resolved')->count(),
        ];
    }

    // Header + state of one chat, for the page to draw without reloading.
    private function head(WhatsappConversation $c): array
    {
        return [
            'id'          => $c->id,
            'name'        => $c->displayName(),
            'phone'       => '+' . $c->wa_id,
            'lead_url'    => $c->lead_id ? route('tenant.leads.show', $c->lead_id) : null,
            'contact_url' => $c->contact_id ? route('tenant.contacts.show', $c->contact_id) : null,
            'status'      => $c->status,
            'window_open' => $c->windowOpen(),
            'assigned_to' => $c->assigned_to,
            'assignee'    => $c->assignee?->name,
        ];
    }

    private function listQuery(string $filter, ?string $search)
    {
        return $this->scoped(WhatsappConversation::query())
            ->with(['lead:id,name', 'contact:id,name', 'assignee:id,name'])
            ->when($filter === 'open',     fn ($q) => $q->where('status', 'open'))
            ->when($filter === 'mine',     fn ($q) => $q->where('status', 'open')->where('assigned_to', auth()->id()))
            ->when($filter === 'unassigned', fn ($q) => $q->where('status', 'open')->whereNull('assigned_to'))
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

        $new  = [];
        $head = null;
        if ($request->filled('c')) {
            $conv = $this->conversation((int) $request->input('c'));
            $new  = $this->timeline($conv, $conv->messages()->with('sentBy:id,name')->where('id', '>', (int) $request->input('after', 0))->orderBy('id')->get(), (int) $request->input('after_note', 0));
            if ($conv->unread_count > 0) {
                $conv->update(['unread_count' => 0]);
            }
            $head = $this->head($conv);
        }

        return response()->json(['conversations' => $list, 'messages' => $new, 'counts' => $this->counts(), 'head' => $head]);
    }

    // ── One chat, as JSON (opened without a page reload) ──────────
    public function show(int $id): JsonResponse
    {
        $conv = $this->conversation($id);
        $msgs = $conv->messages()->with('sentBy:id,name')->orderBy('id')->limit(300)->get();
        if ($conv->unread_count > 0) {
            $conv->update(['unread_count' => 0]);
        }

        return response()->json([
            'head'     => $this->head($conv),
            'messages' => $this->timeline($conv, $msgs, 0),
            'counts'   => $this->counts(),
        ]);
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
            'assignee' => $c->assignee?->name,
        ];
    }

    // Messages plus private notes, in one list for the page (notes newer than
    // $afterNote). The page orders them by 'ts'.
    private function timeline(?WhatsappConversation $conv, $messages, int $afterNote)
    {
        if (!$conv) {
            return collect();
        }

        $notes = WhatsappConversationNote::where('conversation_id', $conv->id)->where('id', '>', $afterNote)
            ->with('user:id,name')->orderBy('id')->limit(300)->get()
            ->map(fn ($n) => $this->noteBubble($n));

        return $messages->map(fn ($m) => $this->bubble($m))->concat($notes)->sortBy('ts')->values();
    }

    public function noteBubble(WhatsappConversationNote $n): array
    {
        return [
            'key'  => 'n' . $n->id,
            'id'   => $n->id,
            'note' => true,
            'text' => $n->body,
            'by'   => $n->user?->name,
            'time' => $n->created_at->format('d M, h:i A'),
            'ts'   => $n->created_at->toIso8601String(),
        ];
    }

    public function bubble(WhatsappLog $m): array
    {
        return [
            'key'      => 'm' . $m->id,
            'ts'       => ($m->sent_at ?? $m->created_at)?->toIso8601String(),
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
            // Replying to a chat nobody owns makes it yours.
            if (!$conv->assigned_to) {
                $conv->assigned_to = auth()->id();
            }
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

    // ── Assign ────────────────────────────────────────────────────
    public function assign(Request $request, int $id): JsonResponse
    {
        $request->validate(['user_id' => ['nullable', 'integer']]);

        $conv   = $this->conversation($id);
        $userId = $request->filled('user_id') ? (int) $request->input('user_id') : null;

        if ($userId && !$this->team()->contains('id', $userId)) {
            return response()->json(['error' => 'That person is not on your team.'], 422);
        }

        $conv->update(['assigned_to' => $userId]);
        $conv->load('assignee:id,name');

        return response()->json(['head' => $this->head($conv), 'counts' => $this->counts()]);
    }

    // ── Internal notes ────────────────────────────────────────────
    public function addNote(Request $request, int $id): JsonResponse
    {
        $request->validate(['body' => ['required', 'string', 'max:2000']]);

        $conv = $this->conversation($id);
        $note = WhatsappConversationNote::create([
            'tenant_id'       => $this->tenantId(),
            'conversation_id' => $conv->id,
            'user_id'         => auth()->id(),
            'body'            => trim($request->input('body')),
        ]);

        return response()->json(['message' => $this->noteBubble($note->load('user:id,name'))]);
    }

    // ── Quick replies ─────────────────────────────────────────────
    public function saveQuickReply(Request $request, ?int $id = null): JsonResponse
    {
        $data = $request->validate([
            'shortcut' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'body'     => ['required', 'string', 'max:2000'],
        ]);
        $data['shortcut'] = strtolower($data['shortcut']);

        $dupe = WhatsappQuickReply::where('tenant_id', $this->tenantId())->where('shortcut', $data['shortcut'])
            ->when($id, fn ($q) => $q->where('id', '!=', $id))->exists();
        if ($dupe) {
            return response()->json(['error' => "/{$data['shortcut']} already exists."], 422);
        }

        $reply = $id
            ? tap(WhatsappQuickReply::where('tenant_id', $this->tenantId())->findOrFail($id))->update($data)
            : WhatsappQuickReply::create($data + ['tenant_id' => $this->tenantId(), 'created_by' => auth()->id()]);

        return response()->json(['reply' => $reply->only(['id', 'shortcut', 'body'])]);
    }

    public function deleteQuickReply(int $id): JsonResponse
    {
        WhatsappQuickReply::where('tenant_id', $this->tenantId())->findOrFail($id)->delete();

        return response()->json(['ok' => true]);
    }

    // ── Auto-assign settings (owner) ──────────────────────────────
    public function saveAutoAssign(Request $request): JsonResponse
    {
        $data = $request->validate([
            'on'      => ['required', 'boolean'],
            'users'   => ['nullable', 'array'],
            'users.*' => ['integer'],
        ]);

        $valid = $this->team()->pluck('id')->all();
        $users = array_values(array_intersect($data['users'] ?? [], $valid));

        if ($data['on'] && !$users) {
            return response()->json(['error' => 'Pick at least one person to share new chats with.'], 422);
        }

        $setting = WhatsappSetting::forTenant($this->tenantId());
        $setting->fill([
            'inbox_auto_assign'       => (bool) $data['on'],
            'inbox_auto_assign_users' => $users,
        ])->save();

        return response()->json(['on' => (bool) $data['on'], 'users' => $users]);
    }

    // ── Resolve / reopen ──────────────────────────────────────────
    public function toggleStatus(int $id): JsonResponse
    {
        $conv = $this->conversation($id);
        $conv->update(['status' => $conv->status === 'open' ? 'resolved' : 'open']);

        return response()->json(['status' => $conv->status, 'head' => $this->head($conv), 'counts' => $this->counts()]);
    }
}
