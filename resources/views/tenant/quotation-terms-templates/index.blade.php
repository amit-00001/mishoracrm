@extends('layouts.app')
@section('title', 'Quotation Terms Templates')

@push('styles')
<style>
.qtt-layout { display:grid; grid-template-columns:1fr 340px; gap:16px; margin-top:16px; }
@media(max-width:900px){ .qtt-layout { grid-template-columns:1fr; } }
.qtt-table { width:100%; border-collapse:collapse; background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.qtt-table th { padding:10px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; background:var(--bg-elevated); border-bottom:1px solid var(--border-subtle); }
.qtt-table td { padding:11px 14px; border-bottom:1px solid var(--border-subtle); font-size:13.5px; color:var(--text-100); vertical-align:top; }
.qtt-table tr:last-child td { border-bottom:none; }
.qtt-preview { font-size:12px; color:var(--text-300); max-width:360px; white-space:pre-wrap; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; }
.qtt-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; padding:17px; height:fit-content; }
.qtt-field { display:flex; flex-direction:column; gap:5px; margin-bottom:12px; }
.qtt-label { font-size:11.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.5px; }
.qtt-input { width:100%; padding:9px 12px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:8px; color:var(--text-100); font-family:var(--font); font-size:13.5px; outline:none; }
.qtt-input:focus { border-color:var(--accent); }
textarea.qtt-input { resize:vertical; min-height:80px; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div class="page-title">Quotation Terms Templates</div>
        <div class="page-sub">Reusable Terms &amp; Conditions / Notes snippets — apply them on any quotation</div>
    </div>
    <a href="{{ route('tenant.quotations.index') }}" class="btn btn-secondary">
        <i class="ti ti-arrow-left" style="font-size:14px"></i> Back to Quotations
    </a>
</div>

@if(session('success'))
<div style="padding:10px 14px;background:var(--green-dim);border:1px solid rgba(52,199,89,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--green)">
    {{ session('success') }}
</div>
@endif

<div class="qtt-layout">

    <div class="qtt-table-wrap" style="overflow-x:auto">
        <table class="qtt-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Terms Preview</th>
                    <th style="width:120px"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $t)
                <tr>
                    <td style="font-weight:600">{{ $t->name }}</td>
                    <td><div class="qtt-preview">{{ $t->terms ?: '—' }}</div></td>
                    <td style="display:flex;gap:6px">
                        <a href="{{ route('tenant.quotation-terms-templates.edit', $t->id) }}" class="btn btn-secondary btn-sm">Edit</a>
                        @can('quotations.edit')
                        <form method="POST" action="{{ route('tenant.quotation-terms-templates.destroy', $t->id) }}"
                              data-confirm="Delete this template?" data-confirm-ok="Delete">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm" type="submit"
                                    style="background:var(--red-dim);color:var(--red);border:1px solid rgba(255,82,87,.25)">Del</button>
                        </form>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" style="text-align:center;padding:40px;color:var(--text-400)">
                        No templates yet. Save your first one using the form on the right.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @can('quotations.edit')
    <div class="qtt-card">
        <div class="qtt-label" style="margin-bottom:12px">New Template</div>
        <form method="POST" action="{{ route('tenant.quotation-terms-templates.store') }}">
            @csrf
            <div class="qtt-field">
                <label class="qtt-label">Name <span style="color:var(--red)">*</span></label>
                <input type="text" name="name" class="qtt-input" value="{{ old('name') }}" required/>
                @error('name')<span style="font-size:12px;color:var(--red)">{{ $message }}</span>@enderror
            </div>
            <div class="qtt-field">
                <label class="qtt-label">Terms &amp; Conditions</label>
                <textarea name="terms" class="qtt-input" rows="6">{{ old('terms') }}</textarea>
            </div>
            <div class="qtt-field">
                <label class="qtt-label">Notes</label>
                <textarea name="notes" class="qtt-input" rows="3">{{ old('notes') }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">Save Template</button>
        </form>
    </div>
    @endcan
</div>

@endsection
