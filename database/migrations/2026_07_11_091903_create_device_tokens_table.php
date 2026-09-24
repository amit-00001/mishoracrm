<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();


            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('device_token', 1000);
            $table->string('device_id', 100)->nullable();
            $table->string('device_name', 100)->nullable();

            $table->string('platform')->default('android');
            
            $table->string('app_version')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();
        });

        // A full unique index on varchar(1000) exceeds MySQL's 3072-byte key limit under utf8mb4.
        DB::statement('ALTER TABLE device_tokens ADD UNIQUE device_tokens_device_token_unique (device_token(700))');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
