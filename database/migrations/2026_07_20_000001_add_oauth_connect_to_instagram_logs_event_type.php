<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE instagram_logs DROP CONSTRAINT IF EXISTS instagram_logs_event_type_check');
            DB::statement("ALTER TABLE instagram_logs ADD CONSTRAINT instagram_logs_event_type_check CHECK (event_type IN ('comment','dm_received','dm_sent','automation_triggered','chatbot_triggered','n8n_triggered','oauth_connect'))");
        } else {
            DB::statement("ALTER TABLE instagram_logs MODIFY event_type ENUM('comment','dm_received','dm_sent','automation_triggered','chatbot_triggered','n8n_triggered','oauth_connect') NOT NULL");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE instagram_logs DROP CONSTRAINT IF EXISTS instagram_logs_event_type_check');
            DB::statement("ALTER TABLE instagram_logs ADD CONSTRAINT instagram_logs_event_type_check CHECK (event_type IN ('comment','dm_received','dm_sent','automation_triggered','chatbot_triggered','n8n_triggered'))");
        } else {
            DB::statement("ALTER TABLE instagram_logs MODIFY event_type ENUM('comment','dm_received','dm_sent','automation_triggered','chatbot_triggered','n8n_triggered') NOT NULL");
        }
    }
};
