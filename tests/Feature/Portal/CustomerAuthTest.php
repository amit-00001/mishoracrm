<?php

namespace Tests\Feature\Portal;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\CustomerOtp;
use App\Models\EmailSetting;
use App\Models\Tenant;
use App\Models\WhatsappSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class CustomerAuthTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private const PHONE = '9876543210';

    private function tenantWithContact(string $phone = self::PHONE): array
    {
        $tenant  = $this->setUpTenant();
        $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Ravi', 'phone' => $phone]);

        return [$tenant, $contact];
    }

    private function connectWhatsapp(Tenant $tenant): void
    {
        WhatsappSetting::create([
            'tenant_id'       => $tenant->id,
            'phone_number_id' => '123456',
            'access_token'    => 'token',
            'is_connected'    => true,
        ]);
    }

    // Pulls the 6-digit code out of the WhatsApp message the portal sent.
    private function sentCode(): string
    {
        $body = Http::recorded()->last()[0]->data()['text']['body'];
        preg_match('/\b(\d{6})\b/', $body, $m);

        return $m[1];
    }

    private function issueCode(Customer $customer): string
    {
        [, $code] = CustomerOtp::issue($customer, $customer->phone, 'whatsapp', null);

        return $code;
    }

    // ── Local-dev test login (config/portal.php) ───────────────

    private function enableTestLogin(string $phone = '9999999999', string $otp = '123456'): void
    {
        config(['portal.test_login.phone' => $phone, 'portal.test_login.otp' => $otp]);
        \App\Models\PlatformSetting::set(\App\Support\PortalTestLogin::SETTING, '1');
    }

    public function test_test_login_signs_the_dev_number_in_with_the_fixed_code_and_no_contact(): void
    {
        Http::fake();
        $this->enableTestLogin();

        $this->post(route('portal.login.request-otp'), ['phone' => '99999 99999'])
            ->assertOk()
            ->assertSee('Test login is on')
            ->assertSee('123456');

        Http::assertNothingSent();
        $customer = Customer::where('phone', '9999999999')->first();
        $this->assertNotNull($customer);

        $this->post(route('portal.login.verify'), ['phone' => '9999999999', 'code' => '123456'])
            ->assertRedirect(route('portal.pin.setup'));

        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_test_login_still_links_the_contacts_on_that_number(): void
    {
        $this->enableTestLogin();
        $tenant  = $this->setUpTenant();
        $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Test Customer', 'phone' => '9999999999']);

        $this->post(route('portal.login.request-otp'), ['phone' => '9999999999'])->assertOk();

        $customer = Customer::where('phone', '9999999999')->first();
        $this->assertSame($customer->id, $contact->fresh()->customer_id);
        $this->assertTrue($contact->fresh()->phone_verified);
    }

    public function test_test_login_honours_a_custom_number_and_code(): void
    {
        $this->enableTestLogin('9000000000', '654321');

        $this->post(route('portal.login.request-otp'), ['phone' => '9000000000'])->assertOk()->assertSee('654321');
        $this->post(route('portal.login.verify'), ['phone' => '9000000000', 'code' => '654321'])->assertRedirect();

        $this->assertAuthenticated('customer');
    }

    public function test_test_login_rejects_a_wrong_code(): void
    {
        $this->enableTestLogin();
        $this->post(route('portal.login.request-otp'), ['phone' => '9999999999']);

        $this->post(route('portal.login.verify'), ['phone' => '9999999999', 'code' => '000000'])
            ->assertOk()->assertSee('incorrect or has expired');

        $this->assertGuest('customer');
    }

    public function test_test_login_is_off_unless_enabled(): void
    {
        $this->post(route('portal.login.request-otp'), ['phone' => '9999999999'])
            ->assertOk()->assertDontSee('Test login is on');

        $this->assertSame(0, Customer::withTrashed()->count());
        $this->post(route('portal.login.verify'), ['phone' => '9999999999', 'code' => '123456']);
        $this->assertGuest('customer');
    }

    public function test_test_login_stops_the_moment_the_superadmin_switches_it_off(): void
    {
        $this->enableTestLogin();
        $this->assertTrue(\App\Support\PortalTestLogin::matches('9999999999'));

        \App\Models\PlatformSetting::set(\App\Support\PortalTestLogin::SETTING, '0');

        $this->assertFalse(\App\Support\PortalTestLogin::active());
        $this->assertFalse(\App\Support\PortalTestLogin::matches('9999999999'));

        $this->post(route('portal.login.request-otp'), ['phone' => '9999999999'])
            ->assertOk()->assertDontSee('Test login is on');
        $this->assertSame(0, Customer::withTrashed()->count());

        $this->post(route('portal.login.verify'), ['phone' => '9999999999', 'code' => '123456']);
        $this->assertGuest('customer');
    }

    public function test_test_login_only_covers_the_configured_number(): void
    {
        $this->enableTestLogin();

        $this->post(route('portal.login.request-otp'), ['phone' => '9888888888'])->assertOk()->assertDontSee('Test login is on');

        $this->assertSame(0, Customer::count());
    }

    public function test_test_login_is_not_blocked_by_the_otp_limits_but_everyone_else_still_is(): void
    {
        Http::fake();
        $this->enableTestLogin();

        // Well past the per-phone resend window, hourly cap and the per-IP throttle.
        for ($i = 0; $i < 8; $i++) {
            $this->post(route('portal.login.request-otp'), ['phone' => '9999999999'])->assertOk();
        }
        $this->post(route('portal.login.verify'), ['phone' => '9999999999', 'code' => '123456'])->assertRedirect();

        // A normal number is still throttled per IP after 5 requests / 10 minutes.
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('portal.login.request-otp'), ['phone' => '9000000' . str_pad((string) $i, 3, '0', STR_PAD_LEFT)]);
        }
        $this->post(route('portal.login.request-otp'), ['phone' => '9000000777'])->assertStatus(429);
    }

    // ── Login page + guard redirects ───────────────────────────

    public function test_login_page_renders(): void
    {
        $this->get(route('portal.login'))->assertOk()->assertSee('Sign in with your phone');
    }

    public function test_guests_hitting_portal_routes_go_to_wallet_login_not_staff_login(): void
    {
        $this->get('/wallet/pin')->assertRedirect(route('portal.login'));
        $this->post(route('portal.logout'))->assertRedirect(route('portal.login'));
    }

    public function test_logged_in_customer_is_sent_past_the_login_page(): void
    {
        $customer = Customer::create(['phone' => self::PHONE]);

        $this->actingAs($customer, 'customer')->get(route('portal.login'))->assertRedirect('/wallet');
    }

    // ── Request code (Gap 4: no Customer for unknown phones, no leak) ──

    public function test_unknown_phone_creates_no_customer_and_no_code_but_gets_the_same_reply(): void
    {
        Http::fake();
        $this->setUpTenant();

        $this->post(route('portal.login.request-otp'), ['phone' => '9000000099'])
            ->assertOk()
            ->assertSee('6-digit code is on its way');

        $this->assertSame(0, Customer::withTrashed()->count());
        $this->assertSame(0, CustomerOtp::count());
        Http::assertNothingSent();
    }

    public function test_known_phone_gets_the_same_reply_creates_customer_links_and_sends_a_code(): void
    {
        Http::fake();
        [$tenant, $contact] = $this->tenantWithContact();
        $this->connectWhatsapp($tenant);

        $this->post(route('portal.login.request-otp'), ['phone' => '+91 98765 43210'])
            ->assertOk()
            ->assertSee('6-digit code is on its way');

        $customer = Customer::where('phone', self::PHONE)->first();
        $this->assertNotNull($customer);
        $this->assertSame($customer->id, $contact->fresh()->customer_id);
        $this->assertDatabaseHas('customer_otps', ['customer_id' => $customer->id, 'phone' => self::PHONE, 'channel' => 'whatsapp']);
        Http::assertSentCount(1);
    }

    public function test_known_phone_with_no_delivery_channel_still_gets_the_generic_reply(): void
    {
        Http::fake();
        $this->tenantWithContact();

        $this->post(route('portal.login.request-otp'), ['phone' => self::PHONE])
            ->assertOk()
            ->assertSee('6-digit code is on its way');

        $this->assertSame(0, CustomerOtp::count());
        Http::assertNothingSent();
    }

    // Login codes go out on WhatsApp only — a shop that has email connected but
    // no WhatsApp must not deliver one (email OTP is deliberately off for now).
    public function test_a_shop_with_only_email_connected_delivers_no_code(): void
    {
        Http::fake();
        [$tenant, $contact] = $this->tenantWithContact();
        $contact->update(['email' => 'ravi@example.com']);
        EmailSetting::create([
            'tenant_id'    => $tenant->id,
            'smtp_host'    => 'smtp.example.com',
            'smtp_port'    => 587,
            'from_address' => 'shop@example.com',
            'is_connected' => true,
        ]);

        $this->post(route('portal.login.request-otp'), ['phone' => self::PHONE])
            ->assertOk()
            ->assertSee('6-digit code is on its way');

        $this->assertSame(0, CustomerOtp::count());
        Http::assertNothingSent();
    }

    public function test_too_short_a_phone_is_a_validation_error(): void
    {
        $this->post(route('portal.login.request-otp'), ['phone' => '12345'])
            ->assertSessionHasErrors('phone');
    }

    public function test_second_request_within_the_resend_window_is_refused_even_for_unknown_phones(): void
    {
        Http::fake();
        $this->setUpTenant();

        $this->post(route('portal.login.request-otp'), ['phone' => '9000000099'])->assertOk();
        $this->post(route('portal.login.request-otp'), ['phone' => '9000000099'])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_hourly_cap_blocks_a_fourth_code(): void
    {
        Http::fake();
        $this->setUpTenant();

        for ($i = 0; $i < CustomerOtp::HOURLY_CAP; $i++) {
            Cache::forget('portal_otp_resend:' . '9000000099');
            $this->post(route('portal.login.request-otp'), ['phone' => '9000000099'])->assertOk();
        }

        Cache::forget('portal_otp_resend:9000000099');
        $this->post(route('portal.login.request-otp'), ['phone' => '9000000099'])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_soft_deleted_customer_is_restored_on_a_new_code_request(): void
    {
        Http::fake();
        [$tenant] = $this->tenantWithContact();
        $this->connectWhatsapp($tenant);
        $customer = Customer::create(['phone' => self::PHONE]);
        $customer->delete();

        $this->post(route('portal.login.request-otp'), ['phone' => self::PHONE])->assertOk();

        $this->assertFalse($customer->fresh()->trashed());
        $this->assertSame(1, Customer::count());
    }

    // ── Verify ─────────────────────────────────────────────────

    public function test_full_otp_flow_logs_the_customer_in_and_offers_pin_setup(): void
    {
        Http::fake();
        [$tenant] = $this->tenantWithContact();
        $this->connectWhatsapp($tenant);

        $this->post(route('portal.login.request-otp'), ['phone' => self::PHONE])->assertOk();

        $this->post(route('portal.login.verify'), ['phone' => self::PHONE, 'code' => $this->sentCode()])
            ->assertRedirect(route('portal.pin.setup'));

        $customer = Customer::where('phone', self::PHONE)->first();
        $this->assertAuthenticatedAs($customer, 'customer');
        $this->assertNotNull($customer->fresh()->last_login_at);
        $this->assertGuest('web');
    }

    public function test_customer_with_a_pin_goes_straight_to_the_wallet_after_otp(): void
    {
        $customer = Customer::create(['phone' => self::PHONE, 'pin_hash' => Hash::make('1234')]);
        $code     = $this->issueCode($customer);

        $this->post(route('portal.login.verify'), ['phone' => self::PHONE, 'code' => $code])
            ->assertRedirect('/wallet');
    }

    public function test_wrong_code_does_not_log_in_and_counts_an_attempt(): void
    {
        $customer = Customer::create(['phone' => self::PHONE]);
        $this->issueCode($customer);

        $this->post(route('portal.login.verify'), ['phone' => self::PHONE, 'code' => '000000'])
            ->assertOk()
            ->assertSee('incorrect or has expired');

        $this->assertGuest('customer');
        $this->assertSame(1, CustomerOtp::first()->attempts);
    }

    public function test_code_locks_after_five_bad_attempts_even_for_the_right_code(): void
    {
        $customer = Customer::create(['phone' => self::PHONE]);
        $code     = $this->issueCode($customer);

        for ($i = 0; $i < CustomerOtp::MAX_ATTEMPTS; $i++) {
            $this->post(route('portal.login.verify'), ['phone' => self::PHONE, 'code' => '000000']);
        }

        $this->post(route('portal.login.verify'), ['phone' => self::PHONE, 'code' => $code])->assertOk();
        $this->assertGuest('customer');
        $this->assertTrue(CustomerOtp::first()->isLocked());
    }

    public function test_expired_code_is_refused(): void
    {
        $customer = Customer::create(['phone' => self::PHONE]);
        $code     = $this->issueCode($customer);
        CustomerOtp::query()->update(['expires_at' => now()->subMinute()]);

        $this->post(route('portal.login.verify'), ['phone' => self::PHONE, 'code' => $code])->assertOk();
        $this->assertGuest('customer');
    }

    public function test_a_code_is_single_use(): void
    {
        $customer = Customer::create(['phone' => self::PHONE]);
        $code     = $this->issueCode($customer);

        $this->post(route('portal.login.verify'), ['phone' => self::PHONE, 'code' => $code])->assertRedirect();
        $this->post(route('portal.logout'));

        $this->post(route('portal.login.verify'), ['phone' => self::PHONE, 'code' => $code])->assertOk();
        $this->assertGuest('customer');
    }

    public function test_blocked_customer_cannot_log_in_by_code(): void
    {
        $customer = Customer::create(['phone' => self::PHONE, 'blocked_at' => now()]);
        $code     = $this->issueCode($customer);

        $this->post(route('portal.login.verify'), ['phone' => self::PHONE, 'code' => $code])->assertOk();
        $this->assertGuest('customer');
    }

    // ── PIN ────────────────────────────────────────────────────

    public function test_customer_can_set_a_pin(): void
    {
        $customer = Customer::create(['phone' => self::PHONE]);

        $this->actingAs($customer, 'customer')
            ->post(route('portal.pin.set'), ['pin' => '4321', 'pin_confirmation' => '4321'])
            ->assertRedirect('/wallet');

        $this->assertTrue(Hash::check('4321', $customer->fresh()->pin_hash));
    }

    public function test_pin_must_be_4_to_6_digits_and_confirmed(): void
    {
        $customer = Customer::create(['phone' => self::PHONE]);

        $this->actingAs($customer, 'customer')
            ->post(route('portal.pin.set'), ['pin' => '12', 'pin_confirmation' => '12'])
            ->assertSessionHasErrors('pin');
        $this->actingAs($customer, 'customer')
            ->post(route('portal.pin.set'), ['pin' => '1234', 'pin_confirmation' => '9999'])
            ->assertSessionHasErrors('pin');
        $this->actingAs($customer, 'customer')
            ->post(route('portal.pin.set'), ['pin' => 'abcd', 'pin_confirmation' => 'abcd'])
            ->assertSessionHasErrors('pin');
    }

    public function test_pin_login_works(): void
    {
        $customer = Customer::create(['phone' => self::PHONE, 'pin_hash' => Hash::make('4321')]);

        $this->post(route('portal.login.pin'), ['phone' => '98765 43210', 'pin' => '4321'])
            ->assertRedirect('/wallet');

        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_wrong_pin_and_unknown_phone_get_the_same_message(): void
    {
        Customer::create(['phone' => self::PHONE, 'pin_hash' => Hash::make('4321')]);

        $wrong = $this->post(route('portal.login.pin'), ['phone' => self::PHONE, 'pin' => '0000']);
        $wrong->assertRedirect()->assertSessionHas('error');
        $wrongMessage = session('error');

        $unknown = $this->post(route('portal.login.pin'), ['phone' => '9000000099', 'pin' => '0000']);
        $unknown->assertRedirect()->assertSessionHas('error');

        $this->assertSame($wrongMessage, session('error'));
        $this->assertGuest('customer');
    }

    public function test_fifth_wrong_pin_locks_pin_login_even_for_the_right_pin(): void
    {
        // Start at 4 misses so the test stays under the route's own throttle.
        $customer = Customer::create(['phone' => self::PHONE, 'pin_hash' => Hash::make('4321'), 'pin_attempts' => 4]);

        $this->post(route('portal.login.pin'), ['phone' => self::PHONE, 'pin' => '0000']);
        $this->assertTrue($customer->fresh()->isPinLocked());

        $this->post(route('portal.login.pin'), ['phone' => self::PHONE, 'pin' => '4321']);
        $this->assertGuest('customer');
    }

    public function test_wrong_pin_counts_an_attempt_and_a_right_pin_resets_it(): void
    {
        $customer = Customer::create(['phone' => self::PHONE, 'pin_hash' => Hash::make('4321')]);

        $this->post(route('portal.login.pin'), ['phone' => self::PHONE, 'pin' => '0000']);
        $this->assertSame(1, $customer->fresh()->pin_attempts);

        $this->post(route('portal.login.pin'), ['phone' => self::PHONE, 'pin' => '4321'])->assertRedirect('/wallet');
        $this->assertSame(0, $customer->fresh()->pin_attempts);
    }

    public function test_otp_login_still_works_while_the_pin_is_locked_and_clears_the_lock(): void
    {
        $customer = Customer::create([
            'phone' => self::PHONE, 'pin_hash' => Hash::make('4321'),
            'pin_locked_until' => now()->addMinutes(20),
        ]);
        $code = $this->issueCode($customer);

        $this->post(route('portal.login.verify'), ['phone' => self::PHONE, 'code' => $code])->assertRedirect('/wallet');

        $this->assertFalse($customer->fresh()->isPinLocked());
    }

    public function test_blocked_customer_cannot_log_in_by_pin(): void
    {
        Customer::create(['phone' => self::PHONE, 'pin_hash' => Hash::make('4321'), 'blocked_at' => now()]);

        $this->post(route('portal.login.pin'), ['phone' => self::PHONE, 'pin' => '4321']);
        $this->assertGuest('customer');
    }

    // ── Logout ─────────────────────────────────────────────────

    public function test_logout_signs_the_customer_out(): void
    {
        $customer = Customer::create(['phone' => self::PHONE]);

        $this->actingAs($customer, 'customer')->post(route('portal.logout'))->assertRedirect(route('portal.login'));

        $this->assertGuest('customer');
    }

    // ── Prune ──────────────────────────────────────────────────

    public function test_prune_only_removes_old_never_logged_in_unlinked_customers(): void
    {
        [$tenant, $contact] = $this->tenantWithContact();

        $junk = Customer::create(['phone' => '9000000001']);
        $junk->forceFill(['created_at' => now()->subDays(45)])->save();

        $fresh = Customer::create(['phone' => '9000000002']);

        $loggedIn = Customer::create(['phone' => '9000000003', 'last_login_at' => now()->subDays(40)]);
        $loggedIn->forceFill(['created_at' => now()->subDays(45)])->save();

        $linked = Customer::create(['phone' => '9000000004']);
        $linked->forceFill(['created_at' => now()->subDays(45)])->save();
        $contact->forceFill(['customer_id' => $linked->id])->save();

        $this->artisan('customers:prune')->assertSuccessful();

        $this->assertSoftDeleted('customers', ['id' => $junk->id]);
        $this->assertNotSoftDeleted('customers', ['id' => $fresh->id]);
        $this->assertNotSoftDeleted('customers', ['id' => $loggedIn->id]);
        $this->assertNotSoftDeleted('customers', ['id' => $linked->id]);
    }
}
