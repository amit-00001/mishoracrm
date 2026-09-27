@extends('layouts.app')
@section('title', 'Quotations')

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
.search-wrap { position:relative; flex:1; min-width:180px; max-width:280px; }
.search-wrap svg { position:absolute; left:10px; top:50%; transform:translateY(-50%); width:15px; height:15px; color:var(--text-300); pointer-events:none; }
.search-wrap input { width:100%; padding-left:34px; }

/* Status tabs */
.status-tabs { display:flex; gap:4px; flex-wrap:wrap; margin-bottom:20px; }
.status-tab {
    padding:6px 14px; border-radius:20px; font-size:12.5px; font-weight:600;
    text-decoration:none; border:1.5px solid var(--border-default);
    color:var(--text-300); background:none; transition:all 0.15s var(--ease);
    display:flex; align-items:center; gap:6px;
}
.status-tab:hover { border-color:var(--border-strong); color:var(--text-100); }
.status-tab.active { background:var(--accent-dim); border-color:var(--accent); color:var(--accent); }
.tab-count { font-size:11px; font-family:var(--mono); background:var(--bg-elevated); padding:0 5px; border-radius:10px; color:var(--text-300); }
.status-tab.active .tab-count { background:var(--accent); color:#fff; }

/* Pagination */
.pagination-wrap { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-top:1px solid var(--border-subtle); font-size:13px; color:var(--text-300); }
.pagination-links { display:flex; gap:4px; }
.page-link { padding:5px 10px; border-radius:var(--r-sm); border:1px solid var(--border-default); color:var(--text-200); text-decoration:none; font-size:13px; transition:all 0.15s; }
.page-link:hover { border-color:var(--accent); color:var(--accent); }
.page-link.active { background:var(--accent); border-color:var(--accent); color:#fff; }
.page-link.disabled { opacity:0.4; pointer-events:none; }

/* Empty state */
.empty-state { padding:60px 20px; text-align:center; }
.empty-icon  { font-size:40px; margin-bottom:12px; }
.empty-title { font-size:15px; font-weight:700; color:var(--text-100); margin-bottom:6px; }
.empty-sub   { font-size:13px; color:var(--text-300); margin-bottom:20px; }

/* ── MOBILE QUOTATION CARDS (<768px) ──────────────────────────── */
.quotations-mobile-list{display:none}
@media(max-width:768px){
    .quotations-table-wrap{display:none}
    .quotations-mobile-list{display:flex;flex-direction:column;gap:10px;padding:14px}
}
.qt-card{background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-md);padding:14px;transition:border-color .15s}
.qt-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:12px}
.qt-id{min-width:0}
.qt-number{font-size:14px;font-weight:700;color:var(--text-100);text-decoration:none;word-break:break-word}
.qt-sub{font-size:11.5px;color:var(--text-400);margin-top:2px}
.qt-badges{display:flex;flex-direction:column;align-items:flex-end;gap:6px;flex-shrink:0}
.qt-amount{font-size:13.5px;font-weight:700;color:var(--accent);font-family:var(--mono)}
.qt-contact{display:flex;flex-direction:column;gap:2px;margin-bottom:10px;padding:9px 11px;background:var(--bg-elevated);border-radius:8px}
.qt-contact-name{font-size:13px;font-weight:600;color:var(--text-100)}
.qt-contact-sub{font-size:11.5px;color:var(--text-400)}
.qt-meta{display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-bottom:10px;font-size:11.5px}
.qt-meta-lbl{color:var(--text-400)}
.qt-meta-val{color:var(--text-200);font-weight:600}
.qt-disc{color:var(--green);font-weight:600}
.qt-foot{display:flex;align-items:center;justify-content:flex-end;gap:8px;padding-top:10px;border-top:1px solid var(--border-subtle)}
.qt-acts{display:flex;align-items:center;gap:6px;flex-wrap:wrap}
</style>
@endpush

@section('content')

@php
    $statuses = config('crm.quotation.statuses');
    $curStatus = request('status', '');
@endphp

{{-- Page header --}}
<div class="page-head">
    <div>
        <div class="page-title">Quotations</div>
        <div class="page-sub">{{ $counts['all'] }} total quotations</div>
    </div>
    <div class="page-actions">
        @can('quotations.export')
        <a href="{{ route('tenant.quotations.export', request()->query()) }}" class="btn btn-secondary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
            </svg>
            Export
        </a>
        @endcan
        <a href="{{ route('tenant.quotations.create') }}" class="btn btn-primary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            New Quotation
        </a>
    </div>
</div>

{{-- Status tabs --}}
<div class="status-tabs">
    <a href="{{ route('tenant.quotations.index') }}"
       class="status-tab {{ $curStatus === '' ? 'active' : '' }}">
        All <span class="tab-count">{{ $counts['all'] }}</span>
    </a>
    @foreach($statuses as $key => $s)
    <a href="{{ route('tenant.quotations.index', ['status' => $key]) }}"
       class="status-tab {{ $curStatus === $key ? 'active' : '' }}"
       style="{{ $curStatus === $key ? 'border-color:var(--'.$s['color'].');background:var(--'.$s['bg'].');color:var(--'.$s['color'].')' : '' }}">
        {{ $s['label'] }}
        <span class="tab-count"
              style="{{ $curStatus === $key ? 'background:var(--'.$s['color'].');color:#fff' : '' }}">
            {{ $counts[$key] ?? 0 }}
        </span>
    </a>
    @endforeach
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('tenant.quotations.index') }}" id="filterForm">
    @if($curStatus) <input type="hidden" name="status" value="{{ $curStatus }}"/> @endif
    <div class="filter-bar">
        <div class="search-wrap">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <input type="text" name="search" class="filter-input"
                   placeholder="Search number, contact..."
                   value="{{ request('search') }}"/>
        </div>

        <input type="date" name="date_from" class="filter-input"
               value="{{ request('date_from') }}" onchange="this.form.submit()"/>

        <input type="date" name="date_to" class="filter-input"
               value="{{ request('date_to') }}" onchange="this.form.submit()"/>

        <button type="submit" class="btn btn-secondary">Search</button>

        @if(request()->hasAny(['search','date_from','date_to']))
        <a href="{{ route('tenant.quotations.index', $curStatus ? ['status'=>$curStatus] : []) }}"
           class="btn btn-secondary">Clear</a>
        @endif
    </div>
</form>

{{-- Table --}}
<div class="card">
    @if($quotations->isEmpty())
    <div class="empty-state">
        <div class="empty-icon">📄</div>
        <div class="empty-title">No quotations found</div>
        <div class="empty-sub">Create your first quotation</div>
        <a href="{{ route('tenant.quotations.create') }}" class="btn btn-primary">Create Quotation</a>
    </div>
    @else
    <div class="quotations-table-wrap" style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Number</th>
                    <th>Contact / Lead</th>
                    <th>Date</th>
                    <th>Valid Until</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quotations as $q)
                @php $s = $statuses[$q->status] ?? ['color'=>'text-300','bg'=>'bg-elevated','label'=>ucfirst($q->status)]; @endphp
                <tr>
                    {{-- Number --}}
                    <td data-label="Number">
                        <a href="{{ route('tenant.quotations.show', $q->id) }}"
                           class="td-name" style="text-decoration:none;color:var(--text-100)">
                            {{ $q->number }}
                        </a>
                        <div style="font-size:11.5px;color:var(--text-400)">
                            {{ $q->created_at->format('d M Y') }}
                        </div>
                    </td>

                    {{-- Contact / Lead --}}
                    <td data-label="Contact / Lead">
                        @if($q->contact)
                        <div style="font-size:13.5px;font-weight:600;color:var(--text-100)">
                            {{ $q->contact->name }}
                        </div>
                        @if($q->contact->company)
                        <div style="font-size:11.5px;color:var(--text-400)">{{ $q->contact->company }}</div>
                        @endif
                        @elseif($q->lead)
                        <div style="font-size:13.5px;font-weight:600;color:var(--text-100)">
                            {{ $q->lead->name }}
                        </div>
                        <div style="font-size:11.5px;color:var(--text-400)">Lead</div>
                        @else
                        <span style="color:var(--text-400)">—</span>
                        @endif
                    </td>

                    {{-- Date --}}
                    <td class="td-mono" style="font-size:12.5px" data-label="Date">
                        {{ $q->date->format('d M Y') }}
                    </td>

                    {{-- Valid until --}}
                    <td class="td-mono" style="font-size:12.5px;{{ $q->isExpired() ? 'color:var(--red)' : '' }}" data-label="Valid Until">
                        {{ $q->valid_until?->format('d M Y') ?? '—' }}
                        @if($q->isExpired())
                        <div style="font-size:11px;color:var(--red)">Expired</div>
                        @endif
                    </td>

                    {{-- Amount --}}
                    <td data-label="Amount">
                        <div style="font-size:14px;font-weight:700;color:var(--accent);font-family:var(--mono)">
                            {{ $q->formatted_total }}
                        </div>
                        @if($q->discount > 0)
                        <div style="font-size:11px;color:var(--green)">
                            -{{ $q->currencySymbol() }}{{ number_format($q->discount,0) }} disc.
                        </div>
                        @endif
                    </td>

                    {{-- Status --}}
                    <td data-label="Status">
                        <span class="badge"
                              style="background:var(--{{ $s['bg'] }});color:var(--{{ $s['color'] }});padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:600">
                            {{ $s['label'] }}
                        </span>
                    </td>

                    {{-- Actions --}}
                    <td>
                        <div style="display:flex;gap:6px;align-items:center">

                            {{-- View --}}
                            <a href="{{ route('tenant.quotations.show', $q->id) }}"
                               class="btn btn-secondary btn-sm btn-icon" title="View">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </a>

                            {{-- PDF --}}
                            <a href="{{ route('tenant.quotations.pdf', $q->id) }}"
                               class="btn btn-secondary btn-sm btn-icon" title="Download PDF" target="_blank">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                </svg>
                            </a>

                            {{-- Edit --}}
                            @if(!in_array($q->status, ['accepted']))
                            <a href="{{ route('tenant.quotations.edit', $q->id) }}"
                               class="btn btn-secondary btn-sm btn-icon" title="Edit">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                                </svg>
                            </a>
                            @endif

                            {{-- Convert to Invoice --}}
                            @if($q->status === 'accepted' && !$q->invoice)
                            <form data-confirm="Convert to Invoice?" data-confirm-ok="Convert" data-confirm-danger="false" method="POST" action="{{ route('tenant.quotations.convert', $q->id) }}">
                                @csrf
                                <button type="submit" class="btn btn-secondary btn-sm"
                                        style="color:var(--green);font-size:12px"
                                        title="Convert to Invoice">
                                    → Invoice
                                </button>
                            </form>
                            @endif

                            {{-- Delete --}}
                            @if($q->status === 'draft')
                            <form method="POST" action="{{ route('tenant.quotations.destroy', $q->id) }}"
                                  data-confirm="Delete {{ $q->number }}?" data-confirm-ok="Delete">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-secondary btn-sm btn-icon"
                                        style="color:var(--red)" title="Delete">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                    </svg>
                                </button>
                            </form>
                            @endif

                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile card list (shown only <768px, table above hides itself) --}}
    <div class="quotations-mobile-list">
    @foreach($quotations as $q)
    @php $s = $statuses[$q->status] ?? ['color'=>'text-300','bg'=>'bg-elevated','label'=>ucfirst($q->status)]; @endphp
    <div class="qt-card">
        <div class="qt-top">
            <div class="qt-id">
                <a href="{{ route('tenant.quotations.show', $q->id) }}" class="qt-number">{{ $q->number }}</a>
                <div class="qt-sub">{{ $q->created_at->format('d M Y') }}</div>
            </div>
            <div class="qt-badges">
                <span class="badge"
                      style="background:var(--{{ $s['bg'] }});color:var(--{{ $s['color'] }});padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:600">
                    {{ $s['label'] }}
                </span>
                <span class="qt-amount">{{ $q->formatted_total }}</span>
            </div>
        </div>

        <div class="qt-contact">
            @if($q->contact)
            <div class="qt-contact-name">{{ $q->contact->name }}</div>
            @if($q->contact->company)
            <div class="qt-contact-sub">{{ $q->contact->company }}</div>
            @endif
            @elseif($q->lead)
            <div class="qt-contact-name">{{ $q->lead->name }}</div>
            <div class="qt-contact-sub">Lead</div>
            @else
            <div class="qt-contact-sub">No contact linked</div>
            @endif
        </div>

        <div class="qt-meta">
            <span>
                <span class="qt-meta-lbl">Valid until: </span>
                <span class="qt-meta-val" style="{{ $q->isExpired() ? 'color:var(--red)' : '' }}">
                    {{ $q->valid_until?->format('d M Y') ?? '—' }}{{ $q->isExpired() ? ' · Expired' : '' }}
                </span>
            </span>
            @if($q->discount > 0)
            <span class="qt-disc">-₹{{ number_format($q->discount,0) }} disc.</span>
            @endif
        </div>

        <div class="qt-foot">
            <div class="qt-acts">
                {{-- View --}}
                <a href="{{ route('tenant.quotations.show', $q->id) }}"
                   class="btn btn-secondary btn-sm btn-icon" title="View">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </a>

                {{-- PDF --}}
                <a href="{{ route('tenant.quotations.pdf', $q->id) }}"
                   class="btn btn-secondary btn-sm btn-icon" title="Download PDF" target="_blank">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                    </svg>
                </a>

                {{-- Edit --}}
                @if(!in_array($q->status, ['accepted']))
                <a href="{{ route('tenant.quotations.edit', $q->id) }}"
                   class="btn btn-secondary btn-sm btn-icon" title="Edit">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                    </svg>
                </a>
                @endif

                {{-- Convert to Invoice --}}
                @if($q->status === 'accepted' && !$q->invoice)
                <form data-confirm="Convert to Invoice?" data-confirm-ok="Convert" data-confirm-danger="false" method="POST" action="{{ route('tenant.quotations.convert', $q->id) }}">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm"
                            style="color:var(--green);font-size:12px"
                            title="Convert to Invoice">
                        → Invoice
                    </button>
                </form>
                @endif

                {{-- Delete --}}
                @if($q->status === 'draft')
                <form method="POST" action="{{ route('tenant.quotations.destroy', $q->id) }}"
                      data-confirm="Delete {{ $q->number }}?" data-confirm-ok="Delete">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-secondary btn-sm btn-icon"
                            style="color:var(--red)" title="Delete">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                        </svg>
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
    @endforeach
    </div>

    {{-- Pagination --}}
    @if($quotations->hasPages())
    <div class="pagination-wrap">
        <span>Showing {{ $quotations->firstItem() }}–{{ $quotations->lastItem() }} of {{ $quotations->total() }}</span>
        <div class="pagination-links">
            <a href="{{ $quotations->previousPageUrl() ?? '#' }}"
               class="page-link {{ !$quotations->previousPageUrl() ? 'disabled' : '' }}">←</a>
            @foreach($quotations->getUrlRange(max(1,$quotations->currentPage()-2), min($quotations->lastPage(),$quotations->currentPage()+2)) as $page => $url)
            <a href="{{ $url }}" class="page-link {{ $page == $quotations->currentPage() ? 'active' : '' }}">
                {{ $page }}
            </a>
            @endforeach
            <a href="{{ $quotations->nextPageUrl() ?? '#' }}"
               class="page-link {{ !$quotations->nextPageUrl() ? 'disabled' : '' }}">→</a>
        </div>
    </div>
    @endif
    @endif
</div>

@endsection

@push('scripts')
<script>
document.querySelector('input[name="search"]')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') document.getElementById('filterForm').submit();
});
</script>
@endpush