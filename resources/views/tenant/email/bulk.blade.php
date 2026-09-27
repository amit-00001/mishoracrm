@extends('layouts.app')
@section('title', 'Bulk Email')

@push('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet"/>
<style>
.quill-wrap { border:1.5px solid var(--border-default); border-radius:var(--r-sm); overflow:hidden; }
.quill-wrap .ql-toolbar { background:var(--bg-elevated); border:none; border-bottom:1px solid var(--border-subtle); padding:8px 10px; }
.quill-wrap .ql-container { border:none; font-size:13.5px; background:var(--bg-input); }
.quill-wrap .ql-editor { color:var(--text-100); min-height:160px; padding:14px 16px; line-height:1.7; }
.quill-wrap .ql-editor.ql-blank::before { color:var(--text-400); font-style:normal; }
.quill-wrap .ql-stroke { stroke:var(--text-300) !important; }
.quill-wrap .ql-fill   { fill:var(--text-300) !important; }
.quill-wrap .ql-picker-label { color:var(--text-300) !important; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.email.index') }}" style="color:var(--text-300);text-decoration:none">Email</a>
            <span style="margin:0 6px">›</span> Bulk Send
        </div>
        <div class="page-title">Bulk Email Send</div>
    </div>
    <a href="{{ route('tenant.email.send') }}" class="btn btn-secondary">Single Send</a>
</div>

<div style="display:grid;grid-template-columns:1fr 300px;gap:16px">

    <div style="background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-lg);overflow:hidden">
        <form method="POST" action="{{ route('tenant.email.bulk.send') }}" id="bulkForm" enctype="multipart/form-data">
            @csrf

            {{-- Recipients --}}
            <div style="padding:20px;border-bottom:1px solid var(--border-subtle)">
                <div style="font-size:13px;font-weight:700;color:var(--text-100);text-transform:uppercase;letter-spacing:.4px;margin-bottom:16px">Recipients</div>

                <div style="display:flex;gap:8px;margin-bottom:14px">
                    <button type="button" id="typeLeads" onclick="switchType('leads')"
                            style="flex:1;padding:8px;border-radius:var(--r-sm);border:1.5px solid var(--accent);background:var(--accent-dim);color:var(--accent);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)">
                        Leads ({{ $leads->count() }})
                    </button>
                    <button type="button" id="typeContacts" onclick="switchType('contacts')"
                            style="flex:1;padding:8px;border-radius:var(--r-sm);border:1.5px solid var(--border-default);background:none;color:var(--text-200);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)">
                        Contacts ({{ $contacts->count() }})
                    </button>
                </div>

                <input type="hidden" name="type" id="typeInput" value="leads"/>

                <input type="text" id="recipientSearch" placeholder="Filter recipients..."
                       style="width:100%;padding:8px 12px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm);color:var(--text-100);font-family:var(--font);font-size:13px;outline:none;margin-bottom:8px"
                       oninput="filterList(this.value)"/>

                {{-- Select all --}}
                <div style="display:flex;align-items:center;gap:10px;padding:9px 12px;background:var(--bg-elevated);border:1px solid var(--border-subtle);border-radius:var(--r-sm);margin-bottom:8px">
                    <input type="checkbox" id="selectAll" onchange="toggleAll(this)" style="accent-color:var(--accent);width:15px;height:15px"/>
                    <label for="selectAll" style="font-size:13px;font-weight:600;color:var(--text-100);cursor:pointer">Select All</label>
                    <span id="selCount" style="font-size:12px;color:var(--text-300);margin-left:auto;font-family:var(--mono)">0 selected</span>
                </div>

                {{-- Leads --}}
                <div id="leadsList" style="max-height:300px;overflow-y:auto;border:1.5px solid var(--border-default);border-radius:var(--r-sm)">
                    @foreach($leads as $l)
                    <label id="lead_{{ $l->id }}" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid var(--border-subtle);cursor:pointer">
                        <input type="checkbox" name="recipients[]" value="{{ $l->id }}"
                               class="rec-check" style="accent-color:var(--accent);width:15px;height:15px"
                               onchange="updateCount()"/>
                        <div style="flex:1">
                            <div style="font-size:13.5px;font-weight:600;color:var(--text-100)">{{ $l->name }}</div>
                            <div style="font-size:12px;color:var(--text-300)">{{ $l->email }}</div>
                        </div>
                        <span style="font-size:10.5px;padding:1px 7px;border-radius:20px;background:var(--bg-elevated);color:var(--text-300)">{{ ucfirst($l->status) }}</span>
                    </label>
                    @endforeach
                </div>

                {{-- Contacts --}}
                <div id="contactsList" style="display:none;max-height:300px;overflow-y:auto;border:1.5px solid var(--border-default);border-radius:var(--r-sm)">
                    @foreach($contacts as $c)
                    <label id="contact_{{ $c->id }}" style="display:flex;align-items:center;gap:10px;padding:10px 14px;border-bottom:1px solid var(--border-subtle);cursor:pointer">
                        <input type="checkbox" name="recipients[]" value="{{ $c->id }}"
                               class="rec-check" style="accent-color:var(--accent);width:15px;height:15px"
                               onchange="updateCount()"/>
                        <div style="flex:1">
                            <div style="font-size:13.5px;font-weight:600;color:var(--text-100)">{{ $c->name }}</div>
                            <div style="font-size:12px;color:var(--text-300)">{{ $c->email }}</div>
                        </div>
                        @if($c->company)<span style="font-size:10.5px;padding:1px 7px;border-radius:20px;background:var(--bg-elevated);color:var(--text-300)">{{ $c->company }}</span>@endif
                    </label>
                    @endforeach
                </div>
            </div>

            {{-- Template --}}
            <div style="padding:20px;border-bottom:1px solid var(--border-subtle)">
                <div style="font-size:13px;font-weight:700;color:var(--text-100);text-transform:uppercase;letter-spacing:.4px;margin-bottom:14px">Template</div>
                <select name="template_id" class="field-input field-select"
                        style="width:100%;padding:10px 13px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm);color:var(--text-100);font-family:var(--font);font-size:14px;outline:none;-webkit-appearance:none"
                        onchange="loadTemplate(this)">
                    <option value="">— No Template —</option>
                    @foreach($templates as $tpl)
                    <option value="{{ $tpl->id }}" data-subject="{{ $tpl->subject }}">
                        {{ $tpl->name }}
                    </option>
                    @endforeach
                </select>

                {{-- Hidden raw template bodies (kept outside the <option> tags so the HTML isn't escaped) --}}
                @foreach($templates as $tpl)
                <template id="tplbody_{{ $tpl->id }}">{!! $tpl->body !!}</template>
                @endforeach
            </div>

            {{-- Subject + Body --}}
            <div style="padding:20px;border-bottom:1px solid var(--border-subtle)">
                <div style="font-size:13px;font-weight:700;color:var(--text-100);text-transform:uppercase;letter-spacing:.4px;margin-bottom:14px">Email Content</div>
                <input type="text" name="subject" id="bulkSubject"
                       style="width:100%;padding:10px 13px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm);color:var(--text-100);font-family:var(--font);font-size:14px;outline:none;margin-bottom:12px"
                       placeholder="Email subject..." required/>
                <textarea name="body" id="bulkBody" style="display:none" required></textarea>
                <div class="quill-wrap">
                    <div id="quillBulkBody"></div>
                </div>
                <div style="font-size:11.5px;color:var(--text-400);margin-top:6px">
                    Variables @{{name}}, @{{company}}, @{{email}}, @{{business}} auto-replaced per recipient
                </div>

                <div style="margin-top:14px">
                    <label style="font-size:12.5px;font-weight:600;color:var(--text-200);text-transform:uppercase;letter-spacing:.3px;display:block;margin-bottom:6px">Attachments</label>
                    <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx"
                           style="width:100%;padding:9px 12px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm);color:var(--text-100);font-family:var(--font);font-size:13px">
                    <span style="font-size:11.5px;color:var(--text-400)">Up to 5 files, 10MB each — same attachment(s) sent to every recipient.</span>
                </div>
            </div>

            <div style="padding:16px 20px;background:var(--bg-elevated);border-top:1px solid var(--border-subtle);display:flex;justify-content:flex-end;gap:10px">
                <a href="{{ route('tenant.email.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" onclick="return confirmSend()">
                    Send to <span id="sendCount">0</span> Recipients
                </button>
            </div>
        </form>
    </div>

    {{-- Summary --}}
    <div style="background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-lg);overflow:hidden;position:sticky;top:80px">
        <div style="padding:16px;border-bottom:1px solid var(--border-subtle);font-size:13.5px;font-weight:700;color:var(--text-100)">Send Summary</div>
        @foreach([['Recipients','selSummary','0'],['Type','typeSummary','Leads'],['Template','tplSummary','None']] as $r)
        <div style="display:flex;justify-content:space-between;padding:12px 16px;border-bottom:1px solid var(--border-subtle);font-size:13px">
            <span style="color:var(--text-300)">{{ $r[0] }}</span>
            <span id="{{ $r[1] }}" style="font-weight:700;color:var(--text-100);font-family:var(--mono)">{{ $r[2] }}</span>
        </div>
        @endforeach
        <div style="padding:14px 16px">
            <div style="background:var(--amber-dim);border:1px solid rgba(248,184,78,.3);border-radius:var(--r-sm);padding:12px;font-size:12px;color:var(--amber);line-height:1.5">
                ⚠️ Make sure SMTP is configured in Settings before sending bulk emails.
            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
<script>
var quillBulkBody = new Quill('#quillBulkBody', {
    theme: 'snow',
    placeholder: 'Dear @{{name}}, ...',
    modules: {
        toolbar: [
            [{ header: [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ color: [] }, { background: [] }],
            [{ align: [] }],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['link'],
            ['clean']
        ]
    }
});

let currentType = 'leads';

function switchType(type){
    currentType = type;
    document.getElementById('typeInput').value = type;
    document.getElementById('leadsList').style.display    = type==='leads'    ? 'block':'none';
    document.getElementById('contactsList').style.display = type==='contacts' ? 'block':'none';
    const lBtn = document.getElementById('typeLeads');
    const cBtn = document.getElementById('typeContacts');
    if(type==='leads'){
        lBtn.style.cssText='flex:1;padding:8px;border-radius:var(--r-sm);border:1.5px solid var(--accent);background:var(--accent-dim);color:var(--accent);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)';
        cBtn.style.cssText='flex:1;padding:8px;border-radius:var(--r-sm);border:1.5px solid var(--border-default);background:none;color:var(--text-200);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)';
    } else {
        cBtn.style.cssText='flex:1;padding:8px;border-radius:var(--r-sm);border:1.5px solid var(--accent);background:var(--accent-dim);color:var(--accent);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)';
        lBtn.style.cssText='flex:1;padding:8px;border-radius:var(--r-sm);border:1.5px solid var(--border-default);background:none;color:var(--text-200);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)';
    }
    document.querySelectorAll('.rec-check').forEach(c => c.checked = false);
    document.getElementById('selectAll').checked = false;
    updateCount();
    document.getElementById('typeSummary').textContent = type==='leads' ? 'Leads' : 'Contacts';
}

function toggleAll(cb){
    const list = document.getElementById(currentType==='leads' ? 'leadsList':'contactsList');
    list.querySelectorAll('.rec-check').forEach(c => {
        if(c.closest('label').style.display !== 'none') c.checked = cb.checked;
    });
    updateCount();
}

function updateCount(){
    const n = document.querySelectorAll('.rec-check:checked').length;
    document.getElementById('selCount').textContent  = n + ' selected';
    document.getElementById('sendCount').textContent = n;
    document.getElementById('selSummary').textContent = n;
}

function filterList(q){
    const list = document.getElementById(currentType==='leads' ? 'leadsList':'contactsList');
    list.querySelectorAll('label').forEach(item => {
        item.style.display = item.textContent.toLowerCase().includes(q.toLowerCase()) ? 'flex':'none';
    });
}

function loadTemplate(sel){
    const opt = sel.options[sel.selectedIndex];
    if(opt.value){
        document.getElementById('bulkSubject').value = opt.dataset.subject || '';
        const tmpl = document.getElementById('tplbody_' + opt.value);
        quillBulkBody.clipboard.dangerouslyPasteHTML(tmpl ? tmpl.innerHTML : '');
        quillBulkBody.history.clear();
        document.getElementById('tplSummary').textContent = opt.textContent.trim();
    } else {
        quillBulkBody.setContents([]);
        document.getElementById('tplSummary').textContent = 'None';
    }
}

function confirmSend(){
    const html = quillBulkBody.root.innerHTML;
    if (!html || html === '<p><br></p>') { showToast('Email body is required.', 'error'); return false; }
    document.getElementById('bulkBody').value = html;

    const count = document.querySelectorAll('.rec-check:checked').length;
    if(count === 0){ showToast('Please select at least one recipient.', 'error'); return false; }
    return requireConfirm(document.getElementById('bulkForm'), `Send email to ${count} recipients?`, {ok: 'Send', danger: false});
}
</script>
@endpush