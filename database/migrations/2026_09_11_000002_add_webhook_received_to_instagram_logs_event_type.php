<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        \App\Helpers\Sql::setEnum('instagram_logs', 'event_type', ['comment', 'dm_received', 'dm_sent', 'automation_triggered', 'chatbot_triggered', 'n8n_triggered', 'oauth_connect', 'oauth_deauthorize', 'data_deletion_request', 'webhook_received'], null, true);
    }

    public function down(): void
    {
        \App\Helpers\Sql::setEnum('instagram_logs', 'event_type', ['comment', 'dm_received', 'dm_sent', 'automation_triggered', 'chatbot_triggered', 'n8n_triggered', 'oauth_connect', 'oauth_deauthorize', 'data_deletion_request'], null, true);
    }
};
