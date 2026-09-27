@extends('layouts.app')
@section('title', $integration->getLabel() . ' — Integration Setup')

@push('styles')
<style>
.setup-wrap{max-width:680px}
.setup-header{display:flex;align-items:center;gap:16px;margin-bottom:28px;padding:22px;background:var(--bg-surface);border:1.5px solid var(--border-default);border-radius:18px}
.setup-icon{width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:22px;color:#fff;flex-shrink:0}
.setup-title{font-size:18px;font-weight:800;color:var(--text-100)}
.setup-subtitle{font-size:13px;color:var(--text-300);margin-top:3px}
.setup-section{background:var(--bg-surface);border:1.5px solid var(--border-default);border-radius:18px;padding:22px;margin-bottom:18px}
.setup-section-title{font-size:13px;font-weight:700;color:var(--text-200);text-transform:uppercase;letter-spacing:.08em;margin-bottom:16px;padding-bottom:10px;border-bottom:1px solid var(--border-default)}
.webhook-url-box{display:flex;align-items:center;gap:8px;background:var(--bg-elevated);border:1.5px solid var(--border-default);border-radius:10px;padding:10px 14px}
.webhook-url-text{font-family:var(--mono);font-size:12px;color:var(--text-100);word-break:break-all;flex:1}
.btn-copy{padding:6px 12px;border-radius:7px;background:var(--accent);color:#fff;font-size:12px;font-weight:600;border:none;cursor:pointer;flex-shrink:0;transition:opacity .15s}
.btn-copy:hover{opacity:.85}
.info-box{background:var(--bg-elevated);border-left:3px solid var(--accent);border-radius:0 10px 10px 0;padding:12px 16px;font-size:13px;color:var(--text-200);line-height:1.6;margin-bottom:16px}
.info-box ol{margin:6px 0 0 16px;padding:0}
.info-box li{margin-bottom:4px}
.toggle-row{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-top:1px solid var(--border-default);margin-top:10px}
.toggle-label{font-size:14px;font-weight:600;color:var(--text-100)}
.toggle-desc{font-size:12px;color:var(--text-300);margin-top:2px}
.switch{position:relative;display:inline-block;width:46px;height:25px}
.switch input{opacity:0;width:0;height:0}
.slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#cbd5e1;border-radius:25px;transition:.3s}
.slider:before{position:absolute;content:"";height:19px;width:19px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
input:checked+.slider{background:var(--accent)}
input:checked+.slider:before{transform:translateX(21px)}
</style>
@endpush

@section('content')
@php
    $info = $integration->getPlatformInfo();
@endphp

<div class="page-header">
    <div style="display:flex;align-items:center;gap:10px">
        <a href="{{ route('tenant.lead-integrations.index') }}" class="btn-back">← Back</a>
        <h1 class="page-title">{{ $info['label'] }} Integration</h1>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="setup-wrap">

    {{-- ── Header Card ─────────────────────────────────────────── --}}
    <div class="setup-header">
        <div class="setup-icon" style="background:{{ $info['color'] }}">
            <i class="{{ $info['icon'] }}"></i>
        </div>
        <div>
            <div class="setup-title">{{ $info['label'] }}</div>
            <div class="setup-subtitle">
                {{ $info['type'] === 'webhook' ? 'Webhook-based — leads arrive automatically when someone fills a form.' : 'API Polling — we fetch leads from your account every 30 minutes.' }}
            </div>
        </div>
    </div>

    {{-- ── Webhook URL (for webhook-based platforms) ──────────── --}}
    @if($integration->isWebhook())
    <div class="setup-section">
        <div class="setup-section-title">Your Webhook URL</div>
        <div class="info-box">
            Copy this URL and paste it in your {{ $info['label'] }} developer/seller settings as the webhook endpoint.
        </div>
        <div class="webhook-url-box">
            <span class="webhook-url-text" id="webhook-url">{{ $integration->webhookUrl() }}</span>
            <button class="btn-copy" onclick="copyUrl()">Copy</button>
        </div>
        <div style="margin-top:10px;display:flex;gap:8px">
            <form method="POST" action="{{ route('tenant.lead-integrations.regenerate', $platform) }}" data-confirm="This will invalidate your old webhook URL. You will need to update it in {{ $info['label'] }}. Continue?">
                @csrf
                <button class="btn btn-sm btn-outline-secondary">Regenerate URL</button>
            </form>
        </div>
    </div>
    @endif

    {{-- ── Platform-specific How-To ─────────────────────────── --}}
    @if($platform === 'meta_lead_ads')
    <div class="setup-section">
        <div class="setup-section-title">How to Connect Meta Lead Ads</div>
        <div class="info-box">
            <ol>
                <li>Go to <strong>Meta for Developers</strong> → Create or open your App</li>
                <li>Add the <strong>Webhooks</strong> product to your app</li>
                <li>Subscribe to the <strong>leadgen</strong> field on your Facebook Page</li>
                <li>Set the Callback URL to the webhook URL above</li>
                <li>Set a <strong>Verify Token</strong> (any secret string you choose) and enter it below</li>
                <li>Copy your <strong>Page Access Token</strong> from the Graph API Explorer</li>
                <li>Copy your <strong>App Secret</strong> from App Settings → Basic</li>
            </ol>
        </div>
    </div>
    @elseif($platform === 'indiamart')
    <div class="setup-section">
        <div class="setup-section-title">How to Get IndiaMART API Key</div>
        <div class="info-box">
            <ol>
                <li>Login to <strong>seller.indiamart.com</strong></li>
                <li>Go to <strong>My Account → Manage Account → CRM</strong></li>
                <li>Click on <strong>Lead Manager API</strong></li>
                <li>Generate or copy your <strong>API Key (GLUSR Code)</strong></li>
                <li>Enter your registered <strong>Mobile Number</strong> below</li>
            </ol>
        </div>
    </div>
    @elseif($platform === 'justdial')
    <div class="setup-section">
        <div class="setup-section-title">How to Connect JustDial</div>
        <div class="info-box">
            <ol>
                <li>Login to your <strong>JustDial Vendor Panel</strong></li>
                <li>Go to <strong>Settings → API Integration</strong></li>
                <li>Enter the webhook URL above as your <strong>Lead Push URL</strong></li>
                <li>If JustDial provides a secret key, enter it below for security</li>
                <li>JustDial will now push leads to your CRM automatically</li>
            </ol>
        </div>
    </div>
    @endif

    {{-- ── Credentials Form ─────────────────────────────────── --}}
    <form method="POST" action="{{ route('tenant.lead-integrations.save', $platform) }}">
        @csrf

        <div class="setup-section">
            <div class="setup-section-title">Credentials & Settings</div>

            @if($platform === 'meta_lead_ads')
                <div class="form-group">
                    <label class="form-label">Verify Token <span class="req">*</span></label>
                    <input type="text" name="verify_token" class="form-control"
                           value="{{ $integration->getCredential('verify_token') }}"
                           placeholder="Any secret string you set in Facebook webhook config">
                    <div class="form-hint">Must match exactly what you set in Meta Webhooks → Verify Token</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Page Access Token <span class="req">*</span></label>
                    <input type="password" name="page_access_token" class="form-control"
                           value="{{ $integration->getCredential('page_access_token') ? '••••••••' : '' }}"
                           placeholder="Long-lived Page Access Token from Graph API">
                </div>
                <div class="form-group">
                    <label class="form-label">App Secret</label>
                    <input type="password" name="app_secret" class="form-control"
                           value="{{ $integration->getCredential('app_secret') ? '••••••••' : '' }}"
                           placeholder="From App Settings → Basic → App Secret">
                    <div class="form-hint">Used to verify webhook signature. Recommended but optional.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Form IDs (optional filter)</label>
                    <input type="text" name="form_ids" class="form-control"
                           value="{{ implode(', ', $integration->settings['form_ids'] ?? []) }}"
                           placeholder="123456789, 987654321">
                    <div class="form-hint">Comma-separated Facebook Lead Form IDs. Leave empty to accept leads from all forms.</div>
                </div>

            @elseif($platform === 'indiamart')
                <div class="form-group">
                    <label class="form-label">API Key (GLUSR Code) <span class="req">*</span></label>
                    <input type="text" name="glusr_usr_given_code" class="form-control"
                           value="{{ $integration->getCredential('glusr_usr_given_code') }}"
                           placeholder="Your IndiaMART CRM API Key">
                </div>
                <div class="form-group">
                    <label class="form-label">Registered Mobile Number <span class="req">*</span></label>
                    <input type="text" name="mobile" class="form-control"
                           value="{{ $integration->getCredential('mobile') }}"
                           placeholder="10-digit mobile number">
                </div>

            @elseif($platform === 'justdial')
                <div class="form-group">
                    <label class="form-label">Secret Key (optional)</label>
                    <input type="text" name="secret_key" class="form-control"
                           value="{{ $integration->getCredential('secret_key') }}"
                           placeholder="Secret key provided by JustDial for request verification">
                    <div class="form-hint">If JustDial provides a key, enter it here to validate incoming requests.</div>
                </div>

            @elseif(in_array($platform, ['tradeindia', 'sulekha']))
                <div class="form-group">
                    <label class="form-label">API Key</label>
                    <input type="text" name="api_key" class="form-control"
                           value="{{ $integration->getCredential('api_key') }}"
                           placeholder="API key for request verification (if provided)">
                </div>
            @endif

            {{-- ── Active Toggle ──────────────────────────────── --}}
            <div class="toggle-row">
                <div>
                    <div class="toggle-label">Enable Integration</div>
                    <div class="toggle-desc">Turn off to pause lead imports without deleting settings.</div>
                </div>
                <label class="switch">
                    <input type="checkbox" name="is_active" value="1" {{ $integration->is_active ? 'checked' : '' }}>
                    <span class="slider"></span>
                </label>
            </div>
        </div>

        <div style="display:flex;gap:10px">
            <button type="submit" class="btn btn-primary">Save Settings</button>

            @if($platform === 'indiamart')
            <button type="button" class="btn btn-outline-secondary" onclick="testConnection()">
                Test Connection
            </button>
            @endif

            <a href="{{ route('tenant.lead-integrations.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function copyUrl() {
    const url = document.getElementById('webhook-url').textContent;
    navigator.clipboard.writeText(url).then(() => {
        const btn = document.querySelector('.btn-copy');
        btn.textContent = 'Copied!';
        setTimeout(() => btn.textContent = 'Copy', 2000);
    });
}

function testConnection() {
    const btn = event.target;
    btn.textContent = 'Testing...';
    btn.disabled = true;

    fetch('{{ route('tenant.lead-integrations.test', $platform) }}', {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message);
        btn.textContent = 'Test Connection';
        btn.disabled = false;
    })
    .catch(() => {
        alert('Request failed.');
        btn.textContent = 'Test Connection';
        btn.disabled = false;
    });
}
</script>
@endpush
