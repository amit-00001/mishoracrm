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
            <input type="text" id="ibSearch" class="ib-search" placeholder="Search name or number…" value="{{ request('search') }}" autocomplete="off">
            <div class="ib-tabs" id="ibTabs">
                @foreach(['open' => 'Open', 'unread' => 'Unread', 'resolved' => 'Resolved', 'all' => 'All'] as $key => $label)
                <a class="ib-tab {{ $filter === $key ? 'active' : '' }}" href="#" data-filter="{{ $key }}">{{ $label }}@if(isset($counts[$key]))<b data-count="{{ $key }}">{{ $counts[$key] }}</b>@endif</a>
                @endforeach
            </div>
        </div>
        <div class="ib-items" id="ibItems"></div>
    </div>

    {{-- Conversation (filled by JS, never reloads the page) --}}
    <div class="ib-chat" id="ibChat" style="display:none">
        <div class="ib-head">
            <div>
                <div class="ib-head-name" id="ibHeadName"></div>
                <div class="ib-head-sub" id="ibHeadSub"></div>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" id="statusBtn">Mark resolved</button>
        </div>

        <div class="ib-msgs" id="ibMsgs"></div>

        @if($canReply)
        <div class="ib-reply" id="ibReply">
            <div id="ibWindowNote" class="ib-note" style="display:none">
                The 24-hour reply window has closed.
                @if(!empty($templates)) Send an approved template to restart the chat.
                @else WhatsApp allows free-form replies only within 24 hours of the customer's last message. @endif
            </div>

            @if(!empty($templates))
            <div id="ibTplBox" style="display:none">
                <select id="ibTpl" class="field-input field-select">
                    <option value="">— Choose approved template —</option>
                    @foreach($templates as $t)
                    <option value="{{ $t['name'] }}|{{ $t['language'] }}" data-body="{{ $t['body'] }}" data-params="{{ $t['params'] }}">{{ $t['name'] }} ({{ $t['language'] }})</option>
                    @endforeach
                </select>
                <div id="ibTplParams" style="display:flex;flex-direction:column;gap:6px;margin-top:6px"></div>
            </div>
            @endif

            <div class="ib-reply-row" id="ibFreeRow">
                <label class="btn btn-secondary btn-sm" style="cursor:pointer;margin:0" title="Attach a file (image, PDF, doc — up to 16 MB)">
                    📎<input type="file" id="ibFile" hidden accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx">
                </label>
                <textarea id="ibText" rows="1" placeholder="Type a reply… (Enter to send, Shift+Enter for a new line)"></textarea>
            </div>
            <div id="ibFileName" style="font-size:12px;color:var(--text-300);display:none"></div>
            <div id="ibError" class="ib-err" style="display:none"></div>
            <div style="display:flex;justify-content:flex-end">
                <button type="button" class="btn btn-primary" id="ibSend">Send</button>
            </div>
        </div>
        @else
        <div class="ib-reply"><div class="ib-note">You don't have permission to send WhatsApp messages.</div></div>
        @endif
    </div>
    <div class="ib-none" id="ibNone">Select a conversation to read and reply.</div>
</div>
@endsection

@push('scripts')
<script>
(function () {
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';
const BASE = @json(route('tenant.whatsapp.inbox.index'));
const HAS_TPL = @json(!empty($templates));
const CAN_REPLY = @json($canReply);

let filter = @json($filter);
let search = @json(request()->input('search') ?? '');
let cur = @json($selected?->id);
let lastId = 0;
let head = null;
let sending = false;
let listSeq = 0;

const $ = id => document.getElementById(id);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const json = async (url, opts = {}) => {
    const r = await fetch(url, {...opts, headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, ...(opts.headers || {})}});
    const d = await r.json().catch(() => ({}));
    return {ok: r.ok, data: d};
};

// ── List ──
function renderList(list) {
    const box = $('ibItems');
    if (!list.length) {
        box.innerHTML = '<div class="ib-empty">No conversations here yet.<br>Chats appear when a customer messages your WhatsApp number.</div>';
        return;
    }
    box.innerHTML = list.map(c =>
        `<a href="${BASE}/${c.id}" class="ib-item ${c.id === cur ? 'active' : ''}" data-id="${c.id}">`
        + `<div class="ib-row"><span class="ib-name">${esc(c.name)}</span><span class="ib-time">${esc(c.time)}</span></div>`
        + `<div class="ib-row"><span class="ib-prev">${esc(c.preview)}</span>`
        + (c.unread > 0 && c.id !== cur ? `<span class="ib-badge">${c.unread}</span>` : '') + `</div></a>`
    ).join('');
}

function renderCounts(counts) {
    if (!counts) return;
    Object.entries(counts).forEach(([k, v]) => { const el = document.querySelector(`[data-count="${k}"]`); if (el) el.textContent = v; });
}

async function refreshList() {
    const seq = ++listSeq;
    const q = new URLSearchParams({filter, search});
    const {ok, data} = await json(`${BASE}/poll?${q}`);
    if (!ok || seq !== listSeq) return;
    renderList(data.conversations);
    renderCounts(data.counts);
}

// ── Conversation ──
function bubbleHtml(m) {
    const cls = (m.in ? 'in' : 'out') + (m.status === 'failed' ? ' failed' : '');
    return `<div class="ib-msg ${cls}" data-id="${m.id}">`
        + (m.file ? `<div class="ib-file">📎 ${esc(m.file)}</div>` : '')
        + esc(m.text)
        + (m.status === 'failed' && m.error ? `<div class="ib-err">Not sent: ${esc(m.error)}</div>` : '')
        + `<div class="ib-meta">${m.by ? esc(m.by) + ' · ' : ''}${esc(m.time)}</div></div>`;
}

function addMessages(list, replace = false) {
    const el = $('ibMsgs');
    if (replace) { el.innerHTML = ''; lastId = 0; }
    const atBottom = replace || el.scrollHeight - el.scrollTop - el.clientHeight < 80;
    list.forEach(m => {
        if (el.querySelector(`[data-id="${m.id}"]`)) return;
        el.insertAdjacentHTML('beforeend', bubbleHtml(m));
        lastId = Math.max(lastId, m.id);
    });
    if (atBottom) el.scrollTop = el.scrollHeight;
}

function applyHead(h) {
    if (!h) return;
    head = h;
    $('ibHeadName').textContent = h.name;
    $('ibHeadSub').innerHTML = esc(h.phone)
        + (h.lead_url ? ` · <a href="${esc(h.lead_url)}">Lead</a>` : '')
        + (h.contact_url ? ` · <a href="${esc(h.contact_url)}">Contact</a>` : '');
    $('statusBtn').textContent = h.status === 'open' ? 'Mark resolved' : 'Reopen';

    if (CAN_REPLY) {
        const free = h.window_open;
        $('ibFreeRow').style.display = free ? 'flex' : 'none';
        $('ibWindowNote').style.display = free ? 'none' : 'block';
        if ($('ibTplBox')) $('ibTplBox').style.display = free ? 'none' : 'block';
        $('ibSend').style.display = (free || HAS_TPL) ? '' : 'none';
    }
}

async function openChat(id, push = true) {
    cur = id;
    document.querySelectorAll('.ib-item').forEach(a => a.classList.toggle('active', Number(a.dataset.id) === id));
    const {ok, data} = await json(`${BASE}/${id}`);
    if (!ok) return;
    $('ibChat').style.display = 'flex';
    $('ibNone').style.display = 'none';
    applyHead(data.head);
    addMessages(data.messages, true);
    renderCounts(data.counts);
    clearError();
    const badge = document.querySelector(`.ib-item[data-id="${id}"] .ib-badge`);
    if (badge) badge.remove();
    if (push) history.pushState({c: id}, '', `${BASE}?filter=${filter}&c=${id}`);
    if ($('ibText')) $('ibText').focus();
}

function closeChat() {
    cur = null;
    $('ibChat').style.display = 'none';
    $('ibNone').style.display = 'flex';
    document.querySelectorAll('.ib-item').forEach(a => a.classList.remove('active'));
}

// ── Live refresh ──
async function poll() {
    if (sending || document.hidden) return;
    const q = new URLSearchParams({filter, search});
    if (cur) { q.set('c', cur); q.set('after', lastId); }
    const {ok, data} = await json(`${BASE}/poll?${q}`);
    if (!ok) return;
    renderList(data.conversations);
    renderCounts(data.counts);
    if (cur && data.head) applyHead(data.head);
    if (cur) addMessages(data.messages);
}
setInterval(poll, 6000);

// ── List interactions (no page reloads) ──
$('ibItems').addEventListener('click', e => {
    const a = e.target.closest('.ib-item');
    if (!a) return;
    e.preventDefault();
    openChat(Number(a.dataset.id));
});

$('ibTabs').addEventListener('click', e => {
    const t = e.target.closest('.ib-tab');
    if (!t) return;
    e.preventDefault();
    filter = t.dataset.filter;
    document.querySelectorAll('.ib-tab').forEach(x => x.classList.toggle('active', x === t));
    history.replaceState({c: cur}, '', `${BASE}?filter=${filter}${cur ? '&c=' + cur : ''}`);
    refreshList();
});

let searchTimer;
$('ibSearch').addEventListener('input', e => {
    clearTimeout(searchTimer);
    search = e.target.value.trim();
    searchTimer = setTimeout(refreshList, 250);
});

window.addEventListener('popstate', e => {
    const id = (e.state && e.state.c) || Number(new URLSearchParams(location.search).get('c')) || null;
    id ? openChat(id, false) : closeChat();
});

$('statusBtn').addEventListener('click', async () => {
    if (!cur) return;
    const btn = $('statusBtn');
    btn.disabled = true;
    const {ok, data} = await json(`${BASE}/${cur}/status`, {method: 'POST'});
    btn.disabled = false;
    if (!ok) return;
    applyHead(data.head);
    renderCounts(data.counts);
    refreshList();
});

// ── Reply ──
function showError(msg) { const el = $('ibError'); if (!el) return; el.textContent = msg; el.style.display = msg ? 'block' : 'none'; }
function clearError() { showError(''); }

if (CAN_REPLY) {
    const txt = $('ibText');
    txt.addEventListener('keydown', e => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendReply(); } });
    txt.addEventListener('input', () => { txt.style.height = 'auto'; txt.style.height = Math.min(txt.scrollHeight, 140) + 'px'; });
    $('ibSend').addEventListener('click', sendReply);

    $('ibFile').addEventListener('change', () => {
        const f = $('ibFile').files[0];
        const el = $('ibFileName');
        el.style.display = f ? 'block' : 'none';
        el.textContent = f ? '📎 ' + f.name : '';
    });

    if ($('ibTpl')) $('ibTpl').addEventListener('change', () => {
        const sel = $('ibTpl');
        const box = $('ibTplParams');
        box.innerHTML = '';
        const opt = sel.value ? sel.options[sel.selectedIndex] : null;
        if (!opt) return;
        box.insertAdjacentHTML('beforeend', `<div style="font-size:12px;color:var(--text-300);white-space:pre-wrap">${esc(opt.dataset.body)}</div>`);
        for (let i = 0; i < parseInt(opt.dataset.params || '0', 10); i++) {
            box.insertAdjacentHTML('beforeend', `<input class="ib-search tpl-param" placeholder="Value for field ${i + 1}">`);
        }
    });
}

async function sendReply() {
    if (sending || !cur || !head) return;
    const fd = new FormData();
    const tpl = $('ibTpl');

    if (!head.window_open) {
        if (!tpl || !tpl.value) { showError('Choose an approved template first.'); return; }
        fd.append('meta_template', tpl.value);
        document.querySelectorAll('.tpl-param').forEach((el, i) => fd.append(`template_params[${i}]`, el.value));
    } else {
        const t = $('ibText').value.trim();
        const f = $('ibFile').files[0];
        if (!t && !f) return;
        fd.append('message', t);
        if (f) fd.append('attachment', f);
    }

    sending = true;
    clearError();
    const btn = $('ibSend');
    btn.disabled = true; btn.textContent = 'Sending…';
    try {
        const {ok, data} = await json(`${BASE}/${cur}/reply`, {method: 'POST', body: fd});
        if (data.message) addMessages([data.message]);
        if (!ok && data.error) showError(data.error);
        if (ok) {
            $('ibText').value = ''; $('ibText').style.height = 'auto';
            $('ibFile').value = ''; $('ibFileName').style.display = 'none';
            if (tpl) { tpl.value = ''; $('ibTplParams').innerHTML = ''; }
            refreshList();
        }
    } catch (e) {
        showError('Network error — message not sent.');
    }
    sending = false; btn.disabled = false; btn.textContent = 'Send';
}

// ── First paint (from the server, no extra request) ──
renderList(@json($list));
const firstHead = @json($head);
if (firstHead) {
    $('ibChat').style.display = 'flex';
    $('ibNone').style.display = 'none';
    applyHead(firstHead);
    addMessages(@json($bubbles), true);
    history.replaceState({c: cur}, '');
}
})();
</script>
@endpush
