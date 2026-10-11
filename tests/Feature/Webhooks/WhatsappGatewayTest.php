<?php

namespace Tests\Feature\Webhooks;

use App\Models\Contact;
use App\Models\ErrorLog;
use App\Models\PlatformSetting;
use App\Models\Tenant;
use App\Models\WhatsappChatbotFlow;
use App\Models\WhatsappChatbotSession;
use App\Models\WhatsappLog;
use App\Models\WhatsappSetting;
use App\Services\WhatsappChatbotService;
use App\Services\WhatsappGatewayClient;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

    public function test_an_inbound_message_is_saved_to_the_whatsapp_log(): void
    {
        $this->enableGateway();
        $tenant = $this->setUpTenant();
        $this->gatewaySetting($tenant);

        $this->signedPost($this->received($tenant, 'need a quote'))->assertOk();

        $this->assertDatabaseHas('whatsapp_logs', [
            'tenant_id' => $tenant->id, 'to_phone' => '919876543210', 'to_name' => 'Asha',
            'message' => 'need a quote', 'status' => 'received',
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

    // ── Event ids (DB dedupe) ────────────────────────────────────

    public function test_each_event_id_is_recorded_once_in_the_database(): void
    {
        $this->enableGateway();
        $tenant = $this->setUpTenant();
        $this->gatewaySetting($tenant);

        $event = [
            'id' => 'evt-once', 'event' => 'account.connected', 'workspace' => ['id' => 'tenant-' . $tenant->id],
            'data' => ['connected' => true, 'display_phone_number' => '+91 1'],
        ];

        $this->signedPost($event)->assertOk()->assertJsonMissing(['duplicate' => true]);
        $this->signedPost($event)->assertOk()->assertJson(['duplicate' => true]);

        $this->assertSame(1, DB::table('whatsapp_webhook_events')->where('event_id', 'evt-once')->count());
        $this->assertDatabaseHas('whatsapp_webhook_events', ['event_id' => 'evt-once', 'event' => 'account.connected']);
    }

    public function test_a_failed_handling_leaves_no_event_row_so_the_retry_is_processed(): void
    {
        $this->enableGateway();
        $tenant = $this->setUpTenant();
        WhatsappSetting::create(['tenant_id' => $tenant->id, 'gateway_workspace_id' => 'tenant-' . $tenant->id]);

        $fail = true;
        WhatsappSetting::saving(function () use (&$fail) {
            if ($fail) {
                throw new \RuntimeException('database went away');
            }
        });

        $event = [
            'id' => 'evt-retry', 'event' => 'account.connected', 'workspace' => ['id' => 'tenant-' . $tenant->id],
            'data' => ['connected' => true, 'display_phone_number' => '+91 2'],
        ];

        $this->signedPost($event)->assertStatus(500);
        $this->assertDatabaseHas('error_logs', ['message' => 'database went away']);
        $this->assertDatabaseMissing('whatsapp_webhook_events', ['event_id' => 'evt-retry']);
        $this->assertFalse(WhatsappSetting::forTenant($tenant->id)->is_connected);

        // The gateway retries the same event id — now it goes through.
        $fail = false;
        $this->signedPost($event)->assertOk();
        $this->assertDatabaseHas('whatsapp_webhook_events', ['event_id' => 'evt-retry']);
        $this->assertTrue(WhatsappSetting::forTenant($tenant->id)->is_connected);
    }

    // PostgreSQL (production) silently turns the COMMIT of a transaction that hit
    // an error into a ROLLBACK. Simulated here by the event row vanishing while the
    // event is being handled — the webhook must answer 500, not a false 200.
    public function test_an_event_that_was_silently_rolled_back_is_not_acknowledged(): void
    {
        $this->enableGateway();
        $tenant = $this->setUpTenant();
        WhatsappSetting::create(['tenant_id' => $tenant->id, 'gateway_workspace_id' => 'tenant-' . $tenant->id]);

        WhatsappSetting::saving(function () {
            DB::table('whatsapp_webhook_events')->where('event_id', 'evt-gone')->delete();
        });

        $this->signedPost([
            'id' => 'evt-gone', 'event' => 'account.connected', 'workspace' => ['id' => 'tenant-' . $tenant->id],
            'data' => ['connected' => true],
        ])->assertStatus(500);

        $this->assertStringContainsString('rolled back', ErrorLog::latest('id')->first()->message);
    }

    // A swallowed failure of the "received" log insert must not take the event row
    // (same transaction) or the chatbot reply down with it.
    public function test_a_failed_inbound_log_does_not_stop_the_chatbot_or_lose_the_event(): void
    {
        $this->enableGateway();
        Http::fake(['gw.test/*' => Http::response(['success' => true, 'data' => ['wamid' => 'wamid.OUT']], 201)]);
        $tenant = $this->setUpTenant();
        $this->gatewaySetting($tenant, ['chatbot_enabled' => true]);
        WhatsappChatbotFlow::create([
            'tenant_id' => $tenant->id, 'name' => 'Hello', 'trigger_keywords' => ['hello'],
            'keyword_match' => 'exact', 'response_message' => 'Hi there!', 'is_active' => true,
        ]);

        WhatsappLog::creating(function ($log) {
            if ($log->status === 'received') {
                throw new \RuntimeException('violates check constraint whatsapp_logs_status_check');
            }
        });

        $this->signedPost($this->received($tenant, 'hello', 'evt-log-fail'))->assertOk();

        Http::assertSent(fn ($r) => $r['type'] === 'text' && $r['text'] === 'Hi there!');
        $this->assertDatabaseHas('whatsapp_webhook_events', ['event_id' => 'evt-log-fail']);
    }

    public function test_old_event_ids_are_pruned_by_the_daily_schedule(): void
    {
        DB::table('whatsapp_webhook_events')->insert([
            ['event_id' => 'old', 'event' => 'x', 'created_at' => now()->subDays(20)],
            ['event_id' => 'recent', 'event' => 'x', 'created_at' => now()->subDays(2)],
        ]);

        $prune = collect(app(Schedule::class)->events())->first(fn ($e) => $e->description === 'whatsapp-gateway:prune-events');
        $this->assertNotNull($prune);
        $prune->run(app());

        $this->assertDatabaseMissing('whatsapp_webhook_events', ['event_id' => 'old']);
        $this->assertDatabaseHas('whatsapp_webhook_events', ['event_id' => 'recent']);
    }

    // ── Problems are visible in Superadmin → Error Logs ──────────

    public function test_a_rejected_webhook_is_recorded_in_error_logs_with_the_reason(): void
    {
        $this->enableGateway();
        $tenant = $this->setUpTenant();
        $this->gatewaySetting($tenant);

        // Secret differs from the gateway client's.
        $this->signedPost($this->received($tenant, 'hi'), null, 'some-other-secret')->assertStatus(401);
        $this->assertDatabaseHas('error_logs', ['http_status' => 401]);
        $this->assertStringContainsString('webhook secret', ErrorLog::latest('id')->first()->message);

        // The endpoint is public — the same problem isn't recorded again within 10 minutes.
        $this->signedPost($this->received($tenant, 'hi', 'd-2'), null, 'some-other-secret')->assertStatus(401);
        $this->assertSame(1, ErrorLog::count());

        // A different cause gets its own wording: the server clock / a stale request.
        Cache::flush();
        $this->signedPost($this->received($tenant, 'hi', 'd-3'), time() - 3600)->assertStatus(401);
        $this->assertStringContainsString('clock', ErrorLog::latest('id')->first()->message);
    }

    public function test_an_event_for_an_unknown_workspace_is_recorded_in_error_logs(): void
    {
        $this->enableGateway();
        $tenant = $this->setUpTenant();

        $event = $this->received($tenant, 'hi');
        $event['workspace']['id'] = 'tenant-9999';

        $this->signedPost($event)->assertOk();

        $this->assertStringContainsString('tenant-9999', ErrorLog::latest('id')->first()->message);
    }

    public function test_a_chatbot_crash_is_recorded_in_error_logs_but_still_acknowledged(): void
    {
        $this->enableGateway();
        $tenant = $this->setUpTenant();
        $this->gatewaySetting($tenant, ['chatbot_enabled' => true]);

        WhatsappChatbotSession::creating(function () {
            throw new \RuntimeException('chatbot session store is broken');
        });

        $this->signedPost($this->received($tenant, 'hello'))->assertOk();

        $this->assertDatabaseHas('error_logs', ['message' => 'chatbot session store is broken']);
    }

    // ── Billing warning (account.update) ─────────────────────────

    private function billingIssue(Tenant $tenant, mixed $value, string $id = 'b-1'): array
    {
        return [
            'id' => $id, 'event' => 'account.update', 'workspace' => ['id' => 'tenant-' . $tenant->id],
            'data' => ['field' => 'billing_issue', 'value' => $value],
        ];
    }

    public function test_billing_issue_shows_the_admin_a_warning_with_the_fix_link(): void
    {
        $this->enableGateway();
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $this->gatewaySetting($tenant);

        $this->signedPost($this->billingIssue($tenant, [
            'code' => 131042, 'message' => 'Add a payment method to send templates', 'fix_url' => 'https://business.facebook.com/billing',
        ]))->assertOk();

        $setting = WhatsappSetting::forTenant($tenant->id);
        $this->assertSame('Add a payment method to send templates', $setting->gateway_warning);
        $this->assertSame('https://business.facebook.com/billing', $setting->gateway_warning_url);

        $this->actingAs($admin)->get(route('tenant.whatsapp.api-settings'))
            ->assertOk()
            ->assertSee('WhatsApp needs your attention')
            ->assertSee('Add a payment method to send templates')
            ->assertSee('href="https://business.facebook.com/billing"', false);
    }

    public function test_billing_warning_is_for_admins_only(): void
    {
        $this->enableGateway();
        $tenant = $this->setUpTenant();
        $staff  = $this->makeUser($tenant, 'staff');
        $this->gatewaySetting($tenant, ['gateway_warning' => 'Billing needed']);

        $this->actingAs($staff)->get(route('tenant.whatsapp.api-settings'))
            ->assertOk()->assertDontSee('Billing needed');
    }

    public function test_billing_issue_is_cleared_when_resolved_and_a_non_https_link_is_dropped(): void
    {
        $this->enableGateway();
        $tenant = $this->setUpTenant();
        $this->gatewaySetting($tenant);

        $this->signedPost($this->billingIssue($tenant, ['code' => 131042, 'message' => 'Fix billing', 'fix_url' => 'javascript:alert(1)']))->assertOk();
        $setting = WhatsappSetting::forTenant($tenant->id);
        $this->assertSame('Fix billing', $setting->gateway_warning);
        $this->assertNull($setting->gateway_warning_url);

        $this->signedPost($this->billingIssue($tenant, null, 'b-2'))->assertOk();
        $setting = WhatsappSetting::forTenant($tenant->id);
        $this->assertNull($setting->gateway_warning);
        $this->assertNull($setting->gateway_warning_url);
    }

    public function test_other_account_update_fields_leave_the_warning_alone(): void
    {
        $this->enableGateway();
        $tenant = $this->setUpTenant();
        $this->gatewaySetting($tenant, ['gateway_warning' => 'Billing needed']);

        $this->signedPost([
            'id' => 'u-1', 'event' => 'account.update', 'workspace' => ['id' => 'tenant-' . $tenant->id],
            'data' => ['field' => 'quality_rating', 'value' => 'YELLOW'],
        ])->assertOk();

        $this->assertSame('Billing needed', WhatsappSetting::forTenant($tenant->id)->gateway_warning);
    }

    public function test_account_connected_warnings_are_kept_for_the_admin(): void
    {
        $this->enableGateway();
        $tenant = $this->setUpTenant();
        WhatsappSetting::create(['tenant_id' => $tenant->id, 'gateway_workspace_id' => 'tenant-' . $tenant->id]);

        $this->signedPost([
            'id' => 'w-1', 'event' => 'account.connected', 'workspace' => ['id' => 'tenant-' . $tenant->id],
            'data' => ['connected' => true, 'warnings' => ['Display name is pending review']],
        ])->assertOk();

        $this->assertSame('Display name is pending review', WhatsappSetting::forTenant($tenant->id)->gateway_warning);
    }

    public function test_refreshing_status_syncs_the_billing_warning_and_clears_it_once_fixed(): void
    {
        $this->enableGateway();
        $workspace = fn (array $whatsapp) => ['success' => true, 'data' => ['whatsapp' => [
            'connected' => true, 'phone_number_id' => 'PN7', 'display_phone_number' => '+91 90000 00000',
        ] + $whatsapp]];
        Http::fake(['gw.test/*/workspaces/*' => Http::sequence()
            ->push($workspace(['billing_issue' => true, 'billing_fix_url' => 'https://business.facebook.com/fix']), 200)
            ->push($workspace([]), 200)]);

        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        WhatsappSetting::create(['tenant_id' => $tenant->id, 'gateway_workspace_id' => 'tenant-' . $tenant->id]);

        $this->actingAs($admin)->post(route('tenant.whatsapp.gateway.sync'));
        $setting = WhatsappSetting::forTenant($tenant->id);
        $this->assertStringContainsString('billing', $setting->gateway_warning);
        $this->assertSame('https://business.facebook.com/fix', $setting->gateway_warning_url);

        $this->actingAs($admin)->post(route('tenant.whatsapp.gateway.sync'));
        $setting = WhatsappSetting::forTenant($tenant->id);
        $this->assertNull($setting->gateway_warning);
        $this->assertNull($setting->gateway_warning_url);
    }

    public function test_disconnecting_clears_the_warning(): void
    {
        $this->enableGateway();
        $tenant = $this->setUpTenant();
        $setting = $this->gatewaySetting($tenant, ['gateway_warning' => 'Billing needed', 'gateway_warning_url' => 'https://business.facebook.com/fix']);

        WhatsappGatewayClient::applyDisconnection($setting);

        $setting = WhatsappSetting::forTenant($tenant->id);
        $this->assertNull($setting->gateway_warning);
        $this->assertNull($setting->gateway_warning_url);
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

    public function test_superadmin_page_shows_what_the_gateway_has_delivered(): void
    {
        $this->enableGateway();
        $tenant = $this->setUpTenant();
        $super  = $this->makeUser($tenant, 'superadmin');
        $this->gatewaySetting($tenant);

        $this->actingAs($super)->get(route('superadmin.platform-settings.whatsapp-gateway'))
            ->assertOk()->assertSee('Nothing received yet');

        $this->signedPost($this->received($tenant, 'hi'))->assertOk();

        $this->actingAs($super)->get(route('superadmin.platform-settings.whatsapp-gateway'))
            ->assertOk()->assertDontSee('Nothing received yet')->assertSee('message.received');
    }

    public function test_chatbot_checklist_names_what_is_missing_and_turns_ready(): void
    {
        $this->enableGateway();
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');
        $this->gatewaySetting($tenant);

        // Connected, but the chatbot is off, there is no flow and nothing has arrived yet.
        $this->actingAs($admin)->get(route('tenant.whatsapp.api-settings'))
            ->assertOk()
            ->assertSee("Won't reply yet", false)
            ->assertSee('Tick "Enable Chatbot"')
            ->assertSee('No active flow')
            ->assertSee('None received yet');

        // Everything in place, and a customer message has come through the webhook.
        WhatsappSetting::forTenant($tenant->id)->update(['chatbot_enabled' => true]);
        WhatsappChatbotFlow::create([
            'tenant_id' => $tenant->id, 'name' => 'Default', 'trigger_keywords' => [], 'keyword_match' => 'any',
            'response_message' => 'Hi!', 'is_default' => true, 'is_active' => true,
        ]);
        $this->signedPost($this->received($tenant, 'hello'))->assertOk();

        $this->actingAs($admin)->get(route('tenant.whatsapp.api-settings'))
            ->assertOk()->assertSee('Ready to reply')->assertSee('including a default flow')->assertDontSee("Won't reply yet", false);
    }

    public function test_diagnose_says_the_gateway_itself_gets_no_customer_messages(): void
    {
        $this->enableGateway();
        Http::fake([
            'gw.test/*/workspaces/*/messages*' => Http::response(['success' => true, 'data' => []], 200),
            'gw.test/*/webhook-deliveries*'    => Http::response(['success' => true, 'data' => []], 200),
            'gw.test/*/client'                 => Http::response(['success' => false], 404),
        ]);
        $tenant = $this->setUpTenant();
        $super  = $this->makeUser($tenant, 'superadmin');
        $this->gatewaySetting($tenant);

        $res = $this->actingAs($super)->postJson(route('superadmin.platform-settings.whatsapp-gateway.diagnose'))->assertOk();

        $this->assertFalse($res->json('success'));
        $this->assertStringContainsString('NO message from any customer', $res->json('message'));
        Http::assertSent(fn ($r) => str_contains($r->url(), "/workspaces/tenant-{$tenant->id}/messages") && $r['direction'] === 'inbound');
    }

    public function test_diagnose_reads_messages_and_failed_deliveries_and_explains_a_401(): void
    {
        $this->enableGateway();
        Http::fake([
            'gw.test/*/workspaces/*/messages*' => Http::response(['success' => true, 'data' => [
                ['id' => 'm-1', 'direction' => 'inbound', 'phone' => '919876543210', 'text' => 'hi', 'created_at' => '2026-10-11T10:00:00+05:30'],
            ]], 200),
            'gw.test/*/webhook-deliveries*'    => Http::response(['success' => true, 'data' => [
                ['event' => 'message.received', 'last_response_code' => 401, 'last_error' => 'Invalid signature.'],
            ]], 200),
            'gw.test/*/client'                 => Http::response(['success' => false], 404),
        ]);
        $tenant = $this->setUpTenant();
        $super  = $this->makeUser($tenant, 'superadmin');
        $this->gatewaySetting($tenant);

        $res = $this->actingAs($super)->postJson(route('superadmin.platform-settings.whatsapp-gateway.diagnose'))->assertOk();

        $this->assertFalse($res->json('success'));
        $this->assertStringContainsString('the gateway has received 1 recent customer message', $res->json('message'));
        $this->assertStringContainsString('HTTP 401', $res->json('message'));
        $this->assertStringContainsString('webhook secret', $res->json('message'));
    }

    // $recentDeliveries = what the gateway lists for "latest deliveries, any status"
    // (the "failed" list is always empty here).
    private function fakeDiagnose(?string $registeredUrl, string $gatewayMessageAt, ?array $recentDeliveries = null): void
    {
        $recentDeliveries ??= [['event' => 'message.received', 'status' => 'delivered', 'response_code' => 200, 'created_at' => $gatewayMessageAt]];

        Http::fake([
            'gw.test/*/workspaces/*/messages*' => Http::response(['success' => true, 'data' => [
                ['id' => 'm-1', 'direction' => 'inbound', 'phone' => '919876543210', 'text' => 'hi', 'created_at' => $gatewayMessageAt],
            ]], 200),
            'gw.test/*/webhook-deliveries*'    => fn ($request) => Http::response([
                'success' => true,
                'data'    => str_contains($request->url(), 'status=failed') ? [] : $recentDeliveries,
            ], 200),
            'gw.test/*/client'                 => $registeredUrl === null
                ? Http::response(['success' => false], 404)
                : Http::response(['success' => true, 'data' => ['webhook_url' => $registeredUrl]], 200),
        ]);
    }

    public function test_diagnose_flags_a_webhook_url_registered_for_another_app(): void
    {
        $this->enableGateway();
        $this->fakeDiagnose('https://other-app.example/hook', now()->toIso8601String());
        $tenant = $this->setUpTenant();
        $super  = $this->makeUser($tenant, 'superadmin');
        $this->gatewaySetting($tenant);

        $res = $this->actingAs($super)->postJson(route('superadmin.platform-settings.whatsapp-gateway.diagnose'))->assertOk();

        $this->assertFalse($res->json('success'));
        $this->assertStringContainsString('https://other-app.example/hook, not to this CRM', $res->json('message'));
        $this->assertStringContainsString('never received a message.received event', $res->json('message'));
    }

    public function test_diagnose_reports_the_chatbot_off_when_everything_else_arrives(): void
    {
        $this->enableGateway();
        $this->fakeDiagnose(url('/webhook/wa-gateway'), now()->toIso8601String());
        $tenant = $this->setUpTenant();
        $super  = $this->makeUser($tenant, 'superadmin');
        $this->gatewaySetting($tenant);
        DB::table('whatsapp_webhook_events')->insert(['event_id' => 'e-1', 'event' => 'message.received', 'created_at' => now()]);

        $res = $this->actingAs($super)->postJson(route('superadmin.platform-settings.whatsapp-gateway.diagnose'))->assertOk();

        $this->assertFalse($res->json('success'));
        $this->assertStringContainsString('registered to send events to this CRM', $res->json('message'));
        $this->assertStringContainsString('received a message.received event', $res->json('message'));
        $this->assertStringContainsString('the chatbot is switched OFF', $res->json('message'));
    }

    public function test_diagnose_is_all_clear_when_the_message_arrived_and_the_chatbot_is_ready(): void
    {
        $this->enableGateway();
        $this->fakeDiagnose(url('/webhook/wa-gateway'), now()->toIso8601String());
        $tenant = $this->setUpTenant();
        $super  = $this->makeUser($tenant, 'superadmin');
        $this->gatewaySetting($tenant, ['chatbot_enabled' => true]);
        WhatsappChatbotFlow::create([
            'tenant_id' => $tenant->id, 'name' => 'Default', 'trigger_keywords' => [], 'keyword_match' => 'any',
            'response_message' => 'Hi!', 'is_default' => true, 'is_active' => true,
        ]);
        DB::table('whatsapp_webhook_events')->insert(['event_id' => 'e-1', 'event' => 'message.received', 'created_at' => now()]);
        WhatsappLog::create(['tenant_id' => $tenant->id, 'to_phone' => '919876543210', 'message' => 'hi', 'status' => 'received']);

        $res = $this->actingAs($super)->postJson(route('superadmin.platform-settings.whatsapp-gateway.diagnose'))->assertOk();

        $this->assertTrue($res->json('success'), $res->json('message'));
        $this->assertStringContainsString('chatbot is on with 1 active flow(s), including a default flow', $res->json('message'));
    }

    public function test_diagnose_says_when_the_gateway_never_tried_to_deliver(): void
    {
        $this->enableGateway();
        $this->fakeDiagnose(url('/webhook/wa-gateway'), now()->toIso8601String(), []);
        $tenant = $this->setUpTenant();
        $super  = $this->makeUser($tenant, 'superadmin');
        $this->gatewaySetting($tenant, ['chatbot_enabled' => true]);

        $res = $this->actingAs($super)->postJson(route('superadmin.platform-settings.whatsapp-gateway.diagnose'))->assertOk();

        $this->assertFalse($res->json('success'));
        $this->assertStringContainsString('has never created a webhook delivery', $res->json('message'));
    }

    public function test_diagnose_points_at_the_gateway_scheduler_for_queued_deliveries(): void
    {
        $this->enableGateway();
        $this->fakeDiagnose(url('/webhook/wa-gateway'), now()->toIso8601String(), [
            ['event' => 'message.received', 'status' => 'pending', 'created_at' => now()->toIso8601String()],
        ]);
        $tenant = $this->setUpTenant();
        $super  = $this->makeUser($tenant, 'superadmin');
        $this->gatewaySetting($tenant, ['chatbot_enabled' => true]);

        $res = $this->actingAs($super)->postJson(route('superadmin.platform-settings.whatsapp-gateway.diagnose'))->assertOk();

        $this->assertStringContainsString('queued but not delivered', $res->json('message'));
        $this->assertStringContainsString('gateway:retry-webhooks', $res->json('message'));
    }

    public function test_diagnose_notices_a_gateway_message_that_never_arrived(): void
    {
        $this->enableGateway();
        $this->fakeDiagnose(null, now()->toIso8601String());
        $tenant = $this->setUpTenant();
        $super  = $this->makeUser($tenant, 'superadmin');
        $this->gatewaySetting($tenant, ['chatbot_enabled' => true]);
        // The last event this CRM got is an hour older than the gateway's newest customer message.
        DB::table('whatsapp_webhook_events')->insert(['event_id' => 'e-old', 'event' => 'message.received', 'created_at' => now()->subHour()]);

        $res = $this->actingAs($super)->postJson(route('superadmin.platform-settings.whatsapp-gateway.diagnose'))->assertOk();

        $this->assertFalse($res->json('success'));
        $this->assertStringContainsString('never arrived here', $res->json('message'));
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
