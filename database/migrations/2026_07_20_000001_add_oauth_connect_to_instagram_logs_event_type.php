<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE instagram_logs MODIFY event_type ENUM('comment','dm_received','dm_sent','automation_triggered','chatbot_triggered','n8n_triggered','oauth_connect') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE instagram_logs MODIFY event_type ENUM('comment','dm_received','dm_sent','automation_triggered','chatbot_triggered','n8n_triggered') NOT NULL");
    }
};
