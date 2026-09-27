<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SetsUpTenant;
use Tests\TestCase;

// Superadmin error log (P4 triage): "Call to a member function format() on null" on the
// superadmin dashboard / tenants list, hit by tenants that have no created_at
// (seeders / raw inserts) and no subscription. Those pages must render for them.
class TenantPagesNullSafetyTest extends TestCase
{
    use RefreshDatabase, SetsUpTenant;

    private function tenantWithoutTimestampsOrSubscription(): Tenant
    {
        $bare = Tenant::factory()->create(['name' => 'Bare Tenant']);
        DB::table('tenants')->where('id', $bare->id)->update(['created_at' => null, 'updated_at' => null]);

        return $bare->fresh();
    }

    public function test_superadmin_dashboard_renders_with_a_tenant_that_has_no_created_at(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'superadmin');
        $this->tenantWithoutTimestampsOrSubscription();

        $this->actingAs($admin)->get(route('superadmin.dashboard'))->assertOk();
    }

    public function test_tenants_list_and_detail_render_with_a_tenant_that_has_no_created_at(): void
    {
        $tenant = $this->setUpTenant();
        $admin  = $this->makeUser($tenant, 'superadmin');
        $bare   = $this->tenantWithoutTimestampsOrSubscription();

        $this->actingAs($admin)->get(route('superadmin.tenants.index'))->assertOk()->assertSee('Bare Tenant');
        $this->actingAs($admin)->get(route('superadmin.tenants.show', $bare->id))->assertOk();
    }
}
