<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Only idempotent, production-critical seeders belong here (they use
     * firstOrCreate/updateOrCreate, so re-running on every deploy is safe
     * and never creates duplicates). Fake/demo data (sample leads, deals,
     * tasks) lives in DemoDataSeeder — run that manually, only in local/dev.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            PlanSeeder::class,
            SuperAdminSeeder::class,
            MessageTemplateSeeder::class,
            NotificationSeeder::class,
            CustomFieldSeeder::class,
            GlobalFieldTemplateSeeder::class,
            TaskTemplateSeeder::class,
            PortalTestCustomerSeeder::class, // local/testing only — no-op in production
        ]);
    }
}
