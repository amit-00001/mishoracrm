@extends('layouts.app')
@section('title', 'Deal — ' . $deal->title)

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600;1,400&family=DM+Mono:wght@400;500&display=swap');
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css');

.ds-page { font-family: 'DM Sans', var(--font), sans-serif; }

/* ── Layout ── */
.ds-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 290px;
    gap: 16px;
    margin-top: 20px;
}
.ds-main    { display: flex; flex-direction: column; gap: 14px; }
.ds-sidebar { display: flex; flex-direction: column; gap: 14px; }
@media (max-width: 900px) { .ds-layout { grid-template-columns: 1fr; } }
@media (max-width: 480px) { .ds-stats { grid-template-columns: repeat(2, 1fr); row-gap: 14px; } }

/* ── Card ── */
.ds-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    border-radius: 14px;
    overflow: hidden;
}
.ds-card-hd {
    padding: 14px 18px;
    border-bottom: 1px solid var(--border-subtle);
    background: var(--bg-elevated);
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.ds-card-title {
    font-size: 11px; font-weight: 600;
    color: var(--text-300); text-transform: uppercase; letter-spacing: .6px;
}

/* ── Hero ── */
.ds-hero { padding: 22px; }
.ds-hero-top { display: flex; align-items: flex-start; gap: 16px; margin-bottom: 16px; }
.ds-avatar {
    width: 56px; height: 56px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; font-weight: 600; flex-shrink: 0;
    background: var(--accent-dim); color: var(--accent);
}
.ds-title   { font-size: 20px; font-weight: 600; color: var(--text-100); letter-spacing: -.3px; margin-bottom: 6px; line-height: 1.2; }
.ds-value   { font-size: 24px; font-weight: 600; color: var(--text-100); font-family: 'DM Mono', monospace; letter-spacing: -1px; line-height: 1; }
.ds-value-lbl { font-size: 11px; color: var(--text-300); margin-top: 2px; text-align: right; }
.ds-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 11px; border-radius: 20px;
    font-size: 11.5px; font-weight: 600;
}
.ds-id { font-size: 11.5px; color: var(--text-300); font-family: 'DM Mono', monospace; }

/* ── Stats bar ── */
.ds-stats {
    display: grid; grid-template-columns: repeat(4, 1fr);
    padding: 14px 22px;
    background: var(--bg-elevated);
    border-top: 1px solid var(--border-subtle);
    gap: 10px;
}
.ds-stat-val { font-size: 16px; font-weight: 600; color: var(--text-100); font-family: 'DM Mono', monospace; letter-spacing: -.5px; }
.ds-stat-lbl { font-size: 11px; color: var(--text-300); margin-top: 2px; }

/* ── Pipeline ── */
.ds-pipeline { padding: 18px 22px; }
.ds-pip-steps { display: flex; align-items: center; margin-top: 16px; }
.ds-pip-step  { flex: 1; text-align: center; position: relative; }
.ds-pip-dot {
    width: 26px; height: 26px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 5px; font-size: 11px; font-weight: 600;
}
.ds-pip-done    .ds-pip-dot { background: var(--green-dim); color: var(--green); border: 2px solid var(--green); }
.ds-pip-active  .ds-pip-dot { background: var(--accent); color: #fff;    border: 2px solid var(--accent); }
.ds-pip-pending .ds-pip-dot { background: var(--bg-input); color: var(--text-300); border: 1px solid var(--border-default); }
.ds-pip-won     .ds-pip-dot { background: var(--green-dim); color: var(--green); border: 2px solid var(--green); }
.ds-pip-lost    .ds-pip-dot { background: var(--red-dim); color: var(--red); border: 2px solid var(--red); }
.ds-pip-lbl { font-size: 10.5px; font-weight: 500; color: var(--text-300); }
.ds-pip-active  .ds-pip-lbl { color: var(--accent); font-weight: 600; }
.ds-pip-won     .ds-pip-lbl { color: var(--green); font-weight: 600; }
.ds-pip-lost    .ds-pip-lbl { color: var(--red); font-weight: 600; }
.ds-pip-line       { flex: 1; height: 2px; background: var(--border-subtle); margin-bottom: 20px; }
.ds-pip-line.done  { background: var(--green); }

/* ── Info Grid ── */
.ds-info-grid { display: grid; grid-template-columns: 1fr 1fr; }
.ds-info-cell {
    padding: 11px 16px;
    border-bottom: 1px solid var(--border-subtle);
    border-right:  1px solid var(--border-subtle);
}
.ds-info-cell:nth-child(even)       { border-right: none; }
.ds-info-cell:nth-last-child(-n+2)  { border-bottom: none; }
.ds-info-lbl { font-size: 11px; font-weight: 600; color: var(--text-300); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 3px; }
.ds-info-val { font-size: 13.5px; font-weight: 500; color: var(--text-100); }
.ds-info-val a { color: var(--accent); text-decoration: none; }
.ds-info-val.muted { color: var(--text-400); font-weight: 400; font-style: italic; }

/* ── Probability bar ── */
.ds-prob-wrap  { padding: 16px 22px; }
.ds-prob-row   { display: flex; align-items: center; gap: 12px; }
.ds-prob-track { flex: 1; height: 8px; background: var(--border-subtle); border-radius: 4px; overflow: hidden; }
.ds-prob-fill  { height: 100%; border-radius: 4px; transition: width .4s ease; }
.ds-prob-pct   { font-size: 16px; font-weight: 600; color: var(--text-100); font-family: 'DM Mono', monospace; min-width: 42px; }

/* ── Notes ── */
.ds-notes { padding: 16px 22px; font-size: 13.5px; color: var(--text-200); line-height: 1.65; white-space: pre-wrap; }
.ds-notes.empty { color: var(--text-400); font-style: italic; }

/* ── Followups ── */
.ds-fu-item {
    display: flex; gap: 14px; padding: 13px 18px;
    border-bottom: 1px solid var(--border-subtle);
}
.ds-fu-item:last-child { border-bottom: none; }
.ds-fu-icon {
    width: 32px; height: 32px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.ds-fu-type   { font-size: 13px; font-weight: 600; color: var(--text-100); }
.ds-fu-time   { font-size: 11.5px; color: var(--text-300); font-family: 'DM Mono', monospace; }
.ds-fu-note   { font-size: 12.5px; color: var(--text-300); margin-top: 3px; line-height: 1.45; }
.ds-fu-status { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600; margin-top: 5px; }

/* ── Empty state ── */
.ds-empty { text-align: center; padding: 28px 16px; }
.ds-empty-icon { width: 38px; height: 38px; border-radius: 10px; background: var(--bg-elevated); display: flex; align-items: center; justify-content: center; margin: 0 auto 9px; }
.ds-empty-txt  { font-size: 12.5px; color: var(--text-400); }

/* ── Sidebar card ── */
.ds-sc { background: var(--bg-surface); border: 1px solid var(--border-default); border-radius: 14px; padding: 17px; }
.ds-sc-title { font-size: 11px; font-weight: 600; color: var(--text-300); text-transform: uppercase; letter-spacing: .6px; margin-bottom: 12px; }

/* ── Quick Actions ── */
.ds-qa-btn {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 12px; border-radius: 8px; cursor: pointer;
    background: var(--bg-elevated); border: 1px solid var(--border-subtle);
    font-family: 'DM Sans', var(--font), sans-serif;
    font-size: 13px; color: var(--text-100); font-weight: 500;
    transition: all .15s; width: 100%; text-align: left;
    text-decoration: none; margin-bottom: 6px;
}
.ds-qa-btn:last-child { margin-bottom: 0; }
.ds-qa-btn:hover { background: var(--bg-surface); border-color: var(--border-default); }
.ds-qa-icon { width: 28px; height: 28px; border-radius: 7px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }

/* ── Detail rows ── */
.ds-dl-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 8px 0; border-bottom: 1px solid var(--border-subtle);
}
.ds-dl-row:last-child { border-bottom: none; }
.ds-dl-key { font-size: 12px; color: var(--text-300); }
.ds-dl-val { font-size: 12.5px; font-weight: 500; color: var(--text-100); text-align: right; }

/* ── Lost reason box ── */
.ds-lost-box {
    margin: 14px 22px;
    padding: 12px 14px;
    background: var(--red-dim);
    border: 1px solid var(--red);
    border-radius: 10px;
}
.ds-lost-label { font-size: 11px; font-weight: 600; color: var(--red); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 4px; }
.ds-lost-text  { font-size: 13px; color: var(--red); line-height: 1.5; }

@keyframes ds-spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
</style>
@endpush

@section('content')
@php
$cfgStages = config('deal_fields.stages');

$initials = substr(
    collect(explode(' ', $deal->title))->map(fn($p) => strtoupper($p[0] ?? ''))->join(''),
    0, 2
);

$stg           = $cfgStages[$deal->stage] ?? $cfgStages['new'];
$prob          = (int) ($deal->probability ?? $stg['probability']);
$probColor     = $prob >= 60 ? 'var(--green)' : ($prob >= 30 ? 'var(--amber)' : 'var(--accent)');
$daysOpen      = (int) $deal->created_at->diffInDays(now());
$isWon         = $deal->stage === 'won';
$isLost        = $deal->stage === 'lost';
$isOpen        = !$isWon && !$isLost;

/* Pipeline order (won/lost shown separately) */
$pipeStages = ['new', 'proposal', 'negotiation'];
$pipeLabels = ['New', 'Proposal', 'Negotiation'];
$pipeIcons  = ['ti-sparkles', 'ti-file-description', 'ti-messages'];
$currentIdx = array_search($deal->stage, $pipeStages);

$fuTypeIcons = [
    'call'     => ['ti-phone',      'var(--accent-dim)', 'var(--accent)'],
    'email'    => ['ti-mail',       'var(--purple-dim)', 'var(--purple)'],
    'whatsapp' => ['ti-brand-whatsapp', 'var(--green-dim)', 'var(--green)'],
    'meeting'  => ['ti-users',      'var(--amber-dim)', 'var(--amber)'],
    'other'    => ['ti-dots',       'var(--bg-hover)', 'var(--text-300)'],
];
$fuStatusColors = [
    'scheduled'   => ['var(--accent-dim)', 'var(--accent)'],
    'done'        => ['var(--green-dim)', 'var(--green)'],
    'missed'      => ['var(--red-dim)', 'var(--red)'],
    'rescheduled' => ['var(--amber-dim)', 'var(--amber)'],
];

$tkStatusColors = config('task_fields.stages');
$tkPriorityColors = config('task_fields.priorities');

$assignInit = $deal->assignedTo
    ? substr(collect(explode(' ', $deal->assignedTo->name))->map(fn($p) => strtoupper($p[0] ?? ''))->join(''), 0, 2)
    : '';
@endphp

<div class="ds-page">

{{-- Header --}}
<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px;display:flex;align-items:center;gap:5px">
            <a href="{{ route('tenant.deals.index') }}" style="color:var(--text-300);text-decoration:none">Deals</a>
            <span style="opacity:.4">›</span>
            <span>{{ \Illuminate\Support\Str::limit($deal->title, 40) }}</span>
        </div>
        <div class="page-title">Deal Details</div>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <a href="{{ route('tenant.deals.edit', $deal->id) }}" class="btn btn-secondary">
            <i class="ti ti-edit" style="font-size:14px"></i> Edit
        </a>
        <form method="POST" action="{{ route('tenant.deals.destroy', $deal->id) }}"
              data-confirm="Delete deal '{{ $deal->title }}'?" data-confirm-ok="Delete">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-secondary"
                    style="border-color:var(--red);color:var(--red)">
                <i class="ti ti-trash" style="font-size:14px"></i> Delete
            </button>
        </form>
    </div>
</div>

@if(session('success'))
<div style="display:flex;align-items:center;gap:10px;padding:11px 15px;background:var(--green-dim);border:1px solid var(--green);border-radius:8px;margin-bottom:14px;font-size:13px;color:var(--green);font-weight:500">
    <i class="ti ti-circle-check" style="font-size:16px"></i> {{ session('success') }}
</div>
@endif

<div class="ds-layout">

    {{-- ═══ MAIN ═══ --}}
    <div class="ds-main">

        {{-- Hero Card --}}
        <div class="ds-card">
            <div class="ds-hero">
                <div class="ds-hero-top">
                    <div class="ds-avatar">{{ $initials }}</div>
                    <div style="flex:1">
                        <div class="ds-title">{{ $deal->title }}</div>
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
                            <span class="ds-badge"
                                  style="background:{{ $stg['bg'] }};color:{{ $stg['text_color'] }};border:1px solid {{ $stg['color'] }}30">
                                <span style="width:5px;height:5px;border-radius:50%;background:{{ $stg['color'] }};display:inline-block"></span>
                                {{ $stg['label'] }}
                            </span>
                            <span class="ds-id">#DL-{{ str_pad($deal->id, 4, '0', STR_PAD_LEFT) }}</span>
                        </div>
                    </div>
                    <div style="text-align:right;flex-shrink:0">
                        <div class="ds-value">{{ $deal->formatted_value }}</div>
                        <div class="ds-value-lbl">Deal Value</div>
                    </div>
                </div>

                {{-- Pipeline Progress --}}
                @if($isWon || $isLost)
                <div style="display:flex;align-items:center;gap:10px;padding:12px 0 4px">
                    @if($isWon)
                    <div style="display:flex;align-items:center;gap:8px;padding:10px 16px;background:var(--green-dim);border:1px solid var(--green);border-radius:10px;font-size:13px;font-weight:600;color:var(--green);width:100%">
                        <i class="ti ti-trophy" style="font-size:18px"></i>
                        Deal Won!
                        @if($deal->actual_close_date)
                        <span style="font-weight:400;font-size:12px;margin-left:auto;color:var(--green)">
                            Closed {{ \Carbon\Carbon::parse($deal->actual_close_date)->format('M d, Y') }}
                        </span>
                        @endif
                    </div>
                    @else
                    <div style="display:flex;align-items:center;gap:8px;padding:10px 16px;background:var(--red-dim);border:1px solid var(--red);border-radius:10px;font-size:13px;font-weight:600;color:var(--red);width:100%">
                        <i class="ti ti-x" style="font-size:18px"></i>
                        Deal Lost
                        @if($deal->actual_close_date)
                        <span style="font-weight:400;font-size:12px;margin-left:auto;color:var(--red)">
                            {{ \Carbon\Carbon::parse($deal->actual_close_date)->format('M d, Y') }}
                        </span>
                        @endif
                    </div>
                    @endif
                </div>
                @else
                {{-- Active pipeline --}}
                <div class="ds-pip-steps">
                    @foreach($pipeStages as $pi => $ps)
                    @php
                        $isDone    = $currentIdx !== false && $pi < $currentIdx;
                        $isCurrent = $pi === $currentIdx;
                        $cls       = $isDone ? 'ds-pip-done' : ($isCurrent ? 'ds-pip-active' : 'ds-pip-pending');
                        $icon      = $isDone ? 'ti-check' : $pipeIcons[$pi];
                    @endphp
                    @if($pi > 0)
                    <div class="ds-pip-line {{ $isDone ? 'done' : '' }}"></div>
                    @endif
                    <div class="ds-pip-step {{ $cls }}">
                        <div class="ds-pip-dot">
                            <i class="ti {{ $icon }}" style="font-size:11px"></i>
                        </div>
                        <div class="ds-pip-lbl">{{ $pipeLabels[$pi] }}</div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Probability bar --}}
            <div class="ds-prob-wrap" style="border-top:1px solid var(--border-subtle)">
                <div style="font-size:11px;font-weight:600;color:var(--text-300);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Win Probability</div>
                <div class="ds-prob-row">
                    <div class="ds-prob-track">
                        <div class="ds-prob-fill" style="width:{{ $prob }}%;background:{{ $probColor }}"></div>
                    </div>
                    <span class="ds-prob-pct" style="color:{{ $probColor }}">{{ $prob }}%</span>
                </div>
            </div>

            {{-- Stats --}}
            <div class="ds-stats">
                <div>
                    <div class="ds-stat-val">{{ $daysOpen }}</div>
                    <div class="ds-stat-lbl">Days Open</div>
                </div>
                <div>
                    <div class="ds-stat-val">{{ $deal->followups_count ?? $deal->followups->count() }}</div>
                    <div class="ds-stat-lbl">Follow-ups</div>
                </div>
                <div>
                    <div class="ds-stat-val">
                        @if($deal->expected_close_date)
                            {{ \Carbon\Carbon::parse($deal->expected_close_date)->format('M d') }}
                        @else —
                        @endif
                    </div>
                    <div class="ds-stat-lbl">Close Date</div>
                </div>
                <div>
                    <div class="ds-stat-val">{{ $deal->assignedTo ? \Illuminate\Support\Str::limit($deal->assignedTo->name, 10) : '—' }}</div>
                    <div class="ds-stat-lbl">Assigned</div>
                </div>
            </div>
        </div>

        {{-- Lost reason --}}
        @if($isLost && $deal->lost_reason)
        <div class="ds-card">
            <div style="padding:16px 22px">
                <div class="ds-lost-label">Lost Reason</div>
                <div class="ds-lost-text">{{ $deal->lost_reason }}</div>
            </div>
        </div>
        @endif

        {{-- Deal Details --}}
        <div class="ds-card">
            <div class="ds-card-hd">
                <span class="ds-card-title">Deal Details</span>
            </div>
            <div class="ds-info-grid">
                <div class="ds-info-cell">
                    <div class="ds-info-lbl">Contact</div>
                    <div class="ds-info-val">
                        @if($deal->contact)
                        <a href="{{ route('tenant.contacts.show', $deal->contact_id) }}">
                            {{ $deal->contact->name }}
                            @if($deal->contact->company)
                            <span style="color:var(--text-400);font-size:12px"> · {{ $deal->contact->company }}</span>
                            @endif
                        </a>
                        @else
                        <span class="muted">No contact linked</span>
                        @endif
                    </div>
                </div>
                <div class="ds-info-cell">
                    <div class="ds-info-lbl">Linked Lead</div>
                    <div class="ds-info-val">
                        @if($deal->lead)
                        <a href="{{ route('tenant.leads.show', $deal->lead_id) }}">{{ $deal->lead->name }}</a>
                        @else
                        <span class="muted">No lead linked</span>
                        @endif
                    </div>
                </div>
                <div class="ds-info-cell">
                    <div class="ds-info-lbl">Assigned To</div>
                    <div class="ds-info-val">
                        @if($deal->assignedTo)
                        <div style="display:flex;align-items:center;gap:7px">
                            <div style="width:22px;height:22px;border-radius:50%;background:var(--accent-dim);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700">{{ $assignInit }}</div>
                            {{ $deal->assignedTo->name }}
                        </div>
                        @else <span class="muted">Unassigned</span>
                        @endif
                    </div>
                </div>
                <div class="ds-info-cell">
                    <div class="ds-info-lbl">Created By</div>
                    <div class="ds-info-val">
                        {{ $deal->createdBy?->name ?? '—' }}
                    </div>
                </div>
                <div class="ds-info-cell">
                    <div class="ds-info-lbl">Expected Close</div>
                    <div class="ds-info-val">
                        @if($deal->expected_close_date)
                        @php $cd = \Carbon\Carbon::parse($deal->expected_close_date); $ov = $cd->isPast() && $isOpen; @endphp
                        <span style="color:{{ $ov ? 'var(--red)' : 'inherit' }}">
                            @if($ov)<i class="ti ti-alert-triangle" style="font-size:12px"></i> @endif
                            {{ $cd->format('M d, Y') }}
                            <span style="font-size:11.5px;color:var(--text-400)">
                                ({{ $ov ? 'overdue' : $cd->diffForHumans() }})
                            </span>
                        </span>
                        @else <span class="muted">Not set</span>
                        @endif
                    </div>
                </div>
                <div class="ds-info-cell">
                    <div class="ds-info-lbl">Actual Close</div>
                    <div class="ds-info-val">
                        @if($deal->actual_close_date)
                        <span style="color:var(--green)">{{ \Carbon\Carbon::parse($deal->actual_close_date)->format('M d, Y') }}</span>
                        @else <span class="muted">—</span>
                        @endif
                    </div>
                </div>
                <div class="ds-info-cell">
                    <div class="ds-info-lbl">Created</div>
                    <div class="ds-info-val">{{ $deal->created_at->format('M d, Y') }}</div>
                </div>
                <div class="ds-info-cell">
                    <div class="ds-info-lbl">Last Updated</div>
                    <div class="ds-info-val">{{ $deal->updated_at->diffForHumans() }}</div>
                </div>
            </div>
        </div>

        {{-- Quotations --}}
        @php
            $qStatusColors = [
                'draft'    => ['var(--bg-hover)', 'var(--text-300)'],
                'sent'     => ['var(--accent-dim)', 'var(--accent)'],
                'accepted' => ['var(--green-dim)', 'var(--green)'],
                'rejected' => ['var(--red-dim)', 'var(--red)'],
            ];
        @endphp
        <div class="ds-card">
            <div class="ds-card-hd">
                <span class="ds-card-title">Quotations ({{ $deal->quotations->count() }})</span>
                <a href="{{ route('tenant.quotations.create', ['deal_id' => $deal->id]) }}" style="font-size:12px;color:var(--accent);text-decoration:none;font-weight:500">
                    <i class="ti ti-plus" style="font-size:12px"></i> New
                </a>
            </div>

            @if($deal->quotations->isNotEmpty())
            <div style="padding:9px 22px;background:var(--bg-elevated);border-bottom:1px solid var(--border-subtle);font-size:11.5px;color:var(--text-300)">
                <i class="ti ti-info-circle" style="font-size:12px"></i>
                Deal Value ({{ $deal->formatted_value }}) is your estimate — each quotation below has its own priced total, which can differ.
            </div>
            @endif

            @if($deal->quotations->isEmpty())
            <div class="ds-empty">
                <div class="ds-empty-icon"><i class="ti ti-file-invoice" style="font-size:20px;color:var(--text-400)"></i></div>
                <div class="ds-empty-txt">No quotations created for this deal yet.</div>
            </div>
            @else
            @foreach($deal->quotations as $q)
            @php [$qBg, $qTx] = $qStatusColors[$q->status] ?? ['var(--bg-hover)','var(--text-300)']; @endphp
            <a href="{{ route('tenant.quotations.show', $q->id) }}" class="ds-fu-item" style="text-decoration:none;align-items:center">
                <div class="ds-fu-icon" style="background:{{ $qBg }}">
                    <i class="ti ti-file-invoice" style="font-size:14px;color:{{ $qTx }}"></i>
                </div>
                <div style="flex:1">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:2px">
                        <span class="ds-fu-type">{{ $q->number }}</span>
                        <span class="ds-fu-status" style="background:{{ $qBg }};color:{{ $qTx }}">
                            {{ \App\Models\Quotation::statuses()[$q->status] ?? ucfirst($q->status) }}
                        </span>
                    </div>
                    <div class="ds-fu-time">
                        <i class="ti ti-calendar" style="font-size:11px"></i>
                        {{ $q->date?->format('M d, Y') }}
                    </div>
                </div>
                <div style="text-align:right">
                    <div style="font-size:13px;font-weight:600;color:var(--text-100);font-family:'DM Mono',monospace">
                        {{ $q->formatted_total }}
                    </div>
                    <div style="font-size:10px;color:var(--text-400)">Quotation Total</div>
                </div>
            </a>
            @endforeach
            @endif
        </div>

        {{-- Notes --}}
        <div class="ds-card">
            <div class="ds-card-hd">
                <span class="ds-card-title">Notes</span>
            </div>
            <div class="ds-notes {{ $deal->notes ? '' : 'empty' }}">
                {{ $deal->notes ?: 'No notes added for this deal.' }}
            </div>
        </div>

        {{-- Follow-ups --}}
        <div class="ds-card">
            <div class="ds-card-hd">
                <span class="ds-card-title">Follow-ups ({{ $deal->followups->count() }})</span>
            </div>

            @if($deal->followups->isEmpty())
            <div class="ds-empty">
                <div class="ds-empty-icon"><i class="ti ti-calendar" style="font-size:20px;color:var(--text-400)"></i></div>
                <div class="ds-empty-txt">No follow-ups scheduled for this deal.</div>
            </div>
            @else
            @foreach($deal->followups as $fu)
            @php
                [$fuIco, $fuBg, $fuTx] = $fuTypeIcons[$fu->type] ?? $fuTypeIcons['other'];
                [$fsBg, $fsTx] = $fuStatusColors[$fu->status] ?? ['var(--bg-hover)','var(--text-300)'];
                $fuInit = $fu->assignedTo
                    ? substr(collect(explode(' ', $fu->assignedTo->name))->map(fn($p)=>strtoupper($p[0]??''))->join(''),0,2)
                    : '';
            @endphp
            <div class="ds-fu-item">
                <div class="ds-fu-icon" style="background:{{ $fuBg }}">
                    <i class="ti {{ $fuIco }}" style="font-size:14px;color:{{ $fuTx }}"></i>
                </div>
                <div style="flex:1">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:2px">
                        <span class="ds-fu-type">{{ \App\Models\Followup::types()[$fu->type] ?? ucfirst($fu->type) }}</span>
                        <span class="ds-fu-status" style="background:{{ $fsBg }};color:{{ $fsTx }}">
                            {{ ucfirst($fu->status) }}
                        </span>
                    </div>
                    <div class="ds-fu-time">
                        <i class="ti ti-clock" style="font-size:11px"></i>
                        {{ $fu->scheduled_at ? $fu->scheduled_at->format('M d, Y · g:i A') : 'Not scheduled' }}
                    </div>
                    @if($fu->notes)
                    <div class="ds-fu-note">{{ $fu->notes }}</div>
                    @endif
                    @if($fu->outcome)
                    <div style="font-size:12px;color:var(--text-300);margin-top:4px">
                        <strong style="color:var(--text-200)">Outcome:</strong> {{ $fu->outcome }}
                    </div>
                    @endif
                    @if($fu->assignedTo)
                    <div style="font-size:11.5px;color:var(--text-400);margin-top:4px;display:flex;align-items:center;gap:5px">
                        <i class="ti ti-user" style="font-size:11px"></i>
                        {{ $fu->assignedTo->name }}
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
            @endif
        </div>

        {{-- Tasks --}}
        <div class="ds-card">
            <div class="ds-card-hd" style="display:flex;align-items:center;justify-content:space-between">
                <span class="ds-card-title">Tasks ({{ $deal->tasks->count() }})</span>
                <a href="{{ route('tenant.tasks.create', ['deal_id' => $deal->id]) }}"
                   style="font-size:12px;font-weight:600;color:var(--accent);text-decoration:none;display:inline-flex;align-items:center;gap:4px">
                    <i class="ti ti-plus" style="font-size:13px"></i>
                    Add Task
                </a>
            </div>

            @if($deal->tasks->isEmpty())
            <div class="ds-empty">
                <div class="ds-empty-icon"><i class="ti ti-checklist" style="font-size:20px;color:var(--text-400)"></i></div>
                <div class="ds-empty-txt">No tasks linked to this deal.</div>
            </div>
            @else
            @foreach($deal->tasks as $task)
            @php
                $tkStatus = $tkStatusColors[$task->status] ?? ['label' => ucfirst($task->status), 'color' => 'var(--text-300)', 'bg' => 'var(--bg-hover)', 'text_color' => 'var(--text-300)'];
                $tkPriority = $tkPriorityColors[$task->priority] ?? null;
            @endphp
            <div class="ds-fu-item">
                <div class="ds-fu-icon" style="background:{{ $tkStatus['bg'] }}">
                    <i class="ti ti-checklist" style="font-size:14px;color:{{ $tkStatus['text_color'] ?? $tkStatus['color'] }}"></i>
                </div>
                <div style="flex:1">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:2px">
                        <a href="{{ route('tenant.tasks.show', $task->id) }}" class="ds-fu-type" style="text-decoration:none">{{ $task->title }}</a>
                        <span class="ds-fu-status" style="background:{{ $tkStatus['bg'] }};color:{{ $tkStatus['text_color'] ?? $tkStatus['color'] }}">
                            {{ $tkStatus['label'] }}
                        </span>
                        @if($tkPriority)
                        <span class="ds-fu-status" style="background:{{ $tkPriority['bg'] }};color:{{ $tkPriority['color'] }}">
                            {{ $tkPriority['label'] }}
                        </span>
                        @endif
                    </div>
                    <div class="ds-fu-time">
                        <i class="ti ti-clock" style="font-size:11px"></i>
                        {{ $task->due_at ? $task->due_at->format('M d, Y') : 'No due date' }}
                    </div>
                    @if($task->assignedTo)
                    <div style="font-size:11.5px;color:var(--text-400);margin-top:4px;display:flex;align-items:center;gap:5px">
                        <i class="ti ti-user" style="font-size:11px"></i>
                        {{ $task->assignedTo->name }}
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
            @endif
        </div>

    </div>

    {{-- ═══ SIDEBAR ═══ --}}
    <div class="ds-sidebar">

        {{-- Quick Actions --}}
        <div class="ds-sc">
            <div class="ds-sc-title">Quick Actions</div>

            <a href="{{ route('tenant.deals.edit', $deal->id) }}" class="ds-qa-btn">
                <div class="ds-qa-icon" style="background:var(--accent-dim)"><i class="ti ti-edit" style="font-size:14px;color:var(--accent)"></i></div>
                Edit Deal
            </a>

            <a href="{{ route('tenant.quotations.create', ['deal_id' => $deal->id]) }}" class="ds-qa-btn">
                <div class="ds-qa-icon" style="background:var(--purple-dim)"><i class="ti ti-file-invoice" style="font-size:14px;color:var(--purple)"></i></div>
                Create Quotation
            </a>

            @if($isOpen || $isLost)
            <form method="POST" action="{{ route('tenant.deals.mark_won', $deal->id) }}">
                @csrf
                <button type="submit" class="ds-qa-btn" style="background:var(--green-dim);border-color:var(--green);color:var(--green)">
                    <div class="ds-qa-icon" style="background:#C4EDDF"><i class="ti ti-trophy" style="font-size:14px;color:var(--green)"></i></div>
                    Mark as Won 🎉
                </button>
            </form>
            @endif

            @if($isOpen || $isWon)
            <button type="button" onclick="document.getElementById('lostModal').showModal()"
                    class="ds-qa-btn" style="background:var(--red-dim);border-color:var(--red);color:var(--red)">
                <div class="ds-qa-icon" style="background:#F8D0D0"><i class="ti ti-x" style="font-size:14px;color:var(--red)"></i></div>
                Mark as Lost
            </button>
            @endif

            @if($isWon || $isLost)
            <form data-confirm="Reopen this deal?" data-confirm-ok="Reopen" data-confirm-danger="false" method="POST" action="{{ route('tenant.deals.update_stage', $deal->id) }}">
                @csrf @method('PATCH')
                <input type="hidden" name="stage" value="new">
                <button type="submit" class="ds-qa-btn">
                    <div class="ds-qa-icon" style="background:var(--accent-dim)"><i class="ti ti-refresh" style="font-size:14px;color:var(--accent)"></i></div>
                    Reopen Deal
                </button>
            </form>
            @endif
        </div>

        {{-- Deal Meta --}}
        <div class="ds-sc">
            <div class="ds-sc-title">Deal Info</div>
            <div style="display:flex;flex-direction:column;gap:0">
                <div class="ds-dl-row">
                    <span class="ds-dl-key">Deal ID</span>
                    <span class="ds-dl-val" style="font-family:'DM Mono',monospace">#DL-{{ str_pad($deal->id,4,'0',STR_PAD_LEFT) }}</span>
                </div>
                <div class="ds-dl-row">
                    <span class="ds-dl-key">Stage</span>
                    <span class="ds-dl-val">
                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:12px">
                            <span style="width:6px;height:6px;border-radius:50%;background:{{ $stg['color'] }};display:inline-block"></span>
                            {{ $stg['label'] }}
                        </span>
                    </span>
                </div>
                <div class="ds-dl-row">
                    <span class="ds-dl-key">Probability</span>
                    <span class="ds-dl-val" style="color:{{ $probColor }}">{{ $prob }}%</span>
                </div>
                <div class="ds-dl-row">
                    <span class="ds-dl-key">Value</span>
                    <span class="ds-dl-val" style="font-family:'DM Mono',monospace">{{ $deal->formatted_value }}</span>
                </div>
                <div class="ds-dl-row">
                    <span class="ds-dl-key">Days Open</span>
                    <span class="ds-dl-val">{{ $daysOpen }}</span>
                </div>
            </div>
        </div>

        {{-- Stage Reference --}}
        <div class="ds-sc">
            <div class="ds-sc-title">Pipeline Stages</div>
            <div style="display:flex;flex-direction:column;gap:7px">
                @foreach($cfgStages as $slug => $s)
                <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 8px;border-radius:7px;{{ $deal->stage===$slug ? 'background:'.$s['bg'].';border:1px solid '.$s['color'].'30' : '' }}">
                    <div style="display:flex;align-items:center;gap:7px">
                        <span style="width:7px;height:7px;border-radius:50%;background:{{ $s['color'] }};display:inline-block"></span>
                        <span style="font-size:12.5px;color:{{ $deal->stage===$slug ? $s['text_color'] : 'var(--text-200)' }};font-weight:{{ $deal->stage===$slug ? '600' : '500' }}">{{ $s['label'] }}</span>
                    </div>
                    <span style="font-size:11.5px;color:var(--text-400);font-family:'DM Mono',monospace">{{ $s['probability'] }}%</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Danger Zone --}}
        <div class="ds-sc" style="border-color:var(--red)">
            <div class="ds-sc-title" style="color:var(--red)">Danger Zone</div>
            <div style="font-size:12px;color:var(--text-300);margin-bottom:12px;line-height:1.5">
                Deal permanently remove ho jayega.
            </div>
            <form method="POST" action="{{ route('tenant.deals.destroy', $deal->id) }}"
                  data-confirm="Delete deal '{{ $deal->title }}'? This cannot be undone." data-confirm-ok="Delete">
                @csrf @method('DELETE')
                <button type="submit" class="btn"
                        style="width:100%;justify-content:center;background:var(--red-dim);border-color:var(--red);color:var(--red);font-size:12.5px">
                    <i class="ti ti-trash" style="font-size:14px"></i>
                    Delete Deal
                </button>
            </form>
        </div>

    </div>
</div>
</div>

{{-- Mark Lost Modal --}}
<dialog id="lostModal" style="border:1px solid var(--border-default);border-radius:14px;padding:24px;background:var(--bg-surface);width:420px;max-width:95vw;box-shadow:0 8px 32px rgba(0,0,0,.15)">
    <form method="POST" action="{{ route('tenant.deals.mark_lost', $deal->id) }}">
        @csrf
        <div style="font-size:16px;font-weight:600;color:var(--text-100);margin-bottom:4px">Mark Deal as Lost</div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:16px">
            Optionally add a reason to improve future pipeline analysis.
        </div>
        <label style="font-size:11.5px;font-weight:600;color:var(--text-200);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px">
            Lost Reason (optional)
        </label>
        <textarea name="lost_reason"
                  style="width:100%;padding:9px 12px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:8px;color:var(--text-100);font-family:'DM Sans',sans-serif;font-size:13px;resize:vertical;min-height:80px;outline:none;margin-bottom:16px;box-sizing:border-box"
                  placeholder="e.g. Budget constraints, competitor chosen...">{{ $deal->lost_reason }}</textarea>
        <div style="display:flex;gap:8px;justify-content:flex-end">
            <button type="button" onclick="document.getElementById('lostModal').close()"
                    class="btn btn-secondary">Cancel</button>
            <button type="submit" class="btn" style="background:var(--red-dim);border-color:var(--red);color:var(--red)">
                <i class="ti ti-x" style="font-size:14px"></i>
                Confirm Lost
            </button>
        </div>
    </form>
</dialog>

@endsection
