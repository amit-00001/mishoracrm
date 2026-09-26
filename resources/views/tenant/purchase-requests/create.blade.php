@extends('layouts.app')
@section('title', 'New Purchase Request — ' . $number)

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap');
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css');

.pf { font-family: 'DM Sans', var(--font), sans-serif; }
.pf-layout { display:grid; grid-template-columns:minmax(0,1fr) 280px; gap:16px; margin-top:20px; }
@media(max-width:960px){ .pf-layout { grid-template-columns:1fr; } }
.pf-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; overflow:hidden; }
.pf-section { padding:20px 22px; border-bottom:1px solid var(--border-subtle); }
.pf-section:last-of-type { border-bottom:none; }
.pf-sec-head { display:flex; align-items:flex-start; gap:11px; margin-bottom:16px; }
.pf-sec-icon { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.pf-sec-title { font-size:13px; font-weight:600; color:var(--text-100); }
.pf-sec-sub { font-size:12px; color:var(--text-300); margin-top:1px; }
.pf-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.pf-grid .span-full { grid-column:1/-1; }
@media(max-width:640px){ .pf-grid { grid-template-columns:1fr; } .pf-grid .span-full { grid-column:1; } }
.pf-field { display:flex; flex-direction:column; gap:5px; }
.pf-label { font-size:11.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.5px; }
.pf-req { color:var(--red); margin-left:2px; }
.pf-input {
    width:100%; padding:9px 12px; background:var(--bg-input); border:1.5px solid var(--border-default);
    border-radius:8px; color:var(--text-100); font-family:'DM Sans',var(--font),sans-serif; font-size:13.5px; outline:none;
    transition:border-color .15s, box-shadow .15s;
}
.pf-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
.pf-area { resize:vertical; min-height:80px; line-height:1.55; }
.pf-err { font-size:12px; color:var(--red); font-weight:500; }

.items-table-wrap { overflow-x:auto; }
.items-table { width:100%; border-collapse:collapse; font-size:13px; min-width:560px; }
.items-table thead tr { background:var(--bg-elevated); }
.items-table th { padding:8px 10px; text-align:left; font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid var(--border-subtle); }
.items-table td { padding:7px 6px; border-bottom:1px solid var(--border-subtle); vertical-align:top; }
.items-table tr:last-child td { border-bottom:none; }
.item-input { width:100%; padding:7px 9px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:7px; color:var(--text-100); font-family:'DM Sans',var(--font),sans-serif; font-size:13px; outline:none; }
.item-input:focus { border-color:var(--accent); box-shadow:0 0 0 2px var(--accent-dim); }
.item-input.is-err { border-color:var(--red); }
.item-err { display:block; margin-top:4px; font-size:11.5px; color:var(--red); font-weight:500; }
.pf-alert { padding:11px 15px; background:var(--red-dim); border:1px solid var(--red); border-radius:8px; margin-bottom:14px; font-size:13px; color:var(--red); }
.pf-alert-title { font-weight:600; margin-bottom:4px; }
.pf-alert ul { margin:0; padding-left:18px; }
.del-row-btn { width:28px; height:28px; border-radius:6px; background:transparent; border:1px solid var(--border-subtle); cursor:pointer; color:var(--text-400); display:flex; align-items:center; justify-content:center; margin:2px auto 0; }
.del-row-btn:hover { background:var(--red-dim); border-color:var(--red); color:var(--red); }
.add-item-btn { display:flex; align-items:center; gap:6px; padding:9px 16px; margin:12px 0 0; background:transparent; border:1.5px dashed var(--border-default); border-radius:8px; font-size:13px; color:var(--text-300); cursor:pointer; font-family:'DM Sans',var(--font),sans-serif; }
.add-item-btn:hover { border-color:var(--accent); color:var(--accent); }

.pf-footer { display:flex; align-items:center; justify-content:space-between; padding:15px 22px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); }
.pf-footer-note { font-size:12px; color:var(--text-300); }
.pf-sidebar { display:flex; flex-direction:column; gap:13px; }
.pf-sc { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; padding:17px; }
.pf-sc-title { font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.6px; margin-bottom:13px; }
.pr-number { font-size:18px; font-weight:600; color:var(--text-100); font-family:'DM Mono',monospace; }
.tip-list { display:flex; flex-direction:column; gap:9px; }
.tip-item { display:flex; align-items:flex-start; gap:8px; font-size:12px; color:var(--text-300); line-height:1.45; }
.tip-dot { width:5px; height:5px; border-radius:50%; background:var(--accent); margin-top:5px; flex-shrink:0; }
</style>
@endpush

@section('content')
<div class="pf">

    <div class="page-head">
        <div>
            <div style="font-size:12px;color:var(--text-300);margin-bottom:4px;display:flex;align-items:center;gap:5px">
                <a href="{{ route('tenant.purchase-requests.index') }}" style="color:var(--text-300);text-decoration:none">Purchase Requests</a>
                <span style="opacity:.4">›</span>
                <span>New Request</span>
            </div>
            <div class="page-title">New Purchase Request</div>
        </div>
        <a href="{{ route('tenant.purchase-requests.index') }}" class="btn btn-secondary">
            <i class="ti ti-arrow-left" style="font-size:14px"></i> Back
        </a>
    </div>

    <div class="pf-alert" id="prErrors" role="alert" @if(!$errors->any()) style="display:none" @endif>
        <div class="pf-alert-title">Please fix the following before submitting:</div>
        <ul id="prErrorList">
            @foreach(array_unique($errors->all()) as $message)
            <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>

    <form method="POST" action="{{ route('tenant.purchase-requests.store') }}" novalidate id="prForm" data-submit-once>
        @csrf
        <input type="hidden" name="submission_token" value="{{ old('submission_token', $submissionToken) }}"/>
        <div class="pf-layout">
            <div>
                <div class="pf-card" style="margin-bottom:14px">
                    <div class="pf-section">
                        <div class="pf-sec-head">
                            <div class="pf-sec-icon" style="background:var(--accent-dim)">
                                <i class="ti ti-clipboard-list" style="font-size:15px;color:var(--accent)"></i>
                            </div>
                            <div>
                                <div class="pf-sec-title">Request Details</div>
                                <div class="pf-sec-sub">Basic details for this request</div>
                            </div>
                        </div>
                        <div class="pf-grid">
                            <div class="pf-field">
                                <label class="pf-label">Request Number</label>
                                <input type="text" class="pf-input" value="{{ $number }}" readonly
                                       style="background:var(--bg-elevated);color:var(--text-300);cursor:default;font-family:'DM Mono',monospace"/>
                            </div>
                            <div class="pf-field">
                                <label class="pf-label" for="pr_date">Date <span class="pf-req">*</span></label>
                                <input type="date" name="date" id="pr_date" class="pf-input {{ $errors->has('date')?'is-err':'' }}"
                                       value="{{ old('date', now()->format('Y-m-d')) }}" required/>
                                @error('date')<span class="pf-err">{{ $message }}</span>@enderror
                                <span class="pf-err" id="dateErr" style="display:none"></span>
                            </div>
                            <div class="pf-field span-full">
                                <label class="pf-label" for="pr_department">Department</label>
                                <select name="department_id" id="pr_department" class="pf-input">
                                    <option value="">— Select Department (optional) —</option>
                                    @foreach($departments as $d)
                                    <option value="{{ $d->id }}" {{ old('department_id') == $d->id ? 'selected':'' }}>{{ $d->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pf-card" style="margin-bottom:14px">
                    <div class="pf-section" style="border-bottom:none">
                        <div class="pf-sec-head">
                            <div class="pf-sec-icon" style="background:var(--green-dim)">
                                <i class="ti ti-list-details" style="font-size:15px;color:var(--green)"></i>
                            </div>
                            <div>
                                <div class="pf-sec-title">Items Needed <span class="pf-req">*</span></div>
                                <div class="pf-sec-sub">What do you need procured?</div>
                            </div>
                        </div>
                        <div class="items-table-wrap">
                            <table class="items-table" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th style="width:26%">Item</th>
                                        <th style="width:26%">Description</th>
                                        <th style="width:12%">Qty</th>
                                        <th style="width:28%">Note</th>
                                        <th style="width:8%"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody"></tbody>
                            </table>
                        </div>
                        @error('items')<span class="pf-err" style="display:block;margin-top:8px">{{ $message }}</span>@enderror
                        <span class="pf-err" id="itemsErr" style="display:none;margin-top:8px"></span>
                        <button type="button" class="add-item-btn" onclick="addItemRow()">
                            <i class="ti ti-plus" style="font-size:14px"></i> Add Item
                        </button>
                    </div>
                </div>

                <div class="pf-card">
                    <div class="pf-section">
                        <div class="pf-sec-head">
                            <div class="pf-sec-icon" style="background:var(--amber-dim)">
                                <i class="ti ti-notes" style="font-size:15px;color:var(--amber)"></i>
                            </div>
                            <div>
                                <div class="pf-sec-title">Reason</div>
                                <div class="pf-sec-sub">Why is this purchase needed?</div>
                            </div>
                        </div>
                        <div class="pf-grid">
                            <div class="pf-field span-full">
                                <textarea name="reason" class="pf-input pf-area" rows="3"
                                          placeholder="Explain the business need...">{{ old('reason') }}</textarea>
                                @error('reason')<span class="pf-err">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="pf-footer">
                        <div class="pf-footer-note">Fields marked <strong>*</strong> are required</div>
                        <div style="display:flex;gap:8px">
                            <a href="{{ route('tenant.purchase-requests.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-send" style="font-size:14px"></i> Submit Request
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pf-sidebar">
                <div class="pf-sc">
                    <div class="pf-sc-title">Request Preview</div>
                    <div class="pr-number">{{ $number }}</div>
                    <div style="font-size:12px;color:var(--text-300);margin-top:6px" id="itemsCount">0 items</div>
                </div>
                <div class="pf-sc">
                    <div class="pf-sc-title">Tips</div>
                    <div class="tip-list">
                        <div class="tip-item"><div class="tip-dot"></div><span>Pick a product to auto-fill its name and description</span></div>
                        <div class="tip-item"><div class="tip-dot"></div><span>You can still edit this request while it's pending</span></div>
                        <div class="tip-item"><div class="tip-dot"></div><span>Once approved, a draft Purchase Order is created automatically</span></div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function(){
const PRODUCTS = @json($products);
let rowIndex = 0;

function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function productOptions(selectedId){
    let opts = '<option value="">— Type manually —</option>';
    PRODUCTS.forEach(p => {
        opts += `<option value="${p.id}" ${String(selectedId)===String(p.id)?'selected':''}>${esc(p.name)}${p.product_code ? ' ['+esc(p.product_code)+']' : ''}</option>`;
    });
    return opts;
}

window.addItemRow = function(name='', description='', qty=1, note='', productId=''){
    const i = rowIndex++;
    const tbody = document.getElementById('itemsBody');
    const tr = document.createElement('tr');
    tr.id = 'row_' + i;
    tr.innerHTML = `
        <td data-label="Item">
            <select class="item-input" onchange="onProductPick(${i}, this)">${productOptions(productId)}</select>
            <input type="hidden" name="items[${i}][product_id]" id="pid_${i}" value="${productId}"/>
            <input type="text" name="items[${i}][name]" id="name_${i}" class="item-input" style="margin-top:5px"
                   placeholder="Item name" value="${esc(name)}" required/>
            <span class="item-err" id="nameErr_${i}" style="display:none"></span>
        </td>
        <td data-label="Description">
            <input type="text" name="items[${i}][description]" id="desc_${i}" class="item-input" placeholder="Optional description" value="${esc(description)}"/>
        </td>
        <td data-label="Qty">
            <input type="number" name="items[${i}][quantity]" id="qty_${i}" class="item-input" value="${qty}" min="0.01" step="0.01" required oninput="updateCount()"/>
            <span class="item-err" id="qtyErr_${i}" style="display:none"></span>
        </td>
        <td data-label="Note">
            <input type="text" name="items[${i}][reason]" class="item-input" placeholder="Optional note" value="${esc(note)}"/>
        </td>
        <td>
            <button type="button" class="del-row-btn" onclick="delRow(${i})" title="Remove">
                <i class="ti ti-trash" style="font-size:13px"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    updateCount();
};

window.onProductPick = function(i, sel){
    const id = sel.value;
    document.getElementById('pid_'+i).value = id;
    if(!id) return;
    const p = PRODUCTS.find(p => String(p.id) === String(id));
    if(p){
        document.getElementById('name_'+i).value = p.name;
        document.getElementById('desc_'+i).value = p.description || '';
    }
};

window.delRow = function(i){
    document.getElementById('row_'+i)?.remove();
    updateCount();
};

window.updateCount = function(){
    const rows = document.querySelectorAll('#itemsBody tr');
    document.getElementById('itemsCount').textContent = rows.length + (rows.length===1?' item':' items');
};

// ── Validation (inline, per field) ───────────────────────────────
function setErr(inputEl, errEl, message){
    if(inputEl) inputEl.classList.toggle('is-err', !!message);
    if(errEl){ errEl.textContent = message || ''; errEl.style.display = message ? 'block' : 'none'; }
}

function showSummary(messages){
    const box  = document.getElementById('prErrors');
    const list = document.getElementById('prErrorList');
    list.innerHTML = [...new Set(messages)].map(m => `<li>${esc(m)}</li>`).join('');
    box.style.display = messages.length ? '' : 'none';
    if(messages.length) box.scrollIntoView({behavior:'smooth', block:'center'});
}

// Runs on submit (the form has novalidate, so the browser won't). Returns the
// list of problems; an empty list means the form may be posted.
function validateForm(){
    const messages = [];
    let firstBad = null;
    const bad = el => { if(!firstBad) firstBad = el; };

    const dateEl  = document.getElementById('pr_date');
    const dateMsg = dateEl.value ? '' : 'Date is required.';
    setErr(dateEl, document.getElementById('dateErr'), dateMsg);
    if(dateMsg){ messages.push(dateMsg); bad(dateEl); }

    const rows     = document.querySelectorAll('#itemsBody tr');
    const itemsErr = document.getElementById('itemsErr');
    if(rows.length === 0){
        itemsErr.textContent   = 'At least one item is required.';
        itemsErr.style.display = 'block';
        messages.push('At least one item is required.');
        bad(document.querySelector('.add-item-btn'));
    } else {
        itemsErr.style.display = 'none';
    }

    rows.forEach(tr => {
        const i      = tr.id.replace('row_', '');
        const nameEl = document.getElementById('name_'+i);
        const qtyEl  = document.getElementById('qty_'+i);

        const nameMsg = nameEl.value.trim() ? '' : 'Item name is required.';
        setErr(nameEl, document.getElementById('nameErr_'+i), nameMsg);
        if(nameMsg){ messages.push(nameMsg); bad(nameEl); }

        const qty    = parseFloat(qtyEl.value);
        const qtyMsg = (qtyEl.value === '' || isNaN(qty)) ? 'Quantity is required.'
                     : (qty < 0.01 ? 'Quantity must be greater than zero.' : '');
        setErr(qtyEl, document.getElementById('qtyErr_'+i), qtyMsg);
        if(qtyMsg){ messages.push(qtyMsg); bad(qtyEl); }
    });

    showSummary(messages);
    if(firstBad) firstBad.focus();
    return messages;
}

document.getElementById('prForm').addEventListener('submit', function(e){
    // Blocks the post. Because the event is then defaultPrevented, the layout's
    // data-submit-once guard leaves the button enabled so the user can retry.
    if(validateForm().length) e.preventDefault();
});

// Clear a field's error as soon as the user edits it.
document.getElementById('itemsBody').addEventListener('input', function(e){
    const el = e.target;
    if(!el.classList.contains('item-input')) return;
    el.classList.remove('is-err');
    const slot = el.parentElement.querySelector('.item-err');
    if(slot) slot.style.display = 'none';
});

// Mark rows the server rejected (its errors are keyed by the *submitted* row index).
const SERVER_ERRORS = @json($errors->getMessages());
function markServerRowErrors(rowIdx, submittedKey){
    const nameMsg = (SERVER_ERRORS[`items.${submittedKey}.name`] || [])[0];
    const qtyMsg  = (SERVER_ERRORS[`items.${submittedKey}.quantity`] || [])[0];
    setErr(document.getElementById('name_'+rowIdx), document.getElementById('nameErr_'+rowIdx), nameMsg);
    setErr(document.getElementById('qty_'+rowIdx),  document.getElementById('qtyErr_'+rowIdx),  qtyMsg);
}

@if(old('items'))
const oldItems = @json(old('items'));
// Object.entries: submitted rows can be non-contiguous ({0:…, 2:…}) if a row was removed.
const oldEntries = Object.entries(oldItems || {});
if(oldEntries.length){
    oldEntries.forEach(([key, item]) => {
        addItemRow(item.name||'', item.description||'', item.quantity ?? '', item.reason||'', item.product_id||'');
        markServerRowErrors(rowIndex - 1, key);
    });
} else {
    addItemRow();
}
@elseif(!empty($prefillItems))
const prefillItems = @json($prefillItems);
if(prefillItems && prefillItems.length){
    prefillItems.forEach(item => addItemRow(item.name||'', item.description||'', item.quantity||1, item.reason||'', item.material_id||''));
} else {
    addItemRow();
}
@else
addItemRow();
@endif

})();
</script>
@endpush
