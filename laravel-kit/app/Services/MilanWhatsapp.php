<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

// Your product's door to Milan CRM's WhatsApp Gateway. "$tenantId" is YOUR tenant's id — the same
// value you used when creating the workspace; the gateway keeps one WhatsApp number per workspace.
//
//   app(MilanWhatsapp::class)->sendTemplate($tenant->id, '919876543210', 'welcome', ['Asha']);
class MilanWhatsapp
{
    public function __construct(
        private ?string $baseUrl = null,
        private ?string $apiKey = null,
    ) {
        $this->baseUrl ??= rtrim((string) config('services.milan_wa.url'), '/');
        $this->apiKey  ??= (string) config('services.milan_wa.key');
    }

    // ── Workspaces & connecting a number ──────────────────────────

    // Safe to call every time (e.g. on signup and again before "Connect"): an existing id is returned as-is.
    public function ensureWorkspace(string|int $tenantId, string $name): array
    {
        return $this->call('post', '/workspaces', ['id' => (string) $tenantId, 'name' => $name]);
    }

    public function workspace(string|int $tenantId): array
    {
        return $this->call('get', "/workspaces/{$tenantId}");
    }

    // A page on Milan CRM where the user signs in with Facebook and picks their number.
    // They come back to $returnUrl?status=connected — and you also get an "account.connected" webhook.
    public function connectUrl(string|int $tenantId, string $returnUrl): string
    {
        return $this->call('post', "/workspaces/{$tenantId}/connect-sessions", ['return_url' => $returnUrl])['url'];
    }

    // Live health of the connected number: quality rating, messaging limit, and "problem" if it cannot send.
    public function account(string|int $tenantId): array
    {
        return $this->call('get', "/workspaces/{$tenantId}/account");
    }

    public function disconnect(string|int $tenantId): array
    {
        return $this->call('post', "/workspaces/{$tenantId}/disconnect");
    }

    // ── Sending ───────────────────────────────────────────────────
    // $ref = your own unique key (e.g. "order-9-shipped"): sending the same $ref again never double-sends.

    // Free-form text — only works within 24 hours of the customer's last message.
    public function sendText(string|int $tenantId, string $to, string $text, ?string $ref = null): array
    {
        return $this->send($tenantId, ['to' => $to, 'type' => 'text', 'text' => $text], $ref);
    }

    // An approved template — works any time, and is the only way to START a conversation.
    // $bodyParams fill {{1}}, {{2}}, ... in order.
    public function sendTemplate(string|int $tenantId, string $to, string $template, array $bodyParams = [], string $language = 'en', ?string $ref = null): array
    {
        return $this->send($tenantId, [
            'to' => $to, 'type' => 'template',
            'template' => ['name' => $template, 'language' => $language, 'body_params' => array_values($bodyParams)],
        ], $ref);
    }

    // type: image | video | audio | document. $url must be publicly reachable by WhatsApp.
    public function sendMedia(string|int $tenantId, string $to, string $type, string $url, ?string $caption = null, ?string $filename = null, ?string $ref = null): array
    {
        return $this->send($tenantId, [
            'to' => $to, 'type' => $type,
            'media' => array_filter(['link' => $url, 'caption' => $caption, 'filename' => $filename]),
        ], $ref);
    }

    // Up to 3 quick-reply buttons: $buttons = [['id' => 'yes', 'title' => 'Haan'], ...]. A tap arrives as message.received.
    public function sendButtons(string|int $tenantId, string $to, string $body, array $buttons, ?string $ref = null): array
    {
        return $this->send($tenantId, ['to' => $to, 'type' => 'buttons', 'buttons' => ['body' => $body, 'items' => $buttons]], $ref);
    }

    // Blue ticks for an inbound message ($gatewayMessageId = the "id" of the message in the webhook).
    public function markRead(string|int $tenantId, string $gatewayMessageId): array
    {
        return $this->call('post', "/workspaces/{$tenantId}/messages/{$gatewayMessageId}/read");
    }

    // ── Templates (they live on the client's own WhatsApp Business Account; Meta approves them) ──

    public function templates(string|int $tenantId, array $query = []): array
    {
        return $this->call('get', "/workspaces/{$tenantId}/templates", $query);
    }

    // $components e.g. [['type' => 'BODY', 'text' => 'Hi {{1}}, your order {{2}} shipped.', 'example' => ['body_text' => [['Asha', 'ORD-1']]]]]
    public function createTemplate(string|int $tenantId, string $name, string $category, array $components, string $language = 'en'): array
    {
        return $this->call('post', "/workspaces/{$tenantId}/templates", [
            'name' => $name, 'language' => $language, 'category' => $category, 'components' => $components,
        ]);
    }

    public function deleteTemplate(string|int $tenantId, string $name): array
    {
        return $this->call('delete', "/workspaces/{$tenantId}/templates/{$name}");
    }

    // ── History ───────────────────────────────────────────────────

    public function messages(string|int $tenantId, array $query = []): array
    {
        return $this->call('get', "/workspaces/{$tenantId}/messages", $query);
    }

    public function conversations(string|int $tenantId, array $query = []): array
    {
        return $this->call('get', "/workspaces/{$tenantId}/conversations", $query);
    }

    // ── Plumbing ──────────────────────────────────────────────────

    private function send(string|int $tenantId, array $payload, ?string $ref): array
    {
        if ($ref) {
            $payload['client_reference'] = $ref;
        }

        return $this->call('post', "/workspaces/{$tenantId}/messages", $payload);
    }

    private function http(): PendingRequest
    {
        return Http::withToken($this->apiKey)->acceptJson()->timeout(20)->baseUrl($this->baseUrl . '/api/v1/gateway');
    }

    // Returns the response's "data"; throws MilanWhatsappException on any error.
    private function call(string $method, string $path, array $data = []): array
    {
        try {
            $res = $this->http()->{$method}($path, $data);
        } catch (ConnectionException $e) {
            throw new MilanWhatsappException('Could not reach Milan CRM: ' . $e->getMessage(), 0, 'network_error');
        }

        if ($res->failed()) {
            throw MilanWhatsappException::fromResponse($res);
        }

        return $res->json('data') ?? [];
    }
}
