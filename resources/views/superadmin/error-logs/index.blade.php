@extends('layouts.app')
@section('title', 'Error Logs')

@push('styles')
<style>
.el-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
@media(max-width:900px){ .el-stats{ grid-template-columns:repeat(2,1fr); } }
.elstat { background:var(--bg-elevated); border:1px solid var(--border-default); border-radius:var(--r-md); padding:14px 16px; }
.elstat-num   { font-size:22px; font-weight:700; color:var(--text-100); font-family:var(--mono); }
.elstat-label { font-size:12px; color:var(--text-300); margin-top:2px; }

.http-badge { display:inline-block; font-size:11px; font-weight:700; padding:2px 7px; border-radius:4px; font-family:var(--mono); }
.http-5xx { background:var(--red-dim); color:var(--red); }
.http-4xx { background:var(--amber-dim); color:var(--amber); }
.http-other { background:var(--border-subtle); color:var(--text-300); }

.resolved-dot { display:inline-block; width:7px; height:7px; border-radius:50%; }
.dot-open     { background:var(--red); }
.dot-resolved { background:var(--green); }

.exc-class { font-size:11px; color:var(--text-400); font-family:var(--mono); }
.filters-bar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:16px; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div class="page-title">Error Logs</div>
        <div class="page-sub">Real-time errors captured from all tenant users</div>
    </div>
    <div style="display:flex;gap:8px;">
        <form data-confirm="Mark all unresolved errors as resolved?" data-confirm-ok="Mark" data-confirm-danger="false" method="POST" action="{{ route('superadmin.error-logs.resolve-all') }}">
            @csrf
            @if(request('tenant_id'))
                <input type="hidden" name="tenant_id" value="{{ request('tenant_id') }}">
            @endif
            <button type="submit" class="btn btn-secondary btn-sm">
                Mark All Resolved
            </button>
        </form>
    </div>
</div>

{{-- Stats --}}
<div class="el-stats">
    <div class="elstat">
        <div class="elstat-num">{{ $stats['total'] }}</div>
        <div class="elstat-label">Total Errors</div>
    </div>
    <div class="elstat" style="border-color:var(--red);">
        <div class="elstat-num" style="color:var(--red)">{{ $stats['unresolved'] }}</div>
        <div class="elstat-label">Unresolved</div>
    </div>
    <div class="elstat" style="border-color:var(--amber);">
        <div class="elstat-num" style="color:var(--amber)">{{ $stats['critical'] }}</div>
        <div class="elstat-label">Critical (5xx)</div>
    </div>
    <div class="elstat" style="border-color:var(--green);">
        <div class="elstat-num" style="color:var(--green)">{{ $stats['today'] }}</div>
        <div class="elstat-label">Today</div>
    </div>
</div>

{{-- Filters --}}
<div class="card" style="margin-bottom:16px;">
    <div class="card-body" style="padding:12px 16px;">
        <form method="GET" action="{{ route('superadmin.error-logs.index') }}" class="filters-bar">
            <input type="text" name="search" value="{{ request('search') }}"
                   placeholder="Search message, URL, exception…"
                   class="form-input" style="min-width:220px;font-size:13px;">

            <select name="tenant_id" class="form-input" style="font-size:13px;width:auto;">
                <option value="">All Tenants</option>
                @foreach($tenants as $tenant)
                    <option value="{{ $tenant->id }}" {{ request('tenant_id') == $tenant->id ? 'selected' : '' }}>
                        {{ $tenant->name }}
                    </option>
                @endforeach
            </select>

            <select name="status" class="form-input" style="font-size:13px;width:auto;">
                <option value="">All Status Codes</option>
                <option value="500" {{ request('status') === '500' ? 'selected' : '' }}>500 - Server Error</option>
                <option value="403" {{ request('status') === '403' ? 'selected' : '' }}>403 - Forbidden</option>
                <option value="404" {{ request('status') === '404' ? 'selected' : '' }}>404 - Not Found</option>
                <option value="422" {{ request('status') === '422' ? 'selected' : '' }}>422 - Validation</option>
            </select>

            <select name="resolved" class="form-input" style="font-size:13px;width:auto;">
                <option value="">All</option>
                <option value="0" {{ request('resolved') === '0' ? 'selected' : '' }}>Unresolved</option>
                <option value="1" {{ request('resolved') === '1' ? 'selected' : '' }}>Resolved</option>
            </select>

            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            @if(request()->hasAny(['search','tenant_id','status','resolved']))
                <a href="{{ route('superadmin.error-logs.index') }}" class="btn btn-secondary btn-sm">Clear</a>
            @endif
        </form>
    </div>
</div>

{{-- Error table --}}
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Errors</div>
            <div class="card-subtitle">{{ $errors->total() }} results</div>
        </div>
    </div>
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width:50px;"></th>
                    <th>Error</th>
                    <th>Tenant / User</th>
                    <th>URL</th>
                    <th>Time</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($errors as $error)
                <tr>
                    <td>
                        @php
                            $statusClass = $error->http_status >= 500 ? 'http-5xx'
                                : ($error->http_status >= 400 ? 'http-4xx' : 'http-other');
                        @endphp
                        <span class="http-badge {{ $statusClass }}">{{ $error->http_status ?? '?' }}</span>
                    </td>
                    <td style="max-width:340px;" data-label="Error">
                        <div style="display:flex;align-items:center;gap:6px;margin-bottom:2px;">
                            <span class="resolved-dot {{ $error->is_resolved ? 'dot-resolved' : 'dot-open' }}"
                                  title="{{ $error->is_resolved ? 'Resolved' : 'Open' }}"></span>
                            <span style="font-size:13px;font-weight:600;color:var(--text-100);">
                                {{ Str::limit($error->message, 80) }}
                            </span>
                        </div>
                        <div class="exc-class">{{ $error->short_class }}</div>
                    </td>
                    <td data-label="Tenant / User">
                        @if($error->tenant)
                            <div style="font-size:12.5px;font-weight:600;color:var(--text-100)">{{ $error->tenant->name }}</div>
                        @else
                            <span style="font-size:12px;color:var(--text-400)">—</span>
                        @endif
                        @if($error->user)
                            <div style="font-size:11.5px;color:var(--text-300)">{{ $error->user->name }}</div>
                        @endif
                    </td>
                    <td style="max-width:200px;" data-label="URL">
                        <div style="font-size:11.5px;color:var(--text-300);font-family:var(--mono);word-break:break-all;">
                            <span style="font-weight:600;color:var(--accent);">{{ $error->method }}</span>
                            {{ Str::limit(parse_url($error->url ?? '', PHP_URL_PATH) ?? '', 60) }}
                        </div>
                    </td>
                    <td style="white-space:nowrap;font-size:12px;color:var(--text-300);" data-label="Time">
                        {{ $error->created_at->diffForHumans() }}
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center;">
                            <a href="{{ route('superadmin.error-logs.show', $error) }}"
                               class="btn btn-secondary btn-sm" style="font-size:11px;padding:3px 8px;">View</a>

                            @if(!$error->is_resolved)
                            <form method="POST" action="{{ route('superadmin.error-logs.resolve', $error) }}">
                                @csrf
                                <button type="submit" class="btn btn-sm"
                                    style="font-size:11px;padding:3px 8px;background:var(--green-dim);color:var(--green);border:none;border-radius:var(--r-sm);cursor:pointer;">
                                    Resolve
                                </button>
                            </form>
                            @endif

                            <form method="POST" action="{{ route('superadmin.error-logs.destroy', $error) }}"
                                  data-confirm="Delete this error log?" data-confirm-ok="Delete">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm"
                                    style="font-size:11px;padding:3px 8px;background:var(--red-dim);color:var(--red);border:none;border-radius:var(--r-sm);cursor:pointer;">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="text-align:center;padding:40px;color:var(--text-400);">
                        No errors found. Your CRM is running clean!
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($errors->hasPages())
    <div class="card-footer">
        {{ $errors->links() }}
    </div>
    @endif
</div>

@endsection
