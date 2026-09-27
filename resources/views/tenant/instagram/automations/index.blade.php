@extends('layouts.app')
@section('title', 'Instagram Automations')

@push('styles')
<style>
.auto-grid { display:grid; gap:12px; }
.auto-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); padding:18px 20px; display:flex; align-items:center; gap:16px; }
.auto-card.inactive { opacity:.6; }
.auto-icon { width:40px; height:40px; border-radius:var(--r-md); display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:18px; }
.auto-info { flex:1; min-width:0; }
.auto-name { font-weight:700; font-size:14px; color:var(--text-100); }
.auto-meta { font-size:12px; color:var(--text-300); margin-top:3px; display:flex; gap:8px; flex-wrap:wrap; }
.auto-actions { display:flex; gap:6px; align-items:center; }
.trigger-badge { display:inline-flex; padding:2px 8px; border-radius:99px; font-size:11px; font-weight:600; }
.t-any_post_comment { background:rgba(254,243,199,0.14); color:#F19D6A; }
.t-specific_post_comment { background:rgba(219,234,254,0.14); color:#748FE7; }
.t-dm_keyword { background:rgba(209,250,229,0.14); color:#65F5CD; }
.action-badge { display:inline-flex; padding:2px 8px; border-radius:99px; font-size:11px; font-weight:600; }
.a-send_dm { background:rgba(237,233,254,0.14); color:#A175E6; }
.a-reply_comment { background:rgba(252,231,243,0.14); color:#E675A2; }
.toggle-switch { position:relative; display:inline-block; width:36px; height:20px; }
.toggle-switch input { opacity:0; width:0; height:0; }
.toggle-slider { position:absolute; cursor:pointer; inset:0; background:rgba(209,213,219,0.14); border-radius:99px; transition:.2s; }
.toggle-slider:before { content:''; position:absolute; width:14px; height:14px; left:3px; top:3px; background:#fff; border-radius:50%; transition:.2s; }
input:checked + .toggle-slider { background:var(--accent); }
input:checked + .toggle-slider:before { transform:translateX(16px); }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Automations</h1>
        <p class="page-sub">Set up triggers: comment on post → auto DM reply</p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('tenant.instagram.index') }}" class="btn btn-ghost">Back</a>
        <a href="{{ route('tenant.instagram.automations.create') }}" class="btn btn-primary">+ New Automation</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('success') }}</div>
@endif

@if($automations->isEmpty())
    <div class="card" style="padding:60px;text-align:center;">
        <div style="font-size:40px;margin-bottom:12px;">⚡</div>
        <div style="font-size:16px;font-weight:600;color:var(--text-100);margin-bottom:6px;">No automations yet</div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:20px;">Create your first automation to automatically reply to comments or DMs</div>
        <a href="{{ route('tenant.instagram.automations.create') }}" class="btn btn-primary">Create Automation</a>
    </div>
@else
    <div class="auto-grid">
        @foreach($automations as $automation)
        <div class="auto-card {{ $automation->is_active ? '' : 'inactive' }}" id="auto-{{ $automation->id }}">
            <div class="auto-icon" style="background:rgba(252,231,243,0.14);">
                @if($automation->action_type === 'send_dm')
                    <span>💬</span>
                @elseif($automation->action_type === 'reply_comment')
                    <span>💭</span>
                @else
                    <span>🔗</span>
                @endif
            </div>
            <div class="auto-info">
                <div class="auto-name">{{ $automation->name }}</div>
                <div class="auto-meta">
                    <span class="trigger-badge t-{{ $automation->trigger_type }}">{{ str_replace('_',' ',ucfirst($automation->trigger_type)) }}</span>
                    <span class="action-badge a-{{ $automation->action_type }}">{{ str_replace('_',' ',ucfirst($automation->action_type)) }}</span>
                    @if(!empty($automation->trigger_keywords))
                        <span>Keywords: {{ implode(', ', $automation->trigger_keywords) }}</span>
                    @endif
                    <span>Triggered: {{ $automation->triggered_count }}x</span>
                </div>
            </div>
            <div class="auto-actions">
                <label class="toggle-switch" title="{{ $automation->is_active ? 'Disable' : 'Enable' }}">
                    <input type="checkbox" {{ $automation->is_active ? 'checked' : '' }}
                        onchange="toggleAuto({{ $automation->id }}, this)">
                    <span class="toggle-slider"></span>
                </label>
                <a href="{{ route('tenant.instagram.automations.edit', $automation->id) }}" class="btn btn-ghost btn-sm">Edit</a>
                <form method="POST" action="{{ route('tenant.instagram.automations.destroy', $automation->id) }}"
                    data-confirm="Delete this automation?" data-confirm-ok="Delete">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger);">Delete</button>
                </form>
            </div>
        </div>
        @endforeach
    </div>

    <div style="margin-top:16px;">
        {{ $automations->links() }}
    </div>
@endif
@endsection

@push('scripts')
<script>
function toggleAuto(id, checkbox) {
    fetch(`/instagram/automations/${id}/toggle`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        const card = document.getElementById('auto-' + id);
        card.classList.toggle('inactive', !data.is_active);
        checkbox.checked = data.is_active;
    })
    .catch(() => { checkbox.checked = !checkbox.checked; });
}
</script>
@endpush
