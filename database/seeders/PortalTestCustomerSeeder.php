<?php

namespace Database\Seeders;

use App\Services\PortalTestData;
use App\Support\PortalTestLogin;
use Illuminate\Database\Seeder;

// Adds the wallet test customer to the first 5 tenants — but only while the
// superadmin's "Test login" switch (Superadmin → Portal Customers) is ON, so a
// plain `db:seed` / container start never plants fake data on its own.
class PortalTestCustomerSeeder extends Seeder
{
    public function run(): void
    {
        if (PortalTestLogin::active()) {
            PortalTestData::seed();
        }
    }
}
