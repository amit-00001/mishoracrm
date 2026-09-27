@extends('layouts.app')
@section('title', 'Attendance')

@push('styles')
<style>
/* ── Filter bar ─────────────────────────────────────────── */
.filter-bar {
    display:flex; align-items:center; gap:10px;
    flex-wrap:wrap; margin-bottom:20px;
}
.filter-input {
    padding:8px 12px; height:36px;
    background:var(--bg-input); border:1.5px solid var(--border-default);
    border-radius:var(--r-sm); color:var(--text-100);
    font-family:var(--font); font-size:13px; outline:none;
    transition:border-color 0.15s var(--ease);
}
.filter-input:focus { border-color:var(--accent); }

/* ── Stats row ──────────────────────────────────────────── */
.att-stats {
    display:grid; grid-template-columns:repeat(5,1fr);
    gap:12px; margin-bottom:20px;
}
@media(max-width:1000px) { .att-stats { grid-template-columns:repeat(3,1fr); } }
@media(max-width:600px)  { .att-stats { grid-template-columns:repeat(2,1fr); } }
.as-card {
    background:var(--bg-surface); border:1px solid var(--border-default);
    border-radius:var(--r-md); padding:16px;
    display:flex; align-items:center; gap:12px;
    cursor:pointer; transition:border-color 0.15s var(--ease);
}
.as-card:hover  { border-color:var(--border-strong); }
.as-card.active { border-color:var(--accent); }
.as-icon { width:36px; height:36px; border-radius:var(--r-sm); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.as-icon svg { width:17px; height:17px; }
.as-num   { font-size:20px; font-weight:800; color:var(--text-100); font-family:var(--mono); letter-spacing:-0.5px; }
.as-label { font-size:12px; color:var(--text-300); margin-top:1px; }

/* ── Attendance table ───────────────────────────────────── */
.status-badge {
    display:inline-flex; align-items:center; gap:4px;
    padding:3px 9px; border-radius:20px;
    font-size:11.5px; font-weight:600; letter-spacing:0.2px;
}
.badge-green  { background:var(--green-dim);  color:var(--green);  }
.badge-red    { background:var(--red-dim);    color:var(--red);    }
.badge-amber  { background:var(--amber-dim);  color:var(--amber);  }
.badge-purple { background:var(--purple-dim); color:var(--purple); }
.badge-blue   { background:var(--blue-dim,rgba(59,130,246,.12)); color:var(--blue); }

/* Pagination */
.pagination-wrap { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-top:1px solid var(--border-subtle); font-size:13px; color:var(--text-300); }
.pagination-links { display:flex; gap:4px; }
.page-link { padding:5px 10px; border-radius:var(--r-sm); border:1px solid var(--border-default); color:var(--text-200); text-decoration:none; font-size:13px; transition:all 0.15s; }
.page-link:hover   { border-color:var(--accent); color:var(--accent); }
.page-link.active  { background:var(--accent); border-color:var(--accent); color:#fff; }
.page-link.disabled { opacity:0.4; pointer-events:none; }

/* Empty state */
.empty-state { padding:60px 20px; text-align:center; }
.empty-icon  { font-size:40px; margin-bottom:12px; }
.empty-title { font-size:15px; font-weight:700; color:var(--text-100); margin-bottom:6px; }
.empty-sub   { font-size:13px; color:var(--text-300); margin-bottom:20px; }

/* ── MOBILE ATTENDANCE CARDS (list view, <768px) ──────────────── */
.at-mobile-list{display:none}
@media(max-width:768px){
    .at-table-wrap{display:none}
    .at-mobile-list{display:flex;flex-direction:column;gap:10px;padding:14px}
}
.at-card{background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-md);padding:14px}
.at-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:12px}
.at-id{display:flex;align-items:center;gap:10px;min-width:0}
.at-av{width:32px;height:32px;border-radius:50%;background:var(--accent-dim);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0}
.at-name{font-size:14px;font-weight:700;color:var(--text-100);line-height:1.3;word-break:break-word}
.at-desig{font-size:11.5px;color:var(--text-400);margin-top:2px}
.at-date{display:flex;flex-direction:column;gap:2px;margin-bottom:10px;padding:9px 11px;background:var(--bg-elevated);border-radius:8px}
.at-date-main{font-size:12.5px;font-family:var(--mono);color:var(--text-200)}
.at-date-day{font-size:11px;color:var(--text-400)}
.at-times{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-bottom:10px}
.at-time-lbl{font-size:10px;color:var(--text-400);text-transform:uppercase;letter-spacing:.04em}
.at-time-val{font-size:12.5px;font-family:var(--mono);color:var(--text-200);margin-top:2px}
.at-notes{font-size:12px;color:var(--text-300);margin-bottom:10px}
.at-foot{display:flex;align-items:center;justify-content:flex-end;gap:6px;padding-top:10px;border-top:1px solid var(--border-subtle)}
</style>
@endpush

@section('content')
@php $tenantSlug = auth()->user()->tenant->subdomain; @endphp

{{-- Page Header --}}
<div class="page-head">
    <div>
        <div class="page-title">Attendance</div>
        <div class="page-sub">
            {{ \Carbon\Carbon::create($year, $month)->format('F Y') }} —
            {{ $total }} records
        </div>
    </div>
    <div class="page-actions">
        <a href="{{ route('tenant.attendances.clock', ['tenant' => $tenantSlug]) }}"
           class="btn btn-secondary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Clock In/Out
        </a>
        <a href="{{ route('tenant.attendances.bulk', ['tenant' => $tenantSlug]) }}"
           class="btn btn-secondary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/>
            </svg>
            Bulk Entry
        </a>
        <a href="{{ route('tenant.attendances.create', ['tenant' => $tenantSlug]) }}"
           class="btn btn-primary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Manual Entry
        </a>
    </div>
</div>

{{-- Stats Cards --}}
<div class="att-stats">
    <div class="as-card" onclick="filterStatus('')">
        <div class="as-icon" style="background:var(--accent-dim)">
            <svg fill="none" stroke="var(--accent)" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
            </svg>
        </div>
        <div>
            <div class="as-num">{{ $total }}</div>
            <div class="as-label">Total</div>
        </div>
    </div>
    <div class="as-card" onclick="filterStatus('present')">
        <div class="as-icon" style="background:var(--green-dim)">
            <svg fill="none" stroke="var(--green)" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <div class="as-num">{{ $summary['present'] ?? 0 }}</div>
            <div class="as-label">Present</div>
        </div>
    </div>
    <div class="as-card" onclick="filterStatus('absent')">
        <div class="as-icon" style="background:var(--red-dim)">
            <svg fill="none" stroke="var(--red)" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <div class="as-num">{{ $summary['absent'] ?? 0 }}</div>
            <div class="as-label">Absent</div>
        </div>
    </div>
    <div class="as-card" onclick="filterStatus('half_day')">
        <div class="as-icon" style="background:var(--amber-dim)">
            <svg fill="none" stroke="var(--amber)" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
            </svg>
        </div>
        <div>
            <div class="as-num">{{ $summary['half_day'] ?? 0 }}</div>
            <div class="as-label">Half Day</div>
        </div>
    </div>
    <div class="as-card" onclick="filterStatus('leave')">
        <div class="as-icon" style="background:var(--purple-dim)">
            <svg fill="none" stroke="var(--purple)" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
            </svg>
        </div>
        <div>
            <div class="as-num">{{ ($summary['leave'] ?? 0) + ($summary['holiday'] ?? 0) }}</div>
            <div class="as-label">Leave/Holiday</div>
        </div>
    </div>
</div>

{{-- Filter Bar --}}
<form method="GET"
      action="{{ route('tenant.attendances.index', ['tenant' => $tenantSlug]) }}"
      id="filterForm">
    <div class="filter-bar">

        {{-- Month --}}
        <select name="month" class="filter-input" onchange="this.form.submit()">
            @foreach(range(1,12) as $m)
                <option value="{{ $m }}" @selected($m == $month)>
                    {{ \Carbon\Carbon::create(null,$m)->format('F') }}
                </option>
            @endforeach
        </select>

        {{-- Year --}}
        <input type="number" name="year" class="filter-input"
               value="{{ $year }}" min="2020" max="{{ now()->year }}"
               style="width:90px" onchange="this.form.submit()">

        {{-- Staff --}}
        <select name="staff_id" class="filter-input">
            <option value="">All Staff</option>
            @foreach($staffList as $s)
                <option value="{{ $s->id }}" @selected(request('staff_id') == $s->id)>
                    {{ $s->name }}
                </option>
            @endforeach
        </select>

        {{-- Status --}}
        <select name="status" class="filter-input" id="statusFilter">
            <option value="">All Status</option>
            @foreach(['present','absent','half_day','holiday','leave'] as $st)
                <option value="{{ $st }}" @selected(request('status') == $st)>
                    {{ ucfirst(str_replace('_',' ',$st)) }}
                </option>
            @endforeach
        </select>

        <button type="submit" class="btn btn-secondary">Filter</button>

        @if(request()->hasAny(['staff_id','status']))
        <a href="{{ route('tenant.attendances.index', ['tenant' => $tenantSlug, 'month' => $month, 'year' => $year]) }}"
           class="btn btn-secondary">Clear</a>
        @endif

    </div>
</form>

{{-- Table --}}
<div class="card">
    @if($attendances->isEmpty())
    <div class="empty-state">
        <div class="empty-icon">📋</div>
        <div class="empty-title">Koi attendance record nahi mila</div>
        <div class="empty-sub">
            @if(request()->hasAny(['staff_id','status']))
                Filters clear karo ya
            @endif
            Bulk entry se start karo
        </div>
        <a href="{{ route('tenant.attendances.bulk', ['tenant' => $tenantSlug]) }}"
           class="btn btn-primary">Bulk Entry</a>
    </div>
    @else
    <div class="at-table-wrap" style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Staff</th>
                    <th>Date</th>
                    <th>Clock In</th>
                    <th>Clock Out</th>
                    <th>Worked</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($attendances as $att)
                <tr>
                    <td data-label="Staff">
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:32px;height:32px;border-radius:50%;
                                        background:var(--accent-dim);color:var(--accent);
                                        display:flex;align-items:center;justify-content:center;
                                        font-size:13px;font-weight:700;flex-shrink:0">
                                {{ strtoupper(substr($att->staff->name, 0, 1)) }}
                            </div>
                            <div>
                                <div style="font-size:13.5px;font-weight:600;color:var(--text-100)">
                                    {{ $att->staff->name }}
                                </div>
                                @if($att->staff->designation)
                                <div style="font-size:11.5px;color:var(--text-400)">
                                    {{ $att->staff->designation }}
                                </div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="td-mono" style="font-size:13px" data-label="Date">
                        {{ $att->date->format('d M Y') }}
                        <div style="font-size:11px;color:var(--text-400)">
                            {{ $att->date->format('l') }}
                        </div>
                    </td>
                    <td class="td-mono" style="font-size:13px" data-label="Clock In">
                        {{ $att->clock_in?->format('h:i A') ?? '—' }}
                    </td>
                    <td class="td-mono" style="font-size:13px" data-label="Clock Out">
                        {{ $att->clock_out?->format('h:i A') ?? '—' }}
                    </td>
                    <td class="td-mono" style="font-size:13px;color:var(--text-200)" data-label="Worked">
                        {{ $att->worked_hours ?? '—' }}
                    </td>
                    <td data-label="Status">
                        <span class="status-badge badge-{{ $att->status_color }}">
                            {{ $att->status_label }}
                        </span>
                    </td>
                    <td style="font-size:12.5px;color:var(--text-300);max-width:160px" data-label="Notes">
                        {{ Str::limit($att->notes, 35) ?? '—' }}
                    </td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <a href="{{ route('tenant.screenshots.show', ['tenant' => $tenantSlug, 'attendance' => $att]) }}"
                               class="btn btn-secondary btn-sm btn-icon">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:13px;height:13px">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                                </svg>
                            </a>
                            <a href="{{ route('tenant.attendances.edit', ['tenant' => $tenantSlug, 'attendance' => $att->id]) }}"
                               class="btn btn-secondary btn-sm btn-icon">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:13px;height:13px">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                                </svg>
                            </a>
                            <form method="POST"
                                  action="{{ route('tenant.attendances.destroy', ['tenant' => $tenantSlug, 'attendance' => $att->id]) }}"
                                  data-confirm="Delete karna chahte ho?" data-confirm-ok="Delete">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-secondary btn-sm btn-icon"
                                        style="color:var(--red)">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:13px;height:13px">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile card list (shown only <768px, table above hides itself) --}}
    <div class="at-mobile-list">
    @foreach($attendances as $att)
    <div class="at-card">
        <div class="at-top">
            <div class="at-id">
                <div class="at-av">{{ strtoupper(substr($att->staff->name, 0, 1)) }}</div>
                <div style="min-width:0">
                    <div class="at-name">{{ $att->staff->name }}</div>
                    @if($att->staff->designation)
                    <div class="at-desig">{{ $att->staff->designation }}</div>
                    @endif
                </div>
            </div>
            <span class="status-badge badge-{{ $att->status_color }}">{{ $att->status_label }}</span>
        </div>
        <div class="at-date">
            <div class="at-date-main">{{ $att->date->format('d M Y') }}</div>
            <div class="at-date-day">{{ $att->date->format('l') }}</div>
        </div>
        <div class="at-times">
            <div>
                <div class="at-time-lbl">Clock In</div>
                <div class="at-time-val">{{ $att->clock_in?->format('h:i A') ?? '—' }}</div>
            </div>
            <div>
                <div class="at-time-lbl">Clock Out</div>
                <div class="at-time-val">{{ $att->clock_out?->format('h:i A') ?? '—' }}</div>
            </div>
            <div>
                <div class="at-time-lbl">Worked</div>
                <div class="at-time-val">{{ $att->worked_hours ?? '—' }}</div>
            </div>
        </div>
        @if($att->notes)
        <div class="at-notes">{{ Str::limit($att->notes, 60) }}</div>
        @endif
        <div class="at-foot">
            <a href="{{ route('tenant.screenshots.show', ['tenant' => $tenantSlug, 'attendance' => $att]) }}"
               class="btn btn-secondary btn-sm btn-icon">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:13px;height:13px">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                </svg>
            </a>
            <a href="{{ route('tenant.attendances.edit', ['tenant' => $tenantSlug, 'attendance' => $att->id]) }}"
               class="btn btn-secondary btn-sm btn-icon">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:13px;height:13px">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                </svg>
            </a>
            <form method="POST"
                  action="{{ route('tenant.attendances.destroy', ['tenant' => $tenantSlug, 'attendance' => $att->id]) }}"
                  data-confirm="Delete karna chahte ho?" data-confirm-ok="Delete">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-secondary btn-sm btn-icon"
                        style="color:var(--red)">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:13px;height:13px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
    @endforeach
    </div>

    {{-- Pagination --}}
    @if($attendances->hasPages())
    <div class="pagination-wrap">
        <span>Showing {{ $attendances->firstItem() }}–{{ $attendances->lastItem() }} of {{ $attendances->total() }}</span>
        <div class="pagination-links">
            <a href="{{ $attendances->previousPageUrl() ?? '#' }}"
               class="page-link {{ !$attendances->previousPageUrl() ? 'disabled':'' }}">←</a>
            @foreach($attendances->getUrlRange(
                max(1, $attendances->currentPage()-2),
                min($attendances->lastPage(), $attendances->currentPage()+2)
            ) as $page => $url)
            <a href="{{ $url }}"
               class="page-link {{ $page == $attendances->currentPage() ? 'active':'' }}">
               {{ $page }}
            </a>
            @endforeach
            <a href="{{ $attendances->nextPageUrl() ?? '#' }}"
               class="page-link {{ !$attendances->nextPageUrl() ? 'disabled':'' }}">→</a>
        </div>
    </div>
    @endif
    @endif
</div>

@endsection

@push('scripts')
<script>
// Stats card click → status filter
function filterStatus(status) {
    document.getElementById('statusFilter').value = status;
    document.getElementById('filterForm').submit();
}
</script>
@endpush