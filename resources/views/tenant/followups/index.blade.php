@extends('layouts.app')
@section('title', 'Follow-ups')

@push('styles')
<style>
.filter-bar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
.filter-input {
    padding:8px 12px; background:var(--bg-input);
    border:1.5px solid var(--border-default); border-radius:var(--r-sm);
    color:var(--text-100); font-family:var(--font); font-size:13px;
    outline:none; height:36px; transition:border-color 0.15s var(--ease);
}
.filter-input:focus { border-color:var(--accent); }

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
.status-tab.overdue { color:var(--red); border-color:rgba(255,82,87,0.3); background:var(--red-dim); }

/* Type icon */
.type-dot { width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.type-dot svg { width:13px; height:13px; }
.type-call      { background:var(--green-dim);  color:var(--green); }
.type-email     { background:var(--accent-dim); color:var(--accent); }
.type-whatsapp  { background:var(--green-dim);  color:var(--green); }
.type-meeting   { background:var(--purple-dim); color:var(--purple); }
.type-other     { background:var(--amber-dim);  color:var(--amber); }

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

/* Done modal */
.modal-overlay {
    position:fixed; inset:0; background:rgba(0,0,0,0.5);
    display:flex; align-items:center; justify-content:center;
    z-index:1000; padding:16px;
}
.modal-box {
    background:var(--bg-surface); border:1px solid var(--border-default);
    border-radius:var(--r-lg); width:100%; max-width:480px;
    max-height:90vh; overflow-y:auto;
}
.modal-head {
    display:flex; align-items:center; justify-content:space-between;
    padding:16px 20px; border-bottom:1px solid var(--border-subtle);
}
.modal-title { font-size:14px; font-weight:700; color:var(--text-100); }
.modal-close {
    background:none; border:none; cursor:pointer; font-size:20px;
    color:var(--text-400); line-height:1; padding:2px 6px; border-radius:var(--r-sm);
}
.modal-close:hover { color:var(--text-100); background:var(--bg-hover); }
.modal-body { padding:20px; }
.modal-actions {
    display:flex; align-items:center; justify-content:flex-end; gap:10px;
    padding:16px 20px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle);
}
.attach-upload {
    display:flex; align-items:center; gap:10px;
    padding:12px; border:1.5px dashed var(--border-default);
    border-radius:var(--r-md);
}
.attach-upload input[type=file] { flex:1; font-size:12.5px; color:var(--text-300); }
.attach-hint { font-size:11.5px; color:var(--text-400); margin-top:6px; }

/* ── MOBILE FOLLOW-UP CARDS (<768px) ──────────────────────────── */
.followups-mobile-list{display:none}
@media(max-width:768px){
    .followups-table-wrap{display:none}
    .followups-mobile-list{display:flex;flex-direction:column;gap:10px;padding:14px}
}
.fu-card{background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-md);padding:14px}
.fu-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:12px}
.fu-id{display:flex;align-items:center;gap:10px;min-width:0}
.fu-name{font-size:14px;font-weight:700;color:var(--text-100);text-decoration:none;word-break:break-word}
.fu-kind{font-size:11.5px;color:var(--text-400);margin-top:2px}
.fu-badges{display:flex;flex-direction:column;align-items:flex-end;gap:6px;flex-shrink:0}
.fu-when{display:flex;flex-direction:column;gap:2px;margin-bottom:10px;padding:9px 11px;background:var(--bg-elevated);border-radius:8px}
.fu-date{font-size:12.5px;font-family:var(--mono);color:var(--text-200)}
.fu-time{font-size:11.5px;color:var(--text-400)}
.fu-meta{display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-bottom:10px;font-size:11.5px}
.fu-meta-lbl{color:var(--text-400)}
.fu-meta-val{color:var(--text-200);font-weight:600}
.fu-notes{font-size:12px;color:var(--text-300);margin-bottom:10px;overflow-wrap:anywhere}
.fu-foot{display:flex;align-items:center;justify-content:flex-end;gap:8px;padding-top:10px;border-top:1px solid var(--border-subtle)}
.fu-acts{display:flex;align-items:center;gap:6px;flex-wrap:wrap}
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div class="page-title">Follow-ups</div>
        <div class="page-sub">Track all scheduled follow-ups</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('tenant.followups.create') }}" class="btn btn-primary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Schedule Follow-up
        </a>
    </div>
</div>

{{-- Status tabs --}}
<div class="status-tabs">
    @php $cur = request('status', ''); @endphp
    <a href="{{ route('tenant.followups.index') }}"
       class="status-tab {{ $cur === '' ? 'active' : '' }}">
        All <span class="tab-count">{{ $counts['all'] }}</span>
    </a>
    <a href="{{ route('tenant.followups.index', ['status'=>'scheduled']) }}"
       class="status-tab {{ $cur === 'scheduled' ? 'active' : '' }}">
        Scheduled <span class="tab-count">{{ $counts['scheduled'] }}</span>
    </a>
    <a href="{{ route('tenant.followups.index', ['date'=>today()->format('Y-m-d')]) }}"
       class="status-tab {{ request('date') === today()->format('Y-m-d') ? 'active' : '' }}">
        Today <span class="tab-count">{{ $counts['today'] }}</span>
    </a>
    @if($counts['overdue'] > 0)
    <a href="{{ route('tenant.followups.index', ['overdue'=>1]) }}"
       class="status-tab overdue">
        Overdue <span class="tab-count" style="background:var(--red);color:#fff">{{ $counts['overdue'] }}</span>
    </a>
    @endif
    <a href="{{ route('tenant.followups.index', ['status'=>'done']) }}"
       class="status-tab {{ $cur === 'done' ? 'active' : '' }}">
        Done <span class="tab-count">{{ $counts['done'] }}</span>
    </a>
    <a href="{{ route('tenant.followups.index', ['status'=>'missed']) }}"
       class="status-tab {{ $cur === 'missed' ? 'active' : '' }}">
        Missed <span class="tab-count">{{ $counts['missed'] }}</span>
    </a>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('tenant.followups.index') }}">
    @if(request('status')) <input type="hidden" name="status" value="{{ request('status') }}"/> @endif
    <div class="filter-bar">
        <select name="type" class="filter-input" onchange="this.form.submit()">
            <option value="">All Types</option>
            @foreach($types as $val => $label)
            <option value="{{ $val }}" {{ request('type') === $val ? 'selected':'' }}>{{ $label }}</option>
            @endforeach
        </select>

        <select name="assigned_to" class="filter-input" onchange="this.form.submit()">
            <option value="">All Staff</option>
            @foreach($staffList as $staff)
            <option value="{{ $staff->id }}" {{ request('assigned_to') == $staff->id ? 'selected':'' }}>{{ $staff->name }}</option>
            @endforeach
        </select>

        <input type="date" name="date" class="filter-input"
               value="{{ request('date') }}" onchange="this.form.submit()"/>

        <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text-200);cursor:pointer">
            <input type="checkbox" name="mine" value="1" {{ request('mine') ? 'checked':'' }} onchange="this.form.submit()" style="accent-color:var(--accent)"/>
            My follow-ups only
        </label>

        @if(request()->hasAny(['type','assigned_to','date','mine']))
        <a href="{{ route('tenant.followups.index', request()->only('status')) }}" class="btn btn-secondary">Clear</a>
        @endif
    </div>
</form>

{{-- Table --}}
<div class="card">
    @if($followups->isEmpty())
    <div class="empty-state">
        <div class="empty-icon">📅</div>
        <div class="empty-title">No follow-ups found</div>
        <div class="empty-sub">Schedule your first follow-up</div>
        <a href="{{ route('tenant.followups.create') }}" class="btn btn-primary">Schedule Now</a>
    </div>
    @else
    <div class="followups-table-wrap" style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Lead / Contact</th>
                    <th>Assigned To</th>
                    <th>Scheduled At</th>
                    <th>Status</th>
                    <th>Notes</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($followups as $followup)
                <tr>
                    <td data-label="Type">
                        <div style="display:flex;align-items:center;gap:8px">
                            <div class="type-dot type-{{ $followup->type }}">
                                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                    @if($followup->type === 'call' || $followup->type === 'whatsapp')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                                    @elseif($followup->type === 'email')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                                    @elseif($followup->type === 'meeting')
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    @endif
                                </svg>
                            </div>
                            <span style="font-size:12.5px;color:var(--text-200)">{{ $types[$followup->type] }}</span>
                        </div>
                    </td>
                    <td data-label="Lead / Contact">
                        @if($followup->lead)
                        <a href="{{ route('tenant.leads.show', $followup->lead_id) }}"
                           style="font-size:13.5px;font-weight:600;color:var(--text-100);text-decoration:none">
                            {{ $followup->lead->name }}
                        </a>
                        <div style="font-size:11.5px;color:var(--text-400)">Lead</div>
                        @elseif($followup->contact)
                        {{-- <a href="{{ route('tenant.contacts.show', $followup->contact_id) }}" --}}
                        <a href="#"
                           style="font-size:13.5px;font-weight:600;color:var(--text-100);text-decoration:none">
                            {{ $followup->contact->name }}
                        </a>
                        <div style="font-size:11.5px;color:var(--text-400)">Contact</div>
                        @else
                        <span style="color:var(--text-400)">—</span>
                        @endif
                    </td>
                    <td style="font-size:13px;color:var(--text-200)" data-label="Assigned To">
                        {{ $followup->assignedTo?->name ?? '—' }}
                    </td>
                    <td data-label="Scheduled At">
                        <div class="td-mono" style="font-size:12.5px;{{ $followup->isOverdue() ? 'color:var(--red)' : '' }}">
                            {{ $followup->scheduled_at->format('d M Y') }}
                        </div>
                        <div style="font-size:11.5px;color:var(--text-400)">
                            {{ $followup->scheduled_at->format('h:i A') }}
                            @if($followup->isOverdue())
                                <span style="color:var(--red)"> · Overdue</span>
                            @endif
                        </div>
                    </td>
                    <td data-label="Status">
                        <span class="badge
                            @if($followup->status === 'scheduled') badge-new
                            @elseif($followup->status === 'done')  badge-qualified
                            @elseif($followup->status === 'missed') badge-lost
                            @else badge-contacted
                            @endif">
                            {{ ucfirst($followup->status) }}
                        </span>
                    </td>
                    <td style="font-size:12.5px;color:var(--text-300);max-width:180px" data-label="Notes">
                        <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                            {{ $followup->notes ?? '—' }}
                        </div>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px">
                            @if($followup->isScheduled())
                            <button type="button" class="btn btn-secondary btn-sm"
                                    style="color:var(--green);font-size:12px" title="Mark done"
                                    onclick="openDoneModal('{{ route('tenant.followups.done', $followup) }}')">
                                ✓ Done
                            </button>
                            @endif
                            <a href="{{ route('tenant.followups.edit', $followup) }}"
                               class="btn btn-secondary btn-sm btn-icon">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                            </a>
                            <form method="POST" action="{{ route('tenant.followups.destroy', $followup) }}"
                                  data-confirm="Delete this follow-up?" data-confirm-ok="Delete">
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
    <div class="followups-mobile-list">
    @foreach($followups as $followup)
    <div class="fu-card">
        <div class="fu-top">
            <div class="fu-id">
                <div class="type-dot type-{{ $followup->type }}">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        @if($followup->type === 'call' || $followup->type === 'whatsapp')
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                        @elseif($followup->type === 'email')
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                        @elseif($followup->type === 'meeting')
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        @endif
                    </svg>
                </div>
                <div style="min-width:0">
                    @if($followup->lead)
                    <a href="{{ route('tenant.leads.show', $followup->lead_id) }}" class="fu-name">{{ $followup->lead->name }}</a>
                    <div class="fu-kind">Lead · {{ $types[$followup->type] }}</div>
                    @elseif($followup->contact)
                    <a href="#" class="fu-name">{{ $followup->contact->name }}</a>
                    <div class="fu-kind">Contact · {{ $types[$followup->type] }}</div>
                    @else
                    <span class="fu-name" style="color:var(--text-400)">—</span>
                    <div class="fu-kind">{{ $types[$followup->type] }}</div>
                    @endif
                </div>
            </div>
            <div class="fu-badges">
                <span class="badge
                    @if($followup->status === 'scheduled') badge-new
                    @elseif($followup->status === 'done')  badge-qualified
                    @elseif($followup->status === 'missed') badge-lost
                    @else badge-contacted
                    @endif">
                    {{ ucfirst($followup->status) }}
                </span>
            </div>
        </div>

        <div class="fu-when">
            <div class="fu-date" style="{{ $followup->isOverdue() ? 'color:var(--red)' : '' }}">{{ $followup->scheduled_at->format('d M Y') }}</div>
            <div class="fu-time">
                {{ $followup->scheduled_at->format('h:i A') }}
                @if($followup->isOverdue())
                    <span style="color:var(--red)"> · Overdue</span>
                @endif
            </div>
        </div>

        <div class="fu-meta">
            <span><span class="fu-meta-lbl">Assigned: </span><span class="fu-meta-val">{{ $followup->assignedTo?->name ?? '—' }}</span></span>
        </div>

        @if($followup->notes)
        <div class="fu-notes">{{ $followup->notes }}</div>
        @endif

        <div class="fu-foot">
            <div class="fu-acts">
                @if($followup->isScheduled())
                <button type="button" class="btn btn-secondary btn-sm"
                        style="color:var(--green);font-size:12px" title="Mark done"
                        onclick="openDoneModal('{{ route('tenant.followups.done', $followup) }}')">
                    ✓ Done
                </button>
                @endif
                <a href="{{ route('tenant.followups.edit', $followup) }}"
                   class="btn btn-secondary btn-sm btn-icon">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                </a>
                <form method="POST" action="{{ route('tenant.followups.destroy', $followup) }}"
                      data-confirm="Delete this follow-up?" data-confirm-ok="Delete">
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

    @if($followups->hasPages())
    <div class="pagination-wrap">
        <span>Showing {{ $followups->firstItem() }}–{{ $followups->lastItem() }} of {{ $followups->total() }}</span>
        <div class="pagination-links">
            <a href="{{ $followups->previousPageUrl() ?? '#' }}"
               class="page-link {{ !$followups->previousPageUrl() ? 'disabled':'' }}">←</a>
            @foreach($followups->getUrlRange(max(1,$followups->currentPage()-2), min($followups->lastPage(),$followups->currentPage()+2)) as $page => $url)
            <a href="{{ $url }}" class="page-link {{ $page == $followups->currentPage() ? 'active':'' }}">{{ $page }}</a>
            @endforeach
            <a href="{{ $followups->nextPageUrl() ?? '#' }}"
               class="page-link {{ !$followups->nextPageUrl() ? 'disabled':'' }}">→</a>
        </div>
    </div>
    @endif
    @endif
</div>

{{-- Mark Done modal --}}
<div id="doneModal" class="modal-overlay" style="display:none">
    <div class="modal-box">
        <div class="modal-head">
            <div class="modal-title">✅ Mark Follow-up as Done</div>
            <button type="button" class="modal-close" onclick="closeDoneModal()">&times;</button>
        </div>
        <form id="doneForm" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">
                <div class="field" style="display:flex;flex-direction:column;gap:7px">
                    <label class="field-label" style="font-size:12.5px;font-weight:600;color:var(--text-200);text-transform:uppercase;letter-spacing:0.3px">
                        Discussion / Outcome
                    </label>
                    <textarea name="outcome" class="field-input field-textarea" rows="4"
                              style="padding:10px 13px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm);color:var(--text-100);font-family:var(--font);font-size:14px;outline:none;resize:vertical"
                              placeholder="Kya discuss hua, result kya raha..."></textarea>
                </div>

                <div style="margin-top:16px">
                    <label class="field-label" style="font-size:12.5px;font-weight:600;color:var(--text-200);text-transform:uppercase;letter-spacing:0.3px;display:block;margin-bottom:7px">
                        Attach Documents (optional)
                    </label>
                    <div class="attach-upload">
                        <input type="file" name="attachments[]" multiple
                               accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx"/>
                    </div>
                    <div class="attach-hint">Images, PDFs, Word or Excel · up to 10 MB each · max 5 files</div>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeDoneModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="doneSubmitBtn">Mark Done</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
function openDoneModal(actionUrl) {
    const form = document.getElementById('doneForm');
    form.action = actionUrl;
    form.reset();
    document.getElementById('doneModal').style.display = 'flex';
}

function closeDoneModal() {
    document.getElementById('doneModal').style.display = 'none';
}

document.getElementById('doneModal').addEventListener('click', function (e) {
    if (e.target === this) closeDoneModal();
});

document.getElementById('doneForm').addEventListener('submit', function () {
    const btn = document.getElementById('doneSubmitBtn');
    btn.innerHTML = '⏳ Saving...';
    btn.disabled = true;
});
</script>
@endpush