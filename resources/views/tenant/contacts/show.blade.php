@extends('layouts.app')
@section('title', $contact->name . ' — Contact')

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap');
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css');

.cs-page { font-family: 'DM Sans', var(--font), sans-serif; }

/* ── Layout ── */
.cs-layout { display:grid; grid-template-columns:minmax(0,1fr) 280px; gap:16px; margin-top:20px; }
@media(max-width:900px){ .cs-layout { grid-template-columns:1fr; } }

.cs-main    { display:flex; flex-direction:column; gap:14px; }
.cs-sidebar { display:flex; flex-direction:column; gap:14px; }

/* ── Cards ── */
.cs-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    border-radius: 14px; overflow: hidden;
}
.cs-card-head {
    padding: 14px 20px 12px;
    border-bottom: 1px solid var(--border-subtle);
}
.cs-card-title {
    font-size: 11px; font-weight: 600;
    color: var(--text-300); text-transform: uppercase; letter-spacing: .6px;
}

/* ── Hero ── */
.cs-hero { padding: 20px; }
.cs-hero-top { display:flex; align-items:flex-start; gap:16px; }
.cs-avatar {
    width: 52px; height: 52px; border-radius: 50%;
    background: var(--accent-dim); color: var(--accent);
    display: flex; align-items: center; justify-content: center;
    font-size: 17px; font-weight: 600; flex-shrink: 0;
}
.cs-name { font-size: 19px; font-weight: 600; color: var(--text-100); letter-spacing: -0.3px; margin-bottom: 2px; }
.cs-sub  { font-size: 13px; color: var(--text-300); }
.cs-badges { display:flex; gap:6px; flex-wrap:wrap; margin-top:10px; }
.cs-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 4px 10px; border-radius: 20px;
    font-size: 11.5px; font-weight: 600;
}

/* ── Info Grid ── */
.cs-info-grid { display:grid; grid-template-columns:1fr 1fr; }
.cs-info-cell {
    padding: 11px 16px;
    border-bottom: 1px solid var(--border-subtle);
    border-right: 1px solid var(--border-subtle);
}
.cs-info-cell:nth-child(even)  { border-right: none; }
.cs-info-cell:nth-last-child(-n+2) { border-bottom: none; }
.cs-info-lbl {
    font-size: 11px; font-weight: 600; color: var(--text-300);
    text-transform: uppercase; letter-spacing: .5px; margin-bottom: 3px;
    display: flex; align-items: center; gap: 5px;
}
.cs-info-val { font-size: 13.5px; font-weight: 500; color: var(--text-100); }
.cs-info-val a { color: var(--accent); text-decoration: none; }
.cs-info-val a:hover { text-decoration: underline; }
.cs-info-val.muted { color: var(--text-400); font-style: italic; font-weight: 400; }

/* Copy button */
.copy-btn {
    margin-left: 6px; cursor: pointer; background: transparent; border: none;
    color: var(--text-400); font-size: 12px; padding: 0; transition: color .15s;
    display: inline-flex; align-items: center;
}
.copy-btn:hover { color: var(--accent); }

/* ── Quick Actions ── */
.cs-qa-list { display:flex; flex-direction:column; gap:6px; padding:12px; }
.cs-qa-btn {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 12px; border-radius: 8px; cursor: pointer;
    background: var(--bg-elevated); border: 1px solid var(--border-subtle);
    font-family: 'DM Sans', var(--font), sans-serif;
    font-size: 13px; color: var(--text-100); font-weight: 500;
    transition: all .15s; width: 100%; text-align: left; text-decoration: none;
}
.cs-qa-btn:hover { background: var(--bg-surface); border-color: var(--border-default); }
.cs-qa-icon {
    width: 28px; height: 28px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}

/* ── Detail List ── */
.cs-dl-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 9px 16px; border-bottom: 1px solid var(--border-subtle);
}
.cs-dl-row:last-child { border-bottom: none; }
.cs-dl-key { font-size: 12px; color: var(--text-300); }
.cs-dl-val { font-size: 12.5px; font-weight: 500; color: var(--text-100); text-align: right; }

/* ── Notes section ── */
.cs-notes {
    padding: 14px 16px;
    font-size: 13px; color: var(--text-200);
    line-height: 1.6; white-space: pre-wrap;
}
</style>
@endpush

@section('content')
@php
    $contactFields = config('contact_fields');
    $tenantSlug    = auth()->user()->tenant->subdomain;

    // Initials
    $initials = collect(explode(' ', $contact->name))
        ->map(fn($p) => strtoupper($p[0] ?? ''))->join('');
    $initials = substr($initials, 0, 2);

    // Fields that should be shown in info grid (show_in_list = true)
    $visibleFields = collect($contactFields['fields'])->where('show_in_list', true);

    // Section color map
    $sectionColors = [
        'blue'   => ['bg'=>'var(--accent-dim)','text'=>'var(--accent)'],
        'purple' => ['bg'=>'var(--purple-dim)','text'=>'var(--purple)'],
        'teal'   => ['bg'=>'var(--green-dim)','text'=>'var(--green)'],
        'amber'  => ['bg'=>'var(--amber-dim)','text'=>'var(--amber)'],
    ];
@endphp

<div class="cs-page">

    {{-- Page Header --}}
    <div class="page-head">
        <div>
            <div style="font-size:12px;color:var(--text-300);margin-bottom:4px;display:flex;align-items:center;gap:5px">
                <a href="{{ route('tenant.contacts.index') }}" style="color:var(--text-300);text-decoration:none">Contacts</a>
                <span style="opacity:.4">›</span>
                <span>{{ $contact->name }}</span>
            </div>
            <div class="page-title">Contact Detail</div>
        </div>
        <div style="display:flex;gap:8px">
            <a href="{{ route('tenant.contacts.edit', ['tenant'=>$tenantSlug,'id'=>$contact->id]) }}"
               class="btn btn-secondary">
                <i class="ti ti-edit" style="font-size:14px" aria-hidden="true"></i>
                Edit
            </a>
            <form method="POST"
                  action="{{ route('tenant.contacts.destroy', ['tenant'=>$tenantSlug,'id'=>$contact->id]) }}"
                  data-confirm="Delete contact '{{ $contact->name }}'?" data-confirm-ok="Delete"
                  style="display:inline">
                @csrf @method('DELETE')
                <button type="submit" class="btn" style="background:var(--red-dim);border-color:var(--red);color:var(--red)">
                    <i class="ti ti-trash" style="font-size:14px" aria-hidden="true"></i>
                </button>
            </form>
        </div>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div style="display:flex;align-items:center;gap:10px;padding:11px 15px;background:var(--green-dim);border:1px solid var(--green);border-radius:8px;margin-bottom:14px;font-size:13px;color:var(--green);font-weight:500">
        <i class="ti ti-circle-check" style="font-size:16px" aria-hidden="true"></i>
        {{ session('success') }}
    </div>
    @endif

    <div class="cs-layout">

        {{-- ── Main Column ── --}}
        <div class="cs-main">

            {{-- Hero Card --}}
            <div class="cs-card">
                <div class="cs-hero">
                    <div class="cs-hero-top">
                        <div class="cs-avatar">{{ $initials }}</div>
                        <div style="flex:1">
                            <div class="cs-name">{{ $contact->name }}</div>
                            <div class="cs-sub">
                                {{ $contact->designation ?? '' }}
                                @if($contact->designation && $contact->company) · @endif
                                {{ $contact->company ?? '' }}
                                @if(!$contact->designation && !$contact->company)
                                <span style="color:var(--text-400);font-style:italic">No designation/company</span>
                                @endif
                            </div>
                            <div class="cs-badges">
                                @if($contact->city)
                                <span class="cs-badge" style="background:var(--accent-dim);color:var(--accent)">
                                    <i class="ti ti-map-pin" style="font-size:12px" aria-hidden="true"></i>
                                    {{ $contact->city }}@if($contact->state), {{ $contact->state }}@endif
                                </span>
                                @endif
                                @if($contact->gst_number)
                                <span class="cs-badge" style="background:var(--green-dim);color:var(--green)">
                                    <i class="ti ti-receipt-tax" style="font-size:12px" aria-hidden="true"></i>
                                    GST Verified
                                </span>
                                @endif
                                @if($contact->lead)
                                <span class="cs-badge" style="background:var(--purple-dim);color:var(--purple)">
                                    <i class="ti ti-target" style="font-size:12px" aria-hidden="true"></i>
                                    Lead Linked
                                </span>
                                @endif
                            </div>
                        </div>
                        <div style="text-align:right;flex-shrink:0">
                            <div style="font-size:11px;color:var(--text-300);font-family:'DM Mono',monospace">
                                #CT-{{ str_pad($contact->id,4,'0',STR_PAD_LEFT) }}
                            </div>
                            <div style="font-size:11.5px;color:var(--text-300);margin-top:4px">
                                {{ $contact->created_at->format('M d, Y') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Info Grid — Dynamic from config (show_in_list=true fields) ── --}}
            <div class="cs-card">
                <div class="cs-card-head">
                    <div class="cs-card-title">Contact Information</div>
                </div>
                <div class="cs-info-grid">
                    @foreach($visibleFields as $field)
                    @php
                        $rawVal = $contact->{$field['key']} ?? null;

                        // Special: lead_id → show lead name
                        if($field['key'] === 'lead_id') {
                            $displayVal = $contact->lead?->name ?? null;
                        } else {
                            $displayVal = $rawVal;
                        }
                    @endphp
                    <div class="cs-info-cell">
                        <div class="cs-info-lbl">
                            <i class="{{ $field['icon'] }}" style="font-size:13px" aria-hidden="true"></i>
                            {{ $field['label'] }}
                        </div>
                        <div class="cs-info-val {{ !$displayVal ? 'muted' : '' }}">
                            @if(!$displayVal)
                                Not provided
                            @elseif($field['type'] === 'email')
                                <a href="mailto:{{ $displayVal }}">{{ $displayVal }}</a>
                                @if($field['copyable'] ?? false)
                                <button class="copy-btn" onclick="copyText('{{ $displayVal }}')" title="Copy">
                                    <i class="ti ti-copy" aria-hidden="true"></i>
                                </button>
                                @endif
                            @elseif($field['type'] === 'tel')
                                <a href="tel:{{ $displayVal }}">{{ $displayVal }}</a>
                                @if($field['copyable'] ?? false)
                                <button class="copy-btn" onclick="copyText('{{ $displayVal }}')" title="Copy">
                                    <i class="ti ti-copy" aria-hidden="true"></i>
                                </button>
                                @endif
                            @elseif($field['key'] === 'lead_id' && $contact->lead)
                                <a href="{{ route('tenant.leads.show', ['tenant'=>$tenantSlug,'id'=>$contact->lead->id]) }}">
                                    {{ $displayVal }}
                                </a>
                            @else
                                {{ $displayVal }}
                                @if(($field['copyable'] ?? false) && $displayVal)
                                <button class="copy-btn" onclick="copyText('{{ $displayVal }}')" title="Copy">
                                    <i class="ti ti-copy" aria-hidden="true"></i>
                                </button>
                                @endif
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Company Employees ── --}}
            @if($contact->employees->isNotEmpty())
            <div class="cs-card">
                <div class="cs-card-head">
                    <div class="cs-card-title">
                        <i class="ti ti-users" style="font-size:13px;margin-right:5px" aria-hidden="true"></i>
                        Company Employees
                        <span style="color:var(--text-400);font-weight:500;text-transform:none;letter-spacing:0">({{ $contact->employees->count() }})</span>
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;gap:0">
                    @foreach($contact->employees as $employee)
                    <div style="padding:13px 16px;border-bottom:1px solid var(--border-subtle)">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap">
                            <div style="font-size:13.5px;font-weight:600;color:var(--text-100);display:flex;align-items:center;gap:7px">
                                {{ $employee->name ?: '—' }}
                                @if($employee->is_primary)
                                <span class="cs-badge" style="background:var(--green-dim);color:var(--green)">
                                    <i class="ti ti-star-filled" style="font-size:11px" aria-hidden="true"></i>
                                    Primary
                                </span>
                                @endif
                            </div>
                            @if($employee->designation)
                            <span class="cs-badge" style="background:var(--purple-dim);color:var(--purple)">{{ $employee->designation }}</span>
                            @endif
                        </div>
                        @if(!empty($employee->emails) || !empty($employee->phones))
                        <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:6px;font-size:12.5px;color:var(--text-300)">
                            @foreach($employee->emails ?? [] as $email)
                            <a href="mailto:{{ $email }}" style="color:var(--accent);text-decoration:none;display:inline-flex;align-items:center;gap:4px">
                                <i class="ti ti-mail" style="font-size:12px" aria-hidden="true"></i>{{ $email }}
                            </a>
                            @endforeach
                            @foreach($employee->phones ?? [] as $phone)
                            <a href="tel:{{ $phone }}" style="color:var(--accent);text-decoration:none;display:inline-flex;align-items:center;gap:4px">
                                <i class="ti ti-phone" style="font-size:12px" aria-hidden="true"></i>{{ $phone }}
                            </a>
                            @endforeach
                        </div>
                        @endif
                        @if($employee->attachments->isNotEmpty())
                        <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px">
                            @foreach($employee->attachments as $att)
                            <a href="{{ $att->url }}" target="_blank" rel="noopener"
                               style="display:inline-flex;align-items:center;gap:4px;padding:4px 9px;background:var(--bg-elevated);border:1px solid var(--border-subtle);border-radius:6px;font-size:11.5px;color:var(--text-200);text-decoration:none">
                                <i class="ti ti-paperclip" style="font-size:11px" aria-hidden="true"></i>{{ $att->original_name }}
                            </a>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Visiting Card / Documents ── --}}
            @if($contact->attachments->isNotEmpty())
            <div class="cs-card">
                <div class="cs-card-head">
                    <div class="cs-card-title">
                        <i class="ti ti-paperclip" style="font-size:13px;margin-right:5px" aria-hidden="true"></i>
                        Visiting Card / Documents
                        <span style="color:var(--text-400);font-weight:500;text-transform:none;letter-spacing:0">({{ $contact->attachments->count() }})</span>
                    </div>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:8px;padding:14px 16px">
                    @foreach($contact->attachments as $att)
                    <a href="{{ $att->url }}" target="_blank" rel="noopener"
                       style="display:inline-flex;align-items:center;gap:6px;padding:6px 11px;background:var(--bg-elevated);border:1px solid var(--border-subtle);border-radius:7px;font-size:12.5px;color:var(--text-100);text-decoration:none">
                        <i class="ti ti-paperclip" style="font-size:12px" aria-hidden="true"></i>
                        {{ $att->original_name }}
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Address Section (if any address fields filled) ── --}}
            @if($contact->address || $contact->city || $contact->state || $contact->pincode)
            <div class="cs-card">
                <div class="cs-card-head">
                    <div class="cs-card-title">
                        <i class="ti ti-map-pin" style="font-size:13px;margin-right:5px" aria-hidden="true"></i>
                        Address
                    </div>
                </div>
                <div style="padding:14px 16px;font-size:13.5px;color:var(--text-100);line-height:1.7">
                    @if($contact->address)
                    <div>{{ $contact->address }}</div>
                    @endif
                    @if($contact->city || $contact->state || $contact->pincode)
                    <div style="color:var(--text-300)">
                        {{ collect([$contact->city, $contact->state, $contact->pincode])->filter()->join(', ') }}
                    </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Notes ── --}}
            @if($contact->notes)
            <div class="cs-card">
                <div class="cs-card-head">
                    <div class="cs-card-title">
                        <i class="ti ti-notes" style="font-size:13px;margin-right:5px" aria-hidden="true"></i>
                        Notes
                    </div>
                </div>
                <div class="cs-notes">{{ $contact->notes }}</div>
            </div>
            @endif

            {{-- Unified Activity Timeline (follow-ups + email + WhatsApp) ── --}}
            <div class="cs-card">
                <div class="cs-card-head" style="display:flex;align-items:center;justify-content:space-between">
                    <div class="cs-card-title">
                        <i class="ti ti-calendar-time" style="font-size:13px;margin-right:5px" aria-hidden="true"></i>
                        Activity Timeline
                        @if($timeline->count())
                        <span style="color:var(--text-400);font-weight:500;text-transform:none;letter-spacing:0">({{ $timeline->count() }})</span>
                        @endif
                    </div>
                    <div style="display:flex;align-items:center;gap:14px">
                        <a href="{{ route('tenant.tasks.create', ['contact_id' => $contact->id]) }}"
                           style="font-size:12px;font-weight:600;color:var(--accent);text-decoration:none;display:inline-flex;align-items:center;gap:4px">
                            <i class="ti ti-plus" style="font-size:13px" aria-hidden="true"></i>
                            Add Task
                        </a>
                        <a href="{{ route('tenant.followups.create', ['contact_id' => $contact->id]) }}"
                           style="font-size:12px;font-weight:600;color:var(--accent);text-decoration:none;display:inline-flex;align-items:center;gap:4px">
                            <i class="ti ti-plus" style="font-size:13px" aria-hidden="true"></i>
                            Schedule Follow-up
                        </a>
                    </div>
                </div>

                @include('components.activity-timeline', ['entries' => $timeline])
            </div>

        </div>{{-- /cs-main --}}

        {{-- ── Sidebar ── --}}
        <div class="cs-sidebar">

            {{-- Quick Actions ── --}}
            <div class="cs-card">
                <div style="padding:14px 16px 6px">
                    <div class="cs-card-title">Quick Actions</div>
                </div>
                <div class="cs-qa-list">
                    @if($contact->email)
                    <a href="mailto:{{ $contact->email }}" class="cs-qa-btn">
                        <div class="cs-qa-icon" style="background:var(--accent-dim)">
                            <i class="ti ti-mail" style="font-size:15px;color:var(--accent)" aria-hidden="true"></i>
                        </div>
                        Send Email
                    </a>
                    @endif

                    @if($contact->phone)
                    <a href="https://wa.me/91{{ preg_replace('/\D/','',$contact->phone) }}" target="_blank" class="cs-qa-btn">
                        <div class="cs-qa-icon" style="background:var(--green-dim)">
                            <i class="ti ti-brand-whatsapp" style="font-size:15px;color:var(--green)" aria-hidden="true"></i>
                        </div>
                        Send WhatsApp
                    </a>
                    <a href="tel:{{ $contact->phone }}" class="cs-qa-btn">
                        <div class="cs-qa-icon" style="background:var(--amber-dim)">
                            <i class="ti ti-phone" style="font-size:15px;color:var(--amber)" aria-hidden="true"></i>
                        </div>
                        Call Now
                    </a>
                    @endif

                    <a href="{{ route('tenant.contacts.edit', ['tenant'=>$tenantSlug,'id'=>$contact->id]) }}"
                       class="cs-qa-btn">
                        <div class="cs-qa-icon" style="background:var(--purple-dim)">
                            <i class="ti ti-edit" style="font-size:15px;color:var(--purple)" aria-hidden="true"></i>
                        </div>
                        Edit Contact
                    </a>
                </div>
            </div>

            {{-- Contact Details ── --}}
            <div class="cs-card">
                <div style="padding:14px 16px 4px">
                    <div class="cs-card-title">Details</div>
                </div>
                <div>
                    <div class="cs-dl-row">
                        <span class="cs-dl-key">Contact ID</span>
                        <span class="cs-dl-val" style="font-family:'DM Mono',monospace;font-size:12px">
                            #CT-{{ str_pad($contact->id,4,'0',STR_PAD_LEFT) }}
                        </span>
                    </div>
                    <div class="cs-dl-row">
                        <span class="cs-dl-key">Created</span>
                        <span class="cs-dl-val">{{ $contact->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="cs-dl-row">
                        <span class="cs-dl-key">Last Updated</span>
                        <span class="cs-dl-val">{{ $contact->updated_at->diffForHumans() }}</span>
                    </div>
                    @if($contact->lead)
                    <div class="cs-dl-row">
                        <span class="cs-dl-key">Linked Lead</span>
                        <span class="cs-dl-val">
                            <a href="{{ route('tenant.leads.show', ['tenant'=>$tenantSlug,'id'=>$contact->lead->id]) }}"
                               style="color:var(--accent);text-decoration:none;font-size:12.5px">
                                {{ \Illuminate\Support\Str::limit($contact->lead->name, 18) }}
                                <i class="ti ti-external-link" style="font-size:11px" aria-hidden="true"></i>
                            </a>
                        </span>
                    </div>
                    @endif
                    @if($contact->gst_number)
                    <div class="cs-dl-row">
                        <span class="cs-dl-key">GST</span>
                        <span class="cs-dl-val" style="font-family:'DM Mono',monospace;font-size:12px">
                            {{ $contact->gst_number }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- All Fields Summary — any non-null hidden fields ── --}}
            @php
                $hiddenFields = collect($contactFields['fields'])
                    ->where('show_in_list', false)
                    ->reject(fn($f) => !empty($f['module']) && !auth()->user()->tenant?->hasModuleEnabled($f['module']))
                    ->filter(fn($f) => !empty($contact->{$f['key']}))
                    ->values();
            @endphp
            @if($hiddenFields->isNotEmpty())
            <div class="cs-card">
                <div style="padding:14px 16px 4px">
                    <div class="cs-card-title">Additional Info</div>
                </div>
                <div>
                    @foreach($hiddenFields as $hf)
                    @if($hf['key'] !== 'notes')
                    <div class="cs-dl-row">
                        <span class="cs-dl-key">{{ $hf['label'] }}</span>
                        <span class="cs-dl-val" style="font-size:12.5px;max-width:160px;text-align:right;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                            @php $hv = $contact->{$hf['key']}; @endphp
                            {{ $hv instanceof \Carbon\CarbonInterface ? $hv->format('d M Y') : $hv }}
                        </span>
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
            @endif

            @if(auth()->user()->tenant?->hasModuleEnabled('loyalty'))
                @include('tenant.loyalty._contact-panel')
            @endif

        </div>{{-- /cs-sidebar --}}

    </div>{{-- /cs-layout --}}
</div>

{{-- Copy toast --}}
<div id="copyToast" style="position:fixed;bottom:20px;right:20px;background:var(--accent);color:#fff;padding:9px 16px;border-radius:8px;font-size:13px;font-weight:500;opacity:0;transition:opacity .2s;pointer-events:none;z-index:9999;display:flex;align-items:center;gap:7px">
    <i class="ti ti-check" style="font-size:14px" aria-hidden="true"></i>
    Copied to clipboard!
</div>

@endsection

@push('scripts')
<script>
function copyText(text){
    navigator.clipboard.writeText(text).then(function(){
        const t = document.getElementById('copyToast');
        t.style.opacity = '1';
        setTimeout(() => t.style.opacity = '0', 2000);
    });
}
</script>
@endpush