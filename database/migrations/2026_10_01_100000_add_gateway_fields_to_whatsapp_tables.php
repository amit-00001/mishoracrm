<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // WhatsApp Gateway mode (Milan CRM relays Meta for us until our own Meta
    // app is live). connection_mode says which transport a tenant's number
    // uses — every existing row stays 'direct', so nothing changes until the
    // superadmin switches the gateway on and a tenant connects through it.
    // wamid is the WhatsApp message id returned on send, used to match the
    // gateway's later delivered / read / failed receipts back to a log row.
    public function up(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->string('connection_mode', 20)->default('direct')->after('tenant_id');
            $table->string('gateway_workspace_id')->nullable()->unique()->after('connection_mode');
        });

        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->string('wamid')->nullable()->index()->after('media_id');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->dropIndex(['wamid']);
            $table->dropColumn('wamid');
        });

        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->dropUnique(['gateway_workspace_id']);
            $table->dropColumn(['connection_mode', 'gateway_workspace_id']);
        });
    }
};
