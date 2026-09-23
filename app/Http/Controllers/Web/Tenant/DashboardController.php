<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Deal;
use App\Models\Followup;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\ServiceSubscription;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\TimeEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user   = auth()->user();
        $userId = $user->id;

        if ($user->user_type !== 'superadmin' && !$user->can('reports.view_all')) {
            return $this->staffDashboard($request, $user);
        }

        return $this->adminDashboard($request, $userId);
    }

    private function staffDashboard(Request $request, $user): \Illuminate\View\View
    {
        $userId   = $user->id;
        $tenantId = $user->tenant_id;

        $myLeads = Lead::where('assigned_to', $userId)->where('tenant_id', $tenantId);
        $myDeals = Deal::where('assigned_to', $userId)->where('tenant_id', $tenantId);

        $stats = [
            'my_leads_total'     => (clone $myLeads)->count(),
            'my_leads_new'       => (clone $myLeads)->where('status', 'new')->count(),
            'my_leads_today'     => (clone $myLeads)->whereDate('created_at', today())->count(),
            'my_leads_converted' => (clone $myLeads)->where('status', 'converted')->whereMonth('converted_at', now()->month)->count(),

            'my_deals_active'    => (clone $myDeals)->whereNotIn('stage', ['won', 'lost'])->count(),
            'my_deals_won_month' => (clone $myDeals)->where('stage', 'won')->whereMonth('updated_at', now()->month)->count(),
            'my_pipeline_value'  => (clone $myDeals)->whereNotIn('stage', ['won', 'lost'])->sum('value'),
            'my_won_value_month' => (clone $myDeals)->where('stage', 'won')->whereMonth('updated_at', now()->month)->sum('value'),

            'tasks_pending'      => Task::where('status', 'pending')
                                        ->where(function ($q) use ($userId) {
                                            $q->where('assigned_to', $userId)->orWhere('created_by', $userId);
                                        })->where('tenant_id', $user->tenant_id)->count(),
            'tasks_overdue'      => Task::where('status', 'pending')
                                        ->whereNotNull('due_at')->where('due_at', '<', now())
                                        ->where(function ($q) use ($userId) {
                                            $q->where('assigned_to', $userId)->orWhere('created_by', $userId);
                                        })->where('tenant_id', $user->tenant_id)->count(),
        ];

        $myRecentLeads = (clone $myLeads)->with('assignedTo')->latest()->limit(6)->get()
            ->map(fn($l) => [
                'id'     => $l->id,
                'name'   => $l->name,
                'phone'  => $l->phone,
                'source' => $l->source ?? '—',
                'status' => $l->status,
                'time'   => $l->created_at->diffForHumans(),
            ])->toArray();

        $myAgendaTasks = Task::where(function ($q) use ($userId) {
                $q->where('assigned_to', $userId)->orWhere('created_by', $userId);
            })
            ->where('tenant_id', $tenantId)
            ->where(function ($q) { $q->whereDate('due_at', today())->orWhere('status', 'pending'); })
            ->get()
            ->map(fn($t) => [
                'sort_time' => $t->due_at,
                'id'        => $t->id,
                'kind'      => 'task',
                'text'      => $t->title,
                'due'       => $t->due_at?->format('h:i A') ?? 'No time',
                'done'      => $t->status === 'completed',
                'priority'  => $t->priority ?? 'medium',
                'url'       => route('tenant.tasks.show', $t->id),
            ]);

        $myAgendaFollowups = Followup::with(['lead', 'contact'])
            ->where(function ($q) use ($userId) {
                $q->where('assigned_to', $userId)->orWhere('created_by', $userId);
            })
            ->where('tenant_id', $tenantId)
            ->whereDate('scheduled_at', today())
            ->where('status', 'scheduled')
            ->get()
            ->map(fn($f) => [
                'sort_time' => $f->scheduled_at,
                'id'        => $f->id,
                'kind'      => 'followup',
                'text'      => (\App\Models\Followup::types()[$f->type] ?? ucfirst($f->type)) . ' — ' . ($f->lead?->name ?? $f->contact?->name ?? 'Follow-up'),
                'due'       => $f->scheduled_at?->format('h:i A') ?? 'No time',
                'done'      => false,
                'priority'  => 'medium',
                'url'       => route('tenant.followups.show', $f->id),
            ]);

        $myTodayTasks = $myAgendaTasks->concat($myAgendaFollowups)
            ->sortBy(fn($item) => $item['sort_time'] ?? now()->addYear())
            ->take(7)
            ->values()
            ->toArray();

        $myDealsByStage = (clone $myDeals)->selectRaw('stage, COUNT(*) as count, SUM(value) as total')
            ->groupBy('stage')->get()
            ->map(fn($s) => [
                'label'  => ucfirst($s->stage),
                'value'  => $s->count,
                'amount' => '₹' . number_format($s->total / 1000, 0) . 'K',
                'stage'  => $s->stage,
            ])->toArray();

        return view('tenant.staff-dashboard', compact('user', 'stats', 'myRecentLeads', 'myTodayTasks', 'myDealsByStage'));
    }

    private function adminDashboard(Request $request, int $userId): \Illuminate\View\View|\Illuminate\Http\JsonResponse
    {
        // Task has no BelongsToTenant scope — every Task:: query below must
        // filter tenant_id explicitly or it leaks other tenants' task data.
        $tenantId = auth()->user()->tenant_id;

        // ── Stats ─────────────────────────────────────────────────
        $stats = [
            // Leads
            'total_leads'        => Lead::count(),
            'new_leads_today'    => Lead::whereDate('created_at', today())->count(),
            'new_leads_count'    => Lead::where('status', 'new')->count(),
            'new_leads_month'    => Lead::thisMonth()->count(),

            // Deals
            'active_deals'       => Deal::whereNotIn('stage', ['won', 'lost'])->count(),
            'pipeline_value'     => Deal::whereNotIn('stage', ['won', 'lost'])->sum('value'),
            'won_this_month'     => Deal::where('stage', 'won')
                                        ->whereMonth('updated_at', now()->month)
                                        ->count(),
            'won_value_month'    => Deal::where('stage', 'won')
                                        ->whereMonth('updated_at', now()->month)
                                        ->sum('value'),

            // Revenue
            'revenue_this_month' => Invoice::where('status', 'paid')
                                           ->whereMonth('paid_at', now()->month)
                                           ->whereYear('paid_at', now()->year)
                                           ->sum('total'),
            'revenue_last_month' => Invoice::where('status', 'paid')
                                           ->whereMonth('paid_at', now()->subMonth()->month)
                                           ->whereYear('paid_at', now()->subMonth()->year)
                                           ->sum('total'),
            'invoices_paid'      => Invoice::where('status', 'paid')
                                           ->whereMonth('paid_at', now()->month)
                                           ->count(),
            'invoices_overdue'   => Invoice::where('status', 'overdue')->count(),

            // Tasks
            'tasks_pending'      => Task::where('tenant_id', $tenantId)
                                        ->where('status', 'pending')
                                        ->where(function ($q) use ($userId) {
                                            $q->where('assigned_to', $userId)
                                              ->orWhere('created_by', $userId);
                                        })->count(),
            'tasks_completed'    => Task::where('tenant_id', $tenantId)
                                        ->where('status', 'completed')
                                        ->whereMonth('completed_at', now()->month)
                                        ->count(),
            'tasks_overdue'      => Task::where('tenant_id', $tenantId)
                                        ->where('status', 'pending')
                                        ->whereNotNull('due_at')
                                        ->where('due_at', '<', now())
                                        ->count(),

            // Follow-ups
            'followups_today'    => Followup::whereDate('scheduled_at', today())
                                            ->where('status', 'scheduled')
                                            ->count(),

            // Month-over-month comparison bases for the stat-card trend badges
            'new_leads_last_month'      => Lead::whereMonth('created_at', now()->subMonth()->month)
                                                ->whereYear('created_at', now()->subMonth()->year)
                                                ->count(),
            'deals_created_this_month'  => Deal::whereMonth('created_at', now()->month)
                                                ->whereYear('created_at', now()->year)
                                                ->count(),
            'deals_created_last_month'  => Deal::whereMonth('created_at', now()->subMonth()->month)
                                                ->whereYear('created_at', now()->subMonth()->year)
                                                ->count(),
            'tasks_completed_last_month'=> Task::where('tenant_id', $tenantId)
                                                ->where('status', 'completed')
                                                ->whereMonth('completed_at', now()->subMonth()->month)
                                                ->whereYear('completed_at', now()->subMonth()->year)
                                                ->count(),
        ];


        // ── Trend %s — rolling 30-day window vs the previous 30 days.
        // Calendar-month comparison made every card read "-100%" on the
        // 1st of a month; a rolling window stays meaningful any day.
        // 0 when there's no prior-window baseline (avoids a misleading
        // divide-by-zero swing on a brand new tenant). ────────────────
        $trendPct = fn($current, $previous) => $previous > 0
            ? round((($current - $previous) / $previous) * 100, 1)
            : 0;

        $win  = [now()->subDays(30), now()];
        $prev = [now()->subDays(60), now()->subDays(30)];

        $leads30      = Lead::whereBetween('created_at', $win)->count();
        $leadsPrev30  = Lead::whereBetween('created_at', $prev)->count();
        $dealsNew30   = Deal::whereBetween('created_at', $win)->count();
        $dealsPrev30  = Deal::whereBetween('created_at', $prev)->count();
        $revenue30    = Invoice::where('status', 'paid')->whereBetween('paid_at', $win)->sum('total');
        $revenuePrev30 = Invoice::where('status', 'paid')->whereBetween('paid_at', $prev)->sum('total');

        $stats['revenue_trend'] = $trendPct($revenue30, $revenuePrev30);
        $stats['leads_trend']   = $trendPct($leads30, $leadsPrev30);
        $stats['deals_trend']   = $trendPct($dealsNew30, $dealsPrev30);
        $stats['tasks_trend']   = $trendPct($stats['tasks_completed'], $stats['tasks_completed_last_month']);

        // ── Revenue chart — last 12 months ────────────────────────
        $revenueRaw = Invoice::where('status', 'paid')
            ->whereYear('paid_at', now()->year)
            ->selectRaw('MONTH(paid_at) as month, SUM(total) as total')
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $chartData = [];
        for ($m = 1; $m <= 12; $m++) {
            $chartData[] = round($revenueRaw[$m] ?? 0, 2);
        }

        // ── KPI sparklines — last 8 months of activity so each stat
        // card carries a small trend chart like the dashboard mockup. ──
        $sparkMonths = collect(range(7, 0))->map(fn($i) => now()->startOfMonth()->subMonths($i));
        $sparkFrom   = $sparkMonths->first();
        $monthKey    = fn($y, $m) => $y . '-' . $m;

        $leadsByMonth = Lead::where('created_at', '>=', $sparkFrom)
            ->selectRaw('YEAR(created_at) y, MONTH(created_at) m, COUNT(*) c')
            ->groupBy('y', 'm')->get()->keyBy(fn($r) => $monthKey($r->y, $r->m));
        $dealsByMonth = Deal::where('created_at', '>=', $sparkFrom)
            ->selectRaw('YEAR(created_at) y, MONTH(created_at) m, COUNT(*) c')
            ->groupBy('y', 'm')->get()->keyBy(fn($r) => $monthKey($r->y, $r->m));
        $wonByMonth = Deal::where('stage', 'won')->where('updated_at', '>=', $sparkFrom)
            ->selectRaw('YEAR(updated_at) y, MONTH(updated_at) m, SUM(value) v')
            ->groupBy('y', 'm')->get()->keyBy(fn($r) => $monthKey($r->y, $r->m));
        $convByMonth = Lead::whereNotNull('converted_at')->where('converted_at', '>=', $sparkFrom)
            ->selectRaw('YEAR(converted_at) y, MONTH(converted_at) m, COUNT(*) c')
            ->groupBy('y', 'm')->get()->keyBy(fn($r) => $monthKey($r->y, $r->m));

        $spark = ['leads' => [], 'deals' => [], 'won' => [], 'conversion' => []];
        foreach ($sparkMonths as $d) {
            $k = $monthKey($d->year, $d->month);
            $spark['leads'][]      = (int) ($leadsByMonth[$k]->c ?? 0);
            $spark['deals'][]      = (int) ($dealsByMonth[$k]->c ?? 0);
            $spark['won'][]        = round($wonByMonth[$k]->v ?? 0, 2);
            $spark['conversion'][] = (int) ($convByMonth[$k]->c ?? 0);
        }

        // ── Extra KPI stats — Won deals value + Conversion rate, with
        // their own month-over-month trend badges. ────────────────────
        $convertedTotal = Lead::where('status', 'converted')->count();
        $stats['conversion_rate']       = $stats['total_leads'] > 0
            ? round($convertedTotal / $stats['total_leads'] * 100, 1)
            : 0;
        $stats['conversions_this_month'] = Lead::where('status', 'converted')
            ->whereMonth('converted_at', now()->month)->whereYear('converted_at', now()->year)->count();

        $conv30      = Lead::where('status', 'converted')->whereBetween('converted_at', $win)->count();
        $convPrev30  = Lead::where('status', 'converted')->whereBetween('converted_at', $prev)->count();
        $wonVal30    = Deal::where('stage', 'won')->whereBetween('updated_at', $win)->sum('value');
        $wonValPrev30 = Deal::where('stage', 'won')->whereBetween('updated_at', $prev)->sum('value');

        $stats['won_trend']        = $trendPct($wonVal30, $wonValPrev30);
        $stats['conversion_trend'] = $trendPct($conv30, $convPrev30);

        // ── Pipeline by stage ─────────────────────────────────────
        $pipelineRaw = Deal::selectRaw('stage, COUNT(*) as count, SUM(value) as total')
            ->groupBy('stage')
            ->get()
            ->keyBy('stage');

        $stageColors = [
            'new'         => 'var(--accent)',
            'proposal'    => 'var(--amber)',
            'negotiation' => 'var(--purple)',
            'won'         => 'var(--green)',
            'lost'        => 'var(--red)',
        ];

        // Representative (highest-value) deal per stage — powers the
        // company / value / rep line on each pipeline stage card.
        $stageOrder = ['new', 'proposal', 'negotiation', 'won', 'lost'];
        $topDeals   = Deal::with(['contact', 'lead', 'assignedTo'])
            ->whereIn('stage', $stageOrder)
            ->orderByDesc('value')
            ->get()
            ->groupBy('stage')
            ->map(fn($g) => $g->first());

        $compactMoney = function ($v) {
            $v = (float) $v;
            if ($v >= 100000) return '₹' . number_format($v / 100000, 1) . 'L';
            if ($v >= 1000)   return '₹' . number_format($v / 1000, 0) . 'K';
            return '₹' . number_format($v);
        };

        // Per-stage 6-month sparkline — deals in each stage bucketed by
        // the month they were created (the little trend line per column).
        $pipeMonths   = collect(range(5, 0))->map(fn($i) => now()->startOfMonth()->subMonths($i));
        $pipeSparkRaw = Deal::whereIn('stage', $stageOrder)
            ->where('created_at', '>=', $pipeMonths->first())
            ->selectRaw('stage, YEAR(created_at) y, MONTH(created_at) m, COUNT(*) c')
            ->groupBy('stage', 'y', 'm')
            ->get();

        $pipeSpark = [];
        foreach ($stageOrder as $st) {
            $pipeSpark[$st] = $pipeMonths->map(function ($d) use ($pipeSparkRaw, $st) {
                $row = $pipeSparkRaw->first(fn($r) => $r->stage === $st
                    && (int) $r->y === $d->year && (int) $r->m === $d->month);
                return (int) ($row->c ?? 0);
            })->toArray();
        }

        $maxCount = $pipelineRaw->max('count') ?: 1;
        $pipeline = collect($stageOrder)->map(function ($stage) use ($pipelineRaw, $topDeals, $stageColors, $maxCount, $compactMoney, $pipeSpark) {
            $row   = $pipelineRaw[$stage] ?? null;
            $count = (int) ($row->count ?? 0);
            $total = (float) ($row->total ?? 0);
            $top   = $topDeals[$stage] ?? null;

            return [
                'stage'       => $stage,
                'label'       => Deal::stages()[$stage] ?? ucfirst($stage),
                'value'       => $count,
                'amount'      => '₹' . number_format($total / 100000, 1) . 'L',
                'color'       => $stageColors[$stage] ?? 'var(--accent)',
                'pct'         => round(($count / $maxCount) * 100),
                'spark'       => $pipeSpark[$stage] ?? [],
                'top_company' => $top ? ($top->contact?->company ?: $top->lead?->company ?: $top->title) : null,
                'top_value'   => $top ? $compactMoney($top->value) : null,
                'top_rep'     => $top?->assignedTo?->name,
            ];
        })->toArray();

        // ── Recent leads ──────────────────────────────────────────
        $recentLeads = Lead::with('assignedTo')
            ->latest()
            ->limit(6)
            ->get()
            ->map(fn($l) => [
                'id'       => $l->id,
                'name'     => $l->name,
                'phone'    => $l->phone,
                'company'  => $l->company ?: '—',
                'value'    => (float) ($l->lead_value ?? 0),
                'source'   => $l->source ?? '—',
                'status'   => $l->status,
                'assigned' => $l->assignedTo?->name ?? '—',
                'time'     => $l->created_at->diffForHumans(),
            ])->toArray();

        // ── Today's Agenda (Tasks + Follow-ups, merged) ────────────
        $agendaTasks = Task::with('assignedTo')
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($userId) {
                $q->where('assigned_to', $userId)
                  ->orWhere('created_by', $userId);
            })
            ->where(function ($q) {
                $q->whereDate('due_at', today())
                  ->orWhere('status', 'pending');
            })
            ->get()
            ->map(fn($t) => [
                'sort_time' => $t->due_at,
                'id'        => $t->id,
                'kind'      => 'task',
                'text'      => $t->title,
                'due'       => $t->due_at?->format('h:i A') ?? 'No time set',
                'done'      => $t->status === 'completed',
                'priority'  => $t->priority,
                'url'       => route('tenant.tasks.show', $t->id),
            ]);

        $agendaFollowups = Followup::with(['lead', 'contact'])
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($userId) {
                $q->where('assigned_to', $userId)
                  ->orWhere('created_by', $userId);
            })
            ->whereDate('scheduled_at', today())
            ->where('status', 'scheduled')
            ->get()
            ->map(fn($f) => [
                'sort_time' => $f->scheduled_at,
                'id'        => $f->id,
                'kind'      => 'followup',
                'text'      => (\App\Models\Followup::types()[$f->type] ?? ucfirst($f->type)) . ' — ' . ($f->lead?->name ?? $f->contact?->name ?? 'Follow-up'),
                'due'       => $f->scheduled_at?->format('h:i A') ?? 'No time set',
                'done'      => false,
                'priority'  => 'medium',
                'url'       => route('tenant.followups.show', $f->id),
            ]);

        $todayTasks = $agendaTasks->concat($agendaFollowups)
            ->sortBy(fn($item) => $item['sort_time'] ?? now()->addYear())
            ->take(7)
            ->values()
            ->toArray();

        // ── Recent activity ───────────────────────────────────────
        // Combine recent leads + deals + tasks into one feed
        $actLeads = Lead::latest()->limit(3)->get()->map(fn($l) => [
            'type' => 'lead',
            'text' => '<strong>' . e($l->createdBy?->name ?? 'Someone') . '</strong> added lead <strong>' . e($l->name) . '</strong>',
            'time' => $l->created_at,
        ]);

        $actDeals = Deal::latest()->limit(3)->get()->map(fn($d) => [
            'type' => 'deal',
            'text' => 'Deal <strong>' . e($d->title) . '</strong> is in <strong>' . ucfirst($d->stage) . '</strong> stage',
            'time' => $d->updated_at,
        ]);

        $actTasks = Task::where('tenant_id', $tenantId)->where('status', 'completed')->latest('completed_at')->limit(2)->get()->map(fn($t) => [
            'type' => 'task',
            'text' => '<strong>' . e($t->assignedTo?->name ?? 'Someone') . '</strong> completed task <strong>' . e($t->title) . '</strong>',
            'time' => $t->completed_at ?? $t->updated_at,
        ]);

        $activities = $actLeads->merge($actDeals)->merge($actTasks)
            ->sortByDesc('time')
            ->take(7)
            ->map(fn($a) => [
                'type' => $a['type'],
                'text' => $a['text'],
                'time' => $a['time']->diffForHumans(),
            ])->values()->toArray();

        // ── Lead source breakdown ─────────────────────────────────
        $leadSources = Lead::selectRaw('source, COUNT(*) as count')
            ->whereNotNull('source')
            ->groupBy('source')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->mapWithKeys(fn($s) => [$s->source => $s->count])
            ->toArray();

        // ── New leads badge for sidebar ───────────────────────────
        $newLeadsCount = $stats['new_leads_count'];

        // ── Service subscription alerts (only when the module's on) ──
        $subscriptionAlerts = null;
        $tenant = auth()->user()->tenant;
        if ($tenant?->hasModuleEnabled('subscriptions')) {
            $reminderDays = $tenant->subscriptionReminderDays();
            $subscriptionAlerts = [
                'expiring' => ServiceSubscription::where('tenant_id', $tenantId)->expiringSoon($reminderDays)->count(),
                'expired'  => ServiceSubscription::where('tenant_id', $tenantId)->expired()->count(),
            ];
        }

        // ── Appointment alerts (only when the module's on) — today's
        // upcoming bookings, so staff notice a full day at a glance. ────
        $appointmentAlerts = null;
        if ($tenant?->hasModuleEnabled('appointments')) {
            $appointmentAlerts = [
                'today' => Appointment::where('tenant_id', $tenantId)->today()->active()->count(),
            ];
        }

        // ── Time tracking alerts — any timer left running is worth
        // surfacing, since a forgotten timer skews billable-hours totals. ─
        $timeTrackingAlerts = null;
        if ($tenant?->hasModuleEnabled('time_tracking')) {
            $timeTrackingAlerts = [
                'running' => TimeEntry::where('tenant_id', $tenantId)->whereNull('ended_at')->count(),
            ];
        }

        // ── Ticket alerts — open/unassigned tickets need a human to
        // pick them up, so this mirrors the subscription-alert pattern. ──
        $ticketAlerts = null;
        if ($tenant?->hasModuleEnabled('tickets')) {
            $ticketAlerts = [
                'open'       => Ticket::where('tenant_id', $tenantId)->openTickets()->count(),
                'unassigned' => Ticket::where('tenant_id', $tenantId)->openTickets()->whereNull('assigned_to')->count(),
            ];
        }

        // ── Manufacturing alerts — low stock / pending purchase requests
        // (only when the module's on) ─────────────────────────────────
        $manufacturingAlerts = null;
        if ($tenant?->hasModuleEnabled('manufacturing')) {
            $manufacturingAlerts = [
                'low_stock'        => Product::lowStock()->count(),
                'pending_purchase' => PurchaseRequest::where('status', 'pending')->count(),
            ];
        }

        // ── Compile all data ──────────────────────────────────────
        $data = compact(
            'stats',
            'chartData',
            'spark',
            'pipeline',
            'recentLeads',
            'todayTasks',
            'activities',
            'leadSources',
            'newLeadsCount',
            'subscriptionAlerts',
            'appointmentAlerts',
            'timeTrackingAlerts',
            'ticketAlerts',
            'manufacturingAlerts'
        );

        // ── Web or API ────────────────────────────────────────────
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'data'    => $data,
            ]);
        }

        // return response()->json(['success' => true, 'data' => Auth::user()->tenant]); // ← Debugging line, remove in production --- IGNORE ---

        return view('tenant.dashboard', $data);
    }
}