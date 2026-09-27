@extends('layouts.app')
@section('title', 'Instagram Chatbot')

@push('styles')
<style>
.chatbot-layout { display:grid; grid-template-columns:1fr 380px; gap:20px; align-items:start; }
@media(max-width:900px){ .chatbot-layout { grid-template-columns:1fr; } }
.flow-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); padding:16px 18px; margin-bottom:10px; display:flex; align-items:flex-start; gap:14px; }
.flow-card.inactive { opacity:.6; }
.flow-icon { width:36px; height:36px; border-radius:var(--r-md); background:rgba(252,231,243,0.14); display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
.flow-info { flex:1; min-width:0; }
.flow-name { font-weight:700; font-size:14px; color:var(--text-100); }
.flow-kw { font-size:12px; color:var(--text-300); margin-top:2px; }
.flow-msg { font-size:12px; color:var(--text-200); margin-top:6px; background:var(--bg-subtle); border-radius:var(--r-sm); padding:8px 10px; white-space:pre-wrap; word-break:break-word; max-height:80px; overflow:hidden; }
.flow-actions { display:flex; gap:6px; align-items:center; flex-shrink:0; }
.toggle-switch { position:relative; display:inline-block; width:36px; height:20px; }
.toggle-switch input { opacity:0; width:0; height:0; }
.toggle-slider { position:absolute; cursor:pointer; inset:0; background:rgba(209,213,219,0.14); border-radius:99px; transition:.2s; }
.toggle-slider:before { content:''; position:absolute; width:14px; height:14px; left:3px; top:3px; background:#fff; border-radius:50%; transition:.2s; }
input:checked + .toggle-slider { background:var(--accent); }
input:checked + .toggle-slider:before { transform:translateX(16px); }
.kw-tag { display:inline-block; background:var(--bg-subtle); border:1px solid var(--border-subtle); border-radius:99px; padding:1px 8px; font-size:11px; color:var(--text-200); margin:1px; }
.default-badge { background:rgba(254,243,199,0.14); color:#F19D6A; border-radius:99px; padding:1px 8px; font-size:11px; font-weight:600; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Instagram Chatbot</h1>
        <p class="page-sub">Keyword-based auto-replies to DMs</p>
    </div>
    <a href="{{ route('tenant.instagram.index') }}" class="btn btn-ghost">Back</a>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('success') }}</div>
@endif

<div class="chatbot-layout">
    {{-- Flows list --}}
    <div>
        @if($flows->isEmpty())
            <div class="card" style="padding:50px;text-align:center;">
                <div style="font-size:36px;margin-bottom:10px;">🤖</div>
                <div style="font-size:15px;font-weight:600;margin-bottom:6px;">No chatbot flows yet</div>
                <p style="font-size:13px;color:var(--text-300);">Add your first flow using the form on the right</p>
            </div>
        @else
            @foreach($flows as $flow)
            <div class="flow-card {{ $flow->is_active ? '' : 'inactive' }}" id="flow-{{ $flow->id }}">
                <div class="flow-icon">{{ $flow->is_default ? '⭐' : '💬' }}</div>
                <div class="flow-info">
                    <div class="flow-name">
                        {{ $flow->name }}
                        @if($flow->is_default) <span class="default-badge">Default</span> @endif
                    </div>
                    <div class="flow-kw">
                        @foreach($flow->trigger_keywords ?? [] as $kw)
                            <span class="kw-tag">{{ $kw }}</span>
                        @endforeach
                        <span style="margin-left:4px;color:var(--text-300);">({{ $flow->keyword_match }})</span>
                        • Triggered {{ $flow->triggered_count }}x
                    </div>
                    <div class="flow-msg">{{ $flow->response_message }}</div>
                </div>
                <div class="flow-actions">
                    <label class="toggle-switch">
                        <input type="checkbox" {{ $flow->is_active ? 'checked' : '' }}
                            onchange="toggleFlow({{ $flow->id }}, this)">
                        <span class="toggle-slider"></span>
                    </label>
                    <button class="btn btn-ghost btn-sm" onclick="openEditModal({{ $flow->id }})">Edit</button>
                    <form method="POST" action="{{ route('tenant.instagram.chatbot.destroy', $flow->id) }}"
                        data-confirm="Delete this flow?" data-confirm-ok="Delete">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger);">Del</button>
                    </form>
                </div>
            </div>
            @endforeach
            {{ $flows->links() }}
        @endif
    </div>

    {{-- Add flow form --}}
    <div class="card" id="formCard">
        <div class="card-header">
            <h3 class="card-title" id="formTitle">Add Chatbot Flow</h3>
        </div>
        <div class="card-body">
            <form method="POST" id="chatbotForm" action="{{ route('tenant.instagram.chatbot.store') }}">
                @csrf
                <input type="hidden" name="_method" id="formMethod" value="POST">
                <input type="hidden" name="flow_id" id="flowId" value="">

                <div class="form-group">
                    <label class="form-label">Flow Name <span class="required">*</span></label>
                    <input type="text" name="name" id="flowName" class="form-input" required placeholder="e.g. Price inquiry">
                </div>
                <div class="form-group">
                    <label class="form-label">Trigger Keywords <span class="required">*</span></label>
                    <input type="text" name="trigger_keywords" id="flowKeywords" class="form-input" required placeholder="hi, hello, price, info">
                    <span class="form-hint">Comma separated. User sends any of these → auto-reply</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Keyword Match</label>
                    <select name="keyword_match" id="flowMatch" class="form-input">
                        <option value="contains">Contains</option>
                        <option value="exact">Exact</option>
                        <option value="any">Any word</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Response Message <span class="required">*</span></label>
                    <textarea name="response_message" id="flowResponse" class="form-input" rows="5" required placeholder="Hi! Thanks for messaging us. Our prices start from..."></textarea>
                    <span class="form-hint">This message will be sent as a DM reply</span>
                </div>
                <div class="form-group">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="is_default" id="flowDefault" value="1">
                        <span class="form-label" style="margin:0;">Set as default (fallback when no keyword matches)</span>
                    </label>
                </div>

                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-primary" style="flex:1;">Save Flow</button>
                    <button type="button" class="btn btn-ghost" onclick="resetForm()">Reset</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit modal data (hidden) --}}
<script>
const flowData = {
    @foreach($flows as $flow)
    {{ $flow->id }}: {
        name: @json($flow->name),
        keywords: @json(implode(', ', $flow->trigger_keywords ?? [])),
        match: @json($flow->keyword_match),
        response: @json($flow->response_message),
        is_default: {{ $flow->is_default ? 'true' : 'false' }},
    },
    @endforeach
};

function openEditModal(id) {
    const f = flowData[id];
    if (!f) return;

    document.getElementById('formTitle').textContent = 'Edit Flow';
    document.getElementById('formMethod').value = 'PUT';
    document.getElementById('flowId').value = id;
    document.getElementById('chatbotForm').action = `/instagram/chatbot/${id}`;
    document.getElementById('flowName').value = f.name;
    document.getElementById('flowKeywords').value = f.keywords;
    document.getElementById('flowMatch').value = f.match;
    document.getElementById('flowResponse').value = f.response;
    document.getElementById('flowDefault').checked = f.is_default;

    document.getElementById('formCard').scrollIntoView({ behavior: 'smooth' });
}

function resetForm() {
    document.getElementById('formTitle').textContent = 'Add Chatbot Flow';
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('chatbotForm').action = '{{ route("tenant.instagram.chatbot.store") }}';
    document.getElementById('chatbotForm').reset();
}

function toggleFlow(id, checkbox) {
    fetch(`/instagram/chatbot/${id}/toggle`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        const card = document.getElementById('flow-' + id);
        card.classList.toggle('inactive', !data.is_active);
        checkbox.checked = data.is_active;
    })
    .catch(() => { checkbox.checked = !checkbox.checked; });
}
</script>
@endsection
