@extends('layouts.app')
@section('title', $vendor->name . ' — Vendor')

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap');
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css');

.vs-page { font-family: 'DM Sans', var(--font), sans-serif; }
.vs-layout { display:grid; grid-template-columns:minmax(0,1fr) 280px; gap:16px; margin-top:20px; }
@media(max-width:900px){ .vs-layout { grid-template-columns:1fr; } }
.vs-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; overflow:hidden; }
.vs-card-head { padding:14px 20px 12px; border-bottom:1px solid var(--border-subtle); }
.vs-card-title { font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.6px; }
.vs-hero { padding:20px; }
.vs-hero-top { display:flex; align-items:flex-start; gap:16px; }
.vs-avatar { width:52px; height:52px; border-radius:50%; background:var(--accent-dim); color:var(--accent); display:flex; align-items:center; justify-content:center; font-size:17px; font-weight:600; flex-shrink:0; }
.vs-name { font-size:19px; font-weight:600; color:var(--text-100); letter-spacing:-0.3px; margin-bottom:2px; }
.vs-sub  { font-size:13px; color:var(--text-300); }
.vs-info-grid { display:grid; grid-template-columns:1fr 1fr; }
.vs-info-cell { padding:11px 16px; border-bottom:1px solid var(--border-subtle); border-right:1px solid var(--border-subtle); }
.vs-info-cell:nth-child(even) { border-right:none; }
.vs-info-cell:nth-last-child(-n+2) { border-bottom:none; }
.vs-info-lbl { font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.5px; margin-bottom:3px; }
.vs-info-val { font-size:13.5px; font-weight:500; color:var(--text-100); }
.vs-info-val.muted { color:var(--text-400); font-style:italic; font-weight:400; }
.vs-sc { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; padding:17px; }
.vs-sc-title { font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.6px; margin-bottom:13px; }
.vs-action-btn { display:flex; align-items:center; gap:9px; padding:10px 13px; border-radius:9px; border:1px solid var(--border-default); background:var(--bg-elevated); font-family:'DM Sans',var(--font),sans-serif; font-size:13px; font-weight:500; cursor:pointer; transition:all .15s; width:100%; text-align:left; text-decoration:none; color:var(--text-100); }
.vs-action-btn:hover { background:var(--bg-surface); border-color:var(--border-strong); }
.vs-action-btn + .vs-action-btn { margin-top:7px; }
.vs-act-icon { width:28px; height:28px; border-radius:7px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.po-row { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:12px 20px; border-bottom:1px solid var(--border-subtle); }
.po-row:last-child { border-bottom:none; }
</style>
@endpush

@section('content')
@php
    $initials = collect(explode(' ', $vendor->name))->map(fn($p) => strtoupper($p[0]??''))->join('');
    $initials = substr($initials, 0, 2);
@endphp

<div class="vs-page">

    <div class="page-head">
        <div>
            <div style="font-size:12px;color:var(--text-300);margin-bottom:4px;display:flex;align-items:center;gap:5px">
                <a href="{{ route('tenant.vendors.index') }}" style="color:var(--text-300);text-decoration:none">Vendors</a>
                <span style="opacity:.4">›</span>
                <span>{{ $vendor->name }}</span>
            </div>
            <div class="page-title">Vendor Detail</div>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            @can('modify', $vendor)
            <a href="{{ route('tenant.vendors.edit',$vendor->id) }}" class="btn btn-secondary">
                <i class="ti ti-edit" style="font-size:14px"></i> Edit
            </a>
            @endcan
            @can('create', \App\Models\PurchaseOrder::class)
            <a href="{{ route('tenant.purchase-orders.create') }}" class="btn btn-primary">
                <i class="ti ti-plus" style="font-size:14px"></i> New Purchase Order
            </a>
            @endcan
        </div>
    </div>

    @foreach(['success','error'] as $type)
    @if(session($type))
    <div style="display:flex;align-items:center;gap:10px;padding:11px 15px;background:{{ $type==='success'?'var(--green-dim)':'var(--red-dim)' }};border:1px solid {{ $type==='success'?'var(--green)':'var(--red)' }};border-radius:8px;margin-bottom:14px;font-size:13px;color:{{ $type==='success'?'var(--green)':'var(--red)' }};font-weight:500">
        {{ session($type) }}
    </div>
    @endif
    @endforeach

    <div class="vs-layout">
        <div style="display:flex;flex-direction:column;gap:14px">

            <div class="vs-card">
                <div class="vs-hero">
                    <div class="vs-hero-top">
                        <div class="vs-avatar">{{ $initials }}</div>
                        <div>
                            <div class="vs-name">{{ $vendor->name }}</div>
                            @if($vendor->company)<div class="vs-sub">{{ $vendor->company }}</div>@endif
                        </div>
                    </div>
                </div>
                <div class="vs-info-grid">
                    <div class="vs-info-cell">
                        <div class="vs-info-lbl">Phone</div>
                        <div class="vs-info-val {{ $vendor->phone ? '' : 'muted' }}">{{ $vendor->phone ?? 'Not set' }}</div>
                    </div>
                    <div class="vs-info-cell">
                        <div class="vs-info-lbl">Email</div>
                        <div class="vs-info-val {{ $vendor->email ? '' : 'muted' }}">{{ $vendor->email ?? 'Not set' }}</div>
                    </div>
                    <div class="vs-info-cell">
                        <div class="vs-info-lbl">GST Number</div>
                        <div class="vs-info-val {{ $vendor->gst_number ? '' : 'muted' }}" style="font-family:'DM Mono',monospace">{{ $vendor->gst_number ?? 'Not set' }}</div>
                    </div>
                    <div class="vs-info-cell">
                        <div class="vs-info-lbl">Address</div>
                        <div class="vs-info-val {{ $vendor->full_address ? '' : 'muted' }}">{{ $vendor->full_address ?: 'Not set' }}</div>
                    </div>
                    <div class="vs-info-cell">
                        <div class="vs-info-lbl">Payment Terms</div>
                        <div class="vs-info-val {{ $vendor->payment_terms_days !== null ? '' : 'muted' }}">{{ $vendor->payment_terms_days !== null ? $vendor->payment_terms_days . ' days' : 'Not set' }}</div>
                    </div>
                    <div class="vs-info-cell">
                        <div class="vs-info-lbl">Outstanding (Payable)</div>
                        <div class="vs-info-val" style="font-family:'DM Mono',monospace;color:{{ $outstanding > 0 ? 'var(--red)' : 'var(--text-100)' }}">₹{{ number_format($outstanding, 2) }}</div>
                    </div>
                </div>
                @if($vendor->bank_details)
                <div style="padding:14px 20px;border-top:1px solid var(--border-subtle)">
                    <div class="vs-info-lbl" style="margin-bottom:6px">Bank / Payment Details</div>
                    <div style="font-size:13px;color:var(--text-200);line-height:1.6;white-space:pre-wrap">{{ $vendor->bank_details }}</div>
                </div>
                @endif
                @if($vendor->notes)
                <div style="padding:14px 20px;border-top:1px solid var(--border-subtle)">
                    <div class="vs-info-lbl" style="margin-bottom:6px">Notes</div>
                    <div style="font-size:13px;color:var(--text-200);line-height:1.6;white-space:pre-wrap">{{ $vendor->notes }}</div>
                </div>
                @endif
            </div>

            <div class="vs-card">
                <div class="vs-card-head">
                    <div class="vs-card-title">
                        <i class="ti ti-file-invoice" style="font-size:13px;margin-right:5px"></i>
                        Purchase Orders ({{ $vendor->purchaseOrders->count() }})
                    </div>
                </div>
                @if($vendor->purchaseOrders->isEmpty())
                <div style="padding:24px 20px;text-align:center;color:var(--text-400);font-size:13px">
                    No purchase orders raised with this vendor yet.
                </div>
                @else
                @foreach($vendor->purchaseOrders as $po)
                <div class="po-row">
                    <div>
                        <div style="font-size:13.5px;font-weight:600;color:var(--text-100)">{{ $po->number }}</div>
                        <div style="font-size:11.5px;color:var(--text-400);margin-top:2px">
                            {{ ucfirst(str_replace('_',' ',$po->status)) }} · {{ $po->created_at->format('d M Y') }}
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px">
                        <span style="font-size:13px;font-weight:700;color:var(--accent);font-family:'DM Mono',monospace">₹{{ number_format($po->total,2) }}</span>
                        <a href="{{ route('tenant.purchase-orders.show', $po->id) }}" class="btn btn-secondary btn-sm">View</a>
                    </div>
                </div>
                @endforeach
                @endif
            </div>

            <div class="vs-card">
                <div class="vs-card-head">
                    <div class="vs-card-title">
                        <i class="ti ti-receipt" style="font-size:13px;margin-right:5px"></i>
                        Vendor Bills ({{ $vendor->bills->count() }})
                    </div>
                </div>
                @if($vendor->bills->isEmpty())
                <div style="padding:24px 20px;text-align:center;color:var(--text-400);font-size:13px">
                    No bills recorded from this vendor yet.
                </div>
                @else
                @foreach($vendor->bills as $b)
                <div class="po-row">
                    <div>
                        <div style="font-size:13.5px;font-weight:600;color:var(--text-100)">{{ $b->number }}</div>
                        <div style="font-size:11.5px;color:var(--text-400);margin-top:2px">
                            {{ \App\Models\VendorBill::statuses()[$b->status] ?? $b->status }} · due {{ $b->due_date?->format('d M Y') ?? '—' }}
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px">
                        <span style="font-size:12.5px;color:var(--text-300);font-family:'DM Mono',monospace">bal ₹{{ number_format($b->due_amount, 2) }}</span>
                        <a href="{{ route('tenant.vendor-bills.show', $b->id) }}" class="btn btn-secondary btn-sm">View</a>
                    </div>
                </div>
                @endforeach
                @endif
            </div>

        </div>

        <div style="display:flex;flex-direction:column;gap:14px">
            <div class="vs-sc">
                <div class="vs-sc-title">Actions</div>
                @can('create', \App\Models\PurchaseOrder::class)
                <a href="{{ route('tenant.purchase-orders.create') }}" class="vs-action-btn">
                    <div class="vs-act-icon" style="background:var(--accent-dim)">
                        <i class="ti ti-file-invoice" style="font-size:15px;color:var(--accent)"></i>
                    </div>
                    New Purchase Order
                </a>
                @endcan
                @can('create', \App\Models\VendorBill::class)
                <a href="{{ route('tenant.vendor-bills.create') }}" class="vs-action-btn">
                    <div class="vs-act-icon" style="background:var(--green-dim)">
                        <i class="ti ti-receipt" style="font-size:15px;color:var(--green)"></i>
                    </div>
                    Record Vendor Bill
                </a>
                @endcan
            </div>

            @can('delete', $vendor)
            <div class="vs-sc" style="border-color:var(--red)">
                <div class="vs-sc-title" style="color:var(--red)">Danger Zone</div>
                <form method="POST" action="{{ route('tenant.vendors.destroy',$vendor->id) }}"
                      data-confirm="Delete vendor {{ $vendor->name }}?" data-confirm-ok="Delete">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn" style="width:100%;justify-content:center;background:var(--red-dim);border-color:var(--red);color:var(--red);font-size:12.5px">
                        <i class="ti ti-trash" style="font-size:14px"></i> Delete Vendor
                    </button>
                </form>
            </div>
            @endcan
        </div>
    </div>
</div>
@endsection
