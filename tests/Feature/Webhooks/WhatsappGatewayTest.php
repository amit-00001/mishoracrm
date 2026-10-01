<?php

namespace Tests\Feature\Webhooks;

use App\Models\Contact;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\WhatsappChatbotFlow;
use App\Models\WhatsappLog;
use App\Models\WhatsappSetting;
use App\Services\WhatsappChatbotService;
use App\Services\WhatsappGatewayClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

// WhatsApp Gateway mode: strictly opt-in (superadmin switch), and while it is
// off the app must behave exactly as before.
class WhatsappGatewayTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function enableGateway(): void
    {
        PlatformSetting::set('wa_gateway_enabled', '1');
        PlatformSetting::set('wa_gateway_base_url', 'https://gw.test/api/v1/gateway');
        PlatformSetting::set('wa_gateway_api_key', 'gw_testkey');
        PlatformSetting::set('wa_gateway_webhook_secret', 'whsec_test');
    }

    private function gatewaySetting(Tenant $tenant, array $extra = []): WhatsappSetting
    {
        return WhatsappSetting::create(array_merge([
            'tenant_id'            => $tenant->id,
            'connection_mode'      => 'gateway',
            'gateway_workspace_id' => 'tenant-' . $tenant->id,
            'phone_number_id'      => 'PN1',
            'waba_id'              => 'WB1',
            'is_connected'         => true,
        ], $extra));
    }

    private function signedPost(array $event, ?int $timestamp = null, string $secret = 'whsec_test')
    {
        $body = json_encode($event);
        $ts   = (string) ($timestamp ?? time());

        return $this->call('POST', '/webhook/wa-gateway', [], [], [], [
            'CONTENT_TYPE'             => 'application/json',
            'HTTP_X_GATEWAY_TIMESTAMP' => $ts,
            'HTTP_X_GATEWAY_SIGNATURE' => 'sha256=' . hash_hmac('sha256', $ts . '.' . $body, $secret),
            'HTTP_X_GATEWAY_DELIVERY'  => $event['id'],
        ], $body);
    }

    private function received(Tenant $tenant, string $text, string $id = 'd-1'): array
    {
        return [
            'id'         => $id,
            'event'      => 'message.received',
            'created_at' => now()->toIso8601String(),
            'workspace'  => ['id' => 'tenant-' . $tenant->id, 'name' => $tenant->name],
            'data'       => ['message' => [
                'wamid' => 'wamid.IN', 'direction' => 'inbound', 'phone' => '919876543210',
                'contact_name' => 'Asha', 'type' => 'text', 'text' => $text, 'content' => ['text' => $text],
            ]],
        ];
    }

    // ── Off by default ───────────────────────────────────────────

    public function test_gateway_is_off_by_default_and_webhook_refuses(): void
    {
        $tenant = $this->setUpTenant();
        $this->gatewaySetting($tenant);

        $this->signedPost($this->received($tenant, 'hi'))->assertStatus(503);
    }

    public function test_gateway_connected_number_only_counts_as_connected_while_gateway_is_on(): void
    {
        $tenant = $this->setUpTenant();
        $this->gatewaySetting($tenant);

        $this->assertFalse(WhatsappSetting::forTenant($tenant->id)->is_connected);

        $this->enableGateway();
        $this->assertTrue(WhatsappSetting::forTenant($tenant->id)->is_connected);

        PlatformSetting::set('wa_gateway_enabled', '0');
        $this->assertFalse(WhatsappSetting::forTenant($tenant->id)->is_connected);
    }

    public function test_direct_meta_connection_is_untouched_by_the_gateway_switch(): void
    {
        $tenant = $this->setUpTenant();
        WhatsappSetting::create(['tenant_id' => $tenant->id, 'phone_number_id' => 'PN', 'access_token' => 't', 'is_connected' => true]);

        $this->assertTrue(WhatsappSetting::forTenant($tenant->id)->is_connected);
        $this->enableGateway();
        $this->assertTrue(WhatsappSetting::forTenant($tenant->id)->is_connected);
    }

    public function test_tenant_settings_page_is_the_original_one_while_gateway_is_off(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)->get(route('tenant.whatsapp.api-settings'))
            ->assertOk()->assertSee('Coexistence Mode')->assertDontSee('Connect WhatsApp');
    }

    // ── Webhook auth ─────────────────────────────────────────────

    public function test_webhook_rejects_bad_signature_and_stale_timestamp(): void
    {
        $this->enableGateway();
        $tenant = $this->setUpTenant();
        $this->gatewaySetting($tenant);

        $this->signedPost($this->received($tenant, 'hi'), null, 'wrong-secret')->assertStatus(401);
        $this->signedPost($this->received($tenant, 'hi', 'd-old'), time() - 3600)->assertStatus(401);
    }

    // ── Receiving ────────────────────────────────────────────────

    public function test_inbound_message_runs_the_chatbot_and_replies_through_the_gateway(): void
    {
        $this->enableGateway();
        Http::fake(['gw.test/*' => Http::response(['success' => true, 'data' => ['status' => 'sent', 'wamid' => 'wamid.OUT']], 201)]);

        $tenant = $this->setUpTenant();
        $this->gatewaySetting($tenant, ['chatbot_enabled' => true]);
        WhatsappChatbotFlow::create([
            'tenant_id' => $tenant->id, 'name' => 'Hello', 'trigger_keywords' => ['hello'],
            'keyword_match' => 'exact', 'response_message' => 'Hi there!', 'is_active' => true,
        ]);

        $this->signedPost($this->received($tenant, 'hello'))->assertOk();

        Http::assertSent(fn ($r) => $r->url() === "https://gw.test/api/v1/gateway/workspaces/tenant-{$tenant->id}/messages"
            && $r->hasHeader('Authorization', 'Bearer gw_testkey')
            && $r['to'] === '919876543210'
            && $r['type'] === 'text'
            && $r['text'] === 'Hi there!');

        $this->assertDatabaseHas('whatsapp_logs', [
            'tenant_id' => $tenant->id, 'status' => 'sent', 'message' => 'Hi there!', 'wamid' => 'wamid.OUT',
        ]);
    }

    public function test_a_redelivered_event_is_processed_once(): void
    {
        $this->enableGateway();
        Http::fake(['gw.test/*' => Http::response(['success' => true, 'data' => ['wamid' => 'wamid.OUT']], 201)]);

        $tenant = $this->setUpTenant();
        $this->gatewaySetting($tenant, ['chatbot_enabled' => true]);
        WhatsappChatbotFlow::create([
            'tenant_id' => $tenant->id, 'name' => 'Hello', 'trigger_keywords' => ['hello'],
            'keyword_match' => 'exact', 'response_message' => 'Hi there!', 'is_active' => true,
        ]);

        $event = $this->received($tenant, 'hello', 'same-delivery');
        $this->signedPost($event)->assertOk();
        $this->signedPost($event)->assertOk()->assertJson(['duplicate' => true]);

        Http::assertSentCount(1);
    }

    public function test_event_for_an_unknown_workspace_is_acknowledged(): void
    {
        $this->enableGateway();
        $tenant = $this->setUpTenant();

        $event = $this->received($tenant, 'hi');
        $event['workspace']['id'] = 'tenant-9999';

        $this->signedPost($event)->assertOk();
    }

    public function test_status_receipts_move_a_log_forward_only(): void
    {
        $this->enableGateway();
        $tenant = $this->setUpTenant();
        $this->gatewaySetting($tenant);
        $log = WhatsappLog::create([
            'tenant_id' => $tenant->id, 'to_phone' => '919876543210', 'message' => 'x', 'status' => 'sent', 'wamid' => 'wamid.OUT',
        ]);

        $status = fn (string $id, string $s, $error = null) => [
            'id' => $id, 'event' => 'message.status', 'created_at' => now()->toIso8601String(),
            'workspace' => ['id' => 'tenant-' . $tenant->id],
            'data' => ['message' => ['wamid' => 'wamid.OUT', 'status' => $s, 'error' => $error]],
        ];

        $this->signedPost($status('s-1', 'read'))->assertOk();
        $this->assertSame('delivered', $log->fresh()->status);

        // A late "failed" must not drag a delivered message back.
        $this->signedPost($status('s-2', 'failed', ['message' => 'late']))->assertOk();
        $this->assertSame('delivered', $log->fresh()->status);
    }

    public function test_failed_receipt_records_the_reason(): void
    {
        $this->enableGateway();
        $tenant = $this->setUpTenant();
        $this->gatewaySetting($tenant);
        $log = WhatsappLog::create([
            'tenant_id' => $tenant->id, 'to_phone' => '919876543210', 'message' => 'x', 'status' => 'sent', 'wamid' => 'wamid.OUT',
        ]);

        $this->signedPost([
            'id' => 'f-1', 'event' => 'message.status', 'workspace' => ['id' => 'tenant-' . $tenant->id],
            'data' => ['message' => ['wamid' => 'wamid.OUT', 'status' => 'failed', 'error' => ['message' => 'Undeliverable']]],
        ])->assertOk();

        $this->assertSame('failed', $log->fresh()->status);
        $this->assertSame('Undeliverable', $log->fresh()->error_message);
    }

    public function test_account_connected_event_marks_the_tenant_connected(): void
    {
        $this->enableGateway();
        $tenant = $this->setUpTenant();
        WhatsappSetting::create(['tenant_id' => $tenant->id, 'gateway_workspace_id' => 'tenant-' . $tenant->id]);

        $this->signedPost([
            'id' => 'c-1', 'event' => 'account.connected', 'workspace' => ['id' => 'tenant-' . $tenant->id],
            'data' => [
                'connected' => true, 'phone_number_id' => 'PN9', 'waba_id' => 'WB9',
                'display_phone_number' => '+91 98765 43210', 'verified_name' => 'Acme Traders',
            ],
        ])->assertOk();

        $setting = WhatsappSetting::forTenant($tenant->id);
        $this->assertTrue($setting->is_connected);
        $this->assertTrue($setting->viaGateway());
        $this->assertSame('PN9', $setting->phone_number_id);
        $this->assertSame('Acme Traders', $setting->verified_name);
    }

    // ── Sending ──────────────────────────────────────────────────

    public function test_gateway_rejection_surfaces_the_reason_and_sends_no_wamid(): void
    {
        $this->enableGateway();
        Http::fake(['gw.test/*' => Http::response([
            'success' => false, 'message' => 'Rejected',
            'error' => ['code' => 'whatsapp_error', 'message' => 'Re-engagement message', 'meta_code' => 131047],
        ], 422)]);

        $tenant  = $this->setUpTenant();
        $service = new WhatsappChatbotService($this->gatewaySetting($tenant));

        $this->assertFalse($service->sendMessage('919876543210', 'hello'));
        $this->assertSame('Re-engagement message', $service->lastError);
        $this->assertNull($service->lastMessageId);
    }

    public function test_reply_buttons_go_out_as_a_gateway_buttons_message(): void
    {
        $this->enableGateway();
        Http::fake(['gw.test/*' => Http::response(['success' => true, 'data' => ['wamid' => 'w']], 201)]);

        $tenant  = $this->setUpTenant();
        $service = new WhatsappChatbotService($this->gatewaySetting($tenant));

        $this->assertTrue($service->sendInteractiveButtons('919876543210', 'Confirm?', [
            ['title' => 'Accept', 'reply_id' => 'qacc_tok'],
            ['title' => 'Next', 'next_flow_id' => 7],
        ]));

        Http::assertSent(fn ($r) => $r['type'] === 'buttons'
            && $r['buttons']['body'] === 'Confirm?'
            && $r['buttons']['items'] === [['id' => 'qacc_tok', 'title' => 'Accept'], ['id' => 'flow_7', 'title' => 'Next']]);
    }

    public function test_sending_through_a_gateway_number_fails_cleanly_when_gateway_is_off(): void
    {
        Http::fake();
        $tenant  = $this->setUpTenant();
        $service = new WhatsappChatbotService($this->gatewaySetting($tenant));

        $this->assertFalse($service->sendMessage('919876543210', 'hello'));
        $this->assertNotNull($service->lastError);
        Http::assertNothingSent();
    }

    // ── Direct Meta path is unchanged ────────────────────────────

    public function test_direct_meta_webhook_still_processes_inbound_messages(): void
    {
        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'x']]], 200)]);

        $tenant = $this->setUpTenant();
        WhatsappSetting::create([
            'tenant_id' => $tenant->id, 'phone_number_id' => 'PNID', 'waba_id' => 'WABA1',
            'access_token' => 'tok', 'is_connected' => true, 'chatbot_enabled' => true,
        ]);
        WhatsappChatbotFlow::create([
            'tenant_id' => $tenant->id, 'name' => 'Hello', 'trigger_keywords' => ['hello'],
            'keyword_match' => 'exact', 'response_message' => 'Hi there!', 'is_active' => true,
        ]);

        $this->postJson('/webhook/whatsapp', [
            'object' => 'whatsapp_business_account',
            'entry'  => [['id' => 'WABA1', 'changes' => [['field' => 'messages', 'value' => [
                'contacts' => [['wa_id' => '919876543210', 'profile' => ['name' => 'Asha']]],
                'messages' => [['from' => '919876543210', 'id' => 'wamid.X', 'type' => 'text', 'text' => ['body' => 'hello']]],
            ]]]]],
        ])->assertOk();

        Http::assertSent(fn ($r) => str_starts_with($r->url(), 'https://graph.facebook.com/')
            && $r['text']['body'] === 'Hi there!');
    }

    // ── Superadmin ───────────────────────────────────────────────

    public function test_superadmin_cannot_switch_on_without_credentials_then_can_toggle(): void
    {
        $tenant = $this->setUpTenant();
        $super  = $this->makeUser($tenant, 'superadmin');

        $this->actingAs($super)->post(route('superadmin.platform-settings.whatsapp-gateway.toggle'))
            ->assertSessionHas('error');
        $this->assertNotSame('1', PlatformSetting::get('wa_gateway_enabled'));

        $this->actingAs($super)->post(route('superadmin.platform-settings.whatsapp-gateway.save'), [
            'base_url' => 'https://gw.test', 'api_key' => 'gw_k', 'webhook_secret' => 'whsec_s',
        ])->assertSessionHasNoErrors();
        $this->assertSame('https://gw.test/api/v1/gateway', PlatformSetting::get('wa_gateway_base_url'));

        $this->actingAs($super)->post(route('superadmin.platform-settings.whatsapp-gateway.toggle'));
        $this->assertSame('1', PlatformSetting::get('wa_gateway_enabled'));

        $this->actingAs($super)->post(route('superadmin.platform-settings.whatsapp-gateway.toggle'));
        $this->assertSame('0', PlatformSetting::get('wa_gateway_enabled'));
    }

    public function test_superadmin_gateway_page_renders_off_and_on(): void
    {
        $super = $this->makeUser($this->setUpTenant(), 'superadmin');

        $this->actingAs($super)->get(route('superadmin.platform-settings.whatsapp-gateway'))
            ->assertOk()->assertSee('Gateway is OFF')->assertSee('Turn ON');

        $this->enableGateway();

        $this->actingAs($super)->get(route('superadmin.platform-settings.whatsapp-gateway'))
            ->assertOk()->assertSee('Gateway is ON')->assertSee('Turn OFF')
            ->assertDontSee('gw_testkey')->assertDontSee('whsec_test');
    }

    public function test_superadmin_can_test_credentials_while_switched_off(): void
    {
        PlatformSetting::set('wa_gateway_base_url', 'https://gw.test/api/v1/gateway');
        PlatformSetting::set('wa_gateway_api_key', 'gw_k');
        Http::fake(['gw.test/*' => Http::response(['success' => true, 'data' => []], 200)]);

        $super = $this->makeUser($this->setUpTenant(), 'superadmin');

        $this->actingAs($super)->postJson(route('superadmin.platform-settings.whatsapp-gateway.test'))
            ->assertOk()->assertJson(['success' => true]);
    }

    // ── Tenant connect flow ──────────────────────────────────────

    public function test_tenant_connect_creates_a_workspace_and_redirects_to_the_hosted_page(): void
    {
        $this->enableGateway();
        Http::fake([
            'gw.test/*/workspaces'                       => Http::response(['success' => true, 'data' => ['id' => 'x']], 201),
            'gw.test/*/workspaces/*/connect-sessions'    => Http::response(['success' => true, 'data' => ['url' => 'https://gw.test/gateway/connect/TOKEN']], 201),
        ]);

        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)->get(route('tenant.whatsapp.api-settings'))->assertOk()->assertSee('Connect WhatsApp');

        $this->actingAs($admin)->post(route('tenant.whatsapp.gateway.connect'))
            ->assertRedirect('https://gw.test/gateway/connect/TOKEN');

        $this->assertSame('tenant-' . $tenant->id, WhatsappSetting::forTenant($tenant->id)->gateway_workspace_id);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/connect-sessions')
            && $r['return_url'] === route('tenant.whatsapp.api-settings'));
    }

    public function test_returning_from_the_connect_page_syncs_the_connection(): void
    {
        $this->enableGateway();
        Http::fake(['gw.test/*/workspaces/*' => Http::response(['success' => true, 'data' => ['whatsapp' => [
            'connected' => true, 'phone_number_id' => 'PN7', 'waba_id' => 'WB7',
            'display_phone_number' => '+91 90000 00000', 'verified_name' => 'Acme',
        ]]], 200)]);

        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        WhatsappSetting::create(['tenant_id' => $tenant->id, 'gateway_workspace_id' => 'tenant-' . $tenant->id]);

        $this->actingAs($admin)->get(route('tenant.whatsapp.api-settings', ['status' => 'connected']))
            ->assertOk()->assertSee('WhatsApp connected successfully');

        $this->assertTrue(WhatsappSetting::forTenant($tenant->id)->is_connected);
    }

    public function test_tenant_already_connected_directly_keeps_the_original_screen(): void
    {
        $this->enableGateway();
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        WhatsappSetting::create(['tenant_id' => $tenant->id, 'phone_number_id' => 'PN', 'access_token' => 't', 'is_connected' => true]);

        $this->actingAs($admin)->get(route('tenant.whatsapp.api-settings'))
            ->assertOk()->assertSee('Coexistence Mode');
    }

    // ── Meta message templates ───────────────────────────────────

    private function fakeTemplateGateway(): void
    {
        Http::fake([
            'gw.test/*/templates*' => Http::response(['success' => true, 'data' => [
                [
                    'name' => 'order_update', 'language' => 'en', 'status' => 'APPROVED', 'category' => 'UTILITY',
                    'components' => [['type' => 'BODY', 'text' => 'Hi {{1}}, your order {{2}} has shipped.']],
                ],
                [
                    'name' => 'draft_one', 'language' => 'en', 'status' => 'PENDING', 'category' => 'MARKETING',
                    'components' => [['type' => 'BODY', 'text' => 'Hello there']],
                ],
                [
                    'name' => 'with_image', 'language' => 'en', 'status' => 'APPROVED', 'category' => 'MARKETING',
                    'components' => [['type' => 'HEADER', 'format' => 'IMAGE'], ['type' => 'BODY', 'text' => 'Look']],
                ],
            ]], 200),
            'gw.test/*/messages' => Http::response(['success' => true, 'data' => ['status' => 'sent', 'wamid' => 'wamid.T']], 201),
        ]);
    }

    private function templateTenant(): array
    {
        $this->enableGateway();
        $this->fakeTemplateGateway();

        $tenant = $this->setUpTenant();
        $this->gatewaySetting($tenant);

        return [$tenant, $this->makeUser($tenant, 'tenant_admin')];
    }

    public function test_only_approved_sendable_templates_are_offered_and_normalised(): void
    {
        $this->enableGateway();
        $this->fakeTemplateGateway();

        $approved = WhatsappGatewayClient::approvedTemplates('tenant-1');

        $this->assertCount(1, $approved);
        $this->assertSame('order_update', $approved[0]['name']);
        $this->assertSame(2, $approved[0]['params']);
        $this->assertSame('approved', $approved[0]['status']);
    }

    public function test_meta_templates_page_needs_a_gateway_connected_number(): void
    {
        $this->enableGateway();
        $this->fakeTemplateGateway();
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)->get(route('tenant.whatsapp.meta-templates'))
            ->assertRedirect(route('tenant.whatsapp.templates'));

        $this->gatewaySetting($tenant);

        $this->actingAs($admin)->get(route('tenant.whatsapp.meta-templates'))
            ->assertOk()->assertSee('order_update')->assertSee('draft_one')->assertSee('Not sendable here');
    }

    public function test_creating_a_template_sends_body_with_examples_to_the_gateway(): void
    {
        [, $admin] = $this->templateTenant();

        $this->actingAs($admin)->post(route('tenant.whatsapp.meta-templates.store'), [
            'name' => 'shipping_note', 'language' => 'en', 'category' => 'UTILITY',
            'body' => 'Order {{1}} shipped to {{2}}.', 'examples' => ['ORD-1', 'Pune'],
        ])->assertRedirect(route('tenant.whatsapp.meta-templates'));

        Http::assertSent(fn ($r) => $r->method() === 'POST'
            && str_ends_with($r->url(), '/templates')
            && $r['name'] === 'shipping_note'
            && $r['category'] === 'UTILITY'
            && $r['components'][0]['example']['body_text'] === [['ORD-1', 'Pune']]);
    }

    public function test_template_with_skipped_variable_or_missing_examples_is_refused_before_calling_meta(): void
    {
        [, $admin] = $this->templateTenant();

        $base = ['name' => 'note', 'language' => 'en', 'category' => 'UTILITY'];

        $this->actingAs($admin)->post(route('tenant.whatsapp.meta-templates.store'), $base + [
            'body' => 'Hi {{1}} and {{3}}', 'examples' => ['a', 'b'],
        ])->assertSessionHas('error');

        $this->actingAs($admin)->post(route('tenant.whatsapp.meta-templates.store'), $base + [
            'body' => 'Hi {{1}}', 'examples' => [''],
        ])->assertSessionHas('error');

        Http::assertNothingSent();
    }

    public function test_deleting_a_template_calls_the_gateway(): void
    {
        [, $admin] = $this->templateTenant();

        $this->actingAs($admin)->delete(route('tenant.whatsapp.meta-templates.destroy', 'order_update'))
            ->assertSessionHas('success');

        Http::assertSent(fn ($r) => $r->method() === 'DELETE' && str_ends_with($r->url(), '/templates/order_update'));
    }

    public function test_send_form_offers_templates_only_to_gateway_numbers(): void
    {
        [$tenant, $admin] = $this->templateTenant();

        $this->actingAs($admin)->get(route('tenant.whatsapp.send'))
            ->assertOk()->assertSee('order_update (en)')->assertDontSee('draft_one')->assertDontSee('with_image');

        // A tenant connected straight to Meta never sees the picker.
        $other = $this->setUpTenant();
        WhatsappSetting::create(['tenant_id' => $other->id, 'phone_number_id' => 'PN', 'access_token' => 't', 'is_connected' => true]);

        $this->actingAs($this->makeUser($other, 'tenant_admin'))->get(route('tenant.whatsapp.send'))
            ->assertOk()->assertDontSee('Approved WhatsApp Template');
    }

    public function test_sending_a_template_posts_params_and_logs_the_rendered_text(): void
    {
        [$tenant, $admin] = $this->templateTenant();

        $this->actingAs($admin)->post(route('tenant.whatsapp.send.store'), [
            'to_phone' => '+91 98765 43210', 'to_name' => 'Asha',
            'message' => 'Hi {{1}}, your order {{2}} has shipped.',
            'meta_template' => 'order_update|en', 'template_params' => ['Asha', 'ORD-5'],
        ])->assertRedirect(route('tenant.whatsapp.logs'));

        Http::assertSent(fn ($r) => str_ends_with($r->url(), '/messages')
            && $r['to'] === '919876543210'
            && $r['type'] === 'template'
            && $r['template'] === ['name' => 'order_update', 'language' => 'en', 'body_params' => ['Asha', 'ORD-5']]);

        $this->assertDatabaseHas('whatsapp_logs', [
            'tenant_id' => $tenant->id, 'status' => 'sent', 'wamid' => 'wamid.T',
            'message' => 'Hi Asha, your order ORD-5 has shipped.',
        ]);
    }

    public function test_unapproved_template_or_blank_field_sends_nothing(): void
    {
        [, $admin] = $this->templateTenant();
        $send = fn (array $extra) => $this->actingAs($admin)->post(route('tenant.whatsapp.send.store'), [
            'to_phone' => '919876543210', 'message' => 'x',
        ] + $extra);

        $send(['meta_template' => 'draft_one|en'])->assertSessionHas('error');
        $send(['meta_template' => 'order_update|en', 'template_params' => ['Asha', '']])->assertSessionHas('error');

        Http::assertNotSent(fn ($r) => str_ends_with($r->url(), '/messages'));
    }

    public function test_bulk_template_fills_variables_per_recipient(): void
    {
        [$tenant, $admin] = $this->templateTenant();

        $asha = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Asha', 'phone' => '919800000001']);
        $ravi = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Ravi', 'phone' => '919800000002']);

        $this->actingAs($admin)->post(route('tenant.whatsapp.bulk.send'), [
            'type' => 'contacts', 'recipients' => [$asha->id, $ravi->id], 'message' => 'x',
            'meta_template' => 'order_update|en', 'template_params' => ['{{name}}', 'ORD-5'],
        ])->assertRedirect(route('tenant.whatsapp.logs'));

        foreach (['Asha' => '919800000001', 'Ravi' => '919800000002'] as $name => $phone) {
            Http::assertSent(fn ($r) => str_ends_with($r->url(), '/messages')
                && $r['to'] === $phone
                && $r['template']['body_params'] === [$name, 'ORD-5']);

            $this->assertDatabaseHas('whatsapp_logs', ['to_phone' => $phone, 'message' => "Hi {$name}, your order ORD-5 has shipped."]);
        }
    }

    public function test_template_status_webhook_refreshes_the_cached_list(): void
    {
        [$tenant] = $this->templateTenant();
        $workspace = 'tenant-' . $tenant->id;

        WhatsappGatewayClient::approvedTemplates($workspace);
        $this->assertTrue(Cache::has("wa_gateway_templates:{$workspace}"));

        $this->signedPost([
            'id' => 't-1', 'event' => 'template.status', 'workspace' => ['id' => $workspace],
            'data' => ['name' => 'order_update', 'language' => 'en', 'status' => 'APPROVED', 'reason' => null],
        ])->assertOk();

        $this->assertFalse(Cache::has("wa_gateway_templates:{$workspace}"));
    }

    public function test_direct_meta_numbers_cannot_send_gateway_templates(): void
    {
        $tenant  = $this->setUpTenant();
        $service = new WhatsappChatbotService(WhatsappSetting::create([
            'tenant_id' => $tenant->id, 'phone_number_id' => 'PN', 'access_token' => 't', 'is_connected' => true,
        ]));
        Http::fake();

        $this->assertFalse($service->sendTemplate('919876543210', 'order_update', 'en', ['a']));
        $this->assertNotNull($service->lastError);
        Http::assertNothingSent();
    }
}
