@extends('layouts.app')
@section('title', 'Edit Product')

@push('styles')
<style>
.pf-card  { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; max-width:680px; }
.pf-body  { padding:24px; display:flex; flex-direction:column; gap:16px; }
.pf-foot  { padding:14px 24px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center; }
.field    { display:flex; flex-direction:column; gap:6px; }
.fl       { font-size:12px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.4px; }
.fi       { padding:9px 13px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:14px; outline:none; transition:border-color .15s; width:100%; }
.fi:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
.fg2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.fe  { font-size:12px; color:var(--red); }
@media(max-width:640px) { .fg2 { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')

@php
    $units = ['Kg','Gram','Quintal','Litre','Millilitre','Metre','Piece','Box','Bag','Dozen','Ton','Set','Roll'];
    $oldUnit = old('unit', $product->unit);
@endphp

<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.products.index') }}" style="color:var(--text-300);text-decoration:none">Products</a>
            › Edit
        </div>
        <div class="page-title">Edit: {{ $product->name }}</div>
    </div>
    <a href="{{ route('tenant.products.index') }}" class="btn btn-secondary">← Back</a>
</div>

@if(session('success'))
<div style="padding:10px 14px;background:var(--green-dim);border:1px solid rgba(52,199,89,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--green)">
    {{ session('success') }}
</div>
@endif

@if($errors->any())
<div style="padding:10px 14px;background:var(--red-dim);border:1px solid rgba(255,82,87,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--red)">
    {{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ route('tenant.products.update', $product->id) }}">
@csrf @method('PUT')
<div class="pf-card">
    <div class="pf-body">

        <div class="field">
            <label class="fl">Name <span style="color:var(--red)">*</span></label>
            <input type="text" name="name" class="fi"
                   value="{{ old('name', $product->name) }}" required autofocus/>
            @error('name') <span class="fe">{{ $message }}</span> @enderror
        </div>

        <div class="field">
            <label class="fl">Product Code / SKU</label>
            <input type="text" name="product_code" class="fi"
                   value="{{ old('product_code', $product->product_code) }}" placeholder="e.g. SRV-WD001"
                   style="font-family:var(--mono,monospace);letter-spacing:.5px"/>
            <span style="font-size:11.5px;color:var(--text-400)">Search mein code se bhi dhundh sakte hain</span>
        </div>

        <div class="field">
            <label class="fl">Description</label>
            <textarea name="description" class="fi" rows="3"
                      style="resize:vertical">{{ old('description', $product->description) }}</textarea>
        </div>

        <div class="field">
            <label class="fl">Category</label>
            <input type="text" name="category" class="fi" value="{{ old('category', $product->category) }}" placeholder="e.g. Beverages, Electronics"/>
            <span style="font-size:11.5px;color:var(--text-400)">Used for targeted loyalty campaigns</span>
        </div>

        <div class="fg2">
            <div class="field">
                <label class="fl">Rate (₹) <span style="color:var(--red)">*</span></label>
                <input type="number" name="rate" class="fi" min="0" step="0.01"
                       value="{{ old('rate', $product->rate) }}" required/>
                @error('rate') <span class="fe">{{ $message }}</span> @enderror
            </div>
            <div class="field">
                <label class="fl">GST / Tax % <span style="color:var(--red)">*</span></label>
                <select name="tax_percent" class="fi" required>
                    @foreach([0,5,12,18,28] as $rate)
                    <option value="{{ $rate }}" {{ old('tax_percent', $product->tax_percent) == $rate ? 'selected' : '' }}>
                        {{ $rate }}%{{ $rate === 18 ? ' (Default)' : '' }}
                    </option>
                    @endforeach
                </select>
                @error('tax_percent') <span class="fe">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="field">
            <label class="fl">Purchase Cost / Unit (₹)</label>
            <input type="number" name="cost_price" class="fi" min="0" step="0.01"
                   value="{{ old('cost_price', $product->cost_price) }}" placeholder="Defaults to Rate if left blank"/>
            <span style="font-size:11.5px;color:var(--text-400)">Cost basis for production &amp; inventory valuation. Auto-updates (weighted average) each time stock is received via a Purchase Order.</span>
            @error('cost_price') <span class="fe">{{ $message }}</span> @enderror
        </div>

        <div class="fg2">
            <div class="field">
                <label class="fl">HSN / SAC Code</label>
                <input type="text" name="hsn" class="fi"
                       value="{{ old('hsn', $product->hsn) }}" placeholder="e.g. 998314"/>
            </div>
            <div class="field">
                <label class="fl">Unit</label>
                <select class="fi" id="unitSelect" onchange="onUnitChange()">
                    <option value="">— Select unit —</option>
                    @foreach($units as $u)
                    <option value="{{ $u }}" {{ $oldUnit === $u ? 'selected' : '' }}>{{ $u }}</option>
                    @endforeach
                    <option value="__other__" {{ ($oldUnit && !in_array($oldUnit, $units)) ? 'selected' : '' }}>Other (custom)</option>
                </select>
                <input type="text" id="unitOther" class="fi" placeholder="Enter custom unit"
                       value="{{ ($oldUnit && !in_array($oldUnit, $units)) ? $oldUnit : '' }}"
                       style="{{ ($oldUnit && !in_array($oldUnit, $units)) ? '' : 'display:none' }};margin-top:6px"/>
                <input type="hidden" name="unit" id="unitHidden" value="{{ $oldUnit }}">
            </div>
        </div>

        <div class="fg2">
            <div class="field">
                <label class="fl">Type</label>
                <select name="type" class="fi" id="typeSelect">
                    <option value="finished_good" {{ old('type', $product->type) === 'finished_good' ? 'selected' : '' }}>Finished Good</option>
                    <option value="raw_material" {{ old('type', $product->type) === 'raw_material' ? 'selected' : '' }}>Raw Material</option>
                </select>
                <span style="font-size:11.5px;color:var(--text-400)">Finished goods are sold on invoices; raw materials are used in a Bill of Materials</span>
            </div>
            <div class="field">
                <label class="fl">Current Stock</label>
                <input type="number" name="current_stock" class="fi" min="0" step="0.01"
                       value="{{ old('current_stock', $product->current_stock) }}" placeholder="0"/>
            </div>
        </div>

        <div class="fg2">
            <div class="field">
                <label class="fl">Reorder Level</label>
                <input type="number" name="reorder_level" class="fi" min="0" step="0.01"
                       value="{{ old('reorder_level', $product->reorder_level) }}" placeholder="Alert when stock falls to/below this"/>
            </div>
            <div class="field">
                <label class="fl">Reorder Quantity</label>
                <input type="number" name="reorder_quantity" class="fi" min="0" step="0.01"
                       value="{{ old('reorder_quantity', $product->reorder_quantity) }}" placeholder="How many units to replenish"/>
            </div>
        </div>

        <div class="field" style="flex-direction:row;align-items:center;gap:10px">
            <input type="checkbox" name="is_active" value="1" id="is_active"
                   {{ old('is_active', $product->is_active) ? 'checked' : '' }}
                   style="width:16px;height:16px;cursor:pointer"/>
            <label for="is_active" style="font-size:13.5px;color:var(--text-200);cursor:pointer">
                Active (visible in invoice/quotation selectors)
            </label>
        </div>

    </div>
    <div class="pf-foot">
        <div></div>
        <div style="display:flex;gap:8px">
            <a href="{{ route('tenant.products.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </div>
</div>

@if($product->type === 'finished_good')
<div class="pf-card" style="margin-top:20px">
    <div class="pf-body">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px">
            <label class="fl" style="font-size:13px">Bill of Materials</label>
        </div>
        <span style="font-size:11.5px;color:var(--text-400);margin-bottom:10px;display:block">
            Raw materials consumed per 1 unit of {{ $product->name }} produced
        </span>

        <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:13px" id="bomTable">
                <thead>
                    <tr>
                        <th style="text-align:left;padding:6px 8px;font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:var(--text-400);border-bottom:1px solid var(--border-subtle)">Raw Material</th>
                        <th style="text-align:left;padding:6px 8px;font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:var(--text-400);border-bottom:1px solid var(--border-subtle);width:160px">Qty per Unit</th>
                        <th style="width:40px;border-bottom:1px solid var(--border-subtle)"></th>
                    </tr>
                </thead>
                <tbody id="bomBody"></tbody>
            </table>
        </div>
        <button type="button" class="btn btn-secondary btn-sm" style="margin-top:10px" onclick="addBomRow()">+ Add Material</button>
    </div>
</div>
@endif

</form>

<div class="pf-card" style="margin-top:20px;border-color:rgba(255,82,87,.3)">
    <div class="pf-body" style="flex-direction:row;align-items:center;justify-content:space-between">
        <div>
            <div class="fl" style="color:var(--red)">Danger Zone</div>
            <span style="font-size:12px;color:var(--text-400)">Permanently delete this product. This cannot be undone.</span>
        </div>
        <form method="POST" action="{{ route('tenant.products.destroy', $product->id) }}"
              data-confirm="Delete this product?" data-confirm-ok="Delete">
            @csrf @method('DELETE')
            <button type="submit"
                    style="padding:8px 14px;border-radius:var(--r-sm);border:1.5px solid rgba(255,82,87,.3);background:var(--red-dim);color:var(--red);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)">
                Delete Product
            </button>
        </form>
    </div>
</div>

@if($product->type === 'finished_good')
<script>
(function(){
const RAW_MATERIALS = @json($rawMaterials);
const EXISTING_BOM  = @json($bomItems->map(fn($b) => ['material_id' => $b->material_id, 'quantity_per_unit' => $b->quantity_per_unit])->values());
let bomIndex = 0;

function materialOptions(selectedId){
    let opts = '<option value="">— Select material —</option>';
    RAW_MATERIALS.forEach(m => {
        opts += `<option value="${m.id}" ${String(selectedId)===String(m.id)?'selected':''}>${m.name}${m.unit ? ' ('+m.unit+')' : ''}</option>`;
    });
    return opts;
}

window.addBomRow = function(materialId = '', qty = ''){
    const i = bomIndex++;
    const tbody = document.getElementById('bomBody');
    const tr = document.createElement('tr');
    tr.id = 'bom_row_' + i;
    tr.innerHTML = `
        <td style="padding:6px 8px;border-bottom:1px solid var(--border-subtle)">
            <select name="materials[${i}][material_id]" class="fi" style="padding:7px 10px">${materialOptions(materialId)}</select>
        </td>
        <td style="padding:6px 8px;border-bottom:1px solid var(--border-subtle)">
            <input type="number" name="materials[${i}][quantity_per_unit]" class="fi" style="padding:7px 10px" min="0.0001" step="0.0001" value="${qty}" placeholder="0"/>
        </td>
        <td style="padding:6px 8px;border-bottom:1px solid var(--border-subtle);text-align:center">
            <button type="button" onclick="document.getElementById('bom_row_${i}').remove()"
                    style="background:none;border:none;color:var(--red);cursor:pointer;font-size:15px" title="Remove">✕</button>
        </td>
    `;
    tbody.appendChild(tr);
};

if(EXISTING_BOM.length){
    EXISTING_BOM.forEach(row => addBomRow(row.material_id, row.quantity_per_unit));
} else {
    addBomRow();
}
})();
</script>
@endif

@endsection
