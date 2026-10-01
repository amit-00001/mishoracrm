@extends('layouts.app')
@section('title', 'Send WhatsApp')

@push('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet"/>
<style>
.send-layout { display:grid; grid-template-columns:1fr 320px; gap:16px; }
@media(max-width:1024px) { .send-layout { grid-template-columns:1fr; } }

.form-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.form-section { padding:20px; border-bottom:1px solid var(--border-subtle); }
.form-section:last-child { border-bottom:none; }
.fs-title { font-size:13px; font-weight:700; color:var(--text-100); text-transform:uppercase; letter-spacing:.4px; margin-bottom:14px; }

.field { display:flex; flex-direction:column; gap:7px; margin-bottom:14px; }
.field:last-child { margin-bottom:0; }
.field-label { font-size:12.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.3px; }
.req { color:var(--red); margin-left:2px; }
.field-input { padding:10px 13px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:14px; outline:none; transition:border-color .15s; }
.field-input:focus { border-color:var(--accent); }
.field-select { -webkit-appearance:none; cursor:pointer; }

/* Quill */
.quill-wrap { border:1.5px solid var(--border-default); border-radius:var(--r-sm); overflow:hidden; }
.quill-wrap .ql-toolbar { background:var(--bg-elevated); border:none; border-bottom:1px solid var(--border-subtle); padding:8px 10px; }
.quill-wrap .ql-container { border:none; font-family:var(--font); font-size:14px; background:var(--bg-input); }
.quill-wrap .ql-editor { color:var(--text-100); min-height:160px; padding:12px 14px; line-height:1.65; }
.quill-wrap .ql-editor.ql-blank::before { color:var(--text-400); font-style:normal; }
.quill-wrap .ql-stroke { stroke:var(--text-300) !important; }
.quill-wrap .ql-fill   { fill:var(--text-300) !important; }
.quill-wrap .ql-picker-label { color:var(--text-300) !important; }

/* Variable chips */
.var-chip { font-size:11px; font-family:var(--mono); padding:3px 8px; background:var(--bg-surface); border:1px solid var(--border-default); border-radius:4px; color:var(--accent); cursor:pointer; display:inline-block; margin:2px; transition:all .15s; }
.var-chip:hover { border-color:var(--accent); background:var(--accent-dim); }

/* Template chips */
.tpl-chip { padding:5px 12px; border-radius:20px; font-size:12px; font-weight:600; border:1.5px solid var(--border-default); background:none; cursor:pointer; color:var(--text-200); transition:all .15s; font-family:var(--font); }
.tpl-chip:hover { border-color:#25D366; color:#25D366; background:#E8F5E9; }
.tpl-chip.active { border-color:#25D366; color:#25D366; background:#E8F5E9; }

/* WhatsApp preview */
.wa-preview-box {
    margin:16px; background:#E8F5E9; border-radius:10px;
    padding:12px 14px; font-size:13.5px; color:#1a1a1a;
    line-height:1.65; min-height:80px; word-break:break-word;
    white-space:pre-wrap; position:relative;
}
.wa-preview-box::after {
    content:''; position:absolute; left:-8px; top:12px;
    width:0; height:0;
    border-top:6px solid transparent;
    border-right:10px solid #E8F5E9;
    border-bottom:6px solid transparent;
}
.wa-time { font-size:11px; color:#888; text-align:right; margin-top:4px; padding:0 16px 16px; }

.form-actions { padding:16px 20px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); display:flex; justify-content:flex-end; gap:10px; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.whatsapp.index') }}" style="color:var(--text-300);text-decoration:none">WhatsApp</a>
            <span style="margin:0 6px">›</span> Send Message
        </div>
        <div class="page-title">Send WhatsApp Message</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('tenant.whatsapp.bulk') }}" class="btn btn-secondary">Bulk Send</a>
        <a href="{{ route('tenant.whatsapp.templates') }}" class="btn btn-secondary">Templates</a>
    </div>
</div>

@unless($isConnected ?? false)
<div style="background:var(--amber-dim);border:1px solid rgba(248,184,78,.3);border-radius:var(--r-sm);padding:12px 16px;font-size:12.5px;color:var(--amber);margin-bottom:16px">
    ⚠️ WhatsApp API is not connected — messages won't send until you connect it in
    <a href="{{ route('tenant.whatsapp.api-settings') }}" style="color:inherit;text-decoration:underline;font-weight:600">API Settings</a>.
</div>
@endunless

<div class="send-layout">

    {{-- Form --}}
    <div class="form-card">
        <form method="POST" action="{{ route('tenant.whatsapp.send.store') }}" id="sendForm" enctype="multipart/form-data">
            @csrf

            {{-- Recipient --}}
            <div class="form-section">
                <div class="fs-title">Recipient</div>
                <div class="field">
                    <label class="field-label">Quick Select</label>
                    <select class="field-input field-select" onchange="fillRecipient(this)">
                        <option value="">— Select or enter manually —</option>
                        @if($leads->isNotEmpty())
                        <optgroup label="Leads">
                            @foreach($leads as $l)
                            <option value="lead|{{ $l->id }}|{{ $l->name }}|{{ $l->phone }}">
                                {{ $l->name }} — {{ $l->phone }}
                            </option>
                            @endforeach
                        </optgroup>
                        @endif
                        @if($contacts->isNotEmpty())
                        <optgroup label="Contacts">
                            @foreach($contacts as $c)
                            <option value="contact|{{ $c->id }}|{{ $c->name }}|{{ $c->phone }}">
                                {{ $c->name }} — {{ $c->phone }}
                            </option>
                            @endforeach
                        </optgroup>
                        @endif
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="field">
                        <label class="field-label">Phone <span class="req">*</span></label>
                        <input type="text" name="to_phone" id="toPhone" class="field-input"
                               placeholder="+91 98765 43210"
                               value="{{ $lead?->phone ?? $contact?->phone }}" required/>
                    </div>
                    <div class="field">
                        <label class="field-label">Name</label>
                        <input type="text" name="to_name" id="toName" class="field-input"
                               placeholder="Contact name"
                               value="{{ $lead?->name ?? $contact?->name }}"
                               oninput="updatePreview()"/>
                    </div>
                </div>
                <input type="hidden" name="lead_id"    id="leadId"    value="{{ $lead?->id }}"/>
                <input type="hidden" name="contact_id" id="contactId" value="{{ $contact?->id }}"/>
            </div>

            {{-- Template select --}}
            <div class="form-section">
                <div class="fs-title">Template (Optional)</div>
                <input type="hidden" name="template_id" id="templateId"/>
                <div style="display:flex;flex-wrap:wrap;gap:6px">
                    @foreach($templates as $tpl)
                    <button type="button" class="tpl-chip" id="chip_{{ $tpl->id }}"
                            onclick='selectTemplate({{ $tpl->id }}, @json($tpl->body))'>
                        {{ $tpl->name }}
                    </button>
                    @endforeach
                    <button type="button" class="tpl-chip" onclick="clearTemplate()">✕ Clear</button>
                </div>
            </div>

            {{-- Approved Meta template (WhatsApp Gateway numbers only) --}}
            @include('tenant.whatsapp._meta-template-picker', ['metaTemplates' => $metaTemplates ?? [], 'bulk' => false])

            {{-- Message body with Quill --}}
            <div class="form-section">
                <div class="fs-title">Message <span class="req">*</span></div>

                {{-- Variable insert --}}
                <div style="margin-bottom:10px">
                    <div style="font-size:11.5px;color:var(--text-300);margin-bottom:6px">Insert variable:</div>
                    @foreach(\App\Models\WhatsappTemplate::variables() as $var => $desc)
                    <span class="var-chip" onclick="insertVar('{{ $var }}')" title="{{ $desc }}">{{ $var }}</span>
                    @endforeach
                </div>

                {{-- Hidden input for form --}}
                <textarea name="message" id="messageHidden" style="display:none" required></textarea>

                {{-- Quill --}}
                <div class="quill-wrap">
                    <div id="quillSend"></div>
                </div>
            </div>

            {{-- Attachment --}}
            <div class="form-section">
                <div class="fs-title">Attachment (Optional)</div>
                <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" class="field-input">
                <div style="font-size:11.5px;color:var(--text-400);margin-top:6px">One image or document, up to 16MB.</div>
            </div>

            <div class="form-actions">
                <a href="{{ route('tenant.whatsapp.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" onclick="return syncMessage()"
                        style="background:#25D366;border-color:#25D366">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 01-.923 1.785A5.969 5.969 0 006 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337z"/>
                    </svg>
                    Send via WhatsApp
                </button>
            </div>
        </form>
    </div>

    {{-- Preview panel --}}
    <div style="background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-lg);overflow:hidden;position:sticky;top:80px">
        <div style="padding:14px 16px;border-bottom:1px solid var(--border-subtle)">
            <div style="font-size:13px;font-weight:700;color:var(--text-100)">Message Preview</div>
            <div style="font-size:12px;color:var(--text-300);margin-top:2px">
                To: <strong id="previewName" style="color:var(--text-100)">—</strong>
            </div>
        </div>

        {{-- WhatsApp-style preview --}}
        <div style="background:#f0f2f5;min-height:200px;padding:12px 0">
            <div class="wa-preview-box" id="previewBody">
                Your message will appear here...
            </div>
            <div class="wa-time">{{ now()->format('h:i A') }}</div>
        </div>

        {{-- Character count --}}
        <div style="padding:12px 16px;border-top:1px solid var(--border-subtle);display:flex;justify-content:space-between;font-size:12px;color:var(--text-300)">
            <span>Characters</span>
            <span id="charCount" style="font-family:var(--mono);color:var(--text-100)">0</span>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
<script>
// ── Init Quill ────────────────────────────────────────────────────
const quillSend = new Quill('#quillSend', {
    theme: 'snow',
    placeholder: 'Type your message...',
    modules: {
        toolbar: [
            ['bold', 'italic', 'underline'],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['clean']
        ]
    }
});

// Update preview on change
quillSend.on('text-change', updatePreview);

// ── Sync message to hidden input ──────────────────────────────────
function syncMessage() {
    const text = quillSend.getText().trim();
    if (!text) { alert('Message is required.'); return false; }
    document.getElementById('messageHidden').value = text;
    return true;
}

// ── Insert variable at cursor ─────────────────────────────────────
function insertVar(v) {
    quillSend.focus();
    const range = quillSend.getSelection() || { index: quillSend.getLength() };
    quillSend.insertText(range.index, v);
    quillSend.setSelection(range.index + v.length);
    updatePreview();
}

// ── Fill recipient from quick select ─────────────────────────────
function fillRecipient(sel) {
    if (!sel.value) return;
    const [type, id, name, phone] = sel.value.split('|');
    document.getElementById('toPhone').value  = phone;
    document.getElementById('toName').value   = name;
    document.getElementById('leadId').value    = type === 'lead'    ? id : '';
    document.getElementById('contactId').value = type === 'contact' ? id : '';
    updatePreview();
}

// ── Select template ───────────────────────────────────────────────
function selectTemplate(id, body) {
    document.querySelectorAll('.tpl-chip').forEach(c => c.classList.remove('active'));
    document.getElementById('chip_' + id).classList.add('active');
    document.getElementById('templateId').value = id;
    quillSend.setText(body);
    updatePreview();
}

function clearTemplate() {
    document.querySelectorAll('.tpl-chip').forEach(c => c.classList.remove('active'));
    document.getElementById('templateId').value = '';
    quillSend.setContents([]);
    updatePreview();
}

// ── Approved template picked / cleared (see _meta-template-picker) ──
// With a template the text is fixed by Meta, so the editor is locked.
function onMetaTemplateChange(text) {
    quillSend.enable(text === null);
    quillSend.setText(text ?? '');
    updatePreview();
}

// ── Live preview update ───────────────────────────────────────────
function updatePreview() {
    const name    = document.getElementById('toName').value || '—';
    const text    = quillSend.getText().trim() || 'Your message will appear here...';
    document.getElementById('previewName').textContent = name;
    document.getElementById('previewBody').textContent = text;
    document.getElementById('charCount').textContent   = quillSend.getText().trim().length;
}

// Init
document.getElementById('toName').addEventListener('input', updatePreview);
document.getElementById('toPhone').addEventListener('input', updatePreview);
updatePreview();
</script>
@endpush