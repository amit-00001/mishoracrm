<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // 1. Opt-outs: numbers that must not receive bulk / broadcast messages
    //    (customer wrote STOP, or staff marked them by hand).
    // 2. whatsapp_settings switches: opt-out keywords on, auto-create a Lead
    //    from a first inbound WhatsApp message on.
    // 3. Scheduled bulk sends: a bulk send saved now and sent later by the
    //    `whatsapp:send-scheduled` command (runs every minute).
    public function up(): void
    {
        Schema::create('whatsapp_opt_outs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('phone', 32);                  // digits only, with country code
            $table->string('source', 20)->default('keyword'); // keyword | manual
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->unique(['tenant_id', 'phone']);
        });

        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->boolean('optout_enabled')->default(true);
            $table->boolean('auto_create_lead')->default(true);
        });

        Schema::create('whatsapp_scheduled_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('type', 10);                    // leads | contacts
            $table->json('recipients');                    // ids, snapshot at scheduling time
            $table->text('message');
            $table->unsignedBigInteger('template_id')->nullable();
            $table->string('meta_template', 200)->nullable();   // "name|language"
            $table->json('template_params')->nullable();        // raw, {{variables}} still in them
            $table->string('attachment_path')->nullable();      // private disk, deleted after sending
            $table->string('attachment_name')->nullable();
            $table->string('attachment_mime')->nullable();
            $table->timestamp('scheduled_for');
            $table->string('status', 15)->default('pending');   // pending | processing | sent | failed | cancelled
            $table->string('bulk_id', 64);                      // links to Campaigns once sent
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->index(['status', 'scheduled_for']);
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_scheduled_broadcasts');
        Schema::table('whatsapp_settings', function (Blueprint $table) {
            $table->dropColumn(['optout_enabled', 'auto_create_lead']);
        });
        Schema::dropIfExists('whatsapp_opt_outs');
    }
};
