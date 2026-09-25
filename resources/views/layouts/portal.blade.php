<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<meta name="theme-color" content="#6378ff"/>
<title>@yield('title', 'My Wallet') — {{ config('app.name') }}</title>
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}"/>
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}"/>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) ?: '1' }}"/>
<style>
body { background:var(--bg-base); background-image:radial-gradient(circle at 15% 0%, var(--accent-dim), transparent 40%), radial-gradient(circle at 85% 100%, var(--accent-dim), transparent 40%); min-height:100vh; display:flex; align-items:flex-start; justify-content:center; padding:32px 16px; }
.pt-wrap { width:100%; max-width:440px; display:flex; flex-direction:column; gap:14px; }
.pt-flash { padding:12px 16px; border-radius:12px; font-size:13px; font-weight:500; }
.pt-flash.success { background:var(--green-dim); color:var(--green); border:1px solid var(--green); }
.pt-flash.error   { background:var(--red-dim); color:var(--red); border:1px solid var(--red); }
.pt-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:20px; overflow:hidden; box-shadow:0 30px 60px -20px rgba(20,20,45,.22); }
.pt-hero { padding:24px 26px; background:linear-gradient(135deg,var(--accent),var(--accent-hover)); color:#fff; }
.pt-brand { font-size:12px; color:rgba(255,255,255,.82); }
.pt-title { font-size:19px; font-weight:800; }
.pt-body { padding:26px; }
.pt-step-title { font-size:15px; font-weight:800; color:var(--text-100); margin-bottom:4px; }
.pt-step-sub { font-size:12.5px; color:var(--text-300); margin-bottom:18px; }
.pt-field { display:flex; flex-direction:column; gap:6px; margin-bottom:14px; }
.pt-field label { font-size:11px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.5px; }
.pt-input { width:100%; padding:12px 14px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:12px; color:var(--text-100); font-family:var(--font); font-size:14px; outline:none; }
.pt-input:focus { border-color:var(--accent); box-shadow:0 0 0 4px var(--accent-glow); }
.pt-input.otp { letter-spacing:8px; text-align:center; font-size:20px; font-weight:700; font-family:var(--mono,monospace); }
.pt-btn { width:100%; padding:13px; border-radius:12px; border:none; background:linear-gradient(135deg,var(--accent),var(--accent-hover)); color:#fff; font-size:14px; font-weight:700; cursor:pointer; margin-top:6px; }
.pt-btn:hover { transform:translateY(-1px); }
.pt-btn.ghost { background:transparent; border:1.5px solid var(--border-default); color:var(--text-200); }
.pt-err { color:var(--red); font-size:12px; }
.pt-divider { display:flex; align-items:center; gap:10px; color:var(--text-400); font-size:11px; margin:18px 0; }
.pt-divider::before, .pt-divider::after { content:''; flex:1; height:1px; background:var(--border-default); }
.pt-foot { text-align:center; font-size:11px; color:var(--text-400); margin-top:12px; }
.pt-foot a { color:var(--accent); text-decoration:none; }
@stack('styles')
</style>
@stack('head')
</head>
<body>
<div class="pt-wrap">
    @foreach(['success','error'] as $type)
    @if(session($type))
    <div class="pt-flash {{ $type }}">{{ session($type) }}</div>
    @endif
    @endforeach

    @yield('content')

    <div class="pt-foot">Powered by {{ config('app.name') }}</div>
</div>
@stack('scripts')
</body>
</html>
