@extends('layouts.app')
@section('title', 'Task Templates')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css"/>
<style>
.tt-wrap { font-family: var(--font), sans-serif; }
.tt-table-wrap { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:12px; overflow:hidden; }
.tt-table { width:100%; border-collapse:collapse; }
.tt-table thead tr { background:var(--bg-elevated); }
.tt-table th { padding:9px 14px; text-align:left; font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid var(--border-subtle); }
.tt-table td { padding:14px; font-size:13px; color:var(--text-100); border-bottom:1px solid var(--border-subtle); vertical-align:top; }
.tt-table tr:last-child td { border-bottom:none; }
.tt-name { font-weight:600; }
.tt-desc { font-size:12px; color:var(--text-400); margin-top:2px; max-width:360px; }
.tt-badge { display:inline-flex; align-items:center; padding:3px 9px; border-radius:99px; font-size:11px; font-weight:600; }
.tt-acts { display:flex; gap:6px; }
.act-btn { width:30px; height:30px; border-radius:7px; display:flex; align-items:center; justify-content:center; background:var(--bg-elevated); color:var(--text-300); text-decoration:none; border:none; cursor:pointer; }
.act-btn:hover { background:var(--accent); color:#fff; }
.act-btn.del:hover { background:var(--red); }
.tt-empty { text-align:center; padding:40px 20px; color:var(--text-400); }
</style>
@endpush

@section('content')
<div class="tt-wrap">

    <div class="page-head">
        <div>
            <div class="page-title">Task Templates</div>
            <div style="font-size:12px;color:var(--text-300);margin-top:2px">Reusable checklists for common workflows</div>
        </div>
        <div style="display:flex;gap:8px">
            <a href="{{ route('tenant.tasks.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-left" style="font-size:14px"></i> Back to Tasks
            </a>
            <a href="{{ route('tenant.task-templates.create') }}" class="btn btn-primary">
                <i class="ti ti-plus" style="font-size:14px"></i> New Template
            </a>
        </div>
    </div>

    @if(session('success'))
    <div style="display:flex;align-items:center;gap:10px;padding:11px 15px;background:var(--green-dim);border:1px solid rgba(45,212,160,.3);border-radius:8px;margin-bottom:14px;font-size:13px;color:var(--green);font-weight:500">
        <i class="ti ti-circle-check" style="font-size:16px"></i>
        {{ session('success') }}
    </div>
    @endif

    <div class="tt-table-wrap">
        @if($templates->isEmpty())
        <div class="tt-empty">
            <i class="ti ti-template" style="font-size:28px;color:var(--text-300)"></i>
            <div style="margin-top:10px;font-size:13px">No templates yet — create one to speed up repetitive task creation.</div>
        </div>
        @else
        <table class="tt-table">
            <thead>
                <tr>
                    <th>Template</th>
                    <th>Priority</th>
                    <th>Checklist Items</th>
                    <th width="90"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($templates as $template)
                @php
                    $priorityColors = config('task_fields.priorities');
                    $p = $priorityColors[$template->default_priority] ?? null;
                @endphp
                <tr>
                    <td>
                        <div class="tt-name">{{ $template->name }}</div>
                        @if($template->description)
                        <div class="tt-desc">{{ \Illuminate\Support\Str::limit($template->description, 90) }}</div>
                        @endif
                    </td>
                    <td>
                        @if($p)
                        <span class="tt-badge" style="background:{{ $p['bg'] }};color:{{ $p['color'] }}">{{ $p['label'] }}</span>
                        @endif
                    </td>
                    <td>{{ count($template->checklist_items ?? []) }} item(s)</td>
                    <td>
                        <div class="tt-acts">
                            <a href="{{ route('tenant.task-templates.edit', $template->id) }}" class="act-btn"><i class="ti ti-edit"></i></a>
                            <form method="POST" action="{{ route('tenant.task-templates.destroy', $template->id) }}" data-confirm="Delete this template?" data-confirm-ok="Delete">
                                @csrf @method('DELETE')
                                <button type="submit" class="act-btn del"><i class="ti ti-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    <div style="margin-top:14px">
        {{ $templates->links() }}
    </div>

</div>
@endsection
