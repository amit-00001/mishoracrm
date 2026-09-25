<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Helpers\Sql;
use App\Services\LoyaltyNotifier;
use App\Services\LoyaltyService;
use Illuminate\Console\Command;

class SendLoyaltyOccasionOffers extends Command
{
    protected $signature = 'loyalty:occasion-offers';

    protected $description = 'Gift birthday / anniversary loyalty points (and greet the customer) for tenants running the Loyalty module';

    public function handle(LoyaltyService $loyalty): int
    {
        $today   = now()->format('m-d');
        $granted = 0;

        foreach (['birthday', 'anniversary'] as $occasion) {
            $contacts = Contact::withoutGlobalScopes()
                ->whereNotNull($occasion)
                ->whereRaw(Sql::monthDay($occasion) . ' = ?', [$today])
                ->with('tenant')
                ->get();

            foreach ($contacts as $contact) {
                if (!$contact->tenant?->hasModuleEnabled('loyalty')) {
                    continue;
                }

                $row = $loyalty->grantOccasionBonus($contact, $occasion);
                if ($row) {
                    LoyaltyNotifier::greet($contact->fresh(), $occasion, (int) $row->points);
                    $granted++;
                }
            }
        }

        $this->info("Granted {$granted} occasion bonus(es).");

        return self::SUCCESS;
    }
}
