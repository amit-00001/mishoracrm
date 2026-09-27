@extends('layouts.app')
@section('title', 'Appointments')

@push('styles')
<style>
.status-tabs { display:flex; gap:4px; flex-wrap:wrap; margin-bottom:16px; }
.s-tab { padding:7px 14px; border-radius:var(--r-sm); font-size:12.5px; font-weight:600; text-decoration:none; color:var(--text-300); border:1.5px solid transparent; transition:all .15s; }
.s-tab:hover { color:var(--text-100); background:var(--bg-elevated); }
.s-tab.active { background:var(--accent-dim); color:var(--accent); border-color:rgba(var(--accent-rgb),.25); }
.sub-table { width:100%; border-collapse:collapse; background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.sub-table th { padding:10px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; background:var(--bg-elevated); border-bottom:1px solid var(--border-subtle); }
.sub-table td { padding:11px 14px; border-bottom:1px solid var(--border-subtle); font-size:13.5px; color:var(--text-100); vertical-align:middle; }
.sub-table tr:last-child td { border-bottom:none; }
.sub-table tr:hover td { background:var(--bg-elevated); }
.mono { font-family:var(--mono); }
.badge-status { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11.5px; font-weight:600; }
.badge-booked, .badge-confirmed { background:var(--green-dim); color:var(--green); }
.badge-in_progress { background:var(--accent-dim); color:var(--accent); }
.badge-completed { background:var(--accent-dim); color:var(--accent); }
.badge-cancelled, .badge-no_show { background:var(--red-dim); color:var(--red); }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div class="page-title">Appointments</div>
        <div class="page-sub">Customer bookings — online and manual</div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('tenant.appointments.settings') }}" class="btn btn-secondary">Booking Settings</a>
        <a href="{{ route('tenant.appointments.create') }}" class="btn btn-primary">+ Book Appointment</a>
    </div>
</div>

@if(session('success'))
<div style="padding:10px 14px;background:var(--green-dim);border:1px solid rgba(52,199,89,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--green)">
    {{ session('success') }}
</div>
@endif

@if($tenant->bookingSettings()['enabled'])
<div style="padding:10px 14px;background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-sm);margin-bottom:16px;font-size:12.5px;color:var(--text-300);display:flex;align-items:center;gap:8px;flex-wrap:wrap">
    <span>Public booking link:</span>
    <code style="color:var(--accent)">{{ $tenant->bookingPublicUrl() }}</code>
</div>
@endif

<div class="status-tabs">
    <a href="{{ route('tenant.appointments.index', ['status'=>'upcoming']) }}" class="s-tab {{ $status === 'upcoming' ? 'active' : '' }}">Upcoming ({{ $counts['upcoming'] }})</a>
    <a href="{{ route('tenant.appointments.index', ['status'=>'today']) }}" class="s-tab {{ $status === 'today' ? 'active' : '' }}">Today ({{ $counts['today'] }})</a>
    <a href="{{ route('tenant.appointments.index', ['status'=>'completed']) }}" class="s-tab {{ $status === 'completed' ? 'active' : '' }}">Completed ({{ $counts['completed'] }})</a>
    <a href="{{ route('tenant.appointments.index', ['status'=>'cancelled']) }}" class="s-tab {{ $status === 'cancelled' ? 'active' : '' }}">Cancelled ({{ $counts['cancelled'] }})</a>
</div>

<div class="sub-table-wrap">
<table class="sub-table">
    <thead>
        <tr>
            <th>Contact</th>
            <th>Service</th>
            <th>Technician</th>
            <th>Date &amp; Time</th>
            <th>Source</th>
            <th>Status</th>
            <th style="width:260px"></th>
        </tr>
    </thead>
    <tbody>
        @forelse($appointments as $a)
        <tr>
            <td style="font-weight:600" data-label="Contact">
                <a href="{{ route('tenant.appointments.show', $a->id) }}" style="color:var(--text-100);text-decoration:none">{{ $a->contact?->name ?? '—' }}</a>
                @if($a->contact?->phone)<div style="font-size:11.5px;color:var(--text-400)">{{ $a->contact->phone }}</div>@endif
            </td>
            <td data-label="Service">{{ $a->service?->name ?? '—' }}</td>
            <td data-label="Technician">{{ $a->assignedTo?->name ?? '—' }}</td>
            <td class="mono" data-label="Date & Time">{{ $a->starts_at->format('d M Y, h:i A') }}</td>
            <td data-label="Source">{{ $a->source === 'public' ? 'Online' : 'Manual' }}</td>
            <td data-label="Status">
                <span class="badge-status badge-{{ $a->status }}">{{ \App\Models\Appointment::statuses()[$a->status] ?? ucfirst($a->status) }}</span>
            </td>
            <td style="display:flex;gap:6px;flex-wrap:wrap">
                <a href="{{ route('tenant.appointments.show', $a->id) }}" class="btn btn-secondary btn-sm">View</a>
                @if(in_array($a->status, ['booked', 'confirmed']))
                <form method="POST" action="{{ route('tenant.appointments.status', $a->id) }}">
                    @csrf
                    <input type="hidden" name="status" value="confirmed"/>
                    <button class="btn btn-secondary btn-sm" type="submit">Confirm</button>
                </form>
                <form method="POST" action="{{ route('tenant.appointments.status', $a->id) }}">
                    @csrf
                    <input type="hidden" name="status" value="completed"/>
                    <button class="btn btn-secondary btn-sm" type="submit">Complete</button>
                </form>
                <form method="POST" action="{{ route('tenant.appointments.status', $a->id) }}">
                    @csrf
                    <input type="hidden" name="status" value="no_show"/>
                    <button class="btn btn-secondary btn-sm" type="submit">No-show</button>
                </form>
                <form method="POST" action="{{ route('tenant.appointments.status', $a->id) }}" data-confirm="Cancel this appointment?">
                    @csrf
                    <input type="hidden" name="status" value="cancelled"/>
                    <button class="btn btn-sm" type="submit" style="background:var(--red-dim);color:var(--red);border:1px solid rgba(255,82,87,.25)">Cancel</button>
                </form>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align:center;padding:40px;color:var(--text-400)">
                No appointments yet. <a href="{{ route('tenant.appointments.create') }}" style="color:var(--accent)">Book your first appointment</a>.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>

<div style="margin-top:14px">{{ $appointments->links() }}</div>

@endsection
