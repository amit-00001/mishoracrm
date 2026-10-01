@extends('layouts.app')
@section('title', 'WhatsApp Gateway — Superadmin')

@push('styles')
<style>
.gw-wrap { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:24px; align-items:start; }
@media(max-width:900px){ .gw-wrap { grid-template-columns:1fr; } }

.gw-status {
    display:flex; align-items:center; justify-content:space-between; gap:16px; flex-wrap:wrap;
    border-radius:var(--r-lg); padding:18px 20px; margin-bottom:24px;
    border:1.5px solid var(--border-default); background:var(--bg-surface);
}
.gw-status.on  { border-color:rgba(45,212,160,0.45); background:var(--green-dim); }
.gw-status-title { font-weight:700; font-size:15px; color:var(--text-100); display:flex; align-items:center; gap:8px; }
.gw-status-sub   { font-size:13px; color:var(--text-300); margin-top:4px; line-height:1.5; max-width:620px; }
.gw-dot { width:10px; height:10px; border-radius:50%; background:var(--text-400); flex-shrink:0; }
.gw-status.on .gw-dot { background:var(--green); }

.gw-url {
    display:flex; align-items:center; gap:8px;
    background:var(--bg-subtle); border:1px solid var(--border-subtle);
    border-radius:var(--r-md); padding:8px 12px; margin:6px 0 4px;
}
.gw-url code { font-family:var(--mono); font-size:12px; color:var(--text-200); word-break:break-all; flex:1; }
.gw-copy { cursor:pointer; background:none; border:none; color:var(--text-400); padding:2px 4px; }
.gw-copy:hover { color:var(--accent); }

.gw-steps { margin:0; padding-left:18px; display:flex; flex-direction:column; gap:10px; font-size:13px; color:var(--text-300); line-height:1.55; }
.gw-steps strong { color:var(--text-200); }
.gw-steps code { font-family:var(--mono); font-size:12px; background:var(--bg-subtle); border:1px solid var(--border-subtle); border-radius:4px; padding:1px 6px; color:var(--text-200); }

.gw-result { font-size:12.5px; margin-top:10px; display:none; }
.gw-result.ok  { color:var(--green); }
.gw-result.bad { color:var(--red); }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">WhatsApp Gateway</h1>
        <p class="page-sub">Use another CRM's approved Meta app for WhatsApp until ours is verified — switch it off any time to go back to direct Meta</p>
    </div>
</div>

@if($errors->any())
    <div class="alert alert-error" style="margin-bottom:20px;">{{ $errors->first() }}</div>
@endif

{{-- ── Master switch ───────────────────────────────────────────── --}}
<div class="gw-status {{ $enabled ? 'on' : '' }}">
    <div>
        <div class="gw-status-title">
            <span class="gw-dot"></span>
            @if($enabled)
                Gateway is ON
            @else
                Gateway is OFF
            @endif
        </div>
        <div class="gw-status-sub">
            @if($enabled)
                New tenants connect WhatsApp through the gateway, and their messages are sent and received through it.
                @if($gatewayTenants > 0)
                    <strong>{{ $gatewayTenants }}</strong> {{ \Illuminate\Support\Str::plural('tenant', $gatewayTenants) }} connected this way — they stop working (shown as "not connected") the moment you switch it off, and must reconnect directly with Meta.
                @endif
                Tenants already connected straight to Meta are not touched.
            @else
                WhatsApp works exactly as it did before — every tenant uses the direct Meta connection. Nothing below has any effect until you turn this on.
            @endif
        </div>
    </div>
    <form method="POST" action="{{ route('superadmin.platform-settings.whatsapp-gateway.toggle') }}"
          @if($enabled) onsubmit="return confirm('Turn the WhatsApp Gateway OFF? Tenants connected through it will show as not connected until they reconnect directly with Meta.');" @endif>
        @csrf
        <button type="submit" class="btn {{ $enabled ? 'btn-secondary' : 'btn-primary' }}">
            {{ $enabled ? 'Turn OFF' : 'Turn ON' }}
        </button>
    </form>
</div>

<div class="gw-wrap">

    {{-- ── Credentials ─────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">Gateway credentials</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ route('superadmin.platform-settings.whatsapp-gateway.save') }}">
                @csrf

                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Gateway URL</label>
                    <input type="url" name="base_url" class="form-input" required
                        value="{{ old('base_url', $base_url) }}"
                        placeholder="https://your-gateway-crm.com">
                    <span class="form-hint">The CRM that runs the gateway. <code>/api/v1/gateway</code> is added automatically.</span>
                </div>

                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">API key</label>
                    <input type="password" name="api_key" class="form-input" autocomplete="off"
                        placeholder="{{ $has_api_key ? '••••••• (saved — leave blank to keep)' : 'gw_…' }}">
                    <span class="form-hint">Printed once by <code>php artisan gateway:client-create</code>.</span>
                </div>

                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Webhook secret</label>
                    <input type="password" name="webhook_secret" class="form-input" autocomplete="off"
                        placeholder="{{ $has_secret ? '••••••• (saved — leave blank to keep)' : 'whsec_…' }}">
                    <span class="form-hint">Printed together with the API key. Used to verify every event the gateway sends us.</span>
                </div>

                <div class="form-group" style="margin-bottom:20px;">
                    <label class="form-label">Workspace id prefix</label>
                    <input type="text" name="workspace_prefix" class="form-input"
                        value="{{ old('workspace_prefix', $prefix) }}" placeholder="tenant-">
                    <span class="form-hint">Each tenant becomes the workspace <code>{{ $prefix }}&lt;tenant id&gt;</code>. Change this only if another environment (local / staging) shares the same API key; tenants already created keep their id.</span>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">Save credentials</button>
            </form>

            <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border-subtle);display:flex;gap:10px;flex-wrap:wrap;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="gwAction('{{ route('superadmin.platform-settings.whatsapp-gateway.test') }}', this)" @disabled(!$has_api_key || !$base_url)>Test connection</button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="gwAction('{{ route('superadmin.platform-settings.whatsapp-gateway.register-webhook') }}', this)" @disabled(!$has_api_key || !$base_url)>Register webhook URL with gateway</button>
            </div>
            <div class="gw-result" id="gwResult"></div>
        </div>
    </div>

    {{-- ── Setup guide ─────────────────────────────────────────── --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">Setup</h3></div>
        <div class="card-body">
            <ol class="gw-steps">
                <li>
                    <strong>Get credentials</strong> — on the gateway CRM's server run
                    <code>php artisan gateway:client-create "Mishora CRM" --webhook-url={{ $webhook_url }}</code>
                    and paste the API key (<code>gw_…</code>) and webhook secret (<code>whsec_…</code>) here. Both are shown only once.
                </li>
                <li>
                    <strong>Webhook URL</strong> — the gateway must POST its events here (use <em>Register webhook URL</em> to set it for you):
                    <div class="gw-url">
                        <code id="gwWebhook">{{ $webhook_url }}</code>
                        <button type="button" class="gw-copy" onclick="navigator.clipboard.writeText(document.getElementById('gwWebhook').textContent.trim())" title="Copy">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </button>
                    </div>
                    It must be a public <strong>https</strong> address.
                </li>
                <li><strong>Test connection</strong>, then <strong>Turn ON</strong>.</li>
                <li>
                    <strong>Tenants</strong> open <em>WhatsApp → API Settings</em> and click <em>Connect WhatsApp</em> — they sign in with Facebook on Meta's own screen and pick or create their number. Nothing else to configure per tenant.
                </li>
            </ol>

            <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border-subtle);font-size:13px;color:var(--text-300);line-height:1.6;">
                <strong style="color:var(--text-200);">When our own Meta app goes live:</strong> turn the gateway OFF. WhatsApp goes back to the direct Meta connection (Superadmin → Meta App); tenants that were connected through the gateway reconnect there once.
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function gwAction(url, btn) {
    const out = document.getElementById('gwResult');
    const label = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Working…';
    out.style.display = 'none';

    fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(d => { out.className = 'gw-result ' + (d.success ? 'ok' : 'bad'); out.textContent = d.message; })
        .catch(() => { out.className = 'gw-result bad'; out.textContent = 'Request failed.'; })
        .finally(() => { out.style.display = 'block'; btn.disabled = false; btn.textContent = label; });
}
</script>
@endpush
