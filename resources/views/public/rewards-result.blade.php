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
.rw-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:20px; overflow:hidden; box-shadow:0 30px 60px -20px rgba(20,20,45,.22); }
.rw-hero { padding:26px; background:linear-gradient(135deg,var(--accent),var(--accent-hover)); color:#fff; }
.rw-brand { font-size:12px; color:rgba(255,255,255,.82); }
.rw-title { font-size:19px; font-weight:800; }
.rw-hello { font-size:13px; margin-top:8px; color:rgba(255,255,255,.9); }
.rw-points { padding:28px 26px; text-align:center; border-bottom:1px solid var(--border-subtle); }
.rw-points .n { font-size:44px; font-weight:800; color:var(--text-100); line-height:1; }
.rw-points .l { font-size:12px; color:var(--text-400); text-transform:uppercase; letter-spacing:.5px; margin-top:6px; }
.rw-tier { display:inline-block; margin-top:12px; padding:4px 14px; border-radius:20px; font-size:12px; font-weight:700; }
.rw-tier.bronze { background:var(--amber-dim); color:var(--amber); }
.rw-tier.silver { background:var(--accent-dim); color:var(--accent); }
.rw-tier.gold   { background:var(--green-dim); color:var(--green); }
.rw-value { padding:16px 26px; background:var(--green-dim); color:var(--green); font-size:13.5px; font-weight:600; text-align:center; }
.rw-list { padding:18px 26px 24px; }
.rw-list h4 { font-size:11px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.5px; margin:0 0 10px; }
.rw-tx { display:flex; justify-content:space-between; gap:8px; padding:8px 0; border-bottom:1px solid var(--border-subtle); font-size:13px; }
.rw-tx:last-child { border-bottom:none; }
.rw-tx .d { color:var(--text-300); }
.rw-tx .p.pos { color:var(--green); font-weight:700; }
.rw-tx .p.neg { color:var(--red); font-weight:700; }
.rw-foot { text-align:center; font-size:11px; color:var(--text-400); }
.rw-logout { background:none; border:none; color:var(--accent); font-size:12px; cursor:pointer; text-decoration:underline; }
</style>
</head>
<body>
<div class="rw-wrap">
    <div class="rw-card">
        <div class="rw-hero">
            <div class="rw-brand">Loyalty rewards</div>
            <div class="rw-title">{{ $tenant->name }}</div>
            <div class="rw-hello">Hi {{ $snapshot['name'] }} 👋</div>
        </div>

        <div class="rw-points">
            <div class="n">{{ number_format($snapshot['points']) }}</div>
            <div class="l">points available</div>
            @if($snapshot['tier'])
            <div><span class="rw-tier {{ $snapshot['tier'] }}">{{ $snapshot['tier_label'] }} member</span></div>
            @endif
        </div>

        @if($snapshot['redeemable_value'] > 0)
        <div class="rw-value">Worth up to ₹{{ number_format($snapshot['redeemable_value'], 0) }} off your next bill</div>
        @endif

        @if(count($snapshot['recent']))
        <div class="rw-list">
            <h4>Recent activity</h4>
            @foreach($snapshot['recent'] as $tx)
            <div class="rw-tx">
                <span class="d">{{ $tx['description'] ?: ucfirst($tx['type']) }}<br><span style="font-size:11px;color:var(--text-500)">{{ \Illuminate\Support\Carbon::parse($tx['date'])->format('d M Y') }}</span></span>
                <span class="p {{ $tx['points'] >= 0 ? 'pos' : 'neg' }}">{{ $tx['points'] >= 0 ? '+' : '' }}{{ number_format($tx['points']) }}</span>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <div class="rw-foot">
        <form method="POST" action="{{ route('public.rewards.logout', $token) }}" style="display:inline">
            @csrf
            <button type="submit" class="rw-logout">Done</button>
        </form>
    </div>
</div>
</body>
</html>
