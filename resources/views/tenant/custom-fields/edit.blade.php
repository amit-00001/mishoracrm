@extends('layouts.app')
@section('title', 'Edit Custom Field')

@push('styles')
<style>
.cf-layout { display:grid; grid-template-columns:1fr 300px; gap:16px; align-items:start; }
@media(max-width:1024px) { .cf-layout { grid-template-columns:1fr; } }

.form-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.fc-section { padding:24px; border-bottom:1px solid var(--border-subtle); }
.fc-section:last-child { border-bottom:none; }
.fc-title { font-size:13px; font-weight:700; color:var(--text-100); text-transform:uppercase; letter-spacing:.4px; margin-bottom:4px; }
.fc-sub   { font-size:12.5px; color:var(--text-300); margin-bottom:20px; }

.field { display:flex; flex-direction:column; gap:7px; margin-bottom:16px; }
.field:last-child { margin-bottom:0; }
.field-label { font-size:12.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.3px; }
.req { color:var(--red); margin-left:2px; }
.field-input {
    padding:10px 13px; background:var(--bg-input);
    border:1.5px solid var(--border-default); border-radius:var(--r-sm);
    color:var(--text-100); font-family:var(--font); font-size:14px; outline:none;
    transition:border-color .15s, box-shadow .15s;
}
.field-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
.field-input::placeholder { color:var(--text-400); }
.field-input.is-error { border-color:var(--red); }
.field-error { font-size:12px; color:var(--red); }
.field-hint  { font-size:12px; color:var(--text-400); }
.field-input:disabled { opacity:.6; cursor:not-allowed; }

/* Options */
.options-list { display:flex; flex-direction:column; gap:6px; }
.option-row { display:flex; gap:6px; align-items:center; }
.option-input { flex:1; padding:9px 12px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:13.5px; outline:none; transition:border-color .15s; }
.option-input:focus { border-color:var(--accent); }
.del-opt { width:34px; height:36px; background:var(--red-dim); border:1.5px solid rgba(255,82,87,.25); border-radius:var(--r-sm); color:var(--red); cursor:pointer; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:14px; }
.add-opt-btn { display:flex; align-items:center; justify-content:center; gap:6px; padding:8px 14px; border:1.5px dashed var(--accent); border-radius:var(--r-sm); background:none; color:var(--accent); cursor:pointer; font-family:var(--font); font-size:13px; font-weight:600; width:100%; margin-top:6px; transition:background .15s; }
.add-opt-btn:hover { background:var(--accent-dim); }

/* Toggle */
.sw-row { display:flex; align-items:center; justify-content:space-between; padding:12px 0; border-bottom:1px solid var(--border-subtle); }
.sw-row:last-child { border-bottom:none; }
.sw-info-title { font-size:13.5px; font-weight:600; color:var(--text-100); }
.sw-info-sub   { font-size:12px; color:var(--text-400); margin-top:2px; }
.sw { position:relative; width:42px; height:24px; flex-shrink:0; }
.sw input { opacity:0; width:0; height:0; position:absolute; }
.sw-track { position:absolute; inset:0; background:var(--border-default); border-radius:24px; cursor:pointer; transition:background .2s; }
.sw input:checked ~ .sw-track { background:var(--accent); }
.sw-thumb { position:absolute; top:3px; left:3px; width:18px; height:18px; border-radius:50%; background:#fff; box-shadow:0 1px 4px rgba(0,0,0,.2); transition:transform .2s; pointer-events:none; }
.sw input:checked ~ .sw-track .sw-thumb { transform:translateX(18px); }

/* Sidebar info */
.info-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; position:sticky; top:80px; }
.info-head { padding:14px 16px; border-bottom:1px solid var(--border-subtle); font-size:13px; font-weight:700; color:var(--text-100); }
.info-row  { display:flex; justify-content:space-between; padding:10px 16px; border-bottom:1px solid var(--border-subtle); font-size:13px; }
.info-row:last-child { border-bottom:none; }
.info-lbl  { color:var(--text-300); }
.info-val  { color:var(--text-100); font-weight:600; font-family:var(--mono); font-size:12px; }

/* Danger zone */
.danger-zone { border:1.5px solid rgba(255,82,87,.2); border-radius:var(--r-md); overflow:hidden; margin:16px; }
.dz-head { padding:10px 14px; background:var(--red-dim); font-size:11.5px; font-weight:700; color:var(--red); text-transform:uppercase; letter-spacing:.4px; }
.dz-body { padding:14px; font-size:13px; color:var(--text-200); }

.form-footer { padding:16px 24px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center; gap:10px; }
</style>
@endpush

@section('content')

@php
    $modLabel  = $modules[$module]['label'] ?? ucfirst($module);
    $fieldTypes = \App\Models\CustomField::fieldTypes();
    $ft        = $fieldTypes[$field->field_type] ?? ['label'=>ucfirst($field->field_type),'icon'=>'?'];
    $needsOpts = in_array($field->field_type, ['dropdown','multi_select']);
@endphp

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.custom-fields.index') }}" style="color:var(--text-300);text-decoration:none">Custom Fields</a>
            <span style="margin:0 6px">›</span>
            <a href="{{ route('tenant.custom-fields.module', $module) }}" style="color:var(--text-300);text-decoration:none">{{ $modLabel }}</a>
            <span style="margin:0 6px">›</span>
            Edit
        </div>
        <div class="page-title">Edit Field: {{ $field->label }}</div>
        <div class="page-sub">{{ $modLabel }} module • {{ $ft['icon'] }} {{ $ft['label'] }}</div>
    </div>
    <a href="{{ route('tenant.custom-fields.module', $module) }}" class="btn btn-secondary">← Back</a>
</div>

<div class="cf-layout">

    {{-- ── Form ────────────────────────────────────────────────── --}}
    <div class="form-card">
        <form method="POST"
              action="{{ route('tenant.custom-fields.update', [$module, $field->id]) }}"
              id="editForm">
            @csrf @method('PUT')

            {{-- Basic info --}}
            <div class="fc-section">
                <div class="fc-title">Field Information</div>
                <div class="fc-sub">Label aur placeholder change kar sakte ho</div>

                <div class="field">
                    <label class="field-label">Field Label <span class="req">*</span></label>
                    <input type="text" name="label"
                           class="field-input {{ $errors->has('label') ? 'is-error':'' }}"
                           value="{{ old('label', $field->label) }}" required/>
                    @error('label') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field">
                    <label class="field-label">Placeholder Text</label>
                    <input type="text" name="placeholder" class="field-input"
                           placeholder="Hint text..."
                           value="{{ old('placeholder', $field->placeholder) }}"/>
                </div>

                <div class="field">
                    <label class="field-label">Default Value</label>
                    <input type="text" name="default_value" class="field-input"
                           placeholder="Pre-filled value (optional)"
                           value="{{ old('default_value', $field->default_value) }}"/>
                </div>

                {{-- Type — readonly (cannot change) --}}
                <div class="field">
                    <label class="field-label">Field Type</label>
                    <div style="display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--bg-elevated);border:1.5px solid var(--border-subtle);border-radius:var(--r-sm)">
                        <span style="font-size:18px">{{ $ft['icon'] }}</span>
                        <span style="font-size:14px;font-weight:600;color:var(--text-100)">{{ $ft['label'] }}</span>
                        <span style="font-size:11px;color:var(--text-400);margin-left:auto;font-family:var(--mono)">{{ $field->field_type }}</span>
                    </div>
                    <span class="field-hint">⚠️ Field type change nahi ho sakta (data loss hoga). Delete karke naya field add karo.</span>
                </div>
            </div>

            {{-- Options (if dropdown/multi_select) --}}
            @if($needsOpts)
            <div class="fc-section">
                <div class="fc-title">Dropdown Options</div>
                <div class="fc-sub">Options add, edit ya remove karo</div>

                <div class="options-list" id="optionsList">
                    @php $opts = old('options', $field->options_array ?: ['']); @endphp
                    @foreach($opts as $opt)
                    <div class="option-row">
                        <input type="text" name="options[]" class="option-input"
                               value="{{ $opt }}" placeholder="Option value"/>
                        <button type="button" class="del-opt" onclick="removeOpt(this)">✕</button>
                    </div>
                    @endforeach
                </div>
                <button type="button" class="add-opt-btn" onclick="addOpt()">+ Add Option</button>
            </div>
            @endif

            {{-- Settings --}}
            <div class="fc-section">
                <div class="fc-title">Field Settings</div>
                <div class="fc-sub">Behavior aur visibility configure karo</div>

                <div style="background:var(--bg-elevated);border-radius:var(--r-sm);padding:4px 16px">

                    <div class="sw-row">
                        <div>
                            <div class="sw-info-title">Active</div>
                            <div class="sw-info-sub">Disabled karne par form mein nahi dikhega</div>
                        </div>
                        <label class="sw">
                            <input type="checkbox" name="is_active" value="1"
                                   {{ old('is_active', $field->is_active) ? 'checked':'' }}/>
                            <div class="sw-track"><div class="sw-thumb"></div></div>
                        </label>
                    </div>

                    <div class="sw-row">
                        <div>
                            <div class="sw-info-title">Required Field</div>
                            <div class="sw-info-sub">Yeh field fill karna zaroori hoga</div>
                        </div>
                        <label class="sw">
                            <input type="checkbox" name="is_required" value="1"
                                   {{ old('is_required', $field->is_required) ? 'checked':'' }}/>
                            <div class="sw-track"><div class="sw-thumb"></div></div>
                        </label>
                    </div>

                    <div class="sw-row">
                        <div>
                            <div class="sw-info-title">Show in List View</div>
                            <div class="sw-info-sub">Table mein column dikhega</div>
                        </div>
                        <label class="sw">
                            <input type="checkbox" name="show_in_list" value="1"
                                   {{ old('show_in_list', $field->show_in_list) ? 'checked':'' }}/>
                            <div class="sw-track"><div class="sw-thumb"></div></div>
                        </label>
                    </div>

                    <div class="sw-row">
                        <div>
                            <div class="sw-info-title">Show in Filter</div>
                            <div class="sw-info-sub">Filter bar mein available hoga</div>
                        </div>
                        <label class="sw">
                            <input type="checkbox" name="show_in_filter" value="1"
                                   {{ old('show_in_filter', $field->show_in_filter) ? 'checked':'' }}/>
                            <div class="sw-track"><div class="sw-thumb"></div></div>
                        </label>
                    </div>

                </div>
            </div>

            <div class="form-footer">
                <div></div>

                <div style="display:flex;gap:10px">
                    <a href="{{ route('tenant.custom-fields.module', $module) }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                        Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- ── Info sidebar ─────────────────────────────────────────── --}}
    <div class="info-card">
        <div class="info-head">📋 Field Details</div>
        <div class="info-row">
            <span class="info-lbl">Field ID</span>
            <span class="info-val">#{{ $field->id }}</span>
        </div>
        <div class="info-row">
            <span class="info-lbl">Module</span>
            <span class="info-val">{{ $modLabel }}</span>
        </div>
        <div class="info-row">
            <span class="info-lbl">Type</span>
            <span class="info-val">{{ $field->field_type }}</span>
        </div>
        <div class="info-row">
            <span class="info-lbl">Field Key</span>
            <span class="info-val" style="color:var(--accent)">{{ $field->field_key }}</span>
        </div>
        <div class="info-row">
            <span class="info-lbl">Order</span>
            <span class="info-val">{{ $field->sort_order }}</span>
        </div>
        <div class="info-row">
            <span class="info-lbl">Values Saved</span>
            <span class="info-val">{{ $field->values()->count() }}</span>
        </div>
        <div class="info-row">
            <span class="info-lbl">Created</span>
            <span class="info-val" style="font-size:11px">{{ $field->created_at->format('d M Y') }}</span>
        </div>

        {{-- Usage instructions --}}
        <div style="padding:14px 16px;border-top:1px solid var(--border-subtle)">
            <div style="font-size:11.5px;font-weight:700;color:var(--text-300);text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px">How to use in code</div>
            <div style="background:var(--bg-elevated);border-radius:var(--r-sm);padding:10px;font-size:11px;font-family:var(--mono);color:var(--accent);line-height:1.8;word-break:break-all">
                custom_fields[{{ $field->id }}]<br/>
                field_key: {{ $field->field_key }}
            </div>
        </div>
    </div>

</div>

<div style="margin-top:16px;padding:16px 18px;background:var(--bg-surface);border:1px solid rgba(255,82,87,.3);border-radius:var(--r-lg);display:flex;align-items:center;justify-content:space-between;gap:12px">
    <div>
        <div style="font-size:13px;font-weight:600;color:var(--red)">Danger Zone</div>
        <span style="font-size:12px;color:var(--text-400)">Permanently delete this field. All saved data will be lost.</span>
    </div>
    <form method="POST" action="{{ route('tenant.custom-fields.destroy', $field->id) }}"
          data-confirm="Delete '{{ $field->label }}' permanently? All saved data will be lost." data-confirm-ok="Delete">
        @csrf @method('DELETE')
        <button type="submit"
                style="padding:8px 16px;border-radius:var(--r-sm);border:1.5px solid rgba(255,82,87,.3);background:var(--red-dim);color:var(--red);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)">
            🗑 Delete Field
        </button>
    </form>
</div>

@endsection

@push('scripts')
<script>
function addOpt() {
    const list  = document.getElementById('optionsList');
    const count = list.querySelectorAll('.option-row').length + 1;
    const row   = document.createElement('div');
    row.className = 'option-row';
    row.innerHTML = `
        <input type="text" name="options[]" class="option-input" placeholder="Option ${count}"/>
        <button type="button" class="del-opt" onclick="removeOpt(this)">✕</button>`;
    list.appendChild(row);
    row.querySelector('input').focus();
}

function removeOpt(btn) {
    const list = document.getElementById('optionsList');
    if (list.querySelectorAll('.option-row').length > 1) {
        btn.closest('.option-row').remove();
    } else {
        alert('At least one option is required.');
    }
}
</script>
@endpush