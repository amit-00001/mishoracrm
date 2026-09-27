@extends('layouts.app')
@section('title', 'Staff')

@push('styles')
<style>
.filter-bar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
.filter-input {
    padding:8px 12px; height:36px;
    background:var(--bg-input); border:1.5px solid var(--border-default);
    border-radius:var(--r-sm); color:var(--text-100);
    font-family:var(--font); font-size:13px; outline:none;
    transition:border-color .15s var(--ease);
}
.filter-input:focus { border-color:var(--accent); }
.search-wrap { position:relative; flex:1; min-width:180px; max-width:280px; }
.search-wrap svg { position:absolute; left:10px; top:50%; transform:translateY(-50%); width:15px; height:15px; color:var(--text-300); pointer-events:none; }
.search-wrap input { width:100%; padding-left:34px; }

/* Staff card grid */
.staff-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:14px; }

.staff-card {
    background:var(--bg-surface); border:1px solid var(--border-default);
    border-radius:var(--r-lg); padding:20px;
    transition:border-color .15s var(--ease), transform .15s var(--ease);
    animation:fadeUp .3s var(--ease) both;
}
.staff-card:hover { border-color:var(--border-strong); transform:translateY(-1px); }

.sc-top { display:flex; align-items:center; gap:14px; margin-bottom:14px; }
.sc-avatar {
    width:48px; height:48px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:18px; font-weight:800; flex-shrink:0;
}
.sc-name { font-size:14.5px; font-weight:700; color:var(--text-100); margin-bottom:2px; }
.sc-designation { font-size:12px; color:var(--text-300); }

.sc-info { display:flex; flex-direction:column; gap:6px; margin-bottom:14px; }
.sc-row  { display:flex; align-items:center; gap:8px; font-size:12.5px; color:var(--text-200); }
.sc-row svg { width:13px; height:13px; color:var(--text-400); flex-shrink:0; }

.sc-foot { display:flex; align-items:center; justify-content:space-between; padding-top:12px; border-top:1px solid var(--border-subtle); }
.sc-actions { display:flex; gap:6px; }
.sc-btn {
    padding:6px 12px; border-radius:var(--r-sm); font-size:12px; font-weight:600;
    border:1.5px solid var(--border-default); background:none;
    color:var(--text-200); cursor:pointer; text-decoration:none;
    transition:all .15s var(--ease); font-family:var(--font);
}
.sc-btn:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-dim); }
.sc-btn.danger:hover { border-color:var(--red); color:var(--red); background:var(--red-dim); }

.role-badge {
    font-size:11px; font-weight:600; padding:2px 8px;
    border-radius:20px; background:var(--accent-dim); color:var(--accent);
}
.inactive-badge {
    font-size:11px; font-weight:600; padding:2px 8px;
    border-radius:20px; background:var(--red-dim); color:var(--red);
}

.pagination-wrap { display:flex; align-items:center; justify-content:space-between; margin-top:20px; font-size:13px; color:var(--text-300); }
.pagination-links { display:flex; gap:4px; }
.page-link { padding:5px 10px; border-radius:var(--r-sm); border:1px solid var(--border-default); color:var(--text-200); text-decoration:none; font-size:13px; transition:all .15s; }
.page-link:hover { border-color:var(--accent); color:var(--accent); }
.page-link.active { background:var(--accent); border-color:var(--accent); color:#fff; }
.page-link.disabled { opacity:.4; pointer-events:none; }

.empty-state { padding:60px 20px; text-align:center; }
.empty-icon  { font-size:40px; margin-bottom:12px; }
.empty-title { font-size:15px; font-weight:700; color:var(--text-100); margin-bottom:6px; }
.empty-sub   { font-size:13px; color:var(--text-300); margin-bottom:20px; }

@keyframes fadeUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:none} }

/* Bulk role bar */
.sc-check { position:absolute; top:14px; right:14px; width:16px; height:16px; accent-color:var(--accent); cursor:pointer; }
.staff-card { position:relative; }
.bulk-bar {
    position:sticky; bottom:16px; z-index:20;
    display:flex; align-items:center; gap:12px; flex-wrap:wrap;
    margin-top:16px; padding:12px 16px;
    background:var(--bg-elevated); border:1.5px solid var(--accent);
    border-radius:var(--r-md); box-shadow:0 6px 24px rgba(0,0,0,.12);
}
.bulk-bar.hidden { display:none; }
.bulk-bar select {
    padding:7px 10px; background:var(--bg-input); border:1.5px solid var(--border-default);
    border-radius:var(--r-sm); color:var(--text-100); font-size:13px;
}
</style>
@endpush

@section('content')

@php
    $avatarColors = [
        ['var(--accent-dim)','var(--accent)'], ['var(--green-dim)','var(--green)'],
        ['var(--amber-dim)','var(--amber)'], ['var(--purple-dim)','var(--purple)'],
        ['#FEE2E2','#991B1B'], ['#FEF3C7','#92400E'],
    ];
@endphp

<div class="page-head">
    <div>
        <div class="page-title">Staff</div>
        <div class="page-sub">{{ $staff->total() }} team members</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('tenant.staffs.create') }}" class="btn btn-primary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Staff
        </a>
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('tenant.staffs.index') }}" id="filterForm">
    <div class="filter-bar">
        <div class="search-wrap">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
            <input type="text" name="search" class="filter-input"
                   placeholder="Search name, email..."
                   value="{{ request('search') }}"/>
        </div>

        <select name="department_id" class="filter-input" onchange="this.form.submit()">
            <option value="">All Departments</option>
            @foreach($departments as $dept)
            <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected':'' }}>
                {{ $dept->name }}
            </option>
            @endforeach
        </select>

        <select name="employment_type" class="filter-input" onchange="this.form.submit()">
            <option value="">All Types</option>
            @foreach($types as $key => $label)
            <option value="{{ $key }}" {{ request('employment_type') === $key ? 'selected':'' }}>
                {{ $label }}
            </option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-secondary">Search</button>

        @if(request()->hasAny(['search','department_id','employment_type']))
        <a href="{{ route('tenant.staffs.index') }}" class="btn btn-secondary">Clear</a>
        @endif
    </div>
</form>

{{-- Staff grid --}}
@if($staff->isEmpty())
<div class="card">
    <div class="empty-state">
        <div class="empty-icon">👥</div>
        <div class="empty-title">No staff found</div>
        <div class="empty-sub">Add your first team member</div>
        <a href="{{ route('tenant.staffs.create') }}" class="btn btn-primary">Add Staff</a>
    </div>
</div>
@else
@php $canBulk = $assignableRoles->isNotEmpty(); @endphp
<form method="POST" action="{{ route('tenant.staffs.bulk-role') }}" id="bulkForm">
@csrf
<div class="staff-grid">
    @foreach($staff as $i => $member)
    @php [$avBg, $avTx] = $avatarColors[$i % 6]; @endphp
    <div class="staff-card">

        @if($canBulk && $member->user && $member->user->id !== auth()->id())
        <input type="checkbox" name="staff_ids[]" value="{{ $member->id }}" class="sc-check bulk-check"
               title="Select for bulk role change"/>
        @endif

        <div class="sc-top">
            <div class="sc-avatar" style="background:{{ $avBg }};color:{{ $avTx }}">
                {{ strtoupper(substr($member->user->name, 0, 1)) }}
            </div>
            <div>
                <div class="sc-name">{{ $member->user->name }}</div>
                <div class="sc-designation">
                    {{ $member->designation ?? $member->department?->name ?? '—' }}
                </div>
            </div>
        </div>

        <div class="sc-info">
            <div class="sc-row">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                {{ $member->user->email }}
            </div>
            @if($member->user->phone)
            <div class="sc-row">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                {{ $member->user->phone }}
            </div>
            @endif
            @if($member->employee_code)
            <div class="sc-row">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z"/></svg>
                ID: {{ $member->employee_code }}
            </div>
            @endif
        </div>

        <div class="sc-foot">
            <div style="display:flex;gap:6px;flex-wrap:wrap">
                @foreach($member->user->roles as $role)
                <span class="role-badge">{{ \App\Helpers\Roles::label($role->name) }}</span>
                @endforeach
                @if(!$member->user->is_active)
                <span class="inactive-badge">Inactive</span>
                @endif
            </div>
            <div class="sc-actions">
                <a href="{{ route('tenant.staffs.show', $member->id) }}" class="sc-btn">View</a>
                <a href="{{ route('tenant.staffs.edit', $member->id) }}" class="sc-btn">Edit</a>
            </div>
        </div>

    </div>
    @endforeach
</div>

@if($canBulk)
<div class="bulk-bar hidden" id="bulkBar">
    <strong style="font-size:13px;color:var(--text-100)"><span id="bulkCount">0</span> selected</strong>
    <select name="role" id="bulkRole">
        <option value="">Change role to…</option>
        @foreach($assignableRoles as $r)
        <option value="{{ $r['name'] }}">{{ $r['label'] }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-primary btn-sm" id="bulkApply" disabled>Apply</button>
    <button type="button" class="btn btn-secondary btn-sm" id="bulkClear">Clear</button>
</div>
@endif
</form>

{{-- Pagination --}}
@if($staff->hasPages())
<div class="pagination-wrap">
    <span>Showing {{ $staff->firstItem() }}–{{ $staff->lastItem() }} of {{ $staff->total() }}</span>
    <div class="pagination-links">
        <a href="{{ $staff->previousPageUrl() ?? '#' }}" class="page-link {{ !$staff->previousPageUrl() ? 'disabled':'' }}">←</a>
        @foreach($staff->getUrlRange(max(1,$staff->currentPage()-2), min($staff->lastPage(),$staff->currentPage()+2)) as $page => $url)
        <a href="{{ $url }}" class="page-link {{ $page==$staff->currentPage() ? 'active':'' }}">{{ $page }}</a>
        @endforeach
        <a href="{{ $staff->nextPageUrl() ?? '#' }}" class="page-link {{ !$staff->nextPageUrl() ? 'disabled':'' }}">→</a>
    </div>
</div>
@endif
@endif

@endsection

@push('scripts')
<script>
document.querySelector('input[name="search"]')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') document.getElementById('filterForm').submit();
});

(function () {
    const bar   = document.getElementById('bulkBar');
    if (!bar) return;
    const checks = () => [...document.querySelectorAll('.bulk-check')];
    const count = document.getElementById('bulkCount');
    const role  = document.getElementById('bulkRole');
    const apply = document.getElementById('bulkApply');

    function refresh() {
        const n = checks().filter(c => c.checked).length;
        count.textContent = n;
        bar.classList.toggle('hidden', n === 0);
        apply.disabled = (n === 0 || !role.value);
    }
    checks().forEach(c => c.addEventListener('change', refresh));
    role.addEventListener('change', refresh);
    document.getElementById('bulkClear').addEventListener('click', () => {
        checks().forEach(c => c.checked = false); refresh();
    });
    document.getElementById('bulkForm').addEventListener('submit', e => {
        const n = checks().filter(c => c.checked).length;
        if (n === 0 || !role.value) { e.preventDefault(); return; }
        requireConfirm(e.target, `Change role to "${role.options[role.selectedIndex].text}" for ${n} staff member(s)?`, {ok: 'Change role', danger: false});
    });
})();
</script>
@endpush