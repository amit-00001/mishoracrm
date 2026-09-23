<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('trial','active','cancelled','expired','past_due','pending_payment') DEFAULT 'trial'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('trial','active','cancelled','expired','past_due') DEFAULT 'trial'");
    }
};
