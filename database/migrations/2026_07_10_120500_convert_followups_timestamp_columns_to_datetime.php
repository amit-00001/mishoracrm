<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE followups ALTER COLUMN scheduled_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        DB::statement('ALTER TABLE followups ALTER COLUMN done_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        DB::statement('ALTER TABLE followups ALTER COLUMN due_notified_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
        DB::statement('ALTER TABLE followups ALTER COLUMN overdue_notified_at TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE followups ALTER COLUMN scheduled_at TYPE TIMESTAMP');
        DB::statement('ALTER TABLE followups ALTER COLUMN done_at TYPE TIMESTAMP');
        DB::statement('ALTER TABLE followups ALTER COLUMN due_notified_at TYPE TIMESTAMP');
        DB::statement('ALTER TABLE followups ALTER COLUMN overdue_notified_at TYPE TIMESTAMP');
    }
};
