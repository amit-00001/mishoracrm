@extends('layouts.app')
@section('title', 'Booking Settings')

@push('styles')
<style>
.pf-card  { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; max-width:720px; }
.pf-body  { padding:24px; display:flex; flex-direction:column; gap:18px; }
.pf-foot  { padding:14px 24px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center; }
.field    { display:flex; flex-direction:column; gap:6px; }
.fl       { font-size:12px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.4px; }
.fi       { padding:9px 13px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:14px; outline:none; width:100%; }
.fg3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; }
.day-row { display:grid; grid-template-columns:90px auto 1fr 1fr; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid var(--border-subtle); }
.day-row:last-child { border-bottom:none; }
@media(max-width:640px) { .fg3 { grid-template-columns:1fr; } .day-row { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.appointments.index') }}" style="color:var(--text-300);text-decoration:none">Appointments</a>
            › Booking Settings
        </div>
        <div class="page-title">Booking Settings</div>
    </div>
    <a href="{{ route('tenant.appointments.index') }}" class="btn btn-secondary">← Back</a>
</div>

@if(session('success'))
<div style="padding:10px 14px;background:var(--green-dim);border:1px solid rgba(52,199,89,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--green)">
    {{ session('success') }}
</div>
@endif

<form method="POST" action="{{ route('tenant.appointments.settings.update') }}">
@csrf
<div class="pf-card">
    <div class="pf-body">

        <div class="field" style="flex-direction:row;align-items:center;gap:10px;padding:10px 12px;background:var(--bg-elevated);border-radius:var(--r-sm)">
            <input type="checkbox" name="enabled" value="1" id="enabled" {{ $settings['enabled'] ? 'checked' : '' }} style="width:16px;height:16px;cursor:pointer"/>
            <label for="enabled" style="font-size:13.5px;color:var(--text-200);cursor:pointer">Enable public online booking
                <span style="display:block;font-size:11.5px;color:var(--text-400)">Customers can only book services you also tick under "Bookable Services" below.</span>
            </label>
        </div>

        @if($settings['enabled'])
        <div style="font-size:12.5px;color:var(--text-300);padding:10px 12px;background:var(--bg-input);border-radius:var(--r-sm)">
            Public booking link: <code style="color:var(--accent)">{{ $tenant->bookingPublicUrl() }}</code>
        </div>

        @if($bookableCount === 0)
        <div id="noBookableWarning" role="alert" style="font-size:12.5px;padding:10px 12px;background:var(--amber-dim);border:1px solid var(--amber);color:var(--amber);border-radius:var(--r-sm);font-weight:500">
            ⚠ Online booking is on, but <strong>no service is enabled for booking yet</strong>, so customers will see "no services available".
            A service must be ticked under <em>Bookable Services</em> below before it can be booked — then save.
            @if($bookableServices->isEmpty() && \Illuminate\Support\Facades\Route::has('tenant.services.index'))
            <a href="{{ route('tenant.services.index') }}" style="color:inherit;text-decoration:underline">Add a service first.</a>
            @endif
        </div>
        @endif
        @endif

        <div class="field" style="flex-direction:row;align-items:center;gap:10px;padding:10px 12px;background:var(--bg-elevated);border-radius:var(--r-sm)">
            <input type="checkbox" name="notify_customers" value="1" id="notify_customers" {{ $notifyCustomers ? 'checked' : '' }} style="width:16px;height:16px;cursor:pointer"/>
            <label for="notify_customers" style="font-size:13.5px;color:var(--text-200);cursor:pointer">Send customers Email/WhatsApp notifications — booking confirmation, a reminder before the appointment, and a same-day reminder</label>
        </div>

        <div class="fg3">
            <div class="field">
                <label class="fl">Slot Duration (min)</label>
                <input type="number" name="slot_duration_minutes" class="fi" min="5" max="480" value="{{ $settings['slot_duration_minutes'] }}" required/>
            </div>
            <div class="field">
                <label class="fl">Capacity per Slot</label>
                <input type="number" name="capacity_per_slot" class="fi" min="1" max="100" value="{{ $settings['capacity_per_slot'] }}" required/>
                <span style="font-size:11px;color:var(--text-400)">How many bookings are allowed in the same slot</span>
            </div>
            <div class="field">
                <label class="fl">Advance Booking (days)</label>
                <input type="number" name="advance_booking_days" class="fi" min="1" max="365" value="{{ $settings['advance_booking_days'] }}" required/>
            </div>
        </div>

        <div class="field">
            <label class="fl">Business Hours</label>
            @php $dayLabels = ['mon'=>'Monday','tue'=>'Tuesday','wed'=>'Wednesday','thu'=>'Thursday','fri'=>'Friday','sat'=>'Saturday','sun'=>'Sunday']; @endphp
            <div style="border:1.5px solid var(--border-default);border-radius:var(--r-sm);padding:8px 12px;background:var(--bg-input)">
                @foreach($dayLabels as $key => $label)
                @php $day = $settings['hours'][$key] ?? ['closed'=>false,'open'=>'09:00','close'=>'18:00']; @endphp
                <div class="day-row">
                    <span style="font-size:13px;color:var(--text-200);font-weight:600">{{ $label }}</span>
                    <label style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-300);cursor:pointer">
                        <input type="checkbox" name="hours[{{ $key }}][closed]" value="1" {{ $day['closed'] ? 'checked' : '' }} style="width:13px;height:13px;cursor:pointer"/>
                        Closed
                    </label>
                    <input type="time" name="hours[{{ $key }}][open]" class="fi" value="{{ $day['open'] }}"/>
                    <input type="time" name="hours[{{ $key }}][close]" class="fi" value="{{ $day['close'] }}"/>
                </div>
                @endforeach
            </div>
        </div>

        <div class="field">
            <label class="fl">Bookable Services</label>
            <div style="display:flex;flex-direction:column;gap:6px;max-height:220px;overflow-y:auto;padding:10px 12px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm)">
                @forelse($bookableServices as $s)
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-200);cursor:pointer">
                    <input type="checkbox" name="bookable_service_ids[]" value="{{ $s->id }}" {{ $s->is_bookable ? 'checked' : '' }} style="width:14px;height:14px;cursor:pointer"/>
                    {{ $s->name }}
                </label>
                @empty
                <span style="font-size:12.5px;color:var(--text-400)">No active services available — add some from the Services page first.</span>
                @endforelse
            </div>
            <span style="font-size:11.5px;color:var(--text-400)">A service must be checked here to be bookable — only checked services appear on the public booking page, even when online booking is enabled.</span>
        </div>

    </div>
    <div class="pf-foot">
        <a href="{{ route('tenant.appointments.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Settings</button>
    </div>
</div>
</form>

@endsection
