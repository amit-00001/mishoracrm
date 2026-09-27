@extends('layouts.app')
@section('title', 'Roles & Permissions')

@push('styles')
<style>
.roles-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:14px; }
.role-card  {
    background:var(--bg-surface); border:1px solid var(--border-default);
    border-radius:var(--r-lg); overflow:hidden;
    transition:border-color .15s, box-shadow .15s;
}
.role-card:hover { border-color:var(--accent); box-shadow:0 2px 14px rgba(0,0,0,.06); }
.role-card.system { border-left:3px solid var(--accent); }
.role-card-head { padding:16px 18px; display:flex; align-items:flex-start; gap:12px; border-bottom:1px solid var(--border-subtle); }
.role-icon { width:36px;height:36px;border-radius:var(--r-sm);display:flex;align-items:center;justify-content:center;flex-shrink:0 }
.role-name { font-size:14.5px; font-weight:700; color:var(--text-100); margin-bottom:3px; }
.role-desc { font-size:12px; color:var(--text-300); line-height:1.5; }
.role-card-body { padding:14px 18px; display:flex; align-items:center; justify-content:space-between; }
.perm-count { font-size:12px; color:var(--text-300); }
.user-count { font-size:12px; font-weight:600; color:var(--text-200); display:flex; align-items:center; gap:5px; }
.sys-label { font-size:10.5px; font-weight:700; padding:2px 8px; border-radius:100px; background:var(--accent-dim); color:var(--accent); letter-spacing:.04em; }
.action-row { padding:12px 18px; border-top:1px solid var(--border-subtle); display:flex; gap:8px; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div class="page-title">Roles & Permissions</div>
        <div class="page-sub">Create custom roles and assign permissions to your staff</div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('tenant.permissions.create') }}" class="btn btn-secondary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Permission
        </a>
        <a href="{{ route('tenant.roles.create') }}" class="btn btn-primary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Create Role
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

{{-- Info banner --}}
<div style="padding:12px 16px;background:var(--bg-elevated);border:1px solid var(--border-subtle);border-radius:var(--r-sm);font-size:13px;color:var(--text-300);margin-bottom:20px;display:flex;gap:10px;align-items:flex-start">
    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" style="width:16px;height:16px;flex-shrink:0;margin-top:1px;color:var(--accent)">
        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
    </svg>
    <div>
        <strong style="color:var(--text-200)">How roles work:</strong>
        You can create custom roles and assign any combination of permissions to them.
        System roles (Tenant Admin, Staff) are predefined and cannot be deleted.
        Need a permission for a new module? <a href="{{ route('tenant.permissions.create') }}" style="color:var(--accent)">Add it here</a> — no need to wait on the developer.
    </div>
</div>

{{-- System Roles --}}
<div style="margin-bottom:24px">
    <div style="font-size:12px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px">
        System Roles
        <span style="font-size:11px;font-weight:400;color:var(--text-400);text-transform:none;letter-spacing:0;margin-left:6px">(built-in — the Staff role can be customised for your workspace)</span>
    </div>
    <div class="roles-grid">
        @foreach($systemRoles as $role)
        <div class="role-card system">
            <div class="role-card-head">
                <div class="role-icon" style="background:var(--accent-dim)">
                    <svg fill="none" stroke="var(--accent)" stroke-width="1.75" viewBox="0 0 24 24" style="width:17px;height:17px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                    </svg>
                </div>
                <div style="flex:1">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:3px">
                        <div class="role-name">{{ $role->label }}</div>
                        @if($role->customised)
                            <span class="sys-label" style="background:var(--purple-dim);color:var(--purple)">Customised</span>
                        @elseif($role->editable)
                            <span class="sys-label">System</span>
                        @else
                            <span class="sys-label">Platform-managed</span>
                        @endif
                    </div>
                    <div class="role-desc">{{ $role->description }}</div>
                </div>
            </div>
            <div class="role-card-body">
                <span class="perm-count">{{ $role->perm_count }} permissions</span>
                <span class="user-count">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:13px;height:13px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                    </svg>
                    {{ $role->user_count }} staff
                </span>
            </div>
            <div class="action-row">
                @if($role->customised)
                    <a href="{{ route('tenant.roles.edit', $role->id) }}" class="btn btn-secondary btn-sm">Edit Permissions</a>
                    <a href="{{ route('tenant.roles.show', $role->id) }}" class="btn btn-secondary btn-sm">View</a>
                @else
                    <form method="POST" action="{{ route('tenant.roles.system.customise', $role->base) }}"
                          data-confirm="This creates your workspace's own editable copy of the {{ $role->label }} role. Current {{ $role->label }} members move to it automatically. Continue?" data-confirm-danger="false">
                        @csrf
                        <button type="submit" class="btn btn-secondary btn-sm">Customise</button>
                    </form>
                    <span style="font-size:11.5px;color:var(--text-400);align-self:center">Uses platform defaults</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>
</div>

{{-- Custom Roles --}}
<div>
    <div style="font-size:12px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px">
        Custom Roles
        <span style="font-weight:400;color:var(--text-400);text-transform:none;letter-spacing:0;margin-left:6px">({{ $tenantRoles->count() }} created)</span>
    </div>

    @if($tenantRoles->isEmpty())
    <div style="padding:48px 20px;text-align:center;background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-lg)">
        <div style="font-size:28px;margin-bottom:10px">🎭</div>
        <div style="font-size:14px;font-weight:700;color:var(--text-100);margin-bottom:6px">No custom roles yet</div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:16px">
            Create roles like "Sales Manager", "Support Staff", "Billing Team"
            with exactly the permissions they need.
        </div>
        <a href="{{ route('tenant.roles.create') }}" class="btn btn-primary">Create First Role</a>
    </div>

    @else
    <div class="roles-grid">
        @foreach($tenantRoles as $role)
        @php
            $displayName = ucwords(str_replace('_', ' ', preg_replace('/^tenant_\d+_/', '', $role->name)));
        @endphp
        <div class="role-card">
            <div class="role-card-head">
                <div class="role-icon" style="background:var(--purple-dim)">
                    <svg fill="none" stroke="var(--purple)" stroke-width="1.75" viewBox="0 0 24 24" style="width:17px;height:17px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                    </svg>
                </div>
                <div style="flex:1">
                    <div class="role-name">{{ $displayName }}</div>
                    <div class="role-desc">{{ $role->description ?? 'No description' }}</div>
                </div>
            </div>
            <div class="role-card-body">
                <span class="perm-count">{{ $role->permissions()->count() }} permissions</span>
                <span class="user-count">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:13px;height:13px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                    {{ $role->users_count }} staff
                </span>
            </div>
            <div class="action-row">
                <a href="{{ route('tenant.roles.edit', $role->id) }}" class="btn btn-secondary btn-sm">Edit Permissions</a>
                <a href="{{ route('tenant.roles.show', $role->id) }}" class="btn btn-secondary btn-sm">View</a>
                @if($role->users_count > 0)
                    <a href="{{ route('tenant.roles.show', $role->id) }}#danger-zone"
                       class="btn btn-secondary btn-sm" style="color:var(--red);margin-left:auto">Delete…</a>
                @else
                    <form method="POST" action="{{ route('tenant.roles.destroy', $role->id) }}"
                          data-confirm="Delete role '{{ $displayName }}'?" data-confirm-ok="Delete"
                          style="margin-left:auto">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--red)">Delete</button>
                    </form>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

@endsection