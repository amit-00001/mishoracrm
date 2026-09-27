@extends('layouts.app')
@section('title', 'Vendor Bill — ' . $bill->number)

@push('styles')
<style>
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css');
.vb-layout { display:grid; grid-template-columns:minmax(0,1fr) 300px; gap:16px; margin-top:20px; }
@media(max-width:960px){ .vb-layout { grid-template-columns:1fr; } }
.vb-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; overflow:hidden; }
.vb-card-head { padding:14px 20px 12px; border-bottom:1px solid var(--border-subtle); font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.6px; }
.vb-hero { padding:22px; }
.vb-number { font-size:22px; font-weight:600; color:var(--text-100); font-family:var(--mono); margin-bottom:3px; }
.vb-badge { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:20px; font-size:13px; font-weight:600; }
.vb-items { width:100%; border-collapse:collapse; }
.vb-items thead tr { background:var(--bg-elevated); }
.vb-items th { padding:10px 14px; text-align:left; font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid var(--border-subtle); }
.vb-items td { padding:12px 14px; font-size:13.5px; color:var(--text-100); border-bottom:1px solid var(--border-subtle); }
.vb-items .r { text-align:right; font-family:var(--mono); }
.dl-row { display:flex; justify-content:space-between; padding:8px 0; border-bottom:1px solid var(--border-subtle); font-size:12.5px; }
.dl-row:last-child { border-bottom:none; }
.dl-row .k { color:var(--text-300); }
.dl-row .v { font-weight:500; color:var(--text-100); text-align:right; }
.vb-sc { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; padding:17px; }
.vb-sc-title { font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.6px; margin-bottom:13px; }
.pf-input { width:100%; padding:8px 11px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:8px; color:var(--text-100); font-family:var(--font); font-size:13px; outline:none; }
.pay-row { display:grid; grid-template-columns:1fr 1fr; gap:8px; margin-bottom:8px; }
</style>
@endpush

@section('content')
@php
    $meta = config('crm.vendor_bill.statuses')[$bill->status] ?? ['color' => 'text-300', 'bg' => 'bg-elevated', 'label' => ucfirst($bill->status)];
@endphp

<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.vendor-bills.index') }}" style="color:var(--text-300);text-decoration:none">Vendor Bills</a>
            › {{ $bill->number }}
        </div>
        <div class="page-title">Vendor Bill</div>
    </div>
    <div style="display:flex;gap:8px">
        @can('modify', $bill)
        @if($bill->isEditable())
        <a href="{{ route('tenant.vendor-bills.edit', $bill->id) }}" class="btn btn-secondary"><i class="ti ti-edit" style="font-size:14px"></i> Edit</a>
        @endif
        @endcan
    </div>
</div>

@foreach(['success','error'] as $t)
@if(session($t))
<div style="padding:11px 15px;background:{{ $t==='success'?'var(--green-dim)':'var(--red-dim)' }};border:1px solid {{ $t==='success'?'var(--green)':'var(--red)' }};border-radius:8px;margin-bottom:14px;font-size:13px;color:{{ $t==='success'?'var(--green)':'var(--red)' }};font-weight:500">{{ session($t) }}</div>
@endif
@endforeach

<div class="vb-layout">
    <div style="display:flex;flex-direction:column;gap:14px">

        <div class="vb-card">
            <div class="vb-hero">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;flex-wrap:wrap">
                    <div>
                        <div class="vb-number">{{ $bill->number }}</div>
                        <div style="font-size:13px;color:var(--text-300)">
                            {{ $bill->vendor?->name }}
                            @if($bill->vendor_invoice_number) • Vendor Inv: {{ $bill->vendor_invoice_number }}@endif
                        </div>
                    </div>
                    <span class="vb-badge" style="background:var(--{{ $meta['bg'] }});color:var(--{{ $meta['color'] }})">
                        {{ \App\Models\VendorBill::statuses()[$bill->status] ?? ucfirst($bill->status) }}
                        @if($bill->isOverdue()) • Overdue @endif
                    </span>
                </div>
                <div style="display:flex;gap:24px;flex-wrap:wrap;margin-top:18px">
                    <div><div style="font-size:10.5px;font-weight:600;color:var(--text-300);text-transform:uppercase">Bill Date</div><div style="font-size:13px;color:var(--text-100);font-family:var(--mono)">{{ $bill->date?->format('d M Y') }}</div></div>
                    <div><div style="font-size:10.5px;font-weight:600;color:var(--text-300);text-transform:uppercase">Due Date</div><div style="font-size:13px;color:var(--text-100);font-family:var(--mono)">{{ $bill->due_date?->format('d M Y') ?? '—' }}</div></div>
                    @if($bill->purchaseOrder)
                    <div><div style="font-size:10.5px;font-weight:600;color:var(--text-300);text-transform:uppercase">From PO</div><a href="{{ route('tenant.purchase-orders.show', $bill->purchaseOrder->id) }}" style="font-size:13px;color:var(--accent);text-decoration:none;font-family:var(--mono)">{{ $bill->purchaseOrder->number }}</a></div>
                    @endif
                    <div><div style="font-size:10.5px;font-weight:600;color:var(--text-300);text-transform:uppercase">Created By</div><div style="font-size:13px;color:var(--text-100)">{{ $bill->createdBy?->name ?? '—' }}</div></div>
                </div>
            </div>
        </div>

        <div class="vb-card">
            <div class="vb-card-head">Line Items</div>
            <div style="overflow-x:auto">
                <table class="vb-items">
                    <thead>
                        <tr><th>Item</th><th class="r">Qty</th><th class="r">Rate</th><th class="r">GST %</th><th class="r">Amount</th></tr>
                    </thead>
                    <tbody>
                        @foreach($bill->items ?? [] as $item)
                        <tr>
                            <td>
                                <div style="font-weight:500">{{ $item['name'] ?? '—' }}</div>
                                @if(!empty($item['description']))<div style="font-size:12px;color:var(--text-300)">{{ $item['description'] }}</div>@endif
                            </td>
                            <td class="r">{{ number_format((float)($item['quantity'] ?? 0), 2) }}</td>
                            <td class="r">₹{{ number_format((float)($item['rate'] ?? 0), 2) }}</td>
                            <td class="r">{{ number_format((float)($item['tax_percent'] ?? 0), 1) }}%</td>
                            <td class="r">₹{{ number_format((float)($item['amount'] ?? 0), 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div style="display:flex;justify-content:flex-end;padding:16px 20px;background:var(--bg-elevated);border-top:1px solid var(--border-subtle)">
                <div style="width:260px">
                    <div class="dl-row"><span class="k">Subtotal</span><span class="v">₹{{ number_format($bill->subtotal, 2) }}</span></div>
                    <div class="dl-row"><span class="k">Discount</span><span class="v" style="color:var(--red)">-₹{{ number_format($bill->discount, 2) }}</span></div>
                    @foreach($bill->gstLines() as $line)
                    <div class="dl-row"><span class="k">{{ $line['label'] }}</span><span class="v" style="color:var(--green)">+₹{{ number_format($line['amount'], 2) }}</span></div>
                    @endforeach
                    <div class="dl-row"><span class="k" style="font-weight:700">Total</span><span class="v" style="font-weight:700;font-size:14px">₹{{ number_format($bill->total, 2) }}</span></div>
                    <div class="dl-row"><span class="k">Paid</span><span class="v" style="color:var(--green)">₹{{ number_format($bill->amount_paid, 2) }}</span></div>
                    <div class="dl-row"><span class="k" style="font-weight:700">Balance Due</span><span class="v" style="font-weight:700;color:var(--red)">₹{{ number_format($bill->due_amount, 2) }}</span></div>
                </div>
            </div>
            @if($bill->notes)
            <div style="padding:16px 20px;border-top:1px solid var(--border-subtle)">
                <div style="font-size:11px;font-weight:600;color:var(--text-300);text-transform:uppercase;margin-bottom:6px">Notes</div>
                <div style="font-size:13px;color:var(--text-200);white-space:pre-wrap">{{ $bill->notes }}</div>
            </div>
            @endif
        </div>

        <div class="vb-card">
            <div class="vb-card-head">Payments</div>
            <div style="overflow-x:auto">
                <table class="vb-items">
                    <thead><tr><th>Date</th><th>Method</th><th>Reference</th><th class="r">Amount</th><th>By</th></tr></thead>
                    <tbody>
                        @forelse($bill->payments as $p)
                        <tr>
                            <td class="td-mono">{{ $p->paid_at?->format('d M Y') }}</td>
                            <td>{{ \App\Models\VendorBill::paymentMethods()[$p->method] ?? $p->method }}</td>
                            <td style="color:var(--text-300)">{{ $p->reference ?: '—' }}{{ $p->note ? ' — '.$p->note : '' }}</td>
                            <td class="r" style="color:var(--green);font-weight:600">₹{{ number_format($p->amount, 2) }}</td>
                            <td style="color:var(--text-300)">{{ $p->recordedBy?->name ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" style="text-align:center;color:var(--text-300);padding:24px">No payments recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:14px">
        @can('recordPayment', $bill)
        <div class="vb-sc">
            <div class="vb-sc-title">Record Payment</div>
            <form method="POST" action="{{ route('tenant.vendor-bills.payments.store', $bill->id) }}">
                @csrf
                <div class="pay-row">
                    <div>
                        <label style="font-size:11px;color:var(--text-300)">Amount (₹)</label>
                        <input type="number" name="payments[0][amount]" class="pf-input" min="0.01" step="0.01"
                               value="{{ number_format($bill->due_amount, 2, '.', '') }}" required/>
                    </div>
                    <div>
                        <label style="font-size:11px;color:var(--text-300)">Date</label>
                        <input type="date" name="payments[0][paid_at]" class="pf-input" value="{{ now()->format('Y-m-d') }}" required/>
                    </div>
                </div>
                <div class="pay-row">
                    <div>
                        <label style="font-size:11px;color:var(--text-300)">Method</label>
                        <select name="payments[0][method]" class="pf-input">
                            @foreach(\App\Models\VendorBill::paymentMethods() as $k => $label)
                            <option value="{{ $k }}" {{ $k === 'bank_transfer' ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label style="font-size:11px;color:var(--text-300)">Reference</label>
                        <input type="text" name="payments[0][reference]" class="pf-input" placeholder="UTR / cheque no."/>
                    </div>
                </div>
                <input type="text" name="payments[0][note]" class="pf-input" placeholder="Note (optional)" style="margin-bottom:10px"/>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                    <i class="ti ti-cash" style="font-size:14px"></i> Record Payment
                </button>
            </form>
        </div>
        @endcan

        <div class="vb-sc">
            <div class="vb-sc-title">Vendor</div>
            <div style="font-size:13.5px;font-weight:600;color:var(--text-100)">{{ $bill->vendor?->name }}</div>
            @if($bill->vendor?->company)<div style="font-size:12px;color:var(--text-300)">{{ $bill->vendor->company }}</div>@endif
            @if($bill->vendor?->gst_number)<div style="font-size:12px;color:var(--text-300);font-family:var(--mono);margin-top:3px">GST: {{ $bill->vendor->gst_number }}</div>@endif
            <a href="{{ route('tenant.vendors.show', $bill->vendor_id) }}" class="btn btn-secondary btn-sm" style="margin-top:10px;width:100%;justify-content:center">View Vendor</a>
        </div>

        @if(!$bill->isPaid() && !$bill->isCancelled())
        @can('modify', $bill)
        <div class="vb-sc">
            <div class="vb-sc-title">Actions</div>
            <form method="POST" action="{{ route('tenant.vendor-bills.cancel', $bill->id) }}" data-confirm="Cancel this bill? It will drop out of AP totals.">
                @csrf
                <button type="submit" class="btn btn-secondary" style="width:100%;justify-content:center;color:var(--amber)">Cancel Bill</button>
            </form>
        </div>
        @endcan
        @endif

        @can('delete', $bill)
        <div class="vb-sc" style="border-color:var(--red)">
            <div class="vb-sc-title" style="color:var(--red)">Danger Zone</div>
            <form method="POST" action="{{ route('tenant.vendor-bills.destroy', $bill->id) }}" data-confirm="Delete {{ $bill->number }}?" data-confirm-ok="Delete">
                @csrf @method('DELETE')
                <button type="submit" class="btn" style="width:100%;justify-content:center;background:var(--red-dim);border-color:var(--red);color:var(--red)">Delete Bill</button>
            </form>
        </div>
        @endcan
    </div>
</div>
@endsection
