@extends('layouts.app')
@section('title', 'Services')

@push('styles')
<style>
.prod-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
.prod-table { width:100%; border-collapse:collapse; background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.prod-table th { padding:10px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; background:var(--bg-elevated); border-bottom:1px solid var(--border-subtle); }
.prod-table td { padding:11px 14px; border-bottom:1px solid var(--border-subtle); font-size:13.5px; color:var(--text-100); vertical-align:middle; }
.prod-table tr:last-child td { border-bottom:none; }
.prod-table tr:hover td { background:var(--bg-elevated); }
.badge-active   { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11.5px; font-weight:600; background:var(--green-dim); color:var(--green); }
.badge-inactive { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11.5px; font-weight:600; background:var(--bg-elevated); color:var(--text-400); }
.mono { font-family:var(--mono); }
@media(max-width:768px) {
    .prod-table { border:none; }
    .prod-table thead { display:none; }
    .prod-table tbody tr { display:block; margin-bottom:12px; border:1px solid var(--border-default); border-radius:var(--r-md); overflow:hidden; }
    .prod-table td { display:flex; align-items:center; justify-content:space-between; gap:12px; text-align:right; max-width:none !important; white-space:normal !important; }
    .prod-table td::before { content:attr(data-label); font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.4px; color:var(--text-400); text-align:left; flex-shrink:0; }
}
</style>
@endpush

@section('content')

<div class="prod-head">
    <div>
        <div class="page-title">Services</div>
        <div class="page-sub">Service catalog used for auto-filling invoices & quotations</div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('tenant.services.create') }}" class="btn btn-primary">+ Add Service</a>
    </div>
</div>

@if(session('success'))
<div style="padding:10px 14px;background:var(--green-dim);border:1px solid rgba(52,199,89,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--green)">
    {{ session('success') }}
</div>
@endif

<form method="GET" style="margin-bottom:14px;display:flex;gap:8px">
    <input type="text" name="search" value="{{ request('search') }}"
           placeholder="Search services..."
           style="padding:9px 13px;border:1.5px solid var(--border-default);border-radius:var(--r-sm);background:var(--bg-input);color:var(--text-100);font-size:13.5px;outline:none;width:280px"/>
    <button class="btn btn-secondary" type="submit">Search</button>
    @if(request('search'))
    <a href="{{ route('tenant.services.index') }}" class="btn btn-secondary">Clear</a>
    @endif
</form>

<div class="products-table-wrap">
<table class="prod-table">
    <thead>
        <tr>
            <th>Code</th>
            <th>Name</th>
            <th>Description</th>
            <th>HSN/SAC</th>
            <th>Rate (₹)</th>
            <th>GST %</th>
            <th>Unit</th>
            <th>Billing</th>
            <th>Status</th>
            <th style="width:100px"></th>
        </tr>
    </thead>
    <tbody>
        @forelse($services as $s)
        <tr>
            <td class="mono" style="font-size:12px;color:var(--text-300)" data-label="Code">{{ $s->service_code ?: '—' }}</td>
            <td style="font-weight:600" data-label="Name">
                {{ $s->name }}
                @if($s->is_package)
                    <span style="display:inline-block;padding:1px 7px;border-radius:20px;font-size:10.5px;font-weight:600;background:var(--accent-dim);color:var(--accent);margin-left:4px">📦 Package</span>
                @endif
                @if($s->is_package && $s->packageComponents->isNotEmpty())
                    <div style="font-size:11px;color:var(--text-400);margin-top:2px">{{ $s->packageComponents->pluck('name')->join(', ') }}</div>
                @endif
            </td>
            <td style="color:var(--text-300);font-size:12.5px;max-width:240px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" data-label="Description">
                {{ $s->description ?: '—' }}
            </td>
            <td class="mono" style="font-size:12.5px" data-label="HSN/SAC">{{ $s->hsn ?: '—' }}</td>
            <td class="mono" data-label="Rate (₹)">₹{{ number_format($s->rate, 2) }}</td>
            <td class="mono" data-label="GST %">{{ $s->tax_percent }}%</td>
            <td data-label="Unit">{{ $s->unit ?: '—' }}</td>
            <td data-label="Billing">
                {{ \App\Models\Service::billingCycles()[$s->billing_cycle] ?? 'One-time' }}
                @if($s->duration_value && $s->duration_unit)
                    <div style="font-size:11px;color:var(--text-400)">{{ $s->duration_value }} {{ $s->duration_unit }}</div>
                @endif
            </td>
            <td data-label="Status">
                @if($s->is_active)
                    <span class="badge-active">Active</span>
                @else
                    <span class="badge-inactive">Inactive</span>
                @endif
            </td>
            <td style="display:flex;gap:6px">
                <a href="{{ route('tenant.services.edit', $s->id) }}" class="btn btn-secondary btn-sm">Edit</a>
                <form method="POST" action="{{ route('tenant.services.destroy', $s->id) }}"
                      data-confirm="Delete this service?" data-confirm-ok="Delete">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm" type="submit"
                            style="background:var(--red-dim);color:var(--red);border:1px solid rgba(255,82,87,.25)">
                        Delete
                    </button>
                </form>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="9" style="text-align:center;padding:40px;color:var(--text-400)">
                No services yet. <a href="{{ route('tenant.services.create') }}" style="color:var(--accent)">Add your first service</a>.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>

<div style="margin-top:14px">{{ $services->links() }}</div>

@endsection
