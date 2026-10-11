<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    // One row per customer phone number that has messaged the tenant — the
    // WhatsApp Team Inbox thread. The messages themselves stay in
    // whatsapp_logs (status 'received' = customer, anything else = us), so
    // nothing already logged has to be copied. assigned_to is filled by the
    // assignment step of the inbox rollout; it is created now so that step
    // needs no schema change.
    public function up(): void
    {
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('wa_id');
            $table->string('contact_name')->nullable();
            $table->unsignedBigInteger('lead_id')->nullable();
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->string('status', 20)->default('open'); // open | resolved
            $table->unsignedInteger('unread_count')->default(0);
            $table->string('last_message_preview')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('last_inbound_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['tenant_id', 'wa_id']);
            $table->index(['tenant_id', 'status', 'last_message_at']);
        });

        // Backfill from messages already received, so the inbox is not empty
        // on day one.
        $rows = \DB::table('whatsapp_logs')
            ->where('status', 'received')
            ->selectRaw('tenant_id, to_phone, MAX(to_name) as name, MAX(sent_at) as last_at')
            ->groupBy('tenant_id', 'to_phone')
            ->get();

        foreach ($rows as $row) {
            $waId = preg_replace('/[^0-9]/', '', (string) $row->to_phone);
            if ($waId === '') {
                continue;
            }
            \DB::table('whatsapp_conversations')->updateOrInsert(
                ['tenant_id' => $row->tenant_id, 'wa_id' => $waId],
                [
                    'contact_name'    => $row->name,
                    'last_message_at' => $row->last_at,
                    'last_inbound_at' => $row->last_at,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]
            );
        }

        // Permission: tenant admins and staff can use the inbox by default;
        // owners can take it away from a role in Settings → Roles.
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $perm = Permission::firstOrCreate(['name' => 'whatsapp.inbox', 'guard_name' => 'web']);
        foreach (['tenant_admin', 'staff'] as $roleName) {
            Role::where('name', $roleName)->where('guard_name', 'web')->get()
                ->each(fn ($role) => $role->givePermissionTo($perm));
        }
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversations');
        Permission::where('name', 'whatsapp.inbox')->delete();
    }
};
