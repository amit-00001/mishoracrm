<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\Tenant;
use App\Services\LoyaltyService;
use Illuminate\Database\Seeder;

// Local-dev test customer for the wallet: phone 9999999999 (sign in with OTP
// 123456 — see config/portal.php), given a small loyalty card in the first 5
// tenants. Safe to re-run; does nothing outside local/testing, so the deploy-time
// `db:seed` never plants a fake customer in production.
class PortalTestCustomerSeeder extends Seeder
{
    private const PHONE  = '9999999999';
    private const POINTS = 80; // under the "history" threshold, so the card links straight away

    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            return;
        }

        Customer::withTrashed()->firstOrCreate(['phone' => self::PHONE], ['name' => 'Test Customer'])->restore();

        foreach (Tenant::orderBy('id')->limit(5)->get() as $tenant) {
            $exists = Contact::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('phone_normalized', self::PHONE)
                ->exists();

            if ($exists) {
                continue;
            }

            $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Test Customer', 'phone' => self::PHONE]);
            app(LoyaltyService::class)->manualAdjust($contact->fresh(), self::POINTS, 'Wallet test points');
        }
    }
}
