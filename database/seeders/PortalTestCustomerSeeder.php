<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\Tenant;
use App\Services\CustomerLinkService;
use App\Services\LoyaltyService;
use Illuminate\Database\Seeder;

// Local-dev test customer for the wallet: phone 9999999999 (sign in with OTP
// 123456 — see config/portal.php). For the first 5 tenants it adds a small
// loyalty card, switches Loyalty + Customer Portal ON (so the card actually shows
// up in the wallet and the tenant-side portal screens appear), and links the
// contact to the customer. Safe to re-run; does nothing outside local/testing,
// so the deploy-time `db:seed` never plants a fake customer in production.
class PortalTestCustomerSeeder extends Seeder
{
    private const PHONE  = '9999999999';
    private const POINTS = 80; // under the "history" threshold, so the card links straight away

    public function run(): void
    {
        if (!app()->environment(['local', 'testing'])) {
            return;
        }

        $customer = Customer::withTrashed()->firstOrCreate(['phone' => self::PHONE], ['name' => 'Test Customer']);
        if ($customer->trashed()) {
            $customer->restore();
        }

        foreach (Tenant::orderBy('id')->limit(5)->get() as $tenant) {
            $this->enableModules($tenant);

            $exists = Contact::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('phone_normalized', self::PHONE)
                ->exists();

            if (!$exists) {
                $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => 'Test Customer', 'phone' => self::PHONE]);
                app(LoyaltyService::class)->manualAdjust($contact->fresh(), self::POINTS, 'Wallet test points');
            }
        }

        // What a real OTP login does: attach every not-yet-linked contact on this number.
        app(CustomerLinkService::class)->linkByPhone($customer);
    }

    // Force-ON overrides — only where a module is currently off, so a shop that
    // already has them (via plan or override) is left exactly as it was.
    private function enableModules(Tenant $tenant): void
    {
        $settings = $tenant->settings ?? [];

        foreach (['loyalty', 'customer_portal'] as $module) {
            if (!$tenant->hasModuleEnabled($module)) {
                $settings['modules'][$module] = true;
            }
        }

        if ($settings !== ($tenant->settings ?? [])) {
            $tenant->update(['settings' => $settings]);
        }
    }
}
