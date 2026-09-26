<?php

namespace Tests\Feature\Tenant;

use App\Models\Attendance;
use App\Models\AttendanceScreenshot;
use App\Models\Lead;
use App\Models\Staff;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

// QA audit P2: a custom role with four unrelated permissions could still open
// /leads/create (and, across the app, mutate contacts/invoices/etc.) because only
// the navigation hid those links. Each route below must:
//   - return 403 to a signed-in user whose role lacks the permission, and
//   - NOT return 403 to a user whose role has exactly that permission
//     (any other status — 200/302/404/422 — proves it got past authorization).
class RoutePermissionMatrixTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    // A permission unrelated to everything under test — mirrors the audit's low-privilege role.
    private const UNRELATED = ['notifications.view', 'reports.view_basic', 'subscriptions.view', 'appointments.view'];

    /** [method, uri, permission that unlocks it] — id 999999 never exists, so allowed calls 404 or redirect. */
    public static function guardedRoutes(): array
    {
        $rows = [
            // Leads / deals / purchasing / quotations — form pages must match their POST gate
            ['GET',  '/leads/create',                  'leads.create'],
            ['GET',  '/deals/create',                  'deals.create'],
            ['GET',  '/purchase-orders/create',        'purchase_orders.create'],
            ['GET',  '/quotations/create',             'quotations.create'],
            ['GET',  '/quotation-terms-templates/999999/edit', 'quotations.edit'],

            // Contacts
            ['GET',    '/contacts/create',                'contacts.create'],
            ['POST',   '/contacts',                       'contacts.create'],
            ['POST',   '/contacts/check-duplicate',       'contacts.create'],
            ['GET',    '/contacts/999999/edit',           'contacts.edit_own'],
            ['PUT',    '/contacts/999999',                'contacts.edit_own'],
            ['DELETE', '/contacts/999999/attachments/1',  'contacts.edit_own'],
            ['DELETE', '/contacts/999999',                'contacts.delete'],

            // Invoices
            ['GET',    '/invoices/create',                'invoices.create'],
            ['POST',   '/invoices',                       'invoices.create'],
            ['GET',    '/invoices/999999/edit',           'invoices.edit'],
            ['PUT',    '/invoices/999999',                'invoices.edit'],
            ['POST',   '/invoices/999999/status',         'invoices.edit'],
            ['POST',   '/invoices/999999/send',           'invoices.send'],
            ['POST',   '/invoices/999999/send-whatsapp',  'invoices.send'],
            ['POST',   '/invoices/999999/record-payment', 'invoices.record_payment'],
            ['DELETE', '/invoices/999999',                'invoices.delete'],

            // Custom fields
            ['GET',    '/custom-fields/lead/create',      'settings.custom_fields'],
            ['POST',   '/custom-fields/lead',             'settings.custom_fields'],
            ['PUT',    '/custom-fields/lead/999999',      'settings.custom_fields'],
            ['POST',   '/custom-fields/999999/toggle',    'settings.custom_fields'],
            ['POST',   '/custom-fields/reorder',          'settings.custom_fields'],
            ['DELETE', '/custom-fields/999999',           'settings.custom_fields'],

            // Messaging
            ['POST',   '/whatsapp/send',                  'whatsapp.send'],
            ['POST',   '/whatsapp/bulk',                  'whatsapp.bulk_send'],
            ['POST',   '/whatsapp/templates',             'whatsapp.manage_templates'],
            ['DELETE', '/whatsapp/templates/999999',      'whatsapp.manage_templates'],
            ['POST',   '/email/send',                     'email.send'],
            ['POST',   '/email/bulk',                     'email.bulk_send'],
            ['POST',   '/email/templates',                'email.manage_templates'],
            ['DELETE', '/email/templates/999999',         'email.manage_templates'],

            // Tasks
            ['GET',    '/tasks/create',                   'tasks.create'],
            ['POST',   '/tasks',                          'tasks.create'],
            ['GET',    '/task-templates/create',          'tasks.create'],
            ['POST',   '/task-templates',                 'tasks.create'],
            ['GET',    '/task-templates/999999/edit',     'tasks.edit_own'],
            ['PUT',    '/task-templates/999999',          'tasks.edit_own'],
            ['DELETE', '/task-templates/999999',          'tasks.delete'],
        ];

        // Key by "METHOD uri" so a failure names the route.
        return array_combine(array_map(fn ($r) => "$r[0] $r[1]", $rows), $rows);
    }

    /** Workspace-admin-only routes (no permission exists; must be user_type tenant_admin). */
    public static function adminOnlyRoutes(): array
    {
        $rows = [
            ['GET',    '/attendances/create'],
            ['POST',   '/attendances'],
            // {attendance} / {screenshot} are route-model-bound: the binding 404s a missing
            // id before authorization runs, so these use real records (see fixtureUri()).
            ['GET',    '/attendances/{attendance}/edit'],
            ['PUT',    '/attendances/{attendance}'],
            ['DELETE', '/attendances/{attendance}'],
            ['GET',    '/attendances/bulk-upload'],
            ['POST',   '/attendances/bulk-upload'],
            ['GET',    '/lead-integrations'],
            ['POST',   '/lead-integrations/facebook/save'],
            ['POST',   '/lead-integrations/facebook/regenerate'],
            ['POST',   '/whatsapp/chatbot'],
            ['DELETE', '/whatsapp/chatbot/999999'],
            ['POST',   '/whatsapp/api-settings'],
            ['DELETE', '/screenshots/{screenshot}'],
        ];

        return array_combine(array_map(fn ($r) => "$r[0] $r[1]", $rows), $rows);
    }

    private function asUser($user, string $method, string $uri)
    {
        return $this->actingAs($user)->call($method, $uri);
    }

    /** Swap {attendance}/{screenshot} for ids of real rows in $tenant. */
    private function fixtureUri(Tenant $tenant, string $uri): string
    {
        if (! str_contains($uri, '{')) {
            return $uri;
        }

        $owner = $this->makeUser($tenant, 'staff');
        $staff = Staff::create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
        $attendance = Attendance::create(['tenant_id' => $tenant->id, 'staff_id' => $staff->id, 'date' => today()->toDateString(), 'status' => 'present']);
        $screenshot = AttendanceScreenshot::create([
            'tenant_id' => $tenant->id, 'attendance_id' => $attendance->id, 'staff_id' => $staff->id,
            'path' => 'screenshots/none.png', 'filename' => 'none.png', 'captured_at' => now(),
        ]);

        return str_replace(['{attendance}', '{screenshot}'], [$attendance->id, $screenshot->id], $uri);
    }

    #[DataProvider('guardedRoutes')]
    public function test_user_without_the_permission_gets_403(string $method, string $uri, string $permission): void
    {
        $tenant = $this->setUpTenant();
        $user   = $this->makeCustomRoleUser($tenant, self::UNRELATED);

        $this->assertFalse($user->can($permission), "fixture should not hold {$permission}");
        $this->asUser($user, $method, $uri)->assertForbidden();
    }

    #[DataProvider('guardedRoutes')]
    public function test_user_with_the_permission_gets_past_authorization(string $method, string $uri, string $permission): void
    {
        $tenant = $this->setUpTenant();
        $user   = $this->makeCustomRoleUser($tenant, [$permission]);

        $status = $this->asUser($user, $method, $uri)->getStatusCode();

        $this->assertNotSame(403, $status, "{$method} {$uri} should be reachable with {$permission}");
    }

    #[DataProvider('guardedRoutes')]
    public function test_tenant_admin_is_never_blocked(string $method, string $uri, string $permission): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $this->assertNotSame(403, $this->asUser($admin, $method, $uri)->getStatusCode());
    }

    #[DataProvider('adminOnlyRoutes')]
    public function test_admin_only_routes_reject_staff_even_with_broad_permissions(string $method, string $uri): void
    {
        $tenant = $this->setUpTenant();
        // Every permission the seeded "staff" role has, plus more — still not an admin.
        $staff = $this->makeUser($tenant, 'staff', ['leads.create', 'contacts.create', 'whatsapp.send', 'settings.custom_fields']);

        $this->asUser($staff, $method, $this->fixtureUri($tenant, $uri))->assertForbidden();
    }

    #[DataProvider('adminOnlyRoutes')]
    public function test_admin_only_routes_admit_the_tenant_admin(string $method, string $uri): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'tenant_admin');

        $this->assertNotSame(403, $this->asUser($admin, $method, $this->fixtureUri($tenant, $uri))->getStatusCode());
    }

    // ── The audit's exact scenario ──────────────────────────────────

    public function test_custom_role_without_leads_create_cannot_open_or_submit_the_lead_form(): void
    {
        $tenant = $this->setUpTenant();
        $user   = $this->makeCustomRoleUser($tenant, ['leads.view_own', 'contacts.view_own', 'notifications.view', 'reports.view_basic']);

        $this->actingAs($user)->get(route('tenant.leads.create'))->assertForbidden();
        $this->actingAs($user)->post(route('tenant.leads.store'), ['name' => 'Sneaky', 'phone' => '9000000001'])->assertForbidden();

        $this->assertSame(0, Lead::count());
    }

    public function test_the_same_role_can_open_the_lead_form_once_granted_leads_create(): void
    {
        $tenant = $this->setUpTenant();
        $user   = $this->makeCustomRoleUser($tenant, ['leads.create']);

        $this->actingAs($user)->get(route('tenant.leads.create'))->assertOk();
    }

    public function test_roles_and_audit_logs_stay_forbidden_for_the_custom_role(): void
    {
        $tenant = $this->setUpTenant();
        $user   = $this->makeCustomRoleUser($tenant, self::UNRELATED);

        $this->actingAs($user)->get('/roles')->assertForbidden();
        $this->actingAs($user)->get('/audit-logs')->assertForbidden();
    }

    // ── Controller-level checks (no route middleware) ───────────────

    public function test_calendar_quick_create_requires_the_matching_permission(): void
    {
        $tenant = $this->setUpTenant();
        $denied  = $this->makeCustomRoleUser($tenant, self::UNRELATED, 'no_create_role');
        $allowed = $this->makeCustomRoleUser($tenant, ['tasks.create', 'followups.create'], 'creator_role');

        $task = ['kind' => 'task', 'title' => 'Ring the client', 'date' => now()->addDay()->toDateString()];

        $this->actingAs($denied)->postJson('/calendar/quick-create', $task)->assertForbidden();
        $this->actingAs($denied)->postJson('/calendar/quick-create', ['kind' => 'followup', 'type' => 'call', 'date' => now()->addDay()->toDateString()])->assertForbidden();
        $this->actingAs($allowed)->postJson('/calendar/quick-create', $task)->assertSuccessful();
    }

    public function test_screenshots_can_be_viewed_by_their_owner_and_admins_but_not_colleagues(): void
    {
        $tenant  = $this->setUpTenant();
        $owner   = $this->makeUser($tenant, 'staff');
        $other   = $this->makeUser($tenant, 'staff');
        $admin   = $this->makeUser($tenant, 'tenant_admin');
        $staff   = Staff::create(['tenant_id' => $tenant->id, 'user_id' => $owner->id]);
        $attendance = Attendance::create(['tenant_id' => $tenant->id, 'staff_id' => $staff->id, 'date' => today()->toDateString(), 'status' => 'present']);

        $url = route('tenant.screenshots.show', $attendance->id);

        $this->actingAs($owner)->get($url)->assertOk();
        $this->actingAs($admin)->get($url)->assertOk();
        $this->actingAs($other)->get($url)->assertForbidden();
    }

    public function test_only_admins_can_delete_a_screenshot(): void
    {
        $tenant = $this->setUpTenant();
        $staff  = $this->makeUser($tenant, 'staff');
        $uri    = $this->fixtureUri($tenant, '/screenshots/{screenshot}');

        $this->actingAs($staff)->delete($uri)->assertForbidden();
        $this->assertSame(1, AttendanceScreenshot::count());

        $admin = $this->makeUser($tenant, 'tenant_admin');
        $this->actingAs($admin)->delete($uri)->assertRedirect();
        $this->assertSame(0, AttendanceScreenshot::count());
    }

    public function test_a_permission_in_another_tenants_role_does_not_leak_across_tenants(): void
    {
        $tenantA = $this->setUpTenant();
        $tenantB = Tenant::factory()->create();
        $this->giveActiveSubscription($tenantB);
        $userB = $this->makeCustomRoleUser($tenantB, self::UNRELATED, 'tenant_b_role');
        $userA = $this->makeCustomRoleUser($tenantA, ['contacts.delete'], 'tenant_a_role');

        $this->actingAs($userA)->delete('/contacts/999999')->assertNotFound(); // allowed → row missing
        $this->actingAs($userB)->delete('/contacts/999999')->assertForbidden();
    }
}
