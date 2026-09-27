@extends('layouts.app')
@section('title', 'Vendors')

@push('styles')
<style>
.filter-bar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
.filter-input {
    padding:8px 12px; height:36px;
    background:var(--bg-input); border:1.5px solid var(--border-default);
    border-radius:var(--r-sm); color:var(--text-100);
    font-family:var(--font); font-size:13px; outline:none;
    transition:border-color 0.15s var(--ease);
}
.filter-input:focus { border-color:var(--accent); }
.search-wrap { position:relative; flex:1; min-width:200px; max-width:320px; }
.search-wrap svg { position:absolute; left:10px; top:50%; transform:translateY(-50%); width:15px; height:15px; color:var(--text-300); pointer-events:none; }
.search-wrap input { width:100%; padding-left:34px; }

.empty-state { padding:60px 20px; text-align:center; }
.empty-icon  { font-size:40px; margin-bottom:12px; }
.empty-title { font-size:15px; font-weight:700; color:var(--text-100); margin-bottom:6px; }
.empty-sub   { font-size:13px; color:var(--text-300); margin-bottom:20px; }

.pagination-wrap { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-top:1px solid var(--border-subtle); font-size:13px; color:var(--text-300); }
.pagination-links { display:flex; gap:4px; }
.page-link { padding:5px 10px; border-radius:var(--r-sm); border:1px solid var(--border-default); color:var(--text-200); text-decoration:none; font-size:13px; transition:all 0.15s; }
.page-link:hover { border-color:var(--accent); color:var(--accent); }
.page-link.active { background:var(--accent); border-color:var(--accent); color:#fff; }
.page-link.disabled { opacity:0.4; pointer-events:none; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div class="page-title">Vendors</div>
        <div class="page-sub">{{ $vendors->total() }} total vendors</div>
    </div>
    <div class="page-actions">
        @can('create', \App\Models\Vendor::class)
        <a href="{{ route('tenant.vendors.create') }}" class="btn btn-primary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            New Vendor
        </a>
        @endcan
    </div>
</div>

<form method="GET" action="{{ route('tenant.vendors.index') }}" id="filterForm">
    <div class="filter-bar">
        <div class="search-wrap">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <input type="text" name="search" class="filter-input"
                   placeholder="Search name, company, phone, email..."
                   value="{{ request('search') }}"/>
        </div>
        <button type="submit" class="btn btn-secondary">Search</button>
        @if(request()->hasAny(['search']))
        <a href="{{ route('tenant.vendors.index') }}" class="btn btn-secondary">Clear</a>
        @endif
    </div>
</form>

<div class="card">
    @if($vendors->isEmpty())
    <div class="empty-state">
        <div class="empty-icon">🏭</div>
        <div class="empty-title">No vendors found</div>
        <div class="empty-sub">Add your first vendor to start raising purchase orders</div>
        @can('create', \App\Models\Vendor::class)
        <a href="{{ route('tenant.vendors.create') }}" class="btn btn-primary">Add Vendor</a>
        @endcan
    </div>
    @else
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Company</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>City</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($vendors as $v)
                <tr>
                    <td data-label="Name">
                        <a href="{{ route('tenant.vendors.show', $v->id) }}" style="text-decoration:none;color:var(--text-100);font-weight:600">
                            {{ $v->name }}
                        </a>
                    </td>
                    <td data-label="Company">{{ $v->company ?? '—' }}</td>
                    <td data-label="Phone" class="td-mono" style="font-size:12.5px">{{ $v->phone ?? '—' }}</td>
                    <td data-label="Email" style="font-size:12.5px">{{ $v->email ?? '—' }}</td>
                    <td data-label="City">{{ $v->city ?? '—' }}</td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center">
                            <a href="{{ route('tenant.vendors.show', $v->id) }}" class="btn btn-secondary btn-sm btn-icon" title="View">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </a>
                            @can('modify', $v)
                            <a href="{{ route('tenant.vendors.edit', $v->id) }}" class="btn btn-secondary btn-sm btn-icon" title="Edit">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                                </svg>
                            </a>
                            @endcan
                            @can('delete', $v)
                            <form method="POST" action="{{ route('tenant.vendors.destroy', $v->id) }}"
                                  data-confirm="Delete {{ $v->name }}?" data-confirm-ok="Delete">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-secondary btn-sm btn-icon" style="color:var(--red)" title="Delete">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                    </svg>
                                </button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($vendors->hasPages())
    <div class="pagination-wrap">
        <span>Showing {{ $vendors->firstItem() }}–{{ $vendors->lastItem() }} of {{ $vendors->total() }}</span>
        <div class="pagination-links">
            <a href="{{ $vendors->previousPageUrl() ?? '#' }}" class="page-link {{ !$vendors->previousPageUrl() ? 'disabled' : '' }}">←</a>
            @foreach($vendors->getUrlRange(max(1,$vendors->currentPage()-2), min($vendors->lastPage(),$vendors->currentPage()+2)) as $page => $url)
            <a href="{{ $url }}" class="page-link {{ $page == $vendors->currentPage() ? 'active' : '' }}">{{ $page }}</a>
            @endforeach
            <a href="{{ $vendors->nextPageUrl() ?? '#' }}" class="page-link {{ !$vendors->nextPageUrl() ? 'disabled' : '' }}">→</a>
        </div>
    </div>
    @endif
    @endif
</div>

@endsection
