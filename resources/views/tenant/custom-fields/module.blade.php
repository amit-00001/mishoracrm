@extends('layouts.app')
@section('title', ucfirst($module) . ' Custom Fields')

@push('styles')
<style>
.field-row {
    display:flex; align-items:center; gap:14px;
    padding:16px 20px; border-bottom:1px solid var(--border-subtle);
    transition:background .15s; background:var(--bg-surface);
    cursor:default;
}
.field-row:last-child { border-bottom:none; }
.field-row:hover { background:var(--bg-elevated); }
.field-row.is-disabled { opacity:.5; }
.field-row.drag-over-top    { border-top:3px solid var(--accent); }
.field-row.drag-over-bottom { border-bottom:3px solid var(--accent); }
.field-row.is-dragging      { opacity:.35; background:var(--accent-dim); }

.drag-handle {
    cursor:grab; color:var(--text-400);
    flex-shrink:0; padding:4px;
    display:flex; align-items:center;
    border-radius:4px; transition:color .15s;
}
.drag-handle:hover { color:var(--text-100); background:var(--bg-elevated); }
.drag-handle:active { cursor:grabbing; }
.drag-handle svg { width:16px; height:16px; display:block; pointer-events:none; }

.type-pill {
    display:inline-flex; align-items:center; gap:5px;
    padding:4px 10px; border-radius:var(--r-sm);
    background:var(--bg-elevated); border:1px solid var(--border-subtle);
    font-size:11.5px; font-weight:700; color:var(--text-300);
    font-family:var(--mono); white-space:nowrap; flex-shrink:0; width:110px;
}

.field-info { flex:1; min-width:0; }
.field-label-text { font-size:14px; font-weight:700; color:var(--text-100); margin-bottom:3px; }
.field-key   { font-size:12px; color:var(--text-400); font-family:var(--mono); }
.field-tags  { display:flex; gap:5px; flex-wrap:wrap; margin-top:5px; }
.field-tag   { font-size:10.5px; font-weight:600; padding:2px 7px; border-radius:4px; }

/* Toggle switch */
.sw { position:relative; width:40px; height:22px; flex-shrink:0; }
.sw input { opacity:0; width:0; height:0; position:absolute; }
.sw-track { position:absolute; inset:0; background:var(--border-default); border-radius:22px; cursor:pointer; transition:background .2s; }
.sw input:checked ~ .sw-track { background:var(--accent); }
.sw-thumb { position:absolute; top:3px; left:3px; width:16px; height:16px; border-radius:50%; background:#fff; box-shadow:0 1px 4px rgba(0,0,0,.2); transition:transform .2s; pointer-events:none; }
.sw input:checked ~ .sw-track .sw-thumb { transform:translateX(18px); }

.empty-state { padding:64px 20px; text-align:center; }
.empty-icon  { font-size:48px; margin-bottom:12px; }
.empty-title { font-size:16px; font-weight:700; color:var(--text-100); margin-bottom:6px; }
.empty-sub   { font-size:13.5px; color:var(--text-300); margin-bottom:24px; }

/* Mobile: fixed-width columns (type/active/actions) no longer fit next to
   the field name in one row — wrap them onto their own row instead. */
@media(max-width:640px) {
    .cf-header-row { display:none; }
    .field-row { flex-wrap:wrap; row-gap:10px; padding:14px 16px; }
    .field-row .drag-handle { order:0; }
    .field-row .type-pill { order:1; width:auto; }
    .field-row .field-info { order:3; flex:1 1 100%; }
    .field-row .cf-toggle-col { order:2; width:auto !important; margin-left:auto; }
    .field-row .cf-actions-col { order:4; width:auto !important; margin-left:42px; }
}
</style>
@endpush

@section('content')

@php
    $moduleIcons = ['lead'=>'👤','contact'=>'📒','deal'=>'💼','quotation'=>'📄','task'=>'✅'];
    $modLabel    = $modules[$module]['label'] ?? ucfirst($module);
    $modIcon     = $moduleIcons[$module] ?? '📋';
    // Pass reorder URL with correct module
    $reorderUrl  = route('tenant.custom-fields.reorder');
    $toggleBase  = url('/custom-fields');
@endphp

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.custom-fields.index') }}" style="color:var(--text-300);text-decoration:none">Custom Fields</a>
            <span style="margin:0 6px">›</span>
            {{ $modIcon }} {{ $modLabel }}
        </div>
        <div class="page-title">{{ $modLabel }} Custom Fields</div>
        <div class="page-sub">{{ $fields->count() }} field{{ $fields->count() !== 1 ? 's':'' }} configured</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('tenant.custom-fields.index') }}" class="btn btn-secondary">← All Modules</a>
        <a href="{{ route('tenant.custom-fields.create', $module) }}" class="btn btn-primary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Field
        </a>
    </div>
</div>

{{-- Alerts --}}
@if(session('success'))
<div style="display:flex;align-items:center;gap:10px;padding:12px 16px;background:var(--green-dim);border:1px solid rgba(45,212,160,.25);border-radius:var(--r-sm);margin-bottom:16px;font-size:13px;color:var(--green);font-weight:500">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:16px;height:16px;flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div style="display:flex;align-items:center;gap:10px;padding:12px 16px;background:var(--red-dim);border:1px solid rgba(255,82,87,.25);border-radius:var(--r-sm);margin-bottom:16px;font-size:13px;color:var(--red);font-weight:500">
    {{ session('error') }}
</div>
@endif

<div style="background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-lg);overflow:hidden">

    {{-- Table header --}}
    <div class="cf-header-row" style="padding:12px 20px;background:var(--bg-elevated);border-bottom:1px solid var(--border-subtle);display:flex;align-items:center;gap:14px">
        <div style="width:28px"></div>
        <div style="width:110px;font-size:11px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.4px">Type</div>
        <div style="flex:1;font-size:11px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.4px">Field</div>
        <div style="width:80px;text-align:center;font-size:11px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.4px">Active</div>
        <div style="width:100px;font-size:11px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.4px">Actions</div>
    </div>

    @if($fields->isEmpty())
    <div class="empty-state">
        <div class="empty-icon">📋</div>
        <div class="empty-title">No custom fields yet</div>
        <div class="empty-sub">Add fields to customize the {{ $modLabel }} module</div>
        <a href="{{ route('tenant.custom-fields.create', $module) }}" class="btn btn-primary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add First Field
        </a>
    </div>
    @else

    {{-- Fields list --}}
    <div id="cfFieldsList">
        @foreach($fields as $field)
        @php $ft = $fieldTypes[$field->field_type] ?? ['label'=>ucfirst($field->field_type),'icon'=>'?']; @endphp

        <div class="field-row {{ !$field->is_active ? 'is-disabled':'' }}"
             data-id="{{ $field->id }}"
             draggable="true">

            {{-- Drag handle --}}
            <div class="drag-handle" title="Drag to reorder">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
            </div>

            {{-- Type pill --}}
            <div class="type-pill">
                <span>{{ $ft['icon'] }}</span>
                <span>{{ $field->field_type }}</span>
            </div>

            {{-- Field info --}}
            <div class="field-info">
                <div class="field-label-text">{{ $field->label }}</div>
                <div class="field-key">{{ $field->field_key }}</div>
                <div class="field-tags">
                    @if($field->is_required)
                    <span class="field-tag" style="background:var(--red-dim);color:var(--red)">Required</span>
                    @endif
                    @if($field->show_in_list)
                    <span class="field-tag" style="background:var(--accent-dim);color:var(--accent)">In List</span>
                    @endif
                    @if($field->show_in_filter)
                    <span class="field-tag" style="background:var(--purple-dim);color:var(--purple)">In Filter</span>
                    @endif
                    @if(!empty($field->options_array))
                    <span class="field-tag" style="background:var(--bg-elevated);color:var(--text-300)">
                        {{ count($field->options_array) }} options
                    </span>
                    @endif
                </div>
            </div>

            {{-- Active toggle --}}
            <div class="cf-toggle-col" style="width:80px;display:flex;justify-content:center">
                <label class="sw">
                    <input type="checkbox"
                           {{ $field->is_active ? 'checked':'' }}
                           data-field-id="{{ $field->id }}"
                           class="cf-toggle-input"/>
                    <div class="sw-track"><div class="sw-thumb"></div></div>
                </label>
            </div>

            {{-- Actions --}}
            <div class="cf-actions-col" style="width:100px;display:flex;gap:6px;align-items:center">
                <a href="{{ route('tenant.custom-fields.edit', [$module, $field->id]) }}"
                   class="btn btn-secondary btn-sm btn-icon" title="Edit">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:14px;height:14px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                    </svg>
                </a>
                <form method="POST"
                      action="{{ route('tenant.custom-fields.destroy', $field->id) }}"
                      data-confirm="Delete field permanently? All saved data will be lost." data-confirm-ok="Delete">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-secondary btn-sm btn-icon"
                            style="color:var(--red)" title="Delete">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:14px;height:14px">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Footer --}}
    <div style="padding:12px 20px;background:var(--bg-elevated);border-top:1px solid var(--border-subtle);display:flex;align-items:center;justify-content:space-between">
        <span style="font-size:12px;color:var(--text-400);display:flex;align-items:center;gap:6px">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:13px;height:13px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
            </svg>
            Drag rows to reorder
        </span>
        <a href="{{ route('tenant.custom-fields.create', $module) }}" class="btn btn-primary btn-sm">+ Add Field</a>
    </div>

    @endif
</div>

@endsection

@push('scripts')
<script>
(function () {
    // ── Constants ─────────────────────────────────────────────────
    var CF_CSRF       = document.querySelector('meta[name="csrf-token"]').content;
    var REORDER_URL   = '{{ $reorderUrl }}';
    var TOGGLE_BASE   = '{{ $toggleBase }}';

    // ── Toggle active/inactive ────────────────────────────────────
    document.querySelectorAll('.cf-toggle-input').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var fieldId = this.dataset.fieldId;
            var row     = this.closest('.field-row');
            var self    = this;

            fetch(TOGGLE_BASE + '/' + fieldId + '/toggle', {
                method:  'POST',
                headers: {
                    'X-CSRF-TOKEN': CF_CSRF,
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                }
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    row.classList.toggle('is-disabled', !data.is_active);
                } else {
                    // Revert
                    self.checked = !self.checked;
                }
            })
            .catch(function () {
                self.checked = !self.checked;
            });
        });
    });

    // ── Drag & drop reorder ───────────────────────────────────────
    var container = document.getElementById('cfFieldsList');
    if (!container) return;

    var dragging  = null;

    function getRows() {
        return Array.from(container.querySelectorAll('.field-row'));
    }

    // Attach drag listeners to every row
    getRows().forEach(function (row) {
        row.addEventListener('dragstart', function (e) {
            dragging = row;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', row.dataset.id);
            setTimeout(function () {
                row.classList.add('is-dragging');
            }, 0);
        });

        row.addEventListener('dragend', function () {
            dragging = null;
            row.classList.remove('is-dragging');
            getRows().forEach(function (r) {
                r.classList.remove('drag-over-top', 'drag-over-bottom');
            });
            saveOrder();
        });

        row.addEventListener('dragover', function (e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            if (!dragging || row === dragging) return;

            var rect  = row.getBoundingClientRect();
            var after = e.clientY > rect.top + rect.height / 2;

            getRows().forEach(function (r) {
                r.classList.remove('drag-over-top', 'drag-over-bottom');
            });

            row.classList.add(after ? 'drag-over-bottom' : 'drag-over-top');
        });

        row.addEventListener('dragleave', function (e) {
            if (!row.contains(e.relatedTarget)) {
                row.classList.remove('drag-over-top', 'drag-over-bottom');
            }
        });

        row.addEventListener('drop', function (e) {
            e.preventDefault();
            if (!dragging || row === dragging) return;

            var rect  = row.getBoundingClientRect();
            var after = e.clientY > rect.top + rect.height / 2;

            row.classList.remove('drag-over-top', 'drag-over-bottom');

            if (after) {
                row.after(dragging);
            } else {
                row.before(dragging);
            }
        });
    });

    // Save order to server
    function saveOrder() {
        var order = getRows().map(function (r) { return r.dataset.id; });

        fetch(REORDER_URL, {
            method:  'POST',
            headers: {
                'X-CSRF-TOKEN': CF_CSRF,
                'Content-Type': 'application/json',
                'Accept':       'application/json',
            },
            body: JSON.stringify({ order: order })
        })
        .then(function (r) { return r.json(); })
        .catch(function (e) { console.error('Reorder failed:', e); });
    }

})(); // IIFE — no global variables leaked
</script>
@endpush