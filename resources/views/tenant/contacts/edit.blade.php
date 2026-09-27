@extends('layouts.app')
@section('title', 'Edit — ' . $contact->name)

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap');
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css');

/* ── Reuse same form styles from create ── */
.cf-page { font-family: 'DM Sans', var(--font), sans-serif; }
.cf-layout { display:grid; grid-template-columns:minmax(0,1fr) 260px; gap:16px; margin-top:20px; }
@media(max-width:860px){ .cf-layout { grid-template-columns:1fr; } }
.cf-main { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; overflow:hidden; }
.cf-section { padding:22px 24px; border-bottom:1px solid var(--border-subtle); }
.cf-section:last-of-type { border-bottom:none; }
.cf-section-header { display:flex; align-items:flex-start; gap:12px; margin-bottom:18px; }
.cf-section-icon { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.cf-section-title { font-size:13px; font-weight:600; color:var(--text-100); letter-spacing:-0.1px; }
.cf-section-sub { font-size:12px; color:var(--text-300); margin-top:1px; }
.cf-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.cf-grid .span-full { grid-column:1/-1; }
@media(max-width:640px){ .cf-grid { grid-template-columns:1fr; } .cf-grid .span-full { grid-column:1; } }
.cf-field { display:flex; flex-direction:column; gap:5px; }
.cf-label { font-size:11.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:0.5px; }
.cf-req { color:var(--red); margin-left:2px; }
.cf-input { width:100%; padding:9px 12px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:8px; color:var(--text-100); font-family:'DM Sans',var(--font),sans-serif; font-size:13.5px; outline:none; transition:border-color .15s,box-shadow .15s,background .15s; -webkit-appearance:none; }
.cf-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); background:var(--bg-surface); }
.cf-input::placeholder { color:var(--text-400); font-size:13px; }
.cf-input.is-error { border-color:var(--red); }
.cf-select { cursor:pointer; }
.cf-textarea { resize:vertical; min-height:80px; line-height:1.5; }
.cf-field-error { font-size:12px; color:var(--red); font-weight:500; }
.cf-field-hint  { font-size:12px; color:var(--text-400); }
.cf-footer { display:flex; align-items:center; justify-content:space-between; padding:16px 24px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); }
.cf-footer-note { font-size:12px; color:var(--text-300); }
.cf-footer-note strong { color:var(--text-200); }
.cf-sidebar { display:flex; flex-direction:column; gap:14px; }
.cf-side-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; padding:18px; }
.cf-side-title { font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.6px; margin-bottom:14px; }
.tip-list { display:flex; flex-direction:column; gap:9px; }
.tip-item { display:flex; align-items:flex-start; gap:8px; font-size:12px; color:var(--text-300); line-height:1.45; }
.tip-dot { width:5px; height:5px; border-radius:50%; background:var(--accent); margin-top:5px; flex-shrink:0; }

/* ── Changed Fields Badge ── */
.changed-badge {
    display:none; align-items:center; gap:5px;
    padding:3px 9px; border-radius:20px;
    background:var(--amber-dim); color:var(--amber);
    font-size:11px; font-weight:600;
    margin-left:8px;
}
.changed-badge.show { display:inline-flex; }

/* ── Last Updated bar ── */
.lu-bar {
    display:flex; align-items:center; gap:8px;
    padding:10px 18px;
    background:var(--bg-elevated);
    border-bottom:1px solid var(--border-subtle);
    font-size:12px; color:var(--text-300);
}

@keyframes cf-spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
</style>
@endpush

@section('content')
@php
    $contactFields = config('contact_fields');
    $tenantSlug    = auth()->user()->tenant->subdomain;
@endphp

<div class="cf-page">

    {{-- Header --}}
    <div class="page-head">
        <div>
            <div style="font-size:12px;color:var(--text-300);margin-bottom:4px;display:flex;align-items:center;gap:5px">
                <a href="{{ route('tenant.contacts.index') }}" style="color:var(--text-300);text-decoration:none">Contacts</a>
                <span style="opacity:.4">›</span>
                <a href="{{ route('tenant.contacts.show', ['tenant'=>$tenantSlug,'id'=>$contact->id]) }}"
                   style="color:var(--text-300);text-decoration:none">{{ $contact->name }}</a>
                <span style="opacity:.4">›</span>
                <span>Edit</span>
            </div>
            <div class="page-title" style="display:flex;align-items:center">
                Edit Contact
                <span class="changed-badge" id="changedBadge">
                    <i class="ti ti-pencil" style="font-size:11px" aria-hidden="true"></i>
                    Unsaved changes
                </span>
            </div>
        </div>
        <div style="display:flex;gap:8px">
            <a href="{{ route('tenant.contacts.show', ['tenant'=>$tenantSlug,'id'=>$contact->id]) }}"
               class="btn btn-secondary">
                <i class="ti ti-eye" style="font-size:14px" aria-hidden="true"></i>
                View
            </a>
            <a href="{{ route('tenant.contacts.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-left" style="font-size:14px" aria-hidden="true"></i>
                Back
            </a>
        </div>
    </div>

        <div class="cf-layout">

            {{-- Main Form --}}
            <form method="POST"
                  action="{{ route('tenant.contacts.update', ['tenant'=>$tenantSlug,'id'=>$contact->id]) }}"
                  enctype="multipart/form-data"
                  novalidate id="contactForm">
            @csrf
            @method('PUT')
            <div class="cf-main">

                {{-- Last Updated Bar --}}
                <div class="lu-bar">
                    <i class="ti ti-clock" style="font-size:14px" aria-hidden="true"></i>
                    Last updated {{ $contact->updated_at->diffForHumans() }}
                    @if($contact->updatedBy)
                    · by <strong style="color:var(--text-200);margin-left:3px">{{ $contact->updatedBy->name }}</strong>
                    @endif
                </div>

                {{-- Dynamic Sections --}}
                @include('tenant.contacts._form_fields', ['model' => $contact])

                @include('tenant.contacts._employees_section')
                @include('tenant.contacts._attachments_section')

                {{-- Footer --}}
                <div class="cf-footer">
                    <div class="cf-footer-note">Fields marked <strong>*</strong> are required</div>
                    <div style="display:flex;gap:8px">
                        <a href="{{ route('tenant.contacts.show', ['tenant'=>$tenantSlug,'id'=>$contact->id]) }}"
                           class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="ti ti-device-floppy" id="submitIcon" style="font-size:14px" aria-hidden="true"></i>
                            <span id="submitText">Save Changes</span>
                        </button>
                    </div>
                </div>
            </div>
            </form>

            {{-- Standalone delete forms for existing attachments (kept outside the main form to avoid nesting) --}}
            @foreach($contact->attachments as $att)
            <form id="del-contact-attach-{{ $att->id }}" method="POST"
                  action="{{ route('tenant.contacts.attachments.destroy', ['tenant'=>$tenantSlug,'id'=>$contact->id,'attachment'=>$att->id]) }}"
                  data-confirm="Delete this attachment?" data-confirm-ok="Delete" style="display:none">
                @csrf @method('DELETE')
            </form>
            @endforeach
            @foreach($contact->employees as $employee)
                @foreach($employee->attachments as $att)
                <form id="del-emp-attach-{{ $att->id }}" method="POST"
                      action="{{ route('tenant.contacts.employees.attachments.destroy', ['tenant'=>$tenantSlug,'employee'=>$employee->id,'attachment'=>$att->id]) }}"
                      data-confirm="Delete this file?" data-confirm-ok="Delete" style="display:none">
                    @csrf @method('DELETE')
                </form>
                @endforeach
            @endforeach

            {{-- Sidebar --}}
            <div class="cf-sidebar">

                {{-- Contact Meta --}}
                <div class="cf-side-card">
                    <div class="cf-side-title">Contact Info</div>
                    <div style="display:flex;flex-direction:column;gap:10px">
                        <div style="display:flex;align-items:center;gap:10px">
                            @php
                                $initials = collect(explode(' ',$contact->name))->map(fn($p)=>strtoupper($p[0]??''))->join('');
                                $initials = substr($initials,0,2);
                            @endphp
                            <div style="width:40px;height:40px;border-radius:50%;background:var(--accent-dim);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:600;flex-shrink:0">
                                {{ $initials }}
                            </div>
                            <div>
                                <div style="font-size:13.5px;font-weight:600;color:var(--text-100)">{{ $contact->name }}</div>
                                <div style="font-size:12px;color:var(--text-300)">
                                    ID #{{ str_pad($contact->id,4,'0',STR_PAD_LEFT) }}
                                </div>
                            </div>
                        </div>
                        <div style="height:1px;background:var(--border-subtle)"></div>
                        <div style="font-size:12px;color:var(--text-300);display:flex;flex-direction:column;gap:5px">
                            <div>Created: <strong style="color:var(--text-200)">{{ $contact->created_at->format('M d, Y') }}</strong></div>
                            <div>Updated: <strong style="color:var(--text-200)">{{ $contact->updated_at->diffForHumans() }}</strong></div>
                        </div>
                    </div>
                </div>

                {{-- Danger Zone --}}
                <div class="cf-side-card" style="border-color:var(--red)">
                    <div class="cf-side-title" style="color:var(--red)">Danger Zone</div>
                    <div style="font-size:12px;color:var(--text-300);margin-bottom:12px;line-height:1.5">
                        Delete karne ke baad yeh contact permanently remove ho jayega.
                    </div>
                    <form method="POST"
                          action="{{ route('tenant.contacts.destroy', ['tenant'=>$tenantSlug,'id'=>$contact->id]) }}"
                          data-confirm="Delete contact '{{ $contact->name }}'? This cannot be undone." data-confirm-ok="Delete">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn" style="width:100%;justify-content:center;background:var(--red-dim);border-color:var(--red);color:var(--red);font-size:12.5px">
                            <i class="ti ti-trash" style="font-size:14px" aria-hidden="true"></i>
                            Delete Contact
                        </button>
                    </form>
                </div>

                {{-- Tips --}}
                <div class="cf-side-card">
                    <div class="cf-side-title">Tips</div>
                    <div class="tip-list">
                        <div class="tip-item"><div class="tip-dot"></div><span>Phone change karne se upcoming SMS ka number update hoga</span></div>
                        <div class="tip-item"><div class="tip-dot"></div><span>GST number validate karo before saving</span></div>
                        <div class="tip-item"><div class="tip-dot"></div><span>Lead link change karne se history preserved rahegi</span></div>
                    </div>
                </div>

            </div>
        </div>
</div>
@include('tenant.contacts._employees_assets')
@endsection

@push('scripts')
<script>
(function(){
    // Track unsaved changes
    const form    = document.getElementById('contactForm');
    const badge   = document.getElementById('changedBadge');
    let   changed = false;

    form.querySelectorAll('input,select,textarea').forEach(el => {
        el.addEventListener('input',  () => { changed = true; badge.classList.add('show'); });
        el.addEventListener('change', () => { changed = true; badge.classList.add('show'); });
    });

    // Warn before leaving with unsaved changes
    window.addEventListener('beforeunload', e => {
        if(changed && !form.dataset.submitting){
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // Submit loading state
    form.addEventListener('submit', function(){
        this.dataset.submitting = '1';
        const icon = document.getElementById('submitIcon');
        const text = document.getElementById('submitText');
        icon.style.animation = 'cf-spin .7s linear infinite';
        text.textContent = 'Saving...';
        document.getElementById('submitBtn').disabled = true;
    });

    // ── Live duplicate check (excludes this contact's own id) ─────
    let dupTimer;
    const dPhone = document.getElementById('field_phone');
    const dEmail = document.getElementById('field_email');
    const dupBox = document.getElementById('dupWarning');
    const exceptId = {{ $contact->id }};

    function checkDup(){
        clearTimeout(dupTimer);
        dupTimer = setTimeout(async () => {
            const phone = dPhone?.value.trim() ?? '';
            const email = dEmail?.value.trim() ?? '';
            if (!phone && !email) { if (dupBox) dupBox.style.display = 'none'; return; }

            const res = await crmPost("{{ route('tenant.contacts.check-duplicate') }}", { phone, email, except_id: exceptId });
            if (!dupBox) return;
            if (res.duplicate) {
                dupBox.innerHTML = `This phone/email already belongs to <strong>${res.match.name}</strong>.
                    <a href="/contacts/${res.match.id}" target="_blank" style="margin-left:auto;color:var(--accent);font-weight:600;text-decoration:none">View Contact &rarr;</a>`;
                dupBox.style.display = 'flex';
            } else {
                dupBox.style.display = 'none';
            }
        }, 400);
    }
    dPhone?.addEventListener('input', checkDup);
    dEmail?.addEventListener('input', checkDup);
})();
</script>
@endpush