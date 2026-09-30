<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\InstagramAutomation;
use App\Models\InstagramChatbotFlow;
use App\Models\InstagramLog;
use App\Models\InstagramSetting;
use App\Models\PlatformSetting;
use App\Services\InstagramService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InstagramController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    // ── Dashboard ─────────────────────────────────────────────────
    public function index(): View
    {
        $settings = InstagramSetting::forTenant($this->tenantId());

        $stats = [
            'automations'  => InstagramAutomation::where('tenant_id', $this->tenantId())->count(),
            'active_auto'  => InstagramAutomation::where('tenant_id', $this->tenantId())->where('is_active', true)->count(),
            'chatbot_flows'=> InstagramChatbotFlow::where('tenant_id', $this->tenantId())->count(),
            'total_logs'   => InstagramLog::where('tenant_id', $this->tenantId())->count(),
            'today_logs'   => InstagramLog::where('tenant_id', $this->tenantId())->whereDate('created_at', today())->count(),
        ];

        $recentLogs = InstagramLog::where('tenant_id', $this->tenantId())
            ->latest()->limit(10)->get();

        $accountInfo = $this->fetchAccountInfo($settings);

        return view('tenant.instagram.index', compact('settings', 'stats', 'recentLogs', 'accountInfo'));
    }

    // ── Settings — show ───────────────────────────────────────────
    public function settings(): View
    {
        $settings = InstagramSetting::forTenant($this->tenantId());
        $accountInfo = $this->fetchAccountInfo($settings);

        return view('tenant.instagram.settings', compact('settings', 'accountInfo'));
    }

    // ── Fetch the connected account's username/profile so tenant admins
    //    can see WHICH Instagram account is linked, not just a numeric ID ──
    private function fetchAccountInfo(InstagramSetting $settings): ?array
    {
        if (!$settings->is_connected || !$settings->instagram_account_id) {
            return null;
        }

        try {
            return InstagramService::forTenant($settings->tenant_id)->getAccountInfo();
        } catch (\Throwable $e) {
            return null;
        }
    }

    // ── Settings — save ───────────────────────────────────────────
    public function saveSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'instagram_account_id' => ['nullable', 'string', 'max:100'],
            'page_id'              => ['nullable', 'string', 'max:100'],
        ]);

        $settings = InstagramSetting::forTenant($this->tenantId());
        $settings->tenant_id = $this->tenantId();

        if ($request->filled('instagram_account_id')) $settings->instagram_account_id = $request->instagram_account_id;
        if ($request->filled('page_id'))              $settings->page_id = $request->page_id;
        if (!$settings->webhook_verify_token)         $settings->webhook_verify_token = Str::random(32);

        $settings->save();

        return back()->with('success', 'Instagram settings saved successfully.');
    }

    // ── Settings — test connection ────────────────────────────────
    public function testConnection(): JsonResponse
    {
        try {
            $service = InstagramService::forTenant($this->tenantId());
            $info    = $service->getAccountInfo();

            if (!$info) {
                return response()->json(['success' => false, 'message' => 'Could not connect. Check credentials.']);
            }

            InstagramSetting::where('tenant_id', $this->tenantId())
                ->update(['is_connected' => true]);

            // Re-subscribe on every test — cheap, idempotent, and repairs
            // connections that were made before webhook subscription existed.
            $subscription = $service->subscribeWebhookDetailed();

            return response()->json([
                'success'      => true,
                'account'      => $info,
                'subscription' => $subscription,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // ── Automations — list ────────────────────────────────────────
    public function automations(): View
    {
        $automations = InstagramAutomation::where('tenant_id', $this->tenantId())
            ->orderByDesc('is_active')->orderBy('name')->paginate(15);

        return view('tenant.instagram.automations.index', compact('automations'));
    }

    // ── Automations — create form ─────────────────────────────────
    public function createAutomation(): View
    {
        return view('tenant.instagram.automations.create');
    }

    // ── Automations — store ───────────────────────────────────────
    public function storeAutomation(Request $request): RedirectResponse
    {
        $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'trigger_type'    => ['required', 'in:any_post_comment,specific_post_comment,dm_keyword'],
            'post_id'         => ['nullable', 'string', 'max:100'],
            'trigger_keywords'=> ['nullable', 'string'],
            'keyword_match'   => ['required', 'in:any,exact,contains'],
            'action_type'     => ['required', 'in:send_dm,reply_comment'],
            'dm_message'      => ['nullable', 'string', 'max:1000'],
            'comment_reply'   => ['nullable', 'string', 'max:1000'],
        ]);

        $keywords = $request->filled('trigger_keywords')
            ? array_map('trim', explode(',', $request->trigger_keywords))
            : [];

        InstagramAutomation::create([
            'tenant_id'        => $this->tenantId(),
            'name'             => $request->name,
            'trigger_type'     => $request->trigger_type,
            'post_id'          => $request->post_id,
            'trigger_keywords' => $keywords,
            'keyword_match'    => $request->keyword_match,
            'action_type'      => $request->action_type,
            'dm_message'       => $request->dm_message,
            'comment_reply'    => $request->comment_reply,
            'is_active'        => true,
        ]);

        return redirect()->route('tenant.instagram.automations')
            ->with('success', 'Automation created successfully.');
    }

    // ── Automations — fetch recent posts for the picker ───────────
    public function fetchPosts(): JsonResponse
    {
        try {
            $posts = InstagramService::forTenant($this->tenantId())->getRecentMedia();
            return response()->json(['success' => true, 'posts' => $posts]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'posts' => [], 'message' => $e->getMessage()]);
        }
    }

    // ── Automations — edit ────────────────────────────────────────
    public function editAutomation(int $id): View
    {
        $automation = InstagramAutomation::where('id', $id)
            ->where('tenant_id', $this->tenantId())->firstOrFail();

        return view('tenant.instagram.automations.edit', compact('automation'));
    }

    // ── Automations — update ──────────────────────────────────────
    public function updateAutomation(Request $request, int $id): RedirectResponse
    {
        $automation = InstagramAutomation::where('id', $id)
            ->where('tenant_id', $this->tenantId())->firstOrFail();

        $request->validate([
            'name'            => ['required', 'string', 'max:255'],
            'trigger_type'    => ['required', 'in:any_post_comment,specific_post_comment,dm_keyword'],
            'post_id'         => ['nullable', 'string'],
            'trigger_keywords'=> ['nullable', 'string'],
            'keyword_match'   => ['required', 'in:any,exact,contains'],
            'action_type'     => ['required', 'in:send_dm,reply_comment'],
            'dm_message'      => ['nullable', 'string'],
            'comment_reply'   => ['nullable', 'string'],
        ]);

        $keywords = $request->filled('trigger_keywords')
            ? array_map('trim', explode(',', $request->trigger_keywords))
            : [];

        $automation->update([
            'name'             => $request->name,
            'trigger_type'     => $request->trigger_type,
            'post_id'          => $request->post_id,
            'trigger_keywords' => $keywords,
            'keyword_match'    => $request->keyword_match,
            'action_type'      => $request->action_type,
            'dm_message'       => $request->dm_message,
            'comment_reply'    => $request->comment_reply,
        ]);

        return redirect()->route('tenant.instagram.automations')
            ->with('success', 'Automation updated.');
    }

    // ── Automations — toggle ──────────────────────────────────────
    public function toggleAutomation(int $id): JsonResponse
    {
        $automation = InstagramAutomation::where('id', $id)
            ->where('tenant_id', $this->tenantId())->firstOrFail();

        $automation->update(['is_active' => !$automation->is_active]);

        return response()->json(['is_active' => $automation->is_active]);
    }

    // ── Automations — delete ──────────────────────────────────────
    public function destroyAutomation(int $id): RedirectResponse
    {
        InstagramAutomation::where('id', $id)
            ->where('tenant_id', $this->tenantId())->delete();

        return back()->with('success', 'Automation deleted.');
    }

    // ── Chatbot Flows — list ──────────────────────────────────────
    public function chatbot(): View
    {
        $flows = InstagramChatbotFlow::where('tenant_id', $this->tenantId())
            ->orderBy('sort_order')->orderBy('name')->paginate(15);

        return view('tenant.instagram.chatbot.index', compact('flows'));
    }

    // ── Chatbot Flows — store ─────────────────────────────────────
    public function storeChatbotFlow(Request $request): RedirectResponse
    {
        $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'trigger_keywords' => ['required', 'string'],
            'keyword_match'    => ['required', 'in:any,exact,contains'],
            'response_message' => ['required', 'string', 'max:2000'],
            'is_default'       => ['nullable', 'boolean'],
        ]);

        $keywords = array_map('trim', explode(',', $request->trigger_keywords));

        InstagramChatbotFlow::create([
            'tenant_id'        => $this->tenantId(),
            'name'             => $request->name,
            'trigger_keywords' => $keywords,
            'keyword_match'    => $request->keyword_match,
            'response_message' => $request->response_message,
            'is_default'       => (bool) $request->is_default,
            'is_active'        => true,
        ]);

        return back()->with('success', 'Chatbot flow created.');
    }

    // ── Chatbot Flows — update ────────────────────────────────────
    public function updateChatbotFlow(Request $request, int $id): RedirectResponse
    {
        $flow = InstagramChatbotFlow::where('id', $id)
            ->where('tenant_id', $this->tenantId())->firstOrFail();

        $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'trigger_keywords' => ['required', 'string'],
            'keyword_match'    => ['required', 'in:any,exact,contains'],
            'response_message' => ['required', 'string', 'max:2000'],
            'is_default'       => ['nullable', 'boolean'],
        ]);

        $keywords = array_map('trim', explode(',', $request->trigger_keywords));

        $flow->update([
            'name'             => $request->name,
            'trigger_keywords' => $keywords,
            'keyword_match'    => $request->keyword_match,
            'response_message' => $request->response_message,
            'is_default'       => (bool) $request->is_default,
        ]);

        return back()->with('success', 'Chatbot flow updated.');
    }

    // ── Chatbot Flows — toggle ────────────────────────────────────
    public function toggleChatbotFlow(int $id): JsonResponse
    {
        $flow = InstagramChatbotFlow::where('id', $id)
            ->where('tenant_id', $this->tenantId())->firstOrFail();

        $flow->update(['is_active' => !$flow->is_active]);

        return response()->json(['is_active' => $flow->is_active]);
    }

    // ── Chatbot Flows — delete ────────────────────────────────────
    public function destroyChatbotFlow(int $id): RedirectResponse
    {
        InstagramChatbotFlow::where('id', $id)
            ->where('tenant_id', $this->tenantId())->delete();

        return back()->with('success', 'Chatbot flow deleted.');
    }

    // ── Central Meta App credentials for the Instagram Login flow ──
    //    Instagram API "with Instagram Login" uses its own App ID / Secret
    //    (Meta App → Instagram → API setup with Instagram login). Falls back
    //    to the Facebook app credentials when the IG-specific pair is blank.
    private function igCredentials(): array
    {
        return [
            PlatformSetting::get('meta_ig_app_id')     ?: PlatformSetting::get('meta_app_id'),
            PlatformSetting::get('meta_ig_app_secret') ?: PlatformSetting::get('meta_app_secret'),
        ];
    }

    // ── OAuth — Generate QR (authenticated) ──────────────────────
    public function oauthGenerateQr(): JsonResponse
    {
        [$appId, $appSecret] = $this->igCredentials();

        if (!$appId || !$appSecret) {
            return response()->json(['success' => false, 'message' => 'Instagram App credentials not configured yet. Please ask your administrator.']);
        }

        // Cryptographically-random, short-lived, single-use state. No secrets
        // live in the payload — the callback re-reads them server-side.
        $state = Str::random(40);
        cache()->put("ig_oauth_state_{$state}", [
            'tenant_id' => $this->tenantId(),
            'used'      => false,
        ], now()->addMinutes(10));

        return response()->json([
            'success' => true,
            'state'   => $state,
            'url'     => route('instagram.oauth.start', ['state' => $state]),
        ]);
    }

    // ── OAuth — Start (public — phone browser) ────────────────────
    public function oauthStart(Request $request): RedirectResponse|Response
    {
        $state = (string) $request->query('state');
        $data  = cache("ig_oauth_state_{$state}");

        if (!$data || !empty($data['used'])) {
            return response('This QR flow was already used or has expired. Please generate a new one in the CRM.', 400);
        }

        [$appId] = $this->igCredentials();

        if (!$appId) {
            return response('Instagram is not configured yet. Please contact your administrator.', 500);
        }

        $scope = implode(',', [
            'instagram_business_basic',
            'instagram_business_manage_messages',
            'instagram_business_manage_comments',
        ]);

        // Instagram API with Instagram Login (Instagram Business Login) —
        // deliberately NOT facebook.com/dialog/oauth (the Page-based flow).
        $authUrl = 'https://www.instagram.com/oauth/authorize?' . http_build_query([
            'client_id'     => $appId,
            'redirect_uri'  => route('instagram.oauth.callback'),
            'response_type' => 'code',
            'scope'         => $scope,
            'state'         => $state,
        ]);

        return redirect()->away($authUrl);
    }

    // ── Deauthorize Callback (public — Meta POSTs here when a user
    //    revokes the app from their Instagram account) ─────────────
    public function deauthorize(Request $request): JsonResponse
    {
        [, $appSecret] = $this->igCredentials();
        $data = $appSecret ? $this->parseSignedRequest((string) $request->input('signed_request'), $appSecret) : null;
        $igUserId = $data['user_id'] ?? null;

        if ($igUserId) {
            $setting = InstagramSetting::where('instagram_account_id', $igUserId)->first();

            if ($setting) {
                InstagramLog::create([
                    'tenant_id'         => $setting->tenant_id,
                    'event_type'        => 'oauth_deauthorize',
                    'instagram_user_id' => $igUserId,
                    'status'            => 'success',
                    'raw_payload'       => $data,
                ]);

                $setting->update([
                    'is_connected'     => false,
                    'access_token'     => null,
                    'token_expires_at' => null,
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

    // ── Data Deletion Request (public — Meta POSTs here when a user
    //    requests their data be deleted). Must respond with a status-
    //    check URL + confirmation code per Meta's spec. ─────────────
    public function dataDeletion(Request $request): JsonResponse
    {
        [, $appSecret] = $this->igCredentials();
        $data = $appSecret ? $this->parseSignedRequest((string) $request->input('signed_request'), $appSecret) : null;
        $igUserId = $data['user_id'] ?? null;
        $confirmationCode = Str::random(24);

        if ($igUserId) {
            $setting = InstagramSetting::where('instagram_account_id', $igUserId)->first();

            if ($setting) {
                InstagramLog::create([
                    'tenant_id'         => $setting->tenant_id,
                    'event_type'        => 'data_deletion_request',
                    'instagram_user_id' => $igUserId,
                    'status'            => 'success',
                    'raw_payload'       => ($data ?? []) + ['confirmation_code' => $confirmationCode],
                ]);

                // Purge the stored connection — token, account id, page id.
                // Automations/chatbot flow configs are the tenant's own CRM
                // data (not Instagram's), so they are left intact.
                $setting->update([
                    'is_connected'         => false,
                    'access_token'         => null,
                    'instagram_account_id' => null,
                    'page_id'              => null,
                    'token_expires_at'     => null,
                ]);
            }
        }

        cache()->put("ig_data_deletion_{$confirmationCode}", true, now()->addDays(30));

        return response()->json([
            'url'               => route('instagram.data-deletion.status', ['code' => $confirmationCode]),
            'confirmation_code' => $confirmationCode,
        ]);
    }

    // ── Data Deletion — status check page Meta links users to ──────
    public function dataDeletionStatus(string $code): View
    {
        $done = (bool) cache("ig_data_deletion_{$code}");

        return view('tenant.instagram.data_deletion_status', [
            'code' => $code,
            'done' => $done,
        ]);
    }

    // ── Verify + decode a Meta "signed_request" payload (used by both
    //    the deauthorize and data-deletion callbacks) ────────────────
    private function parseSignedRequest(string $signedRequest, string $appSecret): ?array
    {
        $parts = explode('.', $signedRequest, 2);

        if (count($parts) !== 2) {
            return null;
        }

        [$encodedSig, $encodedPayload] = $parts;

        $sig     = $this->base64UrlDecode($encodedSig);
        $payload = json_decode($this->base64UrlDecode($encodedPayload), true);

        if (!is_array($payload)) {
            return null;
        }

        $expectedSig = hash_hmac('sha256', $encodedPayload, $appSecret, true);

        if (!hash_equals($expectedSig, $sig)) {
            return null;
        }

        return $payload;
    }

    private function base64UrlDecode(string $input): string
    {
        return base64_decode(strtr($input, '-_', '+/')) ?: '';
    }

    // ── OAuth — Callback (public — Meta redirects here) ──────────
    public function oauthCallback(Request $request): View|Response
    {
        $state = (string) $request->query('state');
        $code  = (string) $request->query('code');
        $data  = cache("ig_oauth_state_{$state}");

        if ($request->query('error')) {
            $message = $request->query('error_description', 'Authorization denied.');
            $this->logOauthFailure($data['tenant_id'] ?? null, $message, $request->query(), $state);
            cache()->forget("ig_oauth_state_{$state}");

            return view('tenant.instagram.oauth_result', [
                'success' => false,
                'message' => $message,
            ]);
        }

        if (!$data || !empty($data['used'])) {
            $this->logOauthFailure(null, 'QR code expired or state not found when Meta redirected back.', ['state' => $state], $state);
            cache()->forget("ig_oauth_state_{$state}");

            return view('tenant.instagram.oauth_result', [
                'success' => false,
                'message' => 'QR code expired. Please generate a new one.',
            ]);
        }

        $debugContext = ['state' => $state];

        try {
            [$appId, $appSecret] = $this->igCredentials();
            if (!$appId || !$appSecret) {
                throw new \Exception('Instagram App credentials are not configured on the server.');
            }

            if (!$code) {
                throw new \Exception('Instagram redirected back without an authorization code.');
            }

            $callbackUrl = route('instagram.oauth.callback');
            $data['used'] = true;
            cache()->put("ig_oauth_state_{$state}", $data, now()->addMinutes(5));

            // 1) Authorization code → short-lived Instagram User access token
            $shortRes = Http::asForm()->post('https://api.instagram.com/oauth/access_token', [
                'client_id'     => $appId,
                'client_secret' => $appSecret,
                'grant_type'    => 'authorization_code',
                'redirect_uri'  => $callbackUrl,
                'code'          => $code,
            ]);

            $shortJson = $shortRes->json();

            if (!$shortRes->successful() || empty($shortJson['access_token'])) {
                throw new \Exception($shortJson['error_message'] ?? $shortJson['error']['message'] ?? ('Failed to exchange authorization code (HTTP ' . $shortRes->status() . ').'));
            }

            $shortToken = $shortJson['access_token'];
            $igUserId   = (string) ($shortJson['user_id'] ?? '');
            // Keeps user_id + granted permissions (tokens are stripped) so a failed
            // connect can be diagnosed from the Logs page.
            $debugContext['short_token_exchange'] = $this->redactTokens($shortJson);

            // 2) Short-lived → long-lived (~60 day) token
            $longRes = Http::get('https://graph.instagram.com/access_token', [
                'grant_type'    => 'ig_exchange_token',
                'client_secret' => $appSecret,
                'access_token'  => $shortToken,
            ]);
            $longJson = $longRes->json();
            $debugContext['long_token_exchange'] = $this->redactTokens($longJson);

            // Don't silently fall back to the short-lived token — if Instagram
            // refuses the exchange, every later call would fail the same way.
            if (!$longRes->successful() || empty($longJson['access_token'])) {
                throw new \Exception($this->friendlyInstagramError($longJson['error']['message'] ?? null, $longRes->status()));
            }

            $longToken = $longJson['access_token'];
            $expiresIn = $longJson['expires_in'] ?? null;

            // 3) Load the authorized Instagram Professional account.
            //    Instagram Login exposes two IDs: user_id (IGSID, used as the
            //    messaging sender/recipient id) and id (app-scoped). Meta's
            //    webhook payloads key entry.id on either shape depending on the
            //    event, so we keep BOTH and match on either at delivery time.
            $accountRes = Http::get('https://graph.instagram.com/v23.0/me', [
                'fields'       => 'user_id,id,username,account_type',
                'access_token' => $longToken,
            ]);

            $accountJson = $accountRes->json();
            $debugContext['account_response'] = $this->redactTokens($accountJson);

            $igUserId  = (string) ($accountJson['user_id'] ?? $igUserId);
            $igAppId   = (string) ($accountJson['id'] ?? '');

            if (!$accountRes->successful() || $igUserId === '') {
                throw new \Exception($this->friendlyInstagramError($accountJson['error']['message'] ?? null, $accountRes->status()));
            }

            $subscription = $this->persistInstagramConnection($data['tenant_id'], [
                'access_token'         => $longToken,
                'instagram_account_id' => $igUserId,
                'secondary_id'         => $igAppId !== '' && $igAppId !== $igUserId ? $igAppId : null,
                'username'             => $accountJson['username'] ?? null,
                'account_type'         => $accountJson['account_type'] ?? null,
                'expires_in'           => $expiresIn,
            ]);
            $debugContext['webhook_subscription'] = $this->redactTokens($subscription);

            cache()->put("ig_oauth_done_{$state}", true, now()->addMinutes(5));
            cache()->forget("ig_oauth_state_{$state}");

            $this->logOauthSuccess($data['tenant_id'], [
                'username'             => $accountJson['username'] ?? $igUserId,
                'instagram_account_id' => $igUserId,
            ]);

            return view('tenant.instagram.oauth_result', [
                'success' => true,
                'message' => 'Instagram account connected! You can close this window.',
            ]);
        } catch (\Throwable $e) {
            $this->logOauthFailure($data['tenant_id'] ?? null, $e->getMessage(), $debugContext, $state);
            cache()->forget("ig_oauth_state_{$state}");

            return view('tenant.instagram.oauth_result', [
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    // ── Turn Instagram's opaque "Unsupported request" (code 100) into
    //    something the tenant can act on. Instagram returns this for every
    //    call made with a token whose account isn't a usable Professional
    //    account for this app. ─────────────────────────────────────────
    private function friendlyInstagramError(?string $apiMessage, int $status): string
    {
        if ($apiMessage && stripos($apiMessage, 'Unsupported request') !== false) {
            return 'Instagram rejected this account. Make sure it is an Instagram Professional (Business or Creator) account — '
                . 'in the Instagram app go to Settings → Account type and tools → Switch to professional account — then try again. '
                . 'If the account is already professional, ask your administrator to confirm it is added as an Instagram Tester on the Meta app.';
        }

        return $apiMessage ?: ('Failed to load Instagram account details (HTTP ' . $status . ').');
    }

    // ── OAuth — persist the connected Instagram Professional account ──
    //    Returns the raw webhook-subscription response so the callback can log it.
    private function persistInstagramConnection(int $tenantId, array $chosen): array
    {
        $settings = InstagramSetting::firstOrNew(['tenant_id' => $tenantId]);
        $settings->tenant_id            = $tenantId;
        $settings->access_token         = $chosen['access_token'];
        $settings->instagram_account_id = $chosen['instagram_account_id'];
        // No Facebook Page in the Instagram Login flow — reuse this column to
        // hold the account's secondary (app-scoped) ID so webhook delivery can
        // match on either shape. Null when Meta returns a single ID.
        $settings->page_id              = $chosen['secondary_id'] ?? null;
        $settings->is_connected         = true;
        if (!empty($chosen['expires_in'])) {
            $settings->token_expires_at = now()->addSeconds((int) $chosen['expires_in']);
        }
        if (!$settings->webhook_verify_token) {
            $settings->webhook_verify_token = Str::random(32);
        }
        $settings->save();

        $subscription = InstagramService::forTenant($tenantId)->subscribeWebhookDetailed();

        InstagramLog::create([
            'tenant_id'     => $tenantId,
            'event_type'    => 'oauth_connect',
            'status'        => $subscription['success'] ? 'success' : 'failed',
            'outgoing_text' => 'Webhook subscribe (me/subscribed_apps): ' . ($subscription['success'] ? 'OK' : 'FAILED'),
            'error_message' => $subscription['success'] ? null : json_encode($this->redactTokens($subscription['body'])),
            'raw_payload'   => $this->redactTokens($subscription['body']),
        ]);

        return $subscription;
    }

    // ── Strip access tokens out of a Graph API response before it gets
    //    written to instagram_logs — logs are viewable in the CRM UI and
    //    must never hold live Facebook/Instagram credentials ────────────
    private function redactTokens(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        unset($value['access_token']);

        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = $this->redactTokens($item);
            }
        }

        return $value;
    }

    // ── OAuth — record a connect attempt so it shows up on the Logs page
    //    instead of only flashing on the phone screen for a few seconds ──
    private function logOauthFailure(?int $tenantId, string $message, array $context = [], ?string $state = null): void
    {
        // Let the desktop tab (which is polling oauthStatus) surface the
        // failure within seconds instead of only waiting out the 10-minute
        // countdown with no explanation.
        if ($state) {
            cache()->put("ig_oauth_failed_{$state}", $message, now()->addMinutes(10));
        }

        if (!$tenantId) {
            return;
        }

        InstagramLog::create([
            'tenant_id'     => $tenantId,
            'event_type'    => 'oauth_connect',
            'status'        => 'failed',
            'error_message' => $message,
            'raw_payload'   => $context,
        ]);
    }

    private function logOauthSuccess(int $tenantId, array $account): void
    {
        InstagramLog::create([
            'tenant_id'     => $tenantId,
            'event_type'    => 'oauth_connect',
            'status'        => 'success',
            'outgoing_text' => "Connected Instagram account @{$account['username']} (ig_user_id={$account['instagram_account_id']})",
        ]);
    }

    // ── OAuth — Poll status (authenticated) ───────────────────────
    public function oauthStatus(Request $request): JsonResponse
    {
        $state = $request->query('state');

        if (cache("ig_oauth_done_{$state}")) {
            $settings = InstagramSetting::forTenant($this->tenantId());
            return response()->json([
                'connected'            => true,
                'instagram_account_id' => $settings->instagram_account_id,
                'page_id'              => $settings->page_id,
            ]);
        }

        if ($failMessage = cache("ig_oauth_failed_{$state}")) {
            cache()->forget("ig_oauth_failed_{$state}");

            return response()->json(['connected' => false, 'failed' => true, 'message' => $failMessage]);
        }

        return response()->json(['connected' => false]);
    }

    // ── Guide / How it works ──────────────────────────────────────
    public function guide(): View
    {
        $settings  = InstagramSetting::forTenant($this->tenantId());
        $webhookUrl = url('/webhook/instagram');
        return view('tenant.instagram.guide', compact('settings', 'webhookUrl'));
    }

    // ── Logs ──────────────────────────────────────────────────────
    public function logs(Request $request): View
    {
        $query = InstagramLog::where('tenant_id', $this->tenantId())->latest();

        if ($request->filled('event_type')) $query->where('event_type', $request->event_type);
        if ($request->filled('status'))     $query->where('status', $request->status);
        if ($request->filled('date'))       $query->whereDate('created_at', $request->date);

        $logs = $query->paginate(20)->withQueryString();

        return view('tenant.instagram.logs', compact('logs'));
    }
}
