<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<title>Support — {{ $tenant->name }}</title>
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

.bk-progress { display:flex; align-items:center; justify-content:center; gap:8px; margin-bottom:22px; }
.bk-progress span { width:8px; height:8px; border-radius:4px; background:var(--border-strong); transition:all .25s var(--ease); }
.bk-progress span.active { width:26px; background:var(--accent); }

.bk-step { display:none; }
.bk-step.show { display:block; animation:bkFadeIn .25s ease; }
@keyframes bkFadeIn { from { opacity:0; transform:translateY(4px); } to { opacity:1; transform:translateY(0); } }

.bk-step-title { font-size:15.5px; font-weight:800; color:var(--text-100); margin-bottom:4px; }
.bk-step-sub { font-size:12.5px; color:var(--text-300); margin-bottom:20px; }

.bk-back { font-size:12.5px; color:var(--text-300); font-weight:600; cursor:pointer; margin-bottom:16px; display:inline-flex; align-items:center; gap:5px; transition:color .15s; }
.bk-back:hover { color:var(--accent); }

.bk-row2 { display:grid; grid-template-columns:1fr 1fr; gap:12px; }

.bk-field { display:flex; flex-direction:column; gap:6px; margin-bottom:14px; }
.bk-field label { font-size:11.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.5px; }
.bk-input, textarea.bk-input {
  width:100%; padding:12px 14px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-md);
  color:var(--text-100); font-family:var(--font); font-size:13.5px; outline:none; transition:border-color .15s, box-shadow .15s, background .15s;
}
.bk-input:focus, textarea.bk-input:focus { border-color:var(--accent); background:var(--bg-surface); box-shadow:0 0 0 4px var(--accent-glow); }
.bk-input::placeholder { color:var(--text-400); }
textarea.bk-input { resize:vertical; min-height:96px; }

select.bk-input {
  appearance:none; -webkit-appearance:none;
  background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%237b84a8' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
  background-repeat:no-repeat; background-position:right 12px center; padding-right:38px; cursor:pointer;
}

.bk-file { position:relative; }
.bk-file input[type=file] { position:absolute; inset:0; opacity:0; cursor:pointer; }
.bk-file-box { display:flex; align-items:center; gap:10px; padding:13px 14px; border:1.5px dashed var(--border-strong); border-radius:var(--r-md); background:var(--bg-input); color:var(--text-300); font-size:12.5px; font-weight:500; transition:all .15s; }
.bk-file-box svg { flex:none; color:var(--text-300); transition:color .15s; }
.bk-file:hover .bk-file-box, .bk-file.has-files .bk-file-box { border-color:var(--accent); background:var(--accent-dim); color:var(--accent); }
.bk-file:hover .bk-file-box svg, .bk-file.has-files .bk-file-box svg { color:var(--accent); }
.bk-file-hint { font-size:11px; color:var(--text-400); margin-top:5px; }

.bk-btn {
  width:100%; padding:14px; border-radius:var(--r-lg); border:none; background:linear-gradient(135deg,var(--accent),var(--accent-hover));
  color:#fff; font-size:14px; font-weight:700; letter-spacing:.2px; cursor:pointer; margin-top:8px;
  box-shadow:0 10px 24px -4px var(--accent-glow); transition:transform .15s, box-shadow .15s;
  display:flex; align-items:center; justify-content:center; gap:8px;
}
.bk-btn:hover { transform:translateY(-1px); box-shadow:0 14px 28px -4px var(--accent-glow); }
.bk-btn:active { transform:translateY(0); }

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
                    <div class="bk-brand">Contact support for</div>
                    <div class="bk-title">{{ $tenant->name }}</div>
                </div>
            </div>
        </div>

        <div class="bk-body">
            <div class="bk-progress" id="bkProgress">
                <span class="active" data-step="1"></span>
                <span data-step="2"></span>
            </div>

            <form method="POST" action="{{ route('public.support.store', $token) }}" enctype="multipart/form-data" id="supportForm">
                @csrf

                {{-- Step 1: the issue --}}
                <div class="bk-step show" id="step1">
                    <div class="bk-step-title">What's the issue?</div>
                    <div class="bk-step-sub">Give us a few details so we can help faster.</div>

                    <div class="bk-field">
                        <label>Subject <span style="color:var(--red)">*</span></label>
                        <input type="text" name="subject" class="bk-input" placeholder="e.g. Billing question" required value="{{ old('subject') }}"/>
                    </div>

                    @if($services->isNotEmpty())
                    <div class="bk-field">
                        <label>Related Service <span style="font-weight:400;text-transform:none">(optional)</span></label>
                        <select name="service_id" class="bk-input">
                            <option value="">— None —</option>
                            @foreach($services as $s)
                            <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="bk-field">
                        <label>Describe the issue <span style="color:var(--red)">*</span></label>
                        <textarea name="description" class="bk-input" rows="5" placeholder="Tell us what's going on..." required>{{ old('description') }}</textarea>
                    </div>

                    <div class="bk-field">
                        <label>Attachments <span style="font-weight:400;text-transform:none">(optional)</span></label>
                        <div class="bk-file" id="fileDrop">
                            <input type="file" name="attachments[]" id="fileInput" multiple/>
                            <div class="bk-file-box">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                                <span id="fileLabel">Click to attach files</span>
                            </div>
                        </div>
                        <div class="bk-file-hint">Max 5 files, 10MB each</div>
                    </div>

                    <button type="button" class="bk-btn" onclick="goNext()">
                        Continue
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </button>
                </div>

                {{-- Step 2: contact info --}}
                <div class="bk-step" id="step2">
                    <span class="bk-back" onclick="goToStep(1)">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                        Back
                    </span>
                    <div class="bk-step-title">How can we reach you?</div>
                    <div class="bk-step-sub">We'll use this to update you on your ticket.</div>

                    <div class="bk-row2">
                        <div class="bk-field">
                            <label>Your Name <span style="color:var(--red)">*</span></label>
                            <input type="text" name="name" class="bk-input" placeholder="Full name" required value="{{ old('name') }}"/>
                        </div>

                        <div class="bk-field">
                            <label>Phone <span style="color:var(--red)">*</span></label>
                            <input type="tel" name="phone" class="bk-input" placeholder="Phone number" required value="{{ old('phone') }}"/>
                        </div>
                    </div>

                    <div class="bk-field">
                        <label>Email <span style="font-weight:400;text-transform:none">(optional)</span></label>
                        <input type="email" name="email" class="bk-input" placeholder="you@example.com" value="{{ old('email') }}"/>
                    </div>

                    <button type="submit" class="bk-btn">
                        Submit Ticket
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="bk-trust">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Your information is kept private &amp; secure
    </div>
</div>

<script>
const fileInput = document.getElementById('fileInput');
const fileLabel = document.getElementById('fileLabel');
const fileDrop = document.getElementById('fileDrop');
fileInput.addEventListener('change', () => {
    const n = fileInput.files.length;
    fileLabel.textContent = n === 0 ? 'Click to attach files' : (n === 1 ? fileInput.files[0].name : n + ' files selected');
    fileDrop.classList.toggle('has-files', n > 0);
});

// Hidden steps still block native form validation in most browsers, so only
// the currently visible step's fields should carry the `required` attribute.
document.querySelectorAll('.bk-step [required]').forEach(el => el.dataset.req = '1');

function syncRequired(n){
    document.querySelectorAll('.bk-step').forEach(step => {
        const active = step.id === 'step' + n;
        step.querySelectorAll('[data-req]').forEach(el => { el.required = active; });
    });
}
syncRequired(1);

function goToStep(n){
    document.querySelectorAll('.bk-step').forEach(s => s.classList.remove('show'));
    document.getElementById('step' + n).classList.add('show');
    document.querySelectorAll('#bkProgress span').forEach(s => s.classList.toggle('active', Number(s.dataset.step) <= n));
    syncRequired(n);
}

function goNext(){
    const fields = document.querySelectorAll('#step1 input[required], #step1 textarea[required], #step1 select[required]');
    for (const el of fields) {
        if (!el.reportValidity()) return;
    }
    goToStep(2);
}
</script>

</body>
</html>
