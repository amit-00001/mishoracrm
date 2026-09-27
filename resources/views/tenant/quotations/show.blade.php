@extends('layouts.app')
@section('title', 'Quotation — ' . $quotation->number)

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap');
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css');

.qs { font-family: 'DM Sans', var(--font), sans-serif; }

/* Layout */
.qs-layout { display:grid; grid-template-columns:minmax(0,1fr) 290px; gap:16px; margin-top:20px; }
@media(max-width:960px){ .qs-layout { grid-template-columns:1fr; } }
.qs-main    { display:flex; flex-direction:column; gap:14px; }
.qs-sidebar { display:flex; flex-direction:column; gap:14px; }

/* Cards */
.qs-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; overflow:hidden; }
.qs-card-head { padding:14px 20px 12px; border-bottom:1px solid var(--border-subtle); }
.qs-card-title { font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.6px; }

.qs-sc { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; padding:17px; }
.qs-sc-title { font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.6px; margin-bottom:13px; }

/* Hero */
.qs-hero { padding:22px; }
.qs-hero-top { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; margin-bottom:18px; flex-wrap:wrap; }
@media(max-width:480px){
    .qs-hero { padding:16px; }
    .qs-hero-top > div:last-child { text-align:left; }
    .qs-dates { gap:14px; }
}
.qs-number { font-size:22px; font-weight:600; color:var(--text-100); font-family:'DM Mono',monospace; letter-spacing:-.5px; margin-bottom:3px; }
.qs-from   { font-size:13px; color:var(--text-300); }

/* Status badge */
.qs-status-badge { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:20px; font-size:13px; font-weight:600; }

/* Timeline dates */
.qs-dates { display:flex; gap:20px; flex-wrap:wrap; }
.qs-date-item { display:flex; flex-direction:column; gap:2px; }
.qs-date-lbl  { font-size:10.5px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.5px; }
.qs-date-val  { font-size:13px; font-weight:500; color:var(--text-100); font-family:'DM Mono',monospace; }

/* Bill To card */
.qs-bill-to { padding:18px 20px; }
.qs-contact-row { display:flex; align-items:flex-start; gap:12px; }
.qs-avatar { width:42px; height:42px; border-radius:50%; background:var(--accent-dim); color:var(--accent); display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:600; flex-shrink:0; }
.qs-contact-name    { font-size:15px; font-weight:600; color:var(--text-100); margin-bottom:2px; }
.qs-contact-company { font-size:13px; color:var(--text-300); margin-bottom:8px; }
.qs-contact-detail  { display:flex; align-items:center; gap:5px; font-size:12.5px; color:var(--text-300); margin-top:3px; }
.qs-contact-detail a { color:var(--accent); text-decoration:none; }
.qs-contact-detail a:hover { text-decoration:underline; }

/* Items table */
.qs-items-table { width:100%; border-collapse:collapse; }
.qs-items-table thead tr { background:var(--bg-elevated); }
.qs-items-table th { padding:10px 14px; text-align:left; font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid var(--border-subtle); }
.qs-items-table th:last-child { text-align:right; }
.qs-items-table td { padding:13px 14px; font-size:13.5px; color:var(--text-100); border-bottom:1px solid var(--border-subtle); vertical-align:top; }
.qs-items-table tr:last-child td { border-bottom:none; }
.qs-items-table tbody tr:hover td { background:var(--bg-elevated); }
.qs-items-table .td-right { text-align:right; font-family:'DM Mono',monospace; font-weight:500; }
.qs-items-table .td-num { font-family:'DM Mono',monospace; color:var(--text-200); }
@media(max-width:768px) {
    .qs-items-table { width:100%; min-width:0; border-collapse:separate; border-spacing:0 12px; }
    .qs-items-table thead { display:none; }
    .qs-items-table tbody tr { display:block; background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-md); overflow:hidden; }
    .qs-items-table tbody tr:last-child td { border-bottom:1px solid var(--border-subtle); }
    .qs-items-table tbody tr td:last-child { border-bottom:none; }
    .qs-items-table td { display:flex; align-items:center; justify-content:space-between; gap:12px; text-align:right; }
    .qs-items-table .td-right, .qs-items-table .td-num { text-align:right; }
    .qs-items-table td::before { content:attr(data-label); font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.4px; color:var(--text-400); text-align:left; flex-shrink:0; }
}
.item-desc { font-size:12px; color:var(--text-400); margin-top:2px; }

/* Totals */
.qs-totals-wrap { padding:16px 20px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); display:flex; justify-content:flex-end; }
.qs-totals-table { width:280px; }
.qs-totals-table tr td { padding:5px 0; font-size:13px; color:var(--text-200); }
.qs-totals-table tr td:last-child { text-align:right; font-family:'DM Mono',monospace; font-weight:500; color:var(--text-100); }
.qs-totals-table .grand { padding-top:10px; font-size:15px; font-weight:600; color:var(--text-100); border-top:1px solid var(--border-default); }
.qs-totals-table .grand td:last-child { color:var(--accent); font-size:17px; }
@media(max-width:480px){ .qs-totals-table { width:100%; } }

/* Linked Deal / Lead rows */
.qs-link-row { padding:14px 20px; display:flex; align-items:center; justify-content:space-between; gap:12px; }
@media(max-width:480px){ .qs-link-row { flex-direction:column; align-items:stretch; } .qs-link-row .btn { justify-content:center; } }

/* Notes / Terms */
.qs-text-section { padding:18px 20px; border-top:1px solid var(--border-subtle); }
.qs-text-label { font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.5px; margin-bottom:8px; }
.qs-text-content { font-size:13px; color:var(--text-200); line-height:1.6; white-space:pre-wrap; }

/* Sidebar actions */
.qs-action-btn { display:flex; align-items:center; gap:9px; padding:10px 13px; border-radius:9px; border:1px solid var(--border-default); background:var(--bg-elevated); font-family:'DM Sans',var(--font),sans-serif; font-size:13px; font-weight:500; cursor:pointer; transition:all .15s; width:100%; text-align:left; text-decoration:none; color:var(--text-100); }
.qs-action-btn:hover { background:var(--bg-surface); border-color:var(--border-strong); }
.qs-action-btn + .qs-action-btn { margin-top:7px; }
.qs-act-icon { width:28px; height:28px; border-radius:7px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }

/* Status quick-change */
.qs-status-opt { display:flex; align-items:center; gap:8px; padding:8px 11px; border-radius:8px; border:1.5px solid var(--border-default); cursor:pointer; transition:all .15s; font-size:12.5px; font-weight:500; background:var(--bg-input); color:var(--text-200); font-family:'DM Sans',var(--font),sans-serif; width:100%; text-align:left; }
.qs-status-opt + .qs-status-opt { margin-top:6px; }
.qs-status-opt:hover { border-color:var(--border-strong); color:var(--text-100); }
.qs-sdot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }

/* Detail list */
.dl-row { display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid var(--border-subtle); }
.dl-row:last-child { border-bottom:none; }
.dl-key { font-size:12px; color:var(--text-300); }
.dl-val { font-size:12.5px; font-weight:500; color:var(--text-100); text-align:right; }

/* Copy toast */
.copy-toast { position:fixed; bottom:20px; right:20px; background:var(--accent); color:#fff; padding:9px 16px; border-radius:8px; font-size:13px; font-weight:500; opacity:0; transition:opacity .2s; pointer-events:none; z-index:9999; display:flex; align-items:center; gap:7px; }
.copy-toast.show { opacity:1; }

/* Watermark for status */
.status-watermark { position:absolute; top:50%; right:30px; transform:translateY(-50%) rotate(-15deg); font-size:60px; font-weight:700; opacity:.04; text-transform:uppercase; pointer-events:none; letter-spacing:4px; }
</style>
@endpush

@section('content')
@php
    $qConfig     = config('quotation');
    $cfgStatuses = $qConfig['statuses'];
    $st          = $cfgStatuses[$quotation->status] ?? $cfgStatuses['draft'];
    $items       = $quotation->items ?? [];

    $initials = '';
    if($quotation->contact) {
        $initials = collect(explode(' ', $quotation->contact->name))
            ->map(fn($p) => strtoupper($p[0]??''))->join('');
        $initials = substr($initials, 0, 2);
    }

    $isExpired = $quotation->valid_until
        && \Carbon\Carbon::parse($quotation->valid_until)->isPast()
        && !in_array($quotation->status, ['accepted','rejected']);

    $canEdit    = $quotation->status !== 'accepted';
    $canConvert = $quotation->status === 'accepted' && !$quotation->invoice;
    $sym        = $quotation->currencySymbol();
@endphp

<div class="qs">

    {{-- Header --}}
    <div class="page-head">
        <div>
            <div style="font-size:12px;color:var(--text-300);margin-bottom:4px;display:flex;align-items:center;gap:5px">
                <a href="{{ route('tenant.quotations.index') }}" style="color:var(--text-300);text-decoration:none">Quotations</a>
                <span style="opacity:.4">›</span>
                <span>{{ $quotation->number }}</span>
            </div>
            <div class="page-title">Quotation Detail</div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="{{ route('tenant.quotations.pdf',$quotation->id) }}" target="_blank" class="btn btn-secondary">
                <i class="ti ti-download" style="font-size:14px"></i> PDF
            </a>
            @if($canEdit)
            <a href="{{ route('tenant.quotations.edit',$quotation->id) }}" class="btn btn-secondary">
                <i class="ti ti-edit" style="font-size:14px"></i> Edit
            </a>
            @endif
            @if($canConvert)
            <form method="POST" action="{{ route('tenant.quotations.convert',$quotation->id) }}" style="display:inline">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-receipt" style="font-size:14px"></i> Convert to Invoice
                </button>
            </form>
            @endif
        </div>
    </div>

    {{-- Flash --}}
    @foreach(['success','error','info'] as $type)
    @if(session($type))
    <div style="display:flex;align-items:center;gap:10px;padding:11px 15px;background:{{ $type==='success'?'var(--green-dim)':($type==='error'?'var(--red-dim)':'var(--accent-dim)') }};border:1px solid {{ $type==='success'?'var(--green)':($type==='error'?'var(--red)':'var(--accent)') }};border-radius:8px;margin-bottom:14px;font-size:13px;color:{{ $type==='success'?'var(--green)':($type==='error'?'var(--red)':'var(--accent)') }};font-weight:500">
        <i class="ti ti-{{ $type==='success'?'circle-check':($type==='error'?'alert-circle':'info-circle') }}" style="font-size:16px"></i>
        {{ session($type) }}
    </div>
    @endif
    @endforeach

    <div class="qs-layout">

        {{-- ── MAIN ── --}}
        <div class="qs-main">

            {{-- Hero / Header Card --}}
            <div class="qs-card" style="position:relative;overflow:hidden">

                {{-- Status watermark --}}
                <div class="status-watermark" style="color:{{ $st['color'] }}">{{ $st['label'] }}</div>

                <div class="qs-hero">
                    <div class="qs-hero-top">
                        <div>
                            <div class="qs-number">{{ $quotation->number }}</div>
                            <div class="qs-from">{{ $tenant->name ?? auth()->user()->name }}</div>
                        </div>
                        <div style="text-align:right">
                            <div class="qs-status-badge"
                                 style="background:{{ $st['bg'] }};color:{{ $st['text_color'] }};border:1px solid {{ $st['color'] }}40">
                                <i class="ti {{ $st['icon'] }}" style="font-size:14px"></i>
                                {{ $st['label'] }}
                            </div>
                            @if($isExpired)
                            <div style="font-size:11.5px;color:var(--red);margin-top:5px;display:flex;align-items:center;gap:4px;justify-content:flex-end">
                                <i class="ti ti-clock-x" style="font-size:12px"></i> Expired
                            </div>
                            @endif
                        </div>
                    </div>

                    <div class="qs-dates">
                        <div class="qs-date-item">
                            <div class="qs-date-lbl">Date</div>
                            <div class="qs-date-val">
                                {{ $quotation->date ? \Carbon\Carbon::parse($quotation->date)->format('M d, Y') : '—' }}
                            </div>
                        </div>
                        @if($quotation->valid_until)
                        <div class="qs-date-item">
                            <div class="qs-date-lbl">Valid Until</div>
                            <div class="qs-date-val" style="{{ $isExpired?'color:var(--red)':'' }}">
                                {{ \Carbon\Carbon::parse($quotation->valid_until)->format('M d, Y') }}
                            </div>
                        </div>
                        @endif
                        <div class="qs-date-item">
                            <div class="qs-date-lbl">Created By</div>
                            <div class="qs-date-val">{{ $quotation->createdBy?->name ?? '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bill To --}}
            @if($quotation->contact)
            <div class="qs-card">
                <div class="qs-card-head">
                    <div class="qs-card-title">
                        <i class="ti ti-user" style="font-size:13px;margin-right:5px"></i> Bill To
                    </div>
                </div>
                <div class="qs-bill-to">
                    <div class="qs-contact-row">
                        <div class="qs-avatar">{{ $initials }}</div>
                        <div style="flex:1">
                            <div class="qs-contact-name">{{ $quotation->contact->name }}</div>
                            @if($quotation->contact->company)
                            <div class="qs-contact-company">{{ $quotation->contact->company }}</div>
                            @endif
                            @if($quotation->contact->phone)
                            <div class="qs-contact-detail">
                                <i class="ti ti-phone" style="font-size:12px"></i>
                                <a href="tel:{{ $quotation->contact->phone }}">{{ $quotation->contact->phone }}</a>
                            </div>
                            @endif
                            @if($quotation->contact->email)
                            <div class="qs-contact-detail">
                                <i class="ti ti-mail" style="font-size:12px"></i>
                                <a href="mailto:{{ $quotation->contact->email }}">{{ $quotation->contact->email }}</a>
                            </div>
                            @endif
                            @if($quotation->contact->address || $quotation->contact->city)
                            <div class="qs-contact-detail">
                                <i class="ti ti-map-pin" style="font-size:12px"></i>
                                <span>
                                    {{ $quotation->contact->address ? $quotation->contact->address.', ' : '' }}
                                    {{ collect([$quotation->contact->city,$quotation->contact->state])->filter()->join(', ') }}
                                </span>
                            </div>
                            @endif
                            @if($quotation->contact->gst_number)
                            <div class="qs-contact-detail">
                                <i class="ti ti-receipt-tax" style="font-size:12px"></i>
                                <span>GST: <strong style="font-family:'DM Mono',monospace;color:var(--text-100)">{{ $quotation->contact->gst_number }}</strong></span>
                                <button onclick="copyText('{{ $quotation->contact->gst_number }}')"
                                        style="background:none;border:none;cursor:pointer;color:var(--text-400);padding:0;font-size:12px" title="Copy GST">
                                    <i class="ti ti-copy"></i>
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            {{-- Line Items --}}
            <div class="qs-card">
                <div class="qs-card-head">
                    <div class="qs-card-title">
                        <i class="ti ti-list-details" style="font-size:13px;margin-right:5px"></i>
                        Line Items ({{ count($items) }})
                    </div>
                </div>
                <div style="overflow-x:auto">
                    <table class="qs-items-table">
                        <thead>
                            <tr>
                                <th style="width:5%">#</th>
                                <th style="width:25%">Item / Service</th>
                                <th style="width:22%">Description</th>
                                <th style="width:9%;text-align:right">Qty</th>
                                <th style="width:13%;text-align:right">Rate ({{ $sym }})</th>
                                <th style="width:10%;text-align:right">GST %</th>
                                <th style="width:16%;text-align:right">Amount ({{ $sym }})</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $idx => $item)
                            <tr>
                                <td style="font-family:'DM Mono',monospace;font-size:12px;color:var(--text-400)" data-label="#">{{ $idx+1 }}</td>
                                <td data-label="Item / Service">
                                    <div style="font-weight:500;color:var(--text-100)">{{ $item['name'] ?? '—' }}</div>
                                </td>
                                <td data-label="Description">
                                    <div class="item-desc">{{ $item['description'] ?? '—' }}</div>
                                </td>
                                <td class="td-right td-num" data-label="Qty">{{ number_format($item['quantity'] ?? 0, 2) }}</td>
                                <td class="td-right td-num" data-label="Rate ({{ $sym }})">{{ number_format($item['rate'] ?? 0, 2) }}</td>
                                <td class="td-right td-num" data-label="GST %">{{ number_format($item['tax_percent'] ?? $quotation->tax_percent ?? 0, 1) }}%</td>
                                <td class="td-right" style="font-family:'DM Mono',monospace;font-weight:600;color:var(--text-100)" data-label="Amount ({{ $sym }})">
                                    {{ number_format($item['amount'] ?? 0, 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Totals --}}
                <div class="qs-totals-wrap">
                    <table class="qs-totals-table">
                        <tr>
                            <td>Subtotal</td>
                            <td>{{ $sym }}{{ number_format($quotation->subtotal ?? 0, 2) }}</td>
                        </tr>
                        @if(($quotation->discount ?? 0) > 0)
                        <tr>
                            <td>Discount</td>
                            <td style="color:var(--red)">-{{ $sym }}{{ number_format($quotation->discount, 2) }}</td>
                        </tr>
                        @endif
                        @foreach($quotation->gstLines() as $line)
                        <tr>
                            <td>{{ $line['label'] }}</td>
                            <td style="color:var(--green)">+{{ $sym }}{{ number_format($line['amount'], 2) }}</td>
                        </tr>
                        @endforeach
                        <tr class="grand">
                            <td><strong>Total</strong></td>
                            <td><strong>{{ $sym }}{{ number_format($quotation->total ?? 0, 2) }}</strong></td>
                        </tr>
                    </table>
                </div>

                {{-- Notes --}}
                @if($quotation->notes)
                <div class="qs-text-section">
                    <div class="qs-text-label">
                        <i class="ti ti-notes" style="font-size:12px;margin-right:4px"></i> Notes
                    </div>
                    <div class="qs-text-content">{{ $quotation->notes }}</div>
                </div>
                @endif

                {{-- Terms --}}
                @if($quotation->terms)
                <div class="qs-text-section" style="background:var(--bg-elevated)">
                    <div class="qs-text-label">
                        <i class="ti ti-file-certificate" style="font-size:12px;margin-right:4px"></i> Terms & Conditions
                    </div>
                    <div class="qs-text-content">{{ $quotation->terms }}</div>
                </div>
                @endif
            </div>

            {{-- Customer Response (self-serve accept/reject) --}}
            @if($quotation->customer_responded_at)
            <div class="qs-card">
                <div class="qs-card-head">
                    <div class="qs-card-title">
                        <i class="ti ti-signature" style="font-size:13px;margin-right:5px"></i> Customer Response
                    </div>
                </div>
                <div style="padding:16px 20px">
                    @if($quotation->status === 'accepted' && $quotation->signed_name)
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
                        <div>
                            <div style="font-size:13px;color:var(--text-300)">Accepted by</div>
                            <div style="font-size:15px;font-weight:600;color:var(--text-100)">{{ $quotation->signed_name }}</div>
                            <div style="font-size:11.5px;color:var(--text-400);margin-top:2px">
                                {{ $quotation->customer_responded_at->format('d M Y, h:i A') }}
                                @if($quotation->customer_response_ip) · IP {{ $quotation->customer_response_ip }} @endif
                            </div>
                        </div>
                        @if($quotation->signature_data)
                        <img src="{{ $quotation->signature_data }}" alt="Signature" style="max-width:200px;max-height:70px;background:#fff;border:1px solid var(--border-subtle);border-radius:8px;padding:4px">
                        @endif
                    </div>
                    @elseif($quotation->status === 'rejected')
                    <div style="font-size:13px;color:var(--text-300)">
                        Rejected on {{ $quotation->customer_responded_at->format('d M Y, h:i A') }}
                        @if($quotation->rejected_reason)
                        <div style="margin-top:6px;color:var(--text-100)">Reason: {{ $quotation->rejected_reason }}</div>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Linked Deal --}}
            @if($quotation->deal)
            <div class="qs-card">
                <div class="qs-card-head">
                    <div class="qs-card-title">
                        <i class="ti ti-briefcase" style="font-size:13px;margin-right:5px"></i> Linked Deal
                    </div>
                </div>
                <div class="qs-link-row">
                    <div>
                        <div style="font-size:13.5px;font-weight:600;color:var(--text-100)">{{ $quotation->deal->title }}</div>
                        <div style="font-size:12px;color:var(--text-300);margin-top:2px">
                            Stage: {{ \App\Models\Deal::stages()[$quotation->deal->stage] ?? ucfirst($quotation->deal->stage) }}
                            <span style="margin:0 4px;opacity:.5">·</span>
                            Deal Value: {{ $quotation->deal->formatted_value }}
                        </div>
                    </div>
                    <a href="{{ route('tenant.deals.show', $quotation->deal->id) }}"
                       class="btn btn-secondary" style="font-size:12px;padding:6px 12px">
                        <i class="ti ti-arrow-right" style="font-size:13px"></i> View Deal
                    </a>
                </div>
            </div>
            @endif

            {{-- Linked Lead --}}
            @if($quotation->lead)
            <div class="qs-card">
                <div class="qs-card-head">
                    <div class="qs-card-title">
                        <i class="ti ti-target" style="font-size:13px;margin-right:5px"></i> Linked Lead
                    </div>
                </div>
                <div class="qs-link-row">
                    <div>
                        <div style="font-size:13.5px;font-weight:600;color:var(--text-100)">{{ $quotation->lead->name }}</div>
                        @if($quotation->lead->phone)
                        <div style="font-size:12px;color:var(--text-300);margin-top:2px">{{ $quotation->lead->phone }}</div>
                        @endif
                    </div>
                    <a href="{{ route('tenant.leads.show', ['tenant'=>auth()->user()->tenant->subdomain,'id'=>$quotation->lead->id]) }}"
                       class="btn btn-secondary" style="font-size:12px;padding:6px 12px">
                        <i class="ti ti-arrow-right" style="font-size:13px"></i> View Lead
                    </a>
                </div>
            </div>
            @endif

            {{-- Version history --}}
            @if($quotation->parentQuotation || $quotation->revisions->isNotEmpty())
            <div class="qs-card">
                <div class="qs-card-head">
                    <div class="qs-card-title">
                        <i class="ti ti-history" style="font-size:13px;margin-right:5px"></i> Version History
                    </div>
                </div>
                <div>
                    @if($quotation->parentQuotation)
                    <div class="qs-link-row">
                        <div>
                            <div style="font-size:13.5px;font-weight:600;color:var(--text-100)">{{ $quotation->parentQuotation->number }} (v{{ $quotation->parentQuotation->version }})</div>
                            <div style="font-size:12px;color:var(--text-300);margin-top:2px">Original quotation this version was cloned from</div>
                        </div>
                        <a href="{{ route('tenant.quotations.show', $quotation->parentQuotation->id) }}"
                           class="btn btn-secondary" style="font-size:12px;padding:6px 12px">View</a>
                    </div>
                    @endif
                    @foreach($quotation->revisions as $revision)
                    <div class="qs-link-row" style="border-top:1px solid var(--border-subtle)">
                        <div>
                            <div style="font-size:13.5px;font-weight:600;color:var(--text-100)">{{ $revision->number }} (v{{ $revision->version }})</div>
                            <div style="font-size:12px;color:var(--text-300);margin-top:2px">{{ ucfirst($revision->status) }} · {{ $revision->created_at->format('d M Y') }}</div>
                        </div>
                        <a href="{{ route('tenant.quotations.show', $revision->id) }}"
                           class="btn btn-secondary" style="font-size:12px;padding:6px 12px">View</a>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

        </div>{{-- /qs-main --}}

        {{-- ── SIDEBAR ── --}}
        <div class="qs-sidebar">

            {{-- Amount Summary --}}
            <div class="qs-sc" style="text-align:center">
                <div style="font-size:13px;color:var(--text-300);font-family:'DM Mono',monospace;margin-bottom:4px">
                    {{ $quotation->number }}
                </div>
                <div style="font-size:34px;font-weight:600;color:var(--accent);font-family:'DM Mono',monospace;letter-spacing:-1.5px;line-height:1">
                    {{ $sym }}{{ number_format($quotation->total ?? 0, 2) }}
                </div>
                <div style="margin-top:10px">
                    <span class="qs-status-badge"
                          style="background:{{ $st['bg'] }};color:{{ $st['text_color'] }};border:1px solid {{ $st['color'] }}40;font-size:12px">
                        <i class="ti {{ $st['icon'] }}" style="font-size:13px"></i>
                        {{ $st['label'] }}
                    </span>
                </div>
            </div>

            {{-- Change Status --}}
            <div class="qs-sc">
                <div class="qs-sc-title">Update Status</div>
                @foreach($cfgStatuses as $slug => $s)
                @if($slug !== $quotation->status)
                <form method="POST" action="{{ route('tenant.quotations.update_status',$quotation->id) }}">
                    @csrf
                    <input type="hidden" name="status" value="{{ $slug }}">
                    <button type="button"
                            class="qs-status-opt"
                            style="{{ $quotation->status===$slug ? 'background:'.$s['bg'].';border-color:'.$s['color'].';color:'.$s['text_color'] : '' }}"
                            onclick="this.closest('form').submit()">
                        <span class="qs-sdot" style="background:{{ $s['color'] }}"></span>
                        Mark as {{ $s['label'] }}
                    </button>
                </form>
                @endif
                @endforeach
            </div>

            {{-- Actions --}}
            <div class="qs-sc">
                <div class="qs-sc-title">Actions</div>

                <a href="{{ route('tenant.quotations.pdf',$quotation->id) }}" target="_blank" class="qs-action-btn">
                    <div class="qs-act-icon" style="background:var(--amber-dim)">
                        <i class="ti ti-file-download" style="font-size:15px;color:var(--amber)"></i>
                    </div>
                    Download PDF
                </a>

                @if(!in_array($quotation->status, ['accepted', 'rejected']))
                <button type="button" class="qs-action-btn" style="margin-top:7px" onclick="copyText('{{ $quotation->publicUrl() }}')">
                    <div class="qs-act-icon" style="background:var(--purple-dim)">
                        <i class="ti ti-link" style="font-size:15px;color:var(--purple)"></i>
                    </div>
                    Copy Shareable Link
                </button>
                @endif

                @php
                    $sendToEmail = $quotation->contact?->primaryEmail();
                    $sendCcCount = $quotation->contact ? count($quotation->contact->ccEmails()) : 0;
                @endphp
                @if($sendToEmail)
                <form method="POST" action="{{ route('tenant.quotations.send',$quotation->id) }}"
                      data-confirm="Send this quotation to {{ $sendToEmail }}{{ $sendCcCount ? ' (cc: '.$sendCcCount.')' : '' }}?" data-confirm-ok="Send" data-confirm-danger="false">
                    @csrf
                    <button type="submit" class="qs-action-btn" style="margin-top:7px">
                        <div class="qs-act-icon" style="background:var(--accent-dim)">
                            <i class="ti ti-send" style="font-size:15px;color:var(--accent)"></i>
                        </div>
                        Send to {{ $sendToEmail }}@if($sendCcCount) (cc: {{ $sendCcCount }})@endif
                    </button>
                </form>
                @endif

                @if($quotation->contact?->phone)
                <form method="POST" action="{{ route('tenant.quotations.send_whatsapp',$quotation->id) }}"
                      data-confirm="Send this quotation to {{ $quotation->contact->phone }} via WhatsApp?" data-confirm-ok="Send" data-confirm-danger="false">
                    @csrf
                    <button type="submit" class="qs-action-btn" style="margin-top:7px">
                        <div class="qs-act-icon" style="background:var(--green-dim)">
                            <i class="ti ti-brand-whatsapp" style="font-size:15px;color:var(--green)"></i>
                        </div>
                        Send via WhatsApp
                    </button>
                </form>
                @endif

                @if($canEdit)
                <a href="{{ route('tenant.quotations.edit',$quotation->id) }}" class="qs-action-btn" style="margin-top:7px">
                    <div class="qs-act-icon" style="background:var(--purple-dim)">
                        <i class="ti ti-edit" style="font-size:15px;color:var(--purple)"></i>
                    </div>
                    Edit Quotation
                </a>
                @endif

                @if($canConvert)
                <form method="POST" action="{{ route('tenant.quotations.convert',$quotation->id) }}">
                    @csrf
                    <button type="submit" class="qs-action-btn" style="margin-top:7px;background:var(--green-dim);border-color:var(--green);color:var(--green)">
                        <div class="qs-act-icon" style="background:var(--green-dim)">
                            <i class="ti ti-receipt" style="font-size:15px;color:var(--green)"></i>
                        </div>
                        Convert to Invoice
                    </button>
                </form>
                @endif

                @can('quotations.create')
                <form method="POST" action="{{ route('tenant.quotations.new_version',$quotation->id) }}"
                      data-confirm="Create a new draft version cloned from {{ $quotation->number }}?" data-confirm-danger="false">
                    @csrf
                    <button type="submit" class="qs-action-btn" style="margin-top:7px">
                        <div class="qs-act-icon" style="background:#FDECEA">
                            <i class="ti ti-copy" style="font-size:15px;color:#C0392B"></i>
                        </div>
                        Create New Version
                    </button>
                </form>
                @endcan

                @if($quotation->invoice)
                <a href="{{ route('tenant.invoices.show',$quotation->invoice->id) }}" class="qs-action-btn" style="margin-top:7px">
                    <div class="qs-act-icon" style="background:var(--green-dim)">
                        <i class="ti ti-receipt-2" style="font-size:15px;color:var(--green)"></i>
                    </div>
                    View Invoice
                </a>
                @endif
            </div>

            {{-- Details --}}
            <div class="qs-sc">
                <div class="qs-sc-title">Details</div>
                <div>
                    <div class="dl-row">
                        <span class="dl-key">Quotation #</span>
                        <span class="dl-val" style="font-family:'DM Mono',monospace;font-size:12.5px">{{ $quotation->number }}</span>
                    </div>
                    <div class="dl-row">
                        <span class="dl-key">Items</span>
                        <span class="dl-val">{{ count($items) }}</span>
                    </div>
                    <div class="dl-row">
                        <span class="dl-key">Subtotal</span>
                        <span class="dl-val" style="font-family:'DM Mono',monospace">{{ $sym }}{{ number_format($quotation->subtotal??0,2) }}</span>
                    </div>
                    @if(($quotation->discount??0)>0)
                    <div class="dl-row">
                        <span class="dl-key">Discount</span>
                        <span class="dl-val" style="color:var(--red);font-family:'DM Mono',monospace">-{{ $sym }}{{ number_format($quotation->discount,2) }}</span>
                    </div>
                    @endif
                    @foreach($quotation->gstLines() as $line)
                    <div class="dl-row">
                        <span class="dl-key">{{ $line['label'] }}</span>
                        <span class="dl-val" style="font-family:'DM Mono',monospace">{{ $sym }}{{ number_format($line['amount'],2) }}</span>
                    </div>
                    @endforeach
                    <div class="dl-row">
                        <span class="dl-key">Created</span>
                        <span class="dl-val">{{ $quotation->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="dl-row">
                        <span class="dl-key">Updated</span>
                        <span class="dl-val" style="font-size:12px">{{ $quotation->updated_at->diffForHumans() }}</span>
                    </div>
                </div>
            </div>

            {{-- Danger Zone --}}
            @if($quotation->status !== 'accepted')
            <div class="qs-sc" style="border-color:var(--red)">
                <div class="qs-sc-title" style="color:var(--red)">Danger Zone</div>
                <form method="POST" action="{{ route('tenant.quotations.destroy',$quotation->id) }}"
                      data-confirm="Delete quotation {{ $quotation->number }}?" data-confirm-ok="Delete">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn" style="width:100%;justify-content:center;background:var(--red-dim);border-color:var(--red);color:var(--red);font-size:12.5px">
                        <i class="ti ti-trash" style="font-size:14px"></i> Delete Quotation
                    </button>
                </form>
            </div>
            @endif

        </div>{{-- /qs-sidebar --}}
    </div>
</div>

{{-- Copy toast --}}
<div id="copyToast" class="copy-toast">
    <i class="ti ti-check" style="font-size:14px"></i> Copied!
</div>
@endsection

@push('scripts')
<script>
function copyText(text){
    navigator.clipboard.writeText(text).then(function(){
        const t = document.getElementById('copyToast');
        t.classList.add('show');
        setTimeout(() => t.classList.remove('show'), 2000);
    });
}
</script>
@endpush