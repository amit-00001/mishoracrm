@extends('layouts.app')
@section('title', 'Role: ' . $roleDisplayName)

@push('styles')
<style>
.role-show-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 280px;
    gap: 16px;
    align-items: start;
}
@media (max-width: 900px) { .role-show-layout { grid-template-columns: 1fr; } }

.show-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    border-radius: var(--r-lg);
    overflow: hidden;
}
.show-sec {
    padding: 20px 24px;
    border-bottom: 1px solid var(--border-subtle);
}
.show-sec:last-child { border-bottom: none; }

.sec-title {
    font-size: 11.5px;
    font-weight: 700;
    color: var(--text-300);
    text-transform: uppercase;
    letter-spacing: .06em;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Permission badges */
.perm-group { margin-bottom: 16px; }
.perm-group:last-child { margin-bottom: 0; }
.pg-label {
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .05em;
    color: var(--text-400); margin-bottom: 7px;
    display: flex; align-items: center; gap: 6px;
}
.pg-badges { display: flex; flex-wrap: wrap; gap: 6px; }
.perm-badge {
    font-size: 11.5px; font-weight: 500;
    padding: 4px 9px;
    background: var(--accent-dim);
    color: var(--accent);
    border-radius: 100px;
    border: 1px solid rgba(55,138,221,.2);
    font-family: var(--font);
}

/* User list */
.user-row {
    display: flex; align-items: center; gap: 12px;
    padding: 8px 0;
    border-bottom: 1px solid var(--border-subtle);
}
.user-row:last-child { border-bottom: none; }
.user-avatar {
    width: 32px; height: 32px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700; flex-shrink: 0;
    background: var(--accent-dim); color: var(--accent);
}
.user-name  { font-size: 13px; font-weight: 600; color: var(--text-100); }
.user-email { font-size: 11.5px; color: var(--text-300); }

/* Sidebar */
.rs-sidebar { display: flex; flex-direction: column; gap: 14px; position: sticky; top: 80px; }
.sidebar-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    border-radius: var(--r-lg);
    overflow: hidden;
}
.sc-head { padding: 13px 18px; border-bottom: 1px solid var(--border-subtle); font-size: 13px; font-weight: 700; color: var(--text-100); }
.sc-body { padding: 16px 18px; }

.stat-row { display: flex; align-items: center; justify-content: space-between; padding: 7px 0; border-bottom: 1px solid var(--border-subtle); font-size: 13px; }
.stat-row:last-child { border-bottom: none; }
.stat-key { color: var(--text-300); }
.stat-val { font-weight: 700; color: var(--text-100); font-family: var(--mono); }
</style>
@endpush

@section('content')

{{-- Page Header --}}
<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px;display:flex;align-items:center;gap:6px">
            <a href="{{ route('tenant.roles.index') }}" style="color:var(--text-300);text-decoration:none">Roles & Permissions</a>
            <span style="opacity:.4">›</span>
            <span>{{ $roleDisplayName }}</span>
        </div>
        <div class="page-title">{{ $roleDisplayName }}</div>
    </div>
    <div style="display:flex;gap:8px">
        @if($editable)
        <a href="{{ route('tenant.roles.edit', $role->id) }}" class="btn btn-primary">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/>
            </svg>
            Edit Permissions
        </a>
        @endif
        <a href="{{ route('tenant.roles.index') }}" class="btn btn-secondary">← Back</a>
    </div>
</div>

@if(session('success'))
<div style="padding:12px 16px;background:var(--green-dim);border:1px solid rgba(29,158,117,.2);border-radius:var(--r-sm);font-size:13px;color:var(--green);margin-bottom:16px">
    ✓ {{ session('success') }}
</div>
@endif

<div class="role-show-layout">

    {{-- ── Main ── --}}
    <div class="show-card">

        {{-- Description --}}
        <div class="show-sec">
            <div class="sec-title">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:13px;height:13px">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                </svg>
                Role Details
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                <div>
                    <div style="font-size:11.5px;font-weight:600;color:var(--text-300);text-transform:uppercase;letter-spacing:.04em;margin-bottom:5px">Display Name</div>
                    <div style="font-size:15px;font-weight:700;color:var(--text-100)">{{ $roleDisplayName }}</div>
                </div>
                <div>
                    <div style="font-size:11.5px;font-weight:600;color:var(--text-300);text-transform:uppercase;letter-spacing:.04em;margin-bottom:5px">Internal Slug</div>
                    <div style="font-size:12px;font-family:var(--mono);color:var(--text-400);background:var(--bg-elevated);padding:5px 9px;border-radius:var(--r-sm);display:inline-block">{{ $role->name }}</div>
                </div>
                @if($role->description)
                <div style="grid-column:1/-1">
                    <div style="font-size:11.5px;font-weight:600;color:var(--text-300);text-transform:uppercase;letter-spacing:.04em;margin-bottom:5px">Description</div>
                    <div style="font-size:13.5px;color:var(--text-200);line-height:1.5">{{ $role->description }}</div>
                </div>
                @endif
            </div>
        </div>

        {{-- Permissions grouped by module --}}
        <div class="show-sec">
            <div class="sec-title">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:13px;height:13px">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                </svg>
                Permissions
                <span style="font-size:11.5px;font-weight:400;text-transform:none;letter-spacing:0;color:var(--accent)">({{ $role->permissions->count() }} total)</span>
            </div>

            @php
            $icons = [
                'leads'=>'🎯','contacts'=>'👤','deals'=>'💼','followups'=>'📅','tasks'=>'✅',
                'quotations'=>'📄','invoices'=>'🧾','staff'=>'👥','departments'=>'🏢',
                'whatsapp'=>'💬','email'=>'📧','reports'=>'📊','settings'=>'⚙️',
                'notifications'=>'🔔','roles'=>'🔐','attendance'=>'🕐',
            ];
            $grouped = $role->permissions->groupBy(fn($p) => explode('.', $p->name)[0] ?? 'other');
            @endphp

            @if($grouped->isEmpty())
            <div style="padding:24px;text-align:center;color:var(--text-400);font-size:13px">
                No permissions assigned to this role yet.
                @if($editable)
                <br><br>
                <a href="{{ route('tenant.roles.edit', $role->id) }}" class="btn btn-primary btn-sm">Add Permissions</a>
                @endif
            </div>
            @else
            @foreach($grouped as $module => $perms)
            <div class="perm-group">
                <div class="pg-label">
                    <span>{{ $icons[$module] ?? '🔧' }}</span>
                    {{ ucwords(str_replace('_', ' ', $module)) }}
                    <span style="font-weight:400;color:var(--accent)">({{ $perms->count() }})</span>
                </div>
                <div class="pg-badges">
                    @foreach($perms as $perm)
                    @php $label = ucwords(str_replace(['_', '.'], [' ', ': '], $perm->name)); @endphp
                    <span class="perm-badge">{{ $label }}</span>
                    @endforeach
                </div>
            </div>
            @endforeach
            @endif
        </div>

        {{-- Assigned Users --}}
        <div class="show-sec">
            <div class="sec-title">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:13px;height:13px">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
                Staff with this Role
                <span style="font-size:11.5px;font-weight:400;text-transform:none;letter-spacing:0">
                    ({{ $users->count() }} {{ Str::plural('member', $users->count()) }})
                </span>
            </div>

            @if($users->isEmpty())
            <div style="padding:16px;text-align:center;color:var(--text-400);font-size:13px">
                No staff members are assigned to this role yet.
            </div>
            @else
            @foreach($users as $user)
            @php
                $initials = collect(explode(' ', $user->name))->map(fn($p) => strtoupper($p[0]))->join('');
            @endphp
            <div class="user-row">
                <div class="user-avatar">{{ substr($initials, 0, 2) }}</div>
                <div style="flex:1">
                    <div class="user-name">{{ $user->name }}</div>
                    <div class="user-email">{{ $user->email }}</div>
                </div>
            </div>
            @endforeach
            @endif

            {{-- Assign a staff member to this role --}}
            @if($addableStaff->isNotEmpty())
            <form method="POST" action="{{ route('tenant.roles.assign') }}"
                  style="display:flex;gap:8px;margin-top:14px;flex-wrap:wrap">
                @csrf
                <input type="hidden" name="role" value="{{ $role->name }}"/>
                <select name="user_id" required
                        style="flex:1;min-width:200px;padding:8px 10px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm);color:var(--text-100);font-size:13px">
                    <option value="">Add a staff member to this role…</option>
                    @foreach($addableStaff as $u)
                    <option value="{{ $u->id }}">{{ $u->name }} — {{ $u->email }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-secondary btn-sm">Assign</button>
            </form>
            <p style="font-size:11.5px;color:var(--text-400);margin-top:6px">
                Moving someone here replaces their current role.
            </p>
            @endif
        </div>

        {{-- Danger zone --}}
        @if($editable)
        <div class="show-sec" id="danger-zone">
            <div class="sec-title" style="color:var(--red)">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:13px;height:13px">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                </svg>
                Delete this role
            </div>
            <form method="POST" action="{{ route('tenant.roles.destroy', $role->id) }}"
                  data-confirm="Delete '{{ $roleDisplayName }}'? This cannot be undone." data-confirm-ok="Delete">
                @csrf @method('DELETE')
                @if($users->isNotEmpty())
                <div style="font-size:12.5px;color:var(--text-300);margin-bottom:10px">
                    {{ $users->count() }} {{ Str::plural('member', $users->count()) }} on this role.
                    @if(\App\Helpers\Roles::systemBase($role->name))
                        On delete they revert to the default {{ $roleDisplayName }} permissions.
                    @else
                        Choose where to move them:
                    @endif
                </div>
                @if(! \App\Helpers\Roles::systemBase($role->name))
                <select name="reassign_to" required
                        style="width:100%;max-width:340px;padding:8px 10px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm);color:var(--text-100);font-size:13px;margin-bottom:12px">
                    <option value="">Move members to…</option>
                    @foreach($otherRoles as $r)
                    <option value="{{ $r['name'] }}">{{ $r['label'] }}</option>
                    @endforeach
                </select>
                <br>
                @endif
                @endif
                <button type="submit" class="btn btn-secondary" style="color:var(--red)">Delete Role</button>
            </form>
        </div>
        @endif

    </div>{{-- /show-card --}}

    {{-- ── Sidebar ── --}}
    <div class="rs-sidebar">

        {{-- Stats --}}
        <div class="sidebar-card">
            <div class="sc-head">Overview</div>
            <div class="sc-body">
                <div class="stat-row">
                    <span class="stat-key">Total permissions</span>
                    <span class="stat-val">{{ $role->permissions->count() }}</span>
                </div>
                <div class="stat-row">
                    <span class="stat-key">Modules covered</span>
                    <span class="stat-val">{{ $grouped->count() }}</span>
                </div>
                <div class="stat-row">
                    <span class="stat-key">Staff assigned</span>
                    <span class="stat-val">{{ $users->count() }}</span>
                </div>
                <div class="stat-row">
                    <span class="stat-key">Created</span>
                    <span class="stat-val" style="font-size:11.5px;font-family:var(--font)">{{ $role->created_at->format('d M Y') }}</span>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="sidebar-card">
            <div class="sc-head">Actions</div>
            <div class="sc-body" style="display:flex;flex-direction:column;gap:8px">
                @if($editable)
                <a href="{{ route('tenant.roles.edit', $role->id) }}" class="btn btn-primary" style="width:100%;justify-content:center">
                    Edit Permissions
                </a>
                <a href="#danger-zone" class="btn btn-secondary" style="width:100%;justify-content:center;color:var(--red)">
                    Delete Role
                </a>
                @else
                <div style="font-size:12.5px;color:var(--text-400);line-height:1.5">
                    This is the shared platform default and is read-only here.
                    @if(\App\Helpers\Roles::systemBase($role->name))
                    To change it for your workspace, go back to Roles &amp; Permissions and click <strong>Customise</strong> on the {{ $roleDisplayName }} card.
                    @endif
                </div>
                @endif
            </div>
        </div>

        {{-- Module Coverage --}}
        @if($grouped->isNotEmpty())
        <div class="sidebar-card">
            <div class="sc-head">Module Coverage</div>
            <div class="sc-body">
                @php $totalAll = collect($permissions)->flatten()->count(); @endphp
                @foreach($grouped as $module => $perms)
                @php
                    $moduleTotal = isset($permissions[$module]) ? count($permissions[$module]) : $perms->count();
                    $pct = $moduleTotal > 0 ? round(($perms->count() / $moduleTotal) * 100) : 0;
                @endphp
                <div style="margin-bottom:10px">
                    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px">
                        <span style="color:var(--text-200);text-transform:capitalize">{{ $icons[$module] ?? '🔧' }} {{ ucwords(str_replace('_', ' ', $module)) }}</span>
                        <span style="font-family:var(--mono);color:var(--accent);font-weight:700">{{ $perms->count() }}/{{ $moduleTotal }}</span>
                    </div>
                    <div style="height:4px;background:var(--bg-elevated);border-radius:100px;overflow:hidden">
                        <div style="height:100%;background:var(--accent);width:{{ $pct }}%;border-radius:100px"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

    </div>{{-- /rs-sidebar --}}

</div>

@endsection
