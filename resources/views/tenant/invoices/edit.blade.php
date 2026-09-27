@extends('layouts.app')
@section('title', 'Edit Invoice — ' . $invoice->number)

@push('styles')
<style>
.inv-layout { display:grid; grid-template-columns:1fr 300px; gap:16px; align-items:start; }
@media(max-width:1100px) { .inv-layout { grid-template-columns:1fr; } }

.form-card  { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.fc-section { padding:22px 24px; border-bottom:1px solid var(--border-subtle); }
.fc-section:last-child { border-bottom:none; }
.fc-title   { font-size:12.5px; font-weight:700; color:var(--text-300); text-transform:uppercase; letter-spacing:.4px; margin-bottom:16px; }

.field       { display:flex; flex-direction:column; gap:7px; }
.field-label { font-size:12.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.3px; }
.req         { color:var(--red); margin-left:2px; }
.field-input {
    padding:10px 13px; background:var(--bg-input);
    border:1.5px solid var(--border-default); border-radius:var(--r-sm);
    color:var(--text-100); font-family:var(--font); font-size:14px; outline:none;
    transition:border-color .15s, box-shadow .15s; width:100%;
}
.field-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
.field-input::placeholder { color:var(--text-400); }
.field-input.is-error { border-color:var(--red); }
.field-select { -webkit-appearance:none; cursor:pointer; }
.field-error  { font-size:12px; color:var(--red); }
.form-grid    { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.span-2       { grid-column:1/-1; }
@media(max-width:640px) { .form-grid { grid-template-columns:1fr; } .span-2 { grid-column:1; } }

/* Current invoice info box */
.inv-info-box {
    display:flex; align-items:center; gap:12px;
    padding:12px 16px; background:var(--bg-elevated);
    border:1px solid var(--border-subtle); border-radius:var(--r-sm);
    margin-bottom:16px;
}

/* Items table */
.items-table { width:100%; border-collapse:collapse; }
.items-table th { padding:8px 10px; text-align:left; font-size:11px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; border-bottom:1px solid var(--border-subtle); white-space:nowrap; }
.items-table th.right { text-align:right; }
.items-table td { padding:6px 6px; vertical-align:middle; }
.item-input { padding:8px 10px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:13.5px; outline:none; transition:border-color .15s; width:100%; }
.item-input:focus { border-color:var(--accent); }
.item-input.right { text-align:right; }
.item-amount { font-size:13.5px; font-weight:600; text-align:right; padding-right:4px; font-family:var(--mono); color:var(--text-100); white-space:nowrap; }
.del-row { padding:6px 8px; border:none; background:var(--red-dim); color:var(--red); border-radius:var(--r-sm); cursor:pointer; font-size:13px; transition:background .15s; }
.del-row:hover { background:var(--red); color:#fff; }
.add-row-btn { display:flex; align-items:center; gap:6px; padding:9px 14px; border:1.5px dashed var(--accent); background:none; color:var(--accent); border-radius:var(--r-sm); font-size:13px; font-weight:600; cursor:pointer; font-family:var(--font); transition:background .15s; margin-top:10px; }
.add-row-btn:hover { background:var(--accent-dim); }

@media(max-width:768px) {
    .items-table thead { display:none; }
    .items-table, .items-table tbody { display:block; width:100%; }
    .items-table tr {
        display:block; margin-bottom:12px; padding:12px;
        background:var(--bg-elevated); border:1px solid var(--border-default); border-radius:var(--r-md);
    }
    .items-table td {
        display:flex; align-items:center; justify-content:space-between;
        gap:10px; padding:6px 0;
    }
    .items-table td::before {
        content:attr(data-label);
        font-size:11px; font-weight:700; text-transform:uppercase;
        letter-spacing:0.4px; color:var(--text-400); flex-shrink:0;
    }
    .items-table td[style*="text-align:center"] { justify-content:flex-end; }
    .items-table td[style*="text-align:center"]::before { content:''; }
}

/* Totals */
.totals-box { background:var(--bg-elevated); border-radius:var(--r-sm); padding:16px; }
.total-row  { display:flex; justify-content:space-between; align-items:center; padding:6px 0; border-bottom:1px solid var(--border-subtle); font-size:13.5px; }
.total-row:last-child { border-bottom:none; padding-top:10px; margin-top:4px; }
.total-row.grand { font-size:16px; font-weight:800; }
.total-label { color:var(--text-200); }
.total-value { font-family:var(--mono); font-weight:600; color:var(--text-100); }

/* Sidebar */
.summary-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; position:sticky; top:80px; }
.sc-head { padding:14px 18px; border-bottom:1px solid var(--border-subtle); font-size:13.5px; font-weight:700; color:var(--text-100); }
.sc-body { padding:18px; }
.sc-row  { display:flex; justify-content:space-between; font-size:13px; padding:6px 0; border-bottom:1px solid var(--border-subtle); }
.sc-row:last-child { border-bottom:none; }

/* Status badge */
.status-badge { font-size:12px; font-weight:600; padding:4px 10px; border-radius:20px; }

.form-footer { padding:16px 24px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center; }
</style>
@endpush

@section('content')

@php
    // ✅ Key fix — existing items JSON
    $existingItems = old('items') ?? $invoice->items ?? [];
    // Normalize items so view can use them
    $existingItems = collect($existingItems)->map(fn($item) => [
        'product_id'  => $item['product_id']  ?? '',
        'service_id'  => $item['service_id']  ?? '',
        'description' => $item['description'] ?? '',
        'quantity'    => $item['quantity']    ?? 1,
        'rate'        => $item['rate']        ?? 0,
        'tax_percent' => $item['tax_percent'] ?? ($invoice->tax_percent ?? 18),
        'amount'      => ($item['quantity'] ?? 1) * ($item['rate'] ?? 0),
    ])->toArray();

    $contactsJson = $contacts->mapWithKeys(fn($c) => [
        $c->id => [
            'name'    => $c->name,
            'company' => $c->company,
            'phone'   => $c->phone,
            'email'   => $c->email,
            'address' => trim(collect([$c->address, $c->city, $c->state])->filter()->implode(', ')),
            'gst'     => $c->gst_number,
        ]
    ]);

    $statusCfg    = $statuses;
    $selContact   = old('contact_id', $invoice->contact_id);
    $selDiscount  = old('discount',   $invoice->discount ?? 0);
    $selTax       = old('tax_percent',$invoice->tax_percent ?? 18);
@endphp

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.invoices.index') }}" style="color:var(--text-300);text-decoration:none">Invoices</a>
            <span style="margin:0 6px">›</span>
            <a href="{{ route('tenant.invoices.show', $invoice->id) }}" style="color:var(--text-300);text-decoration:none">{{ $invoice->number }}</a>
            <span style="margin:0 6px">›</span>
            Edit
        </div>
        <div class="page-title">Edit Invoice</div>
    </div>
    <a href="{{ route('tenant.invoices.show', $invoice->id) }}" class="btn btn-secondary">← Back</a>
</div>

{{-- Current invoice info --}}
@php $sc = $statuses[$invoice->status] ?? ['label'=>ucfirst($invoice->status),'color'=>'accent','bg'=>'accent-dim']; @endphp
<div class="inv-info-box" style="max-width:860px;margin-bottom:16px">
    <div style="flex:1">
        <div style="font-size:14px;font-weight:700;color:var(--text-100)">{{ $invoice->number }}</div>
        <div style="font-size:12.5px;color:var(--text-300);margin-top:2px">
            {{ $invoice->contact?->name }}
            · Created {{ $invoice->created_at->format('d M Y') }}
        </div>
    </div>
    <span class="status-badge" style="background:var(--{{ $sc['bg'] }});color:var(--{{ $sc['color'] }})">
        {{ $sc['label'] }}
    </span>
    <div style="text-align:right">
        <div style="font-size:18px;font-weight:800;font-family:var(--mono);color:var(--accent)">
            ₹{{ number_format($invoice->total, 2) }}
        </div>
        <div style="font-size:11.5px;color:var(--text-400)">
            Due {{ $invoice->due_date?->format('d M Y') }}
        </div>
    </div>
</div>

@if($errors->any())
<div style="padding:12px 16px;background:var(--red-dim);border:1px solid rgba(255,82,87,.25);border-radius:var(--r-sm);margin-bottom:16px;font-size:13px;color:var(--red)">
    {{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ route('tenant.invoices.update', $invoice->id) }}" id="invForm">
@csrf @method('PUT')

<div class="inv-layout">

    {{-- ── Left: Form ──────────────────────────────────────────── --}}
    <div class="form-card">

        {{-- Header --}}
        <div class="fc-section">
            <div class="fc-title">Invoice Details</div>
            <div class="form-grid">

                <div class="field span-2">
                    <label class="field-label">Bill To <span class="req">*</span></label>
                    <select name="contact_id" id="contactSelect"
                            class="field-input field-select {{ $errors->has('contact_id') ? 'is-error':'' }}"
                            onchange="loadContact(this.value)" required>
                        <option value="">— Select Contact —</option>
                        @foreach($contacts as $c)
                        <option value="{{ $c->id }}" {{ $selContact == $c->id ? 'selected':'' }}>
                            {{ $c->name }}{{ $c->company ? ' ('.$c->company.')' : '' }}
                        </option>
                        @endforeach
                    </select>
                    {{-- Contact info preview --}}
                    <div id="contactPreview"
                         style="margin-top:8px;padding:10px 12px;background:var(--bg-elevated);border-radius:var(--r-sm);font-size:12.5px;line-height:1.7;color:var(--text-200);{{ $invoice->contact ? '':'display:none' }}">
                        @if($invoice->contact)
                        <strong>{{ $invoice->contact->name }}</strong>
                        {{ $invoice->contact->company ? ' · '.$invoice->contact->company : '' }}<br/>
                        {{ $invoice->contact->phone }}
                        {{ $invoice->contact->email ? ' · '.$invoice->contact->email : '' }}
                        @endif
                    </div>
                </div>

                <div class="field">
                    <label class="field-label">Invoice Number</label>
                    <input type="text" class="field-input" value="{{ $invoice->number }}" readonly
                           style="background:var(--bg-elevated);color:var(--text-300);cursor:default"/>
                </div>

                <div style="display:flex;flex-direction:column;gap:14px">
                    <div class="field">
                        <label class="field-label">Invoice Date <span class="req">*</span></label>
                        <input type="date" name="date"
                               class="field-input {{ $errors->has('date') ? 'is-error':'' }}"
                               value="{{ old('date', $invoice->date?->toDateString()) }}" required/>
                        @error('date') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="field">
                        <label class="field-label">Due Date <span class="req">*</span></label>
                        <input type="date" name="due_date"
                               class="field-input {{ $errors->has('due_date') ? 'is-error':'' }}"
                               value="{{ old('due_date', $invoice->due_date?->toDateString()) }}"
                               id="dueDateInput"
                               required/>
                        @error('due_date') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                </div>

            </div>
        </div>

        {{-- Items table --}}
        <div class="fc-section">
            <div class="fc-title">
                Line Items
                <span style="font-size:11px;color:var(--text-400);font-weight:400;text-transform:none;letter-spacing:0;margin-left:6px">
                    {{ count($existingItems) }} item{{ count($existingItems) !== 1 ? 's':'' }}
                </span>
            </div>
            <div style="overflow-x:auto">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="min-width:260px">Description</th>
                            <th style="width:90px">Qty</th>
                            <th style="width:120px">Rate (₹)</th>
                            <th style="width:80px">GST %</th>
                            <th class="right" style="width:120px">Amount</th>
                            <th style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        {{-- Rows injected by JS from existing data --}}
                    </tbody>
                </table>
            </div>
            <button type="button" class="add-row-btn" onclick="addRow()">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:14px;height:14px">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Add Line Item
            </button>
        </div>

        {{-- Discount, Tax --}}
        <div class="fc-section">
            <div class="form-grid">
                <div class="field">
                    <label class="field-label">Discount (₹)</label>
                    <input type="number" name="discount" id="discount"
                           class="field-input" min="0" step="0.01"
                           value="{{ old('discount', $selDiscount) }}"
                           oninput="calcTotals()" placeholder="0"/>
                </div>
                <div class="field">
                    <label class="field-label">GST / Tax (%)</label>
                    <input type="text" class="field-input"
                           style="background:var(--bg-elevated);color:var(--text-300);cursor:default"
                           readonly value="Per-item (set per row below)"
                           id="taxPercent"/>
                    <input type="hidden" name="tax_percent" value="{{ old('tax_percent', $selTax) }}"/>
                </div>
            </div>

            <div class="totals-box" style="margin-top:16px">
                <div class="total-row">
                    <span class="total-label">Subtotal</span>
                    <span class="total-value" id="dispSubtotal">₹0.00</span>
                </div>
                <div class="total-row">
                    <span class="total-label">Discount</span>
                    <span class="total-value" id="dispDiscount" style="color:var(--red)">- ₹0.00</span>
                </div>
                <div class="total-row">
                    <span class="total-label">GST (<span id="dispTaxPct">18</span>%)</span>
                    <span class="total-value" id="dispTax">₹0.00</span>
                </div>
                <div class="total-row grand">
                    <span>Total</span>
                    <span id="dispTotal" style="color:var(--accent)">₹0.00</span>
                </div>
            </div>
        </div>

        {{-- Notes & Terms --}}
        <div class="fc-section">
            <div class="form-grid">
                <div class="field">
                    <label class="field-label">Notes</label>
                    <textarea name="notes" class="field-input" rows="3"
                              style="resize:vertical"
                              placeholder="Notes to client...">{{ old('notes', $invoice->notes) }}</textarea>
                </div>
                <div class="field">
                    <label class="field-label">Terms & Conditions</label>
                    <textarea name="terms" class="field-input" rows="3"
                              style="resize:vertical"
                              placeholder="Payment terms...">{{ old('terms', $invoice->terms) }}</textarea>
                </div>
            </div>
        </div>

        <div class="form-footer">
            <div></div>

            <div style="display:flex;gap:10px">
                <a href="{{ route('tenant.invoices.show', $invoice->id) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" id="saveBtn">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                    Save Changes
                </button>
            </div>
        </div>

    </div>

    {{-- ── Right: Sidebar ──────────────────────────────────────── --}}
    <div class="summary-card">
        <div class="sc-head">Invoice Info</div>
        <div class="sc-body">
            <div class="sc-row">
                <span style="color:var(--text-300)">Invoice #</span>
                <span style="font-family:var(--mono);font-size:12px;color:var(--accent)">{{ $invoice->number }}</span>
            </div>
            <div class="sc-row">
                <span style="color:var(--text-300)">Status</span>
                <span class="status-badge" style="background:var(--{{ $sc['bg'] }});color:var(--{{ $sc['color'] }})">
                    {{ $sc['label'] }}
                </span>
            </div>
            @if($invoice->paid_amount > 0)
            <div class="sc-row">
                <span style="color:var(--text-300)">Paid</span>
                <span style="font-family:var(--mono);color:var(--green);font-weight:600">
                    ₹{{ number_format($invoice->paid_amount, 2) }}
                </span>
            </div>
            <div class="sc-row">
                <span style="color:var(--text-300)">Due</span>
                <span style="font-family:var(--mono);color:var(--red);font-weight:600">
                    ₹{{ number_format($invoice->due_amount, 2) }}
                </span>
            </div>
            @endif
            <div class="sc-row">
                <span style="color:var(--text-300)">Items</span>
                <span id="sumItems" style="font-weight:600">{{ count($existingItems) }}</span>
            </div>
            <div class="sc-row">
                <span style="color:var(--text-300)">Subtotal</span>
                <span id="sumSubtotal" style="font-family:var(--mono)">₹{{ number_format($invoice->subtotal, 2) }}</span>
            </div>
            <div class="sc-row">
                <span style="color:var(--text-300)">GST ({{ $invoice->tax_percent }}%)</span>
                <span id="sumTax" style="font-family:var(--mono)">₹{{ number_format($invoice->tax_amount, 2) }}</span>
            </div>
            <div style="padding-top:12px;border-top:1px solid var(--border-subtle);margin-top:4px">
                <div style="font-size:11px;color:var(--text-400);margin-bottom:4px">New Total</div>
                <div style="font-size:22px;font-weight:800;font-family:var(--mono);color:var(--accent);text-align:right" id="sumTotal">
                    ₹{{ number_format($invoice->total, 2) }}
                </div>
            </div>
        </div>

        {{-- Quick actions --}}
        <div style="padding:14px 18px;border-top:1px solid var(--border-subtle)">
            <div style="font-size:11px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.4px;margin-bottom:10px">Quick Actions</div>
            <div style="display:flex;flex-direction:column;gap:6px">
                <a href="{{ route('tenant.invoices.pdf', $invoice->id) }}" target="_blank"
                   class="btn btn-secondary btn-sm">
                    📄 Download PDF
                </a>
                @if($invoice->status !== 'paid')
                <a href="{{ route('tenant.invoices.show', $invoice->id) }}#payment"
                   class="btn btn-secondary btn-sm">
                    💰 Record Payment
                </a>
                @endif
            </div>
        </div>
    </div>

</div>
</form>

@if($invoice->status !== 'paid')
<div style="margin-top:16px;padding:16px 18px;background:var(--bg-surface);border:1px solid rgba(255,82,87,.3);border-radius:var(--r-lg);display:flex;align-items:center;justify-content:space-between;gap:12px">
    <div>
        <div style="font-size:13px;font-weight:600;color:var(--red)">Danger Zone</div>
        <span style="font-size:12px;color:var(--text-400)">Permanently delete this invoice. This cannot be undone.</span>
    </div>
    <form method="POST" action="{{ route('tenant.invoices.destroy', $invoice->id) }}"
          data-confirm="Delete this invoice permanently?" data-confirm-ok="Delete">
        @csrf @method('DELETE')
        <button type="submit"
                style="padding:8px 14px;border-radius:var(--r-sm);border:1.5px solid rgba(255,82,87,.3);background:var(--red-dim);color:var(--red);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)">
            🗑 Delete Invoice
        </button>
    </form>
</div>
@endif

@endsection

@include('tenant.partials.product-search-js')

@push('scripts')
<script>
// ── Existing data ─────────────────────────────────────────────────
const CONTACTS       = @json($contactsJson);
const EXISTING_ITEMS = @json($existingItems);
window.PRODUCTS = @json($products->keyBy('id'));
window.SERVICES = @json($services->keyBy('id'));
let rowCount = 0;

// ── Contact preview ───────────────────────────────────────────────
function loadContact(id) {
    const c   = CONTACTS[id];
    const box = document.getElementById('contactPreview');
    if (!c || !id) { box.style.display = 'none'; return; }

    box.innerHTML = `<strong>${c.name}</strong>${c.company ? ' · ' + c.company : ''}<br/>
        ${c.phone || ''} ${c.email ? '· ' + c.email : ''}<br/>
        ${c.address || ''}${c.gst ? '<br/>GST: ' + c.gst : ''}`;
    box.style.display = 'block';
}

// ── Autofill row (called by shared partial) ───────────────────────
window.fillRowFromProduct = function(i, p) {
    const row = document.querySelector(`[data-row="${i}"]`);
    if (!row) return;
    row.querySelector(`[name="items[${i}][description]"]`).value  = p.description || p.name;
    row.querySelector(`[name="items[${i}][rate]"]`).value         = p.rate;
    row.querySelector(`[name="items[${i}][tax_percent]"]`).value  = p.tax_percent;
    const pidInput = row.querySelector(`[name="items[${i}][product_id]"]`);
    const sidInput = row.querySelector(`[name="items[${i}][service_id]"]`);
    if (p._kind === 'service') {
        if (sidInput) sidInput.value = p.id;
        if (pidInput) pidInput.value = '';
    } else {
        if (pidInput) pidInput.value = p.id;
        if (sidInput) sidInput.value = '';
    }
    renderSubscriptionToggle(i, p);
    calcRow(i);
    calcTotals();
};

// Recurring/duration-bearing service → show a "Track as subscription"
// checkbox on that row so saving the invoice can auto-create a
// ServiceSubscription without a separate manual step.
function renderSubscriptionToggle(i, p, checked = true){
    const box = document.getElementById('sub_track_' + i);
    if (!box) return;

    const isRecurring = p && p._kind === 'service' && p.billing_cycle && p.billing_cycle !== 'one_time';
    if (!isRecurring) { box.innerHTML = ''; return; }

    const cycleLabel = p.billing_cycle.charAt(0).toUpperCase() + p.billing_cycle.slice(1);
    box.innerHTML = `
        <label style="display:flex;align-items:center;gap:6px;font-size:11.5px;color:var(--text-300);margin-top:5px;cursor:pointer">
            <input type="checkbox" name="items[${i}][track_subscription]" value="1" ${checked ? 'checked' : ''} style="width:13px;height:13px;cursor:pointer"/>
            Track as subscription (${cycleLabel}${p.duration_value && p.duration_unit ? ' · ' + p.duration_value + ' ' + p.duration_unit : ''})
        </label>
    `;
}

// ── Add row ───────────────────────────────────────────────────────
function addRow(desc = '', qty = 1, rate = '', taxPct = '', productId = '', serviceId = '') {
    const tbody = document.getElementById('itemsBody');
    const i     = rowCount++;
    const tr    = document.createElement('tr');
    tr.dataset.row = i;

    const amount = (parseFloat(qty)||0) * (parseFloat(rate)||0);
    const gst    = taxPct !== '' ? taxPct : 18;

    tr.innerHTML = `
        <td data-label="Description">
            <div id="ps_container_${i}"></div>
            <input type="hidden" name="items[${i}][product_id]" value="${productId}"/>
            <input type="hidden" name="items[${i}][service_id]" value="${serviceId}"/>
            <input type="text"
                   name="items[${i}][description]"
                   class="item-input"
                   placeholder="Item description"
                   value="${escHtml(desc)}"
                   required/>
            <div id="sub_track_${i}"></div>
        </td>
        <td data-label="Qty">
            <input type="number"
                   name="items[${i}][quantity]"
                   class="item-input right"
                   min="0.01" step="0.01"
                   value="${qty}"
                   oninput="calcRow(${i}); calcTotals();"
                   required/>
        </td>
        <td data-label="Rate (₹)">
            <input type="number"
                   name="items[${i}][rate]"
                   class="item-input right"
                   min="0" step="0.01"
                   placeholder="0.00"
                   value="${rate}"
                   oninput="calcRow(${i}); calcTotals();"
                   required/>
        </td>
        <td data-label="GST %">
            <input type="number"
                   name="items[${i}][tax_percent]"
                   class="item-input right"
                   min="0" max="100" step="0.1"
                   placeholder="18"
                   value="${gst}"
                   oninput="calcTotals();"/>
        </td>
        <td data-label="Amount">
            <div class="item-amount" id="rowAmt_${i}">₹${fmt(amount)}</div>
            <input type="hidden" name="items[${i}][amount]" id="rowAmtHidden_${i}" value="${amount}"/>
        </td>
        <td style="text-align:center">
            <button type="button" class="del-row" onclick="delRow(this)">✕</button>
        </td>`;

    tbody.appendChild(tr);
    buildProductSearch(i, document.getElementById('ps_container_' + i));
    if (serviceId && window.SERVICES && window.SERVICES[serviceId]) {
        renderSubscriptionToggle(i, {...window.SERVICES[serviceId], _kind: 'service'}, false);
    }
    calcTotals();
    set('sumItems', document.querySelectorAll('#itemsBody tr').length);
}

// ── Delete row ────────────────────────────────────────────────────
function delRow(btn) {
    const tbody = document.getElementById('itemsBody');
    if (tbody.rows.length <= 1) return;
    btn.closest('tr').remove();
    calcTotals();
    set('sumItems', document.querySelectorAll('#itemsBody tr').length);
}

// ── Calc row amount ───────────────────────────────────────────────
function calcRow(i) {
    const row  = document.querySelector(`[data-row="${i}"]`);
    if (!row) return;
    const qty  = parseFloat(row.querySelector('[name$="[quantity]"]')?.value) || 0;
    const rate = parseFloat(row.querySelector('[name$="[rate]"]')?.value)     || 0;
    const amt  = qty * rate;
    const disp = document.getElementById(`rowAmt_${i}`);
    const hid  = document.getElementById(`rowAmtHidden_${i}`);
    if (disp) disp.textContent = '₹' + fmt(amt);
    if (hid)  hid.value = amt;
}

// ── Calc totals ───────────────────────────────────────────────────
function calcTotals() {
    let subtotal = 0;
    let taxAmt   = 0;

    document.querySelectorAll('#itemsBody tr').forEach(tr => {
        const qty    = parseFloat(tr.querySelector('[name$="[quantity]"]')?.value)    || 0;
        const rate   = parseFloat(tr.querySelector('[name$="[rate]"]')?.value)        || 0;
        const taxPct = parseFloat(tr.querySelector('[name$="[tax_percent]"]')?.value) || 0;
        const rowAmt = qty * rate;
        subtotal += rowAmt;
        taxAmt   += rowAmt * taxPct / 100;
    });

    const discount  = parseFloat(document.getElementById('discount')?.value) || 0;
    const afterDisc = Math.max(0, subtotal - discount);
    const discRatio = subtotal > 0 ? afterDisc / subtotal : 1;
    taxAmt          = taxAmt * discRatio;
    const total     = afterDisc + taxAmt;
    const avgTaxPct = afterDisc > 0 ? (taxAmt / afterDisc * 100) : 0;

    set('dispSubtotal', '₹' + fmt(subtotal));
    set('dispDiscount', '- ₹' + fmt(discount));
    set('dispTax',      '₹' + fmt(taxAmt));
    set('dispTaxPct',   avgTaxPct.toFixed(1));
    set('dispTotal',    '₹' + fmt(total));
    set('sumSubtotal',  '₹' + fmt(subtotal));
    set('sumTax',       '₹' + fmt(taxAmt));
    set('sumTotal',     '₹' + fmt(total));
    set('sumItems',     document.querySelectorAll('#itemsBody tr').length);
}

// ── Helpers ───────────────────────────────────────────────────────
function fmt(n) { return parseFloat(n||0).toLocaleString('en-IN',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function set(id, val) { const el = document.getElementById(id); if (el) el.textContent = val; }
function escHtml(s) { return String(s||'').replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// ── Init — load EXISTING items ────────────────────────────────────
(function () {
    if (EXISTING_ITEMS && EXISTING_ITEMS.length) {
        EXISTING_ITEMS.forEach(item => {
            addRow(item.description || '', item.quantity || 1, item.rate || 0, item.tax_percent ?? '', item.product_id || '', item.service_id || '');
        });
    } else {
        addRow();
    }
    calcTotals();

    // Init contact preview from server data
    const sel = document.getElementById('contactSelect');
    if (sel?.value) loadContact(sel.value);

    // Submit loader
    document.getElementById('invForm')?.addEventListener('submit', function() {
        const btn = document.getElementById('saveBtn');
        if (btn) { btn.innerHTML = '⏳ Saving...'; btn.disabled = true; }
    });
})();
</script>
@endpush