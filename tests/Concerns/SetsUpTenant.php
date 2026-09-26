<?php

namespace Tests\Concerns;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

/**
 * Shared "tenant + seeded permissions + role-holding user" boilerplate for
 * Lead module feature tests. Reuses the real RolesAndPermissionsSeeder so
 * tests stay in sync with the actual seeded permission set.
 */
trait SetsUpTenant
{
    protected function setUpTenant(): Tenant
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $tenant = Tenant::factory()->create();

        // Web routes are subscription-gated, so tenants start on a live plan.
        $this->giveActiveSubscription($tenant);

        return $tenant;
    }

    // A live paid subscription so subscription-gated entry points (API key
    // auth) let the tenant in.
    protected function giveActiveSubscription(Tenant $tenant): void
    {
        $plan = \App\Models\Plan::create([
            'name' => 'Paid', 'slug' => 'paid-' . uniqid(), 'monthly_price' => 999, 'yearly_price' => 9999,
            'features' => ['users' => 5], 'is_active' => true, 'is_custom' => false,
            'trial_days' => 0, 'sort_order' => 1,
        ]);

        $tenant->subscriptions()->create([
            'plan_id' => $plan->id, 'status' => 'active', 'billing_cycle' => 'monthly',
            'started_at' => now(), 'ends_at' => now()->addMonth(),
        ]);
    }

    protected function makeUser(Tenant $tenant, string $role, array $permissions = []): User
    {
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'user_type' => in_array($role, ['superadmin', 'tenant_admin']) ? $role : 'staff',
            'is_active' => true,
        ]);

        $user->assignRole($role);

        if (!empty($permissions)) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    /**
     * A staff user on a custom role holding exactly $permissions (no extras from
     * the seeded "staff" role) — for testing that server-side authorization
     * denies actions the role does not grant.
     */
    protected function makeCustomRoleUser(Tenant $tenant, array $permissions, string $roleName = 'qa_auditor'): User
    {
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $role->syncPermissions($permissions);

        return $this->makeUser($tenant, $roleName);
    }
}
