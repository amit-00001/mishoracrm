<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    // WhatsApp Team Inbox, step 3: the Team performance report is for owners,
    // so the permission goes to tenant_admin only (staff see their own chats,
    // not each other's numbers). Campaign pages reuse whatsapp.view_logs.
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $perm = Permission::firstOrCreate(['name' => 'whatsapp.view_reports', 'guard_name' => 'web']);
        Role::where('name', 'tenant_admin')->where('guard_name', 'web')->get()
            ->each(fn ($role) => $role->givePermissionTo($perm));
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where('name', 'whatsapp.view_reports')->delete();
    }
};
