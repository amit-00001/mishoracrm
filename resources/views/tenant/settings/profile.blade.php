@extends('layouts.app')
@section('title', 'My Profile')

@push('styles')
<style>
.profile-layout { display:grid; grid-template-columns:320px 1fr; gap:16px; align-items:start; }
@media(max-width:1024px) { .profile-layout { grid-template-columns:1fr; } }

/* ── Profile card ────────────────────────────────────────────────── */
.profile-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.profile-cover { height:90px; background:linear-gradient(135deg,var(--accent) 0%,var(--accent-hover) 100%); }
.profile-body  { padding:0 20px 20px; }

.profile-av-wrap { margin-top:-38px; margin-bottom:14px; }
.profile-av {
    width:76px; height:76px; border-radius:50%;
    border:4px solid var(--bg-surface);
    display:flex; align-items:center; justify-content:center;
    font-size:26px; font-weight:800;
    object-fit:cover;
}
.profile-name  { font-size:18px; font-weight:800; color:var(--text-100); margin-bottom:3px; }
.profile-role  { font-size:13px; color:var(--text-300); margin-bottom:12px; }
.profile-tags  { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:16px; }
.profile-badge { font-size:11.5px; font-weight:600; padding:3px 10px; border-radius:20px; }

.profile-info-row {
    display:flex; align-items:center; gap:12px;
    padding:10px 0; border-bottom:1px solid var(--border-subtle);
    font-size:13px; color:var(--text-200);
}
.profile-info-row:last-child { border-bottom:none; }
.profile-info-icon { width:32px; height:32px; border-radius:var(--r-sm); background:var(--bg-elevated); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.profile-info-icon svg { width:15px; height:15px; color:var(--text-300); }
.profile-info-label { font-size:11px; color:var(--text-400); font-weight:600; margin-bottom:2px; }
.profile-info-val   { font-size:13.5px; color:var(--text-100); font-weight:500; }

/* ── Stats ───────────────────────────────────────────────────────── */
.stat-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:16px; }
@media(max-width:640px) { .stat-grid { grid-template-columns:repeat(2,1fr); } }
.stat-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-md); padding:16px; text-align:center; }
.stat-num { font-size:22px; font-weight:800; color:var(--text-100); font-family:var(--mono); margin-bottom:4px; }
.stat-lbl { font-size:11.5px; color:var(--text-300); }

/* ── Activity ────────────────────────────────────────────────────── */
.detail-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; margin-bottom:16px; }
.detail-head { padding:14px 20px; border-bottom:1px solid var(--border-subtle); font-size:13.5px; font-weight:700; color:var(--text-100); display:flex; align-items:center; justify-content:space-between; }

/* ── Permission list ─────────────────────────────────────────────── */
.perm-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; padding:16px 20px; }
.perm-item { display:flex; align-items:center; gap:8px; font-size:13px; }
.perm-dot  { width:7px; height:7px; border-radius:50%; flex-shrink:0; }

/* ── API Keys section ────────────────────────────────────────────── */
.ak-gen-row { display:flex; gap:8px; align-items:flex-end; padding:16px 20px; border-bottom:1px solid var(--border-subtle); }
.ak-gen-row .ak-input {
    flex:1; padding:9px 12px;
    background:var(--bg-input); border:1.5px solid var(--border-default);
    border-radius:var(--r-sm); color:var(--text-100);
    font-family:var(--font); font-size:13.5px; outline:none;
    transition:border-color .15s;
}
.ak-gen-row .ak-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
.ak-gen-row .ak-input::placeholder { color:var(--text-400); }
.ak-btn-gen {
    display:inline-flex; align-items:center; gap:5px;
    padding:9px 16px; border-radius:var(--r-sm);
    background:var(--accent); color:#fff; border:none;
    font-size:13px; font-weight:600; cursor:pointer;
    font-family:var(--font); white-space:nowrap;
    transition:opacity .15s;
}
.ak-btn-gen:hover { opacity:.88; }
.ak-btn-gen svg { width:14px; height:14px; }

.ak-row {
    display:flex; align-items:center; gap:10px;
    padding:12px 20px; border-bottom:1px solid var(--border-subtle);
}
.ak-row:last-child { border-bottom:none; }
.ak-info { flex:1; min-width:0; }
.ak-name { font-size:13px; font-weight:600; color:var(--text-100); margin-bottom:2px; }
.ak-meta { font-size:11.5px; color:var(--text-400); }
.ak-key-wrap { display:flex; align-items:center; gap:6px; }
.ak-key-val {
    font-family:monospace; font-size:11.5px;
    background:var(--bg-elevated); border:1px solid var(--border-subtle);
    border-radius:var(--r-sm); padding:4px 8px;
    color:var(--text-100); max-width:200px;
    overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
}
.ak-icon-btn {
    background:none; border:1px solid var(--border-default);
    border-radius:var(--r-sm); padding:4px 8px;
    cursor:pointer; color:var(--text-300);
    display:inline-flex; align-items:center; gap:4px;
    font-size:11.5px; font-weight:500; font-family:var(--font);
    transition:all .15s; white-space:nowrap;
}
.ak-icon-btn svg { width:12px; height:12px; }
.ak-icon-btn:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-dim); }
.ak-icon-btn.danger:hover { border-color:var(--red); color:var(--red); background:var(--red-dim); }
.ak-badge-active   { font-size:10.5px; font-weight:700; padding:2px 7px; border-radius:20px; background:var(--green-dim); color:var(--green); }
.ak-badge-inactive { font-size:10.5px; font-weight:700; padding:2px 7px; border-radius:20px; background:var(--bg-elevated); color:var(--text-400); border:1px solid var(--border-default); }
.ak-empty { text-align:center; padding:24px; color:var(--text-400); font-size:13px; }

/* Alert */
.flash-alert {
    display:flex; align-items:center; gap:8px;
    padding:10px 14px; border-radius:var(--r-sm);
    font-size:13px; font-weight:500; margin-bottom:14px;
}
.flash-success { background:var(--green-dim); color:var(--green); border:1px solid rgba(45,212,160,.2); }
.flash-error   { background:var(--red-dim);   color:var(--red);   border:1px solid rgba(255,82,87,.2); }
.flash-alert svg { width:14px; height:14px; flex-shrink:0; }
</style>
@endpush

@section('content')

@php
    $user         = auth()->user()->load(['roles','tenant','staff.department']);
    $userRole     = $user->roles->first()?->name ?? 'staff';
    $staff        = $user->staff;
    $roleConfig   = config('staff.roles.'.$userRole, ['label'=>ucfirst($userRole),'color'=>'accent','bg'=>'accent-dim']);
    $typeConfig   = config('staff.employment_types.'.$staff?->employment_type, ['label'=>'Full Time','color'=>'accent','bg'=>'accent-dim']);

    $avatarColors = config('staff.avatar_colors');
    $avColor = $avatarColors[abs(crc32($user->name ?? '')) % count($avatarColors)];
    $avBg = $avColor['bg'];
    $avTx = $avColor['text'];

    // Stats
    $leadsCount    = \App\Models\Lead::where('created_by', $user->id)->count();
    $dealsCount    = \App\Models\Deal::where('assigned_to', $user->id)->count();
    $tasksCount    = \App\Models\Task::where('assigned_to', $user->id)->count();
    $followupCount = \App\Models\Followup::where('created_by', $user->id)->count();

    // Permissions map
    $permMap = [
        'Leads'      => in_array($userRole, ['tenant_admin','manager','staff']),
        'Contacts'   => in_array($userRole, ['tenant_admin','manager','staff']),
        'Deals'      => in_array($userRole, ['tenant_admin','manager']),
        'Quotations' => in_array($userRole, ['tenant_admin','manager']),
        'Invoices'   => in_array($userRole, ['tenant_admin']),
        'Staff'      => in_array($userRole, ['tenant_admin']),
        'Reports'    => in_array($userRole, ['tenant_admin','manager']),
        'Settings'   => in_array($userRole, ['tenant_admin']),
        'Tasks'      => in_array($userRole, ['tenant_admin','manager','staff']),
        'Follow-ups' => in_array($userRole, ['tenant_admin','manager','staff']),
    ];
@endphp

<div class="page-head">
    <div>
        <div class="page-title">My Profile</div>
        <div class="page-sub">Your account overview</div>
    </div>
    <a href="{{ route('tenant.settings.index') }}" class="btn btn-primary">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        Edit Profile
    </a>
</div>

<div class="profile-layout">

    {{-- ── Left: Profile card ──────────────────────────────────── --}}
    <div>
        <div class="profile-card">
            <div class="profile-cover"></div>
            <div class="profile-body">
                {{-- Avatar --}}
                <div class="profile-av-wrap">
                    @if($user->avatar)
                    <img src="{{ Storage::url($user->avatar) }}"
                         alt="{{ $user->name }}" class="profile-av"/>
                    @else
                    <div class="profile-av" style="background:{{ $avBg }};color:{{ $avTx }}">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    @endif
                </div>

                <div class="profile-name">{{ $user->name }}</div>
                <div class="profile-role">
                    {{ $staff?->designation ?? $roleConfig['label'] }}
                    @if($user->tenant) · {{ $user->tenant->name }} @endif
                </div>

                <div class="profile-tags">
                    <span class="profile-badge"
                          style="background:var(--{{ $roleConfig['bg'] }});color:var(--{{ $roleConfig['color'] }})">
                        {{ $roleConfig['label'] }}
                    </span>
                    @if($staff)
                    <span class="profile-badge"
                          style="background:var(--{{ $typeConfig['bg'] }});color:var(--{{ $typeConfig['color'] }})">
                        {{ $typeConfig['label'] }}
                    </span>
                    @endif
                    <span class="profile-badge"
                          style="background:{{ $user->is_active ? 'var(--green-dim)':'var(--red-dim)' }};color:{{ $user->is_active ? 'var(--green)':'var(--red)' }}">
                        {{ $user->is_active ? 'Active':'Inactive' }}
                    </span>
                </div>

                {{-- Contact info --}}
                <div class="profile-info-row">
                    <div class="profile-info-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                    </div>
                    <div>
                        <div class="profile-info-label">Email</div>
                        <div class="profile-info-val">
                            <a href="mailto:{{ $user->email }}" style="color:var(--accent);text-decoration:none">{{ $user->email }}</a>
                        </div>
                    </div>
                </div>

                @if($user->phone)
                <div class="profile-info-row">
                    <div class="profile-info-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                    </div>
                    <div>
                        <div class="profile-info-label">Phone</div>
                        <div class="profile-info-val">{{ $user->phone }}</div>
                    </div>
                </div>
                @endif

                @if($staff?->department)
                <div class="profile-info-row">
                    <div class="profile-info-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>
                    </div>
                    <div>
                        <div class="profile-info-label">Department</div>
                        <div class="profile-info-val">{{ $staff->department->name }}</div>
                    </div>
                </div>
                @endif

                @if($staff?->joining_date)
                <div class="profile-info-row">
                    <div class="profile-info-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                    </div>
                    <div>
                        <div class="profile-info-label">Joined</div>
                        <div class="profile-info-val">
                            {{ $staff->joining_date->format('d M Y') }}
                            <span style="font-size:12px;color:var(--text-400)">
                                ({{ $staff->joining_date->diffForHumans() }})
                            </span>
                        </div>
                    </div>
                </div>
                @endif

                <div class="profile-info-row">
                    <div class="profile-info-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="profile-info-label">Last Login</div>
                        <div class="profile-info-val">
                            {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Right: Stats + Permissions ──────────────────────────── --}}
    <div>

        {{-- Stats --}}
        <div class="stat-grid">
            @php
                $statsData = [
                    ['num'=>$leadsCount,    'lbl'=>'Leads Created',  'color'=>'var(--accent)'],
                    ['num'=>$dealsCount,    'lbl'=>'Deals Assigned', 'color'=>'var(--amber)'],
                    ['num'=>$tasksCount,    'lbl'=>'Tasks Assigned', 'color'=>'var(--green)'],
                    ['num'=>$followupCount, 'lbl'=>'Follow-ups',     'color'=>'var(--purple)'],
                ];
            @endphp
            @foreach($statsData as $s)
            <div class="stat-card">
                <div class="stat-num" style="color:{{ $s['color'] }}">{{ $s['num'] }}</div>
                <div class="stat-lbl">{{ $s['lbl'] }}</div>
            </div>
            @endforeach
        </div>

        {{-- Account details --}}
        <div class="detail-card">
            <div class="detail-head">
                Account Details
                <a href="{{ route('tenant.settings.index') }}"
                   style="font-size:12px;color:var(--accent);text-decoration:none">Edit →</a>
            </div>
            <div style="padding:16px 20px;display:grid;grid-template-columns:1fr 1fr;gap:14px">
                @foreach([
                    ['lbl'=>'User ID',         'val'=>'#'.$user->id],
                    ['lbl'=>'Employee Code',    'val'=>$staff?->employee_code ?? '—'],
                    ['lbl'=>'Member Since',     'val'=>$user->created_at->format('d M Y')],
                    ['lbl'=>'Last Updated',     'val'=>$user->updated_at->diffForHumans()],
                ] as $row)
                <div>
                    <div style="font-size:11px;color:var(--text-400);font-weight:600;text-transform:uppercase;letter-spacing:.3px;margin-bottom:3px">
                        {{ $row['lbl'] }}
                    </div>
                    <div style="font-size:13.5px;font-weight:600;color:var(--text-100);font-family:var(--mono)">
                        {{ $row['val'] }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Module access --}}
        <div class="detail-card">
            <div class="detail-head">
                Module Access
                <span style="font-size:11.5px;font-weight:600;padding:2px 8px;border-radius:20px;background:var(--{{ $roleConfig['bg'] }});color:var(--{{ $roleConfig['color'] }})">
                    {{ $roleConfig['label'] }}
                </span>
            </div>
            <div class="perm-grid">
                @foreach($permMap as $module => $hasAccess)
                <div class="perm-item">
                    <div class="perm-dot"
                         style="background:{{ $hasAccess ? 'var(--green)':'var(--border-default)' }}">
                    </div>
                    <span style="color:{{ $hasAccess ? 'var(--text-100)':'var(--text-400)' }}">
                        {{ $module }}
                    </span>
                    @if($hasAccess)
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                         style="width:12px;height:12px;color:var(--green);margin-left:auto">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                    @else
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                         style="width:12px;height:12px;color:var(--text-400);margin-left:auto">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- Quick links --}}
        <div class="detail-card">
            <div class="detail-head">Quick Links</div>
            <div style="padding:12px 20px;display:flex;flex-wrap:wrap;gap:8px">
                @foreach([
                    ['href'=>route('tenant.settings.index'),              'label'=>'⚙️ Settings'],
                    ['href'=>route('tenant.notifications.index'),         'label'=>'🔔 Notifications'],
                    ['href'=>route('tenant.notifications.preferences'),   'label'=>'🎛️ Notification Prefs'],
                    ['href'=>route('tenant.leads.index'),                 'label'=>'👤 My Leads'],
                    ['href'=>route('tenant.tasks.index'),                 'label'=>'✅ My Tasks'],
                ] as $link)
                <a href="{{ $link['href'] }}"
                   style="padding:7px 14px;border-radius:var(--r-sm);border:1.5px solid var(--border-default);font-size:13px;font-weight:500;color:var(--text-200);text-decoration:none;transition:all .15s"
                   onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
                   onmouseout="this.style.borderColor='var(--border-default)';this.style.color='var(--text-200)'">
                    {{ $link['label'] }}
                </a>
                @endforeach
            </div>
        </div>

        {{-- ── API Keys (tenant_admin only) ───────────────────────── --}}
        @if($user->isTenantAdmin())
        <div class="detail-card">
            <div class="detail-head">
                <div>
                    API Keys
                    <span style="font-size:11.5px;font-weight:600;padding:2px 8px;border-radius:20px;background:var(--accent-dim);color:var(--accent);margin-left:6px">
                        {{ $apiKeys->count() }}
                    </span>
                </div>
                <a href="{{ route('tenant.api-keys.index') }}"
                   style="font-size:12px;color:var(--accent);text-decoration:none">
                    Full page →
                </a>
            </div>

            {{-- Flash messages --}}
            @if(session('success'))
            <div class="flash-alert flash-success" style="margin:12px 20px 0;">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                {{ session('success') }}
            </div>
            @endif
            @if($errors->has('name'))
            <div class="flash-alert flash-error" style="margin:12px 20px 0;">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                {{ $errors->first('name') }}
            </div>
            @endif

            {{-- Generate form --}}
            <form method="POST" action="{{ route('tenant.api-keys.store') }}">
                @csrf
                <div class="ak-gen-row">
                    <input type="text" name="name" class="ak-input"
                           placeholder="Label — e.g. Zoho CRM, n8n, Website"
                           value="{{ old('name') }}" maxlength="100" required>
                    <button type="submit" class="ak-btn-gen">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Generate
                    </button>
                </div>
            </form>

            {{-- Keys list --}}
            @if($apiKeys->isEmpty())
            <div class="ak-empty">No API keys yet. Generate one above.</div>
            @else
            @foreach($apiKeys as $key)
            <div class="ak-row">
                {{-- Info --}}
                <div class="ak-info">
                    <div class="ak-name">
                        {{ $key->name }}
                        @if($key->is_active)
                            <span class="ak-badge-active">Active</span>
                        @else
                            <span class="ak-badge-inactive">Inactive</span>
                        @endif
                    </div>
                    <div class="ak-meta">
                        Created {{ $key->created_at->format('d M Y') }}
                        @if($key->last_used_at) · Last used {{ $key->last_used_at->diffForHumans() }} @endif
                    </div>
                    {{-- Key value + copy --}}
                    <div class="ak-key-wrap" style="margin-top:6px">
                        <span class="ak-key-val" title="{{ $key->key }}">{{ substr($key->key,0,20) }}••••</span>
                        <button class="ak-icon-btn" onclick="akCopy('{{ $key->key }}', this)">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/>
                            </svg>
                            Copy
                        </button>
                    </div>
                </div>

                {{-- Actions --}}
                <div style="display:flex;flex-direction:column;gap:5px;flex-shrink:0">
                    {{-- Reset --}}
                    <form method="POST" action="{{ route('tenant.api-keys.regenerate', $key->id) }}"
                          data-confirm="Regenerate this key? The old key will stop working immediately." data-confirm-ok="Regenerate">
                        @csrf
                        <button type="submit" class="ak-icon-btn" style="width:100%">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                            </svg>
                            Reset
                        </button>
                    </form>

                    {{-- Remove --}}
                    <form method="POST" action="{{ route('tenant.api-keys.destroy', $key->id) }}"
                          data-confirm="Remove this API key permanently? Any integration using it will break." data-confirm-ok="Remove">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="ak-icon-btn danger" style="width:100%">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                            </svg>
                            Remove
                        </button>
                    </form>
                </div>
            </div>
            @endforeach
            @endif
        </div>
        @endif

    </div>
</div>

@push('scripts')
<script>
function akCopy(key, btn) {
    navigator.clipboard.writeText(key).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = `<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg> Copied!`;
        btn.style.borderColor = 'var(--green)';
        btn.style.color       = 'var(--green)';
        btn.style.background  = 'var(--green-dim)';
        setTimeout(() => {
            btn.innerHTML = orig;
            btn.style.borderColor = '';
            btn.style.color = '';
            btn.style.background = '';
        }, 2000);
    });
}
</script>
@endpush

@endsection