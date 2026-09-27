@extends('layouts.app')
@section('title', $staff->user->name)

@push('styles')
<style>
.show-layout { display:grid; grid-template-columns:300px 1fr; gap:16px; align-items:start; }
@media(max-width:1024px) { .show-layout { grid-template-columns:1fr; } }

.detail-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; margin-bottom:16px; }
.dc-head { padding:16px 20px; border-bottom:1px solid var(--border-subtle); display:flex; align-items:center; justify-content:space-between; }
.dc-title { font-size:13.5px; font-weight:700; color:var(--text-100); }
.dc-body  { padding:20px; }

/* Profile card */
.profile-card {
    background:var(--bg-surface); border:1px solid var(--border-default);
    border-radius:var(--r-lg); overflow:hidden; margin-bottom:16px;
}
.profile-cover {
    height:80px;
    background:linear-gradient(135deg, var(--accent-dim) 0%, var(--purple-dim) 100%);
}
.profile-body { padding:0 20px 20px; }
.profile-avatar-wrap { margin-top:-32px; margin-bottom:12px; }
.profile-avatar {
    width:64px; height:64px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:24px; font-weight:800;
    border:3px solid var(--bg-surface);
}
.profile-name  { font-size:17px; font-weight:800; color:var(--text-100); margin-bottom:3px; }
.profile-desig { font-size:13px; color:var(--text-300); margin-bottom:12px; }
.profile-tags  { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:16px; }
.role-badge    { font-size:11px; font-weight:600; padding:3px 10px; border-radius:20px; }
.type-badge    { font-size:11px; font-weight:600; padding:3px 10px; border-radius:20px; }
.active-badge  { background:var(--green-dim); color:var(--green); font-size:11px; font-weight:600; padding:3px 10px; border-radius:20px; }
.inactive-badge{ background:var(--red-dim);   color:var(--red);   font-size:11px; font-weight:600; padding:3px 10px; border-radius:20px; }

/* Info rows */
.info-row { display:flex; align-items:flex-start; gap:12px; padding:10px 0; border-bottom:1px solid var(--border-subtle); }
.info-row:last-child { border-bottom:none; }
.info-icon { width:32px; height:32px; border-radius:var(--r-sm); background:var(--bg-elevated); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.info-icon svg { width:15px; height:15px; color:var(--text-300); }
.info-label { font-size:11px; color:var(--text-400); font-weight:600; text-transform:uppercase; letter-spacing:.3px; margin-bottom:2px; }
.info-value { font-size:13.5px; color:var(--text-100); font-weight:500; }

/* Quick actions */
.qa-btn {
    display:flex; align-items:center; gap:10px;
    padding:10px 14px; border-radius:var(--r-sm);
    border:1.5px solid var(--border-default);
    background:none; cursor:pointer; width:100%;
    font-family:var(--font); font-size:13px; font-weight:500;
    color:var(--text-200); text-align:left; text-decoration:none;
    transition:all .15s var(--ease); margin-bottom:8px;
}
.qa-btn:last-child { margin-bottom:0; }
.qa-btn svg { width:15px; height:15px; flex-shrink:0; }
.qa-btn:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-dim); }
.qa-btn.danger:hover { border-color:var(--red); color:var(--red); background:var(--red-dim); }
.qa-btn.success:hover { border-color:var(--green); color:var(--green); background:var(--green-dim); }

/* Stats row */
.stats-row { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:16px; }
.stat-mini { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-md); padding:14px; text-align:center; }
.stat-mini-num { font-size:22px; font-weight:800; color:var(--text-100); font-family:var(--mono); letter-spacing:-.5px; }
.stat-mini-lbl { font-size:11px; color:var(--text-300); margin-top:2px; }

/* Activity / permission list */
.perm-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; }
.perm-item { display:flex; align-items:center; gap:8px; font-size:12.5px; color:var(--text-200); }
.perm-dot  { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
.perm-dot.yes { background:var(--green); }
.perm-dot.no  { background:var(--border-default); }
</style>
@endpush

@section('content')

@php
    $cfg          = config('staff');
    $types        = $cfg['employment_types'];
    $avatarColors = $cfg['avatar_colors'];
    $avColor      = $avatarColors[abs(crc32($staff->user->name ?? '')) % count($avatarColors)];
    $avBg         = $avColor['bg'];
    $avTx         = $avColor['text'];

    $userRole     = $staff->user->roles->first()?->name;
    $isAdminRole  = $userRole === 'tenant_admin';
    $isStaffRole  = $userRole === 'staff' || str_ends_with((string) $userRole, '_staff');
    $roleConfig   = [
        'label' => \App\Helpers\Roles::label($userRole ?: 'staff'),
        'color' => $isAdminRole ? 'red' : ($isStaffRole ? 'green' : 'accent'),
        'bg'    => $isAdminRole ? 'red-dim' : ($isStaffRole ? 'green-dim' : 'accent-dim'),
    ];
    $typeConfig   = $types[$staff->employment_type] ?? ['label'=>ucfirst($staff->employment_type),'color'=>'accent','bg'=>'accent-dim'];
@endphp

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.staffs.index') }}" style="color:var(--text-300);text-decoration:none">Staff</a>
            <span style="margin:0 6px">›</span>
            {{ $staff->user->name }}
        </div>
        <div class="page-title">{{ $staff->user->name }}</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('tenant.staffs.edit', $staff->id) }}" class="btn btn-primary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
            </svg>
            Edit
        </a>
    </div>
</div>

<div class="show-layout">

    {{-- ── Left column ────────────────────────────────────────── --}}
    <div>

        {{-- Profile card --}}
        <div class="profile-card">
            <div class="profile-cover"></div>
            <div class="profile-body">
                <div class="profile-avatar-wrap">
                    <div class="profile-avatar" style="background:{{ $avBg }};color:{{ $avTx }}">
                        {{ strtoupper(substr($staff->user->name, 0, 1)) }}
                    </div>
                </div>
                <div class="profile-name">{{ $staff->user->name }}</div>
                <div class="profile-desig">
                    {{ $staff->designation ?? $staff->department?->name ?? 'Staff Member' }}
                </div>
                <div class="profile-tags">
                    {{-- Role badge --}}
                    <span class="role-badge"
                          style="background:var(--{{ $roleConfig['bg'] }});color:var(--{{ $roleConfig['color'] }})">
                        {{ $roleConfig['label'] }}
                    </span>

                    {{-- Employment type badge --}}
                    <span class="type-badge"
                          style="background:var(--{{ $typeConfig['bg'] }});color:var(--{{ $typeConfig['color'] }})">
                        {{ $typeConfig['label'] }}
                    </span>

                    {{-- Active/inactive --}}
                    @if($staff->user->is_active)
                    <span class="active-badge">Active</span>
                    @else
                    <span class="inactive-badge">Inactive</span>
                    @endif
                </div>

                {{-- Contact info --}}
                <div class="info-row" style="padding-top:0">
                    <div class="info-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                        </svg>
                    </div>
                    <div>
                        <div class="info-label">Email</div>
                        <div class="info-value">
                            <a href="mailto:{{ $staff->user->email }}" style="color:var(--accent);text-decoration:none">
                                {{ $staff->user->email }}
                            </a>
                        </div>
                    </div>
                </div>

                @if($staff->user->phone)
                <div class="info-row">
                    <div class="info-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="info-label">Phone</div>
                        <div class="info-value">
                            <a href="tel:{{ $staff->user->phone }}" style="color:var(--text-100);text-decoration:none">
                                {{ $staff->user->phone }}
                            </a>
                        </div>
                    </div>
                </div>
                @endif

            </div>
        </div>

        {{-- Quick actions --}}
        <div class="detail-card">
            <div class="dc-head"><div class="dc-title">Actions</div></div>
            <div class="dc-body" style="padding:12px">
                <a href="{{ route('tenant.staffs.edit', $staff->id) }}" class="qa-btn">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                    </svg>
                    Edit Staff
                </a>

                @if($staff->user->is_active)
                <form data-confirm="Deactivate {{ $staff->user->name }}?" data-confirm-ok="Deactivate" method="POST" action="{{ route('tenant.staffs.deactivate', $staff->id) }}">
                    @csrf
                    <button type="submit" class="qa-btn danger">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                        Deactivate Account
                    </button>
                </form>
                @else
                <form method="POST" action="{{ route('tenant.staffs.activate', $staff->id) }}">
                    @csrf
                    <button type="submit" class="qa-btn success">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Activate Account
                    </button>
                </form>
                @endif

                <form method="POST" action="{{ route('tenant.staffs.destroy', $staff->id) }}"
                      data-confirm="Remove {{ $staff->user->name }} from staff? This cannot be undone." data-confirm-ok="Remove">
                    @csrf @method('DELETE')
                    <button type="submit" class="qa-btn danger">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                        </svg>
                        Remove Staff
                    </button>
                </form>
            </div>
        </div>

    </div>

    {{-- ── Right column ────────────────────────────────────────── --}}
    <div>

        {{-- Job details --}}
        <div class="detail-card">
            <div class="dc-head">
                <div class="dc-title">Job Information</div>
            </div>
            <div class="dc-body">
                <div class="info-row" style="padding-top:0">
                    <div class="info-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                        </svg>
                    </div>
                    <div>
                        <div class="info-label">Department</div>
                        <div class="info-value">{{ $staff->department?->name ?? '—' }}</div>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0"/>
                        </svg>
                    </div>
                    <div>
                        <div class="info-label">Designation</div>
                        <div class="info-value">{{ $staff->designation ?? '—' }}</div>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="info-label">Employee Code</div>
                        <div class="info-value">{{ $staff->employee_code ?? '—' }}</div>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
                        </svg>
                    </div>
                    <div>
                        <div class="info-label">Joining Date</div>
                        <div class="info-value">
                            {{ $staff->joining_date?->format('d M Y') ?? '—' }}
                            @if($staff->joining_date)
                            <span style="font-size:12px;color:var(--text-400);margin-left:6px">
                                ({{ $staff->joining_date->diffForHumans() }})
                            </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/>
                        </svg>
                    </div>
                    <div>
                        <div class="info-label">Monthly Salary</div>
                        <div class="info-value">
                            {{ $staff->salary ? '₹'.number_format($staff->salary, 0) : '—' }}
                        </div>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="info-label">Employment Type</div>
                        <div class="info-value">
                            <span style="background:var(--{{ $typeConfig['bg'] }});color:var(--{{ $typeConfig['color'] }});padding:2px 8px;border-radius:20px;font-size:12px;font-weight:600">
                                {{ $typeConfig['label'] }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="info-row">
                    <div class="info-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="info-label">Last Login</div>
                        <div class="info-value" style="font-size:13px">
                            {{ $staff->user->last_login_at
                                ? $staff->user->last_login_at->format('d M Y, h:i A')
                                : 'Never logged in' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Permissions --}}
        <div class="detail-card">
            <div class="dc-head">
                <div class="dc-title">Role & Access</div>
                <span style="background:var(--{{ $roleConfig['bg'] }});color:var(--{{ $roleConfig['color'] }});font-size:11.5px;font-weight:600;padding:3px 10px;border-radius:20px">
                    {{ $roleConfig['label'] }}
                </span>
            </div>
            <div class="dc-body">
                @php
                    // Real access, derived from the permissions actually on this user's role.
                    $userPermNames = $staff->user->user_type === 'tenant_admin'
                        ? \Spatie\Permission\Models\Permission::pluck('name')
                        : $staff->user->getPermissionNames();
                    $userModules = $userPermNames->map(fn($p) => explode('.', $p)[0])->unique();

                    $shownModules = ['leads','contacts','deals','quotations','invoices','staff','reports','settings','tasks','followups','tickets','appointments'];
                @endphp
                <div class="perm-grid">
                    @foreach($shownModules as $module)
                    @php $hasAccess = $userModules->contains($module); @endphp
                    <div class="perm-item">
                        <div class="perm-dot {{ $hasAccess ? 'yes':'no' }}"></div>
                        <span style="{{ $hasAccess ? 'color:var(--text-100)':'color:var(--text-400)' }}">
                            {{ ucfirst(str_replace('_',' ',$module)) }}
                        </span>
                    </div>
                    @endforeach
                </div>
                <p style="font-size:11.5px;color:var(--text-400);margin-top:12px">
                    Based on the <strong>{{ $roleConfig['label'] }}</strong> role.
                    <a href="{{ route('tenant.roles.index') }}" style="color:var(--accent)">Manage roles &amp; permissions</a>
                </p>
            </div>
        </div>

        {{-- System info --}}
        <div class="detail-card">
            <div class="dc-head"><div class="dc-title">System Info</div></div>
            <div class="dc-body">
                <div class="info-row" style="padding-top:0">
                    <div>
                        <div class="info-label">Staff ID</div>
                        <div class="info-value td-mono">#{{ $staff->id }}</div>
                    </div>
                </div>
                <div class="info-row">
                    <div>
                        <div class="info-label">User ID</div>
                        <div class="info-value td-mono">#{{ $staff->user_id }}</div>
                    </div>
                </div>
                <div class="info-row">
                    <div>
                        <div class="info-label">Added On</div>
                        <div class="info-value">{{ $staff->created_at->format('d M Y, h:i A') }}</div>
                    </div>
                </div>
                <div class="info-row">
                    <div>
                        <div class="info-label">Last Updated</div>
                        <div class="info-value">{{ $staff->updated_at->diffForHumans() }}</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@endsection