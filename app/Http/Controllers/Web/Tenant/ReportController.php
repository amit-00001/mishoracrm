<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Followup;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Quotation;
use App\Models\Service;
use App\Models\ServiceSubscription;
use App\Models\Task;
use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    // These reports are tenant-wide aggregates with no per-user "own" view —
    // only reports.view_all holders (or superadmin) may access them.
    private function requireViewAll(): void
    {
        abort_unless(auth()->user()->user_type === 'superadmin' || auth()->user()->can('reports.view_all'), 403);
    }

    // ── Date range helper ─────────────────────────────────────────
    private function dateRange(Request $request): array
    {
        $preset = $request->get('range', 'this_month');

        return match($preset) {
            'today'        => [now()->startOfDay(),           now()->endOfDay()],
            'this_week'    => [now()->startOfWeek(),          now()->endOfWeek()],
            'last_week'    => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'this_month'   => [now()->startOfMonth(),         now()->endOfMonth()],
            'last_month'   => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'this_quarter' => [now()->startOfQuarter(),       now()->endOfQuarter()],
            'this_year'    => [now()->startOfYear(),          now()->endOfYear()],
            'last_year'    => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            'custom'       => [
                Carbon::parse($request->get('from', now()->startOfMonth())),
                Carbon::parse($request->get('to',   now()->endOfMonth())),
            ],
            default        => [now()->startOfMonth(), now()->endOfMonth()],
        };
    }

    // ── Overview — main report dashboard ─────────────────────────
    public function overview(Request $request): View
    {
        $this->requireViewAll();
        [$from, $to] = $this->dateRange($request);
        $tid = $this->tenantId();

        // ── KPI cards
        $kpis = [
            'leads'     => Lead::whereBetween('created_at', [$from, $to])->count(),
            'deals'     => Deal::whereBetween('created_at', [$from, $to])->count(),
            'revenue'   => Invoice::where('status', 'paid')->whereBetween('paid_at', [$from, $to])->sum('total'),
            'contacts'  => Contact::whereBetween('created_at', [$from, $to])->count(),
            'tasks_done'=> Task::where('tenant_id', $tid)->where('status', 'completed')->whereBetween('updated_at', [$from, $to])->count(),
            'quotations'=> Quotation::whereBetween('created_at', [$from, $to])->count(),
        ];

        // ── Lead sources breakdown
        $leadSources = Lead::whereBetween('created_at', [$from, $to])
            ->selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->orderByDesc('count')
            ->get();

        // ── Deal stages breakdown
        $dealStages = Deal::whereBetween('created_at', [$from, $to])
            ->selectRaw('stage, COUNT(*) as count, SUM(value) as total')
            ->groupBy('stage')
            ->get();

        // ── Monthly revenue — last 12 months
        $monthlyRevenue = Invoice::where('status', 'paid')
            ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as month, SUM(total) as revenue")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // ── Lead conversion rate
        $totalLeads     = Lead::whereBetween('created_at', [$from, $to])->count();
        $convertedLeads = Lead::where('status', 'converted')->whereBetween('created_at', [$from, $to])->count();
        $conversionRate = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 1) : 0;

        // ── Top staff by leads
        $topStaff = User::withoutGlobalScopes()
            ->where('tenant_id', $tid)
            ->withCount(['createdLeads as leads_count' => fn($q) => $q->whereBetween('created_at', [$from, $to])])
            ->orderByDesc('leads_count')
            ->limit(5)
            ->get();

        return view('tenant.reports.overview', compact(
            'kpis', 'leadSources', 'dealStages',
            'monthlyRevenue', 'conversionRate',
            'topStaff', 'from', 'to', 'request'
        ));
    }

    // ── Leads report ──────────────────────────────────────────────
    public function leads(Request $request): View
    {
        $this->requireViewAll();
        [$from, $to] = $this->dateRange($request);

        $statuses    = config('crm.lead.statuses');
        $sources     = config('crm.lead.sources');
        $priorities  = config('crm.lead.priorities');

        // By status
        $byStatus = Lead::whereBetween('created_at', [$from, $to])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()->keyBy('status');

        // By source
        $bySource = Lead::whereBetween('created_at', [$from, $to])
            ->selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->orderByDesc('count')
            ->get();

        // By priority
        $byPriority = Lead::whereBetween('created_at', [$from, $to])
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->get()->keyBy('priority');

        // Daily trend
        $dailyTrend = Lead::whereBetween('created_at', [$from, $to])
            ->selectRaw("DATE(created_at) as day, COUNT(*) as count")
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // Assigned to staff
        $byStaff = Lead::whereBetween('created_at', [$from, $to])
            ->selectRaw('assigned_to, COUNT(*) as count')
            ->groupBy('assigned_to')
            ->with('assignedTo:id,name')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Recent leads
        $recentLeads = Lead::with(['assignedTo:id,name'])
            ->whereBetween('created_at', [$from, $to])
            ->latest()->limit(10)->get();

        // Total + conversion
        $total      = Lead::whereBetween('created_at', [$from, $to])->count();
        $converted  = Lead::where('status','converted')->whereBetween('created_at', [$from, $to])->count();
        $lost       = Lead::where('status','lost')->whereBetween('created_at', [$from, $to])->count();

        return view('tenant.reports.leads', compact(
            'byStatus','bySource','byPriority','dailyTrend',
            'byStaff','recentLeads','total','converted','lost',
            'statuses','sources','priorities','from','to','request'
        ));
    }

    // ── Recent Conversions report ───────────────────────────────────
    public function conversions(Request $request): View
    {
        $this->requireViewAll();
        [$from, $to] = $this->dateRange($request);

        $sources   = Lead::sources();
        $curSource = $request->get('source', 'all');

        $baseQuery = Lead::where('status', 'converted')
            ->whereBetween('converted_at', [$from, $to])
            ->when($curSource !== 'all', fn($q) => $q->where('source', $curSource));

        // By-source breakdown (within the date range, before the source filter is applied)
        $bySource = Lead::where('status', 'converted')
            ->whereBetween('converted_at', [$from, $to])
            ->selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->orderByDesc('count')
            ->get();

        $totalConverted = (clone $baseQuery)->count();

        $totalDealValue = (clone $baseQuery)
            ->join('deals', 'deals.lead_id', '=', 'leads.id')
            ->sum('deals.value');

        $avgDaysToConvert = (clone $baseQuery)
            ->whereNotNull('converted_at')
            ->selectRaw('AVG(DATEDIFF(converted_at, created_at)) as avg_days')
            ->value('avg_days');

        $leads = (clone $baseQuery)
            ->with(['assignedTo:id,name', 'deal:id,lead_id,value,stage'])
            ->orderByDesc('converted_at')
            ->paginate(20)
            ->withQueryString();

        return view('tenant.reports.conversions', compact(
            'leads', 'sources', 'curSource', 'bySource',
            'totalConverted', 'totalDealValue', 'avgDaysToConvert',
            'from', 'to', 'request'
        ));
    }

    // ── Deals report ──────────────────────────────────────────────
    public function deals(Request $request): View
    {
        $this->requireViewAll();
        [$from, $to] = $this->dateRange($request);

        $stages = config('crm.deal.stages');

        // By stage
        $byStage = Deal::whereBetween('created_at', [$from, $to])
            ->selectRaw('stage, COUNT(*) as count, SUM(value) as total, AVG(value) as avg_value')
            ->groupBy('stage')
            ->get()->keyBy('stage');

        // Won deals
        $wonDeals  = Deal::where('stage','won')->whereBetween('updated_at', [$from, $to]);
        $wonCount  = $wonDeals->count();
        $wonValue  = $wonDeals->sum('value');

        // Lost deals
        $lostCount = Deal::where('stage','lost')->whereBetween('updated_at', [$from, $to])->count();

        // Win rate
        $closedTotal = $wonCount + $lostCount;
        $winRate     = $closedTotal > 0 ? round(($wonCount / $closedTotal) * 100, 1) : 0;

        // Avg deal size
        $avgDealSize = Deal::whereBetween('created_at', [$from, $to])->avg('value') ?? 0;

        // Pipeline value
        $pipelineValue = Deal::whereNotIn('stage',['won','lost'])->sum('value');

        // Monthly deal trend
        $monthlyDeals = Deal::whereBetween('created_at', [$from, $to])
            ->selectRaw("DATE_FORMAT(created_at,'%Y-%m') as month, COUNT(*) as count, SUM(value) as total")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Top deals
        $topDeals = Deal::with(['contact:id,name','assignedTo:id,name'])
            ->whereBetween('created_at', [$from, $to])
            ->orderByDesc('value')
            ->limit(10)
            ->get();

        // By assigned staff
        $byStaff = Deal::whereBetween('created_at', [$from, $to])
            ->selectRaw('assigned_to, COUNT(*) as count, SUM(value) as total')
            ->groupBy('assigned_to')
            ->with('assignedTo:id,name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        return view('tenant.reports.deals', compact(
            'byStage','wonCount','wonValue','lostCount','winRate',
            'avgDealSize','pipelineValue','monthlyDeals',
            'topDeals','byStaff','stages','from','to','request'
        ));
    }

    // ── Deal ↔ Quotation report ────────────────────────────────────
    public function dealQuotations(Request $request): View
    {
        $this->requireViewAll();
        [$from, $to] = $this->dateRange($request);

        $baseQuery = Deal::whereBetween('created_at', [$from, $to]);

        $totalDeals            = (clone $baseQuery)->count();
        $dealsWithQuotations   = (clone $baseQuery)->has('quotations')->count();
        $dealsWithoutQuotations = $totalDeals - $dealsWithQuotations;

        $dealIds         = (clone $baseQuery)->pluck('id');
        $totalQuotations = Quotation::whereIn('deal_id', $dealIds)->count();
        $avgPerDeal      = $totalDeals > 0 ? round($totalQuotations / $totalDeals, 2) : 0;

        // Status breakdown across those quotations
        $statusBreakdown = Quotation::whereIn('deal_id', $dealIds)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()->keyBy('status');

        // Table — deals with quotation counts + latest quotation
        $deals = (clone $baseQuery)
            ->withCount('quotations')
            ->with(['contact:id,name', 'quotations' => fn($q) => $q->latest()->limit(1)])
            ->orderByDesc('quotations_count')
            ->paginate(20)
            ->withQueryString();

        $statuses = Quotation::statuses();
        $stages   = Deal::stages();

        return view('tenant.reports.deal-quotations', compact(
            'totalDeals', 'dealsWithQuotations', 'dealsWithoutQuotations',
            'totalQuotations', 'avgPerDeal', 'statusBreakdown',
            'deals', 'statuses', 'stages', 'from', 'to', 'request'
        ));
    }

    // ── Revenue report ────────────────────────────────────────────
    public function revenue(Request $request): View
    {
        $this->requireViewAll();
        [$from, $to] = $this->dateRange($request);

        // Summary
        $totalRevenue   = Invoice::where('status','paid')->whereBetween('paid_at', [$from, $to])->sum('total');
        $pendingRevenue = Invoice::whereIn('status',['sent','partial'])->sum('total');
        $overdueRevenue = Invoice::where('status','overdue')->sum('total');
        $totalInvoices  = Invoice::whereBetween('created_at', [$from, $to])->count();
        $paidInvoices   = Invoice::where('status','paid')->whereBetween('paid_at', [$from, $to])->count();

        // Monthly revenue
        $monthlyRevenue = Invoice::where('status','paid')
            ->where('paid_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw("DATE_FORMAT(paid_at,'%Y-%m') as month, SUM(total) as revenue, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Quotation to Invoice conversion
        $quotationsTotal  = Quotation::whereBetween('created_at', [$from, $to])->count();
        $quotationsAccepted = Quotation::where('status','accepted')->whereBetween('created_at', [$from, $to])->count();
        $quotationRate    = $quotationsTotal > 0 ? round(($quotationsAccepted / $quotationsTotal) * 100, 1) : 0;

        // By contact
        $byContact = Invoice::where('status','paid')
            ->whereBetween('paid_at', [$from, $to])
            ->selectRaw('contact_id, SUM(total) as revenue, COUNT(*) as count')
            ->groupBy('contact_id')
            ->with('contact:id,name,company')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        // Invoice status breakdown
        $invoiceStatuses = Invoice::whereBetween('created_at', [$from, $to])
            ->selectRaw('status, COUNT(*) as count, SUM(total) as total')
            ->groupBy('status')
            ->get()->keyBy('status');

        // Recent paid invoices
        $recentPaid = Invoice::where('status','paid')
            ->with(['contact:id,name'])
            ->whereBetween('paid_at', [$from, $to])
            ->latest('paid_at')
            ->limit(10)
            ->get();

        return view('tenant.reports.revenue', compact(
            'totalRevenue','pendingRevenue','overdueRevenue',
            'totalInvoices','paidInvoices','monthlyRevenue',
            'quotationsTotal','quotationsAccepted','quotationRate',
            'byContact','invoiceStatuses','recentPaid',
            'from','to','request'
        ));
    }

    // ── Staff performance ─────────────────────────────────────────
    public function staff(Request $request): View
    {
        $this->requireViewAll();
        [$from, $to] = $this->dateRange($request);
        $tid = $this->tenantId();

        $staffList = User::withoutGlobalScopes()
            ->where('tenant_id', $tid)
            ->where('user_type', 'staff')
            ->get()
            ->map(function ($user) use ($from, $to, $tid) {
                $leadsCreated  = Lead::where('created_by', $user->id)->whereBetween('created_at', [$from, $to])->count();
                $leadsAssigned = Lead::where('assigned_to', $user->id)->whereBetween('created_at', [$from, $to])->count();
                $dealsWon      = Deal::where('assigned_to', $user->id)->where('stage','won')->whereBetween('updated_at', [$from, $to])->count();
                $dealValue     = Deal::where('assigned_to', $user->id)->where('stage','won')->whereBetween('updated_at', [$from, $to])->sum('value');
                $tasksCompleted= Task::where('tenant_id', $tid)->where('assigned_to', $user->id)->where('status','completed')->whereBetween('updated_at', [$from, $to])->count();
                $followupsDone = Followup::where('created_by', $user->id)->where('status','done')->whereBetween('updated_at', [$from, $to])->count();
                $totalLeads    = Lead::where('assigned_to', $user->id)->whereBetween('created_at', [$from, $to])->count();
                $converted     = Lead::where('assigned_to', $user->id)->where('status','converted')->whereBetween('created_at', [$from, $to])->count();

                return [
                    'user'           => $user,
                    'leads_created'  => $leadsCreated,
                    'leads_assigned' => $leadsAssigned,
                    'deals_won'      => $dealsWon,
                    'deal_value'     => $dealValue,
                    'tasks_done'     => $tasksCompleted,
                    'followups_done' => $followupsDone,
                    'conversion_rate'=> $totalLeads > 0 ? round(($converted / $totalLeads) * 100, 1) : 0,
                    'score'          => ($leadsCreated * 2) + ($dealsWon * 10) + ($tasksCompleted) + ($followupsDone * 2),
                ];
            })
            ->sortByDesc('score')
            ->values();

        return view('tenant.reports.staff', compact(
            'staffList', 'from', 'to', 'request'
        ));
    }

    // ── Subscriptions report ──────────────────────────────────────
    public function subscriptions(Request $request): View
    {
        $this->requireViewAll();
        [$from, $to] = $this->dateRange($request);

        $kpis = [
            'active'   => ServiceSubscription::currentlyValid()->count(),
            'expiring' => ServiceSubscription::expiringSoon(auth()->user()->tenant->subscriptionReminderDays())->count(),
            'expired'  => ServiceSubscription::expired()->count(),
            'cancelled'=> ServiceSubscription::cancelled()->count(),
            'new'      => ServiceSubscription::whereBetween('created_at', [$from, $to])->count(),
        ];

        // Rough MRR estimate — active subscriptions' service rate, monthly
        // billing_cycle only (weekly/yearly aren't normalized here).
        $mrr = ServiceSubscription::currentlyValid()
            ->whereHas('service', fn ($q) => $q->where('billing_cycle', 'monthly'))
            ->join('services', 'services.id', '=', 'service_subscriptions.service_id')
            ->sum('services.rate');

        $byService = ServiceSubscription::selectRaw('service_id, COUNT(*) as count')
            ->groupBy('service_id')
            ->with('service:id,name')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $monthlyTrend = ServiceSubscription::where('created_at', '>=', now()->subMonths(11)->startOfMonth())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $expiringList = ServiceSubscription::with(['contact:id,name', 'service:id,name'])
            ->expiringSoon(auth()->user()->tenant->subscriptionReminderDays())
            ->orderBy('expires_at')
            ->limit(10)
            ->get();

        return view('tenant.reports.subscriptions', compact(
            'kpis', 'mrr', 'byService', 'monthlyTrend', 'expiringList', 'from', 'to', 'request'
        ));
    }

    // ── Appointments report ───────────────────────────────────────
    public function appointments(Request $request): View
    {
        $this->requireViewAll();
        [$from, $to] = $this->dateRange($request);

        $base = Appointment::whereBetween('starts_at', [$from, $to]);

        $kpis = [
            'total'     => (clone $base)->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
            'cancelled' => (clone $base)->where('status', 'cancelled')->count(),
            'no_show'   => (clone $base)->where('status', 'no_show')->count(),
            'upcoming'  => Appointment::upcoming()->count(),
        ];
        $finished = $kpis['completed'] + $kpis['cancelled'] + $kpis['no_show'];
        $noShowRate = $finished > 0 ? round(($kpis['no_show'] / $finished) * 100, 1) : 0;

        $byService = Appointment::whereBetween('starts_at', [$from, $to])
            ->selectRaw('service_id, COUNT(*) as count')
            ->groupBy('service_id')
            ->with('service:id,name')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $byStatus = Appointment::whereBetween('starts_at', [$from, $to])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()->keyBy('status');

        $dailyTrend = Appointment::whereBetween('starts_at', [$from, $to])
            ->selectRaw('DATE(starts_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return view('tenant.reports.appointments', compact(
            'kpis', 'noShowRate', 'byService', 'byStatus', 'dailyTrend', 'from', 'to', 'request'
        ));
    }

    // ── Tickets report ────────────────────────────────────────────
    public function tickets(Request $request): View
    {
        $this->requireViewAll();
        [$from, $to] = $this->dateRange($request);

        $base = Ticket::whereBetween('created_at', [$from, $to]);

        $kpis = [
            'total'        => (clone $base)->count(),
            'open'         => (clone $base)->openTickets()->count(),
            'resolved'     => (clone $base)->where('status', 'resolved')->count(),
            'closed'       => (clone $base)->where('status', 'closed')->count(),
            'sla_breached' => (clone $base)->whereNotNull('sla_notified_at')->count(),
        ];

        // Avg resolution time in hours, for tickets that have a resolved_at
        $avgResolutionHours = (clone $base)
            ->whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
            ->value('avg_hours');

        $byPriority = Ticket::whereBetween('created_at', [$from, $to])
            ->selectRaw('priority, COUNT(*) as count')
            ->groupBy('priority')
            ->get()->keyBy('priority');

        $byStatus = Ticket::whereBetween('created_at', [$from, $to])
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()->keyBy('status');

        $byStaff = Ticket::whereBetween('created_at', [$from, $to])
            ->whereNotNull('assigned_to')
            ->selectRaw('assigned_to, COUNT(*) as count')
            ->groupBy('assigned_to')
            ->with('assignee:id,name')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return view('tenant.reports.tickets', compact(
            'kpis', 'avgResolutionHours', 'byPriority', 'byStatus', 'byStaff', 'from', 'to', 'request'
        ));
    }

    // ── Time Tracking report ──────────────────────────────────────
    public function timeTracking(Request $request): View
    {
        $this->requireViewAll();
        [$from, $to] = $this->dateRange($request);
        $tid = $this->tenantId();

        $base = TimeEntry::whereBetween('started_at', [$from, $to])->whereNotNull('ended_at');

        $totalMinutes    = (clone $base)->sum('duration_minutes');
        $billableMinutes = (clone $base)->where('is_billable', true)->sum('duration_minutes');
        $invoicedMinutes = (clone $base)->where('is_billable', true)->where('is_invoiced', true)->sum('duration_minutes');
        $uninvoicedBillableMinutes = $billableMinutes - $invoicedMinutes;

        $kpis = [
            'total_hours'      => round($totalMinutes / 60, 1),
            'billable_hours'   => round($billableMinutes / 60, 1),
            'invoiced_hours'   => round($invoicedMinutes / 60, 1),
            'uninvoiced_hours' => round($uninvoicedBillableMinutes / 60, 1),
        ];

        $byStaff = User::withoutGlobalScopes()
            ->where('tenant_id', $tid)
            ->get()
            ->map(function ($user) use ($from, $to) {
                $entries = TimeEntry::where('user_id', $user->id)
                    ->whereBetween('started_at', [$from, $to])
                    ->whereNotNull('ended_at');

                $total    = (clone $entries)->sum('duration_minutes');
                $billable = (clone $entries)->where('is_billable', true)->sum('duration_minutes');

                return [
                    'user'           => $user,
                    'total_hours'    => round($total / 60, 1),
                    'billable_hours' => round($billable / 60, 1),
                ];
            })
            ->filter(fn ($row) => $row['total_hours'] > 0)
            ->sortByDesc('total_hours')
            ->values();

        $byService = TimeEntry::whereBetween('started_at', [$from, $to])
            ->whereNotNull('ended_at')
            ->whereNotNull('service_id')
            ->selectRaw('service_id, SUM(duration_minutes) as minutes')
            ->groupBy('service_id')
            ->with('service:id,name')
            ->orderByDesc('minutes')
            ->limit(10)
            ->get();

        return view('tenant.reports.time-tracking', compact(
            'kpis', 'byStaff', 'byService', 'from', 'to', 'request'
        ));
    }
}