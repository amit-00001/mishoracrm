<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<title>Your booking — {{ $appointment->tenant->name ?? 'Booking' }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) ?: '1' }}"/>
<style>
body {
  background:var(--bg-base);
  background-image:radial-gradient(circle at 15% 0%, var(--accent-dim), transparent 40%), radial-gradient(circle at 85% 100%, var(--accent-dim), transparent 40%);
  min-height:100vh; display:flex; align-items:flex-start; justify-content:center; padding:48px 16px;
}
.bk-wrap { width:100%; max-width:480px; margin:0 auto; display:flex; flex-direction:column; gap:14px; }

.bk-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-xl); overflow:hidden; box-shadow:0 30px 60px -20px rgba(20,20,45,.22), 0 8px 24px rgba(20,20,45,.06); }
.bk-hero { position:relative; padding:36px 28px 54px; background:linear-gradient(135deg, var(--accent) 0%, var(--accent-hover) 100%); overflow:hidden; text-align:center; }
.bk-hero.no { background:linear-gradient(135deg, var(--red) 0%, #F1786B 100%); }
.bk-hero::before { content:''; position:absolute; width:220px; height:220px; border-radius:50%; background:rgba(255,255,255,.08); top:-110px; right:-60px; }
.bk-hero::after { content:''; position:absolute; width:160px; height:160px; border-radius:50%; background:rgba(255,255,255,.06); bottom:-100px; left:-40px; }
.bk-icon { position:relative; z-index:1; width:54px; height:54px; margin:0 auto 14px; border-radius:50%; background:rgba(255,255,255,.2); border:1px solid rgba(255,255,255,.4); backdrop-filter:blur(8px); display:flex; align-items:center; justify-content:center; color:#fff; }
.bk-svc { position:relative; z-index:1; font-size:19px; font-weight:800; color:#fff; margin-bottom:5px; letter-spacing:-.3px; }
.bk-when { position:relative; z-index:1; font-size:13px; color:rgba(255,255,255,.85); }

.bk-body { position:relative; margin-top:-28px; background:var(--bg-surface); border-radius:22px 22px 0 0; padding:26px 26px 28px; text-align:center; }

.bk-flash { padding:12px 16px; border-radius:var(--r-md); font-size:13.5px; font-weight:500; }
.bk-flash.success { background:var(--green-dim); color:var(--green); border:1px solid var(--green); }
.bk-flash.error   { background:var(--red-dim); color:var(--red); border:1px solid var(--red); }
.bk-flash.info    { background:var(--accent-dim); color:var(--accent); border:1px solid var(--accent); }

.bk-badge { display:inline-flex; padding:6px 16px; border-radius:20px; font-size:12px; font-weight:700; letter-spacing:.2px; margin-bottom:16px; text-transform:uppercase; }
.bk-badge.booked, .bk-badge.confirmed { background:var(--green-dim); color:var(--green); }
.bk-badge.in_progress { background:var(--accent-dim); color:var(--accent); }
.bk-badge.cancelled, .bk-badge.no_show { background:var(--red-dim); color:var(--red); }
.bk-badge.completed { background:var(--accent-dim); color:var(--accent); }

.bk-tech { font-size:13px; color:var(--text-300); margin-bottom:6px; }

.bk-btn { padding:12px 24px; border-radius:var(--r-lg); border:1.5px solid var(--red); background:transparent; color:var(--red); font-size:13.5px; font-weight:700; cursor:pointer; transition:all .15s; margin-top:6px; }
.bk-btn:hover { background:var(--red); color:#fff; box-shadow:0 8px 20px -4px var(--red-dim); }

.bk-section { text-align:left; border-top:1px solid var(--border-subtle); margin-top:20px; padding-top:20px; }
.bk-label { font-size:11px; font-weight:700; color:var(--text-300); text-transform:uppercase; letter-spacing:.6px; margin-bottom:10px; }
.bk-photo-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
.bk-photo-grid img { width:100%; height:80px; object-fit:cover; border-radius:var(--r-md); border:1px solid var(--border-default); }
.bk-field { display:flex; flex-direction:column; gap:6px; margin-bottom:14px; text-align:left; }
.bk-field label { font-size:11.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.5px; }
.bk-input { width:100%; padding:12px 14px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-md); color:var(--text-100); font-family:var(--font); font-size:13.5px; outline:none; transition:border-color .15s, box-shadow .15s; }
.bk-input:focus { border-color:var(--accent); box-shadow:0 0 0 4px var(--accent-glow); }
.bk-sig-wrap { border:1.5px dashed var(--border-strong); border-radius:var(--r-md); background:#fff; }
.bk-sig-canvas { width:100%; height:140px; display:block; touch-action:none; cursor:crosshair; border-radius:var(--r-md); }
.bk-sig-actions { display:flex; justify-content:flex-end; padding:6px; }
.bk-btn-primary { width:100%; padding:14px; border-radius:var(--r-lg); border:none; background:linear-gradient(135deg,var(--accent),var(--accent-hover)); color:#fff; font-size:14px; font-weight:700; letter-spacing:.2px; cursor:pointer; box-shadow:0 10px 24px -4px var(--accent-glow); transition:transform .15s, box-shadow .15s; }
.bk-btn-primary:hover { transform:translateY(-1px); box-shadow:0 14px 28px -4px var(--accent-glow); }
.bk-btn-ghost { padding:7px 14px; border-radius:var(--r-sm); border:1.5px solid var(--border-default); background:transparent; color:var(--text-300); font-size:12.5px; font-weight:600; cursor:pointer; transition:all .15s; }
.bk-btn-ghost:hover { border-color:var(--border-strong); color:var(--text-100); }

.bk-trust { text-align:center; font-size:11.5px; color:var(--text-300); display:flex; align-items:center; justify-content:center; gap:6px; }
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
        <div class="bk-hero {{ $appointment->status === 'cancelled' ? 'no' : '' }}">
            <div class="bk-icon">
                @if($appointment->status === 'cancelled')
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                @else
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                @endif
            </div>
            <div class="bk-svc">{{ $appointment->service?->name ?? 'Appointment' }}</div>
            <div class="bk-when">{{ $appointment->starts_at->format('d M Y, h:i A') }} — with {{ $appointment->tenant->name ?? '' }}</div>
        </div>

        <div class="bk-body">
            <span class="bk-badge {{ $appointment->status }}">{{ \App\Models\Appointment::statuses()[$appointment->status] ?? ucfirst($appointment->status) }}</span>

            @if($appointment->assignedTo)
            <div class="bk-tech">Technician: <strong style="color:var(--text-100)">{{ $appointment->assignedTo->name }}</strong></div>
            @endif

            @if(in_array($appointment->status, ['booked', 'confirmed']))
            <form method="POST" action="{{ route('public.booking.cancel', $appointment->public_token) }}"
                  onsubmit="return confirm('Cancel this booking?')">
                @csrf
                <button type="submit" class="bk-btn">Cancel Booking</button>
            </form>
            @endif

            @if($appointment->attachments->isNotEmpty())
            <div class="bk-section">
                <div class="bk-label">Job Photos</div>
                <div class="bk-photo-grid">
                    @foreach($appointment->attachments as $att)
                    <a href="{{ $att->url }}" target="_blank"><img src="{{ $att->url }}" alt="{{ $att->original_name }}"/></a>
                    @endforeach
                </div>
            </div>
            @endif

            @if($appointment->status === 'completed')
            <div class="bk-section">
                <div class="bk-label">Sign-off</div>
                @if($appointment->customer_signed_at)
                    <div style="font-size:13.5px;color:var(--text-100)">Signed by <strong>{{ $appointment->customer_signed_name }}</strong> on {{ $appointment->customer_signed_at->format('d M Y, h:i A') }}. Thank you!</div>
                @else
                    <div style="font-size:13px;color:var(--text-300);margin-bottom:12px">Please review the completed work and sign below to approve.</div>
                    <form method="POST" action="{{ route('public.booking.sign', $appointment->public_token) }}" id="signForm">
                        @csrf
                        <div class="bk-field">
                            <label>Your Full Name <span style="color:var(--red)">*</span></label>
                            <input type="text" name="signed_name" class="bk-input" required maxlength="150" placeholder="Type your full name to sign"/>
                        </div>
                        <div class="bk-field">
                            <label>Signature <span style="color:var(--red)">*</span></label>
                            <div class="bk-sig-wrap">
                                <canvas class="bk-sig-canvas" id="sigCanvas"></canvas>
                                <div class="bk-sig-actions">
                                    <button type="button" class="bk-btn-ghost" onclick="clearSignature()">Clear</button>
                                </div>
                            </div>
                            <input type="hidden" name="signature_data" id="signatureData"/>
                        </div>
                        <button type="submit" class="bk-btn-primary">Approve &amp; Sign</button>
                    </form>
                @endif
            </div>
            @endif
        </div>
    </div>

    <div class="bk-trust">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Secure booking management
    </div>
</div>

@if($appointment->status === 'completed' && !$appointment->customer_signed_at)
<script>
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

function clearSignature(){
    ctx.clearRect(0, 0, canvas.width, canvas.height);
}

document.getElementById('signForm').addEventListener('submit', function(e){
    document.getElementById('signatureData').value = canvas.toDataURL('image/png');
});
</script>
@endif

</body>
</html>
