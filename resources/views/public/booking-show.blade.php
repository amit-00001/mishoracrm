<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<title>Book an appointment — {{ $tenant->name }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) ?: '1' }}"/>
<style>
body {
  background:var(--bg-base);
  background-image:radial-gradient(circle at 15% 0%, var(--accent-dim), transparent 40%), radial-gradient(circle at 85% 100%, var(--accent-dim), transparent 40%);
  min-height:100vh; display:flex; align-items:flex-start; justify-content:center; padding:48px 16px;
}
.bk-wrap { width:100%; max-width:540px; margin:0 auto; display:flex; flex-direction:column; gap:14px; }

.bk-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-xl); overflow:hidden; box-shadow:0 30px 60px -20px rgba(20,20,45,.22), 0 8px 24px rgba(20,20,45,.06); }
.bk-hero { position:relative; padding:34px 28px 52px; background:linear-gradient(135deg, var(--accent) 0%, var(--accent-hover) 100%); overflow:hidden; }
.bk-hero::before { content:''; position:absolute; width:220px; height:220px; border-radius:50%; background:rgba(255,255,255,.08); top:-110px; right:-60px; }
.bk-hero::after { content:''; position:absolute; width:160px; height:160px; border-radius:50%; background:rgba(255,255,255,.06); bottom:-100px; left:-40px; }
.bk-hero-inner { position:relative; z-index:1; display:flex; align-items:center; gap:14px; }
.bk-logo { width:52px; height:52px; flex:none; border-radius:16px; background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.35); backdrop-filter:blur(8px); display:flex; align-items:center; justify-content:center; color:#fff; font-size:20px; font-weight:800; }
.bk-hero-text { min-width:0; }
.bk-brand { font-size:12px; color:rgba(255,255,255,.82); font-weight:500; letter-spacing:.1px; }
.bk-title { font-size:20px; font-weight:800; color:#fff; letter-spacing:-.3px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

.bk-body { position:relative; margin-top:-28px; background:var(--bg-surface); border-radius:22px 22px 0 0; padding:28px 26px 30px; }

.bk-flash { padding:12px 16px; border-radius:var(--r-md); font-size:13.5px; font-weight:500; }
.bk-flash.success { background:var(--green-dim); color:var(--green); border:1px solid var(--green); }
.bk-flash.error   { background:var(--red-dim); color:var(--red); border:1px solid var(--red); }
.bk-flash.info    { background:var(--accent-dim); color:var(--accent); border:1px solid var(--accent); }

.bk-progress { display:flex; align-items:center; justify-content:center; gap:8px; margin-bottom:24px; }
.bk-progress span { width:8px; height:8px; border-radius:4px; background:var(--border-strong); transition:all .25s var(--ease); }
.bk-progress span.active { width:26px; background:var(--accent); }

.bk-field    { display:flex; flex-direction:column; gap:6px; margin-bottom:14px; }
.bk-field label { font-size:11.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.5px; }
.bk-input { width:100%; padding:12px 14px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-md); color:var(--text-100); font-family:var(--font); font-size:13.5px; outline:none; transition:border-color .15s, box-shadow .15s, background .15s; }
.bk-input:focus { border-color:var(--accent); background:var(--bg-surface); box-shadow:0 0 0 4px var(--accent-glow); }
.bk-input::placeholder { color:var(--text-400); }

.bk-svc { display:flex; flex-direction:column; gap:10px; }
.bk-svc-opt { display:flex; align-items:center; justify-content:space-between; gap:10px; padding:15px 16px; border:1.5px solid var(--border-default); border-radius:var(--r-lg); cursor:pointer; transition:all .15s; background:var(--bg-surface); }
.bk-svc-opt:hover { border-color:var(--border-strong); background:var(--bg-hover); transform:translateY(-1px); }
.bk-svc-opt.sel { border-color:var(--accent); background:var(--accent-dim); box-shadow:0 0 0 4px var(--accent-glow); }
.bk-svc-name { font-size:13.5px; font-weight:700; color:var(--text-100); }
.bk-svc-meta { font-size:12px; color:var(--text-400); margin-top:2px; }
.bk-svc-price { font-size:13.5px; font-weight:700; color:var(--accent); white-space:nowrap; }

.bk-slots { display:grid; grid-template-columns:repeat(3,1fr); gap:9px; margin-top:8px; }
.bk-slot { padding:11px 4px; text-align:center; border:1.5px solid var(--border-default); border-radius:var(--r-md); font-size:12.5px; font-family:'DM Mono',monospace; color:var(--text-200); cursor:pointer; transition:all .15s; background:var(--bg-surface); }
.bk-slot:hover { border-color:var(--border-strong); background:var(--bg-hover); }
.bk-slot.sel { border-color:var(--accent); background:var(--accent); color:#fff; font-weight:600; box-shadow:0 4px 12px var(--accent-glow); }
.bk-empty { font-size:12.5px; color:var(--text-400); padding:14px 0; text-align:center; grid-column:1/-1; }

.bk-btn {
  width:100%; padding:14px; border-radius:var(--r-lg); border:none; background:linear-gradient(135deg,var(--accent),var(--accent-hover));
  color:#fff; font-size:14px; font-weight:700; letter-spacing:.2px; cursor:pointer; margin-top:6px;
  box-shadow:0 10px 24px -4px var(--accent-glow); transition:transform .15s, box-shadow .15s;
  display:flex; align-items:center; justify-content:center; gap:8px;
}
.bk-btn:hover { transform:translateY(-1px); box-shadow:0 14px 28px -4px var(--accent-glow); }
.bk-btn:disabled { opacity:.5; cursor:not-allowed; transform:none; }

.bk-step { display:none; }
.bk-step.show { display:block; animation:bkFadeIn .25s ease; }
@keyframes bkFadeIn { from { opacity:0; transform:translateY(4px); } to { opacity:1; transform:translateY(0); } }

.bk-back { font-size:12.5px; color:var(--text-300); font-weight:600; cursor:pointer; margin-bottom:16px; display:inline-flex; align-items:center; gap:5px; transition:color .15s; }
.bk-back:hover { color:var(--accent); }
.bk-disabled { text-align:center; padding:34px 10px; color:var(--text-300); font-size:13.5px; }

.bk-trust { text-align:center; font-size:11.5px; color:var(--text-300); display:flex; align-items:center; justify-content:center; gap:6px; }
.bk-trust svg { flex:none; }
</style>
</head>
<body>

<div class="bk-wrap">
    @foreach(['success','error','info'] as $type)
    @if(session($type))
    <div class="bk-flash {{ $type }}">{{ session($type) }}</div>
    @endif
    @endforeach

    <div class="bk-card">
        <div class="bk-hero">
            <div class="bk-hero-inner">
                <div class="bk-logo">{{ strtoupper(substr($tenant->name, 0, 1)) }}</div>
                <div class="bk-hero-text">
                    <div class="bk-brand">Book an appointment with</div>
                    <div class="bk-title">{{ $tenant->name }}</div>
                </div>
            </div>
        </div>

        <div class="bk-body">
        @if(!$enabled)
            <div class="bk-disabled">Online booking is currently unavailable. Please contact us directly.</div>
        @elseif($services->isEmpty())
            <div class="bk-disabled">No services are open for online booking yet. Please contact {{ $tenant->name }} directly to book.</div>
        @else
            <div class="bk-progress" id="bkProgress">
                <span class="active" data-step="1"></span>
                <span data-step="2"></span>
                <span data-step="3"></span>
            </div>

            {{-- Step 1: pick a service --}}
            <div class="bk-step show" id="step1">
                <div class="bk-field"><label>Choose a Service</label></div>
                <div class="bk-svc" id="serviceList">
                    @foreach($services as $s)
                    <div class="bk-svc-opt" data-id="{{ $s->id }}" data-name="{{ $s->name }}" onclick="selectService(this)">
                        <div>
                            <div class="bk-svc-name">{{ $s->name }}</div>
                            @if($s->description)<div class="bk-svc-meta">{{ $s->description }}</div>@endif
                        </div>
                        <div class="bk-svc-price">₹{{ number_format($s->rate, 0) }}</div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Step 2: pick a date + slot --}}
            <div class="bk-step" id="step2">
                <span class="bk-back" onclick="goToStep(1)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                    Back
                </span>
                <div class="bk-field">
                    <label>Date</label>
                    <input type="date" id="dateInput" class="bk-input" min="{{ now()->toDateString() }}"
                           max="{{ now()->addDays(60)->toDateString() }}" value="{{ now()->toDateString() }}" onchange="loadSlots()"/>
                </div>
                <div class="bk-field">
                    <label>Available Times</label>
                    <div class="bk-slots" id="slotsContainer"></div>
                </div>
            </div>

            {{-- Step 3: contact details --}}
            <div class="bk-step" id="step3">
                <span class="bk-back" onclick="goToStep(2)">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                    Back
                </span>
                <form method="POST" action="{{ route('public.booking.store', $token) }}" id="bookingForm">
                    @csrf
                    <input type="hidden" name="service_id" id="formServiceId"/>
                    <input type="hidden" name="date" id="formDate"/>
                    <input type="hidden" name="time" id="formTime"/>

                    <div class="bk-field">
                        <label>Your Name <span style="color:var(--red)">*</span></label>
                        <input type="text" name="name" class="bk-input" placeholder="Full name" required/>
                    </div>
                    <div class="bk-field">
                        <label>Phone <span style="color:var(--red)">*</span></label>
                        <input type="tel" name="phone" class="bk-input" placeholder="Phone number" required/>
                    </div>
                    <div class="bk-field">
                        <label>Email <span style="font-weight:400;text-transform:none">(optional)</span></label>
                        <input type="email" name="email" class="bk-input" placeholder="you@example.com"/>
                    </div>
                    <div class="bk-field">
                        <label>Notes <span style="font-weight:400;text-transform:none">(optional)</span></label>
                        <textarea name="notes" class="bk-input" rows="2" placeholder="Anything we should know?"></textarea>
                    </div>

                    <button type="submit" class="bk-btn">
                        Confirm Booking
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    </button>
                </form>
            </div>
        @endif
        </div>
    </div>

    <div class="bk-trust">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Your details are safe &amp; secure
    </div>
</div>

<script>
let selectedServiceId = null;
let selectedTime = null;

function goToStep(n){
    document.querySelectorAll('.bk-step').forEach(s => s.classList.remove('show'));
    document.getElementById('step' + n).classList.add('show');
    document.querySelectorAll('#bkProgress span').forEach(s => s.classList.toggle('active', Number(s.dataset.step) <= n));
}

function selectService(el){
    document.querySelectorAll('.bk-svc-opt').forEach(o => o.classList.remove('sel'));
    el.classList.add('sel');
    selectedServiceId = el.dataset.id;
    document.getElementById('formServiceId').value = selectedServiceId;
    goToStep(2);
    loadSlots();
}

function loadSlots(){
    const date = document.getElementById('dateInput').value;
    const container = document.getElementById('slotsContainer');
    container.innerHTML = '<div class="bk-empty">Loading...</div>';

    fetch(`{{ route('public.booking.slots', $token) }}?service_id=${selectedServiceId}&date=${date}`)
        .then(r => r.json())
        .then(data => {
            container.innerHTML = '';
            if(!data.slots || !data.slots.length){
                container.innerHTML = '<div class="bk-empty">No slots available on this date.</div>';
                return;
            }
            data.slots.forEach(t => {
                const div = document.createElement('div');
                div.className = 'bk-slot';
                div.textContent = t;
                div.onclick = () => selectSlot(t, div);
                container.appendChild(div);
            });
        })
        .catch(() => { container.innerHTML = '<div class="bk-empty">Could not load slots.</div>'; });
}

function selectSlot(time, el){
    document.querySelectorAll('.bk-slot').forEach(s => s.classList.remove('sel'));
    el.classList.add('sel');
    selectedTime = time;
    document.getElementById('formDate').value = document.getElementById('dateInput').value;
    document.getElementById('formTime').value = time;
    goToStep(3);
}
</script>

</body>
</html>
