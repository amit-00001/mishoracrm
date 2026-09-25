<?php

namespace Tests\Feature\Portal;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

class SuperAdminCustomersTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function superadmin()
    {
        return $this->makeUser($this->setUpTenant(), 'superadmin');
    }

    private function customer(string $phone, array $attrs = []): Customer
    {
        $customer = Customer::create(['phone' => $phone]);
        if ($attrs) {
            $customer->forceFill($attrs)->save();
        }

        return $customer;
    }

    private function link(Customer $customer, Tenant $tenant, bool $verified = true): Contact
    {
        $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'C' . uniqid(), 'phone' => $customer->phone]);
        $contact->forceFill(['customer_id' => $customer->id, 'phone_verified' => $verified])->save();

        return $contact;
    }

    // ── Access ─────────────────────────────────────────────────

    public function test_only_a_superadmin_can_see_or_change_customers(): void
    {
        $tenant   = $this->setUpTenant();
        $admin    = $this->makeUser($tenant, 'tenant_admin');
        $customer = $this->customer('9876543210');

        $this->get(route('superadmin.customers.index'))->assertRedirect();

        $this->actingAs($admin)->get(route('superadmin.customers.index'))->assertForbidden();
        $this->actingAs($admin)->post(route('superadmin.customers.block', $customer->id))->assertForbidden();
        $this->actingAs($admin)->post(route('superadmin.customers.unblock', $customer->id))->assertForbidden();

        $this->assertNull($customer->fresh()->blocked_at);
    }

    public function test_the_page_renders_for_a_superadmin_and_is_linked_in_the_sidebar(): void
    {
        $this->actingAs($this->superadmin())->get(route('superadmin.customers.index'))
            ->assertOk()
            ->assertSee('Portal Customers')
            ->assertSee(route('superadmin.customers.index'), false);
    }

    // ── Metrics ────────────────────────────────────────────────

    public function test_metrics_count_verified_shop_links_only(): void
    {
        $s1 = $this->setUpTenant();
        $s2 = Tenant::factory()->create();

        $a = $this->customer('9000000001', ['last_login_at' => now()->subDays(5)]);
        $b = $this->customer('9000000002', ['last_login_at' => now()->subDays(60)]);
        $c = $this->customer('9000000003');                                // never used
        $d = $this->customer('9000000004', ['blocked_at' => now()]);

        $this->link($a, $s1);
        $this->link($a, $s1);          // second contact, same shop — still one shop
        $this->link($a, $s2);
        $this->link($b, $s1);
        $this->link($c, $s1, false);   // pending only — doesn't count as a shop

        $metrics = $this->actingAs($this->makeUser($s1, 'superadmin'))
            ->get(route('superadmin.customers.index'))->assertOk()->viewData('metrics');

        $this->assertSame(4, $metrics['total']);
        $this->assertSame(2, $metrics['with_shops']);   // a and b
        $this->assertSame(1.5, (float) $metrics['avg_shops']); // (a:2 + b:1) / 2
        $this->assertSame(1, $metrics['active_30d']);
        $this->assertSame(1, $metrics['blocked']);
    }

    public function test_each_row_shows_its_distinct_verified_shop_count(): void
    {
        $s1 = $this->setUpTenant();
        $s2 = Tenant::factory()->create();
        $a  = $this->customer('9000000001', ['last_login_at' => now()]);

        $this->link($a, $s1);
        $this->link($a, $s1);
        $this->link($a, $s2);
        $this->link($a, $s2, false);

        $rows = $this->actingAs($this->makeUser($s1, 'superadmin'))
            ->get(route('superadmin.customers.index'))->viewData('customers');

        $this->assertSame(2, (int) $rows->firstWhere('id', $a->id)->linked_shops);
    }

    // ── List / filters ─────────────────────────────────────────

    public function test_junk_accounts_are_hidden_by_default_and_shown_on_request(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'superadmin');

        $used   = $this->customer('9111111111', ['last_login_at' => now()]);
        $linked = $this->customer('9222222222');
        $this->link($linked, $tenant);
        $junk   = $this->customer('9333333333');

        $default = $this->actingAs($admin)->get(route('superadmin.customers.index'))->viewData('customers')->pluck('id');
        $this->assertTrue($default->contains($used->id));
        $this->assertTrue($default->contains($linked->id));
        $this->assertFalse($default->contains($junk->id));

        $all = $this->actingAs($admin)->get(route('superadmin.customers.index', ['scope' => 'all']))->viewData('customers')->pluck('id');
        $this->assertTrue($all->contains($junk->id));
    }

    public function test_search_and_status_filters(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'superadmin');

        $ravi   = $this->customer('9876543210', ['name' => 'Ravi Kumar', 'last_login_at' => now()]);
        $sunita = $this->customer('9123456789', ['name' => 'Sunita', 'last_login_at' => now(), 'blocked_at' => now()]);

        $ids = fn (array $q) => $this->actingAs($admin)->get(route('superadmin.customers.index', $q))->viewData('customers')->pluck('id')->all();

        $this->assertSame([$ravi->id], $ids(['q' => '98765']));
        $this->assertSame([$ravi->id], $ids(['q' => '+91 98765 43210']));
        $this->assertSame([$sunita->id], $ids(['q' => 'sunita']));
        $this->assertSame([$sunita->id], $ids(['status' => 'blocked']));
        $this->assertSame([$ravi->id], $ids(['status' => 'active']));
    }

    // ── Block / unblock ────────────────────────────────────────

    public function test_blocking_locks_the_customer_out_immediately(): void
    {
        $tenant   = $this->setUpTenant();
        $admin    = $this->makeUser($tenant, 'superadmin');
        $customer = $this->customer('9876543210', ['remember_token' => 'old-token']);

        $this->actingAs($admin)->post(route('superadmin.customers.block', $customer->id))->assertRedirect();

        $fresh = $customer->fresh();
        $this->assertNotNull($fresh->blocked_at);
        $this->assertNotSame('old-token', $fresh->remember_token);

        // Their next wallet request is refused even with a live session.
        $this->app['auth']->guard('customer')->setUser($fresh);
        $this->get('/wallet')->assertForbidden();
    }

    public function test_unblocking_restores_access(): void
    {
        $tenant   = $this->setUpTenant();
        $admin    = $this->makeUser($tenant, 'superadmin');
        $customer = $this->customer('9876543210', ['blocked_at' => now()]);

        $this->actingAs($admin)->post(route('superadmin.customers.unblock', $customer->id))->assertRedirect();

        $this->assertNull($customer->fresh()->blocked_at);
    }

    public function test_a_deleted_customer_cannot_be_blocked_or_listed(): void
    {
        $tenant   = $this->setUpTenant();
        $admin    = $this->makeUser($tenant, 'superadmin');
        $customer = $this->customer('9876543210', ['last_login_at' => now()]);
        $customer->delete();

        $this->actingAs($admin)->post(route('superadmin.customers.block', $customer->id))->assertNotFound();
        $this->assertFalse($this->actingAs($admin)->get(route('superadmin.customers.index'))->viewData('customers')->pluck('id')->contains($customer->id));
    }

    // ── Test login switch ──────────────────────────────────────

    private function switchOn($admin = null): void
    {
        $this->actingAs($admin ?? $this->superadmin())
            ->post(route('superadmin.customers.test-login.enable'))->assertRedirect();
    }

    public function test_only_a_superadmin_can_flip_the_test_login_switch(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $this->actingAs($admin)->post(route('superadmin.customers.test-login.enable'))->assertForbidden();
        $this->actingAs($admin)->post(route('superadmin.customers.test-login.disable'))->assertForbidden();

        $this->assertFalse(\App\Support\PortalTestLogin::active());
    }

    public function test_the_page_shows_the_switch_and_its_state(): void
    {
        $admin = $this->superadmin();

        $this->actingAs($admin)->get(route('superadmin.customers.index'))
            ->assertOk()->assertSee('Test login')->assertSee('Turn ON')->assertSee('9999999999')->assertSee('123456');

        $this->actingAs($admin)->post(route('superadmin.customers.test-login.enable'));

        $this->actingAs($admin)->get(route('superadmin.customers.index'))
            ->assertOk()->assertSee('Turn OFF')->assertDontSee('Turn ON');
    }

    public function test_switching_on_adds_the_test_customer_to_the_first_five_tenants_only(): void
    {
        $tenants = collect([$this->setUpTenant()])->merge(Tenant::factory()->count(5)->create()); // 6 tenants
        $admin   = $this->makeUser($tenants->first(), 'superadmin');

        $this->actingAs($admin)->post(route('superadmin.customers.test-login.enable'))
            ->assertRedirect()->assertSessionHas('success', fn ($m) => str_contains($m, '5 tenants'));

        $this->assertTrue(\App\Support\PortalTestLogin::active());

        $customer = Customer::where('phone', '9999999999')->firstOrFail();
        $sorted   = $tenants->sortBy('id')->values();

        foreach ($sorted->take(5) as $tenant) {
            $contact = Contact::withoutGlobalScopes()->where('tenant_id', $tenant->id)->where('phone_normalized', '9999999999')->firstOrFail();
            $this->assertSame('Test Customer', $contact->name);
            $this->assertSame(80, $contact->loyalty_points);
            $this->assertSame($customer->id, $contact->customer_id);
            $this->assertTrue($contact->phone_verified);
            $fresh = $tenant->fresh();
            $this->assertTrue($fresh->hasModuleEnabled('loyalty'));
            $this->assertTrue($fresh->hasModuleEnabled('customer_portal'));
        }

        $this->assertSame(0, Contact::withoutGlobalScopes()->where('tenant_id', $sorted[5]->id)->where('phone_normalized', '9999999999')->count());
        $this->assertCount(5, $customer->walletContacts());
    }

    public function test_switching_on_twice_creates_no_duplicates(): void
    {
        $admin = $this->superadmin();
        $this->switchOn($admin);
        $this->switchOn($admin);

        $this->assertSame(1, Customer::where('phone', '9999999999')->count());
        $this->assertSame(1, Contact::withoutGlobalScopes()->where('phone_normalized', '9999999999')->count());
    }

    public function test_after_switching_on_the_wallet_login_works_with_the_fixed_code(): void
    {
        $this->switchOn();

        $this->post(route('portal.login.request-otp'), ['phone' => '9999999999'])->assertOk()->assertSee('123456');
        $this->post(route('portal.login.verify'), ['phone' => '9999999999', 'code' => '123456'])->assertRedirect();

        $this->assertAuthenticated('customer');
        $this->get('/wallet')->assertOk()->assertSee('80');
    }

    public function test_switching_off_stops_the_login_and_removes_only_the_test_data(): void
    {
        $tenantA = $this->setUpTenant();
        $tenantB = Tenant::factory()->create();
        $admin   = $this->makeUser($tenantA, 'superadmin');
        // Shop A already has a real contact on that number, so the seeder leaves it alone
        // (it only links it); shop B gets a fresh "Test Customer" contact.
        $real    = Contact::create(['tenant_id' => $tenantA->id, 'name' => 'Real Person', 'phone' => '9999999999']);

        $this->actingAs($admin)->post(route('superadmin.customers.test-login.enable'));
        $this->assertSame(2, Contact::withoutGlobalScopes()->where('phone_normalized', '9999999999')->count());
        $this->assertSame(1, Contact::withoutGlobalScopes()->where('tenant_id', $tenantB->id)->where('name', 'Test Customer')->count());

        $this->actingAs($admin)->post(route('superadmin.customers.test-login.disable'))->assertRedirect();

        $this->assertFalse(\App\Support\PortalTestLogin::active());
        $this->assertSame(0, Customer::withTrashed()->where('phone', '9999999999')->count());

        $left = Contact::withoutGlobalScopes()->where('phone_normalized', '9999999999')->get();
        $this->assertCount(1, $left);                                  // the real contact survives
        $this->assertSame($real->id, $left->first()->id);
        $this->assertNull($left->first()->customer_id);

        $this->post(route('portal.login.request-otp'), ['phone' => '9999999999'])->assertOk()->assertDontSee('Test login is on');
        $this->assertGuest('customer');
    }

    public function test_the_seeder_only_acts_while_the_switch_is_on(): void
    {
        $this->setUpTenant();

        $this->seed(\Database\Seeders\PortalTestCustomerSeeder::class);
        $this->assertSame(0, Customer::count());

        \App\Models\PlatformSetting::set(\App\Support\PortalTestLogin::SETTING, '1');
        $this->seed(\Database\Seeders\PortalTestCustomerSeeder::class);
        $this->assertSame(1, Customer::where('phone', '9999999999')->count());
        $this->assertSame(1, Contact::withoutGlobalScopes()->where('phone_normalized', '9999999999')->count());
    }
}
