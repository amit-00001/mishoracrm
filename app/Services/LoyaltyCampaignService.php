<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\LoyaltyCampaign;
use App\Models\LoyaltyCampaignRecipient;
use App\Models\Tenant;
use App\Models\WhatsappSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Targeted offers (docs/customer-loyalty.txt §5). Resolves a segment to a set
// of Contacts, snapshots them at launch with per-customer codes, delivers the
// offer, and validates + applies a code against an invoice.
class LoyaltyCampaignService
{
    // ── Segmentation ────────────────────────────────────────────

    public function matchQuery(Tenant $tenant, string $type, array $config): Builder
    {
        $q = Contact::query()->where('contacts.tenant_id', $tenant->id);

        $paidInvoiceSum = fn (?int $days) => Invoice::withoutGlobalScopes()
            ->selectRaw('COALESCE(SUM(total),0)')
            ->whereColumn('invoices.contact_id', 'contacts.id')
            ->whereNull('invoices.deleted_at')
            ->where('invoices.status', 'paid')
            ->when($days, fn ($x) => $x->where('invoices.paid_at', '>=', now()->subDays($days)));

        $paidInvoiceCount = fn (?int $days) => Invoice::withoutGlobalScopes()
            ->selectRaw('COUNT(*)')
            ->whereColumn('invoices.contact_id', 'contacts.id')
            ->whereNull('invoices.deleted_at')
            ->where('invoices.status', 'paid')
            ->when($days, fn ($x) => $x->where('invoices.paid_at', '>=', now()->subDays($days)));

        return match ($type) {
            'spend' => $q
                ->select('contacts.*')
                ->addSelect(['_metric' => $paidInvoiceSum($config['within_days'] ?? null)])
                ->where($paidInvoiceSum($config['within_days'] ?? null), '>=', (float) ($config['min_spend'] ?? 0)),

            'visits' => $q
                ->select('contacts.*')
                ->addSelect(['_metric' => $paidInvoiceCount($config['within_days'] ?? null)])
                ->where($paidInvoiceCount($config['within_days'] ?? null), '>=', (int) ($config['min_visits'] ?? 1)),

            'inactive' => $q
                ->whereHas('invoices', fn ($iq) => $iq->where('status', 'paid'))
                ->whereDoesntHave('invoices', fn ($iq) => $iq->where('status', 'paid')
                    ->where('paid_at', '>=', now()->subDays((int) ($config['inactive_days'] ?? 60)))),

            'tier' => $q->where('contacts.loyalty_tier', $config['tier'] ?? 'gold'),

            'category' => $q->whereIn('contacts.id', $this->contactsWhoSpentOnCategory($tenant, $config)),

            'manual' => $q->whereIn('contacts.id', array_map('intval', $config['contact_ids'] ?? [])),

            default => $q->whereRaw('1 = 0'),
        };
    }

    public function previewCount(LoyaltyCampaign $campaign): int
    {
        return $this->matchQuery($campaign->tenant, $campaign->segment_type, $campaign->segment_config ?? [])->count();
    }

    // ── Launch — snapshot recipients, issue codes, deliver ──────

    public function launch(LoyaltyCampaign $campaign): int
    {
        if ($campaign->status !== 'draft') {
            return 0;
        }

        $contacts = $this->matchQuery($campaign->tenant, $campaign->segment_type, $campaign->segment_config ?? [])->get();

        $made = 0;
        foreach ($contacts as $contact) {
            $recipient = DB::transaction(function () use ($campaign, $contact) {
                $code = $campaign->code_mode === 'shared'
                    ? $campaign->shared_code
                    : $this->uniqueCode($campaign->tenant_id);

                $recipient = LoyaltyCampaignRecipient::firstOrCreate(
                    ['loyalty_campaign_id' => $campaign->id, 'contact_id' => $contact->id],
                    ['tenant_id' => $campaign->tenant_id, 'code' => $code],
                );

                // reward_type "points" pays out immediately, no code needed.
                if ($recipient->wasRecentlyCreated && $campaign->reward_type === 'points') {
                    app(LoyaltyService::class)->manualAdjust(
                        $contact,
                        (int) $campaign->reward_value,
                        "Campaign: {$campaign->name}",
                        $campaign->created_by,
                    );
                    $recipient->forceFill([
                        'redeemed_count' => 1,
                        'redeemed_at'    => now(),
                        'redeemed_value' => $campaign->reward_value,
                    ])->save();
                    $campaign->increment('redeemed_count');
                }

                return $recipient;
            });

            if ($recipient->wasRecentlyCreated) {
                $made++;
                $this->deliver($campaign, $contact, $recipient);
            }
        }

        $campaign->update(['status' => 'active', 'launched_at' => now()]);

        return $made;
    }

    public function end(LoyaltyCampaign $campaign): void
    {
        $campaign->update(['status' => 'ended']);
    }

    // Offers a customer could still use right now — same active / cap / per-
    // customer-limit rules applyCoupon() enforces. Explicit tenant + contact
    // scoping so it's safe from the customer portal (no tenant user in context).
    public function activeOffersFor(Contact $contact): \Illuminate\Support\Collection
    {
        return LoyaltyCampaignRecipient::withoutGlobalScopes()
            ->where('tenant_id', $contact->tenant_id)
            ->where('contact_id', $contact->id)
            ->with(['campaign' => fn ($q) => $q->withoutGlobalScopes()])
            ->latest('id')
            ->get()
            ->filter(fn (LoyaltyCampaignRecipient $r) => $r->campaign
                && $r->campaign->isActive()
                && !$r->campaign->isCapReached()
                && $r->redeemed_count < max(1, (int) $r->campaign->usage_limit_per_customer))
            ->values();
    }

    // ── Apply a code to an invoice ─────────────────────────────

    // ['ok'=>bool, 'message'=>string, 'value'=>float, 'note'=>?string]
    public function applyCoupon(Invoice $invoice, string $rawCode, ?int $userId = null): array
    {
        $fail = fn (string $m) => ['ok' => false, 'message' => $m, 'value' => 0.0, 'note' => null];

        $tenant = $invoice->tenant;
        if (!$tenant || !$tenant->hasModuleEnabled('loyalty') || !$invoice->contact_id) {
            return $fail('Campaign coupons are not available for this invoice.');
        }
        if ($invoice->status === 'paid') {
            return $fail('This invoice is already settled.');
        }
        if ($invoice->hasCampaignCoupon()) {
            return $fail('A campaign coupon is already applied. Remove it first.');
        }

        $code = strtoupper(trim($rawCode));

        $recipient = LoyaltyCampaignRecipient::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('code', $code)
            ->where('contact_id', $invoice->contact_id)
            ->with('campaign')
            ->latest('id')
            ->first();

        if (!$recipient || !$recipient->campaign) {
            return $fail('That code is not valid for this customer.');
        }

        $campaign = $recipient->campaign;

        if (!$campaign->isActive()) {
            return $fail('This offer has ended or expired.');
        }
        if ($campaign->isCapReached()) {
            return $fail('This offer has reached its redemption limit.');
        }
        if ($recipient->redeemed_count >= $campaign->usage_limit_per_customer) {
            return $fail('This code has already been used.');
        }

        $cashDue = max(0.0, (float) $invoice->total - (float) $invoice->paid_amount - (float) $invoice->loyalty_discount);

        [$value, $note] = $this->rewardValue($campaign, (float) $invoice->total, $cashDue);

        if ($campaign->reward_type === 'free_item') {
            $note = 'Campaign: free ' . ($campaign->reward_item ?: 'item') . ' — please add it to the order.';
        } elseif ($value <= 0) {
            return $fail('Nothing left to discount on this bill.');
        }

        DB::transaction(function () use ($invoice, $recipient, $campaign, $value, $userId) {
            $invoice->forceFill([
                'loyalty_campaign_recipient_id' => $recipient->id,
                'campaign_discount'             => $value,
            ])->save();

            $recipient->forceFill([
                'redeemed_count' => $recipient->redeemed_count + 1,
                'redeemed_at'    => now(),
                'redeemed_value' => $value,
            ])->save();

            $campaign->increment('redeemed_count');

            unset($userId); // reserved for a future audit hook
        });

        return ['ok' => true, 'message' => "Coupon applied — {$campaign->rewardLabel()}.", 'value' => $value, 'note' => $note];
    }

    public function reverseCoupon(Invoice $invoice): void
    {
        if (!$invoice->loyalty_campaign_recipient_id) {
            return;
        }

        DB::transaction(function () use ($invoice) {
            $recipient = LoyaltyCampaignRecipient::withoutGlobalScopes()
                ->with('campaign')
                ->find($invoice->loyalty_campaign_recipient_id);

            if ($recipient) {
                $recipient->forceFill([
                    'redeemed_count' => max(0, $recipient->redeemed_count - 1),
                    'redeemed_at'    => null,
                    'redeemed_value' => null,
                ])->save();

                if ($recipient->campaign && $recipient->campaign->redeemed_count > 0) {
                    $recipient->campaign->decrement('redeemed_count');
                }
            }

            $invoice->forceFill([
                'loyalty_campaign_recipient_id' => null,
                'campaign_discount'             => 0,
            ])->save();
        });
    }

    // ── Internals ───────────────────────────────────────────────

    private function rewardValue(LoyaltyCampaign $campaign, float $billTotal, float $cashDue): array
    {
        return match ($campaign->reward_type) {
            'percent' => [
                round(min(
                    (float) $campaign->reward_value / 100 * $billTotal,
                    $campaign->max_discount !== null ? (float) $campaign->max_discount : PHP_FLOAT_MAX,
                    $cashDue,
                ), 2),
                null,
            ],
            'flat' => [round(min((float) $campaign->reward_value, $cashDue), 2), null],
            default => [0.0, null],
        };
    }

    // Contacts whose paid-invoice line items in $config['category'] total at
    // least $config['min_spend'] (0 = "bought from the category at all").
    // Invoice items are JSON, so this is resolved in PHP.
    private function contactsWhoSpentOnCategory(Tenant $tenant, array $config): array
    {
        $category = trim((string) ($config['category'] ?? ''));
        if ($category === '') {
            return [-1];
        }

        $productIds = \App\Models\Product::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)->where('category', $category)->pluck('id')->all();
        $serviceIds = \App\Models\Service::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)->where('category', $category)->pluck('id')->all();

        if (!$productIds && !$serviceIds) {
            return [-1];
        }

        $days    = $config['within_days'] ?? null;
        $minSpend = (float) ($config['min_spend'] ?? 0);

        $spend = [];
        Invoice::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'paid')
            ->when($days, fn ($x) => $x->where('paid_at', '>=', now()->subDays($days)))
            ->whereNotNull('contact_id')
            ->select('contact_id', 'items')
            ->chunk(500, function ($invoices) use (&$spend, $productIds, $serviceIds) {
                foreach ($invoices as $inv) {
                    foreach ($inv->items ?? [] as $it) {
                        $pid = (int) ($it['product_id'] ?? 0);
                        $sid = (int) ($it['service_id'] ?? 0);
                        if (in_array($pid, $productIds, true) || in_array($sid, $serviceIds, true)) {
                            $spend[$inv->contact_id] = ($spend[$inv->contact_id] ?? 0)
                                + (float) ($it['quantity'] ?? 0) * (float) ($it['rate'] ?? 0);
                        }
                    }
                }
            });

        $ids = array_keys(array_filter($spend, fn ($v) => $v >= $minSpend && $v > 0));

        return $ids ?: [-1];
    }

    private function uniqueCode(int $tenantId): string
    {
        do {
            $code = strtoupper(Str::random(3) . random_int(100, 999));
            $exists = LoyaltyCampaignRecipient::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)->where('code', $code)->exists();
        } while ($exists);

        return $code;
    }

    private function deliver(LoyaltyCampaign $campaign, Contact $contact, LoyaltyCampaignRecipient $recipient): void
    {
        if ($campaign->delivery === 'none') {
            return;
        }

        $tenant  = $campaign->tenant;
        $isPoints = $campaign->reward_type === 'points';

        $message = $isPoints
            ? "Hi {$contact->name}! {$tenant->name} added {$campaign->rewardLabel()} to your loyalty account. Enjoy!"
            : "Hi {$contact->name}! {$tenant->name} has a special offer for you: {$campaign->rewardLabel()}. "
                . "Use code {$recipient->code}"
                . ($campaign->expires_at ? ' before ' . $campaign->expires_at->format('d M Y') : '') . '.';

        $sent = false;

        if (in_array($campaign->delivery, ['whatsapp', 'both'], true) && $contact->phone) {
            $wa = WhatsappSetting::forTenant($tenant->id);
            if ($wa->exists && $wa->is_connected) {
                try {
                    WhatsappChatbotService::forTenant($tenant->id)
                        ->sendMessage(preg_replace('/\D/', '', $contact->phone), $message);
                    $sent = true;
                } catch (\Throwable $e) {
                    // ignore delivery failure
                }
            }
        }

        if (in_array($campaign->delivery, ['email', 'both'], true) && $contact->primaryEmail()) {
            try {
                EmailService::send($tenant->id, $contact->primaryEmail(), $contact->name, "An offer from {$tenant->name}", '<p>' . e($message) . '</p>');
                $sent = true;
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if ($sent) {
            $recipient->forceFill(['sent_at' => now()])->save();
        }
    }
}
