<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// MySQL implicitly attaches "DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP"
// to the first non-nullable TIMESTAMP column in a table when
// explicit_defaults_for_timestamp is OFF (this server's setting). Since
// `scheduled_at` was that column, MySQL was silently overwriting every
// user-picked follow-up date/time with "now" on *any* row update (marking
// done, editing notes, adding an attachment, etc.) — which is why follow-up
// reminders never fired at the right time. DATETIME columns are never
// subject to this behaviour, so we switch the affected columns to DATETIME.
// Postgres has no such implicit-timestamp behaviour, so this is a no-op there.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE followups MODIFY scheduled_at DATETIME NOT NULL');
        DB::statement('ALTER TABLE followups MODIFY done_at DATETIME NULL DEFAULT NULL');
        DB::statement('ALTER TABLE followups MODIFY due_notified_at DATETIME NULL DEFAULT NULL');
        DB::statement('ALTER TABLE followups MODIFY overdue_notified_at DATETIME NULL DEFAULT NULL');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE followups MODIFY scheduled_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        DB::statement('ALTER TABLE followups MODIFY done_at TIMESTAMP NULL DEFAULT NULL');
        DB::statement('ALTER TABLE followups MODIFY due_notified_at TIMESTAMP NULL DEFAULT NULL');
        DB::statement('ALTER TABLE followups MODIFY overdue_notified_at TIMESTAMP NULL DEFAULT NULL');
    }
};
