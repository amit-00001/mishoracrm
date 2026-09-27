@extends('layouts.app')
@section('title', 'Departments')

@push('styles')
<style>
.dept-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:14px; }

.dept-card {
    background:var(--bg-surface); border:1px solid var(--border-default);
    border-radius:var(--r-lg); padding:20px;
    transition:border-color .15s var(--ease), transform .15s var(--ease);
    display:flex; flex-direction:column; gap:12px;
}
.dept-card:hover { border-color:var(--border-strong); transform:translateY(-1px); }

.dept-icon {
    width:42px; height:42px; border-radius:var(--r-md);
    background:var(--accent-dim); color:var(--accent);
    display:flex; align-items:center; justify-content:center;
}
.dept-icon svg { width:20px; height:20px; }
.dept-name { font-size:15px; font-weight:700; color:var(--text-100); }
.dept-desc { font-size:12.5px; color:var(--text-300); line-height:1.5; }
.dept-count {
    display:inline-flex; align-items:center; gap:5px;
    font-size:12px; font-weight:600; color:var(--text-300);
    font-family:var(--mono);
}
.dept-actions { display:flex; gap:6px; padding-top:12px; border-top:1px solid var(--border-subtle); }
.dept-btn {
    flex:1; padding:7px; border-radius:var(--r-sm);
    border:1.5px solid var(--border-default); background:none;
    font-size:12px; font-weight:600; font-family:var(--font);
    color:var(--text-200); cursor:pointer; text-decoration:none;
    text-align:center; transition:all .15s var(--ease);
}
.dept-btn:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-dim); }
.dept-btn.danger:hover { border-color:var(--red); color:var(--red); background:var(--red-dim); }

.filter-bar { display:flex; align-items:center; gap:10px; margin-bottom:20px; }
.filter-input {
    padding:8px 12px; height:36px;
    background:var(--bg-input); border:1.5px solid var(--border-default);
    border-radius:var(--r-sm); color:var(--text-100);
    font-family:var(--font); font-size:13px; outline:none;
}
.filter-input:focus { border-color:var(--accent); }
.search-wrap { position:relative; flex:1; max-width:280px; }
.search-wrap svg { position:absolute; left:10px; top:50%; transform:translateY(-50%); width:15px; height:15px; color:var(--text-300); pointer-events:none; }
.search-wrap input { width:100%; padding-left:34px; }

.empty-state { padding:60px 20px; text-align:center; }
.empty-icon  { font-size:40px; margin-bottom:12px; }
.empty-title { font-size:15px; font-weight:700; color:var(--text-100); margin-bottom:6px; }
.empty-sub   { font-size:13px; color:var(--text-300); margin-bottom:20px; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div class="page-title">Departments</div>
        <div class="page-sub">{{ $departments->total() }} total departments</div>
    </div>
    <a href="{{ route('tenant.departments.create') }}" class="btn btn-primary">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Add Department
    </a>
</div>

{{-- Search --}}
<form method="GET" action="{{ route('tenant.departments.index') }}" id="filterForm">
    <div class="filter-bar">
        <div class="search-wrap">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <input type="text" name="search" class="filter-input"
                   placeholder="Search departments..."
                   value="{{ request('search') }}"/>
        </div>
        <button type="submit" class="btn btn-secondary">Search</button>
        @if(request('search'))
        <a href="{{ route('tenant.departments.index') }}" class="btn btn-secondary">Clear</a>
        @endif
    </div>
</form>

@if($departments->isEmpty())
<div class="card">
    <div class="empty-state">
        <div class="empty-icon">🏢</div>
        <div class="empty-title">No departments yet</div>
        <div class="empty-sub">Create your first department to organize staff</div>
        <a href="{{ route('tenant.departments.create') }}" class="btn btn-primary">Add Department</a>
    </div>
</div>
@else
<div class="dept-grid">
    @foreach($departments as $dept)
    <div class="dept-card">
        <div style="display:flex;align-items:center;gap:12px">
            <div class="dept-icon">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                </svg>
            </div>
            <div>
                <div class="dept-name">{{ $dept->name }}</div>
                <div class="dept-count">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:12px;height:12px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                    </svg>
                    {{ $dept->staff_count }} staff
                </div>
            </div>
        </div>

        @if($dept->description)
        <div class="dept-desc">{{ $dept->description }}</div>
        @endif

        <div class="dept-actions">
            <a href="{{ route('tenant.departments.edit', $dept->id) }}" class="dept-btn">Edit</a>
            <form method="POST" action="{{ route('tenant.departments.destroy', $dept->id) }}"
                  data-confirm="Delete {{ $dept->name }}?" data-confirm-ok="Delete">
                @csrf @method('DELETE')
                <button type="submit" class="dept-btn danger">Delete</button>
            </form>
        </div>
    </div>
    @endforeach
</div>

{{-- Pagination --}}
@if($departments->hasPages())
<div style="display:flex;justify-content:center;margin-top:20px;gap:4px">
    <a href="{{ $departments->previousPageUrl() ?? '#' }}"
       style="padding:6px 12px;border:1px solid var(--border-default);border-radius:var(--r-sm);color:var(--text-200);text-decoration:none;font-size:13px;{{ !$departments->previousPageUrl() ? 'opacity:.4;pointer-events:none' : '' }}">←</a>
    @foreach($departments->getUrlRange(max(1,$departments->currentPage()-2), min($departments->lastPage(),$departments->currentPage()+2)) as $page => $url)
    <a href="{{ $url }}"
       style="padding:6px 12px;border:1px solid var(--border-default);border-radius:var(--r-sm);text-decoration:none;font-size:13px;{{ $page==$departments->currentPage() ? 'background:var(--accent);border-color:var(--accent);color:#fff' : 'color:var(--text-200)' }}">
        {{ $page }}
    </a>
    @endforeach
    <a href="{{ $departments->nextPageUrl() ?? '#' }}"
       style="padding:6px 12px;border:1px solid var(--border-default);border-radius:var(--r-sm);color:var(--text-200);text-decoration:none;font-size:13px;{{ !$departments->nextPageUrl() ? 'opacity:.4;pointer-events:none' : '' }}">→</a>
</div>
@endif
@endif

@endsection