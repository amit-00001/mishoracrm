@extends('layouts.app')
@section('title', 'Edit Lead — ' . $lead->name)

@push('styles')
<style>
.form-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; max-width:860px; }
.form-section { padding:24px; border-bottom:1px solid var(--border-subtle); }
.form-section:last-child { border-bottom:none; }
.fs-title { font-size:13px; font-weight:700; color:var(--text-100); text-transform:uppercase; letter-spacing:.4px; margin-bottom:4px; }
.fs-sub   { font-size:12.5px; color:var(--text-300); margin-bottom:20px; }
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.span-2 { grid-column:1/-1; }
@media(max-width:640px) { .form-grid { grid-template-columns:1fr; } .span-2 { grid-column:1; } }

.field { display:flex; flex-direction:column; gap:7px; }
.field-label { font-size:12.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.3px; }
.req { color:var(--red); margin-left:2px; }
.field-input {
    padding:10px 13px; background:var(--bg-input);
    border:1.5px solid var(--border-default); border-radius:var(--r-sm);
    color:var(--text-100); font-family:var(--font); font-size:14px; outline:none;
    transition:border-color .15s var(--ease), box-shadow .15s var(--ease);
}
.field-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
.field-input::placeholder { color:var(--text-400); }
.field-input.is-error { border-color:var(--red); }
.field-select { -webkit-appearance:none; appearance:none; cursor:pointer; }
.field-textarea { resize:vertical; min-height:90px; }
.field-error { font-size:12px; color:var(--red); font-weight:500; }
.field-hint  { font-size:12px; color:var(--text-400); }

/* Status cards */
.status-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:8px; }
@media(max-width:600px) { .status-grid { grid-template-columns:repeat(2,1fr); } }
.status-card {
    padding:10px 8px; border-radius:var(--r-md);
    border:1.5px solid var(--border-default);
    cursor:pointer; transition:all .15s var(--ease);
    text-align:center; background:none;
}
.status-card input { display:none; }
.status-dot  { width:8px; height:8px; border-radius:50%; margin:0 auto 5px; }
.status-name { font-size:11.5px; font-weight:600; color:var(--text-200); }

/* Priority cards */
.priority-grid { display:flex; gap:8px; }
.priority-card {
    flex:1; padding:10px 8px; border-radius:var(--r-md);
    border:1.5px solid var(--border-default);
    cursor:pointer; transition:all .15s var(--ease);
    text-align:center; background:none;
}
.priority-card input { display:none; }
.priority-name { font-size:12px; font-weight:600; color:var(--text-200); }

/* Current info box */
.current-info {
    display:flex; align-items:center; gap:12px;
    padding:12px 16px; background:var(--bg-elevated);
    border:1px solid var(--border-subtle); border-radius:var(--r-sm);
    margin-bottom:20px;
}
.current-av {
    width:40px; height:40px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:16px; font-weight:800; flex-shrink:0;
}

.form-actions {
    display:flex; align-items:center; justify-content:space-between;
    padding:16px 24px; background:var(--bg-elevated);
    border-top:1px solid var(--border-subtle);
}
</style>
@endpush

@section('content')

@php
    $statuses   = config('crm.lead.statuses');
    $sources    = config('crm.lead.sources');
    $priorities = config('crm.lead.priorities');

    $statusColors = [
        'new'         => 'var(--accent)',
        'contacted'   => 'var(--amber)',
        'qualified'   => 'var(--purple)',
        'proposal'    => 'var(--accent)',
        'negotiation' => 'var(--amber)',
        'converted'   => 'var(--green)',
        'lost'        => 'var(--red)',
    ];

    $priorityColors = [
        'low'    => 'var(--green)',
        'medium' => 'var(--amber)',
        'high'   => 'var(--red)',
    ];

    $selStatus   = old('status',   $lead->status);
    $selPriority = old('priority', $lead->priority);
    $selSource   = old('source',   $lead->source);

    $avColors = [['var(--accent-dim)','var(--accent)'],['var(--green-dim)','var(--green)'],['var(--amber-dim)','var(--amber)'],['var(--purple-dim)','var(--purple)']];
    [$avBg,$avTx] = $avColors[abs(crc32($lead->name)) % 4];
@endphp

{{-- Breadcrumb --}}
<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.leads.index') }}" style="color:var(--text-300);text-decoration:none">Leads</a>
            <span style="margin:0 6px">›</span>
            <a href="{{ route('tenant.leads.show', $lead->id) }}" style="color:var(--text-300);text-decoration:none">{{ $lead->name }}</a>
            <span style="margin:0 6px">›</span>
            Edit
        </div>
        <div class="page-title">Edit Lead</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('tenant.leads.show', $lead->id) }}" class="btn btn-secondary">← Back</a>
    </div>
</div>

{{-- Current lead info box --}}
<div class="current-info" style="max-width:860px;margin-bottom:16px">
    <div class="current-av" style="background:{{ $avBg }};color:{{ $avTx }}">
        {{ strtoupper(substr($lead->name,0,1)) }}
    </div>
    <div style="flex:1">
        <div style="font-size:14px;font-weight:700;color:var(--text-100)">{{ $lead->name }}</div>
        <div style="font-size:12.5px;color:var(--text-300);margin-top:2px">
            {{ $lead->phone }}
            @if($lead->email) · {{ $lead->email }} @endif
            · Added {{ $lead->created_at->diffForHumans() }}
        </div>
    </div>
    <div style="display:flex;gap:6px">
        @php $sc = $statuses[$lead->status] ?? ['label'=>ucfirst($lead->status),'color'=>'accent','bg'=>'accent-dim']; @endphp
        <span style="font-size:11.5px;font-weight:600;padding:3px 10px;border-radius:20px;background:var(--{{ $sc['bg'] }});color:var(--{{ $sc['color'] }})">
            {{ $sc['label'] }}
        </span>
        @php $pc = $priorities[$lead->priority] ?? ['label'=>ucfirst($lead->priority),'color'=>'amber']; @endphp
        <span style="font-size:11.5px;font-weight:600;padding:3px 10px;border-radius:20px;background:var(--amber-dim);color:var(--{{ $pc['color'] }})">
            {{ $pc['label'] }}
        </span>
    </div>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('tenant.leads.update', $lead->id) }}" novalidate>
        @csrf @method('PUT')

        {{-- ── Basic Info ─────────────────────────────────────── --}}
        <div class="form-section">
            <div class="fs-title">Basic Information</div>
            <div class="fs-sub">Lead ki primary details</div>
            <div class="form-grid">

                <div class="field">
                    <label class="field-label">Full Name <span class="req">*</span></label>
                    <input type="text" name="name"
                           class="field-input {{ $errors->has('name') ? 'is-error':'' }}"
                           value="{{ old('name', $lead->name) }}"
                           placeholder="Lead ka naam" required/>
                    @error('name') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field">
                    <label class="field-label">Phone <span class="req">*</span></label>
                    <input type="tel" name="phone" id="phone"
                           class="field-input {{ $errors->has('phone') ? 'is-error':'' }}"
                           value="{{ old('phone', $lead->phone) }}"
                           placeholder="+91 98765 43210" required/>
                    @error('phone') <span class="field-error">{{ $message }}</span> @enderror
                    <div id="dupWarning" style="display:none;align-items:center;gap:6px;font-size:12px;color:var(--amber);background:var(--amber-dim);border:1px solid #F0D9A8;border-radius:6px;padding:6px 10px;margin-top:6px"></div>
                </div>

                <div class="field">
                    <label class="field-label">Email</label>
                    <input type="email" name="email" id="email"
                           class="field-input {{ $errors->has('email') ? 'is-error':'' }}"
                           value="{{ old('email', $lead->email) }}"
                           placeholder="email@example.com"/>
                    @error('email') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field">
                    <label class="field-label">Source</label>
                    <select name="source" class="field-input field-select">
                        @foreach($sources as $key => $src)
                        <option value="{{ $key }}" {{ $selSource === $key ? 'selected':'' }}>
                            {{ $src['icon'] }} {{ $src['label'] }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label class="field-label">Assigned To</label>
                    <select name="assigned_to" class="field-input field-select">
                        <option value="">— Unassigned —</option>
                        @foreach($staffList as $staff)
                        <option value="{{ $staff->id }}"
                            {{ old('assigned_to', $lead->assigned_to) == $staff->id ? 'selected':'' }}>
                            {{ $staff->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

            </div>
        </div>

        {{-- ── Status ──────────────────────────────────────────── --}}
        <div class="form-section">
            <div class="fs-title">Status</div>
            <div class="fs-sub">Lead ka current stage kya hai</div>

            <input type="hidden" name="status" id="statusInput" value="{{ $selStatus }}"/>
            <div class="status-grid">
                @foreach($statuses as $key => $sc)
                <div class="status-card" id="sc_{{ $key }}"
                     onclick="selectStatus('{{ $key }}')"
                     style="{{ $selStatus === $key ? 'border-color:var(--'.$sc['color'].');background:var(--'.$sc['bg'].')' : '' }}">
                    <input type="radio" value="{{ $key }}" {{ $selStatus === $key ? 'checked':'' }}/>
                    <div class="status-dot" style="background:var(--{{ $sc['color'] }})"></div>
                    <div class="status-name"
                         style="{{ $selStatus === $key ? 'color:var(--'.$sc['color'].')' : '' }}">
                        {{ $sc['label'] }}
                    </div>
                </div>
                @endforeach
            </div>
            @error('status') <p style="font-size:12px;color:var(--red);margin-top:8px">{{ $message }}</p> @enderror
        </div>

        {{-- ── Priority ─────────────────────────────────────────── --}}
        <div class="form-section">
            <div class="fs-title">Priority</div>
            <div class="fs-sub">Yeh lead kitni important hai</div>

            <input type="hidden" name="priority" id="priorityInput" value="{{ $selPriority }}"/>
            <div class="priority-grid">
                @foreach($priorities as $key => $pc)
                <div class="priority-card" id="pc_{{ $key }}"
                     onclick="selectPriority('{{ $key }}')"
                     style="{{ $selPriority === $key ? 'border-color:var(--'.$pc['color'].');background:var(--'.$pc['color'].'-dim)' : '' }}">
                    <input type="radio" value="{{ $key }}" {{ $selPriority === $key ? 'checked':'' }}/>
                    <div class="priority-name"
                         style="{{ $selPriority === $key ? 'color:var(--'.$pc['color'].')' : '' }}">
                        @php $icons = ['low'=>'🟢','medium'=>'🟡','high'=>'🔴']; @endphp
                        {{ $icons[$key] ?? '' }} {{ $pc['label'] }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- ── Notes ───────────────────────────────────────────── --}}
        <div class="form-section">
            <div class="fs-title">Notes</div>
            <div class="fs-sub">Additional details ya remarks</div>
            <div class="field">
                <textarea name="notes" class="field-input field-textarea"
                          placeholder="Lead ke baare mein koi notes...">{{ old('notes', $lead->notes) }}</textarea>
            </div>
        </div>

        {{-- ── Custom Fields (auto-render if assigned) ─────────── --}}
@include('components.custom-fields.render', [
    'fields' => \App\Models\CustomField::forModule('lead'),
    'values' => $customValues,
])
        {{-- ── Actions ─────────────────────────────────────────── --}}
        <div class="form-actions">
            {{-- Danger zone --}}
            {{-- <form method="POST" action="{{ route('tenant.leads.destroy', $lead->id) }}"
                  data-confirm="Delete this lead permanently?" data-confirm-ok="Delete">
                @csrf @method('DELETE')
                <button type="delete"
                        style="padding:8px 14px;border-radius:var(--r-sm);border:1.5px solid rgba(255,82,87,.3);background:var(--red-dim);color:var(--red);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)">
                    🗑 Delete Lead
                </button>
            </form> --}}

            <div style="display:flex;gap:10px">
                <a href="{{ route('tenant.leads.show', $lead->id) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                    Save Changes
                </button>
            </div>
        </div>

    </form>
</div>

@endsection

@push('scripts')
<script>
// ── Status selector ───────────────────────────────────────────────
const statusColors = @json(array_map(fn($s) => ['color'=>$s['color'],'bg'=>$s['bg']], $statuses));

function selectStatus(val) {
    document.querySelectorAll('.status-card').forEach(c => {
        c.style.borderColor = '';
        c.style.background  = '';
        c.querySelector('.status-name').style.color = '';
    });
    const card = document.getElementById('sc_' + val);
    const cfg  = statusColors[val];
    if (card && cfg) {
        card.style.borderColor = `var(--${cfg.color})`;
        card.style.background  = `var(--${cfg.bg})`;
        card.querySelector('.status-name').style.color = `var(--${cfg.color})`;
    }
    document.getElementById('statusInput').value = val;
}

// ── Priority selector ─────────────────────────────────────────────
const priorityColors = @json(array_map(fn($p) => $p['color'], $priorities));

function selectPriority(val) {
    document.querySelectorAll('.priority-card').forEach(c => {
        c.style.borderColor = '';
        c.style.background  = '';
        c.querySelector('.priority-name').style.color = '';
    });
    const card  = document.getElementById('pc_' + val);
    const color = priorityColors[val];
    if (card && color) {
        card.style.borderColor = `var(--${color})`;
        card.style.background  = `var(--${color}-dim)`;
        card.querySelector('.priority-name').style.color = `var(--${color})`;
    }
    document.getElementById('priorityInput').value = val;
}

// ── Submit loading ────────────────────────────────────────────────
document.querySelector('form').addEventListener('submit', function (e) {
    // Only trigger on main form, not delete form
    if (this.querySelector('[name="_method"][value="PUT"]')) {
        const btn = document.getElementById('submitBtn');
        if (btn) { btn.innerHTML = '⏳ Saving...'; btn.disabled = true; }
    }
});

// ── Live duplicate check (excludes this lead's own id) ────────────
(function () {
    let dupTimer;
    const phoneEl = document.getElementById('phone');
    const emailEl = document.getElementById('email');
    const dupBox  = document.getElementById('dupWarning');
    const exceptId = {{ $lead->id }};

    function checkDup() {
        clearTimeout(dupTimer);
        dupTimer = setTimeout(async () => {
            const phone = phoneEl.value.trim();
            const email = emailEl.value.trim();
            if (!phone && !email) { dupBox.style.display = 'none'; return; }

            const res = await crmPost("{{ route('tenant.leads.check-duplicate') }}", { phone, email, except_id: exceptId });
            if (res.duplicate) {
                dupBox.innerHTML = `This phone/email already belongs to <strong>${res.match.name}</strong>.
                    <a href="/leads/${res.match.id}" target="_blank" style="margin-left:auto;color:var(--accent);font-weight:600;text-decoration:none">View Lead &rarr;</a>`;
                dupBox.style.display = 'flex';
            } else {
                dupBox.style.display = 'none';
            }
        }, 400);
    }
    phoneEl?.addEventListener('input', checkDup);
    emailEl?.addEventListener('input', checkDup);
})();
</script>
@endpush