@extends('layouts.app')
@section('title', 'Edit Follow-up')

@push('styles')
<style>
.form-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; max-width:720px; }
.form-section { padding:24px; border-bottom:1px solid var(--border-subtle); }
.form-section:last-child { border-bottom:none; }
.form-section-title { font-size:13px; font-weight:700; color:var(--text-100); text-transform:uppercase; letter-spacing:0.4px; margin-bottom:4px; }
.form-section-sub { font-size:12.5px; color:var(--text-300); margin-bottom:20px; }
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.form-grid .span-2 { grid-column:1/-1; }
@media(max-width:640px) { .form-grid { grid-template-columns:1fr; } .form-grid .span-2 { grid-column:1; } }

.field { display:flex; flex-direction:column; gap:7px; }
.field-label { font-size:12.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:0.3px; }
.field-label .req { color:var(--red); margin-left:2px; }
.field-input {
    padding:10px 13px;
    background:var(--bg-input); border:1.5px solid var(--border-default);
    border-radius:var(--r-sm); color:var(--text-100);
    font-family:var(--font); font-size:14px; outline:none;
    transition:border-color 0.15s var(--ease), box-shadow 0.15s var(--ease);
}
.field-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
.field-input::placeholder { color:var(--text-400); }
.field-input.is-error { border-color:var(--red); }
.field-select { -webkit-appearance:none; appearance:none; cursor:pointer; }
.field-textarea { resize:vertical; min-height:90px; }
.field-error { font-size:12px; color:var(--red); font-weight:500; }

.type-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:8px; }
@media(max-width:600px) { .type-grid { grid-template-columns:repeat(3,1fr); } }
.type-card {
    display:flex; flex-direction:column; align-items:center; gap:6px;
    padding:12px 8px; border-radius:var(--r-md);
    border:1.5px solid var(--border-default);
    cursor:pointer; transition:all 0.15s var(--ease); background:none;
}
.type-card:hover { border-color:var(--border-strong); background:var(--bg-hover); }
.type-card.selected { border-color:var(--accent); background:var(--accent-dim); }
.type-card input { display:none; }
.type-icon { width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; }
.type-icon svg { width:16px; height:16px; }
.type-label { font-size:11.5px; font-weight:600; color:var(--text-200); }
.type-card.selected .type-label { color:var(--accent); }

.form-actions {
    display:flex; align-items:center; justify-content:flex-end; gap:10px;
    padding:20px 24px; background:var(--bg-elevated);
    border-top:1px solid var(--border-subtle);
}

/* Status badges */
.status-opts { display:flex; gap:8px; flex-wrap:wrap; }
.status-opt {
    padding:7px 14px; border-radius:20px;
    border:1.5px solid var(--border-default);
    cursor:pointer; font-size:12.5px; font-weight:600;
    color:var(--text-300); background:none; transition:all 0.15s var(--ease);
}
.status-opt:hover { border-color:var(--border-strong); color:var(--text-100); }
.status-opt.sel-scheduled { border-color:var(--accent); background:var(--accent-dim); color:var(--accent); }
.status-opt.sel-done { border-color:var(--green); background:var(--green-dim); color:var(--green); }
.status-opt.sel-missed { border-color:var(--red); background:var(--red-dim); color:var(--red); }
.status-opt.sel-rescheduled { border-color:var(--amber); background:var(--amber-dim); color:var(--amber); }

/* Attachments */
.attach-upload {
    display:flex; align-items:center; gap:10px;
    padding:14px; border:1.5px dashed var(--border-default);
    border-radius:var(--r-md);
}
.attach-upload input[type=file] { flex:1; min-width:0; padding:10px 12px; background:var(--bg-input); border:1px solid var(--border-default); border-radius:var(--r-sm); font-size:13px; color:var(--text-300); }
.attach-hint { font-size:11.5px; color:var(--text-400); margin-top:6px; }
.attach-selected { font-size:12px; color:var(--text-300); margin-top:10px; }

.attach-list { display:flex; flex-direction:column; gap:10px; margin-top:16px; }
.attach-item {
    display:flex; align-items:center; gap:12px;
    padding:10px 12px; border:1px solid var(--border-subtle);
    border-radius:var(--r-sm); background:var(--bg-elevated);
}
.attach-thumb {
    width:40px; height:40px; border-radius:var(--r-sm); flex-shrink:0;
    object-fit:cover; border:1px solid var(--border-subtle);
}
.attach-icon {
    width:40px; height:40px; border-radius:var(--r-sm); flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    background:var(--accent-dim); color:var(--accent); font-size:11px; font-weight:700;
}
.attach-meta { flex:1; min-width:0; }
.attach-name {
    font-size:13px; font-weight:600; color:var(--text-100);
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis; display:block;
    text-decoration:none;
}
.attach-name:hover { color:var(--accent); }
.attach-sub { font-size:11.5px; color:var(--text-400); margin-top:2px; }
.attach-del {
    background:none; border:none; cursor:pointer; color:var(--text-400);
    padding:4px; border-radius:var(--r-sm); flex-shrink:0;
}
.attach-del:hover { color:var(--red); background:var(--red-dim); }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.followups.index') }}" style="color:var(--text-300);text-decoration:none">Follow-ups</a>
            <span style="margin:0 6px">›</span>
            <span>Edit Follow-up</span>
        </div>
        <div class="page-title">Edit Follow-up</div>
    </div>
    <a href="{{ route('tenant.followups.index') }}" class="btn btn-secondary">← Back</a>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('tenant.followups.update', $followup->id) }}" enctype="multipart/form-data" novalidate>
        @csrf
        @method('PUT')

        {{-- Type --}}
        <div class="form-section">
            <div class="form-section-title">Follow-up Type <span style="color:var(--red)">*</span></div>
            <div class="form-section-sub">Type change kar sakte ho</div>

            <div class="type-grid">
                @php
                    $typeIcons = [
                        'call'     => ['icon'=>'M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z', 'bg'=>'var(--green-dim)',  'color'=>'var(--green)',  'label'=>'Call'],
                        'email'    => ['icon'=>'M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75', 'bg'=>'var(--accent-dim)', 'color'=>'var(--accent)', 'label'=>'Email'],
                        'whatsapp' => ['icon'=>'M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 01-.923 1.785A5.969 5.969 0 006 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337z', 'bg'=>'var(--green-dim)',  'color'=>'var(--green)',  'label'=>'WhatsApp'],
                        'meeting'  => ['icon'=>'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z', 'bg'=>'var(--purple-dim)', 'color'=>'var(--purple)', 'label'=>'Meeting'],
                        'other'    => ['icon'=>'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z', 'bg'=>'var(--amber-dim)', 'color'=>'var(--amber)', 'label'=>'Other'],
                    ];
                    $curType = old('type', $followup->type);
                @endphp

                @foreach($typeIcons as $val => $t)
                <label class="type-card {{ $curType === $val ? 'selected':'' }}" id="tc-{{ $val }}" onclick="selectType('{{ $val }}')">
                    <input type="radio" name="type" value="{{ $val }}" {{ $curType === $val ? 'checked':'' }}/>
                    <div class="type-icon" style="background:{{ $t['bg'] }};color:{{ $t['color'] }}">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $t['icon'] }}"/>
                        </svg>
                    </div>
                    <span class="type-label">{{ $t['label'] }}</span>
                </label>
                @endforeach
            </div>
        </div>

        {{-- Status --}}
        <div class="form-section">
            <div class="form-section-title">Status <span style="color:var(--red)">*</span></div>
            <div class="form-section-sub">Follow-up ka current status update karo</div>

            @php $curStatus = old('status', $followup->status); @endphp
            <input type="hidden" name="status" id="statusInput" value="{{ $curStatus }}"/>

            <div class="status-opts">
                @foreach(['scheduled'=>'📅 Scheduled','done'=>'✅ Done','missed'=>'❌ Missed','rescheduled'=>'🔄 Rescheduled'] as $val => $label)
                <button type="button"
                    class="status-opt {{ $curStatus === $val ? 'sel-'.$val : '' }}"
                    id="so-{{ $val }}"
                    onclick="selectStatus('{{ $val }}')">
                    {{ $label }}
                </button>
                @endforeach
            </div>
        </div>

        {{-- Schedule details --}}
        <div class="form-section">
            <div class="form-section-title">Schedule Details</div>
            <div class="form-section-sub">Date, time aur assignment update karo</div>

            <div class="form-grid">
                <div class="field">
                    <label class="field-label">Date & Time <span class="req">*</span></label>
                    <input type="datetime-local" name="scheduled_at"
                           class="field-input {{ $errors->has('scheduled_at') ? 'is-error':'' }}"
                           value="{{ old('scheduled_at', $followup->scheduled_at->format('Y-m-d\TH:i')) }}"
                           required/>
                    @error('scheduled_at') <span class="field-error">{{ $message }}</span> @enderror
                </div>

                <div class="field">
                    <label class="field-label">Assign To <span class="req">*</span></label>
                    <select name="assigned_to" class="field-input field-select" required>
                        <option value="">Select staff</option>
                        @foreach($staffList as $staff)
                        <option value="{{ $staff->id }}"
                            {{ old('assigned_to', $followup->assigned_to) == $staff->id ? 'selected':'' }}>
                            {{ $staff->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label class="field-label">Link to Lead</label>
                    <select name="lead_id" class="field-input field-select">
                        <option value="">No lead</option>
                        @foreach($leads as $l)
                        <option value="{{ $l->id }}"
                            {{ old('lead_id', $followup->lead_id) == $l->id ? 'selected':'' }}>
                            {{ $l->name }} — {{ $l->phone }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label class="field-label">Link to Contact</label>
                    <select name="contact_id" class="field-input field-select">
                        <option value="">No contact</option>
                        @foreach($contacts as $c)
                        <option value="{{ $c->id }}"
                            {{ old('contact_id', $followup->contact_id) == $c->id ? 'selected':'' }}>
                            {{ $c->name }} — {{ $c->phone }}
                        </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        {{-- Notes & Outcome --}}
        <div class="form-section">
            <div class="form-section-title">Notes & Outcome</div>
            <div class="form-section-sub">Notes aur result of this follow-up</div>

            <div class="form-grid">
                <div class="field span-2">
                    <label class="field-label">Notes</label>
                    <textarea name="notes" class="field-input field-textarea"
                              placeholder="What to discuss...">{{ old('notes', $followup->notes) }}</textarea>
                </div>

                <div class="field span-2" id="outcomeField"
                     style="{{ old('status', $followup->status) === 'done' ? '' : 'display:none' }}">
                    <label class="field-label">Outcome / Result</label>
                    <textarea name="outcome" class="field-input field-textarea"
                              placeholder="What happened in this follow-up...">{{ old('outcome', $followup->outcome) }}</textarea>
                </div>
            </div>
        </div>

        {{-- Attachments --}}
        <div class="form-section">
            <div class="form-section-title">Attachments</div>
            <div class="form-section-sub">Related documents, PDFs ya images</div>

            <div class="attach-upload">
                <input id="attachments" type="file" name="attachments[]" multiple
                       accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx"/>
            </div>
            <div class="attach-hint">Images, PDFs, Word or Excel files · up to 10 MB each · max 5 files (uploads on save)</div>
            <div class="attach-selected" id="selectedFiles">No file selected.</div>
            @error('attachments') <p style="font-size:12px;color:var(--red);margin-top:8px">{{ $message }}</p> @enderror
            @error('attachments.*') <p style="font-size:12px;color:var(--red);margin-top:8px">{{ $message }}</p> @enderror

            <div class="attach-list">
                @forelse($followup->attachments as $attachment)
                    <div class="attach-item">
                        @if($attachment->isImage())
                            <img src="{{ $attachment->url }}" alt="" class="attach-thumb">
                        @else
                            <div class="attach-icon">{{ strtoupper(pathinfo($attachment->original_name, PATHINFO_EXTENSION)) }}</div>
                        @endif
                        <div class="attach-meta">
                            <a href="{{ $attachment->url }}" target="_blank" rel="noopener" class="attach-name">
                                {{ $attachment->original_name }}
                            </a>
                            <div class="attach-sub">
                                {{ $attachment->file_size_human }}
                                · {{ $attachment->created_at->diffForHumans() }}
                                @if($attachment->uploadedBy)
                                    · {{ $attachment->uploadedBy->name }}
                                @endif
                            </div>
                        </div>
                        <button type="submit" form="del-attach-{{ $attachment->id }}" class="attach-del" title="Delete">
                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                @empty
                    <div style="font-size:13px;color:var(--text-400);padding:4px 0">No documents or images attached yet.</div>
                @endforelse
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('tenant.followups.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary" id="submitBtn">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
                Update Follow-up
            </button>
        </div>

    </form>

    {{-- Standalone delete forms for existing attachments (kept outside the main form to avoid nesting) --}}
    @foreach($followup->attachments as $attachment)
        <form id="del-attach-{{ $attachment->id }}" method="POST"
              action="{{ route('tenant.followups.attachments.destroy', [$followup, $attachment]) }}"
              data-confirm="Delete this attachment?" data-confirm-ok="Delete" style="display:none">
            @csrf @method('DELETE')
        </form>
    @endforeach
</div>

@endsection

@push('scripts')
<script>
function selectType(val) {
    document.querySelectorAll('.type-card').forEach(c => c.classList.remove('selected'));
    document.getElementById('tc-' + val).classList.add('selected');
    document.querySelector(`input[name="type"][value="${val}"]`).checked = true;
}

function selectStatus(val) {
    document.querySelectorAll('.status-opt').forEach(b => {
        b.className = 'status-opt';
    });
    document.getElementById('so-' + val).classList.add('sel-' + val);
    document.getElementById('statusInput').value = val;

    // Show outcome field when done
    document.getElementById('outcomeField').style.display = val === 'done' ? '' : 'none';
}

document.querySelector('form').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '⏳ Updating...';
    btn.disabled = true;
});
</script>
@endpush