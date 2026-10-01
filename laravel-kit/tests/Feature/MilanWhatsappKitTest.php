<?php

namespace Tests\Feature;

use App\Events\WhatsappMessageReceived;
use App\Http\Controllers\MilanWhatsappWebhookController;
use App\Models\TenantWhatsappAccount;
use App\Models\WhatsappMessage;
use App\Services\MilanWhatsapp;
use App\Services\MilanWhatsappException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class MilanWhatsappKitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.milan_wa' => ['url' => 'https://crm.test', 'key' => 'gw_testkey', 'secret' => 'whsec_test']]);
    }

    // ── Sending ───────────────────────────────────────────────────

    public function test_a_template_is_sent_to_the_right_workspace_with_the_api_key(): void
    {
        Http::fake(['crm.test/*' => Http::response(['success' => true, 'data' => ['id' => 'm1', 'status' => 'sent', 'wamid' => 'wamid.1']], 201)]);

        $sent = app(MilanWhatsapp::class)->sendTemplate(42, '919876543210', 'welcome', ['Asha', 'Acme'], 'en', 'welcome-42-1');

        $this->assertSame('sent', $sent['status']);
        Http::assertSent(fn(HttpRequest $r) => $r->url() === 'https://crm.test/api/v1/gateway/workspaces/42/messages'
            && $r->hasHeader('Authorization', 'Bearer gw_testkey')
            && $r['type'] === 'template' && $r['template']['name'] === 'welcome'
            && $r['template']['body_params'] === ['Asha', 'Acme'] && $r['client_reference'] === 'welcome-42-1');
    }

    public function test_gateway_errors_become_a_readable_exception(): void
    {
        Http::fake(['crm.test/*' => Http::response([
            'success' => false, 'message' => 'More than 24 hours have passed.',
            'error' => ['code' => 'whatsapp_error', 'meta_code' => 131047],
            'data' => ['id' => 'm9', 'status' => 'failed'],
        ], 422)]);

        try {
            app(MilanWhatsapp::class)->sendText(42, '919876543210', 'hi');
            $this->fail('expected an exception');
        } catch (MilanWhatsappException $e) {
            $this->assertTrue($e->isOutsideWindow());
            $this->assertSame('m9', $e->data['id']);
        }
    }

    public function test_connect_url_is_returned(): void
    {
        Http::fake(['crm.test/*' => Http::response(['success' => true, 'data' => ['url' => 'https://crm.test/gateway/connect/TOKEN']], 201)]);

        $this->assertSame('https://crm.test/gateway/connect/TOKEN', app(MilanWhatsapp::class)->connectUrl(42, 'https://app.test/back'));
        Http::assertSent(fn(HttpRequest $r) => $r['return_url'] === 'https://app.test/back');
    }

    // ── Webhook ───────────────────────────────────────────────────

    private function signed(array $event, ?string $secret = 'whsec_test', ?int $at = null): Request
    {
        $body = json_encode($event);
        $ts   = (string) ($at ?? time());

        return Request::create('/api/hooks/whatsapp', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_GATEWAY_TIMESTAMP' => $ts,
            'HTTP_X_GATEWAY_SIGNATURE' => 'sha256=' . hash_hmac('sha256', $ts . '.' . $body, (string) $secret),
        ], $body);
    }

    private function receive(Request $request)
    {
        return app(MilanWhatsappWebhookController::class)($request);
    }

    private function inbound(string $eventId = 'evt-1', string $gatewayId = 'g-1'): array
    {
        return ['id' => $eventId, 'event' => 'message.received', 'workspace' => ['id' => '42', 'name' => 'Acme'], 'data' => ['message' => [
            'id' => $gatewayId, 'wamid' => 'wamid.IN', 'direction' => 'inbound', 'phone' => '919876543210', 'contact_name' => 'Asha',
            'type' => 'text', 'text' => 'price?', 'content' => ['text' => 'price?'], 'status' => 'received',
        ]]];
    }

    public function test_a_bad_or_old_signature_is_rejected(): void
    {
        foreach ([$this->signed($this->inbound(), 'wrong-secret'), $this->signed($this->inbound(), 'whsec_test', time() - 3600)] as $request) {
            try {
                $this->receive($request);
                $this->fail('should have been rejected');
            } catch (HttpException $e) {
                $this->assertSame(401, $e->getStatusCode());
            }
        }

        $this->assertSame(0, WhatsappMessage::count());
    }

    public function test_an_inbound_message_is_saved_and_handed_to_your_code_once(): void
    {
        Event::fake([WhatsappMessageReceived::class]);

        $this->receive($this->signed($this->inbound()));
        $this->receive($this->signed($this->inbound()));   // gateway retry of the same event

        $this->assertSame(1, WhatsappMessage::count());
        $this->assertSame('price?', WhatsappMessage::first()->text);
        Event::assertDispatchedTimes(WhatsappMessageReceived::class, 1);
    }

    public function test_status_updates_and_account_connected_are_applied(): void
    {
        Event::fake();
        $this->receive($this->signed($this->inbound('e1', 'g-9')));

        $this->receive($this->signed(['id' => 'e2', 'event' => 'message.status', 'workspace' => ['id' => '42'], 'data' => ['message' => [
            'id' => 'g-9', 'wamid' => 'wamid.IN', 'direction' => 'inbound', 'phone' => '919876543210', 'type' => 'text', 'status' => 'read',
        ]]]));
        $this->assertSame('read', WhatsappMessage::where('gateway_id', 'g-9')->value('status'));

        $this->receive($this->signed(['id' => 'e3', 'event' => 'account.connected', 'workspace' => ['id' => '42'],
            'data' => ['connected' => true, 'display_phone_number' => '+91 99999 00000', 'verified_name' => 'Acme', 'warnings' => []]]));

        $this->assertTrue(TenantWhatsappAccount::isConnected(42));
        $this->assertSame('+91 99999 00000', TenantWhatsappAccount::forTenant(42)->display_phone_number);
    }
}
