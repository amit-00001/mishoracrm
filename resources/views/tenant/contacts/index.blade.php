@extends('layouts.app')
@section('title', 'Contacts')

@push('styles')
<style>
/* ── Filter bar ─────────────────────────────────────────────────── */
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
.search-wrap { position:relative; flex:1; min-width:200px; max-width:320px; }
.search-wrap svg { position:absolute; left:10px; top:50%; transform:translateY(-50%); width:15px; height:15px; color:var(--text-300); pointer-events:none; }
.search-wrap input { width:100%; padding-left:34px; }

/* ── Stats row ──────────────────────────────────────────────────── */
.contact-stats {
    display:grid; grid-template-columns:repeat(4,1fr);
    gap:12px; margin-bottom:20px;
}
@media(max-width:900px)  { .contact-stats { grid-template-columns:repeat(2,1fr); } }
@media(max-width:480px)  { .contact-stats { grid-template-columns:1fr 1fr; } }
.cs-card {
    background:var(--bg-surface); border:1px solid var(--border-default);
    border-radius:var(--r-md); padding:16px;
    display:flex; align-items:center; gap:12px;
}
.cs-icon { width:36px; height:36px; border-radius:var(--r-sm); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.cs-icon svg { width:17px; height:17px; }
.cs-num { font-size:20px; font-weight:800; color:var(--text-100); font-family:var(--mono); letter-spacing:-0.5px; }
.cs-label { font-size:12px; color:var(--text-300); margin-top:1px; }

/* ── Contact card grid ──────────────────────────────────────────── */
.contacts-grid {
    display:grid;
    grid-template-columns:repeat(auto-fill, minmax(280px, 1fr));
    gap:14px;
}
.contact-card {
    background:var(--bg-surface); border:1px solid var(--border-default);
    border-radius:var(--r-lg); padding:20px;
    transition:border-color 0.15s var(--ease), transform 0.15s var(--ease);
    display:flex; flex-direction:column; gap:14px;
    animation:fadeUp 0.3s var(--ease) both;
}
.contact-card:hover { border-color:var(--border-strong); transform:translateY(-1px); }

/* Avatar + name */
.cc-top { display:flex; align-items:flex-start; gap:12px; }
.cc-avatar {
    width:44px; height:44px; border-radius:50%;
    background:var(--accent-dim); border:2px solid var(--border-default);
    display:flex; align-items:center; justify-content:center;
    font-size:16px; font-weight:800; color:var(--accent); flex-shrink:0;
}
.cc-name {
    font-size:14.5px; font-weight:700; color:var(--text-100);
    text-decoration:none; line-height:1.3;
    transition:color 0.15s var(--ease);
}
.cc-name:hover { color:var(--accent); }
.cc-company { font-size:12px; color:var(--text-300); margin-top:2px; }
.cc-designation { font-size:11.5px; color:var(--text-400); }

/* Info rows */
.cc-info { display:flex; flex-direction:column; gap:6px; }
.cc-info-row {
    display:flex; align-items:center; gap:8px;
    font-size:12.5px; color:var(--text-200);
}
.cc-info-row svg { width:13px; height:13px; color:var(--text-400); flex-shrink:0; }
.cc-info-row a { color:var(--text-200); text-decoration:none; }
.cc-info-row a:hover { color:var(--accent); }

/* Stats row */
.cc-stats {
    display:flex; gap:0;
    border-top:1px solid var(--border-subtle);
    padding-top:12px;
}
.cc-stat {
    flex:1; text-align:center;
    border-right:1px solid var(--border-subtle);
    padding:0 8px;
}
.cc-stat:last-child { border-right:none; }
.cc-stat-num { font-size:15px; font-weight:800; color:var(--text-100); font-family:var(--mono); }
.cc-stat-label { font-size:10.5px; color:var(--text-400); text-transform:uppercase; letter-spacing:0.3px; margin-top:1px; }

/* Actions */
.cc-actions { display:flex; gap:6px; padding-top:2px; }
.cc-btn {
    flex:1; display:flex; align-items:center; justify-content:center; gap:5px;
    padding:7px 10px; border-radius:var(--r-sm);
    border:1.5px solid var(--border-default);
    background:none; cursor:pointer; font-family:var(--font);
    font-size:12px; font-weight:600; color:var(--text-200);
    text-decoration:none; transition:all 0.15s var(--ease);
}
.cc-btn:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-dim); }
.cc-btn svg { width:13px; height:13px; }
.cc-btn.danger:hover { border-color:var(--red); color:var(--red); background:var(--red-dim); }

/* View toggle */
.view-toggle { display:flex; gap:2px; background:var(--bg-input); border-radius:var(--r-sm); padding:2px; }
.view-btn {
    padding:5px 10px; border-radius:4px; cursor:pointer;
    border:none; background:none; color:var(--text-300);
    transition:all 0.15s var(--ease); display:flex; align-items:center;
}
.view-btn.active { background:var(--bg-surface); color:var(--text-100); box-shadow:0 1px 4px rgba(0,0,0,0.2); }
.view-btn svg { width:15px; height:15px; }

/* Table view */
.table-view { display:none; }
.table-view.active { display:block; }
.grid-view { display:block; }
.grid-view.hidden { display:none; }

/* ── MOBILE CONTACT CARDS (list/table view, <768px) ───────────── */
.ct-mobile-list{display:none}
@media(max-width:768px){
    .ct-table-wrap{display:none}
    .ct-mobile-list{display:flex;flex-direction:column;gap:10px;padding:14px}
}
.ct-card{background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-md);padding:14px;cursor:pointer;transition:border-color .15s,box-shadow .15s}
.ct-card:active{border-color:var(--accent)}
.ct-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:12px}
.ct-id{display:flex;align-items:center;gap:10px;min-width:0}
.ct-avatar{width:38px;height:38px;border-radius:50%;background:var(--accent-dim);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:800;flex-shrink:0}
.ct-name{font-size:14px;font-weight:700;color:var(--text-100);line-height:1.3;word-break:break-word}
.ct-email{font-size:11.5px;color:var(--text-400);margin-top:2px}
.ct-contact{display:flex;flex-direction:column;gap:2px;margin-bottom:10px;padding:9px 11px;background:var(--bg-elevated);border-radius:8px}
.ct-phone{font-size:12.5px;font-family:var(--mono);color:var(--text-200)}
.ct-co{font-size:11.5px;color:var(--text-400)}
.ct-meta{display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-bottom:10px;font-size:11.5px}
.ct-meta-lbl{color:var(--text-400)}
.ct-meta-val{color:var(--text-200);font-weight:600}
.ct-stats-row{display:flex;gap:14px;margin-bottom:10px;font-size:11.5px;color:var(--text-300)}
.ct-stats-row b{color:var(--text-100);font-family:var(--mono)}
.ct-foot{display:flex;align-items:center;justify-content:flex-end;gap:6px;padding-top:10px;border-top:1px solid var(--border-subtle)}
.ct-acts{display:flex;align-items:center;gap:6px;flex-shrink:0}

/* Empty state */
.empty-state { padding:60px 20px; text-align:center; }
.empty-icon  { font-size:40px; margin-bottom:12px; }
.empty-title { font-size:15px; font-weight:700; color:var(--text-100); margin-bottom:6px; }
.empty-sub   { font-size:13px; color:var(--text-300); margin-bottom:20px; }

/* Pagination */
.pagination-wrap { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-top:1px solid var(--border-subtle); font-size:13px; color:var(--text-300); }
.pagination-links { display:flex; gap:4px; }
.page-link { padding:5px 10px; border-radius:var(--r-sm); border:1px solid var(--border-default); color:var(--text-200); text-decoration:none; font-size:13px; transition:all 0.15s; }
.page-link:hover { border-color:var(--accent); color:var(--accent); }
.page-link.active { background:var(--accent); border-color:var(--accent); color:#fff; }
.page-link.disabled { opacity:0.4; pointer-events:none; }

@keyframes fadeUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:none} }
</style>
@endpush

@section('content')

@php $tenantSlug = auth()->user()->tenant->subdomain; @endphp

{{-- Page header --}}
<div class="page-head">
    <div>
        <div class="page-title">Contacts</div>
        <div class="page-sub">{{ $total }} total contacts</div>
    </div>
    <div class="page-actions" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
        @can('contacts.merge')
        <a href="{{ route('tenant.contacts.duplicates') }}" class="btn btn-secondary" data-tip="Find and merge duplicate contacts">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.108c0-1.135.845-2.098 1.976-2.192.373-.03.748-.057 1.123-.08M15.75 18H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08M15.75 18.75v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5A3.375 3.375 0 006.375 7.5H5.25m11.9-3.664A2.251 2.251 0 0015 2.25h-1.5a2.251 2.251 0 00-2.15 1.586m5.8 0c.065.21.1.433.1.664v.75h-6V4.5c0-.231.035-.454.1-.664M6.75 7.5H4.875c-.621 0-1.125.504-1.125 1.125v12c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125v-1.5"/></svg>
            Duplicates
        </a>
        @endcan
        @can('contacts.export')
        <a href="{{ route('tenant.contacts.export', request()->query()) }}" class="btn btn-secondary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Export
        </a>
        @endcan
        @can('contacts.import')
        <a href="{{ route('tenant.contacts.import') }}" class="btn btn-secondary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M7.5 7.5L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
            Import
        </a>
        @endcan
        <a href="{{ route('tenant.contacts.create', ['tenant' => $tenantSlug]) }}"
           class="btn btn-primary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Contact
        </a>
    </div>
</div>

{{-- Stats row --}}
<div class="contact-stats">
    <div class="cs-card">
        <div class="cs-icon" style="background:var(--accent-dim);color:var(--accent)">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <div>
            <div class="cs-num">{{ $total }}</div>
            <div class="cs-label">Total Contacts</div>
        </div>
    </div>

    <div class="cs-card">
        <div class="cs-icon" style="background:var(--green-dim);color:var(--green)">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/>
            </svg>
        </div>
        <div>
            <div class="cs-num">{{ \App\Models\Invoice::count() }}</div>
            <div class="cs-label">Total Invoices</div>
        </div>
    </div>

    <div class="cs-card">
        <div class="cs-icon" style="background:var(--amber-dim);color:var(--amber)">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6"/>
            </svg>
        </div>
        <div>
            <div class="cs-num">{{ \App\Models\Deal::count() }}</div>
            <div class="cs-label">Active Deals</div>
        </div>
    </div>

    <div class="cs-card">
        <div class="cs-icon" style="background:var(--purple-dim);color:var(--purple)">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
            </svg>
        </div>
        <div>
            <div class="cs-num">{{ \App\Models\Contact::whereDate('created_at', today())->count() }}</div>
            <div class="cs-label">Added Today</div>
        </div>
    </div>
</div>

{{-- Filter bar --}}
<form method="GET" action="{{ route('tenant.contacts.index', ['tenant' => $tenantSlug]) }}" id="filterForm">
    <div class="filter-bar">

        {{-- Search --}}
        <div class="search-wrap">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <input type="text" name="search" class="filter-input"
                   placeholder="Search name, phone, company..."
                   value="{{ request('search') }}"/>
        </div>

        {{-- City filter --}}
        <input type="text" name="city" class="filter-input"
               placeholder="Filter by city..."
               value="{{ request('city') }}"
               style="max-width:160px"/>

        {{-- Sort --}}
        <select name="sort" class="filter-input" onchange="this.form.submit()">
            <option value="created_at" {{ request('sort','created_at') === 'created_at' ? 'selected':'' }}>Latest</option>
            <option value="name"       {{ request('sort') === 'name'       ? 'selected':'' }}>Name A-Z</option>
            <option value="company"    {{ request('sort') === 'company'    ? 'selected':'' }}>Company</option>
        </select>

        <button type="submit" class="btn btn-secondary">Search</button>

        @if(request()->hasAny(['search','city','sort']))
        <a href="{{ route('tenant.contacts.index', ['tenant' => $tenantSlug]) }}"
           class="btn btn-secondary">Clear</a>
        @endif

        {{-- View toggle --}}
        <div class="view-toggle" style="margin-left:auto">
            <button type="button" class="view-btn active" id="gridBtn" onclick="setView('grid')" title="Card view">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/>
                </svg>
            </button>
            <button type="button" class="view-btn" id="listBtn" onclick="setView('list')" title="List view">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/>
                </svg>
            </button>
        </div>

    </div>
</form>

{{-- ── CARD / GRID VIEW ─────────────────────────────────────────── --}}
<div class="grid-view" id="gridView">
    @if($contacts->isEmpty())
    <div class="card">
        <div class="empty-state">
            <div class="empty-icon">👤</div>
            <div class="empty-title">No contacts found</div>
            <div class="empty-sub">
                @if(request()->hasAny(['search','city']))
                    Try clearing your filters
                @else
                    Add your first contact to get started
                @endif
            </div>
            <a href="{{ route('tenant.contacts.create', ['tenant' => $tenantSlug]) }}"
               class="btn btn-primary">Add Contact</a>
        </div>
    </div>
    @else
    <div class="contacts-grid">
        @foreach($contacts as $contact)
        <div class="contact-card">

            {{-- Top --}}
            <div class="cc-top">
                <div class="cc-avatar">
                    {{ strtoupper(substr($contact->name, 0, 1)) }}
                </div>
                <div style="flex:1;min-width:0">
                    <a href="{{ route('tenant.contacts.show', ['tenant' => $tenantSlug, 'id' => $contact->id]) }}"
                       class="cc-name">{{ $contact->name }}</a>
                    @if($contact->company)
                    <div class="cc-company">{{ $contact->company }}</div>
                    @endif
                    @if($contact->designation)
                    <div class="cc-designation">{{ $contact->designation }}</div>
                    @endif
                </div>
            </div>

            {{-- Info --}}
            <div class="cc-info">
                <div class="cc-info-row">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                    </svg>
                    <a href="tel:{{ $contact->phone }}">{{ $contact->phone }}</a>
                </div>

                @if($contact->email)
                <div class="cc-info-row">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                    </svg>
                    <a href="mailto:{{ $contact->email }}">{{ $contact->email }}</a>
                </div>
                @endif

                @if($contact->city || $contact->state)
                <div class="cc-info-row">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                    </svg>
                    {{ collect([$contact->city, $contact->state])->filter()->join(', ') }}
                </div>
                @endif
            </div>

            {{-- Stats --}}
            <div class="cc-stats">
                <div class="cc-stat">
                    <div class="cc-stat-num">{{ $contact->deals_count }}</div>
                    <div class="cc-stat-label">Deals</div>
                </div>
                <div class="cc-stat">
                    <div class="cc-stat-num">{{ $contact->followups_count }}</div>
                    <div class="cc-stat-label">Follow-ups</div>
                </div>
                <div class="cc-stat">
                    <div class="cc-stat-num">{{ $contact->invoices_count }}</div>
                    <div class="cc-stat-label">Invoices</div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="cc-actions">
                <a href="{{ route('tenant.contacts.show', ['tenant' => $tenantSlug, 'id' => $contact->id]) }}"
                   class="cc-btn">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    View
                </a>
                <a href="{{ route('tenant.contacts.edit', ['tenant' => $tenantSlug, 'id' => $contact->id]) }}"
                   class="cc-btn">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                    </svg>
                    Edit
                </a>
                <form method="POST"
                      action="{{ route('tenant.contacts.destroy', ['tenant' => $tenantSlug, 'id' => $contact->id]) }}"
                      data-confirm="Delete {{ $contact->name }}?" data-confirm-ok="Delete">
                    @csrf @method('DELETE')
                    <button type="submit" class="cc-btn danger">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                        </svg>
                    </button>
                </form>
            </div>

        </div>
        @endforeach
    </div>

    {{-- Pagination --}}
    @if($contacts->hasPages())
    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:20px;font-size:13px;color:var(--text-300)">
        <span>Showing {{ $contacts->firstItem() }}–{{ $contacts->lastItem() }} of {{ $contacts->total() }}</span>
        <div class="pagination-links">
            <a href="{{ $contacts->previousPageUrl() ?? '#' }}"
               class="page-link {{ !$contacts->previousPageUrl() ? 'disabled':'' }}">←</a>
            @foreach($contacts->getUrlRange(max(1,$contacts->currentPage()-2), min($contacts->lastPage(),$contacts->currentPage()+2)) as $page => $url)
            <a href="{{ $url }}" class="page-link {{ $page == $contacts->currentPage() ? 'active':'' }}">{{ $page }}</a>
            @endforeach
            <a href="{{ $contacts->nextPageUrl() ?? '#' }}"
               class="page-link {{ !$contacts->nextPageUrl() ? 'disabled':'' }}">→</a>
        </div>
    </div>
    @endif
    @endif
</div>

{{-- ── TABLE / LIST VIEW ────────────────────────────────────────── --}}
<div class="table-view card" id="listView">
    @if($contacts->isEmpty())
    <div class="empty-state">
        <div class="empty-icon">👤</div>
        <div class="empty-title">No contacts found</div>
        <div class="empty-sub">Add your first contact</div>
        <a href="{{ route('tenant.contacts.create', ['tenant' => $tenantSlug]) }}"
           class="btn btn-primary">Add Contact</a>
    </div>
    @else
    <div class="ct-table-wrap" style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Phone</th>
                    <th>Company</th>
                    <th>City</th>
                    <th>Deals</th>
                    <th>Invoices</th>
                    <th>Added</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($contacts as $contact)
                <tr>
                    <td data-label="Name">
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:32px;height:32px;border-radius:50%;background:var(--accent-dim);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0">
                                {{ strtoupper(substr($contact->name,0,1)) }}
                            </div>
                            <div>
                                <a href="{{ route('tenant.contacts.show', ['tenant' => $tenantSlug, 'id' => $contact->id]) }}"
                                   class="td-name" style="text-decoration:none;color:var(--text-100)">
                                    {{ $contact->name }}
                                </a>
                                @if($contact->email)
                                <div style="font-size:11.5px;color:var(--text-400)">{{ $contact->email }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="td-mono" data-label="Phone">{{ $contact->phone }}</td>
                    <td style="font-size:13px;color:var(--text-200)" data-label="Company">{{ $contact->company ?? '—' }}</td>
                    <td style="font-size:13px;color:var(--text-300)" data-label="City">{{ $contact->city ?? '—' }}</td>
                    <td class="td-mono" style="font-size:13px" data-label="Deals">{{ $contact->deals_count }}</td>
                    <td class="td-mono" style="font-size:13px" data-label="Invoices">{{ $contact->invoices_count }}</td>
                    <td class="td-mono" style="font-size:11.5px;color:var(--text-400)" data-label="Added">
                        {{ $contact->created_at->diffForHumans() }}
                    </td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <a href="{{ route('tenant.contacts.show', ['tenant' => $tenantSlug, 'id' => $contact->id]) }}"
                               class="btn btn-secondary btn-sm btn-icon">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </a>
                            <a href="{{ route('tenant.contacts.edit', ['tenant' => $tenantSlug, 'id' => $contact->id]) }}"
                               class="btn btn-secondary btn-sm btn-icon">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                            </a>
                            <form method="POST"
                                  action="{{ route('tenant.contacts.destroy', ['tenant' => $tenantSlug, 'id' => $contact->id]) }}"
                                  data-confirm="Delete this contact?" data-confirm-ok="Delete">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-secondary btn-sm btn-icon"
                                        style="color:var(--red)">
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
    <div class="ct-mobile-list">
    @foreach($contacts as $contact)
    <div class="ct-card" onclick="window.location='{{ route('tenant.contacts.show', ['tenant' => $tenantSlug, 'id' => $contact->id]) }}'">
        <div class="ct-top">
            <div class="ct-id">
                <div class="ct-avatar">{{ strtoupper(substr($contact->name,0,1)) }}</div>
                <div style="min-width:0">
                    <div class="ct-name">{{ $contact->name }}</div>
                    @if($contact->email)<div class="ct-email">{{ $contact->email }}</div>@endif
                </div>
            </div>
        </div>
        <div class="ct-contact">
            <div class="ct-phone">{{ $contact->phone }}</div>
            <div class="ct-co">{{ $contact->company ?? '—' }}</div>
        </div>
        <div class="ct-meta">
            <span><span class="ct-meta-lbl">City: </span><span class="ct-meta-val">{{ $contact->city ?? '—' }}</span></span>
            <span style="color:var(--text-400);font-family:var(--mono)">{{ $contact->created_at->diffForHumans() }}</span>
        </div>
        <div class="ct-stats-row">
            <span><b>{{ $contact->deals_count }}</b> Deals</span>
            <span><b>{{ $contact->invoices_count }}</b> Invoices</span>
        </div>
        <div class="ct-foot" onclick="event.stopPropagation()">
            <div class="ct-acts">
                <a href="{{ route('tenant.contacts.show', ['tenant' => $tenantSlug, 'id' => $contact->id]) }}"
                   class="btn btn-secondary btn-sm btn-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </a>
                <a href="{{ route('tenant.contacts.edit', ['tenant' => $tenantSlug, 'id' => $contact->id]) }}"
                   class="btn btn-secondary btn-sm btn-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                </a>
                <form method="POST"
                      action="{{ route('tenant.contacts.destroy', ['tenant' => $tenantSlug, 'id' => $contact->id]) }}"
                      data-confirm="Delete {{ $contact->name }}?" data-confirm-ok="Delete">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-secondary btn-sm btn-icon"
                            style="color:var(--red)">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endforeach
    </div>

    @if($contacts->hasPages())
    <div class="pagination-wrap">
        <span>Showing {{ $contacts->firstItem() }}–{{ $contacts->lastItem() }} of {{ $contacts->total() }}</span>
        <div class="pagination-links">
            <a href="{{ $contacts->previousPageUrl() ?? '#' }}"
               class="page-link {{ !$contacts->previousPageUrl() ? 'disabled':'' }}">←</a>
            @foreach($contacts->getUrlRange(max(1,$contacts->currentPage()-2), min($contacts->lastPage(),$contacts->currentPage()+2)) as $page => $url)
            <a href="{{ $url }}" class="page-link {{ $page == $contacts->currentPage() ? 'active':'' }}">{{ $page }}</a>
            @endforeach
            <a href="{{ $contacts->nextPageUrl() ?? '#' }}"
               class="page-link {{ !$contacts->nextPageUrl() ? 'disabled':'' }}">→</a>
        </div>
    </div>
    @endif
    @endif
</div>

@endsection

@push('scripts')
<script>
// ── View toggle (card / list) ─────────────────────────────────────
const savedView = localStorage.getItem('contacts_view') || 'grid';
setView(savedView);

function setView(type) {
    const gridView = document.getElementById('gridView');
    const listView = document.getElementById('listView');
    const gridBtn  = document.getElementById('gridBtn');
    const listBtn  = document.getElementById('listBtn');

    if (type === 'grid') {
        gridView.classList.remove('hidden');
        listView.classList.remove('active');
        gridBtn.classList.add('active');
        listBtn.classList.remove('active');
    } else {
        gridView.classList.add('hidden');
        listView.classList.add('active');
        listBtn.classList.add('active');
        gridBtn.classList.remove('active');
    }

    localStorage.setItem('contacts_view', type);
}

// ── Search on Enter ───────────────────────────────────────────────
document.querySelector('input[name="search"]').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') document.getElementById('filterForm').submit();
});
</script>
@endpush