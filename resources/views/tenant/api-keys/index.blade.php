@extends('layouts.app')
@section('title', 'API Keys')

@push('styles')
<style>
/* ── Page ────────────────────────────────────────────────────────── */
.api-wrap { max-width:860px; }

/* ── Card ────────────────────────────────────────────────────────── */
.api-card {
    background:var(--bg-surface);
    border:1px solid var(--border-default);
    border-radius:var(--r-lg);
    overflow:hidden;
    margin-bottom:20px;
}
.api-card-head {
    padding:18px 24px;
    border-bottom:1px solid var(--border-subtle);
    display:flex; align-items:center; justify-content:space-between;
}
.api-card-title  { font-size:15px; font-weight:700; color:var(--text-100); }
.api-card-sub    { font-size:13px; color:var(--text-300); margin-top:2px; }
.api-card-body   { padding:24px; }

/* ── Generate form ───────────────────────────────────────────────── */
.gen-row {
    display:flex; gap:10px; align-items:flex-end;
}
.gen-row .field { flex:1; }
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

/* ── Keys table ──────────────────────────────────────────────────── */
.keys-table { width:100%; border-collapse:collapse; }
.keys-table th {
    padding:10px 16px;
    background:var(--bg-elevated);
    font-size:11.5px; font-weight:700;
    color:var(--text-300);
    text-transform:uppercase; letter-spacing:.4px;
    text-align:left;
    border-bottom:1px solid var(--border-subtle);
}
.keys-table td {
    padding:14px 16px;
    font-size:13.5px;
    color:var(--text-200);
    border-bottom:1px solid var(--border-subtle);
    vertical-align:middle;
}
.keys-table tr:last-child td { border-bottom:none; }
.keys-table tr:hover td { background:var(--bg-elevated); }

/* ── Key display ─────────────────────────────────────────────────── */
.key-box {
    display:flex; align-items:center; gap:8px;
}
.key-value {
    font-family:monospace; font-size:12.5px;
    background:var(--bg-elevated);
    border:1px solid var(--border-subtle);
    border-radius:var(--r-sm);
    padding:5px 10px;
    color:var(--text-100);
    letter-spacing:.3px;
    max-width:340px;
    overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
}
.copy-btn {
    background:none; border:1px solid var(--border-default);
    border-radius:var(--r-sm);
    padding:5px 8px;
    cursor:pointer; color:var(--text-300);
    display:flex; align-items:center; gap:4px;
    font-size:12px; font-weight:500;
    transition:all .15s;
    font-family:var(--font);
    flex-shrink:0;
}
.copy-btn:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-dim); }
.copy-btn svg { width:13px; height:13px; }

@media(max-width:640px) {
    .keys-table td[data-label="API Key"] { flex-direction:column; align-items:flex-start !important; }
    .key-box { width:100%; }
    .key-value { max-width:none; flex:1; min-width:0; }
}

/* ── Status badge ────────────────────────────────────────────────── */
.badge {
    display:inline-flex; align-items:center; gap:5px;
    padding:3px 9px;
    border-radius:50px;
    font-size:11.5px; font-weight:600;
}
.badge-active   { background:var(--green-dim); color:var(--green); }
.badge-inactive { background:var(--bg-elevated); color:var(--text-300); border:1px solid var(--border-default); }
.badge-dot { width:6px; height:6px; border-radius:50%; background:currentColor; }

/* ── Row actions ─────────────────────────────────────────────────── */
.row-actions { display:flex; align-items:center; gap:6px; }
.btn-sm {
    display:inline-flex; align-items:center; gap:5px;
    padding:5px 11px;
    border-radius:var(--r-sm);
    font-size:12px; font-weight:600;
    cursor:pointer; border:1.5px solid;
    font-family:var(--font);
    transition:all .15s;
    background:none;
}
.btn-sm svg { width:13px; height:13px; }
.btn-outline { border-color:var(--border-default); color:var(--text-200); }
.btn-outline:hover { border-color:var(--accent); color:var(--accent); background:var(--accent-dim); }
.btn-danger { border-color:rgba(255,82,87,.3); color:var(--red); }
.btn-danger:hover { background:var(--red-dim); border-color:var(--red); }
.btn-primary {
    background:var(--accent); color:#fff; border-color:var(--accent);
    padding:10px 18px; font-size:13.5px;
}
.btn-primary:hover { opacity:.88; }

/* ── Empty state ─────────────────────────────────────────────────── */
.empty-keys {
    text-align:center; padding:48px 24px;
    color:var(--text-300); font-size:14px;
}
.empty-keys svg { width:40px; height:40px; margin:0 auto 12px; display:block; opacity:.4; }

/* ── Alert ───────────────────────────────────────────────────────── */
.alert {
    display:flex; align-items:center; gap:10px;
    padding:12px 16px; border-radius:var(--r-sm);
    font-size:13px; font-weight:500;
    margin-bottom:20px;
}
.alert-success { background:var(--green-dim); color:var(--green); border:1px solid rgba(45,212,160,.25); }
.alert-error   { background:var(--red-dim);   color:var(--red);   border:1px solid rgba(255,82,87,.25); }
.alert svg { width:16px; height:16px; flex-shrink:0; }

/* ── Docs panel ──────────────────────────────────────────────────── */
.docs-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
@media(max-width:640px) { .docs-grid { grid-template-columns:1fr; } }

.doc-block {
    background:var(--bg-elevated);
    border:1px solid var(--border-subtle);
    border-radius:var(--r-md);
    padding:16px;
}
.doc-block-title {
    font-size:12px; font-weight:700; color:var(--text-200);
    text-transform:uppercase; letter-spacing:.4px;
    margin-bottom:10px;
}
.doc-code {
    font-family:monospace; font-size:12px;
    background:var(--bg-input);
    border:1px solid var(--border-subtle);
    border-radius:var(--r-sm);
    padding:10px 12px;
    color:var(--text-100);
    white-space:pre-wrap; word-break:break-all;
    line-height:1.6;
}
.doc-comment { color:var(--text-400); }
.doc-key  { color:#7dd3fc; }
.doc-val  { color:#86efac; }
.doc-url  { color:#c084fc; }
.doc-meth { color:#fbbf24; }

.endpoint-list { list-style:none; padding:0; margin:0; }
.endpoint-item {
    display:flex; align-items:center; gap:8px;
    padding:6px 0;
    border-bottom:1px solid var(--border-subtle);
    font-size:13px;
}
.endpoint-item:last-child { border-bottom:none; }
.method-badge {
    font-family:monospace; font-size:11px; font-weight:700;
    padding:2px 7px; border-radius:4px;
    flex-shrink:0;
}
.m-get    { background:rgba(59,130,246,.15); color:#60a5fa; }
.m-post   { background:rgba(34,197,94,.15);  color:#4ade80; }
.m-put    { background:rgba(251,191,36,.15);  color:#fbbf24; }
.m-delete { background:rgba(239,68,68,.15);   color:#f87171; }

/* ── MOBILE API KEY CARDS (list view, <768px) ──────────────────────── */
.ak-mobile-list{display:none}
@media(max-width:768px){
    .ak-table-wrap{display:none}
    .ak-mobile-list{display:flex;flex-direction:column;gap:10px;padding:16px}
}
.ak-card{background:var(--bg-elevated);border:1px solid var(--border-subtle);border-radius:var(--r-md);padding:14px}
.ak-top{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px}
.ak-label{font-size:14px;font-weight:700;color:var(--text-100)}
.ak-key-box{margin-bottom:10px}
.ak-meta{display:flex;flex-direction:column;gap:4px;margin-bottom:10px;font-size:12px;color:var(--text-300)}
.ak-meta-lbl{color:var(--text-400)}
.ak-foot{display:flex;align-items:center;justify-content:flex-end;gap:6px;padding-top:10px;border-top:1px solid var(--border-subtle)}
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
        <div class="page-title">API Keys</div>
        <div style="font-size:13px;color:var(--text-300);margin-top:2px;">
            Generate API keys to integrate your CRM with third-party tools.
        </div>
    </div>
</div>

<div class="api-wrap">

    {{-- ── Generate new key ───────────────────────────────────────── --}}
    <div class="api-card">
        <div class="api-card-head">
            <div>
                <div class="api-card-title">Generate New API Key</div>
                <div class="api-card-sub">Give it a label so you remember where it's used.</div>
            </div>
        </div>
        <div class="api-card-body">
            <form method="POST" action="{{ route('tenant.api-keys.store') }}">
                @csrf
                <div class="gen-row">
                    <div class="field">
                        <label class="field-label">Key Label <span style="color:var(--red)">*</span></label>
                        <input type="text" name="name" class="field-input {{ $errors->has('name') ? 'is-error' : '' }}"
                               placeholder="e.g. Zoho CRM, Website Form, n8n Automation"
                               value="{{ old('name') }}" maxlength="100" required>
                        @error('name')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>
                    <button type="submit" class="btn-sm btn-primary" style="flex-shrink:0;height:42px;">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
                        </svg>
                        Generate Key
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ── Keys list ───────────────────────────────────────────────── --}}
    <div class="api-card">
        <div class="api-card-head">
            <div>
                <div class="api-card-title">Your API Keys</div>
                <div class="api-card-sub">{{ $apiKeys->count() }} key{{ $apiKeys->count() !== 1 ? 's' : '' }} total</div>
            </div>
        </div>

        @if($apiKeys->isEmpty())
        <div class="empty-keys">
            <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
            </svg>
            No API keys yet. Generate one above to get started.
        </div>
        @else
        <div class="ak-table-wrap" style="overflow-x:auto">
        <table class="keys-table data-table">
            <thead>
                <tr>
                    <th>Label</th>
                    <th>API Key</th>
                    <th>Status</th>
                    <th>Last Used</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($apiKeys as $key)
                <tr>
                    <td style="font-weight:600;color:var(--text-100);" data-label="Label">{{ $key->name }}</td>
                    <td data-label="API Key">
                        <div class="key-box">
                            <span class="key-value" id="key-{{ $key->id }}" title="{{ $key->key }}">
                                {{ substr($key->key, 0, 18) }}••••••••••••••••
                            </span>
                            <button class="copy-btn" onclick="copyKey('{{ $key->key }}', this)" title="Copy full key">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/>
                                </svg>
                                Copy
                            </button>
                        </div>
                    </td>
                    <td data-label="Status">
                        @if($key->is_active)
                            <span class="badge badge-active">
                                <span class="badge-dot"></span> Active
                            </span>
                        @else
                            <span class="badge badge-inactive">
                                <span class="badge-dot"></span> Inactive
                            </span>
                        @endif
                    </td>
                    <td style="color:var(--text-300);font-size:13px;" data-label="Last Used">
                        {{ $key->last_used_at ? $key->last_used_at->diffForHumans() : '—' }}
                    </td>
                    <td style="color:var(--text-300);font-size:13px;" data-label="Created">
                        {{ $key->created_at->format('d M Y') }}
                        @if($key->creator)
                            <div style="font-size:12px;margin-top:1px;">by {{ $key->creator->name }}</div>
                        @endif
                    </td>
                    <td>
                        <div class="row-actions">
                            {{-- Toggle active/inactive --}}
                            <form method="POST" action="{{ route('tenant.api-keys.toggle', $key->id) }}">
                                @csrf
                                <button type="submit" class="btn-sm btn-outline" title="{{ $key->is_active ? 'Deactivate' : 'Activate' }}">
                                    @if($key->is_active)
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                        Deactivate
                                    @else
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        Activate
                                    @endif
                                </button>
                            </form>

                            {{-- Delete --}}
                            <form method="POST" action="{{ route('tenant.api-keys.destroy', $key->id) }}"
                                  data-confirm="Revoke and permanently delete this API key? Any integration using it will stop working." data-confirm-ok="Revoke">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-sm btn-danger">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                    </svg>
                                    Revoke
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        {{-- Mobile card list (shown only <768px, table above hides itself) --}}
        <div class="ak-mobile-list">
            @foreach($apiKeys as $key)
            <div class="ak-card">
                <div class="ak-top">
                    <span class="ak-label">{{ $key->name }}</span>
                    @if($key->is_active)
                        <span class="badge badge-active">
                            <span class="badge-dot"></span> Active
                        </span>
                    @else
                        <span class="badge badge-inactive">
                            <span class="badge-dot"></span> Inactive
                        </span>
                    @endif
                </div>
                <div class="ak-key-box">
                    <div class="key-box">
                        <span class="key-value" id="key-mobile-{{ $key->id }}" title="{{ $key->key }}">
                            {{ substr($key->key, 0, 18) }}••••••••••••••••
                        </span>
                        <button class="copy-btn" onclick="copyKey('{{ $key->key }}', this)" title="Copy full key">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/>
                            </svg>
                            Copy
                        </button>
                    </div>
                </div>
                <div class="ak-meta">
                    <span><span class="ak-meta-lbl">Last used:</span> {{ $key->last_used_at ? $key->last_used_at->diffForHumans() : '—' }}</span>
                    <span><span class="ak-meta-lbl">Created:</span> {{ $key->created_at->format('d M Y') }}@if($key->creator) by {{ $key->creator->name }}@endif</span>
                </div>
                <div class="ak-foot">
                    <div class="row-actions">
                        {{-- Toggle active/inactive --}}
                        <form method="POST" action="{{ route('tenant.api-keys.toggle', $key->id) }}">
                            @csrf
                            <button type="submit" class="btn-sm btn-outline" title="{{ $key->is_active ? 'Deactivate' : 'Activate' }}">
                                @if($key->is_active)
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                    </svg>
                                    Deactivate
                                @else
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Activate
                                @endif
                            </button>
                        </form>

                        {{-- Delete --}}
                        <form method="POST" action="{{ route('tenant.api-keys.destroy', $key->id) }}"
                              data-confirm="Revoke and permanently delete this API key? Any integration using it will stop working." data-confirm-ok="Revoke">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-sm btn-danger">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                </svg>
                                Revoke
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- ── API Documentation ────────────────────────────────────────── --}}
    <div class="api-card">
        <div class="api-card-head">
            <div>
                <div class="api-card-title">How to Use the API</div>
                <div class="api-card-sub">Integrate your CRM data with any third-party tool.</div>
            </div>
        </div>
        <div class="api-card-body" style="display:flex;flex-direction:column;gap:20px;">

            {{-- Base URL + Auth --}}
            <div class="docs-grid">
                <div class="doc-block">
                    <div class="doc-block-title">Authentication — 3 Methods</div>
                    <div class="doc-code"><span class="doc-comment"># Method 1: X-API-Key header (recommended)</span>
<span class="doc-key">X-API-Key</span>: <span class="doc-val">crm_your_api_key_here</span>

<span class="doc-comment"># Method 2: Bearer token (n8n / Postman)</span>
<span class="doc-key">Authorization</span>: <span class="doc-val">Bearer crm_your_api_key_here</span>

<span class="doc-comment"># Method 3: Query param (webhooks)</span>
<span class="doc-url">?api_key=crm_your_api_key_here</span></div>
                </div>
                <div class="doc-block">
                    <div class="doc-block-title">Base URL</div>
                    <div class="doc-code"><span class="doc-url">{{ config('app.url') }}/api/v1/tenant</span>

<span class="doc-comment"># Response format</span>
{
  <span class="doc-key">"success"</span>: <span class="doc-val">true</span>,
  <span class="doc-key">"data"</span>: [...]
}</div>
                </div>
            </div>

            {{-- Contacts endpoints --}}
            <div class="doc-block">
                <div class="doc-block-title">Contacts API</div>
                <ul class="endpoint-list">
                    <li class="endpoint-item">
                        <span class="method-badge m-get">GET</span>
                        <code style="font-size:13px;color:var(--text-100)">/contacts</code>
                        <span style="color:var(--text-300);font-size:12.5px;margin-left:auto;">List all contacts (supports ?search=, ?city=, ?sort=, ?per_page=)</span>
                    </li>
                    <li class="endpoint-item">
                        <span class="method-badge m-post">POST</span>
                        <code style="font-size:13px;color:var(--text-100)">/contacts</code>
                        <span style="color:var(--text-300);font-size:12.5px;margin-left:auto;">Create a new contact</span>
                    </li>
                    <li class="endpoint-item">
                        <span class="method-badge m-get">GET</span>
                        <code style="font-size:13px;color:var(--text-100)">/contacts/{id}</code>
                        <span style="color:var(--text-300);font-size:12.5px;margin-left:auto;">Get single contact with deals, invoices</span>
                    </li>
                    <li class="endpoint-item">
                        <span class="method-badge m-put">PUT</span>
                        <code style="font-size:13px;color:var(--text-100)">/contacts/{id}</code>
                        <span style="color:var(--text-300);font-size:12.5px;margin-left:auto;">Update contact</span>
                    </li>
                    <li class="endpoint-item">
                        <span class="method-badge m-delete">DELETE</span>
                        <code style="font-size:13px;color:var(--text-100)">/contacts/{id}</code>
                        <span style="color:var(--text-300);font-size:12.5px;margin-left:auto;">Delete contact</span>
                    </li>
                    <li class="endpoint-item">
                        <span class="method-badge m-get">GET</span>
                        <code style="font-size:13px;color:var(--text-100)">/contacts/search?q=term</code>
                        <span style="color:var(--text-300);font-size:12.5px;margin-left:auto;">Quick search (min 2 chars)</span>
                    </li>
                </ul>
            </div>

            {{-- Leads endpoints --}}
            <div class="doc-block">
                <div class="doc-block-title">Leads API</div>
                <ul class="endpoint-list">
                    <li class="endpoint-item">
                        <span class="method-badge m-get">GET</span>
                        <code style="font-size:13px;color:var(--text-100)">/leads</code>
                        <span style="color:var(--text-300);font-size:12.5px;margin-left:auto;">List leads (supports ?status=, ?source=, ?priority=)</span>
                    </li>
                    <li class="endpoint-item">
                        <span class="method-badge m-post">POST</span>
                        <code style="font-size:13px;color:var(--text-100)">/leads</code>
                        <span style="color:var(--text-300);font-size:12.5px;margin-left:auto;">Create a new lead</span>
                    </li>
                    <li class="endpoint-item">
                        <span class="method-badge m-get">GET</span>
                        <code style="font-size:13px;color:var(--text-100)">/leads/{id}</code>
                        <span style="color:var(--text-300);font-size:12.5px;margin-left:auto;">Get lead with followups & tasks</span>
                    </li>
                    <li class="endpoint-item">
                        <span class="method-badge m-put">PUT</span>
                        <code style="font-size:13px;color:var(--text-100)">/leads/{id}</code>
                        <span style="color:var(--text-300);font-size:12.5px;margin-left:auto;">Update lead</span>
                    </li>
                    <li class="endpoint-item">
                        <span class="method-badge m-delete">DELETE</span>
                        <code style="font-size:13px;color:var(--text-100)">/leads/{id}</code>
                        <span style="color:var(--text-300);font-size:12.5px;margin-left:auto;">Delete lead</span>
                    </li>
                </ul>
            </div>

            {{-- Deals endpoints --}}
            <div class="doc-block">
                <div class="doc-block-title">Deals API</div>
                <ul class="endpoint-list">
                    <li class="endpoint-item">
                        <span class="method-badge m-get">GET</span>
                        <code style="font-size:13px;color:var(--text-100)">/deals</code>
                        <span style="color:var(--text-300);font-size:12.5px;margin-left:auto;">List deals (supports ?stage=, ?value_min=, ?value_max=)</span>
                    </li>
                    <li class="endpoint-item">
                        <span class="method-badge m-post">POST</span>
                        <code style="font-size:13px;color:var(--text-100)">/deals</code>
                        <span style="color:var(--text-300);font-size:12.5px;margin-left:auto;">Create a new deal</span>
                    </li>
                    <li class="endpoint-item">
                        <span class="method-badge m-get">GET</span>
                        <code style="font-size:13px;color:var(--text-100)">/deals/{id}</code>
                        <span style="color:var(--text-300);font-size:12.5px;margin-left:auto;">Get deal details</span>
                    </li>
                    <li class="endpoint-item">
                        <span class="method-badge m-put">PUT</span>
                        <code style="font-size:13px;color:var(--text-100)">/deals/{id}</code>
                        <span style="color:var(--text-300);font-size:12.5px;margin-left:auto;">Update deal</span>
                    </li>
                    <li class="endpoint-item">
                        <span class="method-badge m-delete">DELETE</span>
                        <code style="font-size:13px;color:var(--text-100)">/deals/{id}</code>
                        <span style="color:var(--text-300);font-size:12.5px;margin-left:auto;">Delete deal</span>
                    </li>
                </ul>
            </div>

            {{-- Example cURL --}}
            <div class="doc-block">
                <div class="doc-block-title">Example — Create Contact via cURL</div>
                <div class="doc-code"><span class="doc-meth">curl</span> -X POST \
  <span class="doc-url">{{ config('app.url') }}/api/v1/tenant/contacts</span> \
  -H <span class="doc-val">"X-API-Key: crm_your_api_key_here"</span> \
  -H <span class="doc-val">"Content-Type: application/json"</span> \
  -H <span class="doc-val">"Accept: application/json"</span> \
  -d '{
    <span class="doc-key">"name"</span>:    <span class="doc-val">"Ravi Sharma"</span>,
    <span class="doc-key">"phone"</span>:   <span class="doc-val">"9876543210"</span>,
    <span class="doc-key">"email"</span>:   <span class="doc-val">"ravi@example.com"</span>,
    <span class="doc-key">"company"</span>: <span class="doc-val">"Sharma Enterprises"</span>
  }'</div>
            </div>

        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
function copyKey(key, btn) {
    navigator.clipboard.writeText(key).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = `<svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
        </svg> Copied!`;
        btn.style.borderColor = 'var(--green)';
        btn.style.color       = 'var(--green)';
        btn.style.background  = 'var(--green-dim)';
        setTimeout(() => {
            btn.innerHTML = orig;
            btn.style.borderColor = '';
            btn.style.color       = '';
            btn.style.background  = '';
        }, 2000);
    });
}
</script>
@endpush
