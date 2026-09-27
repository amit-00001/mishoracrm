@extends('layouts.app')
@section('title', 'Appointment')

@php
$canManage = auth()->user()->user_type === 'tenant_admin' || $appointment->assigned_to === auth()->id();
@endphp

@push('styles')
<style>
.card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); padding:20px; margin-bottom:16px; }
.card h3 { font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:var(--text-300); margin:0 0 14px; }
.info-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.info-item .l { font-size:11px; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; margin-bottom:3px; }
.info-item .v { font-size:14px; color:var(--text-100); }
.badge-status { display:inline-block; padding:3px 11px; border-radius:20px; font-size:12px; font-weight:600; }
.badge-booked, .badge-confirmed { background:var(--green-dim); color:var(--green); }
.badge-in_progress { background:var(--accent-dim); color:var(--accent); }
.badge-completed { background:var(--accent-dim); color:var(--accent); }
.badge-cancelled, .badge-no_show { background:var(--red-dim); color:var(--red); }
.fi { padding:9px 13px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:14px; outline:none; width:100%; }
.mat-row { display:grid; grid-template-columns:2fr 1fr 1fr 1fr auto; gap:8px; margin-bottom:8px; align-items:center; }
.mat-table { width:100%; border-collapse:collapse; font-size:13px; }
.mat-table th { text-align:left; padding:6px 8px; font-size:11px; color:var(--text-400); text-transform:uppercase; border-bottom:1px solid var(--border-subtle); }
.mat-table td { padding:6px 8px; border-bottom:1px solid var(--border-subtle); color:var(--text-100); }
.photo-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); gap:10px; }
.photo-item { position:relative; border-radius:var(--r-sm); overflow:hidden; border:1px solid var(--border-default); }
.photo-item img { width:100%; height:100px; object-fit:cover; display:block; }
.photo-item .stage-tag { position:absolute; top:4px; left:4px; background:rgba(0,0,0,.6); color:#fff; font-size:10px; padding:1px 6px; border-radius:8px; }
.photo-item .del-btn { position:absolute; top:4px; right:4px; }
.sig-img { max-width:280px; border:1px solid var(--border-default); border-radius:var(--r-sm); background:#fff; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.appointments.index') }}" style="color:var(--text-300);text-decoration:none">Appointments</a>
            › Job #{{ $appointment->id }}
        </div>
        <div class="page-title">{{ $appointment->contact?->name ?? 'Appointment' }}</div>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <span class="badge-status badge-{{ $appointment->status }}">{{ \App\Models\Appointment::statuses()[$appointment->status] ?? ucfirst($appointment->status) }}</span>
        @if($canManage && !in_array($appointment->status, ['completed','cancelled','no_show']))
        <a href="{{ route('tenant.appointments.edit', $appointment->id) }}" class="btn btn-secondary">Edit</a>
        @endif
    </div>
</div>

@if(session('success'))
<div style="padding:10px 14px;background:var(--green-dim);border:1px solid rgba(52,199,89,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--green)">{{ session('success') }}</div>
@endif
@if(session('error'))
<div style="padding:10px 14px;background:var(--red-dim);border:1px solid rgba(255,82,87,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--red)">{{ session('error') }}</div>
@endif

<div style="padding:10px 14px;background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-sm);margin-bottom:16px;font-size:12.5px;color:var(--text-300);display:flex;align-items:center;gap:8px;flex-wrap:wrap">
    <span>Customer link:</span>
    <code style="color:var(--accent)">{{ $appointment->publicUrl() }}</code>
</div>

<div class="card">
    <h3>Job Details</h3>
    <div class="info-grid">
        <div class="info-item"><div class="l">Contact</div><div class="v">{{ $appointment->contact?->name ?? '—' }} @if($appointment->contact?->phone)<span style="color:var(--text-400)">— {{ $appointment->contact->phone }}</span>@endif</div></div>
        <div class="info-item"><div class="l">Service</div><div class="v">{{ $appointment->service?->name ?? '—' }}</div></div>
        <div class="info-item"><div class="l">Technician</div><div class="v">{{ $appointment->assignedTo?->name ?? 'Unassigned' }}</div></div>
        <div class="info-item"><div class="l">Service Address</div><div class="v">{{ $appointment->service_address ?? '—' }}</div></div>
        <div class="info-item"><div class="l">Scheduled</div><div class="v">{{ $appointment->starts_at->format('d M Y, h:i A') }}</div></div>
        <div class="info-item"><div class="l">Notes</div><div class="v">{{ $appointment->notes ?? '—' }}</div></div>
    </div>
</div>

<div class="card">
    <h3>Work Progress</h3>
    <div class="info-grid" style="margin-bottom:16px">
        <div class="info-item">
            <div class="l">Started</div>
            <div class="v">
                @if($appointment->work_started_at)
                    {{ $appointment->work_started_at->format('d M Y, h:i A') }}
                    @if($appointment->work_started_lat)<div style="font-size:11px;color:var(--text-400)">📍 {{ $appointment->work_started_lat }}, {{ $appointment->work_started_lng }}</div>@endif
                @else — @endif
            </div>
        </div>
        <div class="info-item">
            <div class="l">Completed</div>
            <div class="v">
                @if($appointment->work_completed_at)
                    {{ $appointment->work_completed_at->format('d M Y, h:i A') }}
                    @if($appointment->work_completed_lat)<div style="font-size:11px;color:var(--text-400)">📍 {{ $appointment->work_completed_lat }}, {{ $appointment->work_completed_lng }}</div>@endif
                @else — @endif
            </div>
        </div>
    </div>

    @if($canManage && $appointment->canStartWork())
    <form method="POST" action="{{ route('tenant.appointments.start', $appointment->id) }}" id="startForm">
        @csrf
        <input type="hidden" name="lat" id="startLat"/>
        <input type="hidden" name="lng" id="startLng"/>
        <button type="submit" class="btn btn-primary" id="startBtn">Start Work</button>
    </form>
    @endif

    @if($canManage && $appointment->canCompleteWork())
    <form method="POST" action="{{ route('tenant.appointments.complete', $appointment->id) }}" id="completeForm">
        @csrf
        <input type="hidden" name="lat" id="completeLat"/>
        <input type="hidden" name="lng" id="completeLng"/>

        <div style="margin-bottom:10px;font-size:13px;color:var(--text-300)">Materials used (optional) — linking a product will deduct it from stock</div>
        <div id="matRows"></div>
        <button type="button" class="btn btn-secondary btn-sm" onclick="addMatRow()" style="margin-bottom:14px">+ Add Material</button>
        <div><button type="submit" class="btn btn-primary" id="completeBtn">Complete Work</button></div>
    </form>
    @endif

    @if(!$canManage && !in_array($appointment->status, ['completed','cancelled','no_show']))
    <div style="font-size:12.5px;color:var(--text-400)">Only the assigned technician or a tenant admin can start/complete this job.</div>
    @endif
</div>

@if(!empty($appointment->materials_used))
<div class="card">
    <h3>Materials Used</h3>
    <table class="mat-table">
        <thead><tr><th>Item</th><th>Qty</th><th>Rate</th></tr></thead>
        <tbody>
            @foreach($appointment->materials_used as $m)
            <tr>
                <td>{{ $m['name'] ?? '—' }}</td>
                <td>{{ $m['quantity'] ?? '—' }}</td>
                <td>{{ !empty($m['rate']) ? '₹' . number_format($m['rate'], 2) : '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="card">
    <h3>Before / After Photos</h3>
    @if($canManage)
    <form method="POST" action="{{ route('tenant.appointments.attachments.store', $appointment->id) }}" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:center;margin-bottom:16px;flex-wrap:wrap">
        @csrf
        <select name="stage" class="fi" style="width:auto" required>
            <option value="before">Before</option>
            <option value="after">After</option>
        </select>
        <input type="file" name="attachments[]" multiple accept="image/*" required/>
        <button type="submit" class="btn btn-secondary btn-sm">Upload</button>
    </form>
    @endif

    @if($appointment->attachments->isEmpty())
    <div style="font-size:12.5px;color:var(--text-400)">No photos uploaded yet.</div>
    @else
    <div class="photo-grid">
        @foreach($appointment->attachments as $att)
        <div class="photo-item">
            <a href="{{ $att->url }}" target="_blank"><img src="{{ $att->url }}" alt="{{ $att->original_name }}"/></a>
            @if($att->stage)<span class="stage-tag">{{ ucfirst($att->stage) }}</span>@endif
            @if($canManage)
            <form method="POST" action="{{ route('tenant.appointments.attachments.destroy', [$appointment->id, $att->id]) }}" class="del-btn" data-confirm="Delete this photo?" data-confirm-ok="Delete">
                @csrf @method('DELETE')
                <button type="submit" style="background:rgba(0,0,0,.6);color:#fff;border:none;border-radius:4px;padding:1px 6px;font-size:11px;cursor:pointer">✕</button>
            </form>
            @endif
        </div>
        @endforeach
    </div>
    @endif
</div>

<div class="card">
    <h3>Customer Sign-off</h3>
    @if($appointment->customer_signed_at)
        <div style="font-size:13.5px;color:var(--text-100);margin-bottom:8px">Signed by <strong>{{ $appointment->customer_signed_name }}</strong> on {{ $appointment->customer_signed_at->format('d M Y, h:i A') }}</div>
        @if($appointment->customer_signature)
        <img src="{{ $appointment->customer_signature }}" class="sig-img" alt="Customer signature"/>
        @endif
    @elseif($appointment->isAwaitingSignoff())
        <div style="font-size:12.5px;color:var(--text-400)">Awaiting customer sign-off — share the customer's booking link so they can approve the completed job.</div>
    @else
        <div style="font-size:12.5px;color:var(--text-400)">Sign-off happens once the job is marked completed.</div>
    @endif
</div>

<div class="card">
    <h3>Invoice</h3>
    @if($appointment->invoice)
        <a href="{{ route('tenant.invoices.show', $appointment->invoice_id) }}" class="btn btn-secondary">View Invoice {{ $appointment->invoice->number }}</a>
    @elseif($appointment->status === 'completed')
        <form method="POST" action="{{ route('tenant.appointments.convert-invoice', $appointment->id) }}">
            @csrf
            <button type="submit" class="btn btn-primary">Convert to Invoice</button>
        </form>
    @else
        <div style="font-size:12.5px;color:var(--text-400)">Available once the job is marked completed.</div>
    @endif
</div>

<script>
const availableProducts = @json($products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'rate' => $p->rate]));

function geoAndSubmit(formId, latId, lngId, btnId) {
    const form = document.getElementById(formId);
    const btn  = document.getElementById(btnId);
    btn.addEventListener('click', function (e) {
        e.preventDefault();
        btn.disabled = true;
        const finish = () => form.submit();
        if (!navigator.geolocation) { finish(); return; }
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                document.getElementById(latId).value = pos.coords.latitude;
                document.getElementById(lngId).value = pos.coords.longitude;
                finish();
            },
            () => finish(),
            { timeout: 4000 }
        );
    });
}
@if($canManage && $appointment->canStartWork())
geoAndSubmit('startForm', 'startLat', 'startLng', 'startBtn');
@endif
@if($canManage && $appointment->canCompleteWork())
geoAndSubmit('completeForm', 'completeLat', 'completeLng', 'completeBtn');

function addMatRow() {
    const idx = document.querySelectorAll('.mat-row').length;
    const row = document.createElement('div');
    row.className = 'mat-row';
    row.innerHTML = `
        <select class="fi" onchange="fillMatRow(this, ${idx})">
            <option value="">— Custom item —</option>
            ${availableProducts.map(p => `<option value="${p.id}">${p.name}</option>`).join('')}
        </select>
        <input type="hidden" name="materials[${idx}][product_id]" class="mat-pid"/>
        <input type="text" name="materials[${idx}][name]" class="fi mat-name" placeholder="Item name" required/>
        <input type="number" name="materials[${idx}][quantity]" class="fi" placeholder="Qty" step="0.01" min="0.01" required/>
        <input type="number" name="materials[${idx}][rate]" class="fi mat-rate" placeholder="Rate (optional)" step="0.01" min="0"/>
        <button type="button" class="btn btn-secondary btn-sm" onclick="this.parentElement.remove()">✕</button>
    `;
    document.getElementById('matRows').appendChild(row);
}

function fillMatRow(select, idx) {
    const row = select.closest('.mat-row');
    const product = availableProducts.find(p => p.id == select.value);
    row.querySelector('.mat-pid').value = select.value || '';
    if (product) {
        row.querySelector('.mat-name').value = product.name;
        row.querySelector('.mat-rate').value = product.rate;
    }
}
@endif
</script>

@endsection
