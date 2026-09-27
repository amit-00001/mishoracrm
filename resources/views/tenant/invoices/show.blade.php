@extends('layouts.app')

@section('title', $invoice->number)
@push('styles')
<link rel="stylesheet" href="{{ asset('css/tenant/invoices/show.css') }}">
@endpush
@section('content')

<div class="invoice-show-wrap">

    {{-- HEADER --}}
    <div class="invoice-head">

        <div>
            <div class="invoice-number">
                {{ $invoice->number }}
            </div>

            <div class="invoice-meta-line">
                Created {{ $invoice->date->format('d M Y') }}
            </div>
        </div>

        <div class="invoice-actions">

            <a href="{{ route('tenant.invoices.edit', $invoice->id) }}"
               class="btn btn-secondary">
                Edit
            </a>

            <a href="{{ route('tenant.invoices.pdf', $invoice->id) }}"
               class="btn btn-primary">
                Download PDF
            </a>

            @php
                $sendToEmail = $invoice->contact?->primaryEmail();
                $sendCcCount = $invoice->contact ? count($invoice->contact->ccEmails()) : 0;
            @endphp
            @if($sendToEmail)
            <form method="POST" action="{{ route('tenant.invoices.send', $invoice->id) }}"
                  data-confirm="Send this invoice to {{ $sendToEmail }}{{ $sendCcCount ? ' (cc: '.$sendCcCount.')' : '' }}?" data-confirm-ok="Send" data-confirm-danger="false">
                @csrf
                <button type="submit" class="btn btn-success">
                    Send Invoice{{ $sendCcCount ? ' (cc: '.$sendCcCount.')' : '' }}
                </button>
            </form>
            @else
            <button class="btn btn-success" disabled title="Contact has no email address">
                Send Invoice
            </button>
            @endif

            @if($invoice->contact?->phone)
            <form method="POST" action="{{ route('tenant.invoices.send_whatsapp', $invoice->id) }}"
                  data-confirm="Send this invoice to {{ $invoice->contact->phone }} via WhatsApp?" data-confirm-ok="Send" data-confirm-danger="false">
                @csrf
                <button type="submit" class="btn btn-success">
                    Send via WhatsApp
                </button>
            </form>
            @else
            <button class="btn btn-success" disabled title="Contact has no phone number">
                Send via WhatsApp
            </button>
            @endif

        </div>

    </div>

    {{-- STATUS CARDS --}}
    <div class="invoice-stats">

        <div class="stat-card">
            <div class="stat-label">Total</div>
            <div class="stat-value">
                ₹{{ number_format($invoice->total, 2) }}
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Paid</div>
            <div class="stat-value text-green">
                ₹{{ number_format($invoice->paid_amount, 2) }}
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Balance</div>
            <div class="stat-value text-red">
                ₹{{ number_format($invoice->total - $invoice->paid_amount, 2) }}
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Status</div>

            <div class="status-badge status-{{ $invoice->status }}">
                {{ ucfirst($invoice->status) }}
            </div>
        </div>

    </div>

    {{-- GRID --}}
    <div class="invoice-grid">

        {{-- LEFT --}}
        <div>

            {{-- CUSTOMER --}}
            <div class="card mb-4">

                <div class="card-head">
                    Customer Information
                </div>

                <div class="card-body">

                    <div class="customer-name">
                        {{ $invoice->contact?->name }}
                    </div>

                    <div class="meta-line">
                        {{ $invoice->contact?->company }}
                    </div>

                    <div class="meta-line">
                        {{ $invoice->contact?->phone }}
                    </div>

                    <div class="meta-line">
                        {{ $invoice->contact?->email }}
                    </div>

                    <div class="meta-line">
                        GST: {{ $invoice->contact?->gst_number ?: '-' }}
                    </div>

                </div>

            </div>

            {{-- ITEMS --}}
            <div class="card">

                <div class="card-head">
                    Invoice Items
                </div>

                <div class="card-body p-0">

                    <table class="invoice-table">

                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Qty</th>
                                <th>Rate</th>
                                <th>Tax</th>
                                <th>Total</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($invoice->items as $item)

                            <tr>

                                <td data-label="Description">
                                    <div class="item-title">
                                        {{ $item['description'] ?? '-' }}
                                    </div>

                                    <div class="item-sub">
                                        HSN: {{ $item['hsn'] ?? '-' }}
                                    </div>
                                </td>

                                <td data-label="Qty">{{ $item['quantity'] ?? '-' }}</td>

                                <td data-label="Rate">
                                    ₹{{ number_format($item['rate'], 2) }}
                                </td>

                                <td data-label="Tax">
                                    {{ $item['tax_percent'] ?? $invoice->tax_percent }}%
                                </td>

                                <td data-label="Total">
                                    ₹{{ number_format($item['amount'] ?? ($item['quantity'] * $item['rate']), 2) }}
                                </td>

                            </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

                {{-- PAYMENTS — part of the same items card, its own line-item table --}}
                <div class="card-subhead">
                    Payments
                </div>

                <div class="card-body p-0">

                    @if($invoice->payments->isEmpty())

                    <div class="empty-state">
                        <div class="empty-sub">No payments recorded yet.</div>
                    </div>

                    @else

                    <table class="invoice-table">

                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Note</th>
                                <th>Recorded By</th>
                                <th class="right">Amount</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($invoice->payments as $payment)

                            <tr>
                                <td data-label="Date">{{ $payment->paid_at->format('d M Y') }}</td>
                                <td data-label="Method">{{ \App\Models\Invoice::paymentMethods()[$payment->method] ?? ucfirst($payment->method) }}</td>
                                <td data-label="Note">{{ $payment->note ?: '-' }}</td>
                                <td data-label="Recorded By">{{ $payment->recordedBy?->name ?? '-' }}</td>
                                <td data-label="Amount" class="right">₹{{ number_format($payment->amount, 2) }}</td>
                            </tr>

                            @endforeach

                        </tbody>

                        <tfoot>
                            <tr>
                                <td colspan="4" class="right"><strong>Total Paid</strong></td>
                                <td class="right"><strong>₹{{ number_format($invoice->paid_amount, 2) }}</strong></td>
                            </tr>
                        </tfoot>

                    </table>

                    @endif

                </div>

            </div>

        </div>

        {{-- RIGHT --}}
        <div>

            {{-- SUMMARY --}}
            <div class="card sticky-top">

                <div class="card-head">
                    Payment Summary
                </div>

                <div class="card-body">

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <strong>
                            ₹{{ number_format($invoice->subtotal, 2) }}
                        </strong>
                    </div>

                    @foreach($invoice->gstLines() as $line)
                    <div class="summary-row">
                        <span>{{ $line['label'] }}</span>
                        <strong>₹{{ number_format($line['amount'], 2) }}</strong>
                    </div>
                    @endforeach

                    <div class="summary-row">
                        <span>Discount</span>
                        <strong>
                            ₹{{ number_format($invoice->discount, 2) }}
                        </strong>
                    </div>

                    <div class="summary-row total">
                        <span>Total</span>
                        <strong>
                            ₹{{ number_format($invoice->total, 2) }}
                        </strong>
                    </div>

                    @if($invoice->hasLoyaltyRedemption())
                    <div class="summary-row">
                        <span>Loyalty Redeemed ({{ number_format($invoice->loyalty_points_redeemed) }} pts)</span>
                        <strong style="color:var(--green)">− ₹{{ number_format($invoice->loyalty_discount, 2) }}</strong>
                    </div>
                    @endif
                    @if($invoice->campaign_discount > 0)
                    <div class="summary-row">
                        <span>Campaign Coupon</span>
                        <strong style="color:var(--green)">− ₹{{ number_format($invoice->campaign_discount, 2) }}</strong>
                    </div>
                    @endif
                    @if($invoice->hasLoyaltyRedemption() || $invoice->campaign_discount > 0)
                    <div class="summary-row">
                        <span>Paid</span>
                        <strong>₹{{ number_format($invoice->paid_amount, 2) }}</strong>
                    </div>
                    <div class="summary-row total">
                        <span>Balance Due</span>
                        <strong>₹{{ number_format($invoice->due_amount, 2) }}</strong>
                    </div>
                    @endif

                </div>

            </div>

            {{-- SCAN CUSTOMER — read their wallet QR instead of typing points / a code --}}
            @if($tenant->hasModuleEnabled('loyalty') && $tenant->hasModuleEnabled('customer_portal') && $invoice->contact && $invoice->status !== 'paid'
                && (auth()->user()->can('loyalty.stamp') || auth()->user()->can('loyalty.manage')))
            <div class="card mt-4" id="inv-scan" data-contact="{{ $invoice->contact_id }}" data-contact-name="{{ $invoice->contact->name }}">
                <div class="card-head">Scan Customer</div>
                <div class="card-body">
                    <button type="button" class="btn btn-secondary" id="inv-scan-btn">Scan wallet QR</button>
                    <button type="button" class="btn btn-secondary" id="inv-scan-stop" hidden>Stop camera</button>
                    <video id="inv-video" playsinline muted hidden style="width:100%;max-height:260px;border-radius:8px;background:#000;margin-top:10px;object-fit:cover"></video>
                    <div id="inv-scan-msg" style="font-size:13px;margin-top:10px" role="status" aria-live="polite"></div>
                    <div id="inv-scan-actions" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px"></div>
                    <form id="inv-redeem-form" method="POST" action="{{ route('tenant.invoices.redeem_loyalty', $invoice->id) }}" hidden>
                        @csrf
                        <input type="hidden" name="use_max" value="1"/>
                    </form>
                    <form id="inv-coupon-form" method="POST" action="{{ route('tenant.invoices.apply_coupon', $invoice->id) }}" hidden>
                        @csrf
                        <input type="hidden" name="code" id="inv-coupon-code"/>
                    </form>
                </div>
            </div>
            @push('scripts')
            <script src="{{ asset('js/jsQR.js') }}"></script>
            <script src="{{ asset('js/loyalty-counter.js') }}"></script>
            <script src="{{ asset('js/invoice-scan.js') }}"></script>
            @endpush
            @endif

            {{-- LOYALTY REDEMPTION --}}
            @if($invoice->hasLoyaltyRedemption() && $invoice->status !== 'paid')
            <div class="card mt-4">
                <div class="card-head">Loyalty Points</div>
                <div class="card-body">
                    <p style="margin:0 0 12px;font-size:13.5px;color:var(--text-200)">
                        <strong>{{ number_format($invoice->loyalty_points_redeemed) }} points</strong>
                        redeemed on this invoice — worth
                        <strong>₹{{ number_format($invoice->loyalty_discount, 2) }}</strong>.
                    </p>
                    <form method="POST" action="{{ route('tenant.invoices.unredeem_loyalty', $invoice->id) }}"
                          data-confirm="Remove this redemption and return the points to the customer?" data-confirm-ok="Remove">
                        @csrf
                        <button type="submit" class="btn btn-secondary">Remove Redemption</button>
                    </form>
                </div>
            </div>
            @elseif(!is_null($loyaltyQuote ?? null))
            <div class="card mt-4">
                <div class="card-head">Loyalty Points</div>
                <div class="card-body">
                    @if($invoice->contact->loyalty_points > 0)
                    <p style="margin:0 0 10px;font-size:13px;color:var(--text-300)">
                        {{ $invoice->contact->name }} has
                        <strong style="color:var(--text-100)">{{ number_format($invoice->contact->loyalty_points) }} points</strong>{{ $invoice->contact->loyalty_tier ? ' · ' . $invoice->contact->loyaltyTierLabel() : '' }}.
                    </p>
                    @endif

                    @if($loyaltyQuote['error'])
                    <p style="margin:0;font-size:13px;color:var(--text-400)">{{ $loyaltyQuote['error'] }}</p>
                    @else
                    <p style="margin:0 0 12px;font-size:13px;color:var(--text-300)">
                        Up to <strong style="color:var(--green)">{{ number_format($loyaltyQuote['points']) }} points (₹{{ number_format($loyaltyQuote['value'], 2) }})</strong> can be applied to this bill.
                    </p>
                    <form method="POST" action="{{ route('tenant.invoices.redeem_loyalty', $invoice->id) }}"
                          style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                        @csrf
                        <input type="number" name="points" min="1" step="1"
                               placeholder="Points" style="width:120px;padding:8px 10px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm);color:var(--text-100);font-size:13px"/>
                        <button type="submit" class="btn btn-secondary">Redeem</button>
                        <button type="submit" name="use_max" value="1" class="btn btn-primary">Use Max</button>
                    </form>
                    @endif
                </div>
            </div>
            @endif

            {{-- CAMPAIGN COUPON --}}
            @if($tenant->hasModuleEnabled('loyalty') && $invoice->contact && $invoice->status !== 'paid')
            <div class="card mt-4">
                <div class="card-head">Campaign Coupon</div>
                <div class="card-body">
                    @if($invoice->hasCampaignCoupon())
                    <p style="margin:0 0 12px;font-size:13.5px;color:var(--text-200)">
                        Coupon applied — <strong>₹{{ number_format($invoice->campaign_discount, 2) }}</strong> off.
                    </p>
                    <form method="POST" action="{{ route('tenant.invoices.remove_coupon', $invoice->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary">Remove Coupon</button>
                    </form>
                    @else
                    <form method="POST" action="{{ route('tenant.invoices.apply_coupon', $invoice->id) }}"
                          style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                        @csrf
                        <input type="text" name="code" placeholder="Coupon code" required
                               style="width:150px;padding:8px 10px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm);color:var(--text-100);font-size:13px;text-transform:uppercase"/>
                        <button type="submit" class="btn btn-primary">Apply</button>
                    </form>
                    @endif
                </div>
            </div>
            @endif

            {{-- LOYALTY REWARD (free item) --}}
            @php $rewardCatalog = $tenant->hasModuleEnabled('loyalty') ? ($tenant->loyaltySettings()['reward_catalog'] ?? []) : []; @endphp
            @if($tenant->hasModuleEnabled('loyalty') && $invoice->contact && $invoice->status !== 'paid' && ($invoice->hasLoyaltyReward() || count($rewardCatalog)))
            <div class="card mt-4">
                <div class="card-head">Loyalty Reward</div>
                <div class="card-body">
                    @if($invoice->hasLoyaltyReward())
                    <p style="margin:0 0 12px;font-size:13.5px;color:var(--text-200)">
                        <strong>{{ $invoice->loyalty_reward }}</strong> redeemed for
                        <strong>{{ number_format($invoice->loyalty_reward_points) }} points</strong> — add the item to the order.
                    </p>
                    <form method="POST" action="{{ route('tenant.invoices.remove_reward', $invoice->id) }}">
                        @csrf
                        <button type="submit" class="btn btn-secondary">Remove Reward</button>
                    </form>
                    @else
                    <form method="POST" action="{{ route('tenant.invoices.redeem_reward', $invoice->id) }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                        @csrf
                        <select name="reward" required style="padding:8px 10px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm);color:var(--text-100);font-size:13px">
                            <option value="">— Choose a reward —</option>
                            @foreach($rewardCatalog as $r)
                            <option value="{{ $r['name'] }}" @disabled(($r['points'] ?? 0) > (int) $invoice->contact->loyalty_points)>
                                {{ $r['name'] }} ({{ number_format($r['points'] ?? 0) }} pts)
                            </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-primary">Redeem</button>
                    </form>
                    <div style="font-size:11.5px;color:var(--text-400);margin-top:6px">{{ $invoice->contact->name }} has {{ number_format($invoice->contact->loyalty_points) }} points.</div>
                    @endif
                </div>
            </div>
            @endif

            {{-- RECORD PAYMENT --}}
            @if($invoice->due_amount > 0)
            <div class="card mt-4">

                <div class="card-head">
                    Record Payment
                </div>

                <div class="card-body">

                    <form method="POST" action="{{ route('tenant.invoices.record_payment', $invoice->id) }}" id="payForm">
                        @csrf

                        <div style="overflow-x:auto">
                            <table class="pay-table">
                                <thead>
                                    <tr>
                                        <th>Amount (₹)</th>
                                        <th>Method</th>
                                        <th>Paid On</th>
                                        <th>Note</th>
                                        <th style="width:36px"></th>
                                    </tr>
                                </thead>
                                <tbody id="payRowsBody">
                                    {{-- rows injected by JS --}}
                                </tbody>
                            </table>
                        </div>

                        <button type="button" class="add-row-btn" onclick="addPayRow()">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:14px;height:14px">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            Add Payment
                        </button>

                        <div class="totals-box" style="margin-top:14px">
                            <div class="total-row">
                                <span class="total-label">Due Amount</span>
                                <span class="total-value">₹{{ number_format($invoice->due_amount, 2) }}</span>
                            </div>
                            <div class="total-row grand">
                                <span>Total Being Recorded</span>
                                <span id="payRunningTotal">₹0.00</span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:14px;">
                            Record Payment(s)
                        </button>

                    </form>

                </div>

            </div>
            @endif

            {{-- NOTES --}}
            <div class="card mt-4">

                <div class="card-head">
                    Notes
                </div>

                <div class="card-body">

                    {{ $invoice->notes ?: 'No notes added.' }}

                </div>

            </div>

        </div>

    </div>

</div>

@endsection

@if($invoice->due_amount > 0)
@push('scripts')
<script>
const PAYMENT_METHODS = @json(\App\Models\Invoice::paymentMethods());
const DUE_AMOUNT       = {{ $invoice->due_amount }};
let payRowCount = 0;

function payMethodOptions() {
    return Object.entries(PAYMENT_METHODS)
        .map(([key, label]) => `<option value="${key}">${label}</option>`)
        .join('');
}

function addPayRow(amount = '') {
    const tbody = document.getElementById('payRowsBody');
    const i     = payRowCount++;
    const tr    = document.createElement('tr');
    tr.dataset.row = i;

    tr.innerHTML = `
        <td data-label="Amount">
            <input type="number" name="payments[${i}][amount]" class="pay-input right"
                   min="0.01" step="0.01" max="${DUE_AMOUNT}"
                   value="${amount}" oninput="calcPayTotal()" required/>
        </td>
        <td data-label="Method">
            <select name="payments[${i}][method]" class="pay-input" required>
                ${payMethodOptions()}
            </select>
        </td>
        <td data-label="Paid On">
            <input type="date" name="payments[${i}][paid_at]" class="pay-input"
                   value="${new Date().toISOString().slice(0,10)}" required/>
        </td>
        <td data-label="Note">
            <input type="text" name="payments[${i}][note]" class="pay-input" maxlength="255" placeholder="Optional"/>
        </td>
        <td style="text-align:center">
            <button type="button" class="del-row" onclick="delPayRow(this)">✕</button>
        </td>`;

    tbody.appendChild(tr);
    calcPayTotal();
}

function delPayRow(btn) {
    const tbody = document.getElementById('payRowsBody');
    if (tbody.rows.length <= 1) return;
    btn.closest('tr').remove();
    calcPayTotal();
}

function calcPayTotal() {
    let total = 0;
    document.querySelectorAll('#payRowsBody [name$="[amount]"]').forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    const el = document.getElementById('payRunningTotal');
    if (el) {
        el.textContent = '₹' + total.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
        el.style.color = total > DUE_AMOUNT + 0.01 ? 'var(--red)' : 'var(--accent)';
    }
}

(function () {
    addPayRow(DUE_AMOUNT);
})();
</script>
@endpush
@endif