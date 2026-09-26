<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Exports\DealsExport;
use App\Helpers\ViewScope;
use App\Http\Controllers\Controller;
use App\Http\Requests\DealRequest;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\User;
use App\Services\DealService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class DealController extends Controller
{
    // ── Find deal — tenant scope ──────────────────────────────────
    private function findDeal(int|string $id): Deal
    {
        return Deal::where('id', $id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->firstOrFail();
    }

    // ── Staff list — sirf current tenant ─────────────────────────
    private function getStaffList()
    {
        return User::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    // ── Shared filtered query (index page + export reuse this) ────
    public static function filteredQuery(int $tenantId, array $filters, User $user): \Illuminate\Database\Eloquent\Builder
    {
        $query = Deal::query()->where('tenant_id', $tenantId);
        $query = ViewScope::apply($query, 'deals', $user);

        if (!empty($filters['search']))      $query->search($filters['search']);
        if (!empty($filters['stage']))       $query->stage($filters['stage']);
        if (!empty($filters['assigned_to'])) $query->assignedTo($filters['assigned_to']);
        if (!empty($filters['value_min']))   $query->where('value', '>=', $filters['value_min']);
        if (!empty($filters['value_max']))   $query->where('value', '<=', $filters['value_max']);

        return $query;
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $user     = auth()->user();
        $filters  = $request->all();

        $query = self::filteredQuery($tenantId, $filters, $user)
            ->with(['contact', 'assignedTo'])
            ->withCount(['tasks', 'followups']);

        $sort    = $request->get('sort', 'created_at');
        $dir     = $request->get('dir', 'desc');

        $allowed = [
            'title',
            'value',
            'stage',
            'created_at',
            'expected_close_date'
        ];

        if (in_array($sort, $allowed)) {
            $query->orderBy($sort, $dir === 'asc' ? 'asc' : 'desc');
        }

        // ── Kanban data ─────────────────────────────
        // Cap cards per column so a stage with hundreds/thousands of deals
        // doesn't render them all into the DOM at once (huge scroll, slow
        // drag & drop, sluggish page). Real per-stage counts/totals are
        // fetched separately (unaffected by the cap) so headers stay accurate
        // and columns can offer a "view all in list" link when truncated.
        $kanbanLimit = 30;

        $kanbanStageCounts = self::filteredQuery($tenantId, $filters, $user)
            ->selectRaw('stage, COUNT(*) as count, SUM(value) as total')
            ->groupBy('stage')
            ->get()
            ->keyBy('stage');

        $kanbanDeals = collect();
        foreach (array_keys(Deal::stages()) as $slug) {
            $kanbanDeals[$slug] = (clone $query)
                ->where('stage', $slug)
                ->limit($kanbanLimit)
                ->get();
        }

        // ── List view data ─────────────────────────
        $deals = (clone $query)
            ->paginate(20)
            ->withQueryString();

        // ── Summary ────────────────────────────────
        $stageSummary = ViewScope::apply(Deal::where('tenant_id', auth()->user()->tenant_id), 'deals', auth()->user())
            ->selectRaw('stage, COUNT(*) as count, SUM(value) as total')
            ->groupBy('stage')
            ->get()
            ->keyBy('stage');

        $view      = $request->get('view', 'kanban');
        $staffList = $this->getStaffList();
        $stages    = Deal::stages();


        return view('tenant.deals.index', compact(
            'deals',
            'kanbanDeals',
            'kanbanStageCounts',
            'kanbanLimit',
            'stageSummary',
            'staffList',
            'stages',
            'view'
        ));
    }
    // ── Export (respects current index filters) ───────────────────
    public function export(Request $request)
    {
        return Excel::download(
            new DealsExport(auth()->user()->tenant_id, $request->query(), auth()->user()),
            'deals_export_' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

    // ── Create ────────────────────────────────────────────────────
    public function create(Request $request): View
    {
        $this->authorize('create', Deal::class);

        $tenantId  = auth()->user()->tenant_id;
        $staffList = $this->getStaffList();
        $contacts  = Contact::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name', 'company']);
        $leads     = Lead::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $stages    = Deal::stages();

        // Pre-fill contact/lead if coming from their pages
        $contact = $request->filled('contact_id')
            ? Contact::where('id', $request->contact_id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->first()
            : null;

        $lead = $request->filled('lead_id')
            ? Lead::where('id', $request->lead_id)
            ->where('tenant_id', auth()->user()->tenant_id)
            ->first()
            : null;

        return view('tenant.deals.create', compact(
            'staffList',
            'contacts',
            'leads',
            'stages',
            'contact',
            'lead'
        ));
    }

    // ── Store ─────────────────────────────────────────────────────
    public function store(DealRequest $request): RedirectResponse
    {
        $this->authorize('create', Deal::class);

        $deal = DealService::create($request->validated(), auth()->user()->tenant_id, auth()->id());

        return redirect()
            ->route('tenant.deals.show', $deal->id)
            ->with('success', "Deal '{$deal->title}' created successfully.");
    }

    // ── Show ──────────────────────────────────────────────────────
    public function show(int|string $id): View
    {
        $deal = $this->findDeal($id);
        $this->authorize('view', $deal);

        $deal->load([
            'contact',
            'lead',
            'assignedTo',
            'createdBy',
            'tasks.assignedTo',
            'followups.assignedTo',
            'quotations',
        ]);

        $staffList = $this->getStaffList();
        $stages    = Deal::stages();

        return view('tenant.deals.show', compact('deal', 'staffList', 'stages'));
    }

    // ── Edit ──────────────────────────────────────────────────────
    public function edit(int|string $id): View
    {
        $tenantId  = auth()->user()->tenant_id;
        $deal      = $this->findDeal($id);
        $this->authorize('modify', $deal);
        $staffList = $this->getStaffList();
        $contacts  = Contact::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name', 'company']);
        $leads     = Lead::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name']);
        $stages    = Deal::stages();

        return view('tenant.deals.edit', compact(
            'deal',
            'staffList',
            'contacts',
            'leads',
            'stages'
        ));
    }

    // ── Update ────────────────────────────────────────────────────
    public function update(DealRequest $request, int|string $id): RedirectResponse
    {
        $deal = $this->findDeal($id);
        $this->authorize('modify', $deal);

        DealService::update($deal, $request->validated());

        return redirect()
            ->route('tenant.deals.show', $deal->id)
            ->with('success', 'Deal updated successfully.');
    }

    // ── Destroy ───────────────────────────────────────────────────
    public function destroy(int|string $id): RedirectResponse
    {
        $deal = $this->findDeal($id);
        $this->authorize('delete', $deal);
        $title = $deal->title;
        $deal->delete();

        return redirect()
            ->route('tenant.deals.index')
            ->with('success', "Deal '{$title}' deleted.");
    }

    // ── Update stage (Kanban drag or quick action) ────────────────
    public function updateStage(Request $request, int|string $id): RedirectResponse
    {
        $request->validate([
            'stage'       => ['required', 'in:new,proposal,negotiation,won,lost'],
            'lost_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $deal = $this->findDeal($id);
        $this->authorize('modify', $deal);

        DealService::updateStage($deal, $request->stage, $request->lost_reason);

        return back()->with('success', 'Deal stage updated.');
    }

    // ── Mark Won ──────────────────────────────────────────────────
    public function markWon(int|string $id): RedirectResponse
    {
        $deal = $this->findDeal($id);
        $this->authorize('modify', $deal);

        DealService::markWon($deal);

        return back()->with('success', "Deal marked as Won! 🎉");
    }

    // ── Mark Lost ─────────────────────────────────────────────────
    public function markLost(Request $request, int|string $id): RedirectResponse
    {
        $request->validate([
            'lost_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $deal = $this->findDeal($id);
        $this->authorize('modify', $deal);

        DealService::markLost($deal, $request->lost_reason);

        return back()->with('success', 'Deal marked as lost.');
    }

    // ── Pipeline Analytics ────────────────────────────────────────
    public function pipelineAnalytics(): View
    {
        abort_unless(auth()->user()->can('deals.view_all') || auth()->user()->user_type === 'superadmin', 403);

        $tid = auth()->user()->tenant_id;

        // Open pipeline
        $openDeals       = Deal::where('tenant_id', $tid)->open()->get();
        $totalPipeline   = $openDeals->sum('value');
        $weightedForecast = $openDeals->sum(fn($d) => $d->value * ($d->probability / 100));

        // Win/loss this year
        $wonCount  = Deal::where('tenant_id', $tid)->won()->whereYear('actual_close_date', now()->year)->count();
        $lostCount = Deal::where('tenant_id', $tid)->lost()->whereYear('actual_close_date', now()->year)->count();
        $winRate   = ($wonCount + $lostCount) > 0
            ? round(($wonCount / ($wonCount + $lostCount)) * 100)
            : 0;

        // Avg deal size (won all-time)
        $avgDealSize = (float) (Deal::where('tenant_id', $tid)->won()->avg('value') ?? 0);

        // Avg days to close (won with actual_close_date)
        $avgDaysToClose = Deal::where('tenant_id', $tid)
            ->won()
            ->whereNotNull('actual_close_date')
            ->get()
            ->avg(fn($d) => $d->created_at->diffInDays($d->actual_close_date)) ?? 0;

        // Stage breakdown
        $stageData = Deal::where('tenant_id', $tid)
            ->selectRaw('stage, COUNT(*) as count, SUM(value) as total')
            ->groupBy('stage')
            ->get()
            ->keyBy('stage');

        // Monthly closed revenue + win/loss counts — last 6 months.
        // Single query + PHP-side grouping instead of 3 queries × 6 months —
        // also keeps this portable across MySQL/SQLite (tests use SQLite),
        // which a raw DATE_FORMAT() group-by would not.
        $closedByMonth = Deal::where('tenant_id', $tid)
            ->whereIn('stage', ['won', 'lost'])
            ->whereNotNull('actual_close_date')
            ->where('actual_close_date', '>=', now()->subMonths(5)->startOfMonth())
            ->get(['stage', 'value', 'actual_close_date'])
            ->groupBy(fn ($d) => $d->actual_close_date->format('Y-m'));

        $monthlyRevenue = collect();
        $winLossData    = collect();
        for ($i = 5; $i >= 0; $i--) {
            $m      = now()->subMonths($i);
            $bucket = $closedByMonth->get($m->format('Y-m'), collect());
            $rev    = $bucket->where('stage', 'won')->sum('value');
            $w      = $bucket->where('stage', 'won')->count();
            $l      = $bucket->where('stage', 'lost')->count();

            $monthlyRevenue->push(['month' => $m->format('M Y'), 'short' => $m->format('M'), 'value' => (float) $rev]);
            $winLossData->push(['month' => $m->format('M'), 'won' => $w, 'lost' => $l]);
        }

        // Deals closing this month
        $closingThisMonth = Deal::where('tenant_id', $tid)
            ->open()
            ->whereMonth('expected_close_date', now()->month)
            ->whereYear('expected_close_date', now()->year)
            ->with(['contact', 'assignedTo'])
            ->orderBy('expected_close_date')
            ->get();

        // Top 5 open deals by value
        $topDeals = Deal::where('tenant_id', $tid)
            ->open()
            ->with(['contact', 'assignedTo'])
            ->orderByDesc('value')
            ->limit(5)
            ->get();

        // Funnel conversion (stage counts in pipeline order, excluding won/lost)
        $pipelineStages = ['new', 'proposal', 'negotiation'];
        $funnelData     = collect($pipelineStages)->map(function ($stage) use ($stageData) {
            $row = $stageData->get($stage);
            return ['stage' => $stage, 'count' => $row->count ?? 0, 'total' => (float) ($row->total ?? 0)];
        });

        $stages = Deal::stages();

        return view('tenant.deals.pipeline-analytics', compact(
            'totalPipeline', 'weightedForecast', 'winRate', 'avgDealSize',
            'avgDaysToClose', 'stageData', 'monthlyRevenue', 'winLossData',
            'closingThisMonth', 'topDeals', 'funnelData',
            'stages', 'wonCount', 'lostCount'
        ));
    }
}
