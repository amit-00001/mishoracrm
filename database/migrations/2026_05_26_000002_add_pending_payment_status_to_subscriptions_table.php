<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        \App\Helpers\Sql::setEnum('subscriptions', 'status', ['trial', 'active', 'cancelled', 'expired', 'past_due', 'pending_payment'], 'trial');
    }

    public function down(): void
    {
        \App\Helpers\Sql::setEnum('subscriptions', 'status', ['trial', 'active', 'cancelled', 'expired', 'past_due'], 'trial');
    }
};
