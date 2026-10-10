<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // WhatsApp Gateway hardening:
    //  - whatsapp_webhook_events remembers every gateway event id we handled,
    //    so a redelivery is ignored. It is written in the same DB transaction
    //    as the handling, so a handling that fails leaves no row behind and
    //    the gateway's retry is processed instead of being lost.
    //  - gateway_warning(_url) holds the last warning the gateway raised for a
    //    tenant's number (e.g. the 131042 billing issue) so the WhatsApp
    //    settings page can show it with Meta's fix link.
    public function up(): void
    {
        Schema::create('whatsapp_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique();
            $table->string('event', 60)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });

        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->text('gateway_warning')->nullable()->after('gateway_workspace_id');
            $table->string('gateway_warning_url', 500)->nullable()->after('gateway_warning');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->dropColumn(['gateway_warning', 'gateway_warning_url']);
        });

        Schema::dropIfExists('whatsapp_webhook_events');
    }
};
