<!DOCTYPE html>
<html lang="en" data-theme="{{ auth()->user()?->theme ?? 'dark' }}">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Choose a plan to continue — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) ?: '1' }}"/>
    <script>
        try {
            var t = localStorage.getItem('crm_theme');
            if (t === 'light' || t === 'dark') document.documentElement.dataset.theme = t;
        } catch (e) {}
    </script>
    <style>
        body { margin: 0; min-height: 100vh; background: var(--bg-app); color: var(--text-200); font-family: 'Outfit', sans-serif; }
        .exp-top {
            display: flex; align-items: center; justify-content: space-between;
            max-width: 1040px; margin: 0 auto; padding: 22px 24px 0;
        }
        .exp-signout {
            display: inline-flex; align-items: center; gap: 6px;
            background: transparent; border: 1px solid var(--border-subtle); border-radius: var(--r-md);
            padding: 7px 14px; font: 600 13px 'Outfit', sans-serif; color: var(--text-300); cursor: pointer;
        }
        .exp-signout:hover { color: var(--text-100); border-color: var(--text-400); }
        .exp-main { max-width: 1040px; margin: 0 auto; padding: 0 24px 64px; }
        .exp-hero { text-align: center; padding: 40px 0 8px; }
        .exp-pill {
            display: inline-flex; align-items: center; gap: 7px;
            background: rgba(239,68,68,.12); color: #ef4444;
            font-size: 12px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
            padding: 5px 12px; border-radius: 100px; margin-bottom: 16px;
        }
        .exp-hero h1 { font-size: 32px; font-weight: 800; color: var(--text-100); margin: 0 0 10px; }
        .exp-hero p { font-size: 15px; color: var(--text-400); max-width: 560px; margin: 0 auto; line-height: 1.6; }
        .exp-summary {
            display: flex; flex-wrap: wrap; justify-content: center; gap: 10px 28px;
            max-width: 620px; margin: 26px auto 34px; padding: 14px 20px;
            background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--r-lg);
            font-size: 13px; color: var(--text-400);
        }
        .exp-summary strong { color: var(--text-100); font-weight: 700; }
        .exp-note {
            max-width: 560px; margin: 24px auto 0; padding: 18px 22px; text-align: center;
            background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: var(--r-lg);
            font-size: 14px; color: var(--text-300); line-height: 1.6;
        }
        .exp-foot { text-align: center; margin-top: 30px; font-size: 13px; color: var(--text-400); }
        .exp-foot a { color: var(--accent); font-weight: 600; text-decoration: none; }
        .plans-wrap { padding-top: 0; }
    </style>
</head>
<body>

<div class="exp-top">
    @include('components.brand-logo', ['h' => 28])
    <form action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="exp-signout">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
            </svg>
            Sign out
        </button>
    </form>
</div>

<div class="exp-main">

    @php
        $wasTrial = $currentSub && $currentSub->status === 'trial';
        $endedAt  = $currentSub ? ($currentSub->trial_ends_at ?? $currentSub->ends_at) : null;
        $isOwner  = auth()->user()?->user_type === 'tenant_admin';
    @endphp

    <div class="exp-hero">
        <span class="exp-pill">
            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
            {{ $wasTrial ? 'Free trial ended' : 'Subscription expired' }}
        </span>
        <h1>Choose a plan to continue</h1>
        <p>
            Your workspace is on hold. All your leads, contacts, deals and invoices are safe —
            pick a plan below and everything is back exactly as you left it.
        </p>
    </div>

    @if($currentSub)
    <div class="exp-summary">
        <span>Last plan: <strong>{{ $currentSub->plan?->name ?? '—' }}</strong></span>
        @if($endedAt)
        <span>{{ $wasTrial ? 'Trial ended' : 'Expired on' }}: <strong>{{ $endedAt->format('d M Y') }}</strong></span>
        @endif
    </div>
    @endif

    @if($isOwner)
        @include('tenant.subscription._plan-grid')

        <div class="exp-foot">
            Need a custom setup or help choosing?
            <a href="{{ route('contact-sales') }}">Talk to our team</a>
        </div>
    @else
        <div class="exp-note">
            Only the workspace owner can purchase or renew the plan.
            Please ask your owner to renew the subscription — you'll get access back as soon as it's done.
        </div>
    @endif

</div>
</body>
</html>
