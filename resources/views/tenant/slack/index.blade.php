@extends('layouts.app')
@section('title', 'Slack Notifications')

@push('styles')
<style>
.sk-wrap { max-width:760px; }

.sk-card {
    background:var(--bg-surface);
    border:1px solid var(--border-default);
    border-radius:var(--r-lg);
    overflow:hidden;
    margin-bottom:20px;
}
.sk-card-head {
    padding:18px 24px;
    border-bottom:1px solid var(--border-subtle);
    display:flex; align-items:center; justify-content:space-between;
}
.sk-card-title { font-size:15px; font-weight:700; color:var(--text-100); }
.sk-card-sub   { font-size:13px; color:var(--text-300); margin-top:2px; }
.sk-card-body  { padding:24px; }

.field { display:flex; flex-direction:column; gap:7px; }
.field-label { font-size:12.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.3px; }
.field-input {
    padding:10px 13px;
    background:var(--bg-input);
    border:1.5px solid var(--border-default);
    border-radius:var(--r-sm);
    color:var(--text-100);
    font-family:var(--font); font-size:14px; outline:none;
    transition:border-color .15s var(--ease), box-shadow .15s var(--ease);
}
.field-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
.field-input::placeholder { color:var(--text-400); }
.field-error { font-size:12px; color:var(--red); font-weight:500; }

.row-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
.btn-sm {
    display:inline-flex; align-items:center; gap:5px;
    padding:8px 14px;
    border-radius:var(--r-sm);
    font-size:13px; font-weight:600;
    cursor:pointer; border:1.5px solid;
    font-family:var(--font);
    transition:all .15s;
    background:none;
}
.btn-sm svg { width:14px; height:14px; }
.btn-outline { border-color:var(--border-default); color:var(--text-200); }
.btn-outline:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-dim); }
.btn-danger { border-color:rgba(255,82,87,.3); color:var(--red); }
.btn-danger:hover { background:var(--red-dim); border-color:var(--red); }
.btn-primary { background:var(--accent); color:#fff; border-color:var(--accent); }
.btn-primary:hover { opacity:.88; }
.btn-primary:disabled { opacity:.6; cursor:not-allowed; }

.badge {
    display:inline-flex; align-items:center; gap:5px;
    padding:3px 9px; border-radius:50px; font-size:11.5px; font-weight:600;
}
.badge-active   { background:var(--green-dim); color:var(--green); }
.badge-inactive { background:var(--bg-elevated); color:var(--text-300); border:1px solid var(--border-default); }
.badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

.alert {
    display:flex; align-items:center; gap:10px;
    padding:12px 16px; border-radius:var(--r-sm);
    font-size:13px; font-weight:500; margin-bottom:20px;
}
.alert-success { background:var(--green-dim); color:var(--green); border:1px solid rgba(45,212,160,.25); }
.alert-error   { background:var(--red-dim);   color:var(--red);   border:1px solid rgba(255,82,87,.25); }
.alert svg { width:16px; height:16px; flex-shrink:0; }

.test-result { font-size:12.5px; font-weight:600; margin-left:6px; }
.test-result.ok  { color:var(--green); }
.test-result.bad { color:var(--red); }

.url-display {
    font-family:monospace; font-size:12.5px;
    background:var(--bg-elevated);
    border:1px solid var(--border-subtle);
    border-radius:var(--r-sm);
    padding:8px 12px; color:var(--text-200);
    overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
    margin-bottom:14px;
}

.steps-list { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:14px; }
.step-item  { display:flex; gap:12px; align-items:flex-start; }
.step-num   { width:24px; height:24px; border-radius:50%; background:var(--accent); color:#fff; font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:1px; }
.step-title { font-size:13.5px; font-weight:700; color:var(--text-100); margin-bottom:2px; }
.step-desc  { font-size:12.5px; color:var(--text-300); line-height:1.6; }
.step-desc a { color:var(--accent); }

.tip-box {
    display:flex; gap:10px; padding:12px 14px; border-radius:var(--r-md);
    font-size:12.5px; line-height:1.6; margin-top:16px;
    background:rgba(255,122,89,.06); border:1px solid rgba(255,122,89,.15); color:var(--text-200);
}
.tip-box svg { flex-shrink:0; margin-top:1px; width:16px; height:16px; color:var(--accent); }
</style>
@endpush

@section('content')

@if(session('success'))
<div class="alert alert-success">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    {{ session('success') }}
</div>
@endif
@if($errors->any())
<div class="alert alert-error">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
    </svg>
    {{ $errors->first() }}
</div>
@endif

<div class="page-head">
    <div>
        <div class="page-title">Slack Notifications</div>
        <div style="font-size:13px;color:var(--text-300);margin-top:2px;">
            Send CRM notifications (new lead, deal won, etc.) to a Slack channel.
        </div>
    </div>
</div>

<div class="sk-wrap">

    {{-- ── Setup guide ─────────────────────────────────────────────── --}}
    <div class="sk-card">
        <div class="sk-card-head">
            <div>
                <div class="sk-card-title">How to Get Your Slack Webhook URL</div>
                <div class="sk-card-sub">Takes about 2 minutes, no coding needed.</div>
            </div>
        </div>
        <div class="sk-card-body">
            <ol class="steps-list">
                <li class="step-item">
                    <div class="step-num">1</div>
                    <div>
                        <div class="step-title">Create a Slack App</div>
                        <div class="step-desc">Go to <a href="https://api.slack.com/apps" target="_blank" rel="noopener">api.slack.com/apps</a> → "Create New App" → "From scratch" → give it a name (e.g. "CRM Alerts") and pick your workspace.</div>
                    </div>
                </li>
                <li class="step-item">
                    <div class="step-num">2</div>
                    <div>
                        <div class="step-title">Enable Incoming Webhooks</div>
                        <div class="step-desc">In the app settings sidebar, open "Incoming Webhooks" and toggle it <b>On</b>.</div>
                    </div>
                </li>
                <li class="step-item">
                    <div class="step-num">3</div>
                    <div>
                        <div class="step-title">Add a webhook to your workspace</div>
                        <div class="step-desc">Click "Add New Webhook to Workspace", choose the channel that should receive CRM alerts, and click "Allow".</div>
                    </div>
                </li>
                <li class="step-item">
                    <div class="step-num">4</div>
                    <div>
                        <div class="step-title">Copy the Webhook URL</div>
                        <div class="step-desc">Slack will show a URL starting with <code>https://hooks.slack.com/services/...</code> — copy it.</div>
                    </div>
                </li>
                <li class="step-item">
                    <div class="step-num">5</div>
                    <div>
                        <div class="step-title">Paste it below and save</div>
                        <div class="step-desc">Then click "Send Test Message" to confirm it's working before relying on it.</div>
                    </div>
                </li>
            </ol>

            <div class="tip-box">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                Which notifications get sent to Slack is controlled per-type in
                <a href="{{ route('tenant.notifications.preferences') }}" style="color:var(--accent)">Notification Preferences</a> — toggle the Slack column there once this is set up.
            </div>
        </div>
    </div>

    {{-- ── Webhook config ──────────────────────────────────────────── --}}
    <div class="sk-card">
        <div class="sk-card-head">
            <div>
                <div class="sk-card-title">Slack Webhook</div>
                <div class="sk-card-sub">
                    @if($config)
                        @if($config->is_active)
                            <span class="badge badge-active"><span class="badge-dot"></span> Active</span>
                        @else
                            <span class="badge badge-inactive"><span class="badge-dot"></span> Inactive</span>
                        @endif
                        @if($config->last_tested_at)
                            <span style="margin-left:8px;color:var(--text-400)">Last tested {{ $config->last_tested_at->diffForHumans() }}</span>
                        @endif
                    @else
                        Not configured yet
                    @endif
                </div>
            </div>
        </div>
        <div class="sk-card-body">

            @if($config)
                <div class="url-display" title="{{ $config->webhook_url }}">
                    {{ substr($config->webhook_url, 0, 45) }}••••••••••
                </div>
            @endif

            <form method="POST" action="{{ route('tenant.slack.store') }}" style="margin-bottom:16px;">
                @csrf
                <div class="field" style="margin-bottom:12px;">
                    <label class="field-label">Slack Webhook URL <span style="color:var(--red)">*</span></label>
                    <input type="url" name="webhook_url" class="field-input"
                           placeholder="https://hooks.slack.com/services/T00/B00/XXXX"
                           value="{{ old('webhook_url', $config->webhook_url ?? '') }}" required>
                    @error('webhook_url')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>
                <button type="submit" class="btn-sm btn-primary">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                    {{ $config ? 'Update' : 'Save' }} Webhook
                </button>
            </form>

            @if($config)
            <div class="row-actions">
                <button type="button" class="btn-sm btn-outline" id="testBtn" onclick="sendSlackTest()">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                    </svg>
                    Send Test Message
                </button>
                <span id="testResult" class="test-result"></span>

                <form method="POST" action="{{ route('tenant.slack.toggle') }}" style="margin-left:auto;">
                    @csrf
                    <button type="submit" class="btn-sm btn-outline">
                        {{ $config->is_active ? 'Deactivate' : 'Activate' }}
                    </button>
                </form>

                <form method="POST" action="{{ route('tenant.slack.destroy') }}"
                      data-confirm="Remove Slack webhook? CRM will stop sending Slack notifications." data-confirm-ok="Remove">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-sm btn-danger">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                        </svg>
                        Remove
                    </button>
                </form>
            </div>
            @endif

        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
function sendSlackTest() {
    const btn    = document.getElementById('testBtn');
    const result = document.getElementById('testResult');

    btn.disabled = true;
    result.textContent = 'Sending…';
    result.className = 'test-result';

    fetch("{{ route('tenant.slack.test') }}", {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            result.textContent = '✓ Sent — check your Slack channel';
            result.className = 'test-result ok';
        } else {
            result.textContent = '✗ Failed: ' + (data.message || ('HTTP ' + data.status));
            result.className = 'test-result bad';
        }
    })
    .catch(err => {
        result.textContent = '✗ Request failed: ' + err.message;
        result.className = 'test-result bad';
    })
    .finally(() => { btn.disabled = false; });
}
</script>
@endpush
