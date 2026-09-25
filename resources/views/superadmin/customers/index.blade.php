@extends('layouts.app')
@section('title', 'Portal Customers')

@push('styles')
<style>
.stat-row { display:grid; grid-template-columns:repeat(5,1fr); gap:14px; margin-bottom:24px; }
@media(max-width:900px){ .stat-row { grid-template-columns:1fr 1fr; } }
.stat-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); padding:14px 18px; }
.stat-card .s-label { font-size:12px; color:var(--text-400); font-weight:600; text-transform:uppercase; letter-spacing:.04em; }
.stat-card .s-value { font-size:24px; font-weight:800; color:var(--text-100); margin-top:4px; }
.tl-card { display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); padding:14px 18px; margin-bottom:20px; }
.tl-card.is-on { border-color:var(--green); }
.tl-title { font-size:14px; font-weight:700; color:var(--text-100); display:flex; align-items:center; gap:8px; }
.tl-sub { font-size:12.5px; color:var(--text-400); margin-top:3px; max-width:640px; }
.tl-sub code { color:var(--accent); }
.filters { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
.table-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.data-table { width:100%; border-collapse:collapse; }
.data-table th { padding:11px 16px; text-align:left; font-size:11.5px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.05em; border-bottom:1px solid var(--border-subtle); background:var(--bg-elevated); white-space:nowrap; }
.data-table td { padding:13px 16px; font-size:13.5px; color:var(--text-200); border-bottom:1px solid var(--border-subtle); vertical-align:middle; }
.data-table tr:last-child td { border-bottom:none; }
.badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:100px; font-size:11px; font-weight:700; }
.badge-green { background:var(--green-dim); color:var(--green); }
.badge-red { background:var(--red-dim); color:var(--red); }
.empty-state { text-align:center; padding:56px; color:var(--text-400); font-size:14px; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div class="page-title">Portal Customers</div>
        <div class="page-sub">Platform-level wallet accounts — one per phone number, shared across shops. Shops never see this list.</div>
    </div>
</div>

@if(session('success'))
<div style="padding:12px 16px;background:var(--green-dim);border:1px solid rgba(29,158,117,.2);border-radius:var(--r-sm);font-size:13px;color:var(--green);margin-bottom:16px">
    ✓ {{ session('success') }}
</div>
@endif

<div class="tl-card {{ $testLogin['on'] ? 'is-on' : '' }}">
    <div>
        <div class="tl-title">
            Test login
            <span class="badge {{ $testLogin['on'] ? 'badge-green' : 'badge-red' }}">{{ $testLogin['on'] ? 'ON' : 'OFF' }}</span>
        </div>
        <div class="tl-sub">
            @if($testLogin['on'])
            Phone <code>{{ $testLogin['phone'] }}</code> signs in to the wallet with OTP <code>{{ $testLogin['otp'] }}</code> — no real code needed.
            A "Test Customer" card (80 points) has been added to the first 5 tenants. Turning this OFF removes them again.
            @else
            Turn on to let phone <code>{{ $testLogin['phone'] }}</code> sign in with OTP <code>{{ $testLogin['otp'] }}</code> and to add a
            "Test Customer" card (80 points) to the first 5 tenants, so you can try the wallet end to end. Testing only — it puts fake contacts in those tenants.
            @endif
        </div>
    </div>
    @if($testLogin['on'])
    <form method="POST" action="{{ route('superadmin.customers.test-login.disable') }}"
          onsubmit="return confirm('Turn test login OFF? The test customer and its test contacts will be removed.')">
        @csrf
        <button type="submit" class="btn btn-secondary">Turn OFF</button>
    </form>
    @else
    <form method="POST" action="{{ route('superadmin.customers.test-login.enable') }}"
          onsubmit="return confirm('Turn test login ON? This adds a fake Test Customer to the first 5 tenants.')">
        @csrf
        <button type="submit" class="btn btn-primary">Turn ON</button>
    </form>
    @endif
</div>

<div class="stat-row">
    <div class="stat-card"><div class="s-label">Customers</div><div class="s-value">{{ number_format($metrics['total']) }}</div></div>
    <div class="stat-card"><div class="s-label">With a shop</div><div class="s-value">{{ number_format($metrics['with_shops']) }}</div></div>
    <div class="stat-card"><div class="s-label">Active (30 days)</div><div class="s-value">{{ number_format($metrics['active_30d']) }}</div></div>
    <div class="stat-card"><div class="s-label">Avg shops each</div><div class="s-value">{{ $metrics['avg_shops'] }}</div></div>
    <div class="stat-card"><div class="s-label">Blocked</div><div class="s-value">{{ number_format($metrics['blocked']) }}</div></div>
</div>

<form method="GET" class="filters">
    <input type="text" name="q" value="{{ $search }}" placeholder="Search phone / name / email"/>
    <select name="status" onchange="this.form.submit()">
        <option value="">Any status</option>
        <option value="active" @selected($status === 'active')>Active</option>
        <option value="blocked" @selected($status === 'blocked')>Blocked</option>
    </select>
    <select name="scope" onchange="this.form.submit()">
        <option value="real" @selected($scope === 'real')>Linked or signed in</option>
        <option value="all" @selected($scope === 'all')>Everyone (incl. never used)</option>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
    @if($search || $status || $scope === 'all')
    <a href="{{ route('superadmin.customers.index') }}" class="btn btn-secondary btn-sm">Clear</a>
    @endif
</form>

<div class="table-card">
<table class="data-table">
    <thead>
        <tr>
            <th>Phone</th>
            <th>Name</th>
            <th style="text-align:right">Shops</th>
            <th>Last sign-in</th>
            <th>Joined</th>
            <th>Status</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse($customers as $c)
        <tr>
            <td style="font-weight:600;color:var(--text-100)">{{ $c->phone }}</td>
            <td>{{ $c->name ?: '—' }}@if($c->email)<div style="font-size:12px;color:var(--text-400)">{{ $c->email }}</div>@endif</td>
            <td style="text-align:right">{{ (int) $c->linked_shops }}</td>
            <td>{{ $c->last_login_at ? $c->last_login_at->diffForHumans() : 'Never' }}</td>
            <td>{{ $c->created_at->format('d M Y') }}</td>
            <td>
                @if($c->blocked_at)
                <span class="badge badge-red">Blocked</span>
                @else
                <span class="badge badge-green">Active</span>
                @endif
            </td>
            <td style="text-align:right">
                @if($c->blocked_at)
                <form method="POST" action="{{ route('superadmin.customers.unblock', $c->id) }}" style="display:inline">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">Unblock</button>
                </form>
                @else
                <form method="POST" action="{{ route('superadmin.customers.block', $c->id) }}" style="display:inline"
                      onsubmit="return confirm('Block this customer? They will be signed out and unable to log in.')">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">Block</button>
                </form>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="7"><div class="empty-state">No customers match.</div></td></tr>
        @endforelse
    </tbody>
</table>
</div>

<div style="margin-top:14px">{{ $customers->links() }}</div>

@endsection
