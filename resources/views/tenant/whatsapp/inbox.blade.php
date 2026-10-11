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
.ib-optout { font-size:11.5px; font-weight:600; color:#8a4b00; background:#FFF1E0; border:1px solid #f1c48a; border-radius:20px; padding:3px 10px; }
.ib-who { font-size:11px; color:var(--text-400); margin-top:3px; }
.ib-select { height:32px; padding:0 8px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:12.5px; }
.ib-msg.note { align-self:center; max-width:80%; background:#FFF8E1; border:1px dashed #E6B800; color:#5c4a00; }
.ib-mode { display:flex; gap:6px; }
.ib-mode-btn { padding:4px 12px; border-radius:20px; font-size:12px; font-weight:600; border:1.5px solid var(--border-default); background:none; color:var(--text-200); cursor:pointer; font-family:var(--font); }
.ib-mode-btn.active { border-color:#25D366; color:#128C7E; background:#E8F5E9; }
.ib-mode-btn[data-mode="note"].active { border-color:#E6B800; color:#8a6d00; background:#FFF8E1; }
.ib-reply textarea.is-note { background:#FFFBEA; border-color:#E6B800; }
.ib-quick { position:absolute; left:0; right:0; bottom:100%; margin-bottom:6px; background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-sm); box-shadow:0 6px 20px rgba(0,0,0,.12); max-height:200px; overflow-y:auto; z-index:20; }
.ib-quick-item { padding:8px 12px; font-size:13px; cursor:pointer; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.ib-quick-item span { color:var(--text-300); }
.ib-quick-item.sel, .ib-quick-item:hover { background:var(--accent-dim); }
.ib-modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1000; align-items:center; justify-content:center; padding:20px; }
.ib-modal.open { display:flex; }
.ib-modal-box { background:var(--bg-surface); border-radius:12px; max-width:520px; width:100%; max-height:85vh; overflow:auto; padding:18px 20px; }
.ib-modal-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; font-size:15px; }
.ib-x { background:none; border:none; font-size:16px; cursor:pointer; color:var(--text-300); }
.ib-form { display:flex; flex-direction:column; gap:8px; margin-top:12px; padding-top:12px; border-top:1px solid var(--border-subtle); }
.ib-ta { width:100%; padding:9px 12px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:13px; resize:vertical; }
.ib-qr-row { display:flex; justify-content:space-between; gap:10px; padding:8px 0; border-bottom:1px solid var(--border-subtle); font-size:13px; }
.ib-qr-body { color:var(--text-300); font-size:12.5px; white-space:pre-wrap; }
.ib-checks { display:flex; flex-direction:column; gap:6px; font-size:13.5px; max-height:240px; overflow:auto; }
.ib-checks label { display:flex; gap:8px; align-items:center; }
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
    <div class="page-actions">
        @can('whatsapp.view_reports')<a href="{{ route('tenant.whatsapp.performance') }}" class="btn btn-secondary">Team performance</a>@endcan
        @if($canQuick)<button type="button" class="btn btn-secondary" data-open-modal="qrModal">⚡ Quick replies</button>@endif
        @if($canAssign)<button type="button" class="btn btn-secondary" data-open-modal="aaModal">Inbox settings</button>@endif
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
                @foreach(['open' => 'Open', 'mine' => 'Mine', 'unassigned' => 'Unassigned', 'unread' => 'Unread', 'resolved' => 'Resolved', 'all' => 'All'] as $key => $label)
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
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                @if($canAssign)
                <select id="ibAssign" class="ib-select" title="Assign this chat">
                    <option value="">Unassigned</option>
                    @foreach($team as $u)
                    <option value="{{ $u['id'] }}">{{ $u['name'] }}{{ $u['id'] === $me ? ' (me)' : '' }}</option>
                    @endforeach
                </select>
                @else
                <span class="ib-head-sub" id="ibAssignText"></span>
                @endif
                <span class="ib-optout" id="ibOptBadge" style="display:none" title="Gets no bulk / broadcast messages">Opted out of broadcasts</span>
                @if($canReply)<button type="button" class="btn btn-secondary btn-sm" id="ibOptBtn" title="Stop or allow bulk messages to this number">Block broadcasts</button>@endif
                <button type="button" class="btn btn-secondary btn-sm" id="statusBtn">Mark resolved</button>
            </div>
        </div>

        <div class="ib-msgs" id="ibMsgs"></div>

        @if($canReply)
        <div class="ib-reply" id="ibReply">
            <div class="ib-mode">
                <button type="button" class="ib-mode-btn active" data-mode="reply">Reply to customer</button>
                <button type="button" class="ib-mode-btn" data-mode="note">Private note</button>
            </div>

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

            <div class="ib-reply-row" id="ibFreeRow" style="position:relative">
                <div id="ibQuick" class="ib-quick" style="display:none"></div>
                <label class="btn btn-secondary btn-sm" id="ibAttachBtn" style="cursor:pointer;margin:0" title="Attach a file (image, PDF, doc — up to 16 MB)">
                    📎<input type="file" id="ibFile" hidden accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx">
                </label>
                <textarea id="ibText" rows="1" placeholder="Type a reply… (Enter to send, Shift+Enter for a new line, / for quick replies)"></textarea>
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

{{-- Quick replies manager --}}
@if($canQuick)
<div class="ib-modal" id="qrModal">
    <div class="ib-modal-box">
        <div class="ib-modal-head"><b>Quick replies</b><button type="button" class="ib-x" data-close>✕</button></div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:10px">Type <b>/shortcut</b> in the reply box to insert one. Use <code>@{{name}}</code> for the customer's name.</div>
        <div id="qrList"></div>
        <div class="ib-form">
            <input type="text" id="qrShortcut" class="ib-search" placeholder="shortcut (e.g. price)" maxlength="40">
            <textarea id="qrBody" class="ib-ta" rows="3" placeholder="Message text…"></textarea>
            <div id="qrError" class="ib-err" style="display:none"></div>
            <div style="display:flex;gap:8px;justify-content:flex-end">
                <button type="button" class="btn btn-secondary btn-sm" id="qrCancel" style="display:none">Cancel edit</button>
                <button type="button" class="btn btn-primary btn-sm" id="qrSave">Add quick reply</button>
            </div>
        </div>
    </div>
</div>
@endif

{{-- Auto-assign settings --}}
@if($canAssign)
<div class="ib-modal" id="aaModal">
    <div class="ib-modal-box">
        <div class="ib-modal-head"><b>Inbox settings</b><button type="button" class="ib-x" data-close>✕</button></div>
        <div style="font-weight:600;font-size:13px;margin-bottom:6px">Auto-assign new chats</div>
        <label style="display:flex;gap:8px;align-items:center;font-size:13.5px;margin-bottom:6px">
            <input type="checkbox" id="aaOn"> Share new chats between the people below, one after another
        </label>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:10px">A customer who writes again stays with the same person. You can always reassign by hand.</div>
        <div id="aaUsers" class="ib-checks">
            @foreach($team as $u)
            <label><input type="checkbox" value="{{ $u['id'] }}"> {{ $u['name'] }}</label>
            @endforeach
        </div>
        <div style="font-weight:600;font-size:13px;margin:14px 0 6px">Customers &amp; consent</div>
        <label style="display:flex;gap:8px;align-items:flex-start;font-size:13.5px;margin-bottom:8px">
            <input type="checkbox" id="aaLead" style="margin-top:3px"> <span>Create a Lead automatically when a new number messages us <span style="color:var(--text-300)">(source: WhatsApp)</span></span>
        </label>
        <label style="display:flex;gap:8px;align-items:flex-start;font-size:13.5px">
            <input type="checkbox" id="aaOptout" style="margin-top:3px"> <span>Let customers opt out by sending <b>STOP</b> (and back in with <b>START</b>) — opted-out numbers are skipped in bulk sends</span>
        </label>
        <div id="aaError" class="ib-err" style="display:none"></div>
        <div style="display:flex;justify-content:flex-end;margin-top:12px">
            <button type="button" class="btn btn-primary btn-sm" id="aaSave">Save</button>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
(function () {
const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';
const BASE = @json(route('tenant.whatsapp.inbox.index'));
const HAS_TPL = @json(!empty($templates));
const CAN_REPLY = @json($canReply);
const CAN_ASSIGN = @json($canAssign);
const CAN_QUICK = @json($canQuick);
const ME = @json($me);
let quickReplies = @json($quickReplies);
const autoAssign = @json($autoAssign);

let filter = @json($filter);
let search = @json(request()->input('search') ?? '');
let cur = @json($selected?->id);
let lastId = 0;
let lastNote = 0;
let head = null;
let sending = false;
let listSeq = 0;
let mode = 'reply';

const $ = id => document.getElementById(id);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const json = async (url, opts = {}) => {
    const r = await fetch(url, {...opts, headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF_TOKEN, ...(opts.headers || {})}});
    const d = await r.json().catch(() => ({}));
    return {ok: r.ok, data: d};
};
const post = (url, body, method = 'POST') => json(url, {method, body: JSON.stringify(body), headers: {'Content-Type': 'application/json'}});

// ── List ──
function renderList(list) {
    const box = $('ibItems');
    if (!list.length) {
        box.innerHTML = '<div class="ib-empty">No conversations here.<br>Chats appear when a customer messages your WhatsApp number.</div>';
        return;
    }
    box.innerHTML = list.map(c =>
        `<a href="${BASE}/${c.id}" class="ib-item ${c.id === cur ? 'active' : ''}" data-id="${c.id}">`
        + `<div class="ib-row"><span class="ib-name">${esc(c.name)}</span><span class="ib-time">${esc(c.time)}</span></div>`
        + `<div class="ib-row"><span class="ib-prev">${esc(c.preview)}</span>`
        + (c.unread > 0 && c.id !== cur ? `<span class="ib-badge">${c.unread}</span>` : '') + `</div>`
        + `<div class="ib-who">${c.assignee ? '👤 ' + esc(c.assignee) : '<i>Unassigned</i>'}</div></a>`
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
    if (m.note) {
        return `<div class="ib-msg note" data-key="${m.key}" data-ts="${esc(m.ts)}"><div class="ib-file">🔒 Private note — only your team sees this</div>`
            + esc(m.text) + `<div class="ib-meta">${m.by ? esc(m.by) + ' · ' : ''}${esc(m.time)}</div></div>`;
    }
    const cls = (m.in ? 'in' : 'out') + (m.status === 'failed' ? ' failed' : '');
    return `<div class="ib-msg ${cls}" data-key="${m.key}" data-ts="${esc(m.ts)}">`
        + (m.file ? `<div class="ib-file">📎 ${esc(m.file)}</div>` : '')
        + esc(m.text)
        + (m.status === 'failed' && m.error ? `<div class="ib-err">Not sent: ${esc(m.error)}</div>` : '')
        + `<div class="ib-meta">${m.by ? esc(m.by) + ' · ' : ''}${esc(m.time)}</div></div>`;
}

function addMessages(list, replace = false) {
    const el = $('ibMsgs');
    if (replace) { el.innerHTML = ''; lastId = 0; lastNote = 0; }
    const atBottom = replace || el.scrollHeight - el.scrollTop - el.clientHeight < 80;
    list.forEach(m => {
        if (el.querySelector(`[data-key="${m.key}"]`)) return;
        // keep the thread in time order (a note from a teammate can land between two messages)
        const later = [...el.children].find(c => c.dataset.ts > m.ts);
        const html = bubbleHtml(m);
        later ? later.insertAdjacentHTML('beforebegin', html) : el.insertAdjacentHTML('beforeend', html);
        if (m.note) lastNote = Math.max(lastNote, m.id); else lastId = Math.max(lastId, m.id);
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
    $('ibOptBadge').style.display = h.opted_out ? '' : 'none';
    if ($('ibOptBtn')) $('ibOptBtn').textContent = h.opted_out ? 'Allow broadcasts' : 'Block broadcasts';
    if ($('ibAssign')) { if (document.activeElement !== $('ibAssign')) $('ibAssign').value = h.assigned_to || ''; }
    if ($('ibAssignText')) $('ibAssignText').textContent = h.assignee ? '👤 ' + h.assignee : 'Unassigned';
    if (CAN_REPLY) syncComposer();
}

// Composer layout depends on the mode (reply / note) and the 24h window.
function syncComposer() {
    const note = mode === 'note';
    const free = head && head.window_open;
    $('ibFreeRow').style.display = (note || free) ? 'flex' : 'none';
    $('ibAttachBtn').style.display = note ? 'none' : '';
    $('ibWindowNote').style.display = (!note && !free) ? 'block' : 'none';
    if ($('ibTplBox')) $('ibTplBox').style.display = (!note && !free) ? 'block' : 'none';
    $('ibSend').style.display = (note || free || HAS_TPL) ? '' : 'none';
    $('ibSend').textContent = note ? 'Add note' : 'Send';
    $('ibText').placeholder = note
        ? 'Write a private note for your team… (the customer never sees it)'
        : 'Type a reply… (Enter to send, Shift+Enter for a new line, / for quick replies)';
    $('ibText').classList.toggle('is-note', note);
}

async function openChat(id, push = true) {
    cur = id;
    document.querySelectorAll('.ib-item').forEach(a => a.classList.toggle('active', Number(a.dataset.id) === id));
    const {ok, data} = await json(`${BASE}/${id}`);
    if (!ok) return;
    $('ibChat').style.display = 'flex';
    $('ibNone').style.display = 'none';
    setMode('reply');
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
    if (cur) { q.set('c', cur); q.set('after', lastId); q.set('after_note', lastNote); }
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

if ($('ibOptBtn')) {
    $('ibOptBtn').addEventListener('click', async () => {
        if (!cur) return;
        const {ok, data} = await json(`${BASE}/${cur}/opt-out`, {method: 'POST'});
        if (ok) applyHead(data.head);
    });
}

if (CAN_ASSIGN && $('ibAssign')) {
    $('ibAssign').addEventListener('change', async e => {
        if (!cur) return;
        const {ok, data} = await post(`${BASE}/${cur}/assign`, {user_id: e.target.value || null});
        if (!ok) { alert(data.error || 'Could not assign this chat.'); return; }
        applyHead(data.head);
        renderCounts(data.counts);
        refreshList();
    });
}

// ── Reply / note ──
function showError(msg) { const el = $('ibError'); if (!el) return; el.textContent = msg; el.style.display = msg ? 'block' : 'none'; }
function clearError() { showError(''); }

function setMode(m) {
    if (!CAN_REPLY) return;
    mode = m;
    document.querySelectorAll('.ib-mode-btn').forEach(b => b.classList.toggle('active', b.dataset.mode === m));
    hideQuick();
    clearError();
    syncComposer();
}

// ── Quick replies: type "/" at the start of the box ──
let quickIdx = 0;
function quickMatches() {
    const t = $('ibText').value;
    if (mode !== 'reply' || !t.startsWith('/') || /\s/.test(t)) return [];
    const q = t.slice(1).toLowerCase();
    return quickReplies.filter(r => r.shortcut.startsWith(q));
}
function renderQuick() {
    const box = $('ibQuick');
    const list = quickMatches();
    if (!list.length) { box.style.display = 'none'; return; }
    quickIdx = Math.min(quickIdx, list.length - 1);
    box.innerHTML = list.map((r, i) => `<div class="ib-quick-item ${i === quickIdx ? 'sel' : ''}" data-i="${i}"><b>/${esc(r.shortcut)}</b> <span>${esc(r.body)}</span></div>`).join('');
    box.style.display = 'block';
}
function hideQuick() { const b = $('ibQuick'); if (b) b.style.display = 'none'; }
function useQuick(r) {
    const first = (head?.name || '').replace(/^\+/, '').split(' ')[0] || '';
    const txt = $('ibText');
    txt.value = r.body.replace(/\{\{\s*name\s*\}\}/gi, first);
    txt.style.height = 'auto'; txt.style.height = Math.min(txt.scrollHeight, 140) + 'px';
    hideQuick();
    txt.focus();
}

if (CAN_REPLY) {
    const txt = $('ibText');
    txt.addEventListener('keydown', e => {
        const list = quickMatches();
        if (list.length && $('ibQuick').style.display === 'block') {
            if (e.key === 'ArrowDown') { e.preventDefault(); quickIdx = (quickIdx + 1) % list.length; renderQuick(); return; }
            if (e.key === 'ArrowUp')   { e.preventDefault(); quickIdx = (quickIdx - 1 + list.length) % list.length; renderQuick(); return; }
            if (e.key === 'Enter' || e.key === 'Tab') { e.preventDefault(); useQuick(list[quickIdx]); return; }
            if (e.key === 'Escape') { hideQuick(); return; }
        }
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendReply(); }
    });
    txt.addEventListener('input', () => {
        txt.style.height = 'auto'; txt.style.height = Math.min(txt.scrollHeight, 140) + 'px';
        quickIdx = 0; renderQuick();
    });
    $('ibQuick').addEventListener('mousedown', e => {
        const it = e.target.closest('.ib-quick-item');
        if (it) { e.preventDefault(); useQuick(quickMatches()[Number(it.dataset.i)]); }
    });
    document.querySelectorAll('.ib-mode-btn').forEach(b => b.addEventListener('click', () => setMode(b.dataset.mode)));
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

    if (mode === 'note') {
        const body = $('ibText').value.trim();
        if (!body) return;
        sending = true;
        const {ok, data} = await post(`${BASE}/${cur}/notes`, {body});
        sending = false;
        if (!ok) { showError(data.message || 'Could not save the note.'); return; }
        addMessages([data.message]);
        $('ibText').value = ''; $('ibText').style.height = 'auto';
        return;
    }

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
            poll();
        }
    } catch (e) {
        showError('Network error — message not sent.');
    }
    sending = false; btn.disabled = false; btn.textContent = mode === 'note' ? 'Add note' : 'Send';
}

// ── Modals (quick replies manager, auto-assign) ──
document.querySelectorAll('.ib-modal').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m || e.target.closest('[data-close]')) m.classList.remove('open'); });
});
const openModal = id => $(id)?.classList.add('open');
window.ibOpenModal = openModal;
document.querySelectorAll('[data-open-modal]').forEach(b => b.addEventListener('click', () => {
    openModal(b.dataset.openModal);
    if (b.dataset.openModal === 'qrModal') renderQrList();
}));

// Quick replies manager
let qrEditing = null;
function renderQrList() {
    const box = $('qrList');
    if (!box) return;
    box.innerHTML = quickReplies.length ? quickReplies.map(r =>
        `<div class="ib-qr-row"><div><b>/${esc(r.shortcut)}</b><div class="ib-qr-body">${esc(r.body)}</div></div>`
        + `<div style="display:flex;gap:6px"><button type="button" class="btn btn-secondary btn-sm" data-edit="${r.id}">Edit</button>`
        + `<button type="button" class="btn btn-secondary btn-sm" data-del="${r.id}">Delete</button></div></div>`
    ).join('') : '<div class="ib-empty" style="padding:14px">No quick replies yet.</div>';
}
if (CAN_QUICK) {
    $('qrList').addEventListener('click', async e => {
        const ed = e.target.closest('[data-edit]'), del = e.target.closest('[data-del]');
        if (ed) {
            const r = quickReplies.find(x => x.id === Number(ed.dataset.edit));
            qrEditing = r.id; $('qrShortcut').value = r.shortcut; $('qrBody').value = r.body;
            $('qrSave').textContent = 'Save changes'; $('qrCancel').style.display = '';
        }
        if (del && confirm('Delete this quick reply?')) {
            const {ok} = await json(`${BASE}/quick-replies/${del.dataset.del}`, {method: 'DELETE'});
            if (ok) { quickReplies = quickReplies.filter(x => x.id !== Number(del.dataset.del)); renderQrList(); }
        }
    });
    const resetQr = () => { qrEditing = null; $('qrShortcut').value = ''; $('qrBody').value = ''; $('qrSave').textContent = 'Add quick reply'; $('qrCancel').style.display = 'none'; $('qrError').style.display = 'none'; };
    $('qrCancel').addEventListener('click', resetQr);
    $('qrSave').addEventListener('click', async () => {
        const body = {shortcut: $('qrShortcut').value.trim().replace(/^\//, ''), body: $('qrBody').value.trim()};
        const url = qrEditing ? `${BASE}/quick-replies/${qrEditing}` : `${BASE}/quick-replies`;
        const {ok, data} = await post(url, body, qrEditing ? 'PUT' : 'POST');
        if (!ok) {
            const msg = data.error || Object.values(data.errors || {})[0]?.[0] || 'Could not save.';
            $('qrError').textContent = msg; $('qrError').style.display = 'block'; return;
        }
        quickReplies = qrEditing ? quickReplies.map(x => x.id === data.reply.id ? data.reply : x) : [...quickReplies, data.reply];
        quickReplies.sort((a, b) => a.shortcut.localeCompare(b.shortcut));
        resetQr(); renderQrList();
    });
}

// Auto-assign settings
if (CAN_ASSIGN && $('aaModal')) {
    $('aaOn').checked = autoAssign.on;
    $('aaLead').checked = autoAssign.lead;
    $('aaOptout').checked = autoAssign.optout;
    document.querySelectorAll('#aaUsers input').forEach(i => { i.checked = autoAssign.users.includes(Number(i.value)); });
    $('aaSave').addEventListener('click', async () => {
        const users = [...document.querySelectorAll('#aaUsers input:checked')].map(i => Number(i.value));
        const {ok, data} = await post(`${BASE}/auto-assign`, {on: $('aaOn').checked, users, lead: $('aaLead').checked, optout: $('aaOptout').checked});
        if (!ok) {
            $('aaError').textContent = data.error || Object.values(data.errors || {})[0]?.[0] || 'Could not save.';
            $('aaError').style.display = 'block'; return;
        }
        $('aaError').style.display = 'none';
        $('aaModal').classList.remove('open');
    });
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
