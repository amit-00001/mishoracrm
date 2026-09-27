@extends('layouts.app')
@section('title', 'All Tenants')

@push('styles')
<style>
/* Stat mini cards */
.tenant-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
@media(max-width:900px){ .tenant-stats{ grid-template-columns:repeat(2,1fr); } }
.tstat { background:var(--bg-elevated); border:1px solid var(--border-default); border-radius:var(--r-md); padding:14px 16px; }
.tstat-num   { font-size:22px; font-weight:700; color:var(--text-100); font-family:var(--mono); }
.tstat-label { font-size:12px; color:var(--text-300); margin-top:2px; }

/* Filters bar */
.filters-bar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:16px; }
.filters-bar .btn-sm { padding:5px 12px; font-size:12px; }

/* Status badges */
.t-status { display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:600; padding:2px 8px; border-radius:20px; }
.t-status::before { content:''; width:5px; height:5px; border-radius:50%; }
.t-active    { background:var(--green-dim); color:var(--green); }
.t-active::before { background:var(--green); }
.t-inactive  { background:var(--amber-dim); color:var(--amber); }
.t-inactive::before { background:var(--amber); }
.t-suspended { background:var(--red-dim); color:var(--red); }
.t-suspended::before { background:var(--red); }

/* User quota bar */
.quota-bar  { height:4px; border-radius:2px; background:var(--border-subtle); margin-top:4px; overflow:hidden; }
.quota-fill { height:100%; border-radius:2px; background:var(--green); transition:width 0.3s; }
.quota-fill.warn  { background:var(--amber); }
.quota-fill.full  { background:var(--red); }
.quota-text { font-size:11px; color:var(--text-300); font-family:var(--mono); }

/* Sub status pill */
.sub-pill { font-size:11px; font-weight:600; padding:2px 8px; border-radius:20px; }
.sub-active   { background:var(--green-dim); color:var(--green); }
.sub-trial    { background:var(--accent-dim); color:var(--accent); }
.sub-expired  { background:var(--red-dim); color:var(--red); }
.sub-cancelled{ background:var(--border-subtle); color:var(--text-400); }

/* Toggle status form */
.status-select { font-size:11px; padding:3px 6px; background:var(--bg-input); border:1px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); cursor:pointer; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div class="page-title">All Tenants</div>
        <div class="page-sub">{{ $stats['total'] }} total &middot; {{ $stats['active'] }} active &middot; {{ $stats['inactive'] }} inactive &middot; {{ $stats['suspended'] }} suspended</div>
    </div>
</div>

{{-- Mini stat cards --}}
<div class="tenant-stats">
    <div class="tstat">
        <div class="tstat-num">{{ $stats['total'] }}</div>
        <div class="tstat-label">Total Tenants</div>
    </div>
    <div class="tstat" style="border-color:var(--green);">
        <div class="tstat-num" style="color:var(--green)">{{ $stats['active'] }}</div>
        <div class="tstat-label">Active</div>
    </div>
    <div class="tstat" style="border-color:var(--amber);">
        <div class="tstat-num" style="color:var(--amber)">{{ $stats['inactive'] }}</div>
        <div class="tstat-label">Inactive</div>
    </div>
    <div class="tstat" style="border-color:var(--red);">
        <div class="tstat-num" style="color:var(--red)">{{ $stats['suspended'] }}</div>
        <div class="tstat-label">Suspended</div>
    </div>
</div>

{{-- Filters --}}
<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="padding:12px 16px;">
        <form method="GET" action="{{ route('superadmin.tenants.index') }}" class="filters-bar">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search name, email, subdomain…"
                   class="form-input" style="min-width:240px;font-size:13px;">

            <select name="status" class="form-input" style="font-size:13px;width:auto;">
                <option value="">All Status</option>
                <option value="active"    {{ request('status') === 'active'    ? 'selected' : '' }}>Active</option>
                <option value="inactive"  {{ request('status') === 'inactive'  ? 'selected' : '' }}>Inactive</option>
                <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
            </select>

            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            @if(request('search') || request('status'))
                <a href="{{ route('superadmin.tenants.index') }}" class="btn btn-secondary btn-sm">Clear</a>
            @endif
        </form>
    </div>
</div>

{{-- Tenants table --}}
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Tenants</div>
            <div class="card-subtitle">{{ $tenants->total() }} results</div>
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Subdomain</th>
                    <th>Plan</th>
                    <th>Users</th>
                    <th>Last Login</th>
                    <th>Status</th>
                    <th>Joined</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenants as $tenant)
                @php
                    $sub        = $tenant->subscription;
                    $plan       = $sub?->plan;
                    $effSeats   = $tenant->userSeatLimit();          // superadmin override, else plan
                    $maxUsers   = $effSeats > 0 ? $effSeats : 0;     // 0 / -1 → unlimited
                    $seatOverridden = $tenant->userSeatLimitOverride() !== null;
                    $userCount  = $tenant->user_count ?? 0;
                    $lastLogin  = $lastLogins[$tenant->id]?->last_login_at ?? null;
                    $pct        = $maxUsers > 0 ? min(100, round($userCount / $maxUsers * 100)) : 0;
                    $fillClass  = $pct >= 100 ? 'full' : ($pct >= 80 ? 'warn' : '');
                @endphp
                <tr>
                    <td data-label="Company">
                        <div class="td-name">{{ $tenant->name }}</div>
                        <div style="font-size:11.5px;color:var(--text-400)">{{ $tenant->email }}</div>
                    </td>
                    <td class="td-mono" style="font-size:12px;" data-label="Subdomain">{{ $tenant->subdomain }}</td>
                    <td data-label="Plan">
                        @if($plan)
                            <div style="font-size:12.5px;font-weight:600;color:var(--text-100)">{{ $plan->name }}</div>
                            @if($sub)
                                <span class="sub-pill sub-{{ $sub->status }}">{{ ucfirst($sub->status) }}</span>
                            @endif
                        @else
                            <span style="font-size:12px;color:var(--text-400)">No plan</span>
                        @endif
                    </td>
                    <td style="min-width:110px;" data-label="Users">
                        <div class="quota-text">
                            {{ $userCount }}{{ $maxUsers > 0 ? ' / '.$maxUsers : '' }}
                            @if($seatOverridden)
                                <span title="Seat limit manually overridden by superadmin" style="color:var(--accent);font-weight:700">*</span>
                            @endif
                        </div>
                        @if($maxUsers > 0)
                            <div class="quota-bar">
                                <div class="quota-fill {{ $fillClass }}" style="width:{{ $pct }}%"></div>
                            </div>
                        @endif
                    </td>
                    <td class="td-mono" style="font-size:11.5px;" data-label="Last Login">
                        @if($lastLogin)
                            <div style="color:var(--text-200)">{{ $lastLogin->format('d M Y') }}</div>
                            <div style="color:var(--text-400)">{{ $lastLogin->diffForHumans() }}</div>
                        @else
                            <span style="color:var(--text-400)">Never</span>
                        @endif
                    </td>
                    <td data-label="Status">
                        <form method="POST" action="{{ route('superadmin.tenants.toggle-status', $tenant) }}" onchange="this.submit()">
                            @csrf
                            <select name="status" class="status-select">
                                <option value="active"    {{ $tenant->status === 'active'    ? 'selected' : '' }}>● Active</option>
                                <option value="inactive"  {{ $tenant->status === 'inactive'  ? 'selected' : '' }}>● Inactive</option>
                                <option value="suspended" {{ $tenant->status === 'suspended' ? 'selected' : '' }}>● Suspended</option>
                            </select>
                        </form>
                    </td>
                    <td class="td-mono" style="font-size:11.5px;color:var(--text-400)" data-label="Joined">
                        {{ $tenant->created_at?->format('d M Y') ?? '—' }}
                    </td>
                    <td>
                        <a href="{{ route('superadmin.tenants.show', $tenant) }}"
                           class="btn btn-secondary btn-sm">
                            View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align:center;padding:40px;color:var(--text-400);">No tenants found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($tenants->hasPages())
    <div style="padding:16px 20px;border-top:1px solid var(--border-subtle);">
        {{ $tenants->links() }}
    </div>
    @endif
</div>

@endsection
