<?php

namespace App\Http\Controllers\Web\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Helpers\Sql;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        // ── Tenant stats ──────────────────────────────────────────
        $stats = [
            'total_tenants'    => Tenant::count(),
            'active_tenants'   => Tenant::where('status', 'active')->count(),
            'inactive_tenants' => Tenant::where('status', 'inactive')->count(),
            'suspended'        => Tenant::where('status', 'suspended')->count(),
            'new_this_month'   => Tenant::whereMonth('created_at', now()->month)
                                        ->whereYear('created_at', now()->year)
                                        ->count(),
            'new_today'        => Tenant::whereDate('created_at', today())->count(),

            // withoutGlobalScopes — BelongsToTenant bypass karo
            'total_users'  => User::withoutGlobalScopes()
                                  ->whereNotNull('tenant_id')
                                  ->count(),

            'active_users' => User::withoutGlobalScopes()
                                  ->whereNotNull('tenant_id')
                                  ->where('is_active', true)
                                  ->count(),

            // Subscriptions
            'active_subs'   => Subscription::where('status', 'active')->count(),
            'trial_subs'    => Subscription::where('status', 'trial')->count(),
            'expired_subs'  => Subscription::where('status', 'expired')->count(),
            'expiring_soon' => Subscription::where('status', 'active')
                                           ->whereBetween('ends_at', [now(), now()->addDays(7)])
                                           ->count(),

            'total_mrr' => $this->calculateMRR(),
        ];

        // ── Signups chart ─────────────────────────────────────────
        $signupsRaw = Tenant::selectRaw(Sql::month('created_at') . ' as month, COUNT(*) as count')
            ->whereYear('created_at', now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $signupsChart = [];
        for ($m = 1; $m <= 12; $m++) {
            $signupsChart[] = $signupsRaw[$m] ?? 0;
        }

        // ── Revenue chart ─────────────────────────────────────────
        $revenueChart = [];
        for ($m = 1; $m <= 12; $m++) {
            $revenueChart[] = round(
                Subscription::where('status', 'active')
                    ->whereMonth('started_at', $m)
                    ->whereYear('started_at', now()->year)
                    ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
                    ->sum('plans.monthly_price'),
                2
            );
        }

        // ── Plan breakdown ────────────────────────────────────────
        $planBreakdown = $this->getPlanBreakdown();

        // ── Recent tenants ────────────────────────────────────────
        $recentTenants = Tenant::with('subscription.plan')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn($t) => [
                'id'         => $t->id,
                'name'       => $t->name,
                'subdomain'  => $t->subdomain,
                'email'      => $t->email,
                'status'     => $t->status,
                'plan'       => $t->subscription?->plan?->name ?? 'No plan',
                'sub_status' => $t->subscription?->status ?? '—',
                'joined'     => $t->created_at->format('d M Y'),
                'joined_ago' => $t->created_at->diffForHumans(),
            ])->toArray();

        // ── Expiring subscriptions ────────────────────────────────
        $expiringSoon = Subscription::with('tenant', 'plan')
            ->where('status', 'active')
            ->whereBetween('ends_at', [now(), now()->addDays(7)])
            ->orderBy('ends_at')
            ->limit(5)
            ->get()
            ->map(fn($s) => [
                'tenant_name' => $s->tenant?->name ?? '—',
                'plan'        => $s->plan?->name   ?? '—',
                'ends_at'     => $s->ends_at?->format('d M Y') ?? '—',
                'days_left'   => (int) now()->diffInDays($s->ends_at),
            ])->toArray();

        $data = compact(
            'stats',
            'signupsChart',
            'revenueChart',
            'planBreakdown',
            'recentTenants',
            'expiringSoon'
        );

      

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'data' => $data]);
        }


        return view('superadmin.dashboard', $data);
    }

    // ─────────────────────────────────────────────────────────────
    // withCount ki jagah alag alag queries — reliable aur safe
    // ─────────────────────────────────────────────────────────────
    private function getPlanBreakdown(): array
    {
        try {
            $plans = Plan::orderBy('sort_order')->get();

            if ($plans->isEmpty()) {
                return [];
            }

            return $plans->map(function (Plan $plan) {

                $activeCount = Subscription::where('plan_id', $plan->id)
                    ->where('status', 'active')
                    ->count();

                $trialCount = Subscription::where('plan_id', $plan->id)
                    ->where('status', 'trial')
                    ->count();

                return [
                    'name'         => $plan->name,
                    'slug'         => $plan->slug,
                    'price'        => (float) $plan->monthly_price,
                    'active_count' => $activeCount,
                    'trial_count'  => $trialCount,
                    'total'        => $activeCount + $trialCount,
                    'mrr'          => $activeCount * (float) $plan->monthly_price,
                ];

            })->toArray();

        } catch (\Exception $e) {
            Log::error('Plan breakdown error: ' . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    // MRR calculation
    // ─────────────────────────────────────────────────────────────
    private function calculateMRR(): float
    {
        try {
            $mrr = Subscription::where('subscriptions.status', 'active')
                ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
                ->selectRaw('
                    SUM(
                        CASE
                            WHEN subscriptions.billing_cycle = \'yearly\'
                            THEN plans.yearly_price / 12
                            ELSE plans.monthly_price
                        END
                    ) as mrr
                ')
                ->value('mrr');

            return (float) ($mrr ?? 0);

        } catch (\Exception $e) {
            Log::error('MRR calculation error: ' . $e->getMessage());
            return 0;
        }
    }
}