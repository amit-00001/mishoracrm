@extends('layouts.app')
@section('title', 'Invoice PDF Style')

@push('styles')
<link href="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css" rel="stylesheet"/>
<style>
.ips-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; align-items:start; }
@media(max-width:900px) { .ips-grid { grid-template-columns:1fr; } }

/* ── Simple / Custom mode tabs ── */
.ips-mode-tabs { display:flex; gap:0; margin-bottom:16px; border-bottom:2px solid var(--border-subtle); }
.ips-mode-tab {
    padding:10px 16px; font-size:13px; font-weight:600; cursor:pointer;
    color:var(--text-300); border-bottom:2px solid transparent; margin-bottom:-2px;
    transition:all .15s;
}
.ips-mode-tab.active { color:var(--accent); border-bottom-color:var(--accent); }
.ips-mode-panel { display:none; }
.ips-mode-panel.active { display:block; }

/* ── Custom template editor ── */
.ips-var-groups { display:flex; flex-direction:column; gap:12px; margin-bottom:16px; }
.ips-var-group-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.4px; color:var(--text-300); margin-bottom:6px; }
.ips-var-btns { display:flex; flex-wrap:wrap; gap:6px; }
.ips-var-btn {
    font-size:11.5px; font-weight:600; padding:5px 10px; border-radius:20px;
    background:var(--bg-elevated); border:1px solid var(--border-default); color:var(--accent);
    cursor:pointer; transition:all .15s;
}
.ips-var-btn:hover { background:var(--accent-dim); border-color:var(--accent); }

.ips-quill-wrap { border:1.5px solid var(--border-default); border-radius:var(--r-sm); overflow:hidden; }
.ips-quill-wrap .ql-toolbar { background:var(--bg-elevated); border:none; border-bottom:1px solid var(--border-subtle); }
.ips-quill-wrap .ql-container { border:none; font-family:var(--font); font-size:13.5px; background:var(--bg-input); }
.ips-quill-wrap .ql-editor { color:var(--text-100); min-height:340px; padding:16px; line-height:1.7; }
.ips-quill-wrap .ql-editor.ql-blank::before { color:var(--text-400); font-style:normal; }
.ips-quill-wrap .ql-stroke { stroke:var(--text-300) !important; }
.ips-quill-wrap .ql-fill   { fill:var(--text-300) !important; }
.ips-quill-wrap .ql-picker-label { color:var(--text-300) !important; }
.ips-quill-wrap .ql-picker-options { background:var(--bg-elevated) !important; border-color:var(--border-default) !important; }

.ips-color-row { display:flex; align-items:center; gap:12px; }
.ips-color-row input[type="color"] {
    width:44px; height:38px; padding:2px; border:1.5px solid var(--border-default);
    border-radius:var(--r-sm); background:var(--bg-input); cursor:pointer;
}
.ips-color-row input[type="text"] { flex:1; }

.ips-toggle-row { display:flex; align-items:center; justify-content:space-between; padding:10px 0; border-bottom:1px solid var(--border-subtle); }
.ips-toggle-row:last-child { border-bottom:none; }
.ips-toggle-label { font-size:13.5px; font-weight:600; color:var(--text-100); }
.ips-toggle-hint { font-size:11.5px; color:var(--text-400); margin-top:2px; }

/* ── Live preview (mock header, not the real PDF template) ── */
.ips-preview-wrap { position:sticky; top:80px; }
.ips-preview-card { border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; background:#fff; }
.ips-preview-note { font-size:11.5px; color:var(--text-400); padding:10px 14px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); }
.ips-header-bar { padding:20px 24px; transition:background .15s; }
.ips-company-name { font-size:17px; font-weight:bold; color:#fff; letter-spacing:.3px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.ips-company-sub { font-size:10px; color:#cbd5e1; margin-top:4px; }
.ips-stripe { height:4px; }
.ips-meta-band { background:#f1f5f9; padding:12px 24px; display:flex; gap:24px; }
.ips-meta-label { font-size:8.5px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:#94a3b8; }
.ips-meta-value { font-size:12px; font-weight:700; color:#1e293b; margin-top:2px; }
.ips-body-mock { padding:18px 24px; }
.ips-footer-note { font-size:10.5px; color:#475569; padding:12px 24px; background:#f8fafc; border-top:1px solid #e2e8f0; white-space:pre-line; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div class="page-title">Invoice PDF Style</div>
        <div class="page-sub">Apne invoice PDF ka look customize karein — colors, font, logo position</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('tenant.invoices.index') }}" class="btn btn-secondary">← Back to Invoices</a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:20px;">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert-error" style="margin-bottom:20px;">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('tenant.invoice-pdf-style.store') }}" id="ipsForm" enctype="multipart/form-data">
@csrf
<div class="ips-grid">

    <div style="display:flex; flex-direction:column; gap:20px;">

        <div class="card">
            <div class="card-header"><h3 class="card-title">Company Logo</h3></div>
            <div class="card-body">
                <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
                    <div id="logo-preview-wrap" style="width:88px; height:88px; border:1.5px dashed var(--border-default); border-radius:var(--r-md); display:flex; align-items:center; justify-content:center; overflow:hidden; background:var(--bg-elevated); flex-shrink:0;">
                        @if($tenant->logo)
                            <img src="{{ asset('storage/' . $tenant->logo) }}" alt="Logo" id="logo-preview-img" style="width:100%; height:100%; object-fit:contain;">
                        @else
                            <span id="logo-preview-placeholder" style="font-size:11px; color:var(--text-400); text-align:center; padding:4px;">No logo</span>
                        @endif
                    </div>
                    <div style="flex:1; min-width:200px;">
                        <input type="file" name="logo" id="logo_input" accept="image/png,image/jpeg,image/webp,image/svg+xml" class="form-input" style="padding:8px;">
                        <p style="font-size:11px;color:var(--text-400);margin-top:6px;">PNG/JPG/WEBP/SVG, max 2MB. Invoice header par yahi logo dikhega.</p>
                        @if($tenant->logo)
                        <label style="display:flex; align-items:center; gap:6px; margin-top:8px; font-size:12px; color:var(--text-300); cursor:pointer;">
                            <input type="checkbox" name="remove_logo" value="1" id="remove_logo_cb">
                            Remove current logo
                        </label>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="ips-mode-tabs">
            <div class="ips-mode-tab active" onclick="switchMode('simple')" id="tab-simple">Simple Style</div>
            <div class="ips-mode-tab" onclick="switchMode('custom')" id="tab-custom">Custom Template (Advanced)</div>
        </div>

        <div class="ips-mode-panel active" id="panel-simple">

        <div class="card">
            <div class="card-header"><h3 class="card-title">Colors</h3></div>
            <div class="card-body">
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Header / Primary Color</label>
                    <div class="ips-color-row">
                        <input type="color" id="primary_color" name="primary_color" value="{{ old('primary_color', $settings->primary_color) }}">
                        <input type="text" class="form-input" id="primary_color_text" value="{{ old('primary_color', $settings->primary_color) }}" maxlength="7">
                    </div>
                    <p style="font-size:11px;color:var(--text-400);margin-top:6px;">Invoice header bar aur totals row ka background.</p>
                </div>
                <div class="form-group">
                    <label class="form-label">Accent Color</label>
                    <div class="ips-color-row">
                        <input type="color" id="accent_color" name="accent_color" value="{{ old('accent_color', $settings->accent_color) }}">
                        <input type="text" class="form-input" id="accent_color_text" value="{{ old('accent_color', $settings->accent_color) }}" maxlength="7">
                    </div>
                    <p style="font-size:11px;color:var(--text-400);margin-top:6px;">Top accent stripe aur buyer-box border.</p>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Layout & Font</h3></div>
            <div class="card-body">
                <div class="form-group" style="margin-bottom:14px;">
                    <label class="form-label">Font Family</label>
                    <select name="font_family" id="font_family" class="form-input">
                        @foreach($fonts as $font)
                        <option value="{{ $font }}" {{ old('font_family', $settings->font_family) === $font ? 'selected' : '' }}>{{ $font }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Logo Position</label>
                    <select name="logo_position" id="logo_position" class="form-input">
                        @php $pos = old('logo_position', $settings->logo_position); @endphp
                        <option value="left"   {{ $pos === 'left'   ? 'selected' : '' }}>Left</option>
                        <option value="center" {{ $pos === 'center' ? 'selected' : '' }}>Center</option>
                        <option value="right"  {{ $pos === 'right'  ? 'selected' : '' }}>Right</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Footer & Sections</h3></div>
            <div class="card-body">
                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">Custom Footer Note</label>
                    <textarea name="footer_note" id="footer_note" class="form-input" rows="3" placeholder="e.g. Thank you for your business!" maxlength="500">{{ old('footer_note', $settings->footer_note) }}</textarea>
                </div>

                <div class="ips-toggle-row">
                    <div>
                        <div class="ips-toggle-label">Show Bank Details</div>
                        <div class="ips-toggle-hint">Bank/payment box invoice ke neeche dikhayein</div>
                    </div>
                    <input type="checkbox" name="show_bank_details" value="1" {{ old('show_bank_details', $settings->show_bank_details) ? 'checked' : '' }} style="width:18px;height:18px;">
                </div>
                <div class="ips-toggle-row">
                    <div>
                        <div class="ips-toggle-label">Show Tax Summary (CGST/SGST split)</div>
                        <div class="ips-toggle-hint">Totals mein alag se GST breakup dikhayein</div>
                    </div>
                    <input type="checkbox" name="show_tax_summary" value="1" {{ old('show_tax_summary', $settings->show_tax_summary) ? 'checked' : '' }} style="width:18px;height:18px;">
                </div>
            </div>
        </div>

        </div>{{-- /panel-simple --}}

        <div class="ips-mode-panel" id="panel-custom">

        <div class="card" style="margin-bottom:16px;">
            <div class="card-body">
                <div class="ips-toggle-row" style="border-bottom:none;">
                    <div>
                        <div class="ips-toggle-label">Use Custom Template for Invoice PDFs</div>
                        <div class="ips-toggle-hint">On karne par neeche design kiya gaya template hi asli invoice download mein use hoga (Simple Style ignore ho jayegi)</div>
                    </div>
                    <input type="checkbox" name="use_custom_template" value="1" id="use_custom_template" {{ old('use_custom_template', $settings->use_custom_template) ? 'checked' : '' }} style="width:18px;height:18px;flex-shrink:0;">
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Design Your Invoice</h3>
                    <div style="font-size:12.5px;color:var(--text-300);margin-top:2px;">Neeche diye gaye variable buttons click karke apne template mein insert karein</div>
                </div>
                <button type="button" class="btn btn-ghost btn-sm" onclick="loadStarterTemplate()">Load Starter Template</button>
            </div>
            <div class="card-body">
                <div class="ips-var-groups">
                    @foreach($variables as $group => $items)
                    <div>
                        <div class="ips-var-group-label">{{ $group }}</div>
                        <div class="ips-var-btns">
                            @foreach($items as $key => $label)
                            @php $ipsToken = '{{' . $key . '}}'; @endphp
                            <span class="ips-var-btn" onclick="insertVar('{{ $ipsToken }}')" title="{{ $label }}">{{ $ipsToken }}</span>
                            @endforeach
                        </div>
                    </div>
                    @endforeach
                </div>

                <textarea name="custom_html" id="customHtmlHidden" style="display:none">{{ old('custom_html', $settings->custom_html) }}</textarea>
                <div class="ips-quill-wrap">
                    <div id="quillInvoice"></div>
                </div>
                <p style="font-size:11px;color:var(--text-400);margin-top:8px;">Editor hi WYSIWYG preview hai. Blocks (Items Table, Totals, Bank Details, Signature) real invoice data se auto-generate hote hain jab PDF banega.</p>
            </div>
        </div>

        </div>{{-- /panel-custom --}}

        <div style="display:flex; justify-content:flex-end;">
            <button type="submit" class="btn btn-primary">Save Style</button>
        </div>
    </div>

    {{-- ── Live preview ── --}}
    <div class="ips-preview-wrap">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Live Preview</h3></div>
            <div class="card-body" style="padding:16px;">
                <div class="ips-preview-card">
                    <div class="ips-header-bar" id="pv-header">
                        <div id="pv-header-inner" style="display:flex; justify-content:space-between; align-items:flex-start;">
                            <div style="display:flex; align-items:center; gap:10px; min-width:0; flex-shrink:1; overflow:hidden;">
                                <img id="pv-logo" src="{{ $tenant->logo ? asset('storage/' . $tenant->logo) : '' }}" style="max-height:38px; max-width:90px; object-fit:contain; flex-shrink:0; {{ $tenant->logo ? '' : 'display:none;' }}">
                                <div style="min-width:0; overflow:hidden;">
                                    <div class="ips-company-name" id="pv-company">{{ $tenant->name ?? 'Your Business' }}</div>
                                    <div class="ips-company-sub">{{ $tenant->email ?? 'you@business.com' }}</div>
                                </div>
                            </div>
                            <div style="text-align:right; flex-shrink:0; padding-left:16px; border-left:1px solid rgba(255,255,255,0.28);">
                                <div style="font-size:14px;font-weight:bold;color:#fff;letter-spacing:1.8px;white-space:nowrap;">TAX INVOICE</div>
                                <div class="ips-company-sub" style="white-space:nowrap;margin-top:3px;">Original for Recipient</div>
                            </div>
                        </div>
                    </div>
                    <div class="ips-stripe" id="pv-stripe"></div>
                    <div class="ips-meta-band">
                        <div>
                            <div class="ips-meta-label">Invoice No.</div>
                            <div class="ips-meta-value">INV-0001</div>
                        </div>
                        <div>
                            <div class="ips-meta-label">Invoice Date</div>
                            <div class="ips-meta-value">27 Jul 2026</div>
                        </div>
                        <div>
                            <div class="ips-meta-label">Status</div>
                            <div class="ips-meta-value">SENT</div>
                        </div>
                    </div>
                    <div class="ips-body-mock">
                        <div style="font-size:11px;color:#64748b;">Sample line items would render here in the PDF, styled with the selected font.</div>
                        <div style="margin-top:10px;padding:8px 12px;color:#fff;font-weight:bold;font-size:13px;" id="pv-total">Grand Total ₹ 11,800.00</div>
                    </div>
                    @if(($settings->footer_note))
                    <div class="ips-footer-note" id="pv-footer">{{ $settings->footer_note }}</div>
                    @else
                    <div class="ips-footer-note" id="pv-footer" style="display:none;"></div>
                    @endif
                </div>
                <div class="ips-preview-note">Ye ek quick preview hai — asli invoice layout thoda alag dikh sakta hai.</div>
            </div>
        </div>
    </div>
</div>
</form>

@endsection

@push('scripts')
<script>
(function () {
    const primaryColor = document.getElementById('primary_color');
    const primaryText  = document.getElementById('primary_color_text');
    const accentColor  = document.getElementById('accent_color');
    const accentText   = document.getElementById('accent_color_text');
    const fontFamily   = document.getElementById('font_family');
    const footerNote   = document.getElementById('footer_note');

    const pvHeader = document.getElementById('pv-header');
    const pvStripe = document.getElementById('pv-stripe');
    const pvTotal  = document.getElementById('pv-total');
    const pvFooter = document.getElementById('pv-footer');
    const pvHeaderInner = document.getElementById('pv-header-inner');
    const pvLogo   = document.getElementById('pv-logo');

    const logoInput   = document.getElementById('logo_input');
    const removeCb    = document.getElementById('remove_logo_cb');
    const previewWrap = document.getElementById('logo-preview-wrap');

    if (logoInput) {
        logoInput.addEventListener('change', () => {
            const file = logoInput.files && logoInput.files[0];
            if (!file) return;
            if (removeCb) removeCb.checked = false;

            const reader = new FileReader();
            reader.onload = (e) => {
                pvLogo.src = e.target.result;
                pvLogo.style.display = 'inline-block';
                previewWrap.innerHTML = '<img src="' + e.target.result + '" alt="Logo" style="width:100%;height:100%;object-fit:contain;">';
            };
            reader.readAsDataURL(file);
        });
    }

    if (removeCb) {
        removeCb.addEventListener('change', () => {
            if (removeCb.checked) {
                logoInput.value = '';
                pvLogo.style.display = 'none';
                previewWrap.innerHTML = '<span style="font-size:11px;color:var(--text-400);text-align:center;padding:4px;">No logo</span>';
            }
        });
    }

    function syncColor(colorInput, textInput) {
        colorInput.addEventListener('input', () => { textInput.value = colorInput.value; render(); });
        textInput.addEventListener('input', () => {
            if (/^#[0-9A-Fa-f]{6}$/.test(textInput.value)) {
                colorInput.value = textInput.value;
                render();
            }
        });
    }

    function render() {
        pvHeader.style.background = primaryColor.value;
        pvStripe.style.background = accentColor.value;
        pvTotal.style.background  = primaryColor.value;
        pvHeaderInner.style.fontFamily = fontFamily.value;
        pvTotal.style.fontFamily = fontFamily.value;

        if (footerNote.value.trim()) {
            pvFooter.textContent = footerNote.value;
            pvFooter.style.display = 'block';
        } else {
            pvFooter.style.display = 'none';
        }
    }

    syncColor(primaryColor, primaryText);
    syncColor(accentColor, accentText);
    fontFamily.addEventListener('change', render);
    footerNote.addEventListener('input', render);

    render();
})();
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js"></script>
<script>
const quillInvoice = new Quill('#quillInvoice', {
    theme: 'snow',
    placeholder: 'Click "Load Starter Template" ya khud design karein using the variable buttons above...',
    modules: {
        toolbar: [
            [{ header: [1, 2, 3, false] }],
            ['bold', 'italic', 'underline'],
            [{ color: [] }, { background: [] }],
            [{ align: [] }],
            [{ list: 'ordered' }, { list: 'bullet' }],
            ['link'],
            ['clean'],
        ],
    },
});

(function () {
    const hidden = document.getElementById('customHtmlHidden');
    if (hidden.value.trim()) {
        quillInvoice.root.innerHTML = hidden.value;
    }
})();

function switchMode(mode) {
    ['simple', 'custom'].forEach((m) => {
        document.getElementById('tab-' + m).classList.toggle('active', m === mode);
        document.getElementById('panel-' + m).classList.toggle('active', m === mode);
    });
}

function insertVar(token) {
    quillInvoice.focus();
    const range = quillInvoice.getSelection() || { index: quillInvoice.getLength() };
    quillInvoice.insertText(range.index, token, 'user');
    quillInvoice.setSelection(range.index + token.length);
}

function loadStarterTemplate() {
    if (quillInvoice.getLength() > 1) {
        confirmAction({
            message: 'Ye current design ko replace kar dega. Continue?',
            ok: 'Replace',
            danger: false,
            onConfirm: applyStarterTemplate,
        });
        return;
    }

    applyStarterTemplate();
}

function applyStarterTemplate() {
    @verbatim
    quillInvoice.root.innerHTML =
        '<h2>{{company_name}}</h2>' +
        '<p>{{company_address}} &nbsp;|&nbsp; {{company_email}} &nbsp;|&nbsp; {{company_phone}}</p>' +
        '<p>GSTIN: {{company_gstin}}</p>' +
        '<p><br></p>' +
        '<p><strong>Invoice #:</strong> {{invoice_number}} &nbsp;&nbsp; <strong>Date:</strong> {{invoice_date}} &nbsp;&nbsp; <strong>Due:</strong> {{due_date}} &nbsp;&nbsp; <strong>Status:</strong> {{invoice_status}}</p>' +
        '<p><br></p>' +
        '<p><strong>Billed To:</strong> {{customer_name}}, {{customer_company}}</p>' +
        '<p>{{customer_address}} &nbsp;|&nbsp; {{customer_email}} &nbsp;|&nbsp; {{customer_phone}}</p>' +
        '<p><br></p>' +
        '<p>{{items_table}}</p>' +
        '<p>{{totals_block}}</p>' +
        '<p>{{amount_in_words}}</p>' +
        '<p><br></p>' +
        '<p>{{bank_details}}</p>' +
        '<p>{{signature_block}}</p>';
    @endverbatim
}

document.getElementById('ipsForm').addEventListener('submit', function () {
    document.getElementById('customHtmlHidden').value = quillInvoice.root.innerHTML.trim();
});
</script>
@endpush
