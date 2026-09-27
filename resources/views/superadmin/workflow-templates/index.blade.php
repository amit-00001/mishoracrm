@extends('layouts.app')
@section('title', 'Workflow Templates — Superadmin')

@section('content')

<div class="page-head">
    <div>
        <div class="page-title">Workflow Templates</div>
        <div class="page-sub">Manage the AI automation templates shown to tenants</div>
    </div>
    <a href="{{ route('superadmin.workflow-templates.create') }}" class="btn btn-primary">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        Add Template
    </a>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Templates</div>
            <div class="card-subtitle">{{ $templates->count() }} total</div>
        </div>
    </div>
    <div style="overflow-x:auto;">
        @if($templates->isNotEmpty())
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Suitable For</th>
                    <th>Order</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($templates as $tmpl)
                <tr>
                    <td data-label="Title">
                        <div style="font-weight:600;color:var(--text-100);">{{ $tmpl->title }}</div>
                        <div style="font-size:12px;color:var(--text-300);margin-top:2px;">{{ Str::limit($tmpl->description, 65) }}</div>
                    </td>
                    <td data-label="Category">
                        <span style="font-size:12px;font-weight:600;padding:2px 8px;border-radius:20px;background:var(--accent-dim);color:var(--accent);">
                            {{ ucfirst($tmpl->category) }}
                        </span>
                    </td>
                    <td style="font-size:13px;color:var(--text-300);" data-label="Suitable For">{{ $tmpl->suitable_for ?? '—' }}</td>
                    <td style="font-size:13px;color:var(--text-300);" data-label="Order">{{ $tmpl->sort_order }}</td>
                    <td data-label="Status">
                        @if($tmpl->is_active)
                            <span style="font-size:12px;font-weight:600;padding:2px 8px;border-radius:20px;background:var(--green-dim);color:var(--green);">Active</span>
                        @else
                            <span style="font-size:12px;font-weight:600;padding:2px 8px;border-radius:20px;background:var(--red-dim);color:var(--red);">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;align-items:center;">
                            <a href="{{ route('superadmin.workflow-templates.edit', $tmpl) }}"
                               class="btn btn-secondary btn-sm">Edit</a>
                            <form method="POST" action="{{ route('superadmin.workflow-templates.toggle', $tmpl) }}">
                                @csrf
                                <button type="submit" class="btn btn-secondary btn-sm">
                                    {{ $tmpl->is_active ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('superadmin.workflow-templates.destroy', $tmpl) }}"
                                  data-confirm="Delete this template?" data-confirm-ok="Delete">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <div class="card-body" style="text-align:center;padding:48px 24px;color:var(--text-300);">
            <p style="font-size:14px;">No templates yet.
                <a href="{{ route('superadmin.workflow-templates.create') }}" style="color:var(--accent);">Create the first one.</a>
            </p>
        </div>
        @endif
    </div>
</div>

@endsection
