<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // A task's related record (lead/contact/deal) is optional, but morphs()
    // creates taskable_type/taskable_id as NOT NULL — crashes on save when
    // no related record is picked. Widen them to nullable via raw SQL since
    // doctrine/dbal (needed for column->change()) isn't installed.
    // Raw SQL differs per driver (MySQL uses MODIFY, Postgres uses ALTER COLUMN).
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tasks ALTER COLUMN taskable_type DROP NOT NULL');
            DB::statement('ALTER TABLE tasks ALTER COLUMN taskable_id DROP NOT NULL');
        } else {
            DB::statement('ALTER TABLE tasks MODIFY taskable_type VARCHAR(255) NULL');
            DB::statement('ALTER TABLE tasks MODIFY taskable_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        DB::statement("UPDATE tasks SET taskable_type = '' WHERE taskable_type IS NULL");
        DB::statement('UPDATE tasks SET taskable_id = 0 WHERE taskable_id IS NULL');

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE tasks ALTER COLUMN taskable_type SET NOT NULL');
            DB::statement('ALTER TABLE tasks ALTER COLUMN taskable_id SET NOT NULL');
        } else {
            DB::statement('ALTER TABLE tasks MODIFY taskable_type VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE tasks MODIFY taskable_id BIGINT UNSIGNED NOT NULL');
        }
    }
};
