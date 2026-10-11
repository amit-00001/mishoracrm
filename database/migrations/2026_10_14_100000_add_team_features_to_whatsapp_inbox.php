<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    // WhatsApp Team Inbox, step 2: assignment (+ auto-assign), quick replies,
    // private internal notes and the permissions around them. The
    // whatsapp_conversations.assigned_to column already exists from step 1.
    public function up(): void
    {
        Schema::create('whatsapp_quick_replies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('shortcut', 40);            // typed after "/" in the reply box
            $table->text('body');                      // may contain {{name}}
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['tenant_id', 'shortcut']);
        });

        Schema::create('whatsapp_conversation_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('body');
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('conversation_id')->references('id')->on('whatsapp_conversations')->onDelete('cascade');
            $table->index(['conversation_id', 'id']);
        });

        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->boolean('inbox_auto_assign')->default(false);   // off until the owner turns it on
            $table->json('inbox_auto_assign_users')->nullable();    // user ids that share new chats
            $table->unsignedBigInteger('inbox_last_assigned_to')->nullable(); // round-robin cursor
        });

        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->index(['tenant_id', 'assigned_to']);
        });

        // Defaults: everyone sees every chat (whatsapp.view_all), admins and
        // staff can assign; only admins manage quick replies. Owners tighten
        // this per role in Settings → Roles.
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
        $grants = [
            'whatsapp.assign'        => ['tenant_admin', 'staff'],
            'whatsapp.view_all'      => ['tenant_admin', 'staff'],
            'whatsapp.quick_replies' => ['tenant_admin'],
        ];
        foreach ($grants as $name => $roles) {
            $perm = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            foreach ($roles as $roleName) {
                Role::where('name', $roleName)->where('guard_name', 'web')->get()
                    ->each(fn ($role) => $role->givePermissionTo($perm));
            }
        }
        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'assigned_to']);
        });
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->dropColumn(['inbox_auto_assign', 'inbox_auto_assign_users', 'inbox_last_assigned_to']);
        });
        Schema::dropIfExists('whatsapp_conversation_notes');
        Schema::dropIfExists('whatsapp_quick_replies');
        Permission::whereIn('name', ['whatsapp.assign', 'whatsapp.view_all', 'whatsapp.quick_replies'])->delete();
    }
};
