<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Three small tables. None of them touches your existing tenants table.
return new class extends Migration
{
    public function up(): void
    {
        // One row per tenant: is their WhatsApp connected, and which number.
        Schema::create('tenant_whatsapp_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id', 100)->unique();      // YOUR tenant id (also the gateway workspace id)
            $table->boolean('connected')->default(false);
            $table->string('display_phone_number')->nullable();
            $table->string('verified_name')->nullable();
            $table->string('quality_rating', 20)->nullable();
            $table->text('last_warning')->nullable();
            $table->timestamp('connected_at')->nullable();
            $table->timestamps();
        });

        // Every message in and out, kept in YOUR database (so your UI/bot never has to call the gateway to read history).
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id', 100)->index();
            $table->string('gateway_id', 64)->unique();      // message id on the gateway
            $table->string('wamid', 191)->nullable()->index();
            $table->string('direction', 10);                 // inbound | outbound
            $table->string('phone', 32)->index();
            $table->string('contact_name')->nullable();
            $table->string('type', 32);
            $table->text('text')->nullable();
            $table->json('content')->nullable();
            $table->string('status', 20);                    // received | sent | delivered | read | failed
            $table->string('error_code', 32)->nullable();
            $table->text('error_message')->nullable();
            $table->string('client_reference', 100)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'phone', 'id']);
        });

        // Webhook ids already handled — the gateway can deliver the same event twice (retries).
        Schema::create('whatsapp_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id', 64)->unique();
            $table->string('event', 60);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_webhook_events');
        Schema::dropIfExists('whatsapp_messages');
        Schema::dropIfExists('tenant_whatsapp_accounts');
    }
};
