@extends('layouts.app')
@section('title', 'Field Manager — ' . ucfirst($module))

@push('styles')
<style>
.page-layout { display:grid; grid-template-columns:1fr 340px; gap:16px; align-items:start; }
@media(max-width:1024px) { .page-layout { grid-template-columns:1fr; } }

/* Active fields list */
.field-row {
    display:flex; align-items:center; gap:12px;
    padding:14px 18px; border-bottom:1px solid var(--border-subtle);
    background:var(--bg-surface); transition:background .15s;
}
.field-row:last-child { border-bottom:none; }
.field-row:hover { background:var(--bg-elevated); }
.field-row.is-disabled { opacity:.5; }
.field-row.is-system { background:rgba(255,122,89,.03); }

.drag-handle { cursor:grab; color:var(--text-400); flex-shrink:0; }
.drag-handle:hover { color:var(--text-200); }
.drag-handle svg { width:15px; height:15px; pointer-events:none; }

.source-badge {
    font-size:9.5px; font-weight:700; padding:2px 6px;
    border-radius:3px; text-transform:uppercase; letter-spacing:.4px;
    flex-shrink:0;
}
.source-global { background:var(--accent-dim); color:var(--accent); }
.source-custom { background:var(--purple-dim); color:var(--purple); }
.source-system { background:var(--green-dim); color:var(--green); }

.field-name { font-size:13.5px; font-weight:600; color:var(--text-100); }
.field-key  { font-size:11.5px; color:var(--text-400); font-family:var(--mono); margin-top:1px; }

.mini-tags { display:flex; gap:4px; margin-top:4px; flex-wrap:wrap; }
.mini-tag  { font-size:10px; font-weight:600; padding:1px 6px; border-radius:3px; }

/* Toggles in row */
.row-toggles { display:flex; gap:8px; flex-shrink:0; }
.rt-wrap { display:flex; flex-direction:column; align-items:center; gap:3px; }
.rt-label { font-size:9px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.3px; }
.sw-sm { position:relative; width:34px; height:18px; }
.sw-sm input { opacity:0; width:0; height:0; position:absolute; }
.sw-sm-track { position:absolute; inset:0; background:var(--border-default); border-radius:18px; cursor:pointer; transition:background .2s; }
.sw-sm input:checked ~ .sw-sm-track { background:var(--accent); }
.sw-sm-thumb { position:absolute; top:2px; left:2px; width:14px; height:14px; border-radius:50%; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.2); transition:transform .2s; pointer-events:none; }
.sw-sm input:checked ~ .sw-sm-track .sw-sm-thumb { transform:translateX(16px); }
.sw-sm input:disabled ~ .sw-sm-track { opacity:.5; cursor:not-allowed; }

/* Right panel */
.add-panel { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; position:sticky; top:80px; }
.panel-head { padding:14px 18px; border-bottom:1px solid var(--border-subtle); font-size:13.5px; font-weight:700; color:var(--text-100); }
.panel-tabs { display:flex; border-bottom:1px solid var(--border-subtle); }
.ptab { flex:1; padding:10px; text-align:center; font-size:12.5px; font-weight:600; cursor:pointer; color:var(--text-300); border-bottom:2px solid transparent; transition:all .15s; background:none; border-top:none; border-left:none; border-right:none; font-family:var(--font); }
.ptab.active { color:var(--accent); border-bottom-color:var(--accent); }
.panel-body { padding:16px; }

/* Available field chips */
.avail-item {
    display:flex; align-items:center; justify-content:space-between;
    padding:10px 12px; border:1.5px solid var(--border-default);
    border-radius:var(--r-sm); margin-bottom:8px; cursor:pointer;
    transition:all .15s; background:none;
}
.avail-item:hover { border-color:var(--accent); background:var(--accent-dim); }
.avail-item:last-child { margin-bottom:0; }
.avail-name { font-size:13px; font-weight:600; color:var(--text-100); }
.avail-type { font-size:11px; color:var(--text-400); font-family:var(--mono); margin-top:1px; }
.avail-add  { font-size:11px; font-weight:700; color:var(--accent); }

.no-more { padding:20px; text-align:center; font-size:13px; color:var(--text-400); }

.fi { padding:9px 12px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:13.5px; outline:none; transition:border-color .15s; width:100%; }
.fi:focus { border-color:var(--accent); }

.empty-state { padding:50px 20px; text-align:center; }
</style>
@endpush

@section('content')

@php
    $moduleIcons = ['lead'=>'👤','contact'=>'📒','deal'=>'💼','quotation'=>'📄','task'=>'✅'];
    $modLabel    = $modules[$module]['label'] ?? ucfirst($module);
    $modIcon     = $moduleIcons[$module] ?? '📋';
    $toggleUrl   = url('tenant/tenant-fields');
    $reorderUrl  = route('tenant.tenant-fields.reorder');
@endphp

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.tenant-fields.index') }}" style="color:var(--text-300);text-decoration:none">Field Manager</a>
            <span style="margin:0 6px">›</span>
            {{ $modIcon }} {{ $modLabel }}
        </div>
        <div class="page-title">{{ $modLabel }} Fields</div>
        <div class="page-sub">{{ $assignments->where('is_active',true)->count() }} active fields</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('tenant.tenant-fields.index') }}" class="btn btn-secondary">← Back</a>
        <a href="{{ route('tenant.custom-fields.create', $module) }}" class="btn btn-primary">
            + New Custom Field
        </a>
    </div>
</div>

@if(session('success'))
<div style="display:flex;align-items:center;gap:10px;padding:12px 16px;background:var(--green-dim);border:1px solid rgba(45,212,160,.25);border-radius:var(--r-sm);margin-bottom:16px;font-size:13px;color:var(--green);font-weight:500">
    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:16px;height:16px;flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    {{ session('success') }}
</div>
@endif

@if(session('error'))
<div style="padding:12px 16px;background:var(--red-dim);border:1px solid rgba(255,82,87,.25);border-radius:var(--r-sm);margin-bottom:16px;font-size:13px;color:var(--red)">
    {{ session('error') }}
</div>
@endif

<div class="page-layout">

    {{-- ── Active fields list ───────────────────────────────────── --}}
    <div style="background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-lg);overflow:hidden">

        <div style="padding:12px 18px;background:var(--bg-elevated);border-bottom:1px solid var(--border-subtle);display:flex;align-items:center;justify-content:space-between">
            <div style="display:flex;align-items:center;gap:16px">
                <div style="width:20px"></div>
                <span style="font-size:11px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.4px;width:55px">Source</span>
                <span style="font-size:11px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.4px;flex:1">Field</span>
            </div>
            <div style="display:flex;gap:8px;font-size:10px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.3px">
                <div style="width:34px;text-align:center">Active</div>
                <div style="width:34px;text-align:center">Reqd</div>
                <div style="width:34px;text-align:center">List</div>
                <div style="width:34px;text-align:center">Filter</div>
                <div style="width:28px"></div>
            </div>
        </div>

        @if($assignments->isEmpty())
        <div class="empty-state">
            <div style="font-size:36px;margin-bottom:10px">🔧</div>
            <div style="font-size:14px;font-weight:700;color:var(--text-100);margin-bottom:6px">No fields configured</div>
            <div style="font-size:13px;color:var(--text-300)">Add fields from the right panel</div>
        </div>
        @else

        <div id="tfList">
            @foreach($assignments as $assignment)
            @php
                $info     = $assignment->field_info;
                $isSystem = $info['is_system'] ?? false;
                $source   = $info['source'] ?? 'custom';
                $ft       = $fieldTypes[$info['field_type'] ?? 'text'] ?? ['icon'=>'?','label'=>'text'];
            @endphp
            <div class="field-row {{ !$info['is_active'] ? 'is-disabled':'' }} {{ $isSystem ? 'is-system':'' }}"
                 data-id="{{ $assignment->id }}"
                 draggable="{{ $isSystem ? 'false':'true' }}">

                {{-- Drag --}}
                <div class="drag-handle" title="{{ $isSystem ? 'System field — cannot reorder':'Drag to reorder' }}"
                     style="{{ $isSystem ? 'opacity:.3;cursor:not-allowed':'' }}">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                    </svg>
                </div>

                {{-- Source badge --}}
                @if($isSystem)
                <span class="source-badge source-system">System</span>
                @elseif($source === 'global')
                <span class="source-badge source-global">Global</span>
                @else
                <span class="source-badge source-custom">Custom</span>
                @endif

                {{-- Field info --}}
                <div style="flex:1;min-width:0">
                    <div class="field-name">
                        {{ $ft['icon'] }} {{ $info['label'] ?? '—' }}
                    </div>
                    <div class="field-key">{{ $info['field_key'] ?? '' }}</div>
                </div>

                {{-- 4 toggles: Active, Required, List, Filter --}}
                <div class="row-toggles">
                    @foreach(['is_active'=>'Active','is_required'=>'Reqd','show_in_list'=>'List','show_in_filter'=>'Filter'] as $prop => $label)
                    <div class="rt-wrap">
                        <div class="rt-label">{{ $label }}</div>
                        <label class="sw-sm">
                            <input type="checkbox"
                                   {{ $info[$prop] ?? false ? 'checked':'' }}
                                   {{ ($isSystem && $prop==='is_active') ? 'disabled':'' }}
                                   data-assign-id="{{ $assignment->id }}"
                                   data-prop="{{ $prop }}"
                                   class="tf-toggle"/>
                            <div class="sw-sm-track"><div class="sw-sm-thumb"></div></div>
                        </label>
                    </div>
                    @endforeach
                </div>

                {{-- Remove --}}
                @if(!$isSystem)
                <form data-confirm="Remove this field from module?" data-confirm-ok="Remove" method="POST" action="{{ route('tenant.tenant-fields.remove', $assignment->id) }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-secondary btn-sm btn-icon"
                            style="color:var(--red);flex-shrink:0"
                            title="Remove from module">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:13px;height:13px">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </form>
                @else
                <div style="width:28px"></div>
                @endif

            </div>
            @endforeach
        </div>

        <div style="padding:10px 18px;background:var(--bg-elevated);border-top:1px solid var(--border-subtle);font-size:12px;color:var(--text-400)">
            Drag to reorder &nbsp;·&nbsp; System fields cannot be removed
        </div>
        @endif

    </div>

    {{-- ── Right panel: Add fields ──────────────────────────────── --}}
    <div class="add-panel">
        <div class="panel-head">+ Add Fields to {{ $modLabel }}</div>

        <div class="panel-tabs">
            <button type="button" class="ptab active" id="tabGlobal" onclick="switchTab('global')">
                Global Fields
                @if($availableGlobal->isNotEmpty())
                <span style="background:var(--accent);color:#fff;font-size:10px;padding:1px 5px;border-radius:10px;margin-left:4px">{{ $availableGlobal->count() }}</span>
                @endif
            </button>
            <button type="button" class="ptab" id="tabCustom" onclick="switchTab('custom')">
                My Custom
                @if($availableCustom->isNotEmpty())
                <span style="background:var(--purple);color:#fff;font-size:10px;padding:1px 5px;border-radius:10px;margin-left:4px">{{ $availableCustom->count() }}</span>
                @endif
            </button>
        </div>

        {{-- Global templates --}}
        <div class="panel-body" id="panelGlobal">
            @if($availableGlobal->isEmpty())
            <div class="no-more">
                ✅ All global fields added
            </div>
            @else
            <div style="font-size:12px;color:var(--text-400);margin-bottom:12px">
                Click a field to add it to your module
            </div>
            @foreach($availableGlobal as $tpl)
            <form method="POST" action="{{ route('tenant.tenant-fields.add-global', $module) }}">
                @csrf
                <input type="hidden" name="template_id" value="{{ $tpl->id }}"/>
                <button type="submit" class="avail-item" style="width:100%;text-align:left">
                    <div>
                        <div class="avail-name">
                            {{ $fieldTypes[$tpl->field_type]['icon'] ?? '?' }}
                            {{ $tpl->label }}
                            @if($tpl->is_recommended)
                            <span style="font-size:9px;background:var(--amber-dim);color:var(--amber);padding:1px 5px;border-radius:3px;font-weight:700;margin-left:4px">Recommended</span>
                            @endif
                        </div>
                        <div class="avail-type">{{ $tpl->field_type }}{{ $tpl->description ? ' · '.$tpl->description : '' }}</div>
                    </div>
                    <span class="avail-add">+ Add</span>
                </button>
            </form>
            @endforeach
            @endif
        </div>

        {{-- Custom fields --}}
        <div class="panel-body" id="panelCustom" style="display:none">
            @if($availableCustom->isEmpty())
            <div class="no-more">
                No unassigned custom fields.<br/>
                <a href="{{ route('tenant.custom-fields.create', $module) }}"
                   style="color:var(--accent);font-size:13px;font-weight:600">
                    + Create new field →
                </a>
            </div>
            @else
            <div style="font-size:12px;color:var(--text-400);margin-bottom:12px">
                Your custom fields for this module
            </div>
            @foreach($availableCustom as $cf)
            <form method="POST" action="{{ route('tenant.tenant-fields.add-custom', $module) }}">
                @csrf
                <input type="hidden" name="custom_field_id" value="{{ $cf->id }}"/>
                <button type="submit" class="avail-item" style="width:100%;text-align:left">
                    <div>
                        <div class="avail-name">
                            {{ $fieldTypes[$cf->field_type]['icon'] ?? '?' }} {{ $cf->label }}
                        </div>
                        <div class="avail-type">{{ $cf->field_type }}</div>
                    </div>
                    <span class="avail-add" style="color:var(--purple)">+ Add</span>
                </button>
            </form>
            @endforeach
            @endif
        </div>

    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    var TF_CSRF     = document.querySelector('meta[name="csrf-token"]').content;
    var TF_BASE     = '{{ url("/tenant-fields") }}';
    var REORDER_URL = '{{ $reorderUrl }}';

    // ── Toggle switches ───────────────────────────────────────────
    document.querySelectorAll('.tf-toggle').forEach(function (cb) {
        cb.addEventListener('change', function () {
            var assignId = this.dataset.assignId;
            var prop     = this.dataset.prop;
            var val      = this.checked;
            var self     = this;

            var body = {};
            body[prop] = val;

            fetch(TF_BASE + '/' + assignId, {
                method:  'POST',
                headers: {
                    'X-CSRF-TOKEN': TF_CSRF,
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-HTTP-Method-Override': 'PUT',
                },
                body: JSON.stringify(body)
            })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (!d.success) { self.checked = !self.checked; }

                // If toggling is_active — update row opacity
                if (prop === 'is_active') {
                    var row = self.closest('.field-row');
                    row.classList.toggle('is-disabled', !val);
                }
            })
            .catch(function () { self.checked = !self.checked; });
        });
    });

    // ── Drag & drop reorder ───────────────────────────────────────
    var list = document.getElementById('tfList');
    if (!list) return;

    var dragging = null;

    function rows() {
        return Array.from(list.querySelectorAll('.field-row[draggable="true"]'));
    }

    list.querySelectorAll('.field-row[draggable="true"]').forEach(function (row) {
        row.addEventListener('dragstart', function (e) {
            dragging = row;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', row.dataset.id);
            setTimeout(function () { row.style.opacity = '.4'; }, 0);
        });

        row.addEventListener('dragend', function () {
            row.style.opacity = '';
            dragging = null;
            list.querySelectorAll('.field-row').forEach(function (r) {
                r.style.borderTop = '';
                r.style.borderBottom = '';
            });
            saveOrder();
        });

        row.addEventListener('dragover', function (e) {
            e.preventDefault();
            if (!dragging || row === dragging) return;

            var rect  = row.getBoundingClientRect();
            var after = e.clientY > rect.top + rect.height / 2;

            list.querySelectorAll('.field-row').forEach(function (r) {
                r.style.borderTop = '';
                r.style.borderBottom = '';
            });

            if (after) {
                row.style.borderBottom = '2px solid var(--accent)';
            } else {
                row.style.borderTop = '2px solid var(--accent)';
            }
        });

        row.addEventListener('drop', function (e) {
            e.preventDefault();
            if (!dragging || row === dragging) return;

            var rect  = row.getBoundingClientRect();
            var after = e.clientY > rect.top + rect.height / 2;

            row.style.borderTop = '';
            row.style.borderBottom = '';

            if (after) {
                row.after(dragging);
            } else {
                row.before(dragging);
            }
        });
    });

    function saveOrder() {
        var order = Array.from(list.querySelectorAll('.field-row'))
                        .map(function (r) { return r.dataset.id; });

        fetch(REORDER_URL, {
            method:  'POST',
            headers: {
                'X-CSRF-TOKEN': TF_CSRF,
                'Content-Type': 'application/json',
                'Accept':       'application/json',
            },
            body: JSON.stringify({ order: order })
        });
    }

    // ── Tab switch ────────────────────────────────────────────────
    window.switchTab = function (tab) {
        document.getElementById('panelGlobal').style.display = tab === 'global' ? 'block' : 'none';
        document.getElementById('panelCustom').style.display = tab === 'custom' ? 'block' : 'none';
        document.getElementById('tabGlobal').classList.toggle('active', tab === 'global');
        document.getElementById('tabCustom').classList.toggle('active', tab === 'custom');
    };

})();
</script>
@endpush