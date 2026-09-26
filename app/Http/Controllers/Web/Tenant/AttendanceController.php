<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Models\Attendance;
use App\Models\Staff;
use App\Http\Requests\AttendanceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Http\Controllers\Controller;

class AttendanceController extends Controller
{
    private function tenantSlug(): string
    {
        return auth()->user()->tenant->subdomain;
    }

    // ── Index : Monthly View ──────────────────────────────────────

    public function index(Request $request)
    {
        $year  = (int) $request->get('year',  now()->year);
        $month = (int) $request->get('month', now()->month);

        $query = Attendance::with('staff')
            ->forMonth($year, $month);

        // Staff filter
        if ($request->filled('staff_id')) {
            $query->forStaff($request->staff_id);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        $attendances = $query->orderBy('date', 'desc')
            ->orderBy('staff_id')
            ->paginate(30)
            ->withQueryString();

        // Summary counts
        $summary = Attendance::forMonth($year, $month)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $staffList = Staff::with('user')->get();
        $total     = Attendance::forMonth($year, $month)->count();

        return view('tenant.attendances.index', compact(
            'attendances',
            'staffList',
            'summary',
            'year',
            'month',
            'total'
        ));
    }

    // ── Create / Store (Manual) ───────────────────────────────────

    public function create()
    {
        $staffList  = Staff::with('user')->get();
        $attendance = null;
        return view('tenant.attendances.create', compact('staffList', 'attendance'));
    }

    public function store(AttendanceRequest $request)
    {
        $data = $request->validated();

        $data['clock_in']  = ! empty($data['clock_in'])
            ? Carbon::parse($data['date'] . ' ' . $data['clock_in'])
            : null;
        $data['clock_out'] = ! empty($data['clock_out'])
            ? Carbon::parse($data['date'] . ' ' . $data['clock_out'])
            : null;

        Attendance::create($data);

        return redirect()
            ->route('tenant.attendances.index', ['tenant' => $this->tenantSlug()])
            ->with('success', 'Attendance add ho gaya!');
    }

    // ── Edit / Update ─────────────────────────────────────────────

    public function edit(Attendance $attendance)
    {
        $staffList = Staff::with('user')->get();
        return view('tenant.attendances.create', compact('attendance', 'staffList'));
    }

    public function update(AttendanceRequest $request, Attendance $attendance)
    {
        $data = $request->validated();

        $data['clock_in']  = ! empty($data['clock_in'])
            ? Carbon::parse($data['date'] . ' ' . $data['clock_in'])
            : null;
        $data['clock_out'] = ! empty($data['clock_out'])
            ? Carbon::parse($data['date'] . ' ' . $data['clock_out'])
            : null;

        $attendance->update($data);

        return redirect()
            ->route('tenant.attendances.index', ['tenant' => $this->tenantSlug()])
            ->with('success', 'Attendance update ho gaya!');
    }

    public function destroy(Attendance $attendance)
    {
        $attendance->delete();
        return back()->with('success', 'Attendance delete ho gaya!');
    }

    // ── Bulk ──────────────────────────────────────────────────────

    public function bulk(Request $request)
    {
        $date      = $request->get('date', today()->toDateString());
        $staffList = Staff::with('user')->get();

        $existing = Attendance::forDate($date)
            ->get()
            ->keyBy('staff_id');

        return view('tenant.attendances.bulk', compact('staffList', 'date', 'existing'));
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'date'                    => 'required|date',
            'attendances'             => 'required|array',
            'attendances.*.status'    => 'required|in:present,absent,half_day,holiday,leave',
            'attendances.*.clock_in'  => 'nullable|date_format:H:i',
            'attendances.*.clock_out' => 'nullable|date_format:H:i',
        ]);

        $date = $request->date;

        DB::transaction(function () use ($request, $date) {
            foreach ($request->attendances as $staffId => $row) {
                Attendance::updateOrCreate(
                    ['staff_id' => $staffId, 'date' => $date],
                    [
                        'status'   => $row['status'],
                        'notes'    => $row['notes'] ?? null,
                        'clock_in' => ! empty($row['clock_in'])
                            ? Carbon::parse($date . ' ' . $row['clock_in']) : null,
                        'clock_out' => ! empty($row['clock_out'])
                            ? Carbon::parse($date . ' ' . $row['clock_out']) : null,
                    ]
                );
            }
        });

        return redirect()
            ->route('tenant.attendances.index', ['tenant' => $this->tenantSlug()])
            ->with('success', "Bulk attendance {$date} ke liye save ho gaya!");
    }

    // ── Clock In / Out ────────────────────────────────────────────

    public function clockView()
    {
        $staffList = Staff::with('user')->get();

        $today = Attendance::forDate(today())
            ->with('staff')
            ->get()
            ->keyBy('staff_id');

        return view('tenant.attendances.clock', compact('staffList', 'today'));
    }

    // The staff member acting is always the authenticated user's own staff
    // profile. A posted staff_id is never trusted — it is optional and, when
    // present, must match — so nobody can clock another person in or out by
    // editing a hidden field.
    private function actingStaff(Request $request): Staff
    {
        $request->validate(['staff_id' => ['nullable', 'integer']]);

        $staff = auth()->user()->staff;

        abort_unless($staff, 403, 'Your account is not linked to a staff profile.');

        abort_if(
            $request->filled('staff_id') && (int) $request->staff_id !== $staff->id,
            403,
            'You can only clock in or out for yourself.'
        );

        return $staff;
    }

    public function clockIn(Request $request)
    {
        $staff = $this->actingStaff($request);

        // createOrFirst: (staff_id, date) is unique, so two simultaneous
        // requests resolve to the same row instead of one of them 500ing.
        $attendance = Attendance::createOrFirst(
            ['staff_id' => $staff->id, 'date' => today()->toDateString()],
            ['status'   => 'present']
        );

        // Atomic guard: only the request that flips clock_in from NULL wins,
        // so a double-click cannot overwrite the first clock-in time.
        $clockedIn = Attendance::whereKey($attendance->id)
            ->whereNull('clock_in')
            ->update(['clock_in' => now()]);

        if (! $clockedIn) {
            return back()->with('error', 'Staff already clock in hai!');
        }

        // ✅ att_id aur staff_id redirect mein pass karo
        // JS isko read karke monitoring start karega
        return redirect()->route('tenant.attendances.clock', [
            'tenant'   => $this->tenantSlug(),
            'att_id'   => $attendance->id,
            'staff_id' => $staff->id,
        ])->with('success', 'Clock In ho gaya! Screen monitoring shuru...');
    }

    public function clockOut(Request $request)
    {
        $staff = $this->actingStaff($request);

        $attendance = Attendance::forDate(today())
            ->forStaff($staff->id)
            ->first();

        if (! $attendance?->clock_in) {
            return back()->with('error', 'Pehle clock in karo!');
        }

        $clockedOut = Attendance::whereKey($attendance->id)
            ->whereNull('clock_out')
            ->update(['clock_out' => now()]);

        if (! $clockedOut) {
            return back()->with('error', 'Already clock out ho chuka hai!');
        }

        return redirect()->route('tenant.attendances.clock', [
            'tenant' => $this->tenantSlug(),
        ])
            ->with('success', "Clock Out! Kaam kiya: {$attendance->fresh()->worked_hours} hrs ✅")
            ->with('clocked_out', true); // JS localStorage clear karega
    }
}
