@extends('layouts.app')
@section('title', 'WhatsApp API Settings')

@push('styles')
<style>
.gw-card { background:var(--bg-surface); border:1.5px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; margin-bottom:20px; }
.gw-card-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:18px 20px 14px; border-bottom:1px solid var(--border-subtle); }
.gw-card-title { display:flex; align-items:center; gap:10px; font-weight:700; font-size:15px; color:var(--text-100); }
.gw-badge-on  { background:var(--green-dim); color:var(--green); border-radius:20px; padding:3px 10px; font-size:12px; font-weight:600; }
.gw-badge-off { background:var(--red-dim); color:var(--red); border-radius:20px; padding:3px 10px; font-size:12px; font-weight:600; }
.gw-body { padding:24px; }
.gw-lead { font-weight:700; font-size:16px; color:var(--text-100); margin-bottom:6px; }
.gw-sub  { font-size:13px; color:var(--text-300); line-height:1.5; margin-bottom:18px; max-width:560px; }
.gw-how { list-style:none; padding:0; margin:0 0 22px; display:flex; flex-direction:column; gap:12px; max-width:560px; }
.gw-how li { display:flex; gap:10px; align-items:flex-start; font-size:13px; color:var(--text-200); line-height:1.45; }
.gw-how .num { min-width:22px; height:22px; border-radius:50%; background:var(--green-dim); border:1.5px solid var(--green); color:var(--green); font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; margin-top:1px; }
.gw-how strong { color:var(--text-100); }
.gw-meta { display:flex; gap:24px; flex-wrap:wrap; margin:14px 0 20px; }
.gw-meta-item { font-size:12px; color:var(--text-300); }
.gw-meta-item strong { color:var(--text-200); display:block; font-size:11px; margin-bottom:2px; letter-spacing:.03em; text-transform:uppercase; }
.gw-actions { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
.gw-health { margin-top:16px; font-size:13px; line-height:1.6; display:none; }
.gw-health.ok  { color:var(--green); }
.gw-health.bad { color:var(--red); }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">WhatsApp Business API</h1>
        <p class="page-sub">Connect your WhatsApp Business number to send and receive messages from the CRM</p>
    </div>
    <a href="{{ route('tenant.whatsapp.index') }}" class="btn btn-ghost">← Back</a>
</div>

@if(!empty($notice))
    <div class="alert alert-{{ $notice[0] === 'success' ? 'success' : 'error' }}" style="margin-bottom:20px;">{{ $notice[1] }}</div>
@endif

<div class="gw-card">
    <div class="gw-card-head">
        <div class="gw-card-title">
            <svg viewBox="0 0 24 24" fill="#25d366" style="width:22px;height:22px;flex-shrink:0;">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                <path d="M12.004 2C6.477 2 2 6.477 2 12.004c0 1.773.465 3.48 1.348 4.985L2 22l5.13-1.34A9.953 9.953 0 0012.004 22C17.527 22 22 17.523 22 12c0-5.522-4.473-10-9.996-10z" fill-rule="evenodd" clip-rule="evenodd"/>
            </svg>
            WhatsApp connection
        </div>
        @if($settings->is_connected)
            <span class="gw-badge-on">● Connected</span>
        @else
            <span class="gw-badge-off">● Not Connected</span>
        @endif
    </div>

    <div class="gw-body">
        @if($settings->is_connected)
            <div class="gw-lead">WhatsApp Business connected</div>
            <div class="gw-sub">Messages you send from the CRM go out from this number, and customer replies come back into the CRM.</div>

            <div class="gw-meta">
                <div class="gw-meta-item"><strong>Connected number</strong>{{ $settings->display_phone_number ?? '—' }}</div>
                <div class="gw-meta-item"><strong>Verified name</strong>{{ $settings->verified_name ?? '—' }}</div>
                <div class="gw-meta-item"><strong>Phone number ID</strong>{{ $settings->phone_number_id ?? '—' }}</div>
                <div class="gw-meta-item"><strong>WABA ID</strong>{{ $settings->waba_id ?? '—' }}</div>
            </div>

            <div class="gw-actions">
                <button type="button" class="btn btn-ghost btn-sm" id="healthBtn" onclick="checkHealth()">Check connection</button>

                <form method="POST" action="{{ route('tenant.whatsapp.gateway.sync') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm">Refresh status</button>
                </form>

                <form method="POST" action="{{ route('tenant.whatsapp.gateway.disconnect') }}"
                      onsubmit="return confirm('Disconnect this WhatsApp number from the CRM? Messages will stop sending and arriving until you connect again.');">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--red);">Disconnect</button>
                </form>
            </div>
            <div class="gw-health" id="healthResult"></div>
        @else
            <div class="gw-lead">Connect your WhatsApp Business number</div>
            <div class="gw-sub">It takes about a minute. You sign in with Facebook on Meta's own screen — nothing needs to be copied or pasted.</div>

            <ul class="gw-how">
                <li><span class="num">1</span><span>Click <strong>Connect WhatsApp</strong> below — you are taken to a secure Meta page.</span></li>
                <li><span class="num">2</span><span><strong>Sign in with Facebook</strong> and choose or create the WhatsApp Business number to link.</span></li>
                <li><span class="num">3</span><span>You come straight back here with the number connected.</span></li>
            </ul>

            <div class="gw-actions">
                <form method="POST" action="{{ route('tenant.whatsapp.gateway.connect') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">Connect WhatsApp</button>
                </form>
                @if($settings->gateway_workspace_id)
                    <form method="POST" action="{{ route('tenant.whatsapp.gateway.sync') }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm">Refresh status</button>
                    </form>
                @endif
            </div>

            <p style="font-size:11px;color:var(--text-400);margin-top:16px;line-height:1.5;max-width:560px;">
                Use a number that is not already registered on another WhatsApp app or API. A number currently running on the WhatsApp Business phone app can only be linked if Meta allows it for that number.
            </p>
        @endif
    </div>
</div>

<form method="POST" action="{{ route('tenant.whatsapp.api-settings.save') }}">
    @csrf
    <div class="card">
        <div class="card-header"><h3 class="card-title">Chatbot</h3></div>
        <div class="card-body">
            <div class="form-group">
                <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;">
                    <input type="checkbox" name="chatbot_enabled" value="1" {{ $settings->chatbot_enabled ? 'checked' : '' }} style="width:18px;height:18px;margin-top:2px;flex-shrink:0;">
                    <div>
                        <div style="font-weight:600;font-size:14px;">Enable Chatbot</div>
                        <div style="font-size:12px;color:var(--text-300);margin-top:2px;">Keyword-based auto-reply to incoming messages</div>
                    </div>
                </label>
            </div>
        </div>
    </div>

    <div style="margin-top:20px;display:flex;justify-content:flex-end;">
        <button type="submit" class="btn btn-primary">Save Settings</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
function checkHealth() {
    const btn = document.getElementById('healthBtn');
    const out = document.getElementById('healthResult');
    btn.textContent = 'Checking…';
    btn.disabled = true;

    fetch('{{ route("tenant.whatsapp.api-settings.test") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (!data.success) {
            out.className = 'gw-health bad';
            out.textContent = 'Check failed: ' + (data.message || 'could not reach the connection.');
            return;
        }
        const a = data.account || {};
        const parts = [];
        if (a.display_phone_number) parts.push('Number: ' + a.display_phone_number);
        if (a.quality_rating) parts.push('Quality: ' + a.quality_rating);
        if (a.messaging_limit_tier) parts.push('Messaging limit: ' + a.messaging_limit_tier);
        if (a.problem) {
            out.className = 'gw-health bad';
            out.textContent = 'This number cannot send right now: ' + (typeof a.problem === 'string' ? a.problem : JSON.stringify(a.problem));
        } else {
            out.className = 'gw-health ok';
            out.textContent = 'Connection is healthy.' + (parts.length ? ' ' + parts.join(' · ') : '');
        }
    })
    .catch(() => { out.className = 'gw-health bad'; out.textContent = 'Request failed.'; })
    .finally(() => { out.style.display = 'block'; btn.textContent = 'Check connection'; btn.disabled = false; });
}
</script>
@endpush
