<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE subscriptions DROP CONSTRAINT IF EXISTS subscriptions_status_check');
            DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_status_check CHECK (status IN ('trial','active','cancelled','expired','past_due','pending_payment'))");
            DB::statement("ALTER TABLE subscriptions ALTER COLUMN status SET DEFAULT 'trial'");
        } else {
            DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('trial','active','cancelled','expired','past_due','pending_payment') DEFAULT 'trial'");
        }
    }

   public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE subscriptions DROP CONSTRAINT IF EXISTS subscriptions_status_check');
            DB::statement("ALTER TABLE subscriptions ADD CONSTRAINT subscriptions_status_check CHECK (status IN ('trial','active','cancelled','expired','past_due'))");
            DB::statement("ALTER TABLE subscriptions ALTER COLUMN status SET DEFAULT 'trial'");
        } else {
            DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('trial','active','cancelled','expired','past_due') DEFAULT 'trial'");
        }
    }
};
