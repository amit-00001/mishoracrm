@extends('layouts.app')
@section('title', 'Email Templates')

@push('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet"/>
<style>
.tpl-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:14px; }
.tpl-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; transition:border-color .15s; }
.tpl-card:hover { border-color:var(--border-strong); }
.tpl-head { padding:14px 16px; border-bottom:1px solid var(--border-subtle); display:flex; align-items:center; justify-content:space-between; }
.tpl-name { font-size:13.5px; font-weight:700; color:var(--text-100); }
.tpl-subject { padding:8px 16px; font-size:12px; color:var(--text-300); border-bottom:1px solid var(--border-subtle); background:var(--bg-elevated); }
.tpl-body { padding:14px 16px; font-size:12.5px; color:var(--text-200); line-height:1.5; min-height:70px; max-height:100px; overflow:hidden; }
.tpl-foot { padding:12px 16px; border-top:1px solid var(--border-subtle); display:flex; gap:6px; align-items:center; }

.vars-box { background:var(--bg-elevated); border:1px solid var(--border-subtle); border-radius:var(--r-md); padding:14px 16px; margin-bottom:20px; }
.var-chip { font-size:11px; font-family:var(--mono); padding:3px 8px; background:var(--bg-surface); border:1px solid var(--border-default); border-radius:4px; color:var(--accent); cursor:pointer; display:inline-block; margin:2px; transition:all .15s; }
.var-chip:hover { border-color:var(--accent); background:var(--accent-dim); }

.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.6); display:flex; align-items:center; justify-content:center; z-index:1000; padding:20px; }
.modal-box { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); width:100%; max-width:720px; display:flex; flex-direction:column; max-height:90vh; overflow:hidden; }
.modal-head { padding:16px 20px; border-bottom:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center; flex-shrink:0; }
.modal-title { font-size:15px; font-weight:700; color:var(--text-100); }
.modal-body { padding:20px; display:flex; flex-direction:column; gap:14px; overflow-y:auto; flex:1; }
.modal-foot { padding:14px 20px; border-top:1px solid var(--border-subtle); display:flex; justify-content:flex-end; gap:8px; background:var(--bg-elevated); flex-shrink:0; }

.field { display:flex; flex-direction:column; gap:7px; }
.field-label { font-size:12.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.3px; }
.req { color:var(--red); margin-left:2px; }
.field-input { padding:10px 13px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:14px; outline:none; transition:border-color .15s; }
.field-input:focus { border-color:var(--accent); }
.field-select { -webkit-appearance:none; cursor:pointer; }

/* Quill — full toolbar for email HTML */
.quill-wrap { border:1.5px solid var(--border-default); border-radius:var(--r-sm); overflow:hidden; }
.quill-wrap .ql-toolbar { background:var(--bg-elevated); border:none; border-bottom:1px solid var(--border-subtle); padding:8px 10px; flex-wrap:wrap; }
.quill-wrap .ql-container { border:none; font-family:var(--font); font-size:14px; background:var(--bg-input); }
.quill-wrap .ql-editor { color:var(--text-100); min-height:200px; padding:14px 16px; line-height:1.7; }
.quill-wrap .ql-editor.ql-blank::before { color:var(--text-400); font-style:normal; }
.quill-wrap .ql-stroke { stroke:var(--text-300) !important; }
.quill-wrap .ql-fill   { fill:var(--text-300) !important; }
.quill-wrap .ql-picker-label { color:var(--text-300) !important; }
.quill-wrap .ql-picker-options { background:var(--bg-elevated) !important; border-color:var(--border-default) !important; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.email.index') }}" style="color:var(--text-300);text-decoration:none">Email</a>
            <span style="margin:0 6px">›</span> Templates
        </div>
        <div class="page-title">Email Templates</div>
    </div>
    <button class="btn btn-primary" onclick="openModal()">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        New Template
    </button>
</div>

<div class="vars-box">
    <div style="font-size:12px;font-weight:700;color:var(--text-200);margin-bottom:8px;text-transform:uppercase;letter-spacing:.3px">
        Available Variables — Click to insert
    </div>
    <div>
        @foreach(\App\Models\EmailTemplate::variables() as $var => $desc)
        <span class="var-chip" onclick="insertVar('{{ $var }}')" title="{{ $desc }}">{{ $var }}</span>
        @endforeach
    </div>
</div>

@if($templates->isEmpty())
<div class="card" style="padding:60px 20px;text-align:center">
    <div style="font-size:40px;margin-bottom:12px">📧</div>
    <div style="font-size:15px;font-weight:700;color:var(--text-100);margin-bottom:6px">No email templates</div>
    <div style="font-size:13px;color:var(--text-300);margin-bottom:20px">Create reusable HTML email templates</div>
    <button class="btn btn-primary" onclick="openModal()">Create Template</button>
</div>
@else
<div class="tpl-grid">
    @foreach($templates as $tpl)
    <div class="tpl-card">
        <div class="tpl-head">
            <div class="tpl-name">{{ $tpl->name }}</div>
            <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;background:var(--accent-dim);color:var(--accent)">
                {{ $categories[$tpl->category] ?? ucfirst($tpl->category) }}
            </span>
        </div>
        <div class="tpl-subject">
            <strong style="color:var(--text-200)">Subject:</strong> {{ $tpl->subject }}
        </div>
        <div class="tpl-body">
            {{ \Illuminate\Support\Str::limit(strip_tags($tpl->body), 120) }}
        </div>
        <div class="tpl-foot">
            <a href="{{ route('tenant.email.send') }}?template_id={{ $tpl->id }}"
               class="btn btn-secondary btn-sm">Use</a>
            <button class="btn btn-secondary btn-sm"
                    onclick='editTemplate({{ $tpl->id }}, @json($tpl->name), @json($tpl->subject), @json($tpl->body), "{{ $tpl->category }}")'>
                Edit
            </button>
            <form method="POST" action="{{ route('tenant.email.templates.delete', $tpl->id) }}"
                  data-confirm="Delete this template?" data-confirm-ok="Delete">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--red)">Delete</button>
            </form>
            <span style="font-size:11.5px;color:var(--text-400);margin-left:auto;font-family:var(--mono)">{{ $tpl->logs_count }} sent</span>
        </div>
    </div>
    @endforeach
</div>

{{-- Pagination --}}
@if($templates->hasPages())
<div style="display:flex;align-items:center;justify-content:space-between;padding:14px 0;margin-top:8px;font-size:13px;color:var(--text-300)">
    <span>Showing {{ $templates->firstItem() }}–{{ $templates->lastItem() }} of {{ $templates->total() }}</span>
    <div style="display:flex;gap:4px">
        <a href="{{ $templates->previousPageUrl() ?? '#' }}"
           style="padding:5px 10px;border-radius:var(--r-sm);border:1px solid var(--border-default);color:var(--text-200);text-decoration:none;font-size:13px;{{ !$templates->previousPageUrl() ? 'opacity:.4;pointer-events:none' : '' }}">←</a>
        @foreach($templates->getUrlRange(max(1,$templates->currentPage()-2), min($templates->lastPage(),$templates->currentPage()+2)) as $page => $url)
        <a href="{{ $url }}"
           style="padding:5px 10px;border-radius:var(--r-sm);border:1px solid var(--border-default);text-decoration:none;font-size:13px;{{ $page==$templates->currentPage() ? 'background:var(--accent);border-color:var(--accent);color:#fff' : 'color:var(--text-200)' }}">{{ $page }}</a>
        @endforeach
        <a href="{{ $templates->nextPageUrl() ?? '#' }}"
           style="padding:5px 10px;border-radius:var(--r-sm);border:1px solid var(--border-default);color:var(--text-200);text-decoration:none;font-size:13px;{{ !$templates->nextPageUrl() ? 'opacity:.4;pointer-events:none' : '' }}">→</a>
    </div>
</div>
@endif
@endif

{{-- Modal --}}
<div class="modal-overlay" id="tplModal" style="display:none" onclick="if(event.target===this)closeModal()">
    <div class="modal-box">
        <div class="modal-head">
            <div class="modal-title" id="modalTitle">New Email Template</div>
            <button onclick="closeModal()" style="background:none;border:none;cursor:pointer;color:var(--text-300);font-size:20px;line-height:1">✕</button>
        </div>
        <form method="POST" id="tplForm" action="{{ route('tenant.email.templates.store') }}">
            @csrf
            <div id="methodField"></div>
            <div class="modal-body">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="field">
                        <label class="field-label">Template Name <span class="req">*</span></label>
                        <input type="text" name="name" id="tplName" class="field-input"
                               placeholder="e.g. Welcome Email" required/>
                    </div>
                    <div class="field">
                        <label class="field-label">Category <span class="req">*</span></label>
                        <select name="category" id="tplCategory" class="field-input field-select">
                            @foreach($categories as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="field">
                    <label class="field-label">Subject <span class="req">*</span></label>
                    <input type="text" name="subject" id="tplSubject" class="field-input"
                           placeholder="Email subject line..." required/>
                </div>

                {{-- Variable chips inside modal --}}
                <div>
                    <div class="field-label" style="margin-bottom:6px">Insert Variable</div>
                    <div>
                        @foreach(\App\Models\EmailTemplate::variables() as $var => $desc)
                        <span class="var-chip" onclick="insertVar('{{ $var }}')" title="{{ $desc }}">{{ $var }}</span>
                        @endforeach
                    </div>
                </div>

                {{-- Quill HTML editor --}}
                <div class="field">
                    <label class="field-label">Email Body <span class="req">*</span></label>
                    <textarea name="body" id="tplBodyHidden" style="display:none" required></textarea>
                    <div class="quill-wrap">
                        <div id="quillEmail"></div>
                    </div>
                    <span style="font-size:11.5px;color:var(--text-400)">
                        HTML email body. Variables like @{{name}}, @{{company}} will be auto-replaced when sending.
                    </span>
                </div>

            </div>
            <div class="modal-foot">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary" onclick="return syncEmail()">
                    Save Template
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
<script>
// ── Full toolbar for email HTML ───────────────────────────────────
const quillEmail = new Quill('#quillEmail', {
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

function syncEmail() {
    const html = quillEmail.root.innerHTML.trim();
    if (!html || html === '<p><br></p>') { alert('Email body is required.'); return false; }
    document.getElementById('tplBodyHidden').value = html;
    return true;
}

function insertVar(v) {
    quillEmail.focus();
    const range = quillEmail.getSelection() || { index: quillEmail.getLength() };
    quillEmail.insertText(range.index, v, 'user');
    quillEmail.setSelection(range.index + v.length);
}

function openModal() {
    document.getElementById('tplModal').style.display = 'flex';
    setTimeout(() => quillEmail.focus(), 150);
}

function closeModal() {
    document.getElementById('tplModal').style.display = 'none';
    resetForm();
}

function resetForm() {
    document.getElementById('tplForm').action = '{{ route("tenant.email.templates.store") }}';
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('modalTitle').textContent = 'New Email Template';
    document.getElementById('tplName').value    = '';
    document.getElementById('tplSubject').value = '';
    document.getElementById('tplCategory').value = 'general';
    document.getElementById('tplBodyHidden').value = '';
    quillEmail.setContents([]);
}

function editTemplate(id, name, subject, body, category) {
    document.getElementById('tplForm').action = '/email/templates/' + id;
    document.getElementById('methodField').innerHTML = '<input type="hidden" name="_method" value="PUT"/>';
    document.getElementById('modalTitle').textContent = 'Edit Email Template';
    document.getElementById('tplName').value    = name;
    document.getElementById('tplSubject').value = subject;
    document.getElementById('tplCategory').value = category;

    // Load HTML into Quill
    quillEmail.root.innerHTML = body;

    openModal();
}
</script>
@endpush