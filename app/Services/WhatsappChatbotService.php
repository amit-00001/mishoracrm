<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Tenant;
use App\Models\WhatsappChatbotFlow;
use App\Models\WhatsappChatbotSession;
use App\Models\WhatsappLog;
use App\Models\WhatsappSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappChatbotService
{
    private const GRAPH_URL = 'https://graph.facebook.com/v26.0';

    private WhatsappSetting $settings;

    // The real reason the last send*() call failed — Meta's own error
    // message when available, so a failed send in WhatsApp Logs says WHY
    // (expired token, template not approved, number not reachable, etc.)
    // instead of a generic "rejected" string. Null after a successful send.
    public ?string $lastError = null;

    // WhatsApp message id (wamid) of the last message sent through the
    // WhatsApp Gateway — lets callers store it on their log row so the
    // gateway's later delivered / read / failed receipts can be matched back.
    // Stays null for direct-Meta sends and after any failed send.
    public ?string $lastMessageId = null;

    public function __construct(WhatsappSetting $settings)
    {
        $this->settings = $settings;
    }

    public static function forTenant(int $tenantId): self
    {
        $settings = WhatsappSetting::where('tenant_id', $tenantId)->firstOrFail();
        return new self($settings);
    }

    // "Scan the QR → send JOIN" welcome capture (§2a). A first-time number that
    // sends the tenant's welcome keyword is auto-enrolled as a Contact and
    // gifted the welcome bonus. Returns true if it handled the message.
    public function handleLoyaltyWelcome(string $waId, string $messageText, ?string $profileName): bool
    {
        $tenant = Tenant::find($this->settings->tenant_id);
        if (!$tenant || !$tenant->hasModuleEnabled('loyalty')) {
            return false;
        }

        $s     = $tenant->loyaltySettings();
        $bonus = (int) $s['welcome_bonus_points'];
        if ($bonus <= 0) {
            return false;
        }

        $keyword = strtolower(trim((string) ($s['welcome_keyword'] ?: 'JOIN')));
        $text    = strtolower(trim($messageText));
        if ($text !== $keyword && !str_starts_with($text, $keyword . ' ')) {
            return false;
        }

        if ($contact = $this->findContactByWaId($tenant, $waId)) {
            // Already known — don't re-gift, just acknowledge.
            return $this->sendAndLog($waId, "You're already a {$tenant->name} member, {$contact->name}! You have "
                . number_format((int) $contact->loyalty_points) . ' points.', $contact->name);
        }

        $contact = $this->enrolFromWhatsapp($tenant, $waId, $profileName);

        $message = strtr($s['welcome_message'] ?: Tenant::LOYALTY_DEFAULTS['welcome_message'], [
            '{{contact_name}}' => $contact->name,
            '{{points}}'       => number_format($bonus),
            '{{tenant_name}}'  => $tenant->name,
        ]);

        return $this->sendAndLog($waId, $message, $contact->name);
    }

    // Match a Contact by the last 10 digits of a WhatsApp id, ignoring
    // spaces / dashes / + in the stored phone.
    public function findContactByWaId(Tenant $tenant, string $waId): ?Contact
    {
        $digits = preg_replace('/\D/', '', $waId);
        $last10 = strlen($digits) >= 10 ? substr($digits, -10) : $digits;

        return Contact::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('phone_normalized', $last10)
            ->first();
    }

    // Create a Contact for a WhatsApp number and grant the welcome bonus (if
    // one is configured). Returns the (possibly pre-existing) Contact.
    public function enrolFromWhatsapp(Tenant $tenant, string $waId, ?string $profileName): Contact
    {
        $contact = $this->findContactByWaId($tenant, $waId);
        if ($contact) {
            return $contact;
        }

        $contact = Contact::create([
            'tenant_id' => $tenant->id,
            'name'      => $profileName ?: 'WhatsApp Customer',
            'phone'     => $waId,
        ]);

        app(\App\Services\CustomerLinkService::class)->attachContactToCustomer($contact);

        $bonus = (int) $tenant->loyaltySettings()['welcome_bonus_points'];
        if ($bonus > 0) {
            app(\App\Services\LoyaltyService::class)->manualAdjust($contact, $bonus, 'Welcome bonus (WhatsApp join)');
        }

        return $contact->refresh();
    }

    // Swap {{loyalty_*}} / {{contact_name}} / {{tenant_name}} placeholders in a
    // chatbot response for the sender's real data. Non-loyalty flows are
    // untouched (no placeholders → no change).
    public function resolveMessage(string $message, ?Tenant $tenant, string $waId): string
    {
        if (!str_contains($message, '{{')) {
            return $message;
        }

        $contact = $tenant ? $this->findContactByWaId($tenant, $waId) : null;
        $s       = $tenant?->loyaltySettings() ?? Tenant::LOYALTY_DEFAULTS;
        $block   = (int) $s['redeem_points_block'];
        $points  = (int) ($contact->loyalty_points ?? 0);
        $value   = $block > 0 ? floor($points / $block) * (float) $s['redeem_value'] : 0;

        return strtr($message, [
            '{{contact_name}}'       => $contact->name ?? 'there',
            '{{tenant_name}}'        => $tenant->name ?? '',
            '{{loyalty_points}}'     => number_format($points),
            '{{loyalty_lifetime}}'   => number_format((int) ($contact->loyalty_lifetime_points ?? 0)),
            '{{loyalty_tier}}'       => $contact?->loyaltyTierLabel() ?? '—',
            '{{loyalty_redeemable}}' => '₹' . number_format($value, 0),
            '{{booking_link}}'       => $tenant?->bookingPublicUrl() ?? '',
            '{{support_link}}'       => $tenant?->supportPublicUrl() ?? '',
        ]);
    }

    // Shared by the "points"/"balance" self-check keyword AND the
    // loyalty_balance flow action — a real, live lookup (not a placeholder
    // fill), so it works even if the admin's flow text has no {{loyalty_*}}
    // tokens in it, and it correctly handles "no account found" either way.
    private function loyaltyBalanceReply(Tenant $tenant, string $waId): string
    {
        $contact = $this->findContactByWaId($tenant, $waId);

        if (!$contact) {
            return "We couldn't find a loyalty account for this number. Please ask our staff to add you on your next visit!";
        }

        $s     = $tenant->loyaltySettings();
        $block = (int) $s['redeem_points_block'];
        $value = $block > 0 ? floor((int) $contact->loyalty_points / $block) * (float) $s['redeem_value'] : 0;
        $tier  = $contact->loyaltyTierLabel();

        return "Hi {$contact->name}! 🎁\n"
            . 'Loyalty points: ' . number_format((int) $contact->loyalty_points) . ($tier ? " ({$tier})" : '') . "\n"
            . ($value > 0
                ? 'Worth up to ₹' . number_format($value, 0) . " off your next bill at {$tenant->name}."
                : "Keep visiting {$tenant->name} to earn rewards!");
    }

    // Loyalty self-check: a customer texts "points" / "balance" / "rewards" and
    // gets their balance back. Independent of the chatbot flow engine — works
    // whenever the tenant has the Loyalty module + settings['loyalty']
    // ['whatsapp_self_check'] on. Returns true if it answered the message.
    public function handleLoyaltyKeyword(string $waId, string $messageText): bool
    {
        $tenant = Tenant::find($this->settings->tenant_id);
        if (!$tenant || !$tenant->hasModuleEnabled('loyalty')) {
            return false;
        }
        if (!($tenant->loyaltySettings()['whatsapp_self_check'] ?? false)) {
            return false;
        }

        $text = strtolower(trim($messageText));
        $hit  = false;
        foreach (['points', 'balance', 'rewards', 'loyalty'] as $kw) {
            if ($text === $kw || str_starts_with($text, $kw . ' ') || str_starts_with($text, 'my ' . $kw)) {
                $hit = true;
                break;
            }
        }
        if (!$hit) {
            return false;
        }

        return $this->sendAndLog($waId, $this->loyaltyBalanceReply($tenant, $waId));
    }

    // Single entry point for every inbound WhatsApp text. Loyalty's built-in
    // replies (self-check + QR "join") run first — they have their own opt-in
    // switches and work even if the tenant hasn't enabled the full chatbot —
    // then the tenant's own chatbot flows.
    // $buttonId is set only when the inbound message is a tap on a quick-reply
    // button (see WhatsappWebhookController) — when that button was explicitly
    // linked to a next flow (via the "next_flow_id" field in Quick Reply
    // Buttons), we jump straight there instead of keyword-matching $messageText.
    public function handleIncomingMessage(string $waId, string $messageText, ?string $contactName = null, ?string $buttonId = null): bool
    {
        $tenant = Tenant::find($this->settings->tenant_id);

        if ($tenant && $tenant->hasModuleEnabled('loyalty')) {
            if ($this->handleLoyaltyWelcome($waId, $messageText, $contactName)) {
                return true;
            }
            if ($this->handleLoyaltyKeyword($waId, $messageText)) {
                return true;
            }
        }

        if (!$this->settings->chatbot_enabled) return false;

        $session = WhatsappChatbotSession::getOrCreate($this->settings->tenant_id, $waId, $contactName);
        $session->update(['last_message_at' => now()]);

        $matchedFlow = null;

        // Direct jump — the tapped button was linked to a specific next flow.
        if ($buttonId && str_starts_with($buttonId, 'flow_')) {
            $matchedFlow = WhatsappChatbotFlow::where('tenant_id', $this->settings->tenant_id)
                ->where('id', (int) substr($buttonId, 5))
                ->where('is_active', true)
                ->first();
        }

        // Otherwise — keyword match against the typed text / button title, same as before.
        if (!$matchedFlow) {
            $flows = WhatsappChatbotFlow::where('tenant_id', $this->settings->tenant_id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('is_default') // non-default first
                ->get();

            foreach ($flows as $flow) {
                if (!$flow->is_default && $flow->matches($messageText)) {
                    $matchedFlow = $flow;
                    break;
                }
            }

            // Fallback to default
            if (!$matchedFlow) {
                $matchedFlow = $flows->firstWhere('is_default', true);
            }
        }

        if (!$matchedFlow) return false;

        $matchedFlow->incrementTriggered();

        $body = $this->resolveMessage($matchedFlow->response_message, $tenant, $waId);

        // Flow-attached actions — each does a real lookup/side-effect rather
        // than trusting the admin got the {{placeholder}} syntax right.
        switch ($matchedFlow->action) {
            case 'loyalty_join':
                if ($tenant && $tenant->hasModuleEnabled('loyalty')) {
                    $this->enrolFromWhatsapp($tenant, $waId, $contactName);
                }
                break;

            case 'loyalty_balance':
                // Live balance lookup replaces whatever static text the admin
                // wrote — reliable even without {{loyalty_*}} placeholders.
                if ($tenant && $tenant->hasModuleEnabled('loyalty')) {
                    $body = $this->loyaltyBalanceReply($tenant, $waId);
                }
                break;

            case 'book_appointment':
                // Real, live-availability booking page — safety net in case
                // the admin's message text doesn't already have the link.
                if ($tenant && $tenant->hasModuleEnabled('appointments') && !str_contains($body, $tenant->bookingPublicUrl())) {
                    $body = rtrim($body) . "\n\n" . $tenant->bookingPublicUrl();
                }
                break;

            case 'raise_ticket':
                if ($tenant && $tenant->hasModuleEnabled('tickets') && !str_contains($body, $tenant->supportPublicUrl())) {
                    $body = rtrim($body) . "\n\n" . $tenant->supportPublicUrl();
                }
                break;
        }

        // Flows with quick-reply buttons configured get sent as an interactive
        // message; every other flow keeps sending plain text exactly as before.
        if (!empty($matchedFlow->quick_replies)) {
            $ok = $this->sendInteractiveButtons($waId, $body, $matchedFlow->quick_replies);
            $this->recordLog($waId, $contactName, $body, $ok);
            return $ok;
        }

        return $this->sendAndLog($waId, $body, $contactName);
    }

    // Sends a plain-text message and records it in WhatsApp Logs — the single
    // choke point for every automated reply (loyalty welcome/self-check,
    // chatbot flows), so a failed auto-reply shows up in Logs with the real
    // reason, not just silently in the server log. Manual sends from the
    // "Send Message" / bulk-send screens log separately (WhatsappController),
    // since those carry extra context (lead/contact/template/sent_by).
    private function sendAndLog(string $waId, string $message, ?string $contactName = null): bool
    {
        $ok = $this->sendMessage($waId, $message);
        $this->recordLog($waId, $contactName, $message, $ok);
        return $ok;
    }

    private function recordLog(string $waId, ?string $contactName, string $message, bool $ok): void
    {
        WhatsappLog::create([
            'tenant_id'     => $this->settings->tenant_id,
            'to_phone'      => $waId,
            'to_name'       => $contactName,
            'message'       => $message,
            'status'        => $ok ? 'sent' : 'failed',
            'error_message' => $ok ? null : $this->lastError,
            'wamid'         => $ok ? $this->lastMessageId : null,
            'sent_at'       => now(),
        ]);
    }

    // Send a message through the WhatsApp Gateway (Milan CRM) instead of
    // Meta directly — used by every send method below when this tenant's
    // number was connected through the gateway. $payload is the gateway's
    // message body without 'to'.
    private function sendViaGateway(string $waId, array $payload): bool
    {
        $result = WhatsappGatewayClient::sendMessage($this->settings->gateway_workspace_id, ['to' => $waId] + $payload);

        if (!$result['ok']) {
            $this->lastError     = $result['error'];
            $this->lastMessageId = null;
            Log::error('WhatsApp gateway message failed', [
                'tenant_id' => $this->settings->tenant_id,
                'to'        => $waId,
                'error'     => $result['error'],
                'meta_code' => $result['meta_code'],
            ]);
            return false;
        }

        $this->lastError     = null;
        $this->lastMessageId = $result['data']['wamid'] ?? null;
        return true;
    }

    // Send WhatsApp message via Cloud API
    public function sendMessage(string $waId, string $message): bool
    {
        if ($this->settings->viaGateway()) {
            return $this->sendViaGateway($waId, ['type' => 'text', 'text' => $message]);
        }

        $response = Http::withToken($this->settings->access_token)
            ->post(self::GRAPH_URL . '/' . $this->settings->phone_number_id . '/messages', [
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $waId,
                'type'              => 'text',
                'text'              => ['body' => $message],
            ]);

        if ($response->failed()) {
            $this->lastError = $this->extractApiError($response);
            Log::error('WhatsApp message failed', [
                'tenant_id' => $this->settings->tenant_id,
                'to'        => $waId,
                'error'     => $response->json(),
            ]);
            return false;
        }

        $this->lastError = null;
        return true;
    }

    // Send an approved Meta message template — the only message type that can
    // start a conversation or reply after the customer's 24-hour window has
    // closed. Gateway connections only; $bodyParams fill {{1}}, {{2}}, … in order.
    public function sendTemplate(string $waId, string $name, string $language, array $bodyParams = []): bool
    {
        if (!$this->settings->viaGateway()) {
            $this->lastError     = 'Template messages are only available through the WhatsApp gateway.';
            $this->lastMessageId = null;
            return false;
        }

        return $this->sendViaGateway($waId, [
            'type'     => 'template',
            'template' => ['name' => $name, 'language' => $language, 'body_params' => array_values($bodyParams)],
        ]);
    }

    // Send a WhatsApp "reply buttons" interactive message — up to 3 tappable
    // options under $body. Each entry in $buttons is ['title' => string,
    // 'next_flow_id' => int|null, 'reply_id' => string|null]. An explicit
    // 'reply_id' is used verbatim (e.g. "qacc_{token}" for a quotation
    // accept/reject button handled directly in WhatsappWebhookController);
    // otherwise a next_flow_id is encoded as flow_{id} so the webhook can
    // jump straight to that flow, and with neither the button title falls
    // back to normal keyword matching, same as typed text (see handleIncomingMessage).
    public function sendInteractiveButtons(string $waId, string $body, array $buttons): bool
    {
        $buttons = array_slice(array_values(array_filter(
            $buttons,
            fn($b) => trim((string) ($b['title'] ?? '')) !== ''
        )), 0, 3);

        if (empty($buttons)) {
            return $this->sendMessage($waId, $body);
        }

        if ($this->settings->viaGateway()) {
            return $this->sendViaGateway($waId, [
                'type'    => 'buttons',
                'buttons' => [
                    'body'  => $body,
                    'items' => collect($buttons)->values()->map(fn($btn, $i) => [
                        'id'    => $btn['reply_id'] ?? (!empty($btn['next_flow_id']) ? 'flow_' . $btn['next_flow_id'] : 'kw_' . $i),
                        'title' => mb_substr((string) $btn['title'], 0, 20),
                    ])->all(),
                ],
            ]);
        }

        $response = Http::withToken($this->settings->access_token)
            ->post(self::GRAPH_URL . '/' . $this->settings->phone_number_id . '/messages', [
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $waId,
                'type'              => 'interactive',
                'interactive'       => [
                    'type' => 'button',
                    'body' => ['text' => $body],
                    'action' => [
                        'buttons' => collect($buttons)->values()->map(fn($btn, $i) => [
                            'type'  => 'reply',
                            'reply' => [
                                'id'    => $btn['reply_id'] ?? (!empty($btn['next_flow_id']) ? 'flow_' . $btn['next_flow_id'] : 'kw_' . $i),
                                'title' => mb_substr((string) $btn['title'], 0, 20),
                            ],
                        ])->all(),
                    ],
                ],
            ]);

        if ($response->failed()) {
            $this->lastError = $this->extractApiError($response);
            Log::error('WhatsApp interactive message failed', [
                'tenant_id' => $this->settings->tenant_id,
                'to'        => $waId,
                'error'     => $response->json(),
            ]);
            return false;
        }

        $this->lastError = null;
        return true;
    }

    // Upload a local file to Meta's media endpoint, returns the media id
    // (or null on failure) — required before a media message can reference it.
    public function uploadMedia(string $filePath, string $mimeType): ?string
    {
        if ($this->settings->viaGateway()) {
            $result = WhatsappGatewayClient::uploadMedia($this->settings->gateway_workspace_id, $filePath, $mimeType);

            $this->lastError = $result['ok'] ? null : $result['error'];

            return $result['ok'] ? ($result['data']['id'] ?? null) : null;
        }

        $response = Http::withToken($this->settings->access_token)
            ->attach('file', file_get_contents($filePath), basename($filePath))
            ->post(self::GRAPH_URL . '/' . $this->settings->phone_number_id . '/media', [
                'messaging_product' => 'whatsapp',
                'type'              => $mimeType,
            ]);

        if ($response->failed()) {
            $this->lastError = $this->extractApiError($response);
            Log::error('WhatsApp media upload failed', [
                'tenant_id' => $this->settings->tenant_id,
                'error'     => $response->json(),
            ]);
            return null;
        }

        $this->lastError = null;
        return $response->json('id');
    }

    // Send an image/document message referencing an already-uploaded media id.
    public function sendMediaMessage(string $waId, string $mediaId, string $type, ?string $caption = null, ?string $filename = null): bool
    {
        if ($this->settings->viaGateway()) {
            return $this->sendViaGateway($waId, [
                'type'  => $type,
                'media' => $type === 'document'
                    ? array_filter(['id' => $mediaId, 'filename' => $filename, 'caption' => $caption])
                    : array_filter(['id' => $mediaId, 'caption' => $caption]),
            ]);
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $waId,
            'type'              => $type,
        ];

        $payload[$type] = $type === 'document'
            ? array_filter(['id' => $mediaId, 'filename' => $filename, 'caption' => $caption])
            : array_filter(['id' => $mediaId, 'caption' => $caption]);

        $response = Http::withToken($this->settings->access_token)
            ->post(self::GRAPH_URL . '/' . $this->settings->phone_number_id . '/messages', $payload);

        if ($response->failed()) {
            $this->lastError = $this->extractApiError($response);
            Log::error('WhatsApp media message failed', [
                'tenant_id' => $this->settings->tenant_id,
                'to'        => $waId,
                'error'     => $response->json(),
            ]);
            return false;
        }

        $this->lastError = null;
        return true;
    }

    // Meta's error payload shape: {"error":{"message":"...","error_user_msg":"...",...}}.
    // Prefer the user-facing message when Meta provides one (clearer for a
    // non-technical admin reading the Logs page), else the technical message,
    // else just the HTTP status so there's always something to show.
    private function extractApiError($response): string
    {
        $error = $response->json('error') ?? [];
        $message = $error['error_user_msg'] ?? $error['message'] ?? null;

        return $message ? mb_substr($message, 0, 250) : ('WhatsApp API error (HTTP ' . $response->status() . ')');
    }

    // WhatsApp Cloud API only distinguishes "image" from "document" for the
    // media types this app allows sending (jpg/png vs pdf/doc/xls etc).
    public static function mediaTypeForMime(string $mime): string
    {
        return str_starts_with($mime, 'image/') ? 'image' : 'document';
    }

    // Verify webhook token (direct-Meta connections only — the gateway signs
    // its webhooks with HMAC instead, see WhatsappGatewayClient::verifySignature)
    public function verifyWebhookToken(string $token): bool
    {
        return $token === $this->settings->webhook_verify_token;
    }

    // Get WhatsApp Business Account info
    public function getAccountInfo(): ?array
    {
        if ($this->settings->viaGateway()) {
            $result = WhatsappGatewayClient::account($this->settings->gateway_workspace_id);
            if (!$result['ok']) {
                return null;
            }

            // Callers save these two back onto the settings row — keep what we
            // already know if the gateway's health payload doesn't repeat them.
            $info = $result['data'];
            $info['display_phone_number'] = $info['display_phone_number'] ?? $this->settings->display_phone_number;
            $info['verified_name']        = $info['verified_name'] ?? $this->settings->verified_name;

            return $info;
        }

        $response = Http::withToken($this->settings->access_token)
            ->get(self::GRAPH_URL . '/' . $this->settings->phone_number_id, [
                'fields' => 'id,display_phone_number,verified_name,quality_rating',
            ]);

        if ($response->failed()) return null;

        return $response->json();
    }
}
