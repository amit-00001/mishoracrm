<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // WhatsappInboundService logs every customer message with status
    // 'received', but the column only allowed sent / delivered / failed /
    // pending — that insert failed ("Data truncated" on MySQL, a CHECK
    // violation on PostgreSQL), was swallowed, and no incoming message ever
    // showed up in the WhatsApp log. Same for the direct-Meta and the gateway
    // webhook.
    //
    // MySQL stores a real ENUM, which ->change() can alter. PostgreSQL (what
    // production runs on) stores varchar + a CHECK constraint, and Laravel's
    // ->change() would emit "ALTER COLUMN … TYPE varchar(255) CHECK (…)",
    // which PostgreSQL rejects — so there the constraint is swapped by hand.
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            $this->replaceCheck(['sent', 'delivered', 'failed', 'pending', 'received']);
            return;
        }

        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->enum('status', ['sent', 'delivered', 'failed', 'pending', 'received'])->default('pending')->change();
        });
    }

    // The old enum can't hold 'received', so those rows go before it shrinks.
    public function down(): void
    {
        DB::table('whatsapp_logs')->where('status', 'received')->delete();

        if (DB::connection()->getDriverName() === 'pgsql') {
            $this->replaceCheck(['sent', 'delivered', 'failed', 'pending']);
            return;
        }

        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->enum('status', ['sent', 'delivered', 'failed', 'pending'])->default('pending')->change();
        });
    }

    // Drops whichever CHECK constraint(s) guard whatsapp_logs.status (found by
    // looking them up, so the generated name doesn't matter) and adds one
    // that allows exactly $allowed.
    private function replaceCheck(array $allowed): void
    {
        $existing = DB::select(
            "select conname from pg_constraint where conrelid = 'whatsapp_logs'::regclass and contype = 'c' and pg_get_constraintdef(oid) ilike '%status%'"
        );

        foreach ($existing as $constraint) {
            DB::statement('alter table "whatsapp_logs" drop constraint "' . str_replace('"', '""', $constraint->conname) . '"');
        }

        $list = implode(', ', array_map(fn ($status) => "'" . $status . "'", $allowed));

        DB::statement('alter table "whatsapp_logs" add constraint "whatsapp_logs_status_check" check ("status" in (' . $list . '))');
    }
};
