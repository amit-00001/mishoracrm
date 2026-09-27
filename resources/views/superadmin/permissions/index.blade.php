@extends('layouts.app')
@section('title', 'Roles & Permissions')

@push('styles')
<style>
.badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:100px; font-size:11px; font-weight:700; }
.badge-blue  { background:rgba(99,102,241,.1); color:var(--accent); }
.badge-gray  { background:var(--bg-input); color:var(--text-400); }
.action-btns { display:flex; gap:6px; }
.empty-state { text-align:center; padding:56px 16px; color:var(--text-400); font-size:14px; }

.perm-module { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); margin-bottom:14px; overflow:hidden; }
.pm-head { display:flex; align-items:center; gap:10px; padding:14px 18px; background:var(--bg-elevated); cursor:pointer; user-select:none; }
.pm-emoji { font-size:16px; }
.pm-module-name { font-size:14px; font-weight:700; color:var(--text-100); flex:1; }
.pm-count { font-size:12px; color:var(--text-400); }
.pm-chevron { width:15px; height:15px; color:var(--text-400); transition:transform .15s; }
.pm-head.open .pm-chevron { transform:rotate(180deg); }
.pm-perms { display:none; }
.pm-head.open + .pm-perms { display:block; }
.perm-row { display:flex; align-items:center; gap:12px; padding:11px 18px; border-top:1px solid var(--border-subtle); }
.perm-name { font-family:monospace; font-size:13px; font-weight:600; color:var(--text-100); flex:1; }
</style>
@endpush

@section('content')

    <div class="page-head">
        <div>
            <div class="page-title">Roles & Permissions</div>
            <div class="page-sub">Master permission list — add a permission here to make it instantly available for Tenant Admin and the tenant-side role builder. No seeder/code changes needed.</div>
        </div>
        <div class="page-actions">
            <a href="{{ route('superadmin.roles.edit', 'tenant_admin') }}" class="btn btn-secondary">
                Edit Tenant Admin Role
            </a>
            <a href="{{ route('superadmin.roles.edit', 'staff') }}" class="btn btn-secondary">
                Edit Default Staff Role
            </a>
            <a href="{{ route('superadmin.permissions.create') }}" class="btn btn-primary">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Add Permission
            </a>
        </div>
    </div>

    @if(session('success'))
    <div style="padding:12px 16px;background:var(--green-dim);border:1px solid rgba(29,158,117,.2);border-radius:var(--r-sm);font-size:13px;color:var(--green);margin-bottom:16px">
        ✓ {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div style="padding:12px 16px;background:var(--red-dim);border:1px solid rgba(224,82,82,.18);border-radius:var(--r-sm);font-size:13px;color:var(--red);margin-bottom:16px">
        {{ session('error') }}
    </div>
    @endif

    @php
    $icons = [
        'leads'             => '🎯',
        'contacts'          => '👤',
        'deals'             => '💼',
        'followups'         => '📅',
        'tasks'             => '✅',
        'quotations'        => '📄',
        'invoices'          => '🧾',
        'vendors'           => '🏭',
        'purchase_requests' => '🛒',
        'purchase_orders'   => '📦',
        'work_orders'       => '🔧',
        'staff'             => '👥',
        'departments'       => '🏢',
        'whatsapp'          => '💬',
        'email'             => '📧',
        'reports'           => '📊',
        'settings'          => '⚙️',
        'notifications'     => '🔔',
        'roles'             => '🔐',
        'attendance'        => '🕐',
        'audit_logs'        => '📜',
    ];
    @endphp

    @forelse($permissions as $module => $modulePerms)
    <div class="perm-module">
        <div class="pm-head open" onclick="this.classList.toggle('open')">
            <span class="pm-emoji">{{ $icons[$module] ?? '🔧' }}</span>
            <span class="pm-module-name">{{ ucfirst(str_replace('_', ' ', $module)) }}</span>
            <span class="pm-count">{{ count($modulePerms) }} permission(s)</span>
            <svg class="pm-chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
            </svg>
        </div>
        <div class="pm-perms">
            @foreach($modulePerms as $perm)
            <div class="perm-row">
                <span class="perm-name">{{ $perm->name }}</span>
                @if($perm->roles_count > 0)
                    <span class="badge badge-blue">used by {{ $perm->roles_count }} role(s)</span>
                @else
                    <span class="badge badge-gray">unused</span>
                @endif
                <div class="action-btns">
                    <a href="{{ route('superadmin.permissions.edit', $perm) }}" class="btn btn-secondary btn-sm">Edit</a>
                    <form action="{{ route('superadmin.permissions.destroy', $perm) }}" method="POST" style="display:inline"
                          data-confirm="Delete permission {{ $perm->name }}?{{ $perm->roles_count > 0 ? ' It is used by ' . $perm->roles_count . ' role(s) and will be removed from them.' : '' }}" data-confirm-ok="Delete">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--red)">Delete</button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @empty
    <div class="empty-state">
        No permissions yet. <a href="{{ route('superadmin.permissions.create') }}" style="color:var(--accent)">Add your first permission →</a>
    </div>
    @endforelse

@endsection
