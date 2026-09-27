@extends('layouts.app')
@section('title', 'WhatsApp Chatbot')

@push('styles')
<style>
/* ── Toolbar ──────────────────────────────────────────────────── */
.wa-toolbar { display:flex; align-items:center; justify-content:space-between; gap:10px; margin-bottom:12px; flex-wrap:wrap; }
.wa-toolbar-left, .wa-toolbar-right { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.wa-hint { font-size:12px; color:var(--text-300); }

/* ── Canvas ───────────────────────────────────────────────────── */
.wa-canvas-wrap { position:relative; height:68vh; min-height:420px; overflow:auto; border:1px solid var(--border-default); border-radius:var(--r-lg); background:var(--bg-subtle); background-image:radial-gradient(var(--border-default) 1px, transparent 1px); background-size:18px 18px; }
.wa-canvas-inner { position:relative; width:3000px; height:1700px; }
.wa-canvas-svg { position:absolute; inset:0; width:100%; height:100%; pointer-events:none; }
.wa-canvas-empty { position:absolute; top:60px; left:50%; transform:translateX(-50%); text-align:center; width:280px; }

/* ── Node cards ───────────────────────────────────────────────── */
.wa-node { position:absolute; width:260px; background:var(--bg-surface); border:1.5px solid var(--border-default); border-radius:var(--r-lg); box-shadow:0 1px 3px rgba(0,0,0,.08); cursor:grab; user-select:none; }
.wa-node.dragging { cursor:grabbing; box-shadow:0 8px 24px rgba(0,0,0,.18); z-index:20; }
.wa-node.inactive { opacity:.55; }
.wa-node-head { display:flex; align-items:center; gap:6px; padding:9px 10px; border-bottom:1px solid var(--border-subtle); }
.wa-node-icon { font-size:14px; flex-shrink:0; }
.wa-node-name { flex:1; min-width:0; font-weight:700; font-size:13px; color:var(--text-100); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.wa-node-controls { display:flex; align-items:center; gap:4px; flex-shrink:0; }
.wa-node-icon-btn { background:none; border:none; color:var(--text-400); cursor:pointer; padding:2px; line-height:1; font-size:13px; }
.wa-node-icon-btn:hover { color:var(--text-100); }
.wa-node-icon-btn.danger:hover { color:var(--danger); }
.wa-node-body { padding:9px 10px; position:relative; }
.wa-node-kw { font-size:10.5px; color:var(--text-300); margin-bottom:5px; }
.wa-node-kw .kw-tag { font-size:10px; padding:0 6px; }
.wa-node-msg { font-size:11.5px; color:var(--text-200); line-height:1.4; display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
.wa-node-btns { display:flex; flex-direction:column; gap:5px; margin-top:8px; }
.wa-node-btn-row { position:relative; background:var(--bg-subtle); border:1px solid #25d366; border-radius:99px; padding:4px 16px 4px 10px; font-size:11px; color:#128C4A; }
.wa-node-btn-row span.arrow { opacity:.7; }
.wa-dot { width:10px; height:10px; border-radius:50%; background:var(--accent); border:2px solid var(--bg-surface); position:absolute; }
.wa-dot-in { left:-6px; top:18px; }
.wa-dot-out { right:-6px; top:50%; transform:translateY(-50%); }
.toggle-switch.sm { width:28px; height:16px; }
.toggle-switch.sm .toggle-slider:before { width:10px; height:10px; left:3px; top:3px; }
.toggle-switch.sm input:checked + .toggle-slider:before { transform:translateX(12px); }

/* ── Drag handle affordance for toggle/delete (never starts a drag) ── */
.wa-node-noDrag { cursor:default; }

/* ── Slide-over drawer ────────────────────────────────────────── */
.wa-drawer-backdrop { position:fixed; inset:0; background:rgba(15,15,20,.4); opacity:0; pointer-events:none; transition:opacity .2s; z-index:98; }
.wa-drawer-backdrop.open { opacity:1; pointer-events:auto; }
.wa-drawer { position:fixed; top:0; right:0; height:100vh; width:420px; max-width:92vw; background:var(--bg-surface); box-shadow:-8px 0 24px rgba(0,0,0,.18); transform:translateX(100%); transition:transform .25s var(--ease, ease); z-index:99; display:flex; flex-direction:column; }
.wa-drawer.open { transform:translateX(0); }
.wa-drawer-head { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; border-bottom:1px solid var(--border-subtle); flex-shrink:0; }
.wa-drawer-head h3 { font-size:15px; font-weight:700; margin:0; }
.wa-drawer-close { background:none; border:none; font-size:20px; line-height:1; color:var(--text-300); cursor:pointer; padding:2px 6px; }
.wa-drawer-close:hover { color:var(--text-100); }
.wa-drawer-body { padding:18px 20px; overflow-y:auto; flex:1; }

.toggle-switch { position:relative; display:inline-block; width:36px; height:20px; }
.toggle-switch input { opacity:0; width:0; height:0; }
.toggle-slider { position:absolute; cursor:pointer; inset:0; background:rgba(209,213,219,0.14); border-radius:99px; transition:.2s; }
.toggle-slider:before { content:''; position:absolute; width:14px; height:14px; left:3px; top:3px; background:#fff; border-radius:50%; transition:.2s; }
input:checked + .toggle-slider { background:#25d366; }
input:checked + .toggle-slider:before { transform:translateX(16px); }
.chatbot-off-banner { background:rgba(255,247,237,0.14); border:1px solid #fed7aa; border-radius:var(--r-md); padding:14px 18px; display:flex; align-items:center; gap:12px; margin-bottom:16px; }
.sessions-table { width:100%; border-collapse:collapse; }
.sessions-table th,.sessions-table td { padding:8px 10px; text-align:left; border-bottom:1px solid var(--border-subtle); font-size:12.5px; }
.sessions-table th { font-weight:600; color:var(--text-300); font-size:11px; text-transform:uppercase; }
.kw-tag { display:inline-block; background:var(--bg-subtle); border:1px solid var(--border-subtle); border-radius:99px; padding:1px 8px; font-size:11px; color:var(--text-200); margin:1px; }
.qr-row { display:flex; gap:6px; align-items:center; margin-bottom:6px; }
.qr-row .qr-input { flex:1; min-width:0; }
.qr-row .qr-next  { flex:1; min-width:0; }
.qr-remove { background:none; border:none; color:var(--text-400); cursor:pointer; font-size:16px; padding:0 4px; flex-shrink:0; line-height:1; }
.qr-remove:hover { color:var(--danger); }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">WhatsApp Chatbot</h1>
        <p class="page-sub">
            Drag flows to arrange them, click a flow to edit it, drag a button's connection to see how it links
            @if($settings->is_connected)
                &nbsp;·&nbsp; Connected number: <strong style="color:var(--text-100);">{{ $settings->display_phone_number ?? '—' }}</strong>
            @endif
        </p>
    </div>
    <div style="display:flex;gap:8px;">
        <a href="{{ route('tenant.reports.conversions', ['source'=>'whatsapp']) }}" class="btn btn-ghost">Recent Conversions</a>
        <a href="{{ route('tenant.whatsapp.api-settings') }}" class="btn btn-ghost">API Settings</a>
        <a href="{{ route('tenant.whatsapp.index') }}" class="btn btn-ghost">Back</a>
    </div>
</div>

@if(!$settings->chatbot_enabled)
    <div class="chatbot-off-banner">
        <svg fill="none" stroke="#ea580c" stroke-width="2" viewBox="0 0 24 24" style="width:20px;height:20px;flex-shrink:0;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
        </svg>
        <div>
            <strong>Chatbot is disabled.</strong> Go to
            <a href="{{ route('tenant.whatsapp.api-settings') }}" style="color:var(--accent);">API Settings</a>
            to enable the chatbot and configure your WhatsApp Business API credentials.
        </div>
    </div>
@endif

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:16px;">{{ session('success') }}</div>
@endif

<div class="wa-toolbar">
    <div class="wa-toolbar-left">
        <button type="button" class="btn btn-primary btn-sm" onclick="openDrawer('add')">+ Add Flow</button>
        <span class="wa-hint">🔘 = quick-reply button · lines show where each button leads</span>
    </div>
    <div class="wa-toolbar-right">
        <button type="button" class="btn btn-ghost btn-sm" id="conversationsToggle" onclick="toggleConversations()">
            Recent Conversations ({{ $sessions->count() }})
        </button>
        <a href="{{ route('tenant.whatsapp.conversations') }}" class="btn btn-ghost btn-sm">View All Conversations</a>
    </div>
</div>

<div class="wa-canvas-wrap" id="canvasWrap">
    <div class="wa-canvas-inner" id="canvasInner">
        <svg class="wa-canvas-svg" id="linesSvg"></svg>

        @if($flows->isEmpty())
        <div class="wa-canvas-empty">
            <div style="font-size:36px;margin-bottom:10px;">🤖</div>
            <div style="font-size:15px;font-weight:600;margin-bottom:6px;">No chatbot flows yet</div>
            <p style="font-size:13px;color:var(--text-300);margin-bottom:14px;">Add your first flow to start building the conversation.</p>
            <button type="button" class="btn btn-primary btn-sm" onclick="openDrawer('add')">+ Add Flow</button>
        </div>
        @else
            @foreach($flows as $flow)
            <div class="wa-node {{ $flow->is_active ? '' : 'inactive' }}" id="node-{{ $flow->id }}" data-id="{{ $flow->id }}">
                <span class="wa-dot wa-dot-in" data-flow="{{ $flow->id }}"></span>
                <div class="wa-node-head">
                    <span class="wa-node-icon">{{ $flow->is_default ? '⭐' : '💬' }}</span>
                    <span class="wa-node-name">{{ $flow->name }}</span>
                    <span class="wa-node-controls">
                        <label class="toggle-switch sm wa-node-noDrag" title="Active">
                            <input type="checkbox" {{ $flow->is_active ? 'checked' : '' }}
                                onchange="toggleFlow({{ $flow->id }}, this)">
                            <span class="toggle-slider"></span>
                        </label>
                        <button type="button" class="wa-node-icon-btn wa-node-noDrag" title="Edit" onclick="openDrawer('edit', {{ $flow->id }})">✎</button>
                        <form method="POST" action="{{ route('tenant.whatsapp.chatbot.destroy', $flow->id) }}"
                            class="wa-node-noDrag" style="display:inline;" data-confirm="Delete this flow?" data-confirm-ok="Delete">
                            @csrf @method('DELETE')
                            <button type="submit" class="wa-node-icon-btn danger wa-node-noDrag" title="Delete">🗑</button>
                        </form>
                    </span>
                </div>
                <div class="wa-node-body">
                    <div class="wa-node-kw">
                        @forelse($flow->trigger_keywords ?? [] as $kw)
                            <span class="kw-tag">{{ $kw }}</span>
                        @empty
                            <span style="color:var(--text-400);">no keyword — button/default only</span>
                        @endforelse
                    </div>
                    <div class="wa-node-msg">{{ $flow->response_message }}</div>
                    @if(!empty($flow->quick_replies))
                    <div class="wa-node-btns">
                        @foreach($flow->quick_replies as $i => $btn)
                        <div class="wa-node-btn-row">
                            🔘 {{ $btn['title'] ?? $btn }}
                            <span class="wa-dot wa-dot-out" data-flow="{{ $flow->id }}" data-idx="{{ $i }}"></span>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
        @endif
    </div>
</div>

{{-- Recent sessions — collapsed by default --}}
<div class="card" style="margin-top:16px;display:none;" id="conversationsPanel">
    <div class="card-header"><h3 class="card-title">Recent Conversations</h3></div>
    @if($sessions->isEmpty())
        <div style="padding:20px;text-align:center;color:var(--text-300);font-size:13px;">No conversations yet.</div>
    @else
    <table class="sessions-table data-table">
        <thead>
            <tr><th>Phone</th><th>Name</th><th>Last Message</th></tr>
        </thead>
        <tbody>
            @foreach($sessions as $session)
            <tr>
                <td data-label="Phone">{{ $session->wa_id }}</td>
                <td data-label="Name">{{ $session->contact_name ?? '—' }}</td>
                <td style="color:var(--text-300);" data-label="Last Message">{{ $session->last_message_at?->diffForHumans() ?? '—' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

{{-- ══════════════════════════════════════════════════════════════
     SLIDE-OVER DRAWER — add/edit flow
══════════════════════════════════════════════════════════════ --}}
<div class="wa-drawer-backdrop" id="drawerBackdrop" onclick="closeDrawer()"></div>
<div class="wa-drawer" id="drawer">
    <div class="wa-drawer-head">
        <h3 id="formTitle">Add Chatbot Flow</h3>
        <button type="button" class="wa-drawer-close" onclick="closeDrawer()">×</button>
    </div>
    <div class="wa-drawer-body">
        <form method="POST" id="chatbotForm" action="{{ route('tenant.whatsapp.chatbot.store') }}">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">

            <div class="form-group">
                <label class="form-label">Flow Name <span class="required">*</span></label>
                <input type="text" name="name" id="flowName" class="form-input" required placeholder="e.g. Welcome message">
            </div>
            <div class="form-group">
                <label class="form-label">Trigger Keywords <span class="required">*</span></label>
                <input type="text" name="trigger_keywords" id="flowKeywords" class="form-input" required placeholder="hi, hello, start, namaste">
                <span class="form-hint">Comma separated. Leave empty only for default flow.</span>
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
                <textarea name="response_message" id="flowResponse" class="form-input" rows="5" required placeholder="Welcome! How can we help you today?"></textarea>
                @if(auth()->user()->tenant?->hasModuleEnabled('loyalty'))
                <span class="form-hint">Loyalty placeholders: <code>@{{loyalty_points}}</code> <code>@{{loyalty_tier}}</code> <code>@{{loyalty_redeemable}}</code> <code>@{{loyalty_lifetime}}</code> <code>@{{contact_name}}</code> <code>@{{tenant_name}}</code> — filled with the sender's real data.</span>
                @endif
            </div>
            <div class="form-group">
                <label class="form-label">Quick Reply Buttons <span style="font-weight:400;color:var(--text-400);">(optional, max 3)</span></label>
                <div id="quickReplyRows" style="display:flex;flex-direction:column;gap:6px;"></div>
                <button type="button" class="btn btn-ghost btn-sm" id="qrAddBtn" onclick="addQuickReplyRow()" style="margin-top:6px;">+ Add Button</button>
                <span class="form-hint">Sends as tappable WhatsApp buttons instead of plain text. Type the button label, then pick which flow should open when it's tapped — no keyword typing needed. Leave "Next Flow" as "— text match only —" to fall back to normal keyword matching instead.</span>
            </div>
            @php
                $tenant = auth()->user()->tenant;
                $hasLoyalty      = $tenant?->hasModuleEnabled('loyalty');
                $hasAppointments = $tenant?->hasModuleEnabled('appointments');
                $hasTickets      = $tenant?->hasModuleEnabled('tickets');
            @endphp
            @if($hasLoyalty || $hasAppointments || $hasTickets)
            <div class="form-group">
                <label class="form-label">Action</label>
                <select name="action" id="flowAction" class="form-input">
                    <option value="">None — just send the message</option>
                    @if($hasLoyalty)
                    <option value="loyalty_join">Enrol the sender in the loyalty programme (grants the welcome bonus)</option>
                    <option value="loyalty_balance">Reply with their real loyalty points balance (live lookup)</option>
                    @endif
                    @if($hasAppointments)
                    <option value="book_appointment">Send the real appointment booking link (live slots)</option>
                    @endif
                    @if($hasTickets)
                    <option value="raise_ticket">Send the support ticket link (creates a ticket for your team)</option>
                    @endif
                </select>
                <span class="form-hint">
                    For "balance"/"booking link"/"ticket link" actions: put <code>@{{loyalty_points}}</code> / <code>@{{booking_link}}</code> / <code>@{{support_link}}</code> in your Response Message where you want it — or just leave it out, it gets appended automatically.
                </span>
            </div>
            @endif
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="is_default" id="flowDefault" value="1">
                    <span class="form-label" style="margin:0;">Default flow (fallback)</span>
                </label>
            </div>

            <div style="display:flex;gap:8px;">
                <button type="submit" class="btn btn-primary" style="flex:1;">Save Flow</button>
                <button type="button" class="btn btn-ghost" onclick="resetForm()">Reset</button>
            </div>
        </form>
    </div>
</div>

<script>
const flowData = {
    @foreach($flows as $flow)
    {{ $flow->id }}: {
        name: @json($flow->name),
        keywords: @json(implode(', ', $flow->trigger_keywords ?? [])),
        match: @json($flow->keyword_match),
        response: @json($flow->response_message),
        action: @json($flow->action ?? ''),
        quick_replies: @json($flow->quick_replies ?? []),
        is_default: {{ $flow->is_default ? 'true' : 'false' }},
    },
    @endforeach
};
// All flows this tenant has, for the "Next Flow" dropdown. Excludes nothing —
// linking a flow to itself is allowed but pointless, left to the user to avoid.
const allFlows = [
    @foreach($flows as $flow)
    { id: {{ $flow->id }}, name: @json($flow->name) },
    @endforeach
];

// ── Quick reply button rows — up to 3, each with a label + "next flow" pick ──
let qrRowIndex = 0; // ever-increasing, so a row's title + dropdown always share the same array index on submit
function renderQuickReplyRows(values) {
    const wrap = document.getElementById('quickReplyRows');
    wrap.innerHTML = '';
    qrRowIndex = 0;
    (values && values.length ? values.slice(0, 3) : []).forEach(v => addQuickReplyRow(v));
    updateAddButton();
}
function addQuickReplyRow(value) {
    value = value || {};
    const wrap = document.getElementById('quickReplyRows');
    if (wrap.children.length >= 3) return;
    const idx = qrRowIndex++;

    const row = document.createElement('div');
    row.className = 'qr-row';

    const input = document.createElement('input');
    input.type = 'text';
    input.name = `quick_replies[${idx}][title]`;
    input.className = 'form-input qr-input';
    input.maxLength = 20;
    input.placeholder = 'Button label, e.g. Tell me more';
    input.value = typeof value === 'string' ? value : (value.title || '');

    const select = document.createElement('select');
    select.name = `quick_replies[${idx}][next_flow_id]`;
    select.className = 'form-input qr-next';
    select.innerHTML = '<option value="">— text match only —</option>' +
        allFlows.map(f => `<option value="${f.id}">→ ${f.name}</option>`).join('');
    select.value = (typeof value === 'object' && value.next_flow_id) ? String(value.next_flow_id) : '';

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'qr-remove';
    removeBtn.innerHTML = '✕';
    removeBtn.title = 'Remove button';
    removeBtn.onclick = () => { row.remove(); updateAddButton(); };

    row.append(input, select, removeBtn);
    wrap.appendChild(row);
    updateAddButton();
}
function updateAddButton() {
    const wrap = document.getElementById('quickReplyRows');
    const btn = document.getElementById('qrAddBtn');
    if (btn) btn.style.display = wrap.children.length >= 3 ? 'none' : 'inline-flex';
}

// ── Drawer ───────────────────────────────────────────────────────
function openDrawer(mode, id) {
    if (mode === 'edit' && id) {
        fillFormForEdit(id);
    } else {
        resetForm();
    }
    document.getElementById('drawer').classList.add('open');
    document.getElementById('drawerBackdrop').classList.add('open');
}
function closeDrawer() {
    document.getElementById('drawer').classList.remove('open');
    document.getElementById('drawerBackdrop').classList.remove('open');
}
function fillFormForEdit(id) {
    const f = flowData[id];
    if (!f) return;
    document.getElementById('formTitle').textContent = 'Edit Flow';
    document.getElementById('formMethod').value = 'PUT';
    document.getElementById('chatbotForm').action = `/whatsapp/chatbot/${id}`;
    document.getElementById('flowName').value = f.name;
    document.getElementById('flowKeywords').value = f.keywords;
    document.getElementById('flowMatch').value = f.match;
    document.getElementById('flowResponse').value = f.response;
    if (document.getElementById('flowAction')) document.getElementById('flowAction').value = f.action || '';
    document.getElementById('flowDefault').checked = f.is_default;
    renderQuickReplyRows(f.quick_replies);
}
function resetForm() {
    document.getElementById('formTitle').textContent = 'Add Chatbot Flow';
    document.getElementById('formMethod').value = 'POST';
    document.getElementById('chatbotForm').action = '{{ route("tenant.whatsapp.chatbot.store") }}';
    document.getElementById('chatbotForm').reset();
    renderQuickReplyRows([]);
}
renderQuickReplyRows([]);

function toggleFlow(id, checkbox) {
    fetch(`/whatsapp/chatbot/${id}/toggle`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('node-' + id).classList.toggle('inactive', !data.is_active);
        checkbox.checked = data.is_active;
    })
    .catch(() => { checkbox.checked = !checkbox.checked; });
}

function toggleConversations() {
    const el = document.getElementById('conversationsPanel');
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
    if (el.style.display === 'block') el.scrollIntoView({ behavior: 'smooth' });
}

// ── Canvas: layout, connector lines, drag ───────────────────────
const canvasInner = document.getElementById('canvasInner');

// Positions saved from the DB (null if never dragged before).
const savedPositions = {
    @foreach($flows as $flow)
    {{ $flow->id }}: { x: {{ $flow->canvas_x ?? 'null' }}, y: {{ $flow->canvas_y ?? 'null' }} },
    @endforeach
};

// Simple BFS layout (by rank = distance from the default flow, walking
// quick_replies[].next_flow_id edges) for any flow that has never been
// dragged yet. A flow the user HAS dragged keeps its saved spot forever.
function computeAutoLayout() {
    const ids = Object.keys(flowData).map(Number);
    const rank = {};
    const rootId = ids.find(id => flowData[id].is_default) ?? ids[0];

    if (rootId !== undefined) {
        const queue = [[rootId, 0]];
        const seen = new Set([rootId]);
        while (queue.length) {
            const [id, d] = queue.shift();
            rank[id] = d;
            (flowData[id].quick_replies || []).forEach(btn => {
                const cid = btn.next_flow_id;
                if (cid && flowData[cid] && !seen.has(cid)) {
                    seen.add(cid);
                    queue.push([cid, d + 1]);
                }
            });
        }
    }
    const maxRank = Object.values(rank).length ? Math.max(...Object.values(rank)) : -1;
    ids.forEach(id => { if (!(id in rank)) rank[id] = maxRank + 2; });

    const NODE_W = 260, COL_GAP = 340, ROW_H = 210, MARGIN = 40;
    const rowCounter = {};
    const positions = {};
    ids.forEach(id => {
        const r = rank[id];
        rowCounter[r] = (rowCounter[r] ?? -1) + 1;
        positions[id] = { x: MARGIN + r * COL_GAP, y: MARGIN + rowCounter[r] * ROW_H };
    });
    return positions;
}

function placeNodes() {
    const autoLayout = computeAutoLayout();
    Object.keys(flowData).forEach(idStr => {
        const id = Number(idStr);
        const node = document.getElementById('node-' + id);
        if (!node) return;
        const saved = savedPositions[id];
        const pos = (saved && saved.x !== null && saved.y !== null) ? saved : autoLayout[id];
        node.style.left = pos.x + 'px';
        node.style.top  = pos.y + 'px';
    });
}

// Coordinates of an element relative to #canvasInner, in unscaled layout
// pixels (walks the offsetParent chain — this stays correct regardless of
// scroll position, since offsetLeft/offsetTop are layout values, not paint ones).
function localPos(el) {
    let x = 0, y = 0, n = el;
    while (n && n !== canvasInner) {
        x += n.offsetLeft;
        y += n.offsetTop;
        n = n.offsetParent;
    }
    return { x, y };
}

function drawLines() {
    const svg = document.getElementById('linesSvg');
    svg.innerHTML = '<defs><marker id="waArrow" markerWidth="8" markerHeight="8" refX="6" refY="3" orient="auto"><path d="M0,0 L6,3 L0,6 Z" fill="var(--accent)"/></marker></defs>';

    Object.keys(flowData).forEach(idStr => {
        const id = Number(idStr);
        (flowData[id].quick_replies || []).forEach((btn, i) => {
            if (!btn.next_flow_id || !flowData[btn.next_flow_id]) return;
            const src = document.querySelector(`.wa-dot-out[data-flow="${id}"][data-idx="${i}"]`);
            const tgt = document.querySelector(`.wa-dot-in[data-flow="${btn.next_flow_id}"]`);
            if (!src || !tgt) return;

            const p1 = localPos(src), p2 = localPos(tgt);
            const midX = (p1.x + p2.x) / 2;
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', `M${p1.x},${p1.y} C${midX},${p1.y} ${midX},${p2.y} ${p2.x},${p2.y}`);
            path.setAttribute('fill', 'none');
            path.setAttribute('stroke', 'var(--accent)');
            path.setAttribute('stroke-width', '2');
            path.setAttribute('marker-end', 'url(#waArrow)');
            svg.appendChild(path);
        });
    });
}

function savePosition(id, x, y) {
    fetch(`/whatsapp/chatbot/${id}/position`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
        body: JSON.stringify({ canvas_x: Math.round(x), canvas_y: Math.round(y) }),
    }).catch(() => {});
}

function initNodeDrag(node) {
    const id = Number(node.dataset.id);
    node.addEventListener('mousedown', function (e) {
        if (e.target.closest('.wa-node-noDrag')) return; // toggle / edit / delete controls
        const startX = e.clientX, startY = e.clientY;
        const origLeft = node.offsetLeft, origTop = node.offsetTop;
        let moved = false;
        node.classList.add('dragging');

        function onMove(ev) {
            const dx = ev.clientX - startX, dy = ev.clientY - startY;
            if (Math.abs(dx) > 3 || Math.abs(dy) > 3) moved = true;
            node.style.left = Math.max(0, origLeft + dx) + 'px';
            node.style.top  = Math.max(0, origTop + dy) + 'px';
            drawLines();
        }
        function onUp() {
            document.removeEventListener('mousemove', onMove);
            document.removeEventListener('mouseup', onUp);
            node.classList.remove('dragging');
            if (moved) {
                savedPositions[id] = { x: node.offsetLeft, y: node.offsetTop };
                savePosition(id, node.offsetLeft, node.offsetTop);
            } else {
                openDrawer('edit', id);
            }
        }
        document.addEventListener('mousemove', onMove);
        document.addEventListener('mouseup', onUp);
        e.preventDefault();
    });
}

placeNodes();
drawLines();
document.querySelectorAll('.wa-node').forEach(initNodeDrag);
</script>
@endsection
