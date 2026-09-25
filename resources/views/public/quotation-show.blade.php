<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<title>Quotation {{ $quotation->number }} — {{ $quotation->tenant->name ?? 'Quotation' }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) ?: '1' }}"/>
<style>
body { background: var(--bg-app); min-height:100vh; padding:24px 16px; }
.pq-wrap { max-width:820px; margin:0 auto; display:flex; flex-direction:column; gap:16px; }
.pq-brand { text-align:center; padding:8px 0 4px; font-size:13px; color:var(--text-300); }
.pq-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; overflow:hidden; }
.pq-hero { padding:24px; display:flex; align-items:flex-start; justify-content:space-between; gap:16px; flex-wrap:wrap; }
.pq-number { font-size:22px; font-weight:600; color:var(--text-100); font-family:'DM Mono',monospace; }
.pq-from { font-size:13px; color:var(--text-300); margin-top:2px; }
.pq-badge { display:inline-flex; align-items:center; gap:6px; padding:6px 14px; border-radius:20px; font-size:13px; font-weight:600; }
.pq-section { padding:18px 24px; border-top:1px solid var(--border-subtle); }
.pq-label { font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.6px; margin-bottom:10px; }
.pq-items-table { width:100%; border-collapse:collapse; }
.pq-items-table th { text-align:left; padding:8px 10px; font-size:11px; color:var(--text-300); border-bottom:1px solid var(--border-subtle); }
.pq-items-table td { padding:10px; font-size:13px; color:var(--text-100); border-bottom:1px solid var(--border-subtle); }
.pq-items-table .r { text-align:right; font-family:'DM Mono',monospace; }
.pq-totals { display:flex; justify-content:flex-end; padding-top:12px; }
.pq-totals table td { padding:4px 0 4px 20px; font-size:13px; color:var(--text-200); }
.pq-totals table td:last-child { text-align:right; font-family:'DM Mono',monospace; }
.pq-totals .grand td { font-size:16px; font-weight:700; color:var(--text-100); padding-top:8px; border-top:1px solid var(--border-default); }
.pq-text { font-size:13px; color:var(--text-200); line-height:1.6; white-space:pre-wrap; }
.pq-actions { padding:20px 24px; display:flex; gap:10px; flex-wrap:wrap; }
.pq-flash { padding:12px 16px; border-radius:10px; font-size:13.5px; font-weight:500; }
.pq-flash.success { background:var(--green-dim); color:var(--green); border:1px solid var(--green); }
.pq-flash.error   { background:var(--red-dim); color:var(--red); border:1px solid var(--red); }
.pq-flash.info    { background:var(--accent-dim); color:var(--accent); border:1px solid var(--accent); }
.pq-panel { display:none; padding:18px 24px; border-top:1px solid var(--border-subtle); background:var(--bg-elevated); }
.pq-panel.show { display:block; }
.pq-field { display:flex; flex-direction:column; gap:5px; margin-bottom:12px; }
.pq-field label { font-size:11.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.5px; }
.pq-input { width:100%; padding:10px 12px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:8px; color:var(--text-100); font-family:var(--font); font-size:13.5px; outline:none; }
.pq-sig-wrap { border:1.5px dashed var(--border-default); border-radius:8px; background:#fff; }
.pq-sig-canvas { width:100%; height:140px; display:block; touch-action:none; cursor:crosshair; border-radius:8px; }
.pq-sig-actions { display:flex; justify-content:flex-end; padding:6px; }
.pq-responded { padding:24px; text-align:center; }
.pq-responded-icon { font-size:36px; margin-bottom:8px; }
</style>
</head>
<body>

<div class="pq-wrap">
    <div class="pq-brand">Quotation shared by {{ $quotation->tenant->name ?? 'Mishora CRM' }}</div>

    @foreach(['success','error','info'] as $type)
    @if(session($type))
    <div class="pq-flash {{ $type }}">{{ session($type) }}</div>
    @endif
    @endforeach

    @php
    $st = config('quotation.statuses')[$quotation->status] ?? config('quotation.statuses')['draft'];
    $sym = $quotation->currencySymbol();
    $items = $quotation->items ?? [];
    $responded = $quotation->hasCustomerResponded();
    $expired = $quotation->isExpired();
    @endphp

    <div class="pq-card">
        <div class="pq-hero">
            <div>
                <div class="pq-number">{{ $quotation->number }}</div>
                <div class="pq-from">{{ $quotation->contact->name ?? 'Dear Customer' }}</div>
            </div>
            <span class="pq-badge" style="background:{{ $st['bg'] }};color:{{ $st['text_color'] }};border:1px solid {{ $st['color'] }}40">
                {{ $st['label'] }}
            </span>
        </div>

        <div class="pq-section">
            <div class="pq-label">Line Items</div>
            <div style="overflow-x:auto">
                <table class="pq-items-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="r">Qty</th>
                            <th class="r">Rate</th>
                            <th class="r">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($items as $item)
                        <tr>
                            <td>{{ $item['name'] ?? '—' }}</td>
                            <td class="r">{{ number_format($item['quantity'] ?? 0, 2) }}</td>
                            <td class="r">{{ $sym }}{{ number_format($item['rate'] ?? 0, 2) }}</td>
                            <td class="r">{{ $sym }}{{ number_format($item['amount'] ?? 0, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pq-totals">
                <table>
                    <tr><td>Subtotal</td><td>{{ $sym }}{{ number_format($quotation->subtotal ?? 0, 2) }}</td></tr>
                    @if(($quotation->discount ?? 0) > 0)
                    <tr><td>Discount</td><td>-{{ $sym }}{{ number_format($quotation->discount, 2) }}</td></tr>
                    @endif
                    <tr><td>Tax</td><td>+{{ $sym }}{{ number_format($quotation->tax_amount ?? 0, 2) }}</td></tr>
                    <tr class="grand"><td>Total</td><td>{{ $sym }}{{ number_format($quotation->total ?? 0, 2) }}</td></tr>
                </table>
            </div>
        </div>

        @if($quotation->terms)
        <div class="pq-section">
            <div class="pq-label">Terms &amp; Conditions</div>
            <div class="pq-text">{{ $quotation->terms }}</div>
        </div>
        @endif

        @if($quotation->notes)
        <div class="pq-section">
            <div class="pq-label">Notes</div>
            <div class="pq-text">{{ $quotation->notes }}</div>
        </div>
        @endif

        @if($responded)
            <div class="pq-responded">
                <div class="pq-responded-icon">{{ $quotation->status === 'accepted' ? '✅' : '❌' }}</div>
                <div style="font-size:15px;font-weight:600;color:var(--text-100)">
                    You {{ $quotation->status === 'accepted' ? 'accepted' : 'rejected' }} this quotation
                    on {{ $quotation->customer_responded_at->format('d M Y, h:i A') }}.
                </div>
            </div>
        @elseif($expired)
            <div class="pq-responded">
                <div class="pq-responded-icon">⏰</div>
                <div style="font-size:15px;font-weight:600;color:var(--text-100)">This quotation has expired.</div>
                <div style="font-size:13px;color:var(--text-300);margin-top:4px">Please contact {{ $quotation->tenant->name ?? 'us' }} for an updated quotation.</div>
            </div>
        @else
            <div class="pq-actions">
                <button type="button" class="btn btn-primary" onclick="showPanel('accept')">
                    Accept Quotation
                </button>
                <button type="button" class="btn btn-secondary" onclick="showPanel('reject')">
                    Reject
                </button>
            </div>

            {{-- Accept panel --}}
            <div class="pq-panel" id="panel-accept">
                <form method="POST" action="{{ route('public.quotations.accept', $quotation->public_token) }}" id="acceptForm">
                    @csrf
                    <div class="pq-field">
                        <label>Your Full Name <span style="color:var(--red)">*</span></label>
                        <input type="text" name="signed_name" class="pq-input" required maxlength="150" placeholder="Type your full name to sign"/>
                    </div>
                    <div class="pq-field">
                        <label>Signature (optional — draw with mouse or finger)</label>
                        <div class="pq-sig-wrap">
                            <canvas class="pq-sig-canvas" id="sigCanvas"></canvas>
                            <div class="pq-sig-actions">
                                <button type="button" class="btn btn-secondary btn-sm" onclick="clearSignature()">Clear</button>
                            </div>
                        </div>
                        <input type="hidden" name="signature_data" id="signatureData"/>
                    </div>
                    <div style="display:flex;gap:8px">
                        <button type="button" class="btn btn-secondary" onclick="hidePanels()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Confirm Acceptance</button>
                    </div>
                </form>
            </div>

            {{-- Reject panel --}}
            <div class="pq-panel" id="panel-reject">
                <form method="POST" action="{{ route('public.quotations.reject', $quotation->public_token) }}">
                    @csrf
                    <div class="pq-field">
                        <label>Reason (optional)</label>
                        <textarea name="reason" class="pq-input" rows="3" maxlength="500" placeholder="Let us know why, so we can follow up..."></textarea>
                    </div>
                    <div style="display:flex;gap:8px">
                        <button type="button" class="btn btn-secondary" onclick="hidePanels()">Cancel</button>
                        <button type="submit" class="btn btn-primary" style="background:var(--red);border-color:var(--red)">Confirm Rejection</button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>

<script>
function showPanel(name){
    document.getElementById('panel-accept')?.classList.remove('show');
    document.getElementById('panel-reject')?.classList.remove('show');
    document.getElementById('panel-' + name)?.classList.add('show');
    if(name === 'accept') setupSignaturePad();
}
function hidePanels(){
    document.getElementById('panel-accept')?.classList.remove('show');
    document.getElementById('panel-reject')?.classList.remove('show');
}

let sigInitialized = false;
function setupSignaturePad(){
    if(sigInitialized) return;
    sigInitialized = true;

    const canvas = document.getElementById('sigCanvas');
    const ctx = canvas.getContext('2d');
    const ratio = window.devicePixelRatio || 1;

    function resize(){
        const rect = canvas.getBoundingClientRect();
        canvas.width = rect.width * ratio;
        canvas.height = rect.height * ratio;
        ctx.scale(ratio, ratio);
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#1a1a2e';
    }
    resize();

    let drawing = false;
    function pos(e){
        const rect = canvas.getBoundingClientRect();
        const t = e.touches ? e.touches[0] : e;
        return { x: t.clientX - rect.left, y: t.clientY - rect.top };
    }
    function start(e){ drawing = true; const p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); e.preventDefault(); }
    function move(e){ if(!drawing) return; const p = pos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); e.preventDefault(); }
    function end(){ drawing = false; }

    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', move);
    window.addEventListener('mouseup', end);
    canvas.addEventListener('touchstart', start, {passive:false});
    canvas.addEventListener('touchmove', move, {passive:false});
    canvas.addEventListener('touchend', end);

    window.clearSignature = function(){
        ctx.clearRect(0, 0, canvas.width, canvas.height);
    };

    document.getElementById('acceptForm').addEventListener('submit', function(){
        document.getElementById('signatureData').value = canvas.toDataURL('image/png');
    });
}
</script>

</body>
</html>
