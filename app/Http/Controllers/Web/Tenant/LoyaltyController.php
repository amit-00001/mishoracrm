<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Helpers\Sql;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\LoyaltyTransaction;
use App\Models\Tenant;
use App\Services\LoyaltyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoyaltyController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    // ── Members dashboard ────────────────────────────────────────
    public function index(Request $request): View
    {
        $tenant = auth()->user()->tenant;

        $members = Contact::query()
            ->where('loyalty_lifetime_points', '>', 0)
            ->when($request->string('search')->trim()->value(), fn ($q, $s) => $q->search($s))
            ->when(
                in_array($request->get('tier'), ['bronze', 'silver', 'gold'], true),
                fn ($q) => $q->where('loyalty_tier', $request->get('tier'))
            )
            ->orderByDesc('loyalty_points')
            ->paginate(25)
            ->withQueryString();

        $stats = [
            'members'     => Contact::where('loyalty_lifetime_points', '>', 0)->count(),
            'outstanding' => (int) Contact::sum('loyalty_points'),
            'tiers'       => Contact::query()
                ->whereNotNull('loyalty_tier')
                ->selectRaw('loyalty_tier, count(*) as c')
                ->groupBy('loyalty_tier')
                ->pluck('c', 'loyalty_tier'),
        ];

        $overview = $this->overview($tenant);

        return view('tenant.loyalty.index', compact('tenant', 'members', 'stats', 'overview'));
    }

    // Stampzo-style overview: paid-visit trend + recent ledger activity for every
    // loyalty shop, plus the customer-portal / stamp-card numbers when that
    // module is on (docs/customer-portal-loyalty.txt §6).
    private function overview(Tenant $tenant): array
    {
        $since = now()->subDays(29)->startOfDay();

        $perDay = Invoice::query()
            ->where('status', 'paid')
            ->whereNotNull('contact_id')
            ->where('paid_at', '>=', $since)
            ->selectRaw(Sql::date('paid_at') . ' as d, count(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        $trend = collect(range(29, 0))->map(function (int $ago) use ($perDay) {
            $date = now()->subDays($ago)->toDateString();

            return ['date' => $date, 'count' => (int) ($perDay[$date] ?? 0)];
        });

        $overview = [
            'trend'    => $trend,
            'visits'   => $trend->sum('count'),
            'activity' => LoyaltyTransaction::query()
                ->with('contact:id,name')
                ->latest('id')
                ->limit(20)
                ->get(),
            'portal'   => null,
        ];

        if ($tenant->hasModuleEnabled('customer_portal')) {
            $mode = $tenant->loyaltySettings()['mode'] ?? 'points';

            $overview['portal'] = [
                'linked'  => Contact::whereNotNull('customer_id')->where('phone_verified', true)->count(),
                'pending' => Contact::whereNotNull('customer_id')->where('phone_verified', false)->count(),
                'stamps'  => in_array($mode, ['stamps', 'both'], true) ? [
                    'active_cards' => Contact::where('stamp_count', '>', 0)->count(),
                    'unclaimed'    => (int) Contact::sum('stamp_rewards_earned'),
                    'unlocked'     => (int) LoyaltyTransaction::where('type', LoyaltyTransaction::TYPE_STAMP_REWARD)->where('points', '>', 0)->sum('points'),
                    'claimed'      => (int) abs(LoyaltyTransaction::where('type', LoyaltyTransaction::TYPE_STAMP_REWARD)->where('points', '<', 0)->sum('points')),
                ] : null,
            ];
        }

        return $overview;
    }

    // ── Counter lookup — "type a phone, see their points" ────────
    public function lookup(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));

        $matches = collect();
        if ($q !== '') {
            $matches = Contact::query()
                ->search($q)
                ->orderByDesc('loyalty_points')
                ->limit(10)
                ->get();
        }

        return view('tenant.loyalty.lookup', compact('q', 'matches'));
    }

    // ── Top customers by spend over a period ────────────────────
    public function topCustomers(Request $request): View
    {
        $period = in_array($request->get('period'), ['month', '30d', 'year', 'all'], true)
            ? $request->get('period') : 'month';

        $since = match ($period) {
            'month' => now()->startOfMonth(),
            '30d'   => now()->subDays(30),
            'year'  => now()->startOfYear(),
            default => null,
        };

        // Spend subquery — filtered with where() rather than HAVING, since
        // PostgreSQL does not allow select aliases in HAVING.
        $spend = \App\Models\Invoice::withoutGlobalScopes()
            ->selectRaw('COALESCE(SUM(total),0)')
            ->whereColumn('invoices.contact_id', 'contacts.id')
            ->whereNull('invoices.deleted_at')
            ->where('invoices.status', 'paid')
            ->when($since, fn ($q) => $q->where('invoices.paid_at', '>=', $since));

        $customers = Contact::query()
            ->select('contacts.*')
            ->addSelect(['_spend' => $spend])
            ->where($spend, '>', 0)
            ->orderByDesc('_spend')
            ->limit(50)
            ->get();

        return view('tenant.loyalty.top-customers', compact('customers', 'period'));
    }

    // ── Win-back — loyalty members who've gone quiet ─────────────
    public function winBack(): View
    {
        $tenant = auth()->user()->tenant;
        $days   = (int) $tenant->loyaltySettings()['inactive_days'];
        $cutoff = now()->subDays($days);

        $members = Contact::query()
            ->where('loyalty_lifetime_points', '>', 0)
            ->whereHas('invoices', fn ($q) => $q->where('status', 'paid'))
            ->whereDoesntHave('invoices', fn ($q) => $q->where('status', 'paid')->where('paid_at', '>=', $cutoff))
            ->withMax(['invoices as last_paid_at' => fn ($q) => $q->where('status', 'paid')], 'paid_at')
            ->orderByDesc('loyalty_points')
            ->paginate(25);

        return view('tenant.loyalty.win-back', compact('members', 'days'));
    }

    // ── Contacts a customer disowned ("Not me" in their wallet) ──
    // Likely a wrong number typed at the counter — fix the phone, or merge.
    public function needsReview(): View
    {
        abort_unless(auth()->user()->tenant->hasModuleEnabled('customer_portal'), 403);

        $contacts = Contact::query()
            ->whereNotNull('link_flagged_at')
            ->orderByDesc('link_flagged_at')
            ->paginate(25);

        return view('tenant.loyalty.needs-review', compact('contacts'));
    }

    // The number is actually right — stop flagging it.
    public function dismissReview(Contact $contact): RedirectResponse
    {
        abort_unless(auth()->user()->tenant->hasModuleEnabled('customer_portal'), 403);

        $contact->forceFill(['link_flagged_at' => null])->save();

        return redirect()->route('tenant.loyalty.needs-review')->with('success', 'Dismissed.');
    }

    // ── Rules ────────────────────────────────────────────────────
    public function settings(): View
    {
        $tenant   = auth()->user()->tenant;
        $settings = $tenant->loyaltySettings();

        return view('tenant.loyalty.settings', compact('tenant', 'settings'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'points_per_amount'      => ['required', 'integer', 'min:0', 'max:1000'],
            'amount_per_point_block' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'redeem_points_block'    => ['required', 'integer', 'min:1', 'max:1000000'],
            'redeem_value'           => ['required', 'numeric', 'min:0', 'max:1000000'],
            'min_discount'           => ['required', 'numeric', 'min:0', 'max:1000000'],
            'max_discount_percent'   => ['required', 'numeric', 'min:0', 'max:100'],
            'expiry_months'          => ['required', 'integer', 'min:0', 'max:120'],
            'max_points_per_day'     => ['required', 'integer', 'min:0', 'max:1000000'],
            'tiers'                  => ['required', 'array'],
            'tiers.bronze'           => ['required', 'integer', 'min:0'],
            'tiers.silver'           => ['required', 'integer', 'min:0'],
            'tiers.gold'             => ['required', 'integer', 'min:0'],
            'birthday_bonus_points'    => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'anniversary_bonus_points' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'referral_bonus_points'    => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'inactive_days'            => ['nullable', 'integer', 'min:1', 'max:3650'],
            'winback_digest'           => ['nullable', 'boolean'],
            'notify_customers'         => ['nullable', 'boolean'],
            'whatsapp_self_check'      => ['nullable', 'boolean'],
            'public_lookup'            => ['nullable', 'boolean'],
            'birthday_message'         => ['nullable', 'string', 'max:1000'],
            'anniversary_message'      => ['nullable', 'string', 'max:1000'],
            'expiry_reminder_days'     => ['nullable', 'integer', 'min:0', 'max:90'],
            'multiplier'               => ['nullable', 'numeric', 'min:1', 'max:10'],
            'multiplier_days'          => ['nullable', 'array'],
            'multiplier_days.*'        => ['in:mon,tue,wed,thu,fri,sat,sun'],
            'reward_name'              => ['nullable', 'array'],
            'reward_name.*'            => ['nullable', 'string', 'max:120'],
            'reward_points'            => ['nullable', 'array'],
            'reward_points.*'          => ['nullable', 'integer', 'min:1'],
            'welcome_bonus_points'     => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'welcome_keyword'          => ['nullable', 'string', 'max:30'],
            'welcome_wa_number'        => ['nullable', 'string', 'max:20'],
            'welcome_message'          => ['nullable', 'string', 'max:1000'],
            'mode'                     => ['nullable', 'in:points,stamps,both'],
            'stamps_required'          => ['nullable', 'integer', 'min:1', 'max:30'],
            'stamp_reward'             => ['nullable', 'string', 'max:120'],
            'stamp_per'                => ['nullable', 'in:visit,invoice,amount'],
            'stamp_amount'             => ['nullable', 'numeric', 'min:1', 'max:1000000'],
            'stamp_expiry_days'        => ['nullable', 'integer', 'min:0', 'max:3650'],
        ]);

        // The stamp fields are only on the form for portal-enabled shops. This
        // save REPLACES settings['loyalty'], so a missing field must keep the
        // shop's current value — not silently fall back to the default.
        $current = auth()->user()->tenant->loyaltySettings();
        foreach (['mode', 'stamps_required', 'stamp_reward', 'stamp_per', 'stamp_amount', 'stamp_expiry_days'] as $k) {
            $data[$k] = $data[$k] ?? $current[$k];
        }
        $data['stamp_reward'] = trim((string) $data['stamp_reward']);

        // Engagement fields default to their sensible values if the form
        // didn't carry them (keeps older callers / partial saves working).
        foreach (['birthday_bonus_points', 'anniversary_bonus_points', 'referral_bonus_points', 'inactive_days', 'welcome_bonus_points'] as $k) {
            $data[$k] = $data[$k] ?? Tenant::LOYALTY_DEFAULTS[$k];
        }
        $data['welcome_keyword'] = strtoupper(trim($data['welcome_keyword'] ?? '')) ?: 'JOIN';
        $data['winback_digest']      = $request->boolean('winback_digest');
        $data['notify_customers']    = $request->boolean('notify_customers');
        $data['whatsapp_self_check'] = $request->boolean('whatsapp_self_check');
        $data['public_lookup']       = $request->boolean('public_lookup');
        $data['expiry_reminder_days'] = $data['expiry_reminder_days'] ?? Tenant::LOYALTY_DEFAULTS['expiry_reminder_days'];
        $data['multiplier']          = (float) ($data['multiplier'] ?? 1);
        $data['multiplier_days']     = array_values($data['multiplier_days'] ?? []);

        // Build the reward catalog from the parallel name/points arrays.
        $catalog = [];
        foreach ($request->input('reward_name', []) as $i => $name) {
            $name   = trim((string) $name);
            $points = (int) ($request->input("reward_points.{$i}") ?? 0);
            if ($name !== '' && $points > 0) {
                $catalog[] = ['name' => $name, 'points' => $points];
            }
        }
        $data['reward_catalog'] = $catalog;
        unset($data['reward_name'], $data['reward_points']);

        $tiers = [
            'bronze' => (int) $data['tiers']['bronze'],
            'silver' => (int) $data['tiers']['silver'],
            'gold'   => (int) $data['tiers']['gold'],
        ];

        if (!($tiers['bronze'] <= $tiers['silver'] && $tiers['silver'] <= $tiers['gold'])) {
            return back()
                ->withInput()
                ->withErrors(['tiers.silver' => 'Tier thresholds must increase: Bronze ≤ Silver ≤ Gold.']);
        }

        $data['tiers'] = $tiers;

        $tenant   = auth()->user()->tenant;
        $settings = $tenant->settings ?? [];
        $settings['loyalty'] = $data;
        $tenant->update(['settings' => $settings]);

        return back()->with('success', 'Loyalty rules saved.');
    }

    // ── Manual points adjustment ─────────────────────────────────
    public function adjustPoints(Request $request, Contact $contact): RedirectResponse
    {
        abort_unless($contact->tenant_id === $this->tenantId(), 404);

        $data = $request->validate([
            'points' => ['required', 'integer', 'not_in:0', 'min:-1000000', 'max:1000000'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            app(LoyaltyService::class)->manualAdjust(
                $contact,
                (int) $data['points'],
                $data['reason'],
                auth()->id(),
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Points adjusted.');
    }
}
