@extends('layouts.app')
@section('title', 'Counter')

@push('styles')
<style>
.ct-wrap { max-width:520px; display:flex; flex-direction:column; gap:14px; }
.ct-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); padding:18px 20px; }
.ct-scan-row { display:flex; gap:8px; margin-bottom:12px; }
.ct-scan-row .btn { flex:1; justify-content:center; padding:14px; font-size:15px; }
#ct-video { width:100%; max-height:320px; border-radius:var(--r-md); background:#000; object-fit:cover; margin-bottom:12px; }
.ct-or { text-align:center; font-size:11.5px; color:var(--text-400); margin:4px 0 10px; text-transform:uppercase; letter-spacing:.5px; }
.ct-phone { display:flex; gap:8px; }
.ct-phone input { flex:1; padding:11px 14px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-size:15px; }
.ct-msg { margin-top:12px; font-size:13px; min-height:18px; color:var(--text-300); }
.ct-msg.ok { color:var(--green); font-weight:600; }
.ct-msg.err { color:var(--red); font-weight:600; }
.ct-head { display:flex; justify-content:space-between; align-items:flex-start; gap:10px; margin-bottom:14px; }
/* The customer's name is the fraud check (staff match it to the face), so it
   must be the biggest thing on the card — larger than any stat below. */
.ct-name { font-size:26px; font-weight:800; line-height:1.15; color:var(--text-100); overflow-wrap:anywhere; }
.ct-sub { font-size:12.5px; color:var(--text-400); margin-top:2px; }
.ct-stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(110px,1fr)); gap:10px; margin-bottom:14px; }
.ct-stat { background:var(--bg-elevated); border-radius:var(--r-md); padding:11px 12px; text-align:center; }
.ct-stat .v { font-size:20px; font-weight:800; color:var(--text-100); line-height:1.1; }
.ct-stat .l { font-size:11px; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; margin-top:3px; }
.ct-actions { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:12px; }
.ct-actions .btn { padding:11px 16px; }
.ct-bill { display:flex; flex-direction:column; gap:6px; margin-bottom:10px; }
.ct-bill label { font-size:12px; font-weight:600; color:var(--text-200); }
.ct-bill label span { font-weight:400; color:var(--text-400); }
.ct-bill input { padding:10px 13px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-size:15px; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.loyalty.index') }}" style="color:var(--text-300);text-decoration:none">Loyalty</a> › Counter
        </div>
        <div class="page-title">Counter</div>
        <div class="page-sub">Scan the customer's wallet QR, then add a stamp or redeem in a tap.</div>
    </div>
</div>

<div id="counter" class="ct-wrap"
     data-mode="{{ $mode }}"
     data-resolve="{{ route('tenant.loyalty.counter.resolve') }}"
     data-stamp="{{ route('tenant.loyalty.counter.stamp') }}"
     data-checkout="{{ route('tenant.loyalty.counter.checkout') }}">

    <div class="ct-card">
        <div class="ct-scan-row">
            <button type="button" id="ct-scan" class="btn btn-primary">Scan customer QR</button>
            <button type="button" id="ct-scan-stop" class="btn btn-secondary" hidden>Stop camera</button>
        </div>
        <video id="ct-video" playsinline muted hidden></video>

        <div class="ct-or">or type their number</div>
        <form id="ct-phone-form" class="ct-phone" autocomplete="off">
            <input type="tel" id="ct-phone" inputmode="numeric" placeholder="Phone number" maxlength="20"/>
            <button type="submit" class="btn btn-secondary">Find</button>
        </form>

        <div id="ct-msg" class="ct-msg" role="status" aria-live="polite"></div>
    </div>

    <div id="ct-result" class="ct-card" hidden></div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/jsQR.js') }}"></script>
<script src="{{ asset('js/loyalty-counter.js') }}"></script>
<script src="{{ asset('js/loyalty-counter-page.js') }}"></script>
@endpush
