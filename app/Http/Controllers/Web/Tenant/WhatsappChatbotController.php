<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Models\WhatsappChatbotFlow;
use App\Models\WhatsappChatbotSession;
use App\Models\WhatsappLog;
use App\Models\WhatsappSetting;
use App\Services\WhatsappChatbotService;
use App\Services\WhatsappGatewayClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WhatsappChatbotController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    // ── Settings — show ───────────────────────────────────────────
    public function settings(Request $request): View
    {
        $settings = WhatsappSetting::forTenant($this->tenantId());

        // WhatsApp Gateway switched on (and this tenant isn't connected straight
        // to Meta) → the gateway connect screen; otherwise the original screen.
        if (WhatsappGatewayClient::appliesTo($settings)) {
            $notice = null;

            // Back from the gateway's hosted connect page: don't wait for the
            // account.connected webhook, read the real state now.
            if ($request->filled('status') && $settings->gateway_workspace_id) {
                $result = WhatsappGatewayClient::refresh($settings);

                if ($request->query('status') === 'failed') {
                    $reason = Str::limit(strip_tags((string) $request->query('message')), 200);
                    $notice = ['error', 'WhatsApp connection failed' . ($reason ? ": {$reason}" : '.')];
                } elseif (!$result['ok']) {
                    $notice = ['error', $result['error']];
                } elseif ($settings->is_connected) {
                    $notice = ['success', 'WhatsApp connected successfully.'];
                } else {
                    $notice = ['error', 'The connection is still being finalised — click "Refresh status" in a moment.'];
                }
            }

            return view('tenant.whatsapp.gateway-settings', [
                'settings' => $settings,
                'notice'   => $notice,
                'chatbot'  => $this->chatbotChecklist($settings),
            ]);
        }

        return view('tenant.whatsapp.chatbot-settings', compact('settings'));
    }

    // Everything the chatbot needs before it can answer a customer, read live
    // so "the chatbot doesn't reply" points at the one thing that's missing.
    // ok: true = fine, false = blocks replies, null = worth a look.
    private function chatbotChecklist(WhatsappSetting $settings): array
    {
        $tenantId = $this->tenantId();

        $flows        = WhatsappChatbotFlow::where('tenant_id', $tenantId)->where('is_active', true);
        $activeFlows  = (clone $flows)->count();
        $hasDefault   = (clone $flows)->where('is_default', true)->exists();
        $lastReceived = WhatsappLog::where('tenant_id', $tenantId)->where('status', 'received')->latest('id')->first();

        return [
            [
                'ok'    => (bool) $settings->is_connected,
                'label' => 'WhatsApp number connected',
                'hint'  => $settings->is_connected ? ($settings->display_phone_number ?: null) : 'Connect your number above first.',
            ],
            [
                'ok'    => (bool) $settings->chatbot_enabled,
                'label' => 'Chatbot switched on',
                'hint'  => $settings->chatbot_enabled ? null : 'Tick "Enable Chatbot" below and click Save Settings — it is off by default.',
            ],
            [
                'ok'    => $activeFlows === 0 ? false : ($hasDefault ? true : null),
                'label' => 'An active flow to answer with',
                'hint'  => match (true) {
                    $activeFlows === 0 => 'No active flow. Add one (or switch one on) on the Chatbot page.',
                    !$hasDefault       => "{$activeFlows} active, but none is the default — the bot only replies when a message contains a flow's keyword. Mark one flow as Default to answer everything else.",
                    default            => "{$activeFlows} active, including a default flow.",
                },
            ],
            [
                'ok'    => $lastReceived ? true : null,
                'label' => 'Customer messages reaching the CRM',
                'hint'  => $lastReceived
                    ? 'Last one ' . $lastReceived->created_at->diffForHumans() . '.'
                    : 'None received yet. If you already messaged this number, WhatsApp is not reaching the CRM — a platform admin should check Superadmin → WhatsApp Gateway (recent events) and Error Logs.',
            ],
        ];
    }

    // ── Settings — save ───────────────────────────────────────────
    public function saveSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'phone_number_id' => ['nullable', 'string', 'max:100'],
            'waba_id'         => ['nullable', 'string', 'max:100'],
            'chatbot_enabled' => ['nullable', 'boolean'],
        ]);

        $settings = WhatsappSetting::forTenant($this->tenantId());
        $settings->tenant_id = $this->tenantId();

        if ($request->filled('phone_number_id')) $settings->phone_number_id = $request->phone_number_id;
        if ($request->filled('waba_id'))         $settings->waba_id = $request->waba_id;
        if (!$settings->webhook_verify_token)    $settings->webhook_verify_token = Str::random(32);

        $settings->chatbot_enabled  = (bool) $request->chatbot_enabled;
        $settings->save();

        return back()->with('success', 'WhatsApp settings saved successfully.');
    }

    // ── OAuth — Generate QR (authenticated) ──────────────────────
    public function oauthGenerateQr(): JsonResponse
    {
        $appId     = PlatformSetting::get('meta_app_id');
        $appSecret = PlatformSetting::get('meta_app_secret');

        if (!$appId || !$appSecret) {
            return response()->json(['success' => false, 'message' => 'Meta App credentials not configured yet. Please ask your administrator.']);
        }

        $state = Str::random(40);
        cache()->put("wa_oauth_{$state}", [
            'tenant_id'  => $this->tenantId(),
            'app_id'     => $appId,
            'app_secret' => $appSecret,
        ], now()->addMinutes(10));

        return response()->json([
            'success' => true,
            'state'   => $state,
            'url'     => route('whatsapp.oauth.start', ['state' => $state]),
        ]);
    }

    // ── OAuth — Start (public — phone browser) ────────────────────
    public function oauthStart(Request $request): RedirectResponse|Response
    {
        $state = $request->query('state');
        $data  = cache("wa_oauth_{$state}");

        if (!$data) {
            return response('QR code has expired. Please generate a new one in the CRM.', 400);
        }

        $scope = implode(',', [
            'whatsapp_business_management',
            'whatsapp_business_messaging',
            'business_management',
        ]);

        $metaUrl = 'https://www.facebook.com/v26.0/dialog/oauth?' . http_build_query([
            'client_id'     => $data['app_id'],
            'redirect_uri'  => route('whatsapp.oauth.callback'),
            'state'         => $state,
            'scope'         => $scope,
            'response_type' => 'code',
        ]);

        return redirect($metaUrl);
    }

    // ── OAuth — Callback (public — Meta redirects here) ──────────
    public function oauthCallback(Request $request): View|Response
    {
        $state = $request->query('state');
        $code  = $request->query('code');

        if ($request->query('error')) {
            return view('tenant.instagram.oauth_result', [
                'success' => false,
                'message' => $request->query('error_description', 'Authorization denied.'),
            ]);
        }

        $data = cache("wa_oauth_{$state}");
        if (!$data) {
            return view('tenant.instagram.oauth_result', [
                'success' => false,
                'message' => 'QR code expired. Please generate a new one.',
            ]);
        }

        try {
            $callbackUrl = route('whatsapp.oauth.callback');

            $tokenRes = Http::get('https://graph.facebook.com/v26.0/oauth/access_token', [
                'client_id'     => $data['app_id'],
                'client_secret' => $data['app_secret'],
                'redirect_uri'  => $callbackUrl,
                'code'          => $code,
            ])->json();

            if (empty($tokenRes['access_token'])) {
                throw new \Exception($tokenRes['error']['message'] ?? 'Failed to get access token.');
            }

            $longRes = Http::get('https://graph.facebook.com/v26.0/oauth/access_token', [
                'grant_type'        => 'fb_exchange_token',
                'client_id'         => $data['app_id'],
                'client_secret'     => $data['app_secret'],
                'fb_exchange_token' => $tokenRes['access_token'],
            ])->json();

            $longToken = $longRes['access_token'] ?? $tokenRes['access_token'];

            // Get Businesses this user administers
            $businessRes = Http::get('https://graph.facebook.com/v26.0/me/businesses', [
                'access_token' => $longToken,
            ])->json();

            if (empty($businessRes['data'])) {
                throw new \Exception('No Business account found for this Facebook account.');
            }

            // Find the first WhatsApp Business Account (owned or shared) across those businesses
            $wabaId = null;
            foreach ($businessRes['data'] as $business) {
                foreach (['owned_whatsapp_business_accounts', 'client_whatsapp_business_accounts'] as $edge) {
                    $wabaRes = Http::get("https://graph.facebook.com/v26.0/{$business['id']}/{$edge}", [
                        'access_token' => $longToken,
                    ])->json();

                    if (!empty($wabaRes['data'])) {
                        $wabaId = $wabaRes['data'][0]['id'];
                        break 2;
                    }
                }
            }

            if (!$wabaId) {
                throw new \Exception('No WhatsApp Business Account found for this Facebook account.');
            }

            // Get Phone Numbers under this WABA
            $phoneRes = Http::get("https://graph.facebook.com/v26.0/{$wabaId}/phone_numbers", [
                'access_token' => $longToken,
            ])->json();

            if (empty($phoneRes['data'])) {
                throw new \Exception('No phone numbers found in this WhatsApp Business Account.');
            }

            $phoneNumberId = $phoneRes['data'][0]['id'];

            // Register the number on Cloud API — required before a freshly
            // created (Embedded Signup) number can send/receive via the API.
            // Benign no-op if it's already registered (existing WABA case).
            $pin = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $registerRes = Http::withToken($longToken)
                ->post("https://graph.facebook.com/v26.0/{$phoneNumberId}/register", [
                    'messaging_product' => 'whatsapp',
                    'pin'               => $pin,
                ])->json();
            $registerError = $registerRes['error']['message'] ?? null;
            $registeredNow = !$registerError;

            // Save to DB
            $settings = WhatsappSetting::firstOrNew(['tenant_id' => $data['tenant_id']]);
            $settings->tenant_id      = $data['tenant_id'];
            $settings->connection_mode = 'direct'; // a direct Meta connection replaces any earlier gateway link
            $settings->access_token   = $longToken;
            $settings->waba_id        = $wabaId;
            $settings->phone_number_id= $phoneNumberId;
            $settings->display_phone_number = $phoneRes['data'][0]['display_phone_number'] ?? null;
            $settings->verified_name        = $phoneRes['data'][0]['verified_name'] ?? null;
            if ($registeredNow) {
                $settings->registration_pin = $pin;
            }
            $settings->is_connected   = true;
            if (!$settings->webhook_verify_token) {
                $settings->webhook_verify_token = Str::random(32);
            }
            $settings->save();

            // Subscribe our app to this WABA so Meta actually sends webhook events for it
            Http::post("https://graph.facebook.com/v26.0/{$wabaId}/subscribed_apps", [
                'access_token' => $longToken,
            ]);

            cache()->put("wa_oauth_done_{$state}", true, now()->addMinutes(5));
            cache()->forget("wa_oauth_{$state}");

            return view('tenant.instagram.oauth_result', [
                'success' => true,
                'message' => 'WhatsApp Business account connected! You can close this window.',
            ]);
        } catch (\Throwable $e) {
            return view('tenant.instagram.oauth_result', [
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    // ── OAuth — Poll status (authenticated) ───────────────────────
    public function oauthStatus(Request $request): JsonResponse
    {
        $state = $request->query('state');

        if (cache("wa_oauth_done_{$state}")) {
            $settings = WhatsappSetting::forTenant($this->tenantId());
            return response()->json([
                'connected'       => true,
                'phone_number_id' => $settings->phone_number_id,
                'waba_id'         => $settings->waba_id,
            ]);
        }

        return response()->json(['connected' => false]);
    }

    // ── Settings — test connection ────────────────────────────────
    public function testConnection(): JsonResponse
    {
        try {
            $service = WhatsappChatbotService::forTenant($this->tenantId());
            $info    = $service->getAccountInfo();

            if (!$info) {
                return response()->json(['success' => false, 'message' => 'Connection failed. Check credentials.']);
            }

            WhatsappSetting::where('tenant_id', $this->tenantId())
                ->update([
                    'is_connected'          => true,
                    'display_phone_number'  => $info['display_phone_number'] ?? null,
                    'verified_name'         => $info['verified_name'] ?? null,
                ]);

            return response()->json(['success' => true, 'account' => $info]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ── Chatbot Flows — list ──────────────────────────────────────
    public function flows(): View
    {
        $settings = WhatsappSetting::forTenant($this->tenantId());

        $flows = WhatsappChatbotFlow::where('tenant_id', $this->tenantId())
            ->orderBy('sort_order')->orderBy('name')->get();

        $sessions = WhatsappChatbotSession::where('tenant_id', $this->tenantId())
            ->latest('last_message_at')->limit(10)->get();

        return view('tenant.whatsapp.chatbot', compact('settings', 'flows', 'sessions'));
    }

    // ── Recent Conversations — full list ────────────────────────────
    public function conversations(Request $request): View
    {
        $sessions = WhatsappChatbotSession::where('tenant_id', $this->tenantId())
            ->with('flow')
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = $request->input('search');
                $q->where(function ($q) use ($term) {
                    $q->where('wa_id', 'like', "%{$term}%")
                      ->orWhere('contact_name', 'like', "%{$term}%");
                });
            })
            ->latest('last_message_at')
            ->paginate(25)
            ->withQueryString();

        return view('tenant.whatsapp.conversations', compact('sessions'));
    }

    // ── Chatbot Flows — store ─────────────────────────────────────
    public function storeFlow(Request $request): RedirectResponse
    {
        $request->validate($this->flowRules());

        $flow = WhatsappChatbotFlow::create(['tenant_id' => $this->tenantId(), 'is_active' => true] + $this->flowAttributes($request));
        $this->keepOneWelcome($flow);

        return back()->with('success', 'Chatbot flow created.');
    }

    // ── Chatbot Flows — update ────────────────────────────────────
    public function updateFlow(Request $request, int $id): RedirectResponse
    {
        $flow = WhatsappChatbotFlow::where('id', $id)
            ->where('tenant_id', $this->tenantId())->firstOrFail();

        $request->validate($this->flowRules());

        $flow->update($this->flowAttributes($request));
        $this->keepOneWelcome($flow);

        return back()->with('success', 'Chatbot flow updated.');
    }

    // ── Chatbot Flows — duplicate ─────────────────────────────────
    // A copy starts switched off and is neither default nor welcome, so
    // duplicating a live flow never changes what customers get.
    public function duplicateFlow(int $id): RedirectResponse
    {
        $flow = WhatsappChatbotFlow::where('id', $id)
            ->where('tenant_id', $this->tenantId())->firstOrFail();

        $copy = $flow->replicate(['triggered_count', 'canvas_x', 'canvas_y']);
        $copy->name       = Str::limit($flow->name, 240, '') . ' (copy)';
        $copy->is_default = false;
        $copy->is_welcome = false;
        $copy->is_active  = false;
        $copy->save();

        return back()->with('success', 'Flow duplicated — switch it on when it is ready.');
    }

    // ── Chatbot Flows — "which flow would answer this message?" ────
    // Uses the very same matching the live bot uses, so the admin can try
    // wording (typos, Hindi, a long sentence) without messaging the number.
    public function testFlow(Request $request): JsonResponse
    {
        $request->validate(['text' => ['required', 'string', 'max:500']]);

        $flows = WhatsappChatbotFlow::where('tenant_id', $this->tenantId())
            ->where('is_active', true)->orderBy('sort_order')->get();

        $best = WhatsappChatbotFlow::best($flows, $request->text);

        if ($best) {
            return response()->json(['flow' => $best['flow']->name, 'why' => 'Keyword match', 'type' => $best['flow']->effectiveReplyType()]);
        }

        $settings = WhatsappSetting::forTenant($this->tenantId());

        if (!$settings->isOpenNow()) {
            return response()->json(['flow' => null, 'why' => 'No keyword matched and you are outside business hours — the after-hours message is sent.']);
        }

        $welcome = $flows->firstWhere('is_welcome', true);
        $default = $flows->firstWhere('is_default', true);

        if ($welcome) {
            return response()->json(['flow' => $welcome->name, 'why' => 'No keyword matched — a new customer gets the welcome flow' . ($default ? " (a returning one gets “{$default->name}”)" : ' (a returning one gets no reply)'), 'type' => $welcome->effectiveReplyType()]);
        }

        return $default
            ? response()->json(['flow' => $default->name, 'why' => 'No keyword matched — the default flow answers', 'type' => $default->effectiveReplyType()])
            : response()->json(['flow' => null, 'why' => 'No keyword matched and there is no default flow — the bot stays silent.']);
    }

    // ── Business hours + after-hours message ──────────────────────
    public function saveHours(Request $request): RedirectResponse
    {
        $request->validate([
            'hours_enabled'       => ['nullable', 'boolean'],
            'hours_start'         => ['required_if:hours_enabled,1', 'nullable', 'date_format:H:i'],
            'hours_end'           => ['required_if:hours_enabled,1', 'nullable', 'date_format:H:i'],
            'hours_days'          => ['nullable', 'array'],
            'hours_days.*'        => ['integer', 'between:0,6'],
            'after_hours_message' => ['nullable', 'string', 'max:1000'],
        ]);

        $settings = WhatsappSetting::forTenant($this->tenantId());
        $settings->tenant_id           = $this->tenantId();
        $settings->hours_enabled       = (bool) $request->hours_enabled;
        $settings->hours_start         = $request->input('hours_start') ?: '09:00';
        $settings->hours_end           = $request->input('hours_end') ?: '18:00';
        $settings->hours_days          = array_values(array_map('intval', $request->input('hours_days', []))) ?: [1, 2, 3, 4, 5];
        $settings->after_hours_message = $request->input('after_hours_message');
        if (!$settings->webhook_verify_token) $settings->webhook_verify_token = Str::random(32);
        $settings->save();

        return back()->with('success', 'Business hours saved.');
    }

    private function flowRules(): array
    {
        return [
            'name'                         => ['required', 'string', 'max:255'],
            'trigger_keywords'             => ['required_without_all:is_default,is_welcome', 'nullable', 'string'],
            'keyword_match'                => ['required', 'in:any,exact,contains'],
            'response_message'             => ['required', 'string', 'max:4096'],
            'action'                       => ['nullable', 'in:loyalty_join,loyalty_balance,book_appointment,raise_ticket'],
            'is_default'                   => ['nullable'],
            'is_welcome'                   => ['nullable'],
            'reply_type'                   => ['nullable', 'in:' . implode(',', WhatsappChatbotFlow::REPLY_TYPES)],
            'header_text'                  => ['nullable', 'string', 'max:60'],
            'footer_text'                  => ['nullable', 'string', 'max:60'],
            'list_button_text'             => ['nullable', 'string', 'max:20'],
            'media_type'                   => ['nullable', 'in:' . implode(',', WhatsappChatbotFlow::MEDIA_TYPES)],
            'media_url'                    => ['nullable', 'url:https', 'max:500'],
            'quick_replies'                => ['nullable', 'array', 'max:10'],
            'quick_replies.*.title'        => ['nullable', 'string', 'max:24'],
            'quick_replies.*.description'  => ['nullable', 'string', 'max:72'],
            'quick_replies.*.url'          => ['nullable', 'url:https', 'max:500'],
            'quick_replies.*.next_flow_id' => ['nullable', 'integer'],
        ];
    }

    private function flowAttributes(Request $request): array
    {
        $type     = $request->input('reply_type') ?: null;
        $mediaUrl = $request->input('media_url') ?: null;

        return [
            'name'             => $request->name,
            'trigger_keywords' => array_values(array_filter(array_map('trim', explode(',', (string) $request->trigger_keywords)), 'strlen')),
            'keyword_match'    => $request->keyword_match,
            'response_message' => $request->response_message,
            'action'           => $request->action ?: null,
            'reply_type'       => $type,
            'header_text'      => $request->input('header_text') ?: null,
            'footer_text'      => $request->input('footer_text') ?: null,
            'list_button_text' => $request->input('list_button_text') ?: null,
            'media_url'        => $mediaUrl,
            'media_type'       => $mediaUrl ? ($request->input('media_type') ?: $this->mediaTypeFromUrl($mediaUrl)) : null,
            'quick_replies'    => $this->buildQuickReplies($request, $type),
            'is_default'       => (bool) $request->is_default,
            'is_welcome'       => (bool) $request->is_welcome,
        ];
    }

    // Only one welcome flow per tenant — saving a new one retires the old.
    private function keepOneWelcome(WhatsappChatbotFlow $flow): void
    {
        if ($flow->is_welcome) {
            WhatsappChatbotFlow::where('tenant_id', $this->tenantId())
                ->where('id', '!=', $flow->id)->update(['is_welcome' => false]);
        }
    }

    private function mediaTypeFromUrl(string $url): string
    {
        $ext = strtolower(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));

        return match (true) {
            in_array($ext, ['mp4', '3gp', 'mov'], true) => 'video',
            in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true) => 'image',
            default => 'document',
        };
    }

    // ── Chatbot Flows — build quick_replies payload ────────────────
    // What the rows mean depends on the reply type: ≤ 3 buttons, ≤ 10 list
    // rows (with a description), or one URL button. Drops empty rows and
    // strips any next_flow_id that doesn't belong to this tenant (defends
    // against a tampered / stale form submission).
    private function buildQuickReplies(Request $request, ?string $type): ?array
    {
        $rows = $request->input('quick_replies', []);
        if ($type === 'text' || !is_array($rows) || empty($rows)) return null;

        $validFlowIds = WhatsappChatbotFlow::where('tenant_id', $this->tenantId())->pluck('id')->all();

        $limit = match ($type) { 'list' => 10, 'link' => 1, default => 3 };
        $max   = $type === 'list' ? 24 : 20;

        $quickReplies = [];
        foreach ($rows as $row) {
            $title = trim((string) ($row['title'] ?? ''));
            if ($title === '') continue;

            if ($type === 'link') {
                $url = trim((string) ($row['url'] ?? ''));
                if ($url === '') continue;
                $quickReplies[] = ['title' => mb_substr($title, 0, 20), 'url' => $url];
                continue;
            }

            $nextFlowId = $row['next_flow_id'] ?? null;
            $nextFlowId = ($nextFlowId && in_array((int) $nextFlowId, $validFlowIds, true)) ? (int) $nextFlowId : null;

            $item = ['title' => mb_substr($title, 0, $max), 'next_flow_id' => $nextFlowId];
            if ($type === 'list' && filled($row['description'] ?? null)) {
                $item['description'] = mb_substr(trim((string) $row['description']), 0, 72);
            }

            $quickReplies[] = $item;
            if (count($quickReplies) >= $limit) break;
        }

        return $quickReplies ?: null;
    }

    // ── Chatbot Flows — canvas position (drag save) ────────────────
    public function updateFlowPosition(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'canvas_x' => ['required', 'integer'],
            'canvas_y' => ['required', 'integer'],
        ]);

        WhatsappChatbotFlow::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->update(['canvas_x' => $request->canvas_x, 'canvas_y' => $request->canvas_y]);

        return response()->json(['success' => true]);
    }

    // ── Chatbot Flows — toggle ────────────────────────────────────
    public function toggleFlow(int $id): JsonResponse
    {
        $flow = WhatsappChatbotFlow::where('id', $id)
            ->where('tenant_id', $this->tenantId())->firstOrFail();

        $flow->update(['is_active' => !$flow->is_active]);

        return response()->json(['is_active' => $flow->is_active]);
    }

    // ── Chatbot Flows — delete ────────────────────────────────────
    public function destroyFlow(int $id): RedirectResponse
    {
        WhatsappChatbotFlow::where('id', $id)
            ->where('tenant_id', $this->tenantId())->delete();

        return back()->with('success', 'Chatbot flow deleted.');
    }
}
