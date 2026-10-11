@extends('layouts.app')
@section('title', 'WhatsApp Inbox')

@push('styles')
<style>
.inbox { display:grid; grid-template-columns:340px 1fr; height:calc(100vh - 190px); min-height:480px; background:var(--bg-surface); border:1px solid var(--border-subtle); border-radius:var(--r-md, 10px); overflow:hidden; }
.ib-list { border-right:1px solid var(--border-subtle); display:flex; flex-direction:column; min-height:0; }
.ib-tools { padding:12px; border-bottom:1px solid var(--border-subtle); display:flex; flex-direction:column; gap:10px; }
.ib-search { width:100%; padding:8px 12px; height:36px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:13px; outline:none; }
.ib-search:focus { border-color:var(--accent); }
.ib-tabs { display:flex; gap:6px; flex-wrap:wrap; }
.ib-tab { padding:4px 11px; border-radius:20px; font-size:12px; font-weight:600; border:1.5px solid var(--border-default); color:var(--text-200); text-decoration:none; }
.ib-tab.active { border-color:#25D366; color:#128C7E; background:#E8F5E9; }
.ib-tab b { font-weight:700; margin-left:3px; }
.ib-items { overflow-y:auto; flex:1; }
.ib-item { display:block; padding:12px 14px; border-bottom:1px solid var(--border-subtle); text-decoration:none; color:inherit; }
.ib-item:hover { background:var(--bg-elevated); }
.ib-item.active { background:var(--accent-dim); }
.ib-row { display:flex; justify-content:space-between; gap:8px; align-items:baseline; }
.ib-name { font-size:13.5px; font-weight:600; color:var(--text-100); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.ib-time { font-size:11px; color:var(--text-400); flex-shrink:0; }
.ib-prev { font-size:12.5px; color:var(--text-300); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px; }
.ib-badge { background:#25D366; color:#fff; font-size:11px; font-weight:700; min-width:18px; height:18px; border-radius:9px; display:inline-flex; align-items:center; justify-content:center; padding:0 5px; flex-shrink:0; }
.ib-empty { padding:50px 20px; text-align:center; color:var(--text-300); font-size:13px; }

.ib-chat { display:flex; flex-direction:column; min-height:0; min-width:0; }
.ib-head { padding:12px 18px; border-bottom:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
.ib-head-name { font-size:15px; font-weight:700; color:var(--text-100); }
.ib-head-sub { font-size:12px; color:var(--text-300); margin-top:2px; }
.ib-msgs { flex:1; overflow-y:auto; padding:18px; background:var(--bg-elevated); display:flex; flex-direction:column; gap:8px; }
.ib-msg { max-width:70%; padding:8px 12px; border-radius:10px; font-size:13.5px; line-height:1.5; word-break:break-word; white-space:pre-wrap; }
.ib-msg.in { align-self:flex-start; background:var(--bg-surface); border:1px solid var(--border-subtle); color:var(--text-100); }
.ib-msg.out { align-self:flex-end; background:#DCF8C6; color:#1a1a1a; }
.ib-msg.failed { background:#FDECEA; border:1px solid #f5c2c0; }
.ib-meta { font-size:10.5px; color:#777; margin-top:3px; text-align:right; white-space:normal; }
.ib-file { font-size:12px; font-weight:600; margin-bottom:3px; }
.ib-err { font-size:11px; color:#c0392b; margin-top:3px; white-space:normal; }
.ib-reply { border-top:1px solid var(--border-subtle); padding:12px 14px; display:flex; flex-direction:column; gap:8px; }
.ib-reply-row { display:flex; gap:8px; align-items:flex-end; }
.ib-reply textarea { flex:1; resize:none; min-height:40px; max-height:140px; padding:9px 12px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:13.5px; outline:none; }
.ib-reply textarea:focus { border-color:var(--accent); }
.ib-note { font-size:12px; padding:9px 12px; border-radius:var(--r-sm); background:var(--amber-dim); color:var(--amber); }
.ib-none { flex:1; display:flex; align-items:center; justify-content:center; color:var(--text-300); font-size:14px; background:var(--bg-elevated); }
@media (max-width:860px) {
  .inbox { grid-template-columns:1fr; height:auto; }
  .ib-list { max-height:320px; border-right:0; border-bottom:1px solid var(--border-subtle); }
  .ib-chat { height:70vh; }
}
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.whatsapp.index') }}" style="color:var(--text-300);text-decoration:none">WhatsApp</a>
            <span style="margin:0 6px">›</span> Inbox
        </div>
        <div class="page-title">WhatsApp Inbox</div>
    </div>
</div>

@unless($connected)
<div style="background:var(--amber-dim);border:1px solid rgba(248,184,78,.3);border-radius:var(--r-sm);padding:12px 16px;font-size:12.5px;color:var(--amber);margin-bottom:16px">
    ⚠️ WhatsApp is not connected — customer messages won't arrive and replies won't send until you connect it in
    <a href="{{ route('tenant.whatsapp.api-settings') }}" style="color:inherit;text-decoration:underline;font-weight:600">API Settings</a>.
</div>
@endunless

<div class="inbox">

    {{-- Chat list --}}
    <div class="ib-list">
        <div class="ib-tools">
            <form method="GET" action="{{ route('tenant.whatsapp.inbox.index') }}">
                <input type="hidden" name="filter" value="{{ $filter }}">
                <input type="text" name="search" class="ib-search" placeholder="Search name or number…" value="{{ request('search') }}">
            </form>
            <div class="ib-tabs">
                @foreach(['open' => 'Open', 'unread' => 'Unread', 'resolved' => 'Resolved', 'all' => 'All'] as $key => $label)
                <a class="ib-tab {{ $filter === $key ? 'active' : '' }}"
                   href="{{ route('tenant.whatsapp.inbox.index', ['filter' => $key]) }}">{{ $label }}@if(isset($counts[$key]))<b>{{ $counts[$key] }}</b>@endif</a>
                @endforeach
            </div>
        </div>
        <div class="ib-items" id="ibItems">
            @forelse($conversations as $c)
            <a href="{{ route('tenant.whatsapp.inbox.index', ['filter' => $filter, 'c' => $c->id]) }}"
               class="ib-item {{ $selected?->id === $c->id ? 'active' : '' }}" data-id="{{ $c->id }}">
                <div class="ib-row">
                    <span class="ib-name">{{ $c->displayName() }}</span>
                    <span class="ib-time">{{ $c->last_message_at?->diffForHumans(null, true, true) }}</span>
                </div>
                <div class="ib-row">
                    <span class="ib-prev">{{ $c->last_message_preview }}</span>
                    @if($c->unread_count > 0)<span class="ib-badge">{{ $c->unread_count }}</span>@endif
                </div>
            </a>
            @empty
            <div class="ib-empty">No conversations here yet.<br>Chats appear when a customer messages your WhatsApp number.</div>
            @endforelse
        </div>
    </div>

    {{-- Conversation --}}
    @if($selected)
    <div class="ib-chat">
        <div class="ib-head">
            <div>
                <div class="ib-head-name">{{ $selected->displayName() }}</div>
                <div class="ib-head-sub">
                    +{{ $selected->wa_id }}
                    @if($selected->lead) · <a href="{{ route('tenant.leads.show', $selected->lead_id) }}">Lead</a> @endif
                    @if($selected->contact) · <a href="{{ route('tenant.contacts.show', $selected->contact_id) }}">Contact</a> @endif
                </div>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" id="statusBtn" onclick="toggleStatus()">
                {{ $selected->status === 'open' ? 'Mark resolved' : 'Reopen' }}
            </button>
        </div>

        <div class="ib-msgs" id="ibMsgs">
            @foreach($messages as $m)
            <div class="ib-msg {{ $m->status === 'received' ? 'in' : 'out' }} {{ $m->status === 'failed' ? 'failed' : '' }}" data-id="{{ $m->id }}">
                @if($m->attachment_name)<div class="ib-file">📎 {{ $m->attachment_name }}</div>@endif
                {{ $m->message }}
                @if($m->status === 'failed' && $m->error_message)<div class="ib-err">Not sent: {{ $m->error_message }}</div>@endif
                <div class="ib-meta">{{ $m->sentBy?->name ? $m->sentBy->name . ' · ' : '' }}{{ ($m->sent_at ?? $m->created_at)?->format('d M, h:i A') }}</div>
            </div>
            @endforeach
        </div>

        @if($canReply)
        <div class="ib-reply" id="ibReply">
            <div id="ibWindowNote" class="ib-note" style="display:{{ $selected->windowOpen() ? 'none' : 'block' }}">
                The 24-hour reply window has closed.
                @if(!empty($templates)) Send an approved template to restart the chat.
                @else WhatsApp allows free-form replies only within 24 hours of the customer's last message. @endif
            </div>

            @if(!empty($templates))
            <div id="ibTplBox" style="display:{{ $selected->windowOpen() ? 'none' : 'block' }}">
                <select id="ibTpl" class="field-input field-select" onchange="tplChanged()">
                    <option value="">— Choose approved template —</option>
                    @foreach($templates as $t)
                    <option value="{{ $t['name'] }}|{{ $t['language'] }}" data-body="{{ $t['body'] }}" data-params="{{ $t['params'] }}">{{ $t['name'] }} ({{ $t['language'] }})</option>
                    @endforeach
                </select>
                <div id="ibTplParams" style="display:flex;flex-direction:column;gap:6px;margin-top:6px"></div>
            </div>
            @endif

            <div class="ib-reply-row" id="ibFreeRow" style="display:{{ $selected->windowOpen() ? 'flex' : 'none' }}">
                <label class="btn btn-secondary btn-sm" style="cursor:pointer;margin:0" title="Attach a file (image, PDF, doc — up to 16 MB)">
                    📎<input type="file" id="ibFile" hidden accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" onchange="fileChosen()">
                </label>
                <textarea id="ibText" rows="1" placeholder="Type a reply… (Enter to send, Shift+Enter for a new line)"></textarea>
            </div>
            <div id="ibFileName" style="font-size:12px;color:var(--text-300);display:none"></div>
            <div style="display:flex;justify-content:flex-end">
                <button type="button" class="btn btn-primary" id="ibSend" onclick="sendReply()">Send</button>
            </div>
        </div>
        @else
        <div class="ib-reply"><div class="ib-note">You don't have permission to send WhatsApp messages.</div></div>
        @endif
    </div>
    @else
    <div class="ib-none">Select a conversation to read and reply.</div>
    @endif
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
const FILTER = @json($filter);
const CUR = @json($selected?->id);
const BASE = @json(route('tenant.whatsapp.inbox.index'));
let lastId = {{ $messages->max('id') ?? 0 }};
let windowOpen = @json($selected ? $selected->windowOpen() : false);
let busy = false;

const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const msgsEl = document.getElementById('ibMsgs');
if (msgsEl) msgsEl.scrollTop = msgsEl.scrollHeight;

function bubbleHtml(m) {
    const cls = (m.in ? 'in' : 'out') + (m.status === 'failed' ? ' failed' : '');
    return `<div class="ib-msg ${cls}" data-id="${m.id}">`
        + (m.file ? `<div class="ib-file">📎 ${esc(m.file)}</div>` : '')
        + esc(m.text)
        + (m.status === 'failed' && m.error ? `<div class="ib-err">Not sent: ${esc(m.error)}</div>` : '')
        + `<div class="ib-meta">${m.by ? esc(m.by) + ' · ' : ''}${esc(m.time)}</div></div>`;
}

function appendMessages(list) {
    if (!msgsEl || !list.length) return;
    const atBottom = msgsEl.scrollHeight - msgsEl.scrollTop - msgsEl.clientHeight < 80;
    list.forEach(m => {
        if (msgsEl.querySelector(`[data-id="${m.id}"]`)) return;
        msgsEl.insertAdjacentHTML('beforeend', bubbleHtml(m));
        lastId = Math.max(lastId, m.id);
    });
    if (atBottom) msgsEl.scrollTop = msgsEl.scrollHeight;
}

function renderList(list) {
    const box = document.getElementById('ibItems');
    if (!box || !list.length) return;
    box.innerHTML = list.map(c =>
        `<a href="${BASE}?filter=${FILTER}&c=${c.id}" class="ib-item ${c.id === CUR ? 'active' : ''}" data-id="${c.id}">`
        + `<div class="ib-row"><span class="ib-name">${esc(c.name)}</span><span class="ib-time">${esc(c.time)}</span></div>`
        + `<div class="ib-row"><span class="ib-prev">${esc(c.preview)}</span>`
        + (c.unread > 0 && c.id !== CUR ? `<span class="ib-badge">${c.unread}</span>` : '') + `</div></a>`
    ).join('');
}

async function poll() {
    if (busy || document.hidden) return;
    try {
        const q = new URLSearchParams({filter: FILTER, search: @json(request('search', ''))});
        if (CUR) { q.set('c', CUR); q.set('after', lastId); }
        const r = await fetch(BASE + '/poll?' + q, {headers: {'Accept': 'application/json'}});
        if (!r.ok) return;
        const d = await r.json();
        renderList(d.conversations);
        appendMessages(d.messages);
    } catch (e) {}
}
setInterval(poll, 6000);

// ── Reply ──
const txt = document.getElementById('ibText');
if (txt) {
    txt.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendReply(); } });
    txt.addEventListener('input', () => { txt.style.height = 'auto'; txt.style.height = Math.min(txt.scrollHeight, 140) + 'px'; });
}

function fileChosen() {
    const f = document.getElementById('ibFile').files[0];
    const el = document.getElementById('ibFileName');
    el.style.display = f ? 'block' : 'none';
    el.textContent = f ? '📎 ' + f.name : '';
}

function tplChanged() {
    const sel = document.getElementById('ibTpl');
    const box = document.getElementById('ibTplParams');
    box.innerHTML = '';
    const opt = sel.value ? sel.options[sel.selectedIndex] : null;
    if (!opt) return;
    const n = parseInt(opt.dataset.params || '0', 10);
    box.insertAdjacentHTML('beforeend', `<div style="font-size:12px;color:var(--text-300);white-space:pre-wrap">${esc(opt.dataset.body)}</div>`);
    for (let i = 0; i < n; i++) {
        box.insertAdjacentHTML('beforeend', `<input class="ib-search tpl-param" placeholder="Value for field ${i + 1}">`);
    }
}

async function sendReply() {
    if (busy || !CUR) return;
    const fd = new FormData();
    const tpl = document.getElementById('ibTpl');
    if (tpl && tpl.value && (!windowOpen || document.getElementById('ibFreeRow').style.display === 'none')) {
        fd.append('meta_template', tpl.value);
        document.querySelectorAll('.tpl-param').forEach((el, i) => fd.append(`template_params[${i}]`, el.value));
    } else {
        const t = txt.value.trim();
        const f = document.getElementById('ibFile').files[0];
        if (!t && !f) return;
        fd.append('message', t);
        if (f) fd.append('attachment', f);
    }
    busy = true;
    const btn = document.getElementById('ibSend');
    btn.disabled = true; btn.textContent = 'Sending…';
    try {
        const r = await fetch(`${BASE}/${CUR}/reply`, {method: 'POST', body: fd, headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'}});
        const d = await r.json().catch(() => ({}));
        if (d.message) appendMessages([d.message]);
        if (!r.ok && d.error && !d.message) alert(d.error);
        if (r.ok) {
            txt.value = ''; txt.style.height = 'auto';
            document.getElementById('ibFile').value = ''; fileChosen();
            if (tpl) { tpl.value = ''; tplChanged(); }
        }
    } catch (e) {
        alert('Network error — message not sent.');
    }
    busy = false; btn.disabled = false; btn.textContent = 'Send';
}

async function toggleStatus() {
    const r = await fetch(`${BASE}/${CUR}/status`, {method: 'POST', headers: {'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json'}});
    if (r.ok) location.reload();
}
</script>
@endpush
