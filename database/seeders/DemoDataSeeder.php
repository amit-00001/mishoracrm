<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    /**
     * Fake sample data for local/dev testing — NOT idempotent, each run adds
     * more rows. Never run this in production (e.g. via the Docker CMD),
     * only manually on a local/dev database:
     *   php artisan db:seed --class=DemoDataSeeder
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => bcrypt('password')],
        );

        $this->call([
            LeadSeeder::class,
            DealSeeder::class,
            TaskSeeder::class,
        ]);
    }
}
