@extends('layouts.app')
@section('title', 'Bulk WhatsApp')

@push('styles')
<style>
.bulk-layout { display:grid; grid-template-columns:1fr 320px; gap:16px; }
@media(max-width:1024px) { .bulk-layout { grid-template-columns:1fr; } }

.form-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.form-section { padding:20px; border-bottom:1px solid var(--border-subtle); }
.form-section:last-child { border-bottom:none; }
.fs-title { font-size:13px; font-weight:700; color:var(--text-100); text-transform:uppercase; letter-spacing:.4px; margin-bottom:16px; }

.field { display:flex; flex-direction:column; gap:7px; margin-bottom:14px; }
.field:last-child { margin-bottom:0; }
.field-label { font-size:12.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.3px; }
.req { color:var(--red); margin-left:2px; }
.field-input { padding:10px 13px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:14px; outline:none; transition:border-color .15s; }
.field-input:focus { border-color:var(--accent); }
.field-select { -webkit-appearance:none; cursor:pointer; }
.field-textarea { resize:vertical; min-height:120px; font-size:13.5px; line-height:1.6; }

/* Recipient list */
.recipient-list { max-height:360px; overflow-y:auto; border:1.5px solid var(--border-default); border-radius:var(--r-sm); }
.recipient-item {
    display:flex; align-items:center; gap:10px;
    padding:10px 14px; border-bottom:1px solid var(--border-subtle);
    transition:background .1s;
}
.recipient-item:last-child { border-bottom:none; }
.recipient-item:hover { background:var(--bg-elevated); }
.recipient-item input[type="checkbox"] { accent-color:var(--accent); width:15px; height:15px; flex-shrink:0; }
.rec-name { font-size:13.5px; font-weight:600; color:var(--text-100); }
.rec-phone { font-size:12px; color:var(--text-300); font-family:var(--mono); }
.rec-badge { font-size:10.5px; padding:1px 7px; border-radius:20px; background:var(--bg-elevated); color:var(--text-300); margin-left:auto; }

/* Select all bar */
.select-bar {
    display:flex; align-items:center; gap:10px;
    padding:10px 14px; background:var(--bg-elevated);
    border:1.5px solid var(--border-default); border-radius:var(--r-sm);
    margin-bottom:8px;
}
.select-bar label { font-size:13px; font-weight:600; color:var(--text-100); cursor:pointer; }
.sel-count { font-size:12px; color:var(--text-300); margin-left:auto; font-family:var(--mono); }

/* Summary panel */
.summary-panel {
    background:var(--bg-surface); border:1px solid var(--border-default);
    border-radius:var(--r-lg); overflow:hidden; position:sticky; top:80px;
}
.summary-row { display:flex; justify-content:space-between; padding:12px 16px; border-bottom:1px solid var(--border-subtle); font-size:13px; }
.summary-row:last-child { border-bottom:none; }
.summary-label { color:var(--text-300); }
.summary-value { font-weight:700; color:var(--text-100); font-family:var(--mono); }

.form-actions { padding:16px 20px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); display:flex; justify-content:flex-end; gap:10px; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.whatsapp.index') }}" style="color:var(--text-300);text-decoration:none">WhatsApp</a>
            <span style="margin:0 6px">›</span> Bulk Send
        </div>
        <div class="page-title">Bulk WhatsApp Send</div>
    </div>
    <a href="{{ route('tenant.whatsapp.send') }}" class="btn btn-secondary">Single Send</a>
</div>

<div class="bulk-layout">

    {{-- Form --}}
    <div class="form-card">
        <form method="POST" action="{{ route('tenant.whatsapp.bulk.send') }}" id="bulkForm" enctype="multipart/form-data">
            @csrf

            {{-- Recipients type --}}
            <div class="form-section">
                <div class="fs-title">Select Recipients</div>

                {{-- Type toggle --}}
                <div style="display:flex;gap:8px;margin-bottom:16px">
                    <button type="button" class="btn btn-secondary" id="typeLeads"
                            onclick="switchType('leads')"
                            style="flex:1;background:var(--accent-dim);border-color:var(--accent);color:var(--accent)">
                        Leads ({{ $leads->count() }})
                    </button>
                    <button type="button" class="btn btn-secondary" id="typeContacts"
                            onclick="switchType('contacts')"
                            style="flex:1">
                        Contacts ({{ $contacts->count() }})
                    </button>
                </div>

                <input type="hidden" name="type" id="typeInput" value="leads"/>

                {{-- Search filter --}}
                <input type="text" id="recipientSearch" class="field-input"
                       placeholder="Filter recipients..." style="width:100%;margin-bottom:8px"
                       oninput="applyFilters()"/>

                {{-- Leads only: narrow the list by status / source, then "Select All" picks just those --}}
                <div id="leadFilters" style="display:flex;gap:8px;margin-bottom:8px">
                    <select id="filterStatus" class="field-input field-select" style="flex:1" onchange="applyFilters()">
                        <option value="">All statuses</option>
                        @foreach(\App\Models\Lead::statuses() as $key => $st)
                        <option value="{{ $key }}">{{ $st['label'] }}</option>
                        @endforeach
                    </select>
                    <select id="filterSource" class="field-input field-select" style="flex:1" onchange="applyFilters()">
                        <option value="">All sources</option>
                        @foreach(\App\Models\Lead::sources() as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Select all --}}
                <div class="select-bar">
                    <input type="checkbox" id="selectAll" onchange="toggleAll(this)"/>
                    <label for="selectAll">Select All</label>
                    <span class="sel-count" id="selCount">0 selected</span>
                </div>

                {{-- Leads list --}}
                <div class="recipient-list" id="leadsList">
                    @foreach($leads as $l)
                    @php $optL = isset($optedOut[preg_replace('/[^0-9]/', '', (string) $l->phone)]); @endphp
                    <label class="recipient-item" id="lead_{{ $l->id }}" data-status="{{ $l->status }}" data-source="{{ $l->source }}" @if($optL) style="opacity:.55" title="Opted out of broadcasts" @endif>
                        <input type="checkbox" name="recipients[]" value="{{ $l->id }}"
                               class="rec-check" onchange="updateCount()" {{ $optL ? 'disabled' : '' }}/>
                        <div>
                            <div class="rec-name">{{ $l->name }}</div>
                            <div class="rec-phone">{{ $l->phone }}</div>
                        </div>
                        <span class="rec-badge">{{ $optL ? 'Opted out' : ucfirst($l->status) }}</span>
                    </label>
                    @endforeach
                </div>

                {{-- Contacts list --}}
                <div class="recipient-list" id="contactsList" style="display:none">
                    @foreach($contacts as $c)
                    @php $optC = isset($optedOut[preg_replace('/[^0-9]/', '', (string) $c->phone)]); @endphp
                    <label class="recipient-item" id="contact_{{ $c->id }}" @if($optC) style="opacity:.55" title="Opted out of broadcasts" @endif>
                        <input type="checkbox" name="recipients[]" value="{{ $c->id }}"
                               class="rec-check" onchange="updateCount()" {{ $optC ? 'disabled' : '' }}/>
                        <div>
                            <div class="rec-name">{{ $c->name }}</div>
                            <div class="rec-phone">{{ $c->phone }}</div>
                        </div>
                        @if($c->company)
                        <span class="rec-badge">{{ $c->company }}</span>
                        @endif
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Template --}}
            <div class="form-section">
                <div class="fs-title">Template (Optional)</div>
                <div class="field">
                    <label class="field-label">Select Template</label>
                    <select name="template_id" class="field-input field-select" onchange="loadTemplate(this.value)">
                        <option value="">— No Template —</option>
                        @foreach($templates as $tpl)
                        <option value="{{ $tpl->id }}" data-body="{{ $tpl->body }}">
                            {{ $tpl->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Approved Meta template (WhatsApp Gateway numbers only) --}}
            @include('tenant.whatsapp._meta-template-picker', ['metaTemplates' => $metaTemplates ?? [], 'bulk' => true])

            {{-- Message --}}
            <div class="form-section">
                <div class="fs-title">Message <span class="req">*</span></div>
                <div class="field">
                    <textarea name="message" id="bulkMessage" class="field-input field-textarea"
                              placeholder="Hi @{{name}}, ..." required
                              oninput="updateSummary()"></textarea>
                    <span style="font-size:11.5px;color:var(--text-400)">
                        Variables like @{{name}}, @{{company}} will be auto-replaced for each recipient
                    </span>
                </div>
            </div>

            {{-- Attachment --}}
            <div class="form-section">
                <div class="fs-title">Attachment (Optional)</div>
                <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" class="field-input">
                <div style="font-size:11.5px;color:var(--text-400);margin-top:6px">One image or document, up to 16MB — sent to every recipient.</div>
            </div>

            @if($canSchedule ?? false)
            <div class="form-section">
                <div class="fs-title">When to send</div>
                <div style="display:flex;gap:18px;flex-wrap:wrap;margin-bottom:8px;font-size:13.5px">
                    <label style="display:flex;gap:6px;align-items:center;cursor:pointer"><input type="radio" name="when" value="now" checked onchange="whenChanged()"> Send now</label>
                    <label style="display:flex;gap:6px;align-items:center;cursor:pointer"><input type="radio" name="when" value="later" onchange="whenChanged()"> Schedule for later</label>
                </div>
                <div id="scheduleBox" style="display:none">
                    <input type="datetime-local" id="scheduleAt" class="field-input" style="max-width:260px">
                    <div style="font-size:11.5px;color:var(--text-400);margin-top:6px">Between 2 minutes and 60 days from now (India time). Recipients are fixed when you schedule; people who opt out before the time are skipped. You can cancel from Campaigns.</div>
                </div>
            </div>
            @endif

            <div class="form-actions">
                <a href="{{ route('tenant.whatsapp.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" id="submitBtn"
                        style="background:#25D366;border-color:#25D366"
                        onclick="return confirmSend()">
                    Send to <span id="sendCount">0</span> Recipients
                </button>
            </div>
        </form>
    </div>

    {{-- Summary panel --}}
    <div class="summary-panel">
        <div style="padding:16px;border-bottom:1px solid var(--border-subtle)">
            <div style="font-size:13.5px;font-weight:700;color:var(--text-100)">Send Summary</div>
        </div>
        <div class="summary-row">
            <span class="summary-label">Recipients</span>
            <span class="summary-value" id="summaryCount">0</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Type</span>
            <span class="summary-value" id="summaryType">Leads</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Template</span>
            <span class="summary-value" id="summaryTemplate">None</span>
        </div>
        <div class="summary-row">
            <span class="summary-label">Characters</span>
            <span class="summary-value" id="summaryChars">0</span>
        </div>

        <div style="padding:14px 16px">
            @if($isConnected ?? false)
            <div style="background:var(--green-dim);border:1px solid rgba(29,158,117,.3);border-radius:var(--r-sm);padding:12px;font-size:12px;color:var(--green)">
                ✅ WhatsApp API connected — messages send for real. One attachment (if added) goes to every recipient.
            </div>
            @else
            <div style="background:var(--amber-dim);border:1px solid rgba(248,184,78,.3);border-radius:var(--r-sm);padding:12px;font-size:12px;color:var(--amber)">
                ⚠️ WhatsApp API is not connected — connect it in <a href="{{ route('tenant.whatsapp.api-settings') }}" style="color:inherit;text-decoration:underline;font-weight:600">API Settings</a> before sending.
            </div>
            @endif
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
let currentType = 'leads';

function switchType(type) {
    currentType = type;
    document.getElementById('typeInput').value = type;
    document.getElementById('leadsList').style.display    = type==='leads'    ? 'block' : 'none';
    document.getElementById('contactsList').style.display = type==='contacts' ? 'block' : 'none';

    const lBtn = document.getElementById('typeLeads');
    const cBtn = document.getElementById('typeContacts');
    if(type === 'leads'){
        lBtn.style.cssText = 'flex:1;background:var(--accent-dim);border-color:var(--accent);color:var(--accent)';
        cBtn.style.cssText = 'flex:1';
    } else {
        cBtn.style.cssText = 'flex:1;background:var(--accent-dim);border-color:var(--accent);color:var(--accent)';
        lBtn.style.cssText = 'flex:1';
    }

    // Uncheck all
    document.querySelectorAll('.rec-check').forEach(c => c.checked = false);
    document.getElementById('selectAll').checked = false;
    updateCount();
    document.getElementById('summaryType').textContent = type === 'leads' ? 'Leads' : 'Contacts';
    document.getElementById('leadFilters').style.display = type === 'leads' ? 'flex' : 'none';
    applyFilters();
}

function toggleAll(cb){
    const list = document.getElementById(currentType === 'leads' ? 'leadsList' : 'contactsList');
    list.querySelectorAll('.rec-check').forEach(c => {
        if(!c.disabled && c.closest('.recipient-item').style.display !== 'none') c.checked = cb.checked;
    });
    updateCount();
}

function updateCount(){
    const checked = document.querySelectorAll('.rec-check:checked').length;
    document.getElementById('selCount').textContent = checked + ' selected';
    document.getElementById('sendCount').textContent = checked;
    document.getElementById('summaryCount').textContent = checked;
}

function loadTemplate(id){
    const sel = document.querySelector(`option[value="${id}"]`);
    if(sel && sel.dataset.body){
        document.getElementById('bulkMessage').value = sel.dataset.body;
    }
    const name = sel?.textContent?.trim() || 'None';
    document.getElementById('summaryTemplate').textContent = id ? name : 'None';
    updateSummary();
}

// Approved template picked / cleared (see _meta-template-picker) — the text is
// fixed by Meta, so the box is locked and just shows the filled-in template.
function onMetaTemplateChange(text){
    const msg = document.getElementById('bulkMessage');
    msg.readOnly = text !== null;
    msg.value = text ?? '';
    updateSummary();
}

// Text search + (for leads) status and source. "Select All" only ticks what is visible.
function applyFilters(){
    const query  = document.getElementById('recipientSearch').value.toLowerCase();
    const status = document.getElementById('filterStatus').value;
    const source = document.getElementById('filterSource').value;
    const leads  = currentType === 'leads';
    const list   = document.getElementById(leads ? 'leadsList' : 'contactsList');
    let shown = 0;
    list.querySelectorAll('.recipient-item').forEach(item => {
        const ok = item.textContent.toLowerCase().includes(query)
            && (!leads || !status || item.dataset.status === status)
            && (!leads || !source || item.dataset.source === source);
        item.style.display = ok ? 'flex' : 'none';
        if (ok) shown++;
    });
    document.getElementById('selectAll').checked = false;
    document.querySelector('label[for="selectAll"]').textContent = 'Select All (' + shown + ' shown)';
}

function updateSummary(){
    const msg = document.getElementById('bulkMessage').value;
    document.getElementById('summaryChars').textContent = msg.length;
}

// "Send now" / "Schedule for later" (only present when the plan includes scheduling).
function whenChanged(){
    const later = document.querySelector('input[name="when"]:checked')?.value === 'later';
    document.getElementById('scheduleBox').style.display = later ? 'block' : 'none';
    const btn = document.getElementById('submitBtn');
    btn.firstChild.textContent = later ? 'Schedule for ' : 'Send to ';
    if (later) {
        const min = new Date(Date.now() + 5*60000 - new Date().getTimezoneOffset()*60000).toISOString().slice(0,16);
        document.getElementById('scheduleAt').min = min;
    }
}

function confirmSend(){
    const count = document.querySelectorAll('.rec-check:checked').length;
    if(count === 0){ showToast('Please select at least one recipient.', 'error'); return false; }

    const later = document.querySelector('input[name="when"]:checked')?.value === 'later';
    const form  = document.getElementById('bulkForm');
    let old = form.querySelector('input[name="schedule_at"]');
    if (old) old.remove();

    if (later) {
        const at = document.getElementById('scheduleAt').value;
        if (!at) { showToast('Pick the date and time to send.', 'error'); return false; }
        const hidden = document.createElement('input');
        hidden.type = 'hidden'; hidden.name = 'schedule_at'; hidden.value = at;
        form.appendChild(hidden);
        return requireConfirm(form, `Schedule this message to ${count} recipients for ${at.replace('T', ' ')}?`, {ok: 'Schedule', danger: false});
    }
    return requireConfirm(form, `Send WhatsApp message to ${count} recipients?`, {ok: 'Send', danger: false});
}
</script>
@endpush