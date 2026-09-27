@extends('layouts.app')
@section('title', 'Products / Services')

@push('styles')
<style>
.prod-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
.prod-table { width:100%; border-collapse:collapse; background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.prod-table th { padding:10px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; background:var(--bg-elevated); border-bottom:1px solid var(--border-subtle); }
.prod-table td { padding:11px 14px; border-bottom:1px solid var(--border-subtle); font-size:13.5px; color:var(--text-100); vertical-align:middle; }
.prod-table tr:last-child td { border-bottom:none; }
.prod-table tr:hover td { background:var(--bg-elevated); }
.badge-active   { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11.5px; font-weight:600; background:var(--green-dim); color:var(--green); }
.badge-inactive { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11.5px; font-weight:600; background:var(--bg-elevated); color:var(--text-400); }
.badge-type-fg  { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11.5px; font-weight:600; background:var(--accent-dim); color:var(--accent); }
.badge-type-rm  { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11.5px; font-weight:600; background:var(--purple-dim); color:var(--purple); }
.badge-low-stock { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11.5px; font-weight:600; background:var(--red-dim); color:var(--red); margin-left:6px; }
.mono { font-family:var(--mono); }
.status-tabs { display:flex; gap:4px; flex-wrap:wrap; margin-bottom:16px; }
.s-tab { padding:7px 14px; border-radius:var(--r-sm); font-size:12.5px; font-weight:600; text-decoration:none; color:var(--text-300); border:1.5px solid transparent; transition:all .15s; }
.s-tab:hover { color:var(--text-100); background:var(--bg-elevated); }
.s-tab.active { background:var(--accent-dim); color:var(--accent); border-color:rgba(var(--accent-rgb),.25); }
@media(max-width:768px) {
    .prod-table { border:none; }
    .prod-table thead { display:none; }
    .prod-table tbody tr { display:block; margin-bottom:12px; border:1px solid var(--border-default); border-radius:var(--r-md); overflow:hidden; }
    .prod-table td { display:flex; align-items:center; justify-content:space-between; gap:12px; text-align:right; max-width:none !important; white-space:normal !important; }
    .prod-table td::before { content:attr(data-label); font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.4px; color:var(--text-400); text-align:left; flex-shrink:0; }
}

/* ── MOBILE PRODUCT CARDS (<768px) ────────────────────────────── */
.products-mobile-list{display:none}
@media(max-width:768px){
    .products-table-wrap{display:none}
    .products-mobile-list{display:flex;flex-direction:column;gap:10px;padding:0}
}
.pr-card{background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-md);padding:14px}
.pr-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:10px}
.pr-name{font-size:14px;font-weight:700;color:var(--text-100);word-break:break-word}
.pr-code{font-size:11.5px;color:var(--text-400);font-family:var(--mono);margin-top:2px}
.pr-desc{font-size:12.5px;color:var(--text-300);margin-bottom:10px;overflow-wrap:anywhere}
.pr-info{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px;padding:9px 11px;background:var(--bg-elevated);border-radius:8px}
.pr-info-item{display:flex;flex-direction:column;gap:2px;min-width:70px}
.pr-info-lbl{font-size:10.5px;color:var(--text-400);text-transform:uppercase;letter-spacing:.3px}
.pr-info-val{font-size:12.5px;color:var(--text-200);font-weight:600;font-family:var(--mono)}
.pr-foot{display:flex;align-items:center;justify-content:space-between;gap:8px;padding-top:10px;border-top:1px solid var(--border-subtle)}
.pr-acts{display:flex;align-items:center;gap:6px;flex-shrink:0}
</style>
@endpush

@section('content')

<div class="prod-head">
    <div>
        <div class="page-title">Products / Services</div>
        <div class="page-sub">Item catalog used for auto-filling invoices & quotations</div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('tenant.products.low-stock') }}" class="btn btn-secondary">Low Stock</a>
        <a href="{{ route('tenant.products.create') }}" class="btn btn-primary">+ Add Product</a>
    </div>
</div>

@if(session('success'))
<div style="padding:10px 14px;background:var(--green-dim);border:1px solid rgba(52,199,89,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--green)">
    {{ session('success') }}
</div>
@endif

@php $currentType = request('type', ''); @endphp
<div class="status-tabs">
    <a href="{{ route('tenant.products.index', ['search'=>request('search')]) }}" class="s-tab {{ $currentType === '' ? 'active' : '' }}">All</a>
    <a href="{{ route('tenant.products.index', ['type'=>'finished_good','search'=>request('search')]) }}" class="s-tab {{ $currentType === 'finished_good' ? 'active' : '' }}">Finished Goods</a>
    <a href="{{ route('tenant.products.index', ['type'=>'raw_material','search'=>request('search')]) }}" class="s-tab {{ $currentType === 'raw_material' ? 'active' : '' }}">Raw Materials</a>
</div>

<form method="GET" style="margin-bottom:14px;display:flex;gap:8px">
    @if($currentType) <input type="hidden" name="type" value="{{ $currentType }}"/> @endif
    <input type="text" name="search" value="{{ request('search') }}"
           placeholder="Search products..."
           style="padding:9px 13px;border:1.5px solid var(--border-default);border-radius:var(--r-sm);background:var(--bg-input);color:var(--text-100);font-size:13.5px;outline:none;width:280px"/>
    <button class="btn btn-secondary" type="submit">Search</button>
    @if(request('search'))
    <a href="{{ route('tenant.products.index', $currentType ? ['type'=>$currentType] : []) }}" class="btn btn-secondary">Clear</a>
    @endif
</form>

@isset($inventoryValue)
<div style="display:inline-flex;align-items:center;gap:8px;padding:9px 14px;margin-bottom:14px;background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-sm)">
    <span style="font-size:11px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.4px">Stock Value (at cost)</span>
    <span style="font-size:14px;font-weight:700;color:var(--text-100);font-family:var(--mono)">₹{{ number_format($inventoryValue, 2) }}</span>
</div>
@endisset

<div class="products-table-wrap">
<table class="prod-table">
    <thead>
        <tr>
            <th>Code</th>
            <th>Name</th>
            <th>Description</th>
            <th>Type</th>
            <th>HSN</th>
            <th>Rate (₹)</th>
            <th>Cost (₹)</th>
            <th>GST %</th>
            <th>Unit</th>
            <th>Stock</th>
            <th>Status</th>
            <th style="width:100px"></th>
        </tr>
    </thead>
    <tbody>
        @forelse($products as $p)
        <tr>
            <td class="mono" style="font-size:12px;color:var(--text-300)" data-label="Code">{{ $p->product_code ?: '—' }}</td>
            <td style="font-weight:600" data-label="Name">{{ $p->name }}</td>
            <td style="color:var(--text-300);font-size:12.5px;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" data-label="Description">
                {{ $p->description ?: '—' }}
            </td>
            <td data-label="Type">
                @if($p->type === 'raw_material')
                    <span class="badge-type-rm">Raw Material</span>
                @else
                    <span class="badge-type-fg">Finished Good</span>
                @endif
            </td>
            <td class="mono" style="font-size:12.5px" data-label="HSN">{{ $p->hsn ?: '—' }}</td>
            <td class="mono" data-label="Rate (₹)">₹{{ number_format($p->rate, 2) }}</td>
            <td class="mono" data-label="Cost (₹)" style="color:var(--text-300)">₹{{ number_format($p->costBasis(), 2) }}</td>
            <td class="mono" data-label="GST %">{{ $p->tax_percent }}%</td>
            <td data-label="Unit">{{ $p->unit ?: '—' }}</td>
            <td class="mono" data-label="Stock">
                {{ number_format($p->current_stock, 2) }}
                @if($p->isLowStock())<span class="badge-low-stock">Low</span>@endif
            </td>
            <td data-label="Status">
                @if($p->is_active)
                    <span class="badge-active">Active</span>
                @else
                    <span class="badge-inactive">Inactive</span>
                @endif
            </td>
            <td style="display:flex;gap:6px">
                @if(auth()->user()->tenant?->hasModuleEnabled('manufacturing'))
                <a href="{{ route('tenant.products.batches', $p->id) }}" class="btn btn-secondary btn-sm">Batches</a>
                @endif
                <a href="{{ route('tenant.products.edit', $p->id) }}" class="btn btn-secondary btn-sm">Edit</a>
                <form method="POST" action="{{ route('tenant.products.destroy', $p->id) }}"
                      data-confirm="Delete this product?" data-confirm-ok="Delete">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm" type="submit"
                            style="background:var(--red-dim);color:var(--red);border:1px solid rgba(255,82,87,.25)">
                        Del
                    </button>
                </form>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="12" style="text-align:center;padding:40px;color:var(--text-400)">
                No products yet. <a href="{{ route('tenant.products.create') }}" style="color:var(--accent)">Add your first product</a>.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>

{{-- Mobile card list (shown only <768px, table above hides itself) --}}
<div class="products-mobile-list">
@forelse($products as $p)
<div class="pr-card">
    <div class="pr-top">
        <div>
            <div class="pr-name">{{ $p->name }}</div>
            @if($p->product_code)<div class="pr-code">{{ $p->product_code }}</div>@endif
        </div>
        <div style="display:flex;flex-direction:column;gap:4px;align-items:flex-end">
            @if($p->is_active)
                <span class="badge-active">Active</span>
            @else
                <span class="badge-inactive">Inactive</span>
            @endif
            @if($p->type === 'raw_material')
                <span class="badge-type-rm">Raw Material</span>
            @else
                <span class="badge-type-fg">Finished Good</span>
            @endif
        </div>
    </div>
    @if($p->description)
    <div class="pr-desc">{{ $p->description }}</div>
    @endif
    <div class="pr-info">
        <div class="pr-info-item">
            <span class="pr-info-lbl">Rate</span>
            <span class="pr-info-val">₹{{ number_format($p->rate, 2) }}</span>
        </div>
        <div class="pr-info-item">
            <span class="pr-info-lbl">Cost</span>
            <span class="pr-info-val">₹{{ number_format($p->costBasis(), 2) }}</span>
        </div>
        <div class="pr-info-item">
            <span class="pr-info-lbl">GST</span>
            <span class="pr-info-val">{{ $p->tax_percent }}%</span>
        </div>
        <div class="pr-info-item">
            <span class="pr-info-lbl">Unit</span>
            <span class="pr-info-val">{{ $p->unit ?: '—' }}</span>
        </div>
        <div class="pr-info-item">
            <span class="pr-info-lbl">Stock</span>
            <span class="pr-info-val">{{ number_format($p->current_stock, 2) }}{{ $p->isLowStock() ? ' ⚠' : '' }}</span>
        </div>
        @if($p->hsn)
        <div class="pr-info-item">
            <span class="pr-info-lbl">HSN</span>
            <span class="pr-info-val">{{ $p->hsn }}</span>
        </div>
        @endif
    </div>
    <div class="pr-foot">
        <span></span>
        <div class="pr-acts">
            <a href="{{ route('tenant.products.edit', $p->id) }}" class="btn btn-secondary btn-sm">Edit</a>
            <form method="POST" action="{{ route('tenant.products.destroy', $p->id) }}"
                  data-confirm="Delete this product?" data-confirm-ok="Delete">
                @csrf @method('DELETE')
                <button class="btn btn-sm" type="submit"
                        style="background:var(--red-dim);color:var(--red);border:1px solid rgba(255,82,87,.25)">
                    Del
                </button>
            </form>
        </div>
    </div>
</div>
@empty
<div style="text-align:center;padding:40px;color:var(--text-400)">
    No products yet. <a href="{{ route('tenant.products.create') }}" style="color:var(--accent)">Add your first product</a>.
</div>
@endforelse
</div>

<div style="margin-top:14px">{{ $products->links() }}</div>

@endsection
