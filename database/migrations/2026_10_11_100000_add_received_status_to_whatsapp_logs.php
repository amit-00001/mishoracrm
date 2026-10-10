<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // WhatsappInboundService logs every customer message with status
    // 'received', but the column only allowed sent / delivered / failed /
    // pending — on MySQL (strict) that insert failed with "Data truncated for
    // column 'status'", was swallowed, and no incoming message ever showed up
    // in the WhatsApp log. Same for the direct-Meta and the gateway webhook.
    public function up(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->enum('status', ['sent', 'delivered', 'failed', 'pending', 'received'])->default('pending')->change();
        });
    }

    // The old enum can't hold 'received', so those rows go before it shrinks.
    public function down(): void
    {
        DB::table('whatsapp_logs')->where('status', 'received')->delete();

        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->enum('status', ['sent', 'delivered', 'failed', 'pending'])->default('pending')->change();
        });
    }
};
