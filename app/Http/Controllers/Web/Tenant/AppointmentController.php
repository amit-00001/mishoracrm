<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentAttachment;
use App\Models\Contact;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Services\AppointmentJobService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    private function findAppointment(int|string $id): Appointment
    {
        return Appointment::where('id', $id)
            ->where('tenant_id', $this->tenantId())
            ->firstOrFail();
    }

    private function staffList()
    {
        return User::where('tenant_id', $this->tenantId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    // A job can only be started/completed by the technician it's assigned
    // to, or a tenant_admin — same lightweight ad-hoc check style already
    // used elsewhere in this controller (no dedicated Policy class exists
    // for Appointment).
    private function assertCanManageJob(Appointment $appointment): void
    {
        $user = auth()->user();
        abort_unless(
            $user->user_type === 'tenant_admin' || $appointment->assigned_to === $user->id,
            403,
            'Only the assigned technician or a tenant admin can manage this job.'
        );
    }

    // ── Index ─────────────────────────────────────────────────────
    public function index(Request $request): View
    {
        $query = Appointment::with(['contact', 'service'])
            ->where('tenant_id', $this->tenantId())
            ->latest('starts_at');

        $status = $request->get('status', 'upcoming');

        match ($status) {
            'upcoming'  => $query->upcoming(),
            'today'     => $query->today()->active(),
            'completed' => $query->where('status', 'completed'),
            'cancelled' => $query->cancelled(),
            default     => null,
        };

        $appointments = $query->paginate(20)->withQueryString();

        $base = Appointment::where('tenant_id', $this->tenantId());
        $counts = [
            'upcoming'  => (clone $base)->upcoming()->count(),
            'today'     => (clone $base)->today()->active()->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
            'cancelled' => (clone $base)->cancelled()->count(),
        ];

        $tenant = auth()->user()->tenant;

        return view('tenant.appointments.index', compact('appointments', 'counts', 'status', 'tenant'));
    }

    // ── Create (manual booking by staff) ─────────────────────────────
    public function create(): View
    {
        $contacts = Contact::where('tenant_id', $this->tenantId())->orderBy('name')->get(['id', 'name', 'company', 'phone']);
        $services = Service::where('tenant_id', $this->tenantId())->active()->orderBy('name')->get(['id', 'name', 'rate']);
        $staffList = $this->staffList();
        $tenant   = auth()->user()->tenant;

        return view('tenant.appointments.create', compact('contacts', 'services', 'staffList', 'tenant'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'contact_id' => ['required', 'integer', 'exists:contacts,id'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'service_address' => ['nullable', 'string', 'max:1000'],
            'date'       => ['required', 'date'],
            'time'       => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:5'],
            'notes'      => ['nullable', 'string', 'max:1000'],
        ]);

        $startsAt = Carbon::parse($data['date'] . ' ' . $data['time']);

        $appointment = Appointment::create([
            'tenant_id'  => $this->tenantId(),
            'contact_id' => $data['contact_id'],
            'service_id' => $data['service_id'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'service_address' => $data['service_address'] ?? null,
            'starts_at'  => $startsAt,
            'ends_at'    => $startsAt->copy()->addMinutes((int) $data['duration_minutes']),
            'status'     => 'confirmed',
            'source'     => 'manual',
            'notes'      => $data['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        AppointmentJobService::sendBookingConfirmation($appointment);

        return redirect()->route('tenant.appointments.show', $appointment->id)
            ->with('success', 'Appointment booked.');
    }

    // ── Show — the job detail page ───────────────────────────────────
    public function show(int|string $id): View
    {
        $appointment = $this->findAppointment($id);
        $appointment->load(['contact', 'service', 'assignedTo', 'createdBy', 'attachments', 'invoice']);

        $products = Product::where('tenant_id', $this->tenantId())->active()->orderBy('name')->get(['id', 'name', 'rate', 'unit', 'current_stock']);

        return view('tenant.appointments.show', compact('appointment', 'products'));
    }

    // ── Edit — reassign technician / address / service before it starts ──
    public function edit(int|string $id): View
    {
        $appointment = $this->findAppointment($id);

        $contacts  = Contact::where('tenant_id', $this->tenantId())->orderBy('name')->get(['id', 'name', 'company', 'phone']);
        $services  = Service::where('tenant_id', $this->tenantId())->active()->orderBy('name')->get(['id', 'name', 'rate']);
        $staffList = $this->staffList();

        return view('tenant.appointments.edit', compact('appointment', 'contacts', 'services', 'staffList'));
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        $appointment = $this->findAppointment($id);

        $data = $request->validate([
            'contact_id'       => ['required', 'integer', 'exists:contacts,id'],
            'service_id'       => ['nullable', 'integer', 'exists:services,id'],
            'assigned_to'      => ['nullable', 'integer', 'exists:users,id'],
            'service_address'  => ['nullable', 'string', 'max:1000'],
            'date'             => ['required', 'date'],
            'time'             => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:5'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ]);

        $startsAt = Carbon::parse($data['date'] . ' ' . $data['time']);

        $appointment->update([
            'contact_id'       => $data['contact_id'],
            'service_id'       => $data['service_id'] ?? null,
            'assigned_to'      => $data['assigned_to'] ?? null,
            'service_address'  => $data['service_address'] ?? null,
            'starts_at'        => $startsAt,
            'ends_at'          => $startsAt->copy()->addMinutes((int) $data['duration_minutes']),
            'notes'            => $data['notes'] ?? null,
        ]);

        return redirect()->route('tenant.appointments.show', $appointment->id)
            ->with('success', 'Appointment updated.');
    }

    // ── Job workflow: Start / Complete ────────────────────────────────
    public function startWork(Request $request, int|string $id): RedirectResponse
    {
        $appointment = $this->findAppointment($id);
        $this->assertCanManageJob($appointment);

        if (!$appointment->canStartWork()) {
            return back()->with('error', 'Only a booked/confirmed appointment can be started.');
        }

        $request->validate(['lat' => ['nullable', 'numeric'], 'lng' => ['nullable', 'numeric']]);

        AppointmentJobService::startWork($appointment, $request->input('lat'), $request->input('lng'));

        return redirect()->route('tenant.appointments.show', $appointment->id)
            ->with('success', 'Work started.');
    }

    public function completeWork(Request $request, int|string $id): RedirectResponse
    {
        $appointment = $this->findAppointment($id);
        $this->assertCanManageJob($appointment);

        if (!$appointment->canCompleteWork()) {
            return back()->with('error', 'Only an in-progress job can be completed.');
        }

        $data = $request->validate([
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'materials'                  => ['nullable', 'array'],
            'materials.*.product_id'     => ['nullable', 'integer', 'exists:products,id'],
            'materials.*.name'           => ['required_with:materials', 'string', 'max:255'],
            'materials.*.quantity'       => ['required_with:materials', 'numeric', 'min:0.01'],
            'materials.*.rate'           => ['nullable', 'numeric', 'min:0'],
        ]);

        AppointmentJobService::completeWork($appointment, $data['materials'] ?? [], $data['lat'] ?? null, $data['lng'] ?? null);

        return redirect()->route('tenant.appointments.show', $appointment->id)
            ->with('success', 'Job marked completed — stock updated for any materials used.');
    }

    // ── Before/after photos ───────────────────────────────────────────
    public function uploadAttachment(Request $request, int|string $id): RedirectResponse
    {
        $appointment = $this->findAppointment($id);

        $request->validate([
            'attachments'   => ['required', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp'],
            'stage'         => ['required', 'in:before,after'],
        ]);

        $tenantId = $this->tenantId();

        foreach ($request->file('attachments') as $file) {
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path     = $file->storeAs("appointments/{$tenantId}/{$appointment->id}", $filename, 'public');

            AppointmentAttachment::create([
                'tenant_id'      => $tenantId,
                'appointment_id' => $appointment->id,
                'uploaded_by'    => auth()->id(),
                'path'           => $path,
                'original_name'  => $file->getClientOriginalName(),
                'mime_type'      => $file->getMimeType(),
                'file_size'      => $file->getSize(),
                'stage'          => $request->stage,
            ]);
        }

        return back()->with('success', 'Photo(s) uploaded.');
    }

    public function deleteAttachment(int|string $id, AppointmentAttachment $attachmentId): RedirectResponse
    {
        $appointment = $this->findAppointment($id);
        abort_unless($attachmentId->appointment_id === $appointment->id, 404);

        Storage::disk('public')->delete($attachmentId->path);
        $attachmentId->delete();

        return back()->with('success', 'Photo deleted.');
    }

    // ── Convert a completed, signed-off job to an Invoice ────────────
    public function convertToInvoice(int|string $id): RedirectResponse
    {
        $appointment = $this->findAppointment($id);

        if ($appointment->status !== 'completed') {
            return back()->with('error', 'Only a completed job can be converted to an invoice.');
        }

        try {
            $invoice = AppointmentJobService::convertToInvoice($appointment);
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('tenant.invoices.show', $invoice->id)
            ->with('success', "Invoice {$invoice->number} created from this job.");
    }

    // ── Status actions ────────────────────────────────────────────
    public function updateStatus(Request $request, int|string $id): RedirectResponse
    {
        $request->validate(['status' => ['required', 'in:booked,confirmed,in_progress,completed,cancelled,no_show']]);

        $appointment = $this->findAppointment($id);
        $appointment->update(['status' => $request->status]);

        return back()->with('success', 'Appointment marked ' . (Appointment::statuses()[$request->status] ?? $request->status) . '.');
    }

    public function destroy(int|string $id): RedirectResponse
    {
        $this->findAppointment($id)->delete();

        return redirect()->route('tenant.appointments.index')
            ->with('success', 'Appointment deleted.');
    }

    // ── JSON — available slots for the manual-booking form ──────────
    public function slots(Request $request)
    {
        $request->validate(['date' => ['required', 'date'], 'duration_minutes' => ['required', 'integer', 'min:5']]);

        $tenant = auth()->user()->tenant;
        $slots  = Appointment::availableSlots($tenant, Carbon::parse($request->date), (int) $request->duration_minutes);

        return response()->json(['slots' => $slots]);
    }

    // ── Booking Settings ──────────────────────────────────────────
    public function settings(): View
    {
        $tenant = auth()->user()->tenant;
        $settings = $tenant->bookingSettings();
        $notifyCustomers = $tenant->wantsAppointmentNotifications();
        $bookableServices = Service::where('tenant_id', $this->tenantId())->active()->orderBy('name')->get(['id', 'name', 'is_bookable']);

        // Online booking only lists services that are individually ticked as bookable.
        $bookableCount = $bookableServices->where('is_bookable', true)->count();

        return view('tenant.appointments.settings', compact('tenant', 'settings', 'bookableServices', 'bookableCount', 'notifyCustomers'));
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled'               => ['nullable', 'boolean'],
            'notify_customers'      => ['nullable', 'boolean'],
            'slot_duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
            'capacity_per_slot'     => ['required', 'integer', 'min:1', 'max:100'],
            'advance_booking_days'  => ['required', 'integer', 'min:1', 'max:365'],
            'hours'                 => ['required', 'array'],
            'bookable_service_ids'  => ['nullable', 'array'],
            'bookable_service_ids.*'=> ['integer', 'exists:services,id'],
        ]);

        $tenant   = auth()->user()->tenant;
        $settings = $tenant->settings ?? [];

        $hours = [];
        foreach (['mon','tue','wed','thu','fri','sat','sun'] as $day) {
            $hours[$day] = [
                'closed' => empty($data['hours'][$day]['closed']) ? false : true,
                'open'   => $data['hours'][$day]['open']  ?? '09:00',
                'close'  => $data['hours'][$day]['close'] ?? '18:00',
            ];
        }

        $settings['booking'] = [
            'enabled'               => $request->boolean('enabled'),
            'slot_duration_minutes' => (int) $data['slot_duration_minutes'],
            'capacity_per_slot'     => (int) $data['capacity_per_slot'],
            'advance_booking_days'  => (int) $data['advance_booking_days'],
            'hours'                 => $hours,
        ];

        $settings['preferences'] ??= [];
        $settings['preferences']['appointment_notifications'] = $request->boolean('notify_customers');

        $tenant->update(['settings' => $settings]);

        // Sync which Services are bookable
        Service::where('tenant_id', $this->tenantId())->update(['is_bookable' => false]);
        if (!empty($data['bookable_service_ids'])) {
            Service::where('tenant_id', $this->tenantId())
                ->whereIn('id', $data['bookable_service_ids'])
                ->update(['is_bookable' => true]);
        }

        $redirect = back()->with('success', 'Booking settings updated.');

        // Enabling booking alone isn't enough — say so instead of leaving the public
        // page silently empty.
        if ($request->boolean('enabled') && empty($data['bookable_service_ids'])) {
            $redirect->with('error', 'Online booking is on, but no service is enabled for booking yet — customers will see nothing to book. Tick at least one under "Bookable Services".');
        }

        return $redirect;
    }
}
