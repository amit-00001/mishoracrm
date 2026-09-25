<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\CustomerOtp;
use App\Models\Tenant;
use App\Support\PortalTestLogin;

// The wallet's test customer (config/portal.php phone), added to / removed from
// the first 5 tenants. Driven by the superadmin's "Test login" switch
// (Superadmin → Portal Customers) — and by PortalTestCustomerSeeder, which does
// nothing while that switch is off.
class PortalTestData
{
    public const CONTACT_NAME = 'Test Customer';
    private const POINTS      = 80; // under the "history" threshold, so the card links straight away

    // Idempotent. Returns how many tenants now carry the test card.
    public static function seed(): int
    {
        $phone    = PortalTestLogin::phone();
        $customer = Customer::withTrashed()->firstOrCreate(['phone' => $phone], ['name' => self::CONTACT_NAME]);
        if ($customer->trashed()) {
            $customer->restore();
        }

        $count = 0;

        foreach (Tenant::orderBy('id')->limit(5)->get() as $tenant) {
            self::enableModules($tenant);

            $exists = Contact::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('phone_normalized', $phone)
                ->exists();

            if (!$exists) {
                $contact = Contact::create(['tenant_id' => $tenant->id, 'name' => self::CONTACT_NAME, 'phone' => $phone]);
                app(LoyaltyService::class)->manualAdjust($contact->fresh(), self::POINTS, 'Wallet test points');
            }

            $count++;
        }

        // What a real OTP login does: attach every not-yet-linked contact on this number.
        app(CustomerLinkService::class)->linkByPhone($customer);

        return $count;
    }

    // Removes only what seed() made: the test customer and contacts that still
    // carry the test name on the test number. A real contact that happens to share
    // the number keeps its record. Module overrides are left as they are.
    public static function purge(): int
    {
        $phone = PortalTestLogin::phone();

        $contacts = Contact::withoutGlobalScopes()
            ->where('phone_normalized', $phone)
            ->where('name', self::CONTACT_NAME)
            ->get();

        foreach ($contacts as $contact) {
            $contact->forceDelete();
        }

        CustomerOtp::where('phone', $phone)->delete();

        // Any contact left on this number (a real one) just loses its link.
        Contact::withoutGlobalScopes()
            ->where('phone_normalized', $phone)
            ->update(['customer_id' => null, 'phone_verified' => false]);

        Customer::withTrashed()->where('phone', $phone)->get()->each->forceDelete();

        return $contacts->count();
    }

    // Force-ON overrides — only where a module is currently off, so a shop that
    // already has them (via plan or override) is left exactly as it was.
    private static function enableModules(Tenant $tenant): void
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
