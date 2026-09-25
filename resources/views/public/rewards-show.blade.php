<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<title>My Rewards — {{ $tenant->name }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) ?: '1' }}"/>
<style>
body { background:var(--bg-base); background-image:radial-gradient(circle at 15% 0%, var(--accent-dim), transparent 40%), radial-gradient(circle at 85% 100%, var(--accent-dim), transparent 40%); min-height:100vh; display:flex; align-items:flex-start; justify-content:center; padding:48px 16px; }
.rw-wrap { width:100%; max-width:440px; display:flex; flex-direction:column; gap:14px; }
.rw-flash { padding:12px 16px; border-radius:12px; font-size:13px; font-weight:500; }
.rw-flash.success { background:var(--green-dim); color:var(--green); border:1px solid var(--green); }
.rw-flash.error   { background:var(--red-dim); color:var(--red); border:1px solid var(--red); }
.rw-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:20px; overflow:hidden; box-shadow:0 30px 60px -20px rgba(20,20,45,.22); }
.rw-hero { padding:28px 26px; background:linear-gradient(135deg,var(--accent),var(--accent-hover)); color:#fff; display:flex; align-items:center; gap:14px; }
.rw-logo { width:48px; height:48px; flex:none; border-radius:14px; background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.35); display:flex; align-items:center; justify-content:center; font-size:19px; font-weight:800; }
.rw-brand { font-size:12px; color:rgba(255,255,255,.82); }
.rw-title { font-size:19px; font-weight:800; }
.rw-body { padding:26px; }
.rw-step-title { font-size:15px; font-weight:800; color:var(--text-100); margin-bottom:4px; }
.rw-step-sub { font-size:12.5px; color:var(--text-300); margin-bottom:18px; }
.rw-field { display:flex; flex-direction:column; gap:6px; margin-bottom:14px; }
.rw-field label { font-size:11px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.5px; }
.rw-input { width:100%; padding:12px 14px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:12px; color:var(--text-100); font-family:var(--font); font-size:14px; outline:none; }
.rw-input:focus { border-color:var(--accent); box-shadow:0 0 0 4px var(--accent-glow); }
.rw-input.otp { letter-spacing:8px; text-align:center; font-size:20px; font-weight:700; font-family:var(--mono,monospace); }
.rw-btn { width:100%; padding:13px; border-radius:12px; border:none; background:linear-gradient(135deg,var(--accent),var(--accent-hover)); color:#fff; font-size:14px; font-weight:700; cursor:pointer; margin-top:6px; }
.rw-btn:hover { transform:translateY(-1px); }
.rw-foot { text-align:center; font-size:11px; color:var(--text-400); margin-top:12px; }
</style>
</head>
<body>
<div class="rw-wrap">
    @if(($notice ?? null))
    <div class="rw-flash success">{{ $notice }}</div>
    @endif
    @foreach(['success','error'] as $type)
    @if(session($type))
    <div class="rw-flash {{ $type }}">{{ session($type) }}</div>
    @endif
    @endforeach

    <div class="rw-card">
        <div class="rw-hero">
            <div class="rw-logo">{{ strtoupper(substr($tenant->name, 0, 1)) }}</div>
            <div>
                <div class="rw-brand">Loyalty rewards</div>
                <div class="rw-title">{{ $tenant->name }}</div>
            </div>
        </div>

        <div class="rw-body">
            @if($stage === 'identify')
                <div class="rw-step-title">Check your points</div>
                <div class="rw-step-sub">Enter the phone number or email you use here. We'll send you a one-time code to confirm it's you.</div>
                <form method="POST" action="{{ route('public.rewards.request-otp', $token) }}">
                    @csrf
                    <div class="rw-field">
                        <label for="identifier">Phone or email</label>
                        <input type="text" name="identifier" id="identifier" class="rw-input" value="{{ old('identifier') }}" required autofocus/>
                        @error('identifier')<span style="color:var(--red);font-size:12px">{{ $message }}</span>@enderror
                    </div>
                    <button type="submit" class="rw-btn">Send code</button>
                </form>
            @else
                <div class="rw-step-title">Enter the code</div>
                <div class="rw-step-sub">We sent a 6-digit code to <strong>{{ $identifier }}</strong>. It expires in 10 minutes.</div>
                <form method="POST" action="{{ route('public.rewards.verify', $token) }}">
                    @csrf
                    <input type="hidden" name="otp_id" value="{{ $otpId }}"/>
                    <div class="rw-field">
                        <label for="code">6-digit code</label>
                        <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="6" name="code" id="code" class="rw-input otp" required autofocus/>
                        @error('code')<span style="color:var(--red);font-size:12px">{{ $message }}</span>@enderror
                    </div>
                    <button type="submit" class="rw-btn">Verify</button>
                </form>
                <div class="rw-foot"><a href="{{ route('public.rewards.show', $token) }}" style="color:var(--accent);text-decoration:none">Use a different phone / email</a></div>
            @endif
        </div>
    </div>

    @if($tenant->hasModuleEnabled('customer_portal'))
    <div class="rw-foot"><a href="{{ route('portal.login') }}" style="color:var(--accent);text-decoration:none">Log in to see all your rewards in one place →</a></div>
    @endif

    <div class="rw-foot">Powered by {{ config('app.name') }}</div>
</div>
</body>
</html>
