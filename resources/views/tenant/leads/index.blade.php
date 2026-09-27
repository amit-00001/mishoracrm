@extends('layouts.app')
@section('title', 'Leads')

@push('styles')
<style>
/* ── VIEW TOGGLE ─────────────────────────────────────────────── */
.view-toggle{display:flex;align-items:center;background:var(--bg-elevated);border:1.5px solid var(--border-default);border-radius:var(--r-sm);padding:3px;gap:2px}
.vt-btn{padding:6px 14px;border-radius:5px;border:none;background:none;cursor:pointer;color:var(--text-300);display:flex;align-items:center;gap:6px;font-size:12.5px;font-weight:600;font-family:var(--font);transition:all .15s;white-space:nowrap}
.vt-btn svg{width:14px;height:14px;flex-shrink:0}
.vt-btn.active{background:var(--bg-surface);color:var(--text-100);box-shadow:0 1px 4px rgba(0,0,0,.12)}
.vt-btn:hover:not(.active){color:var(--text-200)}

/* ── STAT STRIP ──────────────────────────────────────────────── */
.stat-strip{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-bottom:20px}
.stat-pill{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:14px 16px;background:var(--bg-surface);border:1px solid var(--border-default);border-radius:18px;text-decoration:none;transition:transform .15s,border-color .15s,background .15s,box-shadow .15s;min-width:150px;box-shadow:0 1px 3px rgba(0,0,0,.04)}
.stat-pill:hover{transform:translateY(-1px);border-color:var(--accent);background:var(--bg-elevated)}
.stat-pill.active{border-color:var(--accent);background:var(--accent-dim);box-shadow:0 10px 30px rgba(56,138,221,.08)}
.stat-pill .stat-info{display:flex;flex-direction:column;align-items:flex-start;gap:4px;min-width:0}
.stat-num{font-size:18px;font-weight:800;font-family:var(--mono);color:var(--text-100);line-height:1}
.stat-pill.active .stat-num{color:var(--accent)}
.stat-lbl{font-size:11.5px;color:var(--text-300);font-weight:600;line-height:1;text-transform:uppercase;letter-spacing:.08em;white-space:nowrap}
.stat-pill.active .stat-lbl{color:var(--text-200)}
.stat-dot{width:10px;height:10px;border-radius:50%;flex-shrink:0;box-shadow:0 0 0 5px rgba(255,255,255,.06)}

/* ── FILTER BAR ──────────────────────────────────────────────── */
.filter-bar{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:18px}
.fi{padding:7px 11px;height:34px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm);color:var(--text-100);font-family:var(--font);font-size:13px;outline:none;transition:border-color .15s;-webkit-appearance:none}
.fi:focus{border-color:var(--accent)}
.search-wrap{position:relative;flex:1;min-width:180px;max-width:260px}
.search-wrap svg{position:absolute;left:9px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:var(--text-300);pointer-events:none}
.search-wrap .fi{width:100%;padding-left:32px}

/* ── LIST TABLE ──────────────────────────────────────────────── */
.data-table{width:100%;border-collapse:collapse}
.data-table th{padding:9px 14px;text-align:left;font-size:10.5px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border-subtle);white-space:nowrap;background:var(--bg-elevated)}
.data-table td{padding:11px 14px;font-size:13px;color:var(--text-100);border-bottom:1px solid var(--border-subtle);vertical-align:middle}
.data-table tbody tr{transition:background .12s;cursor:pointer}
.data-table tbody tr:hover td{background:var(--bg-elevated)}
.data-table tbody tr:last-child td{border-bottom:none}
.sort-link{display:inline-flex;align-items:center;gap:3px;color:inherit;text-decoration:none;font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.sort-link:hover{color:var(--text-100)}
.lead-av{width:32px;height:32px;border-radius:50%;flex-shrink:0;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff}
.prio-dot{width:8px;height:8px;border-radius:50%;flex-shrink:0}
.prio-dot.high{background:var(--red)}
.prio-dot.medium{background:var(--amber)}
.prio-dot.low{background:var(--green)}
.s-badge{font-size:10.5px;font-weight:700;padding:3px 9px;border-radius:100px;white-space:nowrap;letter-spacing:.02em}
.row-acts{display:flex;align-items:center;gap:3px;opacity:0;transition:opacity .15s}
.data-table tbody tr:hover .row-acts{opacity:1}
.pag-wrap{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-top:1px solid var(--border-subtle);font-size:12.5px;color:var(--text-300)}
.pag-links{display:flex;gap:4px}
.pg-btn{padding:4px 9px;border-radius:var(--r-sm);border:1px solid var(--border-default);color:var(--text-200);text-decoration:none;font-size:12.5px;transition:all .15s}
.pg-btn:hover{border-color:var(--accent);color:var(--accent)}
.pg-btn.active{background:var(--accent);border-color:var(--accent);color:#fff}
.pg-btn.disabled{opacity:.4;pointer-events:none}

/* ── MOBILE LEAD CARDS (list view, <768px) ──────────────────── */
.leads-mobile-list{display:none}
@media(max-width:768px){
    .leads-table-wrap{display:none}
    .leads-mobile-list{display:flex;flex-direction:column;gap:10px;padding:14px}
}
.lm-card{background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-md);padding:14px;cursor:pointer;transition:border-color .15s,box-shadow .15s}
.lm-card:active{border-color:var(--accent)}
.lm-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:12px}
.lm-id{display:flex;align-items:center;gap:10px;min-width:0}
.lm-name{font-size:14px;font-weight:700;color:var(--text-100);line-height:1.3;word-break:break-word}
.lm-co{font-size:11.5px;color:var(--text-400);margin-top:2px}
.lm-badges{display:flex;flex-direction:column;align-items:flex-end;gap:6px;flex-shrink:0}
.lm-prio{display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:700;text-transform:capitalize;white-space:nowrap}
.lm-contact{display:flex;flex-direction:column;gap:2px;margin-bottom:10px;padding:9px 11px;background:var(--bg-elevated);border-radius:8px}
.lm-phone{font-size:12.5px;font-family:var(--mono);color:var(--text-200)}
.lm-email{font-size:11.5px;color:var(--text-400);word-break:break-all}
.lm-meta{display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-bottom:10px;font-size:11.5px}
.lm-meta-lbl{color:var(--text-400)}
.lm-meta-val{color:var(--text-200);font-weight:600}
.lm-foot{display:flex;align-items:center;justify-content:space-between;gap:8px;padding-top:10px;border-top:1px solid var(--border-subtle)}
.lm-assigned{display:flex;align-items:center;gap:6px;min-width:0}
.lm-acts{display:flex;align-items:center;gap:6px;flex-shrink:0}

/* ── KANBAN BOARD ────────────────────────────────────────────── */
.kanban-wrap{width:100%;overflow-x:auto;padding-bottom:8px}
.kanban-board{
    display:grid;
    grid-template-columns:repeat(5,minmax(220px,1fr));
    gap:12px;
    min-width:1000px;
    width:100%;
}

/* Column */
.k-col{
    display:flex;flex-direction:column;
    border-radius:var(--r-md);
    background:var(--bg-elevated);
    border:1px solid var(--border-default);
    overflow:hidden;
    min-height:400px;
}

/* Column header */
.k-head{
    padding:11px 14px;
    background:var(--bg-elevated);
    display:flex;align-items:center;justify-content:space-between;
    border-bottom:1px solid var(--border-subtle);
    flex-shrink:0;
}
.k-head-left{display:flex;align-items:center;gap:8px}
.k-title{font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:.06em}
.k-count{
    font-size:11px;font-weight:700;font-family:var(--mono);
    padding:2px 8px;border-radius:100px;
    background:var(--bg-surface);color:var(--text-300);
    border:1px solid var(--border-subtle);
    min-width:24px;text-align:center;
}

/* Column body — droppable zone */
.k-body{
    flex:1;
    padding:10px 8px;
    background:var(--bg-elevated);
    display:flex;flex-direction:column;gap:8px;
    min-height:300px;
    transition:background .2s,outline .2s;
}
.k-body.drag-over{
    background:color-mix(in srgb, var(--accent) 6%, var(--bg-elevated));
    outline:2px dashed var(--accent);
    outline-offset:-5px;
    border-radius:0 0 var(--r-md) var(--r-md);
}

/* Kanban card */
.k-card{
    background:var(--bg-surface);
    border:1px solid var(--border-subtle);
    border-radius:var(--r-sm);
    padding:12px 13px;
    cursor:pointer;
    transition:border-color .15s,box-shadow .15s,transform .12s,opacity .15s;
    user-select:none;
    position:relative;
}
.k-card:hover{
    border-color:rgba(var(--accent-rgb),.45);
    box-shadow:0 3px 12px rgba(0,0,0,.08);
    transform:translateY(-1px);
}
/* SortableJS classes */
.k-card.sortable-ghost{
    opacity:.3;
    background:color-mix(in srgb, var(--accent) 12%, var(--bg-surface));
    border:1.5px dashed var(--accent);
    transform:none !important;
    box-shadow:none !important;
}
.k-card.sortable-chosen{
    box-shadow:0 8px 24px rgba(0,0,0,.18);
    border-color:var(--accent);
    transform:scale(1.02);
    z-index:100;
}
.k-card.sortable-drag{
    opacity:.85;
    transform:scale(1.03) rotate(1deg);
    box-shadow:0 12px 32px rgba(0,0,0,.22);
    cursor:grabbing;
}
/* Drag handle */
.k-drag-handle{
    position:absolute;top:8px;right:8px;
    color:var(--border-default);
    font-size:13px;opacity:0;
    transition:opacity .15s;
    pointer-events:none;
    line-height:1;
}
.k-card:hover .k-drag-handle{opacity:.7}

.k-card-top{display:flex;align-items:flex-start;justify-content:space-between;gap:6px;margin-bottom:6px}
.k-card-name{font-size:13px;font-weight:700;color:var(--text-100);line-height:1.35;word-break:break-word;padding-right:16px}
.k-card-co{font-size:11px;color:var(--text-400);margin-top:2px}
.k-card-badges{display:flex;align-items:center;gap:5px;flex-shrink:0;position:absolute;top:12px;right:13px}
.k-card-prio{width:7px;height:7px;border-radius:50%;flex-shrink:0}
.k-card-av{width:22px;height:22px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#fff;flex-shrink:0}
.k-card-phone{font-size:11.5px;color:var(--text-300);font-family:var(--mono);margin-bottom:9px;margin-top:4px}
.k-card-footer{display:flex;align-items:center;justify-content:space-between;gap:6px;padding-top:8px;border-top:1px solid var(--border-subtle)}
.k-tag{font-size:10.5px;padding:2px 8px;border-radius:100px;background:var(--bg-elevated);color:var(--text-300);border:1px solid var(--border-subtle);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:90px}
.k-time{font-size:10.5px;color:var(--text-400);white-space:nowrap}
.k-empty{
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    padding:28px 12px;gap:6px;opacity:.4;flex:1;text-align:center;
    pointer-events:none;
}
.k-empty-ico{font-size:22px}
.k-empty-txt{font-size:11.5px;color:var(--text-400)}

/* Drop status toast */
#drop-toast{
    position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(20px);
    background:var(--ink);color:#fff;
    padding:11px 22px;border-radius:var(--r-md);font-size:13px;font-weight:600;
    box-shadow:0 8px 28px rgba(0,0,0,.22);
    opacity:0;transition:opacity .22s,transform .22s;pointer-events:none;z-index:9999;white-space:nowrap;
}
#drop-toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
#drop-toast.success{background:var(--green)}
#drop-toast.error{background:var(--red)}
</style>
@endpush

@section('content')
@php
$statusCfg=[
    'new'        =>['label'=>'New',        'color'=>'var(--accent)', 'bg'=>'var(--accent-dim)', 'dot'=>'var(--accent)'],
    'contacted'  =>['label'=>'Contacted',  'color'=>'var(--amber)',  'bg'=>'var(--amber-dim)',  'dot'=>'var(--amber)'],
    'qualified'  =>['label'=>'Qualified',  'color'=>'var(--purple)', 'bg'=>'var(--purple-dim)', 'dot'=>'var(--purple)'],
    'converted'  =>['label'=>'Converted',  'color'=>'var(--green)',  'bg'=>'var(--green-dim)',  'dot'=>'var(--green)'],
    'lost'       =>['label'=>'Lost',       'color'=>'var(--red)',    'bg'=>'var(--red-dim)',    'dot'=>'#E05252'],
];
$AVC=['var(--accent)','var(--purple)','var(--green)','var(--amber)','#E05252','var(--accent)','var(--green)'];
if (!function_exists('avc')) {
    function avc(string $n,array $c):string{return $c[ord($n[0]??'A')%count($c)];}
}
if (!function_exists('ini')) {
    function ini(string $n):string{$p=explode(' ',trim($n));return strtoupper(substr($p[0],0,1).(isset($p[1])?substr($p[1],0,1):''));}
}
$kanbanCols=['new','contacted','qualified','converted','lost'];
$byStatus=$kanbanLeads->groupBy('status');
$currentStatus=request('status','');
$currentView=session('lead_view','list');
@endphp

{{-- Page head --}}
<div class="page-head">
    <div>
        <div class="page-title">Leads</div>
        <div class="page-sub">{{ number_format($counts['all']) }} leads in pipeline</div>
    </div>
    <div class="page-actions" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        <div class="view-toggle" role="group" aria-label="View mode">
            <button type="button" class="vt-btn" id="btn-list" aria-pressed="false">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5M3.75 6.75h16.5M3.75 17.25h16.5"/></svg>
                List
            </button>
            <button type="button" class="vt-btn" id="btn-kanban" aria-pressed="false">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
                Kanban
            </button>
        </div>
        @can('leads.merge')
        <a href="{{ route('tenant.leads.duplicates') }}" class="btn btn-secondary" data-tip="Find and merge duplicate leads">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5A3.375 3.375 0 006.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0015 2.25h-1.5a2.251 2.251 0 00-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125v-1.5"/></svg>
            Duplicates
        </a>
        @endcan
        @can('leads.export')
        <a href="{{ route('tenant.leads.export', request()->query()) }}" class="btn btn-secondary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Export
        </a>
        @endcan
        @can('leads.import')
        <a href="{{ route('tenant.leads.import') }}" class="btn btn-secondary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M7.5 7.5L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
            Import
        </a>
        @endcan
        <a href="{{ route('tenant.leads.create') }}" class="btn btn-primary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Lead
        </a>
    </div>
</div>

{{-- Stat strip --}}
<div class="stat-strip">
@php
$strips=[
    ''          =>['label'=>'All',       'num'=>$counts['all'],       'dot'=>'var(--text-300)'],
    'new'       =>['label'=>'New',       'num'=>$counts['new'],       'dot'=>$statusCfg['new']['dot']],
    'contacted' =>['label'=>'Contacted', 'num'=>$counts['contacted'], 'dot'=>$statusCfg['contacted']['dot']],
    'qualified' =>['label'=>'Qualified', 'num'=>$counts['qualified'], 'dot'=>$statusCfg['qualified']['dot']],
    'converted' =>['label'=>'Converted', 'num'=>$counts['converted'], 'dot'=>$statusCfg['converted']['dot']],
    'lost'      =>['label'=>'Lost',      'num'=>$counts['lost'],      'dot'=>$statusCfg['lost']['dot']],
];
@endphp
@foreach($strips as $val=>$tab)
<a href="{{ route('tenant.leads.index', array_merge(request()->except('status','page'),$val?['status'=>$val]:[])) }}"
   class="stat-pill {{ $currentStatus===$val?'active':'' }}">
    <div class="stat-dot" style="background:{{ $tab['dot'] }}"></div>
    <div class="stat-info">
        <div class="stat-num">{{ $tab['num'] }}</div>
        <div class="stat-lbl">{{ $tab['label'] }}</div>
    </div>
</a>
@endforeach
</div>

{{-- Filter bar --}}
<form method="GET" action="{{ route('tenant.leads.index') }}" id="filterForm">
    @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}"/>@endif
    <div class="filter-bar">
        <div class="search-wrap">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
            <input type="text" name="search" class="fi" placeholder="Search name, phone..." value="{{ request('search') }}" style="width:100%;padding-left:32px"/>
        </div>
        <select name="source" class="fi" onchange="this.form.submit()">
            <option value="">All Sources</option>
            @foreach($sources as $val=>$cfg)
            <option value="{{ $val }}" {{ request('source')===$val?'selected':'' }}>{{ is_array($cfg)?$cfg['label']:$cfg }}</option>
            @endforeach
        </select>
        <select name="priority" class="fi" onchange="this.form.submit()">
            <option value="">All Priority</option>
            <option value="high"   {{ request('priority')==='high'   ?'selected':'' }}>High</option>
            <option value="medium" {{ request('priority')==='medium' ?'selected':'' }}>Medium</option>
            <option value="low"    {{ request('priority')==='low'    ?'selected':'' }}>Low</option>
        </select>
        <select name="assigned_to" class="fi" onchange="this.form.submit()">
            <option value="">All Staff</option>
            @foreach($staffList as $staff)
            <option value="{{ $staff->id }}" {{ request('assigned_to')==$staff->id?'selected':'' }}>{{ $staff->name }}</option>
            @endforeach
        </select>
        <input type="date" name="date_from" class="fi" value="{{ request('date_from') }}" onchange="this.form.submit()"/>
        <input type="date" name="date_to"   class="fi" value="{{ request('date_to') }}"   onchange="this.form.submit()"/>
        <button type="submit" class="btn btn-secondary" style="height:34px;padding:0 14px;font-size:13px">Filter</button>
        @if(request()->hasAny(['search','source','priority','assigned_to','date_from','date_to']))
        <a href="{{ route('tenant.leads.index',request()->only('status')) }}" class="btn btn-secondary" style="height:34px;padding:0 12px;font-size:13px">✕ Clear</a>
        @endif
    </div>
</form>

{{-- ════════════════════════════════════════════════════════════
     LIST VIEW
════════════════════════════════════════════════════════════ --}}
<div id="view-list" style="display:none">
<div class="card">
@if($leads->isEmpty())
<div style="padding:60px 20px;text-align:center">
    <div style="font-size:34px;margin-bottom:10px">🎯</div>
    <div style="font-size:15px;font-weight:700;color:var(--text-100);margin-bottom:6px">No leads found</div>
    <div style="font-size:13px;color:var(--text-300);margin-bottom:16px">
        {{ request()->hasAny(['search','source','priority','status','assigned_to'])?'Try adjusting your filters':'Add your first lead to get started' }}
    </div>
    <a href="{{ route('tenant.leads.create') }}" class="btn btn-primary">Add Lead</a>
</div>
@else
<div class="bulk-bar" id="bulkBar" style="display:none;align-items:center;gap:12px;flex-wrap:wrap;padding:10px 14px;margin-bottom:12px;background:var(--accent-dim);border:1px solid var(--accent);border-radius:var(--r-sm)">
    <span style="font-size:12.5px;font-weight:700;color:var(--text-100)"><span id="bulkCount">0</span> selected</span>
    @can('leads.edit_all')
    <select id="bulkStatusSelect" class="fi">
        <option value="">Set status...</option>
        <option value="new">New</option>
        <option value="contacted">Contacted</option>
        <option value="qualified">Qualified</option>
        <option value="lost">Lost</option>
    </select>
    @endcan
    @can('leads.assign')
    <select id="bulkAssignSelect" class="fi">
        <option value="">Assign to...</option>
        @foreach($staffList as $staff)
        <option value="{{ $staff->id }}">{{ $staff->name }}</option>
        @endforeach
    </select>
    @endcan
    @can('leads.delete')
    <button type="button" class="btn btn-secondary btn-sm" id="bulkDeleteBtn" style="color:var(--red)">Delete</button>
    @endcan
    <button type="button" class="btn btn-secondary btn-sm" id="bulkClearBtn" style="margin-left:auto">Clear selection</button>
</div>
<div class="leads-table-wrap" style="overflow-x:auto">
<table class="data-table">
<thead>
<tr>
    <th style="width:32px"><input type="checkbox" id="selectAllRows"></th>
    <th><a class="sort-link" href="{{ route('tenant.leads.index',array_merge(request()->all(),['sort'=>'name','dir'=>request('sort')==='name'&&request('dir')==='asc'?'desc':'asc'])) }}">Name {{ request('sort')==='name'?(request('dir')==='asc'?'↑':'↓'):'' }}</a></th>
    <th>Contact</th>
    <th>Source</th>
    <th><a class="sort-link" href="{{ route('tenant.leads.index',array_merge(request()->all(),['sort'=>'status','dir'=>request('sort')==='status'&&request('dir')==='asc'?'desc':'asc'])) }}">Status</a></th>
    <th><a class="sort-link" href="{{ route('tenant.leads.index',array_merge(request()->all(),['sort'=>'priority','dir'=>request('sort')==='priority'&&request('dir')==='asc'?'desc':'asc'])) }}">Priority</a></th>
    <th>Assigned</th>
    <th><a class="sort-link" href="{{ route('tenant.leads.index',array_merge(request()->all(),['sort'=>'created_at','dir'=>request('sort')==='created_at'&&request('dir')==='asc'?'desc':'asc'])) }}">Added</a></th>
    <th style="width:96px"></th>
</tr>
</thead>
<tbody>
@foreach($leads as $lead)
@php
$sc=$statusCfg[$lead->status]??['label'=>ucfirst($lead->status),'color'=>'var(--text-300)','bg'=>'var(--bg-elevated)'];
$av=avc($lead->name,$AVC);
$in=ini($lead->name);
$priC=$lead->priority==='high'?'var(--red)':($lead->priority==='medium'?'var(--amber)':'var(--green)');
$src=$sources[$lead->source]??null;
$srcL=is_array($src)?($src['label']??ucfirst($lead->source)):($src??ucfirst($lead->source));
@endphp
<tr onclick="window.location='{{ route('tenant.leads.show',$lead->id) }}'" data-id="{{ $lead->id }}">
    <td onclick="event.stopPropagation()" style="padding:11px 6px 11px 14px">
        <input type="checkbox" class="row-select" value="{{ $lead->id }}">
    </td>
    <td style="padding-left:4px" data-label="Name">
        <div style="display:flex;align-items:center;gap:9px">
            <div class="lead-av" style="background:{{ $av }}">{{ $in }}</div>
            <div>
                <div style="font-weight:700;font-size:13px;color:var(--text-100)">{{ $lead->name }}</div>
                @if($lead->company??null)<div style="font-size:11px;color:var(--text-400);margin-top:1px">{{ $lead->company }}</div>@endif
            </div>
        </div>
    </td>
    <td data-label="Contact">
        <div style="font-size:12.5px;font-family:var(--mono);color:var(--text-200)">{{ $lead->phone }}</div>
        @if($lead->email)<div style="font-size:11px;color:var(--text-400);margin-top:2px">{{ $lead->email }}</div>@endif
    </td>
    <td data-label="Source"><span style="font-size:12px;color:var(--text-300)">{{ $srcL }}</span></td>
    <td data-label="Status"><span class="s-badge" style="background:{{ $sc['bg'] }};color:{{ $sc['color'] }}">{{ $sc['label'] }}</span></td>
    <td data-label="Priority"><span style="font-size:12px;font-weight:700;color:{{ $priC }};text-transform:capitalize">{{ $lead->priority }}</span></td>
    <td data-label="Assigned">
        @if($lead->assignedTo)
        <div style="display:flex;align-items:center;gap:7px">
            <div style="width:24px;height:24px;border-radius:50%;background:{{ avc($lead->assignedTo->name,$AVC) }};display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#fff;flex-shrink:0">{{ ini($lead->assignedTo->name) }}</div>
            <span style="font-size:12.5px;color:var(--text-200)">{{ $lead->assignedTo->name }}</span>
        </div>
        @else<span style="font-size:12px;color:var(--text-400)">—</span>@endif
    </td>
    <td data-label="Added"><span style="font-size:11.5px;color:var(--text-400);font-family:var(--mono)">{{ $lead->created_at->diffForHumans() }}</span></td>
    <td onclick="event.stopPropagation()">
        <div class="row-acts">
            <a href="{{ route('tenant.leads.show',$lead->id) }}" class="btn btn-secondary btn-sm btn-icon" title="View">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </a>
            <a href="{{ route('tenant.leads.edit',$lead->id) }}" class="btn btn-secondary btn-sm btn-icon" title="Edit">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
            </a>
            <form method="POST" action="{{ route('tenant.leads.destroy',$lead->id) }}" data-confirm="Delete {{ $lead->name }}?" data-confirm-ok="Delete">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-secondary btn-sm btn-icon" style="color:var(--red)" title="Delete">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
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
<div class="leads-mobile-list">
@foreach($leads as $lead)
@php
$sc=$statusCfg[$lead->status]??['label'=>ucfirst($lead->status),'color'=>'var(--text-300)','bg'=>'var(--bg-elevated)'];
$av=avc($lead->name,$AVC);
$in=ini($lead->name);
$priC=$lead->priority==='high'?'var(--red)':($lead->priority==='medium'?'var(--amber)':'var(--green)');
$src=$sources[$lead->source]??null;
$srcL=is_array($src)?($src['label']??ucfirst($lead->source)):($src??ucfirst($lead->source));
@endphp
<div class="lm-card" onclick="window.location='{{ route('tenant.leads.show',$lead->id) }}'">
    <div class="lm-top">
        <div class="lm-id">
            <div class="lead-av" style="background:{{ $av }};width:38px;height:38px;font-size:13px">{{ $in }}</div>
            <div style="min-width:0">
                <div class="lm-name">{{ $lead->name }}</div>
                @if($lead->company??null)<div class="lm-co">{{ $lead->company }}</div>@endif
            </div>
        </div>
        <div class="lm-badges">
            <span class="s-badge" style="background:{{ $sc['bg'] }};color:{{ $sc['color'] }}">{{ $sc['label'] }}</span>
            <span class="lm-prio" style="color:{{ $priC }}"><span class="prio-dot {{ $lead->priority }}"></span>{{ $lead->priority }}</span>
        </div>
    </div>
    <div class="lm-contact">
        <div class="lm-phone">{{ $lead->phone }}</div>
        @if($lead->email)<div class="lm-email">{{ $lead->email }}</div>@endif
    </div>
    <div class="lm-meta">
        <span><span class="lm-meta-lbl">Source: </span><span class="lm-meta-val">{{ $srcL }}</span></span>
        <span style="color:var(--text-400);font-family:var(--mono)">{{ $lead->created_at->diffForHumans() }}</span>
    </div>
    <div class="lm-foot">
        <div class="lm-assigned">
            @if($lead->assignedTo)
            <div style="width:22px;height:22px;border-radius:50%;background:{{ avc($lead->assignedTo->name,$AVC) }};display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;color:#fff;flex-shrink:0">{{ ini($lead->assignedTo->name) }}</div>
            <span style="font-size:12px;color:var(--text-200);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $lead->assignedTo->name }}</span>
            @else<span style="font-size:12px;color:var(--text-400)">Unassigned</span>@endif
        </div>
        <div class="lm-acts" onclick="event.stopPropagation()">
            <a href="{{ route('tenant.leads.show',$lead->id) }}" class="btn btn-secondary btn-sm btn-icon" title="View">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </a>
            <a href="{{ route('tenant.leads.edit',$lead->id) }}" class="btn btn-secondary btn-sm btn-icon" title="Edit">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
            </a>
            <form method="POST" action="{{ route('tenant.leads.destroy',$lead->id) }}" data-confirm="Delete {{ $lead->name }}?" data-confirm-ok="Delete">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-secondary btn-sm btn-icon" style="color:var(--red)" title="Delete">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                </button>
            </form>
        </div>
    </div>
</div>
@endforeach
</div>

@if($leads->hasPages())
<div class="pag-wrap">
    <span>{{ $leads->firstItem() }}–{{ $leads->lastItem() }} of {{ $leads->total() }}</span>
    <div class="pag-links">
        <a href="{{ $leads->previousPageUrl()??'#' }}" class="pg-btn {{ !$leads->previousPageUrl()?'disabled':'' }}">←</a>
        @foreach($leads->getUrlRange(max(1,$leads->currentPage()-2),min($leads->lastPage(),$leads->currentPage()+2)) as $page=>$url)
        <a href="{{ $url }}" class="pg-btn {{ $page==$leads->currentPage()?'active':'' }}">{{ $page }}</a>
        @endforeach
        <a href="{{ $leads->nextPageUrl()??'#' }}" class="pg-btn {{ !$leads->nextPageUrl()?'disabled':'' }}">→</a>
    </div>
</div>
@endif
@endif
</div>
</div>

{{-- ════════════════════════════════════════════════════════════
     KANBAN VIEW
════════════════════════════════════════════════════════════ --}}
<div id="view-kanban" style="display:none">

@if($kanbanCapped)
<div style="margin-bottom:12px;padding:9px 14px;background:var(--amber-dim);border:1px solid rgba(239,159,39,.25);border-radius:var(--r-sm);font-size:12.5px;color:var(--amber);display:flex;align-items:center;gap:7px">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:14px;height:14px;flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
    Showing latest {{ number_format($kanbanCap) }} of {{ number_format($kanbanTotal) }} leads — narrow your filters for a complete view.
</div>
@endif

<div class="kanban-wrap">
<div class="kanban-board" id="kanbanBoard">

@foreach($kanbanCols as $colStatus)
@php
$colLeads=$byStatus->get($colStatus,collect());
$sc=$statusCfg[$colStatus];
@endphp
<div class="k-col" data-status="{{ $colStatus }}">

    <div class="k-head">
        <div class="k-head-left">
            <div style="width:8px;height:8px;border-radius:50%;background:{{ $sc['dot'] }}"></div>
            <span class="k-title" style="color:{{ $sc['color'] }}">{{ $sc['label'] }}</span>
        </div>
        <span class="k-count" id="count-{{ $colStatus }}">{{ $colLeads->count() }}</span>
    </div>

    <div class="k-body" id="col-{{ $colStatus }}" data-status="{{ $colStatus }}">
        @forelse($colLeads as $lead)
        @php
            $av=avc($lead->name,$AVC);
            $in=ini($lead->name);
            $priC=$lead->priority==='high'?'#E05252':($lead->priority==='medium'?'var(--amber)':'var(--green)');
            $src=$sources[$lead->source]??null;
            $srcL=is_array($src)?($src['label']??ucfirst($lead->source)):($src??ucfirst($lead->source));
        @endphp
        <div class="k-card"
             data-id="{{ $lead->id }}"
             data-status="{{ $lead->status }}"
             data-name="{{ e($lead->name) }}"
             data-url="{{ route('tenant.leads.show',$lead->id) }}">
            <span class="k-drag-handle" aria-hidden="true">⠿</span>
            <div class="k-card-top">
                <div style="padding-right:52px">
                    <div class="k-card-name">{{ $lead->name }}</div>
                    @if($lead->company??null)<div class="k-card-co">{{ $lead->company }}</div>@endif
                </div>
                <div class="k-card-badges">
                    <div class="k-card-prio" style="background:{{ $priC }}" title="{{ ucfirst($lead->priority) }} priority"></div>
                    @if($lead->assignedTo)
                    <div class="k-card-av" style="background:{{ avc($lead->assignedTo->name,$AVC) }}" title="{{ $lead->assignedTo->name }}">{{ ini($lead->assignedTo->name) }}</div>
                    @endif
                </div>
            </div>
            <div class="k-card-phone">{{ $lead->phone }}</div>
            <div class="k-card-footer">
                <span class="k-tag">{{ $srcL }}</span>
                <span class="k-time">{{ $lead->created_at->diffForHumans(null,true) }}</span>
            </div>
        </div>
        @empty
        <div class="k-empty" data-empty>
            <div class="k-empty-ico">◌</div>
            <div class="k-empty-txt">No leads</div>
        </div>
        @endforelse
    </div>

</div>
@endforeach

</div>
</div>

@php $cv=$byStatus->get('converted',collect())->count(); $lv=$byStatus->get('lost',collect())->count(); @endphp
@if($cv>0||$lv>0)
<div style="margin-top:14px;display:flex;gap:8px;flex-wrap:wrap">
    @if($cv>0)
    <a href="{{ route('tenant.leads.index',['status'=>'converted']) }}" style="display:inline-flex;align-items:center;gap:6px;padding:7px 13px;background:var(--green-dim);border:1px solid rgba(29,158,117,.2);border-radius:var(--r-sm);font-size:12px;color:var(--green);font-weight:600;text-decoration:none">
        <span style="width:7px;height:7px;border-radius:50%;background:var(--green)"></span>
        {{ $cv }} Converted →
    </a>
    @endif
    @if($lv>0)
    <a href="{{ route('tenant.leads.index',['status'=>'lost']) }}" style="display:inline-flex;align-items:center;gap:6px;padding:7px 13px;background:var(--red-dim);border:1px solid rgba(224,82,82,.18);border-radius:var(--r-sm);font-size:12px;color:var(--red);font-weight:600;text-decoration:none">
        <span style="width:7px;height:7px;border-radius:50%;background:var(--red)"></span>
        {{ $lv }} Lost →
    </a>
    @endif
</div>
@endif
</div>{{-- /view-kanban --}}

<div id="drop-toast"></div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
(function(){
/* ── Constants ──────────────────────────────────────────────── */
const CSRF      = '{{ csrf_token() }}';
const UPDATE_URL= '{{ route("tenant.leads.status", ["lead"=>"__ID__"]) }}';
const statusLabels = @json(array_combine($kanbanCols, array_map(fn($s)=>$statusCfg[$s]['label'], $kanbanCols)));

/* ── View toggle ────────────────────────────────────────────── */
const listEl    = document.getElementById('view-list');
const kanbanEl  = document.getElementById('view-kanban');
const btnList   = document.getElementById('btn-list');
const btnKanban = document.getElementById('btn-kanban');

function applyView(v) {
    const isKanban = v === 'kanban';
    listEl.style.display   = isKanban ? 'none'  : 'block';
    kanbanEl.style.display = isKanban ? 'block' : 'none';
    btnList.classList.toggle('active',  !isKanban);
    btnKanban.classList.toggle('active', isKanban);
    btnList.setAttribute('aria-pressed',   String(!isKanban));
    btnKanban.setAttribute('aria-pressed', String(isKanban));
    localStorage.setItem('lead_view', v);
}

function setView(v) {
    applyView(v);
    fetch('{{ route("tenant.leads.view") }}', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
        body: JSON.stringify({ view: v })
    }).catch(() => {});
}

btnList.addEventListener('click',   () => setView('list'));
btnKanban.addEventListener('click', () => setView('kanban'));

/* Apply saved or server-side view immediately */
const savedView = localStorage.getItem('lead_view') || '{{ $currentView }}';
applyView(savedView);

/* ── Toast ──────────────────────────────────────────────────── */
let toastTimer;
function toast(msg, type) {
    const el = document.getElementById('drop-toast');
    el.textContent = msg;
    el.className   = 'show ' + (type || '');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { el.className = ''; }, 2600);
}

/* ── Kanban empty state helpers ─────────────────────────────── */
function showEmpty(body) {
    if (!body.querySelector('[data-empty]')) {
        const el = document.createElement('div');
        el.className = 'k-empty';
        el.setAttribute('data-empty', '');
        el.innerHTML = '<div class="k-empty-ico">◌</div><div class="k-empty-txt">No leads</div>';
        body.appendChild(el);
    }
}
function hideEmpty(body) {
    body.querySelectorAll('[data-empty]').forEach(e => e.remove());
}
function updateCount(status) {
    const body  = document.getElementById('col-' + status);
    const badge = document.getElementById('count-' + status);
    if (!body || !badge) return;
    const n = body.querySelectorAll('.k-card').length;
    badge.textContent = n;
    if (n === 0) showEmpty(body);
    else         hideEmpty(body);
}

/* ── SortableJS — one instance per column ───────────────────── */
let isDragging = false;

document.querySelectorAll('.k-body').forEach(body => {
    Sortable.create(body, {
        group        : 'leads',
        animation    : 160,
        ghostClass   : 'sortable-ghost',
        chosenClass  : 'sortable-chosen',
        dragClass    : 'sortable-drag',
        delay        : 0,
        delayOnTouchOnly: false,
        touchStartThreshold: 4,

        onStart() {
            isDragging = true;
        },

        onEnd(evt) {
            /* Small async gap so the click after mouseup doesn't fire */
            setTimeout(() => { isDragging = false; }, 10);

            document.querySelectorAll('.k-body').forEach(c => c.classList.remove('drag-over'));

            const card      = evt.item;
            const toBody    = evt.to;
            const fromBody  = evt.from;
            const newStatus = toBody.dataset.status;
            const oldStatus = card.dataset.status;   /* original status */

            /* Remove any empty placeholders from target */
            hideEmpty(toBody);

            /* Update counts for both columns */
            updateCount(newStatus);
            updateCount(fromBody.dataset.status);

            /* Same column — nothing to save */
            if (newStatus === oldStatus) return;

            /* Update card's data attribute optimistically */
            card.dataset.status = newStatus;

            /* Dim card while saving */
            card.style.opacity       = '.5';
            card.style.pointerEvents = 'none';

            const url = UPDATE_URL.replace('__ID__', card.dataset.id);
            fetch(url, {
                method : 'POST',
                headers: {
                    'Content-Type' : 'application/json',
                    'X-CSRF-TOKEN' : CSRF,
                    'Accept'       : 'application/json',
                },
                body: JSON.stringify({ status: newStatus })
            })
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(data => {
                if (data.redirect) {
                    window.location.href = data.redirect;
                    return;
                }
                card.style.opacity       = '';
                card.style.pointerEvents = '';
                const label = statusLabels[newStatus] || newStatus;
                toast('"' + card.dataset.name + '" moved to ' + label, 'success');
            })
            .catch(err => {
                /* Revert: put card back in original column at original index */
                const origChildren = Array.from(fromBody.children).filter(c => !c.dataset.empty);
                const insertBefore = origChildren[evt.oldIndex] || null;
                fromBody.insertBefore(card, insertBefore);
                card.dataset.status      = oldStatus;
                card.style.opacity       = '';
                card.style.pointerEvents = '';
                updateCount(newStatus);
                updateCount(oldStatus);
                toast('Could not update status — please try again.', 'error');
                console.error(err);
            });
        },

        /* Highlight drop target column */
        onMove(evt) {
            document.querySelectorAll('.k-body').forEach(c => c.classList.remove('drag-over'));
            evt.to.classList.add('drag-over');
        },
    });
});

/* ── Card click → open lead (only when NOT dragging) ────────── */
document.querySelectorAll('.k-card').forEach(card => {
    card.addEventListener('click', function() {
        if (isDragging) return;
        const url = this.dataset.url;
        if (url) window.location.href = url;
    });
});

/* ── Bulk select (List view) ───────────────────────────────── */
const selectAll   = document.getElementById('selectAllRows');
const bulkBar      = document.getElementById('bulkBar');
const bulkCountEl  = document.getElementById('bulkCount');

function rowCheckboxes() {
    return Array.from(document.querySelectorAll('.row-select'));
}
function selectedIds() {
    return rowCheckboxes().filter(cb => cb.checked).map(cb => cb.value);
}
function refreshBulkBar() {
    const ids = selectedIds();
    bulkCountEl.textContent = ids.length;
    bulkBar.style.display = ids.length ? 'flex' : 'none';
    if (selectAll) selectAll.checked = ids.length > 0 && ids.length === rowCheckboxes().length;
}

rowCheckboxes().forEach(cb => cb.addEventListener('change', refreshBulkBar));
selectAll?.addEventListener('change', function () {
    rowCheckboxes().forEach(cb => cb.checked = this.checked);
    refreshBulkBar();
});
document.getElementById('bulkClearBtn')?.addEventListener('click', function () {
    rowCheckboxes().forEach(cb => cb.checked = false);
    refreshBulkBar();
});

async function bulkPost(url, extra) {
    const res = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        body: JSON.stringify(Object.assign({ ids: selectedIds() }, extra || {})),
    });
    return res.json();
}

document.getElementById('bulkStatusSelect')?.addEventListener('change', async function () {
    const status = this.value;
    if (!status) return;
    const res = await bulkPost('{{ route("tenant.leads.bulk-status") }}', { status });
    toast(`${res.updated ?? 0} lead(s) updated.`, 'success');
    setTimeout(() => window.location.reload(), 700);
});

document.getElementById('bulkAssignSelect')?.addEventListener('change', async function () {
    const assignedTo = this.value;
    if (!assignedTo) return;
    const res = await bulkPost('{{ route("tenant.leads.bulk-assign") }}', { assigned_to: assignedTo });
    toast(`${res.assigned ?? 0} lead(s) assigned.`, 'success');
    setTimeout(() => window.location.reload(), 700);
});

document.getElementById('bulkDeleteBtn')?.addEventListener('click', function () {
    const ids = selectedIds();
    if (!ids.length) return;
    confirmAction({
        title: 'Delete selected leads?',
        message: `${ids.length} lead(s) will be deleted. Converted leads in the selection will be skipped.`,
        ok: 'Delete',
        danger: true,
        onConfirm: async () => {
            const res = await bulkPost('{{ route("tenant.leads.bulk-destroy") }}');
            let msg = `${res.deleted ?? 0} lead(s) deleted.`;
            if (res.skipped_converted) msg += ` ${res.skipped_converted} converted lead(s) skipped.`;
            toast(msg, 'success');
            setTimeout(() => window.location.reload(), 700);
        },
    });
});

})();
</script>
@endpush
