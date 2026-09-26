<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Task;
use App\Models\TimeEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class TimeEntryController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    private function findEntry(int|string $id): TimeEntry
    {
        return TimeEntry::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $query = TimeEntry::with(['user', 'contact', 'task', 'service'])
            ->where('tenant_id', $this->tenantId())
            ->whereNotNull('ended_at')
            ->latest('started_at');

        if ($request->filled('contact_id')) {
            $query->where('contact_id', $request->contact_id);
        }
        if ($request->boolean('uninvoiced_only')) {
            $query->where('is_invoiced', false)->where('is_billable', true);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('started_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('started_at', '<=', $request->date_to);
        }

        $entries = $query->paginate(30)->withQueryString();

        $running = TimeEntry::with(['task', 'contact'])
            ->where('tenant_id', $this->tenantId())
            ->where('user_id', auth()->id())
            ->running()
            ->first();

        $contacts = Contact::where('tenant_id', $this->tenantId())->orderBy('name')->get(['id', 'name']);

        return view('tenant.time-entries.index', compact('entries', 'running', 'contacts'));
    }

    // ── Start a timer ─────────────────────────────────────────────
    // JSON for API/AJAX clients (Accept: application/json); browser form posts get
    // a redirect back to the page they came from with a flash message.
    public function start(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'task_id'    => ['nullable', 'integer', 'exists:tasks,id'],
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
        ]);

        // One running timer per staff member at a time — auto-stop any
        // existing one so hours never silently overlap.
        $existing = TimeEntry::where('tenant_id', $this->tenantId())
            ->where('user_id', auth()->id())
            ->running()
            ->first();
        $replaced = $existing?->isRunning();
        $existing?->stop();

        $service = $request->filled('service_id')
            ? Service::where('tenant_id', $this->tenantId())->find($request->service_id)
            : null;

        $entry = TimeEntry::create([
            'tenant_id'  => $this->tenantId(),
            'user_id'    => auth()->id(),
            'task_id'    => $request->task_id,
            'contact_id' => $request->contact_id,
            'service_id' => $request->service_id,
            'hourly_rate'=> $service?->rate,
            'started_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'id' => $entry->id, 'started_at' => $entry->started_at->toIso8601String()]);
        }

        return back()->with('success', $replaced ? 'Timer started — your previous timer was stopped.' : 'Timer started.');
    }

    // ── Stop a timer ──────────────────────────────────────────────
    public function stop(Request $request, int|string $id): JsonResponse|RedirectResponse
    {
        $entry = $this->findEntry($id);

        // A timer belongs to the person running it; workspace admins may stop any.
        abort_unless(
            $entry->user_id === auth()->id() || auth()->user()->user_type === 'tenant_admin',
            403,
            'You can only stop your own timer.'
        );

        $wasRunning = $entry->isRunning();
        $entry->stop();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'duration_minutes' => $entry->duration_minutes]);
        }

        return back()->with(
            $wasRunning ? 'success' : 'error',
            $wasRunning ? "Timer stopped — {$entry->duration_minutes} min logged." : 'That timer was already stopped.'
        );
    }

    // ── Manual entry ──────────────────────────────────────────────
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'task_id'     => ['nullable', 'integer', 'exists:tasks,id'],
            'contact_id'  => ['nullable', 'integer', 'exists:contacts,id'],
            'service_id'  => ['nullable', 'integer', 'exists:services,id'],
            'date'        => ['required', 'date'],
            'hours'       => ['required', 'numeric', 'min:0.01', 'max:24'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'is_billable' => ['nullable', 'boolean'],
            'notes'       => ['nullable', 'string', 'max:1000'],
        ]);

        $minutes  = (int) round($data['hours'] * 60);
        $startsAt = Carbon::parse($data['date'])->startOfDay();

        $entry = TimeEntry::create([
            'tenant_id'         => $this->tenantId(),
            'user_id'           => auth()->id(),
            'task_id'           => $data['task_id'] ?? null,
            'contact_id'        => $data['contact_id'] ?? null,
            'service_id'        => $data['service_id'] ?? null,
            'started_at'        => $startsAt,
            'ended_at'          => $startsAt->copy()->addMinutes($minutes),
            'duration_minutes'  => $minutes,
            'hourly_rate'       => $data['hourly_rate'] ?? null,
            'is_billable'       => $request->boolean('is_billable', true),
            'notes'             => $data['notes'] ?? null,
        ]);

        $entry->task?->recalculateActualHours();

        return back()->with('success', 'Time entry logged.');
    }

    // ── Update / Destroy ──────────────────────────────────────────
    public function update(Request $request, int|string $id): RedirectResponse
    {
        $entry = $this->findEntry($id);

        $data = $request->validate([
            'hours'       => ['required', 'numeric', 'min:0.01', 'max:24'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'is_billable' => ['nullable', 'boolean'],
            'notes'       => ['nullable', 'string', 'max:1000'],
        ]);

        $minutes = (int) round($data['hours'] * 60);

        $entry->update([
            'duration_minutes' => $minutes,
            'ended_at'         => $entry->started_at->copy()->addMinutes($minutes),
            'hourly_rate'      => $data['hourly_rate'] ?? $entry->hourly_rate,
            'is_billable'      => $request->boolean('is_billable', true),
            'notes'            => $data['notes'] ?? $entry->notes,
        ]);

        $entry->task?->recalculateActualHours();

        return back()->with('success', 'Time entry updated.');
    }

    public function destroy(int|string $id): RedirectResponse
    {
        $entry = $this->findEntry($id);
        $task  = $entry->task;
        $entry->delete();
        $task?->recalculateActualHours();

        return back()->with('success', 'Time entry deleted.');
    }

    // ── Convert selected billable, uninvoiced entries (one Contact) into
    // a single draft Invoice — one line per Task (or Service, if no task),
    // hours summed per group. ─────────────────────────────────────────
    public function convertToInvoice(Request $request): RedirectResponse
    {
        $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer']]);

        $entries = TimeEntry::with(['task', 'service'])
            ->where('tenant_id', $this->tenantId())
            ->whereIn('id', $request->ids)
            ->billableUninvoiced()
            ->get();

        if ($entries->isEmpty()) {
            return back()->with('error', 'No billable, uninvoiced entries selected.');
        }

        $contactIds = $entries->pluck('contact_id')->unique()->filter();
        if ($contactIds->count() !== 1) {
            return back()->with('error', 'Select entries for a single contact only.');
        }

        $groups = $entries->groupBy(fn ($e) => $e->task_id ? 'task-' . $e->task_id : ($e->service_id ? 'service-' . $e->service_id : 'time'));

        $items = [];
        foreach ($groups as $group) {
            $first    = $group->first();
            $hours    = round($group->sum(fn ($e) => $e->duration_minutes ?? 0) / 60, 2);
            $rate     = (float) ($first->hourly_rate ?? 0);
            $label    = $first->task?->title ?? $first->service?->name ?? 'Time';

            $items[] = [
                'service_id'  => $first->service_id,
                'description' => "{$label} — {$hours} hrs",
                'quantity'    => $hours,
                'rate'        => $rate,
                'tax_percent' => 18,
                'amount'      => round($hours * $rate, 2),
            ];
        }

        $totals = Invoice::calculateTotals($items, 0, 18);

        $invoice = Invoice::create(array_merge($totals, [
            'tenant_id'   => $this->tenantId(),
            'contact_id'  => $contactIds->first(),
            'number'      => Invoice::generateNumber(),
            'date'        => now()->format('Y-m-d'),
            'due_date'    => now()->addDays(7)->format('Y-m-d'),
            'items'       => $items,
            'notes'       => 'Generated from tracked time entries.',
            'status'      => 'draft',
            'paid_amount' => 0,
            'created_by'  => auth()->id(),
        ]));

        TimeEntry::whereIn('id', $entries->pluck('id'))->update([
            'is_invoiced' => true,
            'invoice_id'  => $invoice->id,
        ]);

        return redirect()->route('tenant.invoices.show', $invoice->id)
            ->with('success', "Draft invoice {$invoice->number} created from " . $entries->count() . ' time entries.');
    }
}
