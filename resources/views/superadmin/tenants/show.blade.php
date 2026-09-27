@extends('layouts.app')
@section('title', $tenant->name . ' — Tenant Detail')

@push('styles')
    @include('partials.panel-ui')
    <style>
        .dsh--sa { --e: var(--red); --ew: var(--red-dim); }

        /* Hero */
        .th-hero {
            display: flex; align-items: center; justify-content: space-between;
            gap: 20px; flex-wrap: wrap;
            background: var(--bg-surface);
            border: 1px solid var(--border-default);
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            padding: 20px 22px;
            margin-bottom: 14px;
        }
        .th-hero-l { display: flex; align-items: center; gap: 16px; min-width: 0; }
        .th-avatar {
            width: 54px; height: 54px; border-radius: 15px; flex-shrink: 0; overflow: hidden;
            background: var(--accent); color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 19px; font-weight: 800;
        }
        .th-avatar img { width: 100%; height: 100%; object-fit: cover; }
        .th-name { font-size: 20px; font-weight: 700; letter-spacing: -0.5px; color: var(--text-100); line-height: 1.15; }
        .th-sub { font-size: 12.5px; color: var(--text-300); margin-top: 2px; }
        .th-badges { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 10px; }
        .th-badge {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 11px; font-weight: 600;
            padding: 3px 10px; border-radius: 20px;
            background: var(--bg-elevated); border: 1px solid var(--border-subtle); color: var(--text-300);
        }
        .th-badge svg { width: 12px; height: 12px; }
        .th-actions { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

        /* Invoice actions inside the Payment History list */
        .inv-actions { display: flex; align-items: center; gap: 6px; flex-shrink: 0; }
        .inv-btn {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 11px; font-weight: 600; font-family: var(--font);
            padding: 5px 9px; border-radius: 8px; cursor: pointer;
            border: 1px solid var(--border-default); background: var(--bg-elevated);
            color: var(--text-200); text-decoration: none; transition: border-color .15s, color .15s;
        }
        .inv-btn:hover { border-color: var(--accent); color: var(--accent); }
        .inv-btn svg { width: 13px; height: 13px; }
        .inv-btn.primary { background: var(--accent-dim); border-color: transparent; color: var(--accent); }
    </style>
@endpush

@section('content')

@php
    $icons = [
        'users'    => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
        'card'     => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z',
        'shield'   => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',
        'cash'     => 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75',
        'cube'     => 'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
        'wrench'   => 'M21.75 6.75a4.5 4.5 0 01-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 11-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 016.336-4.486l-3.276 3.276a3.004 3.004 0 002.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852z',
        'renew'    => 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99',
        'gift'     => 'M21 11.25v8.25a1.5 1.5 0 01-1.5 1.5H5.25a1.5 1.5 0 01-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 109.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1114.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z',
        'calendar' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
        'clock'    => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
        'chat'     => 'M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z',
        'building' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
        'link'     => 'M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244',
        'back'     => 'M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18',
        'wallet'   => 'M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9v3',
    ];

    $initials = strtoupper(substr($tenant->name, 0, 2));
    $sub  = $tenant->subscription;
    $plan = $sub?->plan;

    // Effective seat limit = superadmin override if set, else the plan's.
    $planSeats    = $tenant->planUserSeatLimit();       // what the plan alone allows (0 = none)
    $seatOverride = $tenant->userSeatLimitOverride();   // null = follows plan, -1 = unlimited, >=1 = fixed
    $effSeats     = $tenant->userSeatLimit();           // -1 / 0 = no cap
    $maxUsers     = $effSeats > 0 ? $effSeats : 0;      // 0 → treated as "unlimited" everywhere below
    $userPct  = $maxUsers > 0 ? min(100, round($userCount / $maxUsers * 100)) : 0;
    $fillCls  = $userPct >= 100 ? 'full' : ($userPct >= 80 ? 'warn' : '');

    $lifetimeRevenue = $paymentHistory->reduce(function ($carry, $payment) {
        if (empty($payment->razorpay_payment_id)) return $carry;
        $amount = $payment->original_amount ?? ($payment->billing_cycle === 'yearly'
            ? $payment->plan?->yearly_price
            : $payment->plan?->monthly_price);
        return $carry + max(0, ($amount ?? 0) - ($payment->discount_amount ?? 0));
    }, 0);
    $paidCount = $paymentHistory->filter(fn($p) => !empty($p->razorpay_payment_id))->count();

    $subSubtext = 'No active subscription';
    $subTone = 'off';
    if ($sub) {
        $subTone = match ($sub->status) {
            'active' => 'on',
            'trial'  => 'info',
            default  => 'off',
        };
        if ($sub->status === 'trial' && $sub->trial_ends_at) {
            $subSubtext = 'Trial ends ' . $sub->trial_ends_at->format('d M Y');
        } elseif ($sub->ends_at) {
            $subSubtext = $sub->ends_at->isPast()
                ? 'Expired ' . $sub->ends_at->diffForHumans()
                : 'Renews ' . $sub->ends_at->diffForHumans();
        }
    }

    $statusPill = fn($s) => match ($s) {
        'active'    => 'on',
        'inactive'  => 'warn',
        'suspended' => 'off',
        'trial'     => 'info',
        'expired'   => 'off',
        'cancelled' => 'muted',
        default     => 'muted',
    };

    // Module list is the premium registry in config/modules.php. manufacturing
    // + service keep their dedicated toggle/clear routes; everything else uses
    // the generic toggle-module / clear-module-override pair.
    $dedicatedRoutes = [
        'manufacturing' => ['superadmin.tenants.toggle-manufacturing', 'superadmin.tenants.clear-manufacturing-override'],
        'service'       => ['superadmin.tenants.toggle-service', 'superadmin.tenants.clear-service-override'],
    ];
    $moduleConfigs = [];
    foreach (config('modules') as $modKey => $mod) {
        [$toggleRoute, $clearRoute] = $dedicatedRoutes[$modKey] ?? ['superadmin.tenants.toggle-module', 'superadmin.tenants.clear-module-override'];
        $moduleConfigs[$modKey] = [
            'label'        => $mod['label'],
            'desc'         => $mod['desc'],
            'icon'         => $mod['icon'],
            'toggle_route' => $toggleRoute,
            'clear_route'  => $clearRoute,
            'params'       => isset($dedicatedRoutes[$modKey]) ? [$tenant] : [$tenant, $modKey],
        ];
    }
@endphp

<div class="dsh dsh--sa">

    <a href="{{ route('superadmin.tenants.index') }}" class="dsh-back">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['back'] }}"/></svg>
        All Tenants
    </a>

    @if(session('success'))
        <div class="dalert" style="--a:var(--green);--a-bg:var(--green-dim);margin-bottom:14px">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error') || $errors->any())
        <div class="dalert" style="--a:var(--red);--a-bg:var(--red-dim);margin-bottom:14px">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374l7.028-12.124c.866-1.5 3.032-1.5 3.898 0l7.027 12.124z"/></svg>
            <span>{{ session('error') ?? $errors->first() }}</span>
        </div>
    @endif

    {{-- ── Hero ───────────────────────────────────────────────────── --}}
    <div class="th-hero">
        <div class="th-hero-l">
            <div class="th-avatar">
                @if($tenant->logo)
                    <img src="{{ asset('storage/' . $tenant->logo) }}" alt="{{ $tenant->name }}">
                @else
                    {{ $initials }}
                @endif
            </div>
            <div style="min-width:0">
                <div class="th-name">{{ $tenant->name }}</div>
                <div class="th-sub">{{ $tenant->subdomain }} · {{ $tenant->email }}</div>
                <div class="th-badges">
                    <span class="dpill {{ $statusPill($tenant->status) }}">{{ ucfirst($tenant->status) }}</span>
                    <span class="th-badge">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['card'] }}"/></svg>
                        {{ $plan->name ?? 'No Plan' }}
                    </span>
                    <span class="th-badge">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['calendar'] }}"/></svg>
                        Joined {{ $tenant->created_at?->format('d M Y') ?? '—' }}
                    </span>
                </div>
            </div>
        </div>
        <div class="th-actions">
            <form method="POST" action="{{ route('superadmin.tenants.toggle-status', $tenant) }}" style="display:flex;align-items:center;gap:8px">
                @csrf
                <select name="status" class="dinput" style="width:auto" onchange="this.form.submit()">
                    <option value="active"    {{ $tenant->status === 'active'    ? 'selected' : '' }}>Active</option>
                    <option value="inactive"  {{ $tenant->status === 'inactive'  ? 'selected' : '' }}>Inactive</option>
                    <option value="suspended" {{ $tenant->status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>
                <button type="submit" class="dbtn dbtn-sm">Update</button>
            </form>
            <a href="{{ route('superadmin.tenant-webhooks.index', $tenant) }}" class="dbtn dbtn-sm">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['link'] }}"/></svg>
                Webhooks
            </a>
        </div>
    </div>

    {{-- ── Stats ──────────────────────────────────────────────────── --}}
    <div class="kgrid">
        <div class="kcard" style="--k:#FF7A59;--kw:rgba(255,122,89,.12)">
            <div class="khead">
                <span class="kico"><svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['users'] }}"/></svg></span>
                <span class="klabel">Team Members</span>
            </div>
            <div class="kmid"><span class="knum">{{ $userCount }}{{ $maxUsers > 0 ? ' / ' . $maxUsers : '' }}</span></div>
            <div class="kfoot"><span class="knote">{{ $maxUsers > 0 ? $userPct . '% of ' . ($seatOverride !== null ? 'the seat limit' : 'plan seats') . ' used' : 'Unlimited seats' }}</span></div>
        </div>

        <div class="kcard" style="--k:#7C6FEF;--kw:rgba(124,111,239,.12)">
            <div class="khead">
                <span class="kico"><svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['card'] }}"/></svg></span>
                <span class="klabel">Current Plan</span>
            </div>
            <div class="kmid"><span class="knum knum-sm">{{ $plan->name ?? '—' }}</span></div>
            <div class="kfoot"><span class="knote">
                @if($sub && $plan)
                    {{ ucfirst($sub->billing_cycle) }} · ₹{{ number_format($sub->billing_cycle === 'yearly' ? $plan->yearly_price : $plan->monthly_price) }}
                @else
                    No billing on file
                @endif
            </span></div>
        </div>

        <div class="kcard" style="--k:var(--{{ $subTone === 'on' ? 'green' : ($subTone === 'info' ? 'accent' : 'red') }});--kw:var(--{{ $subTone === 'on' ? 'green' : ($subTone === 'info' ? 'accent' : 'red') }}-dim)">
            <div class="khead">
                <span class="kico"><svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['shield'] }}"/></svg></span>
                <span class="klabel">Subscription</span>
            </div>
            <div class="kmid"><span class="knum knum-sm">{{ $sub ? ucfirst($sub->status) : 'None' }}</span></div>
            <div class="kfoot"><span class="knote">{{ $subSubtext }}</span></div>
        </div>

        <div class="kcard" style="--k:#00A38C;--kw:rgba(0,163,140,.12)">
            <div class="khead">
                <span class="kico"><svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['cash'] }}"/></svg></span>
                <span class="klabel">Lifetime Revenue</span>
            </div>
            <div class="kmid"><span class="knum">₹{{ number_format($lifetimeRevenue) }}</span></div>
            <div class="kfoot"><span class="knote">{{ $paidCount }} paid invoice{{ $paidCount !== 1 ? 's' : '' }}</span></div>
        </div>
    </div>

    {{-- ── Tenant info + current subscription ─────────────────────── --}}
    <div class="dgrid-2">

        <div class="dcard">
            <div class="dcard-h">
                <div class="dcard-ht">
                    <span class="dcard-ico"><svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['building'] }}"/></svg></span>
                    <div class="dcard-t">Tenant Info</div>
                </div>
            </div>
            <div class="dcard-b">
                <div class="dkv">
                    <div class="dkv-row"><span class="dkv-k">Company</span><span class="dkv-v">{{ $tenant->name }}</span></div>
                    <div class="dkv-row"><span class="dkv-k">Email</span><span class="dkv-v">{{ $tenant->email }}</span></div>
                    <div class="dkv-row"><span class="dkv-k">Phone</span><span class="dkv-v">{{ $tenant->phone ?? '—' }}</span></div>
                    <div class="dkv-row"><span class="dkv-k">Subdomain</span><span class="dkv-v mono">{{ $tenant->subdomain }}</span></div>
                    <div class="dkv-row"><span class="dkv-k">Timezone</span><span class="dkv-v">{{ $tenant->timezone }}</span></div>
                    <div class="dkv-row"><span class="dkv-k">Currency</span><span class="dkv-v">{{ $tenant->currency }}</span></div>
                    <div class="dkv-row"><span class="dkv-k">Joined</span><span class="dkv-v">{{ $tenant->created_at?->format('d M Y, h:i A') ?? '—' }}</span></div>
                </div>
            </div>
        </div>

        <div class="dcard">
            <div class="dcard-h">
                <div class="dcard-ht">
                    <span class="dcard-ico" style="--c:var(--purple);--cw:var(--purple-dim)"><svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['card'] }}"/></svg></span>
                    <div class="dcard-t">Current Subscription</div>
                </div>
                @if($sub)
                    <span class="dpill {{ $statusPill($sub->status) }}">{{ ucfirst($sub->status) }}</span>
                @endif
            </div>
            <div class="dcard-b">
                @if($sub && $plan)
                    <div class="dkv">
                        <div class="dkv-row"><span class="dkv-k">Plan</span><span class="dkv-v">{{ $plan->name }}</span></div>
                        <div class="dkv-row"><span class="dkv-k">Billing</span><span class="dkv-v">{{ ucfirst($sub->billing_cycle) }}</span></div>
                        <div class="dkv-row"><span class="dkv-k">Price</span><span class="dkv-v mono">₹{{ number_format($sub->billing_cycle === 'yearly' ? $plan->yearly_price : $plan->monthly_price) }}/{{ $sub->billing_cycle === 'yearly' ? 'yr' : 'mo' }}</span></div>
                        <div class="dkv-row"><span class="dkv-k">Seats</span><span class="dkv-v mono">{{ $userCount }} / {{ $maxUsers > 0 ? $maxUsers : '∞' }}</span></div>
                        @if($sub->started_at)
                        <div class="dkv-row"><span class="dkv-k">Started</span><span class="dkv-v">{{ $sub->started_at->format('d M Y') }}</span></div>
                        @endif
                        @if($sub->ends_at)
                        <div class="dkv-row">
                            <span class="dkv-k">Expires</span>
                            <span class="dkv-v" style="{{ $sub->ends_at->isPast() ? 'color:var(--red)' : '' }}">
                                {{ $sub->ends_at->format('d M Y') }} · {{ $sub->ends_at->isPast() ? 'Expired' : $sub->ends_at->diffForHumans() }}
                            </span>
                        </div>
                        @endif
                        @if($sub->trial_ends_at && $sub->status === 'trial')
                        <div class="dkv-row"><span class="dkv-k">Trial ends</span><span class="dkv-v">{{ $sub->trial_ends_at->format('d M Y') }}</span></div>
                        @endif
                    </div>

                    @if($maxUsers > 0)
                    <div style="margin-top:12px">
                        <div class="dquota-bar"><div class="dquota-fill {{ $fillCls }}" style="width:{{ $userPct }}%"></div></div>
                        <div style="font-size:11px;margin-top:5px;color:{{ $userPct >= 100 ? 'var(--red)' : ($userPct >= 80 ? 'var(--amber)' : 'var(--text-400)') }}">
                            {{ $userPct }}% of {{ $seatOverride !== null ? 'the seat limit' : 'plan seats' }} used{{ $userPct >= 100 ? ' — FULL' : '' }}
                        </div>
                    </div>
                    @endif
                @else
                    <div class="dempty">No active subscription on file.</div>
                @endif
            </div>
        </div>

    </div>

    {{-- ── Plan features ─────────────────────────────────────────── --}}
    @if($plan)
    <div class="dcard" style="margin-top:12px">
        <div class="dcard-h">
            <div class="dcard-ht">
                <span class="dcard-ico" style="--c:var(--accent);--cw:var(--accent-dim)"><svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['shield'] }}"/></svg></span>
                <div>
                    <div class="dcard-t">Plan Features</div>
                    <div class="dcard-s">Everything the {{ $plan->name }} plan unlocks</div>
                </div>
            </div>
        </div>
        <div class="dcard-b">
            <div style="display:flex;flex-wrap:wrap;gap:7px">
                @foreach($plan->features as $feat => $val)
                <span class="fchip {{ is_bool($val) ? ($val ? 'on' : '') : 'num' }}">
                    {{ ucfirst(str_replace('_', ' ', $feat)) }}
                    @if(is_bool($val)) {{ $val ? '✓' : '✗' }}
                    @elseif((int) $val < 0) ∞
                    @else {{ $val }} @endif
                </span>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- ── Manage subscription ───────────────────────────────────── --}}
    <div class="dcard" style="margin-top:12px">
        <div class="dcard-h">
            <div class="dcard-ht">
                <span class="dcard-ico" style="--c:var(--amber);--cw:var(--amber-dim)"><svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['renew'] }}"/></svg></span>
                <div>
                    <div class="dcard-t">Manage Subscription</div>
                    <div class="dcard-s">Change plan, fix term, comp access, or resolve a stuck payment</div>
                </div>
            </div>
        </div>
        <div class="dcard-b">
            <form data-confirm="Update this tenant's subscription?" data-confirm-ok="Update" data-confirm-danger="false" method="POST" action="{{ route('superadmin.tenants.update-subscription', $tenant) }}" style="display:flex;flex-direction:column;gap:13px;max-width:760px">
                @csrf
                <label class="dfield">
                    <span>Plan</span>
                    <select name="plan_id" required class="dinput">
                        @foreach($allPlans as $p)
                            <option value="{{ $p->id }}" {{ $sub && $sub->plan_id == $p->id ? 'selected' : '' }}>
                                {{ $p->name }} — ₹{{ number_format($p->monthly_price) }}/mo
                            </option>
                        @endforeach
                    </select>
                </label>
                <div class="dfield-row">
                    <label class="dfield">
                        <span>Status</span>
                        <select name="status" required class="dinput">
                            @foreach(['trial','active','cancelled','expired'] as $st)
                                <option value="{{ $st }}" {{ $sub && $sub->status === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="dfield">
                        <span>Billing cycle</span>
                        <select name="billing_cycle" required class="dinput">
                            <option value="monthly" {{ !$sub || $sub->billing_cycle === 'monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="yearly"  {{ $sub && $sub->billing_cycle === 'yearly' ? 'selected' : '' }}>Yearly</option>
                        </select>
                    </label>
                </div>
                <div class="dfield-row">
                    <label class="dfield">
                        <span>Expires on (blank = free / no expiry)</span>
                        <input type="date" name="ends_at" value="{{ $sub?->ends_at?->format('Y-m-d') }}" class="dinput">
                    </label>
                    <label class="dfield">
                        <span>Trial ends on (blank if not on trial)</span>
                        <input type="date" name="trial_ends_at" value="{{ $sub?->trial_ends_at?->format('Y-m-d') }}" class="dinput">
                    </label>
                </div>
                <label class="dfield">
                    <span>Note (optional — saved to the audit log)</span>
                    <input type="text" name="note" maxlength="255" placeholder="e.g. Comped Pro for 3 months — partnership deal" class="dinput">
                </label>
                <button type="submit" class="dbtn dbtn-accent" style="align-self:flex-start">
                    Save Subscription
                </button>
            </form>
        </div>
    </div>

    {{-- ── User seats ────────────────────────────────────────────── --}}
    <div class="dcard" style="margin-top:12px">
        <div class="dcard-h">
            <div class="dcard-ht">
                <span class="dcard-ico" style="--c:var(--accent);--cw:var(--accent-dim)"><svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['users'] }}"/></svg></span>
                <div>
                    <div class="dcard-t">User Seats</div>
                    <div class="dcard-s">How many active users this tenant can create — overrides the plan's seat count</div>
                </div>
            </div>
            <span class="dpill {{ $seatOverride !== null ? 'info' : 'muted' }}">
                @if($seatOverride === null) Follows plan
                @elseif($seatOverride === -1) Override · Unlimited
                @else Override · {{ $seatOverride }} seats @endif
            </span>
        </div>
        <div class="dcard-b">
            <div class="mcard-note" style="margin-bottom:14px">
                @if($seatOverride === null)
                    Seats follow the <strong>{{ $plan?->name ?? 'current' }}</strong> plan —
                    <strong>{{ $planSeats > 0 ? $planSeats . ' seat(s)' : 'unlimited' }}</strong>.
                    Currently using <strong>{{ $userCount }}</strong>.
                @elseif($seatOverride === -1)
                    Manually set to <strong>unlimited seats</strong>{{ $planSeats > 0 ? ", overriding the plan's {$planSeats}" : '' }}.
                    Currently using <strong>{{ $userCount }}</strong>.
                @else
                    Manually capped at <strong>{{ $seatOverride }} seat(s)</strong>{{ $planSeats > 0 ? " — plan allows {$planSeats}" : '' }}.
                    Currently using <strong>{{ $userCount }}</strong>.
                    @if($userCount > $seatOverride)
                        <span style="color:var(--amber)">Already above the cap — existing users keep access, but no new ones can be added until usage drops below {{ $seatOverride }}.</span>
                    @endif
                @endif
            </div>

            <form method="POST" action="{{ route('superadmin.tenants.update-seat-limit', $tenant) }}"
                  style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:12px">
                @csrf
                <label class="dfield" style="max-width:180px">
                    <span>Fixed seat limit</span>
                    <input type="number" name="seats" min="1" max="100000" class="dinput"
                           value="{{ $seatOverride && $seatOverride > 0 ? $seatOverride : ($planSeats > 0 ? $planSeats : 5) }}">
                </label>
                <button type="submit" name="mode" value="fixed" class="dbtn dbtn-accent">Set limit</button>
                <button type="submit" name="mode" value="unlimited" class="dbtn dbtn-sm">Set unlimited</button>
                @if($seatOverride !== null)
                    <button type="submit" formaction="{{ route('superadmin.tenants.clear-seat-limit', $tenant) }}" class="dbtn dbtn-sm">Reset to plan</button>
                @endif
            </form>
        </div>
    </div>

    {{-- ── Users ──────────────────────────────────────────────────── --}}
    <div class="dcard" style="margin-top:14px">
        <div class="dcard-h">
            <div class="dcard-ht">
                <span class="dcard-ico"><svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['users'] }}"/></svg></span>
                <div>
                    <div class="dcard-t">Users</div>
                    <div class="dcard-s">{{ $userCount }} user{{ $userCount !== 1 ? 's' : '' }}{{ $maxUsers > 0 ? ' of ' . $maxUsers . ' allowed' : '' }}</div>
                </div>
            </div>
        </div>
        <div style="overflow-x:auto">
            <table class="rtable">
                <thead>
                    <tr><th>Name</th><th>Type</th><th>Role</th><th>Last Login</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr>
                        <td data-label="Name">
                            <span class="rn">{{ $user->name }}</span>
                            <div class="rsub">{{ $user->email }}</div>
                        </td>
                        <td data-label="Type">
                            <span class="dpill {{ $user->user_type === 'tenant_admin' ? 'info' : 'muted' }}">{{ $user->user_type === 'tenant_admin' ? 'Admin' : 'Staff' }}</span>
                        </td>
                        <td data-label="Role" style="font-size:12px;color:var(--text-300)">{{ $user->roles->pluck('name')->implode(', ') ?: '—' }}</td>
                        <td data-label="Last Login" class="rmono">
                            @if($user->last_login_at)
                                {{ $user->last_login_at->format('d M Y') }}
                                <div class="rsub">{{ $user->last_login_at->diffForHumans() }}</div>
                            @else
                                <span style="color:var(--text-400)">Never</span>
                            @endif
                        </td>
                        <td data-label="Status">
                            <span class="dpill {{ $user->is_active ? 'on' : 'warn' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="dempty">No users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Payment history ────────────────────────────────────────── --}}
    <div class="dcard" style="margin-top:14px">
        <div class="dcard-h">
            <div class="dcard-ht">
                <span class="dcard-ico" style="--c:var(--amber);--cw:var(--amber-dim)"><svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['cash'] }}"/></svg></span>
                <div>
                    <div class="dcard-t">Payment History</div>
                    <div class="dcard-s">{{ $paymentHistory->count() }} subscription record{{ $paymentHistory->count() !== 1 ? 's' : '' }}</div>
                </div>
            </div>
        </div>
        <div class="dcard-b tight">
            @forelse($paymentHistory as $payment)
            @php
                $isPaid = !empty($payment->razorpay_payment_id);
                $isInvoiceable = $payment->isInvoiceable();
                $amount = $payment->original_amount ?? ($payment->billing_cycle === 'yearly'
                    ? $payment->plan?->yearly_price
                    : $payment->plan?->monthly_price);
                $finalAmount = $amount - ($payment->discount_amount ?? 0);
            @endphp
            <div class="dlist-row" style="{{ $isPaid ? '--li:var(--green);--liw:var(--green-dim)' : '--li:var(--text-400);--liw:var(--border-subtle)' }}">
                <span class="dlist-ico">
                    @if($isPaid)
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                    @else
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374l7.028-12.124c.866-1.5 3.032-1.5 3.898 0l7.027 12.124z"/></svg>
                    @endif
                </span>
                <div class="dlist-main">
                    <div class="dlist-t">
                        {{ $payment->plan?->name ?? 'Unknown Plan' }} — {{ ucfirst($payment->billing_cycle) }}
                        <span class="dpill {{ $statusPill($payment->status) }}" style="margin-left:6px">{{ ucfirst($payment->status) }}</span>
                    </div>
                    <div class="dlist-s">
                        @if($payment->started_at)
                            {{ $payment->started_at->format('d M Y') }}@if($payment->ends_at) → {{ $payment->ends_at->format('d M Y') }} @endif
                        @elseif($payment->created_at)
                            Created {{ $payment->created_at->format('d M Y') }}
                        @endif
                        @if($payment->razorpay_payment_id) · ID: {{ $payment->razorpay_payment_id }} @endif
                        @if($payment->coupon) · Coupon: {{ $payment->coupon->code }} @endif
                        @if($payment->invoice_number) · Invoice: {{ $payment->invoice_number }} @endif
                    </div>
                </div>
                <div class="dlist-amt">
                    @if($finalAmount)
                        ₹{{ number_format($finalAmount) }}
                        @if($payment->discount_amount > 0)
                            <div style="font-size:10px;font-weight:400;color:var(--green)">-₹{{ number_format($payment->discount_amount) }} off</div>
                        @endif
                    @else
                        <span style="color:var(--text-400);font-size:12px;font-weight:500">Free / Trial</span>
                    @endif
                </div>
                @if($isInvoiceable)
                <div class="inv-actions">
                    <a href="{{ route('superadmin.tenants.invoice-download', [$tenant, $payment]) }}" class="inv-btn primary" title="Download tax invoice PDF">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        Invoice
                    </a>
                    <form action="{{ route('superadmin.tenants.invoice-resend', [$tenant, $payment]) }}" method="POST"
                          data-confirm="Re-send invoice {{ $payment->invoice_number ?? '(new)' }} to {{ $tenant->name }} by email/WhatsApp?" data-confirm-ok="Send" data-confirm-danger="false">
                        @csrf
                        <button type="submit" class="inv-btn" title="Re-send invoice to tenant admins">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
                            Resend
                        </button>
                    </form>
                </div>
                @endif
            </div>
            @empty
            <div class="dempty">No payment records found.</div>
            @endforelse
        </div>
    </div>

    {{-- ── Module access ─────────────────────────────────────────── --}}
    <div class="dcard" style="margin-top:14px">
        <div class="dcard-h">
            <div>
                <div class="dcard-t">Module Access</div>
                <div class="dcard-s">Per-tenant feature overrides, independent of their plan</div>
            </div>
        </div>
        <div class="dcard-b">
            <div class="mgrid">
                @foreach($moduleConfigs as $modKey => $cfg)
                @php
                    $modInPlan   = $tenant->moduleIncludedInPlan($modKey);
                    $modOverride = $tenant->moduleOverride($modKey);
                    $modOn       = $tenant->hasModuleEnabled($modKey);
                @endphp
                <div class="mcard">
                    <div class="mcard-top">
                        <div class="mcard-head">
                            <span class="mcard-ico" style="{{ $modOn ? '--mi:var(--green);--miw:var(--green-dim)' : '' }}">
                                <svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$cfg['icon']] }}"/></svg>
                            </span>
                            <div style="min-width:0">
                                <div class="mcard-name">{{ $cfg['label'] }}</div>
                                <div class="mcard-desc">{{ $cfg['desc'] }}</div>
                            </div>
                        </div>
                        <span class="dpill {{ $modOn ? 'on' : 'off' }}">{{ $modOn ? 'On' : 'Off' }}</span>
                    </div>

                    <div class="mcard-note">
                        @if($modOverride === true)
                            Manually <strong>force-enabled</strong>{{ $modInPlan ? ' (plan already includes it too)' : ", overriding their {$plan?->name} plan" }}.
                        @elseif($modOverride === false)
                            Manually <strong>force-disabled</strong>, overriding their {{ $plan?->name ?? 'current' }} plan.
                        @elseif($modInPlan)
                            Included via the <strong>{{ $plan->name }}</strong> plan.
                        @else
                            Not in the {{ $plan?->name ?? 'current' }} plan, no override set.
                        @endif
                    </div>

                    <div class="mcard-actions">
                        <form method="POST" action="{{ route($cfg['toggle_route'], $cfg['params']) }}">
                            @csrf
                            <input type="hidden" name="enabled" value="1">
                            <button type="submit" class="dbtn dbtn-sm {{ $modOn ? '' : 'dbtn-accent' }}" {{ $modOverride === true ? 'disabled' : '' }}>Force Enable</button>
                        </form>
                        <form method="POST" action="{{ route($cfg['toggle_route'], $cfg['params']) }}">
                            @csrf
                            <input type="hidden" name="enabled" value="0">
                            <button type="submit" class="dbtn dbtn-sm {{ !$modOn ? '' : 'dbtn-danger' }}" {{ $modOverride === false ? 'disabled' : '' }}>Force Disable</button>
                        </form>
                        @if(!is_null($modOverride))
                        <form method="POST" action="{{ route($cfg['clear_route'], $cfg['params']) }}">
                            @csrf
                            <button type="submit" class="dbtn dbtn-sm">Reset</button>
                        </form>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

</div>

@endsection
