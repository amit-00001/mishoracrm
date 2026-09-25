@extends('layouts.app')
@section('title', 'Tasks')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css"/>
<style>
/* ── Base ───────────────────────────────────────────────────────── */
.di { font-family: var(--font), sans-serif; }

/* ── Page header ────────────────────────────────────────────────── */
.tk-page-icon {
    width: 38px; height: 38px; border-radius: 10px; flex-shrink: 0;
    background: color-mix(in srgb, var(--accent) 14%, transparent);
    display: flex; align-items: center; justify-content: center;
    color: var(--accent); font-size: 18px;
}

/* ── Summary cards ──────────────────────────────────────────────── */
.di-summary {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 12px;
    margin-bottom: 18px;
}
@media(max-width:1100px) { .di-summary { grid-template-columns: repeat(3,1fr); } }
@media(max-width:600px)  { .di-summary { grid-template-columns: repeat(2,1fr); } }

.di-sum {
    --s-clr: var(--accent);
    background: var(--bg-surface); border: 1px solid var(--border-subtle);
    border-radius: 12px; padding: 14px 16px; text-decoration: none;
    display: flex; align-items: center; gap: 12px; position: relative; overflow: hidden;
    box-shadow: 0 1px 2px rgba(0,0,0,.03);
    transition: border-color .15s, transform .15s, box-shadow .15s, background .15s; cursor: pointer;
}
.di-sum:hover { border-color: color-mix(in srgb, var(--s-clr) 45%, var(--border-subtle)); transform: translateY(-1px); box-shadow: 0 4px 14px rgba(0,0,0,.06); }
.di-sum.active { border-color: var(--s-clr); background: color-mix(in srgb, var(--s-clr) 8%, var(--bg-surface)); }
.di-sum::before { content:''; position:absolute; top:0; left:0; bottom:0; width:3px; background: var(--s-clr); opacity:0; transition: opacity .15s; }
.di-sum.active::before { opacity: 1; }
.di-sum-icon {
    width: 34px; height: 34px; border-radius: 9px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 15px;
    background: color-mix(in srgb, var(--s-clr) 14%, transparent); color: var(--s-clr);
}
.di-sum-txt { min-width: 0; }
.di-sum-val { font-size: 20px; font-weight: 700; color: var(--text-100); font-family: var(--mono); letter-spacing: -.5px; line-height: 1.15; }
.di-sum.active .di-sum-val { color: var(--s-clr); }
.di-sum-lbl { font-size: 11.5px; color: var(--text-300); margin-top: 3px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* ── Toolbar ────────────────────────────────────────────────────── */
.di-toolbar {
    display:flex; align-items:center; gap:9px; flex-wrap:wrap;
    background: var(--bg-surface); border: 1px solid var(--border-subtle);
    border-radius: 12px; padding: 10px; margin-bottom:18px;
}
.di-fi {
    padding: 8px 12px; height: 36px;
    background: var(--bg-elevated); border: 1px solid var(--border-subtle);
    border-radius: 8px; font-size: 12.5px; color: var(--text-100); font-weight: 500;
    font-family: var(--font); outline: none;
    transition: border-color .15s, box-shadow .15s, background .15s;
    -webkit-appearance: none; appearance: none; cursor: pointer;
}
select.di-fi {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%239aa0ac' stroke-width='1.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 11px center; padding-right: 28px;
}
.di-fi:hover  { background: var(--bg-surface); border-color: var(--border-default); }
.di-fi:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-dim); background: var(--bg-surface); }
.di-sw  { position: relative; flex: 1; min-width: 190px; max-width: 280px; }
.di-sw svg { position: absolute; left: 11px; top: 50%; transform: translateY(-50%); pointer-events: none; }
.di-fi-s { width: 100%; padding-left: 33px; }

.view-toggle { display:flex; border:1px solid var(--border-subtle); border-radius:8px; overflow:hidden; margin-left:auto; background: var(--bg-elevated); }
.vt-btn {
    padding: 7px 14px; background: transparent; border: none;
    cursor: pointer; color: var(--text-300);
    transition: all .15s; display: flex; align-items: center; gap: 6px;
    font-size: 12.5px; font-family: var(--font); font-weight: 600; text-decoration: none;
}
.vt-btn.active { background: var(--accent); color: #fff; }
.vt-btn:not(.active):hover { background: var(--bg-surface); color: var(--text-100); }

/* ── Kanban — "Soft & Friendly" board ──────────────────────────── */
.kanban-scroll {
    overflow-x: auto; padding-bottom: 10px; -webkit-overflow-scrolling: touch;
    scrollbar-width: thin; scrollbar-color: var(--border-default) transparent;
}
.kanban-scroll::-webkit-scrollbar { height: 8px; }
.kanban-scroll::-webkit-scrollbar-thumb { background: var(--border-default); border-radius: 20px; }
.kanban-scroll::-webkit-scrollbar-track { background: transparent; }
.kanban-board  {
    display: flex; gap: 16px; align-items: flex-start;
    padding: 4px 2px 8px;
}

.k-col {
    width: 310px; flex-shrink: 0; display: flex; flex-direction: column;
    background: color-mix(in srgb, var(--text-100) 3.5%, var(--bg-base));
    border: 1px solid var(--border-default);
    border-radius: 16px; overflow: hidden;
    max-height: calc(100vh - 280px); min-height: 280px;
    box-shadow: var(--shadow-sm);
}
.k-col-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 16px; flex-shrink: 0;
    background: var(--bg-surface);
    border-bottom: 1px solid var(--border-default);
    position: relative;
}
.k-col-head-l { display: flex; align-items: center; gap: 8px; }
.k-col-ico { font-size: 16px; display: inline-flex; }
.k-col-head { background: color-mix(in srgb, var(--stage-color, var(--accent)) 8%, var(--bg-surface)); }
.tc-cnt.is-overdue { color: var(--red); background: var(--red-dim); }
.tc-who { display: inline-flex; align-items: center; gap: 6px; min-width: 0; }
.tc-who-name { font-size: 11.5px; font-weight: 600; color: var(--text-200); max-width: 72px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.k-col-title  { font-size: 13.5px; font-weight: 700; letter-spacing: -.1px; }
.k-col-count  { font-size: 11px; font-family: var(--mono); padding: 2px 9px; border-radius: 20px; font-weight: 700; color: #fff; box-shadow: 0 2px 6px rgba(0,0,0,.15); }

/* Drop zone — scrolls internally so one busy column never stretches the whole page */
.k-drop-zone {
    flex: 1; min-height: 120px; padding: 12px;
    display: flex; flex-direction: column; gap: 10px;
    transition: background .2s;
    overflow-y: auto;
}
.k-view-all-btn {
    display: flex; align-items: center; justify-content: center; gap: 5px;
    margin-top: 2px; padding: 9px; background: var(--bg-surface);
    border: 1px solid var(--border-default); border-radius: 10px;
    font-size: 11.5px; font-weight: 600; color: var(--accent); cursor: pointer;
    font-family: var(--font); transition: all .15s; text-decoration: none;
    flex-shrink: 0;
}
.k-view-all-btn:hover { border-color: var(--accent); background: var(--accent-dim); }
.k-drop-zone.drag-over {
    background: var(--accent-dim);
    outline: 2px dashed var(--accent);
    outline-offset: -6px;
}

/* Task card */
.task-card {
    background: var(--bg-surface); border: 1px solid var(--border-default);
    border-radius: 12px; padding: 14px; cursor: grab;
    box-shadow: var(--shadow-sm);
    transition: box-shadow .18s, transform .18s, opacity .15s, border-color .18s;
    user-select: none; position: relative; overflow: hidden;
}
.task-card::before {
    content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px;
    background: var(--card-accent, var(--border-strong));
}
.task-card:hover  { box-shadow: var(--shadow-md); transform: translateY(-2px); border-color: var(--border-strong); }
.task-card.is-dragging { opacity: .5; cursor: grabbing; transform: scale(.97) rotate(-1.5deg); box-shadow: var(--shadow-lg); }

.tc-chip { display:inline-flex; align-items:center; font-size:10px; font-weight:700; color:#fff; padding:3px 10px; border-radius:20px; margin-bottom:10px; letter-spacing:.3px; text-transform:uppercase; }
.tc-top     { display:flex; align-items:flex-start; justify-content:space-between; gap:8px; margin-bottom:4px; }
.tc-title   { font-size: 13.5px; font-weight: 700; color: var(--text-100); line-height: 1.45; flex: 1; }
.tc-grip    { width:16px; height:20px; flex-shrink:0; opacity:0; transition:opacity .15s; display:flex; align-items:center; justify-content:center; color:var(--text-400); cursor:grab; }
.task-card:hover .tc-grip { opacity: 1; }
.tc-desc    { font-size: 12px; color: var(--text-300); margin-bottom: 4px; line-height: 1.55; }
.tc-foot    { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-top:12px; padding-top:11px; border-top:1px solid var(--border-subtle); }
.tc-cnts    { display:flex; gap:8px; }
.tc-cnt     { display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:600; color: var(--text-300); background: var(--bg-hover); padding: 3px 8px; border-radius: 6px; }
.tc-right   { display:flex; align-items:center; gap:6px; }
.tc-date    { font-size: 11px; color: var(--text-400); font-family: var(--mono); }
.tc-av      {
    width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center;
    font-size:10px; font-weight:700; box-shadow: 0 0 0 2px var(--bg-surface);
}
.tc-view-btn {
    width: 26px; height: 26px; border-radius: 8px;
    background: var(--bg-hover); display: flex; align-items: center; justify-content: center;
    color: var(--text-300); text-decoration: none; transition: all .15s; flex-shrink: 0;
}
.tc-view-btn:hover { background: var(--accent); color: #fff; transform: translateX(1px); }
.k-empty { text-align:center; padding:34px 14px; font-size:12px; color:var(--text-400); line-height:1.6; border: 1.5px dashed var(--border-strong); border-radius: 12px; }
.k-empty i { opacity: .5; }

/* Per-card status picker */
.tc-status-row { margin-top: 10px; }
.tc-status-select {
    width: 100%; padding: 0 28px 0 10px; min-height: 32px !important; font-size: 11.5px; font-weight: 600;
    background-color: var(--bg-hover) !important; border: 1px solid transparent !important;
    border-radius: 8px !important; color: var(--text-200);
    cursor: pointer; outline: none; appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%239aa0ac' stroke-width='1.5' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat; background-position: right 10px center;
}
.tc-status-select:hover  { border-color: var(--border-strong) !important; }
.tc-status-select:focus { background-color: var(--bg-surface) !important; border-color: var(--accent) !important; box-shadow: 0 0 0 3px var(--accent-glow); }
.k-add-btn {
    display:flex; align-items:center; justify-content:center; gap:6px; flex-shrink:0;
    margin: 0 12px 12px; padding: 10px;
    border: 1.5px dashed var(--border-strong); border-radius: 12px;
    font-size: 12px; font-weight: 600; color: var(--text-300); cursor: pointer;
    font-family: var(--font); transition: all .15s; text-decoration: none; background: transparent;
}
.k-add-btn:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-dim); border-style: solid; }

/* Toast */
.move-toast {
    position: fixed; bottom: 24px; left: 50%;
    transform: translateX(-50%) translateY(80px);
    background: var(--accent); color: #fff;
    padding: 10px 20px; border-radius: 10px;
    font-size: 13px; font-weight: 500;
    display: flex; align-items: center; gap: 8px;
    box-shadow: 0 4px 24px rgba(0,0,0,.2);
    transition: transform .35s cubic-bezier(.34,1.56,.64,1), opacity .3s;
    z-index: 9999; opacity: 0; pointer-events: none;
}
.move-toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
.move-toast.error { background: var(--red); }

/* ── List view ──────────────────────────────────────────────────── */
.list-wrap { background:var(--bg-surface); border:1px solid var(--border-subtle); border-radius:12px; overflow:hidden; box-shadow: 0 1px 3px rgba(0,0,0,.03); }
.list-head { display:flex; align-items:center; justify-content:space-between; padding:13px 18px; border-bottom:1px solid var(--border-subtle); background:var(--bg-elevated); }
.list-count { font-size:12px; color:var(--text-300); }
.list-count strong { color:var(--text-100); font-weight:700; }

.di-table { width:100%; border-collapse:collapse; min-width:700px; }
.di-table thead tr { background:var(--bg-elevated); }
.di-table th { padding:11px 14px; text-align:left; font-size:10.5px; font-weight:700; color:var(--text-300); text-transform:uppercase; letter-spacing:.6px; border-bottom:1px solid var(--border-subtle); white-space:nowrap; }
.di-table th a { display:inline-flex; align-items:center; gap:4px; color:inherit; text-decoration:none; }
.di-table th a:hover { color:var(--text-100); }
.di-table th a i { font-size:12px; opacity:.6; }
.di-table th a.sorted i { opacity:1; color:var(--accent); }
.di-table td { padding:12px 14px; font-size:13px; color:var(--text-100); border-bottom:1px solid var(--border-subtle); vertical-align:middle; }
.di-table tr:last-child td { border-bottom:none; }
.di-table tbody tr { transition: background .12s; }
.di-table tbody tr:hover td { background:var(--bg-elevated); }

.st-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600; white-space:nowrap; }

.row-actions { display:flex; align-items:center; gap:4px; opacity:0; transition:opacity .15s; }
.di-table tbody tr:hover .row-actions { opacity:1; }
.act-btn {
    width:28px; height:28px; display:flex; align-items:center; justify-content:center;
    border-radius:7px; border:1px solid var(--border-subtle);
    background:var(--bg-surface); cursor:pointer; color:var(--text-300); text-decoration:none; transition:all .15s;
}
.act-btn:hover     { background:var(--bg-elevated); color:var(--text-100); border-color:var(--border-default); transform: translateY(-1px); }
.act-btn.del:hover { background:var(--red-dim); border-color:var(--red); color:var(--red); }

.pag-wrap { display:flex; align-items:center; justify-content:space-between; padding:13px 18px; border-top:1px solid var(--border-subtle); background:var(--bg-elevated); flex-wrap:wrap; gap:8px; }
.pag-info { font-size:12px; color:var(--text-300); }
.pag-info strong { color:var(--text-100); font-weight:600; }
.pag-btns { display:flex; gap:4px; }
.pg-btn { min-width:32px; height:32px; padding:0 9px; display:inline-flex; align-items:center; justify-content:center; border-radius:7px; border:1px solid var(--border-subtle); background:var(--bg-surface); font-size:13px; font-weight:500; cursor:pointer; color:var(--text-200); text-decoration:none; transition:all .15s; font-family:var(--font); }
.pg-btn:hover  { background:var(--bg-elevated); color:var(--text-100); border-color:var(--border-default); }
.pg-btn.active { background:var(--accent); border-color:var(--accent); color:#fff; }
.pg-btn.disabled { opacity:.35; pointer-events:none; }

.di-empty { text-align:center; padding:64px 24px; }
.di-empty-icon { width:52px; height:52px; border-radius:14px; background: color-mix(in srgb, var(--accent) 10%, var(--bg-elevated)); display:flex; align-items:center; justify-content:center; margin:0 auto 14px; }
.di-empty-title { font-size:14.5px; font-weight:700; color:var(--text-100); margin-bottom:5px; }
.di-empty-sub   { font-size:13px; color:var(--text-300); margin-bottom:18px; }

/* ── MOBILE TASK CARDS (list view, <768px) ────────────────────── */
.tk-mobile-list{display:none}
@media(max-width:768px){
    .tk-table-wrap{display:none}
    .tk-mobile-list{display:flex;flex-direction:column;gap:10px;padding:14px}
}
.tk-card{background:var(--bg-surface);border:1px solid var(--border-default);border-radius:10px;padding:14px;cursor:pointer;transition:border-color .15s,box-shadow .15s}
.tk-card:active{border-color:var(--accent)}
.tk-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:10px}
.tk-title{font-size:14px;font-weight:700;color:var(--text-100);line-height:1.3;word-break:break-word}
.tk-desc{font-size:11.5px;color:var(--text-400);margin-top:3px;line-height:1.4}
.tk-badges{display:flex;flex-direction:column;align-items:flex-end;gap:6px;flex-shrink:0}
.tk-meta{display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-bottom:10px;font-size:11.5px}
.tk-meta-lbl{color:var(--text-400)}
.tk-meta-val{color:var(--text-200);font-weight:600}
.tk-foot{display:flex;align-items:center;justify-content:space-between;gap:8px;padding-top:10px;border-top:1px solid var(--border-subtle)}
.tk-assigned{display:flex;align-items:center;gap:6px;min-width:0}
.tk-acts{display:flex;align-items:center;gap:6px;flex-shrink:0}
</style>
@endpush

@section('content')

@php
    $cfgStages   = config('task_fields.stages');
    $cfgPriorities = config('task_fields.priorities');
    $currentView = $view ?? 'kanban';
    $currentStage = request('stage', '');
    $sortCol = request('sort', 'created_at');
    $sortDir = request('dir', 'desc');

    $avColors = [
        ['var(--accent-dim)','var(--accent)'],
        ['var(--green-dim)','var(--green)'],
        ['var(--amber-dim)','var(--amber)'],
        ['var(--purple-dim)','var(--purple)'],
    ];

    $initials = fn(string $name): string =>
        collect(explode(' ', $name))->map(fn($p) => strtoupper($p[0] ?? ''))->join('');

    $allCount = $stageSummary->sum();

    $stageIcons = [
        'pending'     => 'ti-clock',
        'in_progress' => 'ti-loader-2',
        'completed'   => 'ti-circle-check',
        'cancelled'   => 'ti-circle-x',
    ];
@endphp

<div class="di">

    {{-- Page header --}}
    <div class="page-head">
        <div style="display:flex;align-items:center;gap:12px">
            <div class="tk-page-icon"><i class="ti ti-checklist"></i></div>
            <div>
                <div class="page-title">Tasks</div>
                <div style="font-size:12px;color:var(--text-300);margin-top:2px">
                    Track and manage all tasks
                </div>
            </div>
        </div>
        <div style="display:flex;gap:8px">
            <a href="{{ route('tenant.task-templates.index') }}" class="btn btn-secondary">
                <i class="ti ti-template" style="font-size:14px"></i> Templates
            </a>
            <a href="{{ route('tenant.tasks.create') }}" class="btn btn-primary">
                <i class="ti ti-plus" style="font-size:14px"></i> Add Task
            </a>
        </div>
    </div>

    @if(session('success'))
    <div style="display:flex;align-items:center;gap:10px;padding:11px 15px;background:var(--green-dim);border:1px solid rgba(45,212,160,.3);border-radius:8px;margin-bottom:14px;font-size:13px;color:var(--green);font-weight:500">
        <i class="ti ti-circle-check" style="font-size:16px"></i>
        {{ session('success') }}
    </div>
    @endif

    {{-- Summary --}}
    <div class="di-summary">
        <a href="{{ route('tenant.tasks.index', array_merge(request()->except(['stage','page']), ['view'=>$currentView])) }}"
           class="di-sum {{ $currentStage === '' ? 'active' : '' }}" style="--s-clr:var(--accent)">
            <div class="di-sum-icon"><i class="ti ti-list-check"></i></div>
            <div class="di-sum-txt">
                <div class="di-sum-val">{{ $allCount }}</div>
                <div class="di-sum-lbl">All Tasks</div>
            </div>
        </a>
        @foreach($cfgStages as $slug => $stage)
        @php
            $ss   = $stageSummary->get($slug);
            $sc   = is_object($ss) ? ($ss->count ?? 0) : (int) ($ss ?? 0);
        @endphp
        <a href="{{ route('tenant.tasks.index', array_merge(request()->except(['stage','page']), ['stage'=>$slug,'view'=>$currentView])) }}"
           class="di-sum {{ $currentStage === $slug ? 'active' : '' }}" style="--s-clr:{{ $stage['color'] }}">
            <div class="di-sum-icon"><i class="ti {{ $stageIcons[$slug] ?? 'ti-circle' }}"></i></div>
            <div class="di-sum-txt">
                <div class="di-sum-val">{{ $sc }}</div>
                <div class="di-sum-lbl">{{ $stage['label'] }}</div>
            </div>
        </a>
        @endforeach
    </div>

    {{-- Toolbar --}}
    <form method="GET" action="{{ route('tenant.tasks.index') }}" id="filterForm">
        <input type="hidden" name="view" value="{{ $currentView }}">
        @if(request('stage'))  <input type="hidden" name="stage"  value="{{ request('stage') }}"> @endif
        @if(request('sort'))   <input type="hidden" name="sort"   value="{{ request('sort') }}"> @endif
        @if(request('dir'))    <input type="hidden" name="dir"    value="{{ request('dir') }}"> @endif

        <div class="di-toolbar">
            {{-- Search --}}
            <div class="di-sw">
                <svg width="14" height="14" fill="none" stroke="var(--text-300)" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
                </svg>
                <input type="text" name="search" class="di-fi di-fi-s"
                       placeholder="Search tasks..."
                       value="{{ request('search') }}" autocomplete="off"/>
            </div>

            {{-- Assigned to --}}
            <select name="assigned_to" class="di-fi" style="min-width:130px" onchange="this.form.submit()">
                <option value="">All Assignees</option>
                @foreach($staffList as $staff)
                <option value="{{ $staff->id }}" {{ request('assigned_to') == $staff->id ? 'selected':'' }}>
                    {{ $staff->name }}
                </option>
                @endforeach
            </select>

            {{-- Priority --}}
            <select name="priority" class="di-fi" onchange="this.form.submit()">
                <option value="">All Priority</option>
                @foreach($cfgPriorities as $key => $p)
                <option value="{{ $key }}" {{ request('priority') === $key ? 'selected':'' }}>
                    {{ $p['label'] }}
                </option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-secondary">
                <i class="ti ti-filter" style="font-size:13px"></i> Filter
            </button>

            <button type="button" class="btn btn-secondary" onclick="saveCurrentFilter()">
                <i class="ti ti-bookmark-plus" style="font-size:13px"></i> Save Filter
            </button>

            @if(request()->hasAny(['search','assigned_to','priority']))
            <a href="{{ route('tenant.tasks.index', array_merge(request()->only(['view','stage','sort','dir']))) }}"
               class="btn btn-secondary">
                <i class="ti ti-x" style="font-size:13px"></i> Clear
            </a>
            @endif

            {{-- View toggle --}}
            <div class="view-toggle">
                <a href="{{ route('tenant.tasks.index', array_merge(request()->except('view'), ['view'=>'kanban'])) }}"
                   class="vt-btn {{ $currentView === 'kanban' ? 'active':'' }}">
                    <i class="ti ti-layout-columns" style="font-size:13px"></i> Kanban
                </a>
                <a href="{{ route('tenant.tasks.index', array_merge(request()->except('view'), ['view'=>'list'])) }}"
                   class="vt-btn {{ $currentView === 'list' ? 'active':'' }}">
                    <i class="ti ti-list" style="font-size:13px"></i> List
                </a>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('tenant.tasks.saved_filters.store') }}" id="saveFilterForm" style="display:none">
        @csrf
        <input type="hidden" name="name" id="saveFilterName">
        <input type="hidden" name="view" value="{{ $currentView }}">
        <input type="hidden" name="search" value="{{ request('search') }}">
        <input type="hidden" name="stage" value="{{ request('stage') }}">
        <input type="hidden" name="priority" value="{{ request('priority') }}">
        <input type="hidden" name="assigned_to" value="{{ request('assigned_to') }}">
        <input type="hidden" name="sort" value="{{ request('sort') }}">
        <input type="hidden" name="dir" value="{{ request('dir') }}">
    </form>

    @if($savedFilters->isNotEmpty())
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:16px">
        <span style="font-size:11.5px;color:var(--text-400);font-weight:600">SAVED:</span>
        @foreach($savedFilters as $sf)
        <div style="display:inline-flex;align-items:center;gap:6px;padding:5px 6px 5px 12px;border-radius:999px;background:var(--bg-elevated);border:1px solid var(--border-subtle);font-size:12px">
            <a href="{{ route('tenant.tasks.index', $sf->filters) }}" style="text-decoration:none;color:var(--text-100)">{{ $sf->name }}</a>
            <form method="POST" action="{{ route('tenant.tasks.saved_filters.destroy', $sf->id) }}" onsubmit="return confirm('Remove this saved filter?')" style="display:inline">
                @csrf @method('DELETE')
                <button type="submit" style="background:none;border:none;cursor:pointer;color:var(--text-400);font-size:11px;padding:0;display:flex"><i class="ti ti-x"></i></button>
            </form>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ═══ KANBAN VIEW ════════════════════════════════════════════ --}}
    @if($currentView === 'kanban')

    <div class="kanban-scroll">
        <div class="kanban-board" id="kanbanBoard">

            @foreach($cfgStages as $slug => $stage)
            @php
                $colTasks = $kanbanTasks->get($slug, collect());
                $ss       = $stageSummary->get($slug);
                $colCount = is_object($ss) ? ($ss->count ?? 0) : (int) ($ss ?? 0);
                $hasMore  = $colCount > $colTasks->count();
            @endphp

            <div class="k-col" data-stage="{{ $slug }}" style="--stage-color:{{ $stage['color'] }}"
                 ondragover="taskDragOver(event)"
                 ondragenter="taskDragEnter(event)"
                 ondragleave="taskDragLeave(event)"
                 ondrop="taskDrop(event)">

                {{-- Column header --}}
                <div class="k-col-head" style="border-top:3px solid {{ $stage['color'] }}">
                    <div class="k-col-head-l">
                        <span class="k-col-ico" style="color:{{ $stage['color'] }}"><i class="ti {{ $stageIcons[$slug] ?? 'ti-circle' }}"></i></span>
                        <span class="k-col-title" style="color:{{ $stage['text_color'] }}">
                            {{ $stage['label'] }}
                        </span>
                        <span class="k-col-count" id="count_{{ $slug }}" data-total="{{ $colCount }}"
                              style="background:{{ $stage['color'] }}">
                            {{ $colCount }}
                        </span>
                    </div>
                </div>

                {{-- Drop zone --}}
                <div class="k-drop-zone" id="zone_{{ $slug }}" data-stage="{{ $slug }}">

                    @if($colTasks->isEmpty())
                    <div class="k-empty" id="empty_{{ $slug }}">
                        <i class="ti ti-inbox" style="font-size:20px;display:block;margin-bottom:6px"></i>
                        No tasks here
                    </div>
                    @else
                    <div class="k-empty" id="empty_{{ $slug }}" style="display:none">
                        <i class="ti ti-inbox" style="font-size:20px;display:block;margin-bottom:6px"></i>
                        No tasks here
                    </div>
                    @endif

                    @foreach($colTasks as $i => $task)
                    @php
                        [$avBg, $avTx] = $avColors[$i % 4];
                        $priority = $cfgPriorities[$task->priority] ?? null;
                        $taskInitials = $task->assignedTo ? $initials($task->assignedTo->name) : '';
                    @endphp

                    <div class="task-card"
                         style="--card-accent:{{ $priority['color'] ?? 'var(--border-strong)' }}"
                         draggable="true"
                         id="card_{{ $task->id }}"
                         data-task-id="{{ $task->id }}"
                         data-stage="{{ $slug }}"
                         data-title="{{ e($task->title) }}"
                         ondragstart="taskDragStart(event)"
                         ondragend="taskDragEnd(event)">

                        @if($priority)
                        <span class="tc-chip" style="background:{{ $priority['color'] }}">{{ $priority['label'] }}</span>
                        @endif

                        <div class="tc-top">
                            <div class="tc-title">
                                {{ $task->title }}
                                @if($task->isOverdue())
                                <span style="margin-left:5px;font-size:9px;font-weight:700;color:var(--red);background:var(--red-dim);padding:1px 6px;border-radius:99px;white-space:nowrap">OVERDUE</span>
                                @endif
                            </div>
                            <div class="tc-grip">
                                <i class="ti ti-grip-vertical"></i>
                            </div>
                        </div>

                        @if($task->description)
                        <div class="tc-desc">
                            {{ \Illuminate\Support\Str::limit($task->description, 80) }}
                        </div>
                        @endif

                        {{-- Status picker — reliable alternative to drag (touch / webview) --}}
                        <div class="tc-status-row">
                            <select class="tc-status-select no-select2" aria-label="Change status"
                                    draggable="false" onmousedown="event.stopPropagation()">
                                @foreach($cfgStages as $sSlug => $sStage)
                                <option value="{{ $sSlug }}" {{ $sSlug === $slug ? 'selected' : '' }}>
                                    {{ $sStage['label'] }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="tc-foot">
                            <div class="tc-cnts">
                                @if($task->due_at)
                                <span class="tc-cnt {{ $task->isOverdue() ? 'is-overdue' : '' }}">
                                    <i class="ti ti-calendar" style="font-size:11px"></i>
                                    {{ \Carbon\Carbon::parse($task->due_at)->format('M d') }}
                                </span>
                                @endif
                            </div>
                            <div class="tc-right">
                                @if($task->assignedTo)
                                <div class="tc-who" title="{{ $task->assignedTo->name }}">
                                    <div class="tc-av" style="background:{{ $avBg }};color:{{ $avTx }}">
                                        {{ substr($taskInitials,0,2) }}
                                    </div>
                                    <span class="tc-who-name">{{ \Illuminate\Support\Str::before($task->assignedTo->name, ' ') }}</span>
                                </div>
                                @endif
                                <a href="{{ route('tenant.tasks.show', $task->id) }}"
                                   class="tc-view-btn" onclick="event.stopPropagation()">
                                    <i class="ti ti-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach

                    @if($hasMore)
                    <a href="{{ route('tenant.tasks.index',array_merge(request()->except(['page']),['stage'=>$slug,'view'=>'list'])) }}"
                       class="k-view-all-btn">
                        View all {{ number_format($colCount) }} in list <i class="ti ti-arrow-right" style="font-size:11px"></i>
                    </a>
                    @endif
                </div>

                {{-- Add task to this stage --}}
                <a href="{{ route('tenant.tasks.create', ['status'=>$slug]) }}" class="k-add-btn">
                    <i class="ti ti-plus"></i> Add Task
                </a>

            </div>
            @endforeach

        </div>
    </div>

    {{-- Toast --}}
    <div class="move-toast" id="moveToast">
        <i class="ti ti-check"></i>
        <span id="toastText">Task moved!</span>
    </div>

    {{-- ═══ LIST VIEW ══════════════════════════════════════════════ --}}
    @else

    <div class="list-wrap">
        <div class="list-head">
            <div class="list-count" id="listCountLabel">
                @if(isset($tasks) && $tasks->total())
                    Showing <strong>{{ $tasks->firstItem() }}–{{ $tasks->lastItem() }}</strong>
                    of <strong>{{ number_format($tasks->total()) }}</strong> tasks
                @else
                    <strong>0</strong> tasks found
                @endif
            </div>

            {{-- Bulk action toolbar — shown once 1+ rows are selected --}}
            <div id="bulkToolbar" style="display:none;align-items:center;gap:8px;flex-wrap:wrap">
                <span style="font-size:12px;color:var(--text-300)"><strong id="bulkCount">0</strong> selected</span>

                <select id="bulkStatus" class="di-fi" style="height:32px">
                    <option value="">Set status...</option>
                    @foreach($cfgStages as $slug => $stage)
                    <option value="{{ $slug }}">{{ $stage['label'] }}</option>
                    @endforeach
                </select>

                <select id="bulkAssign" class="di-fi" style="height:32px">
                    <option value="">Assign to...</option>
                    <option value="__unassign__">— Unassign —</option>
                    @foreach($staffList as $staff)
                    <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                    @endforeach
                </select>

                <button type="button" class="btn btn-secondary" onclick="submitBulkAction('delete')">
                    <i class="ti ti-trash" style="font-size:13px"></i> Delete
                </button>
            </div>
        </div>

        <form id="bulkActionForm" method="POST" action="{{ route('tenant.tasks.bulk_action') }}" style="display:none">
            @csrf
            <input type="hidden" name="action" id="bulkActionInput">
            <input type="hidden" name="value" id="bulkValueInput">
            <div id="bulkIdsContainer"></div>
        </form>

        @if(!isset($tasks) || $tasks->isEmpty())
        <div class="di-empty">
            <div class="di-empty-icon">
                <i class="ti ti-checklist" style="font-size:22px;color:var(--text-300)"></i>
            </div>
            <div class="di-empty-title">No tasks found</div>
            <div class="di-empty-sub">Start by creating your first task</div>
            <a href="{{ route('tenant.tasks.create') }}" class="btn btn-primary">Add Task</a>
        </div>
        @else
        <div class="tk-table-wrap" style="overflow-x:auto">
            <table class="di-table">
                @php
                    $sortLink = fn(string $col) => route('tenant.tasks.index', array_merge(
                        request()->except(['page']),
                        ['sort' => $col, 'dir' => ($sortCol === $col && $sortDir === 'asc') ? 'desc' : 'asc']
                    ));
                @endphp
                <thead>
                    <tr>
                        <th width="34">
                            <input type="checkbox" id="selectAllTasks">
                        </th>
                        <th>
                            <a href="{{ $sortLink('title') }}" class="{{ $sortCol === 'title' ? 'sorted' : '' }}">
                                Title
                                @if($sortCol === 'title')<i class="ti ti-arrow-{{ $sortDir === 'asc' ? 'up' : 'down' }}"></i>@endif
                            </a>
                        </th>
                        <th>
                            <a href="{{ $sortLink('status') }}" class="{{ $sortCol === 'status' ? 'sorted' : '' }}">
                                Status
                                @if($sortCol === 'status')<i class="ti ti-arrow-{{ $sortDir === 'asc' ? 'up' : 'down' }}"></i>@endif
                            </a>
                        </th>
                        <th>
                            <a href="{{ $sortLink('priority') }}" class="{{ $sortCol === 'priority' ? 'sorted' : '' }}">
                                Priority
                                @if($sortCol === 'priority')<i class="ti ti-arrow-{{ $sortDir === 'asc' ? 'up' : 'down' }}"></i>@endif
                            </a>
                        </th>
                        <th>
                            <a href="{{ $sortLink('due_at') }}" class="{{ $sortCol === 'due_at' ? 'sorted' : '' }}">
                                Due Date
                                @if($sortCol === 'due_at')<i class="ti ti-arrow-{{ $sortDir === 'asc' ? 'up' : 'down' }}"></i>@endif
                            </a>
                        </th>
                        <th>Assigned To</th>
                        <th width="100"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tasks as $i => $task)
                    @php
                        [$avBg,$avTx] = $avColors[$i % 4];
                        $status   = $cfgStages[$task->status]   ?? ['label'=>ucfirst($task->status),'color'=>'#999','bg'=>'#eee','text_color'=>'#555'];
                        $priority = $cfgPriorities[$task->priority] ?? null;
                        $taskInitials = $task->assignedTo ? $initials($task->assignedTo->name) : '';
                    @endphp
                    <tr>
                        <td>
                            <input type="checkbox" class="task-select" value="{{ $task->id }}">
                        </td>
                        <td data-label="Title">
                            <div style="font-weight:600">
                                <a href="{{ route('tenant.tasks.show', $task->id) }}"
                                   style="text-decoration:none;color:inherit">
                                    {{ $task->title }}
                                </a>
                                @if($task->isOverdue())
                                <span style="margin-left:6px;font-size:10px;font-weight:700;color:var(--red);background:rgba(224,82,82,.12);padding:2px 7px;border-radius:99px">OVERDUE</span>
                                @endif
                            </div>
                            @if($task->description)
                            <div style="font-size:12px;color:var(--text-400)">
                                {{ \Illuminate\Support\Str::limit($task->description, 60) }}
                            </div>
                            @endif
                        </td>
                        <td data-label="Status">
                            <span class="st-badge"
                                  style="background:{{ $status['bg'] }};color:{{ $status['text_color'] }};border:1px solid {{ $status['color'] }}30">
                                {{ $status['label'] }}
                            </span>
                        </td>
                        <td data-label="Priority">
                            @if($priority)
                            <span class="st-badge"
                                  style="background:{{ $priority['bg'] }};color:{{ $priority['color'] }};border:1px solid {{ $priority['color'] }}30">
                                {{ $priority['label'] }}
                            </span>
                            @else —
                            @endif
                        </td>
                        <td data-label="Due Date">
                            @if($task->due_at)
                            <span style="font-size:12px;{{ $task->isOverdue() ? 'color:var(--red)':'' }}">
                                {{ \Carbon\Carbon::parse($task->due_at)->format('d M Y, h:i A') }}
                            </span>
                            @else — @endif
                        </td>
                        <td data-label="Assigned To">
                            @if($task->assignedTo)
                            <div style="display:flex;align-items:center;gap:8px">
                                <div class="tc-av" style="background:{{ $avBg }};color:{{ $avTx }}">
                                    {{ substr($taskInitials,0,2) }}
                                </div>
                                <span>{{ $task->assignedTo->name }}</span>
                            </div>
                            @else
                            <span style="color:var(--text-400)">Unassigned</span>
                            @endif
                        </td>
                        <td>
                            <div class="row-actions">
                                <a href="{{ route('tenant.tasks.show', $task->id) }}" class="act-btn">
                                    <i class="ti ti-eye"></i>
                                </a>
                                <a href="{{ route('tenant.tasks.edit', $task->id) }}" class="act-btn">
                                    <i class="ti ti-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('tenant.tasks.destroy', $task->id) }}"
                                      style="display:inline" onsubmit="return confirm('Delete this task?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="act-btn del">
                                        <i class="ti ti-trash"></i>
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
        <div class="tk-mobile-list">
        @foreach($tasks as $i => $task)
        @php
            [$avBg,$avTx] = $avColors[$i % 4];
            $status   = $cfgStages[$task->status]   ?? ['label'=>ucfirst($task->status),'color'=>'#999','bg'=>'#eee','text_color'=>'#555'];
            $priority = $cfgPriorities[$task->priority] ?? null;
            $taskInitials = $task->assignedTo ? $initials($task->assignedTo->name) : '';
        @endphp
        <div class="tk-card" onclick="window.location='{{ route('tenant.tasks.show', $task->id) }}'">
            <div class="tk-top">
                <div style="min-width:0">
                    <div class="tk-title">{{ $task->title }}</div>
                    @if($task->description)
                    <div class="tk-desc">{{ \Illuminate\Support\Str::limit($task->description, 80) }}</div>
                    @endif
                </div>
                <div class="tk-badges">
                    <span class="st-badge" style="background:{{ $status['bg'] }};color:{{ $status['text_color'] }};border:1px solid {{ $status['color'] }}30">{{ $status['label'] }}</span>
                    @if($priority)
                    <span class="st-badge" style="background:{{ $priority['bg'] }};color:{{ $priority['color'] }};border:1px solid {{ $priority['color'] }}30">{{ $priority['label'] }}</span>
                    @endif
                </div>
            </div>
            <div class="tk-meta">
                <span>
                    <span class="tk-meta-lbl">Due: </span>
                    @if($task->due_at)
                    <span class="tk-meta-val" style="{{ $task->isOverdue() ? 'color:var(--red)' : '' }}">{{ \Carbon\Carbon::parse($task->due_at)->format('d M Y, h:i A') }}</span>
                    @else <span class="tk-meta-val">—</span> @endif
                </span>
            </div>
            <div class="tk-foot">
                <div class="tk-assigned">
                    @if($task->assignedTo)
                    <div class="tc-av" style="background:{{ $avBg }};color:{{ $avTx }}">{{ substr($taskInitials,0,2) }}</div>
                    <span style="font-size:12px;color:var(--text-200);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $task->assignedTo->name }}</span>
                    @else<span style="font-size:12px;color:var(--text-400)">Unassigned</span>@endif
                </div>
                <div class="tk-acts" onclick="event.stopPropagation()">
                    <a href="{{ route('tenant.tasks.show', $task->id) }}" class="act-btn">
                        <i class="ti ti-eye"></i>
                    </a>
                    <a href="{{ route('tenant.tasks.edit', $task->id) }}" class="act-btn">
                        <i class="ti ti-edit"></i>
                    </a>
                    <form method="POST" action="{{ route('tenant.tasks.destroy', $task->id) }}"
                          style="display:inline" onsubmit="return confirm('Delete this task?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="act-btn del">
                            <i class="ti ti-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endforeach
        </div>

        {{-- Pagination --}}
        @if($tasks->hasPages())
        <div class="pag-wrap">
            <div class="pag-info">
                Showing <strong>{{ $tasks->firstItem() }}</strong>–<strong>{{ $tasks->lastItem() }}</strong>
                of <strong>{{ $tasks->total() }}</strong>
            </div>
            <div class="pag-btns">
                <a href="{{ $tasks->previousPageUrl() ?? '#' }}"
                   class="pg-btn {{ !$tasks->previousPageUrl() ? 'disabled':'' }}">← Prev</a>
                @foreach($tasks->getUrlRange(max(1,$tasks->currentPage()-2), min($tasks->lastPage(),$tasks->currentPage()+2)) as $page => $url)
                <a href="{{ $url }}" class="pg-btn {{ $page==$tasks->currentPage() ? 'active':'' }}">{{ $page }}</a>
                @endforeach
                <a href="{{ $tasks->nextPageUrl() ?? '#' }}"
                   class="pg-btn {{ !$tasks->nextPageUrl() ? 'disabled':'' }}">Next →</a>
            </div>
        </div>
        @endif
        @endif
    </div>

    @endif

</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // ── Config from PHP ───────────────────────────────────────────
    const CSRF     = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const STAGES   = @json(config('task_fields.stages'));
    // Absolute URL template — {id} placeholder swapped per card. Built from the
    // named route so it works no matter what path the board is viewed at.
    const STAGE_URL_TPL = @json(route('tenant.tasks.update_stage', ['id' => '__ID__']));

    function stageUrl(taskId) {
        return STAGE_URL_TPL.replace('__ID__', encodeURIComponent(taskId));
    }

    // ── Drag state ────────────────────────────────────────────────
    let dragCard  = null;
    let dragStage = null;
    let dragZone  = null;

    // ── DragStart ─────────────────────────────────────────────────
    window.taskDragStart = function (e) {
        dragCard  = e.currentTarget;
        dragStage = dragCard.dataset.stage;
        dragZone  = dragCard.closest('.k-drop-zone');

        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', dragCard.dataset.taskId);

        requestAnimationFrame(() => dragCard?.classList.add('is-dragging'));
    };

    // ── DragEnd ───────────────────────────────────────────────────
    window.taskDragEnd = function () {
        if (dragCard) dragCard.classList.remove('is-dragging');
        document.querySelectorAll('.k-drop-zone').forEach(z => z.classList.remove('drag-over'));
        dragCard = null;
        dragStage = null;
        dragZone = null;
    };

    // ── DragOver ──────────────────────────────────────────────────
    window.taskDragOver = function (e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    };

    // ── DragEnter ─────────────────────────────────────────────────
    window.taskDragEnter = function (e) {
        e.preventDefault();
        e.currentTarget.querySelector('.k-drop-zone')?.classList.add('drag-over');
    };

    // ── DragLeave ─────────────────────────────────────────────────
    window.taskDragLeave = function (e) {
        if (!e.currentTarget.contains(e.relatedTarget)) {
            e.currentTarget.querySelector('.k-drop-zone')?.classList.remove('drag-over');
        }
    };

    // ── Drop ──────────────────────────────────────────────────────
    // Handler is bound to the whole .k-col so a drop anywhere in the column
    // (header, empty space, on a card, the "Add Task" button) still counts.
    window.taskDrop = function (e) {
        e.preventDefault();

        const zone = e.currentTarget.querySelector('.k-drop-zone');
        if (zone) zone.classList.remove('drag-over');

        const taskId   = e.dataTransfer.getData('text/plain');
        const newStage = e.currentTarget.dataset.stage;
        const card     = dragCard || (taskId && document.getElementById('card_' + taskId));

        if (!card || !newStage) return;
        persistStage(card, newStage, card.dataset.stage);
    };

    // ── Move a card to a new stage + persist (shared by drag AND the
    //    per-card <select>). Optimistic, reverts on failure. ─────────
    async function persistStage(card, newStage, oldStage) {
        if (!newStage || newStage === oldStage) return;

        const oldZone = document.getElementById('zone_' + oldStage);
        const newZone = document.getElementById('zone_' + newStage);
        if (!newZone) return;

        const sel       = card.querySelector('.tc-status-select');
        const cardTitle = card.dataset.title;
        const cfg       = STAGES[newStage];

        // ── Optimistic UI update ──────────────────────────────────
        newZone.insertBefore(card, newZone.querySelector('.k-empty'));
        card.dataset.stage = newStage;
        if (sel) sel.value = newStage;

        updateEmptyState(newZone, newStage);
        if (oldZone) updateEmptyState(oldZone, oldStage);
        updateCount(oldStage, -1);
        updateCount(newStage, 1);

        showToast('"' + cardTitle + '" moved to ' + (cfg?.label ?? newStage));

        // ── API call — POST + method spoof (mirrors the deals board) ──
        try {
            const fd = new FormData();
            fd.append('_token', CSRF);
            fd.append('_method', 'PATCH');
            fd.append('status', newStage);

            const resp = await fetch(stageUrl(card.dataset.taskId), {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: fd,
            });

            // A followed redirect (login / subscription page) means the write
            // never happened — treat it as a failure.
            if (!resp.ok || resp.redirected) {
                let message = 'Server error: ' + resp.status;
                try {
                    const body = await resp.json();
                    if (body?.message) message = body.message;
                } catch (parseErr) { /* ignore */ }
                throw new Error(message);
            }

        } catch (err) {
            console.error('Stage update failed:', err);

            if (oldZone) {
                oldZone.insertBefore(card, oldZone.querySelector('.k-empty'));
                card.dataset.stage = oldStage;
                if (sel) sel.value = oldStage;
                updateEmptyState(oldZone, oldStage);
                updateEmptyState(newZone, newStage);
                updateCount(oldStage, 1);
                updateCount(newStage, -1);
            }

            showToast(err.message || 'Failed to update. Please try again.', true);
        }
    }

    // ── Per-card status <select> — the drag-free way to change status ──
    document.addEventListener('change', function (e) {
        const sel = e.target.closest && e.target.closest('.tc-status-select');
        if (!sel) return;
        const card = sel.closest('.task-card');
        if (card) persistStage(card, sel.value, card.dataset.stage);
    });

    // ── Helpers ───────────────────────────────────────────────────
    function updateEmptyState(zone, stage) {
        const empty = document.getElementById('empty_' + stage);
        if (!empty) return;
        empty.style.display = zone.querySelectorAll('.task-card').length === 0 ? 'block' : 'none';
    }

    function updateCount(stage, delta) {
        const badge = document.getElementById('count_' + stage);
        if (!badge) return;
        const next = Math.max(0, (parseInt(badge.dataset.total, 10) || 0) + delta);
        badge.dataset.total  = next;
        badge.textContent    = next;
    }

    function showToast(msg, isError = false) {
        const toast = document.getElementById('moveToast');
        const text  = document.getElementById('toastText');
        if (!toast || !text) return;

        text.textContent = msg;
        toast.classList.toggle('error', isError);
        toast.classList.add('show');

        clearTimeout(toast._timer);
        toast._timer = setTimeout(() => toast.classList.remove('show'), 3000);
    }

    // ── Search debounce ───────────────────────────────────────────
    const searchInput = document.querySelector('.di-fi-s');
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                document.getElementById('filterForm').submit();
            }, 500);
        });
    }

})();
</script>

<script>
(function () {
    'use strict';

    const selectAll   = document.getElementById('selectAllTasks');
    const toolbar     = document.getElementById('bulkToolbar');
    const countLabel  = document.getElementById('bulkCount');
    const statusSel   = document.getElementById('bulkStatus');
    const assignSel   = document.getElementById('bulkAssign');

    function checkboxes() {
        return Array.from(document.querySelectorAll('.task-select'));
    }

    function refreshToolbar() {
        const checked = checkboxes().filter(cb => cb.checked);
        if (!toolbar) return;
        toolbar.style.display = checked.length ? 'flex' : 'none';
        if (countLabel) countLabel.textContent = checked.length;
    }

    selectAll?.addEventListener('change', function () {
        checkboxes().forEach(cb => cb.checked = selectAll.checked);
        refreshToolbar();
    });

    checkboxes().forEach(cb => cb.addEventListener('change', refreshToolbar));

    window.submitBulkAction = function (action, value) {
        const ids = checkboxes().filter(cb => cb.checked).map(cb => cb.value);
        if (!ids.length) return;

        if (action === 'delete' && !confirm('Delete ' + ids.length + ' task(s)? This cannot be undone.')) {
            return;
        }

        const form      = document.getElementById('bulkActionForm');
        const container = document.getElementById('bulkIdsContainer');
        container.innerHTML = '';
        ids.forEach(id => {
            const input = document.createElement('input');
            input.type  = 'hidden';
            input.name  = 'ids[]';
            input.value = id;
            container.appendChild(input);
        });

        document.getElementById('bulkActionInput').value = action;
        document.getElementById('bulkValueInput').value  = value ?? '';
        form.submit();
    };

    statusSel?.addEventListener('change', function () {
        if (this.value) submitBulkAction('status', this.value);
    });

    assignSel?.addEventListener('change', function () {
        if (this.value) submitBulkAction('assign', this.value === '__unassign__' ? '' : this.value);
    });

})();
</script>

<script>
function saveCurrentFilter(){
    const name = prompt('Name this filter (e.g. "My high-priority tasks"):');
    if (!name || !name.trim()) return;

    document.getElementById('saveFilterName').value = name.trim();
    document.getElementById('saveFilterForm').submit();
}
</script>
@endpush