@extends('layouts.app')
@section('title', 'Edit — ' . $deal->title)

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap');
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css');

/* ── Reuse same styles from create ── */
.df-page { font-family: 'DM Sans', var(--font), sans-serif; }
.df-layout { display:grid; grid-template-columns:minmax(0,1fr) 270px; gap:16px; margin-top:20px; }
@media(max-width:900px){ .df-layout { grid-template-columns:1fr; } }
.df-main { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; overflow:hidden; }
.df-section { padding:20px 22px; border-bottom:1px solid var(--border-subtle); }
.df-section:last-of-type { border-bottom:none; }
.df-sec-head { display:flex; align-items:flex-start; gap:11px; margin-bottom:16px; }
.df-sec-icon { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.df-sec-title { font-size:13px; font-weight:600; color:var(--text-100); letter-spacing:-.1px; }
.df-sec-sub   { font-size:12px; color:var(--text-300); margin-top:1px; }
.df-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.df-grid .span-full { grid-column:1/-1; }
@media(max-width:640px){ .df-grid { grid-template-columns:1fr; } .df-grid .span-full { grid-column:1; } }
.df-field { display:flex; flex-direction:column; gap:5px; }
.df-label { font-size:11.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.5px; }
.df-req  { color:var(--red); margin-left:2px; }
.df-hint { font-size:12px; color:var(--text-400); }
.df-err  { font-size:12px; color:var(--red); font-weight:500; }
.df-input { width:100%; padding:9px 12px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:8px; color:var(--text-100); font-family:'DM Sans',var(--font),sans-serif; font-size:13.5px; outline:none; transition:border-color .15s,box-shadow .15s,background .15s; -webkit-appearance:none; }
.df-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); background:var(--bg-surface); }
.df-input::placeholder { color:var(--text-400); font-size:13px; }
.df-input.is-err { border-color:var(--red); }
.df-sel  { cursor:pointer; }
.df-area { resize:vertical; min-height:82px; line-height:1.55; }
.input-prefix-wrap { position:relative; }
.input-prefix { position:absolute; left:11px; top:50%; transform:translateY(-50%); font-size:13px; color:var(--text-300); pointer-events:none; font-family:'DM Mono',monospace; }
.df-input.has-prefix { padding-left:24px; }
.stage-picker { display:flex; gap:6px; flex-wrap:wrap; }
.sp-btn { flex:1; min-width:80px; padding:9px 8px; border-radius:8px; border:1.5px solid var(--border-default); background:var(--bg-input); cursor:pointer; font-family:'DM Sans',var(--font),sans-serif; font-size:12px; font-weight:500; color:var(--text-300); transition:all .15s; display:flex; align-items:center; justify-content:center; gap:6px; }
.sp-btn:hover { border-color:var(--border-strong); color:var(--text-100); }
.sp-dot { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
.prob-slider-wrap { display:flex; align-items:center; gap:10px; }
.prob-display { font-size:15px; font-weight:600; color:var(--text-100); font-family:'DM Mono',monospace; min-width:40px; flex-shrink:0; }
.df-range { flex:1; height:4px; border-radius:2px; cursor:pointer; outline:none; accent-color:var(--accent); }
.prob-bar-bg   { height:4px; border-radius:2px; background:var(--border-subtle); margin-top:5px; overflow:hidden; }
.prob-bar-fill { height:100%; border-radius:2px; background:var(--accent); transition:width .2s; }
.df-footer { display:flex; align-items:center; justify-content:space-between; padding:15px 22px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); }
.df-footer-note { font-size:12px; color:var(--text-300); }
.df-footer-note strong { color:var(--text-200); }
.df-sidebar { display:flex; flex-direction:column; gap:13px; }
.df-sc { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; padding:17px; }
.df-sc-title { font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.6px; margin-bottom:13px; }
.dp-value { font-size:28px; font-weight:600; color:var(--text-100); font-family:'DM Mono',monospace; letter-spacing:-1px; line-height:1; }
.dp-title { font-size:13px; color:var(--text-300); margin-top:4px; line-height:1.4; }
.dp-stage-badge { display:inline-flex; align-items:center; gap:5px; margin-top:10px; padding:4px 11px; border-radius:20px; font-size:12px; font-weight:600; transition:background .2s,color .2s; }
.tip-list { display:flex; flex-direction:column; gap:9px; }
.tip-item { display:flex; align-items:flex-start; gap:8px; font-size:12px; color:var(--text-300); line-height:1.45; }
.tip-dot  { width:5px; height:5px; border-radius:50%; background:var(--accent); margin-top:5px; flex-shrink:0; }

/* ── Edit-specific ── */
.changed-badge {
    display:none; align-items:center; gap:5px;
    padding:3px 9px; border-radius:20px;
    background:var(--amber-dim); color:var(--amber);
    font-size:11px; font-weight:600; margin-left:8px;
}
.changed-badge.show { display:inline-flex; }

.last-updated-bar {
    display:flex; align-items:center; gap:8px;
    padding:10px 18px; background:var(--bg-elevated);
    border-bottom:1px solid var(--border-subtle);
    font-size:12px; color:var(--text-300);
}

/* Stage change diff */
.stage-changed-notice {
    display:none; align-items:center; gap:7px;
    padding:8px 12px; border-radius:8px;
    background:var(--amber-dim); border:1px solid var(--amber);
    font-size:12px; color:var(--amber); font-weight:500;
    margin-top:8px;
}
.stage-changed-notice.show { display:flex; }

@keyframes df-spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
</style>
@endpush

@section('content')
@php
    $dealConfig  = config('deal_fields');
    $cfgStages   = $dealConfig['stages'];
    $isEdit      = true;
    $model       = $deal;

    $activeStage     = old('stage', $deal->stage ?? 'new');
    $activeStageData = $cfgStages[$activeStage] ?? $cfgStages['new'];
    $originalStage   = $deal->stage ?? 'new';

    $initials = fn(string $name): string =>
        substr(collect(explode(' ',$name))->map(fn($p)=>strtoupper($p[0]??''))->join(''),0,2);
@endphp

<div class="df-page">

    {{-- Header --}}
    <div class="page-head">
        <div>
            <div style="font-size:12px;color:var(--text-300);margin-bottom:4px;display:flex;align-items:center;gap:5px">
                <a href="{{ route('tenant.deals.index') }}" style="color:var(--text-300);text-decoration:none">Deals</a>
                <span style="opacity:.4">›</span>
                <a href="{{ route('tenant.deals.show', $deal->id) }}" style="color:var(--text-300);text-decoration:none">
                    {{ \Illuminate\Support\Str::limit($deal->title, 30) }}
                </a>
                <span style="opacity:.4">›</span>
                <span>Edit</span>
            </div>
            <div class="page-title" style="display:flex;align-items:center">
                Edit Deal
                <span class="changed-badge" id="changedBadge">
                    <i class="ti ti-pencil" style="font-size:11px"></i>
                    Unsaved changes
                </span>
            </div>
        </div>
        <div style="display:flex;gap:8px">
            <a href="{{ route('tenant.deals.show', $deal->id) }}" class="btn btn-secondary">
                <i class="ti ti-eye" style="font-size:14px"></i>
                View
            </a>
            <a href="{{ route('tenant.deals.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-left" style="font-size:14px"></i>
                Back
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('tenant.deals.update', $deal->id) }}"
          novalidate id="dealForm">
        @csrf
        @method('PUT')

        <div class="df-layout">

            {{-- ── Main Form ── --}}
            <div class="df-main">

                {{-- Last Updated Bar --}}
                <div class="last-updated-bar">
                    <i class="ti ti-clock" style="font-size:14px"></i>
                    Last updated {{ $deal->updated_at->diffForHumans() }}
                    @if($deal->createdBy)
                    · Created by <strong style="color:var(--text-200);margin-left:3px">{{ $deal->createdBy->name }}</strong>
                    @endif
                </div>

                {{-- Dynamic sections from config --}}
                @include('tenant.deals._form', [
                    'dealConfig' => $dealConfig,
                    'contacts'   => $contacts,
                    'leads'      => $leads,
                    'staffList'  => $staffList,
                    'model'      => $deal,
                    'isEdit'     => true,
                ])

                {{-- Stage changed notice (shown by JS) --}}
                <div class="stage-changed-notice" id="stageChangedNotice" style="margin:0 22px 16px">
                    <i class="ti ti-refresh" style="font-size:14px"></i>
                    <span id="stageChangedText">Stage change hogi — probability auto-update hogi</span>
                </div>

                {{-- Footer --}}
                <div class="df-footer">
                    <div class="df-footer-note">Fields marked <strong>*</strong> are required</div>
                    <div style="display:flex;gap:8px">
                        <a href="{{ route('tenant.deals.show', $deal->id) }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="ti ti-device-floppy" id="submitIcon" style="font-size:14px"></i>
                            <span id="submitText">Save Changes</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ── Sidebar ── --}}
            <div class="df-sidebar">

                {{-- Current Deal Card --}}
                <div class="df-sc">
                    <div class="df-sc-title">Deal Preview</div>
                    <div class="dp-value" id="previewValue">
                        ₹{{ number_format($deal->value) }}
                    </div>
                    <div class="dp-title" id="previewTitle">
                        {{ $deal->title }}
                    </div>
                    <div class="dp-stage-badge" id="previewStage"
                         style="background:{{ $activeStageData['bg'] }};color:{{ $activeStageData['text_color'] }}">
                        <span style="width:6px;height:6px;border-radius:50%;background:{{ $activeStageData['color'] }};display:inline-block"></span>
                        {{ $activeStageData['label'] }}
                    </div>
                </div>

                {{-- Deal Meta --}}
                <div class="df-sc">
                    <div class="df-sc-title">Deal Info</div>
                    <div style="display:flex;flex-direction:column;gap:9px">
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="width:38px;height:38px;border-radius:50%;background:var(--accent-dim);color:var(--accent);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:600;flex-shrink:0">
                                {{ strtoupper(substr($deal->title,0,2)) }}
                            </div>
                            <div>
                                <div style="font-size:13px;font-weight:600;color:var(--text-100)">
                                    {{ \Illuminate\Support\Str::limit($deal->title,24) }}
                                </div>
                                <div style="font-size:11.5px;color:var(--text-300)">
                                    #DL-{{ str_pad($deal->id,4,'0',STR_PAD_LEFT) }}
                                </div>
                            </div>
                        </div>
                        <div style="height:1px;background:var(--border-subtle)"></div>
                        <div style="display:flex;flex-direction:column;gap:6px;font-size:12px;color:var(--text-300)">
                            <div>Created: <strong style="color:var(--text-200)">{{ $deal->created_at->format('M d, Y') }}</strong></div>
                            <div>Updated: <strong style="color:var(--text-200)">{{ $deal->updated_at->diffForHumans() }}</strong></div>
                            @if($deal->actual_close_date)
                            <div>Closed: <strong style="color:var(--green)">{{ \Carbon\Carbon::parse($deal->actual_close_date)->format('M d, Y') }}</strong></div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Quick Stage Change --}}
                <div class="df-sc">
                    <div class="df-sc-title">Quick Actions</div>
                    <div style="display:flex;flex-direction:column;gap:7px">
                        @if($deal->stage !== 'won')
                        <form method="POST" action="{{ route('tenant.deals.mark_won', $deal->id) }}">
                            @csrf
                            <button type="submit" class="btn" style="width:100%;justify-content:center;background:var(--green-dim);border-color:var(--green);color:var(--green);font-size:12.5px">
                                <i class="ti ti-trophy" style="font-size:14px"></i>
                                Mark as Won 🎉
                            </button>
                        </form>
                        @endif
                        @if($deal->stage !== 'lost')
                        <button type="button" onclick="document.getElementById('lostModal').showModal()"
                                class="btn" style="width:100%;justify-content:center;background:var(--red-dim);border-color:var(--red);color:var(--red);font-size:12.5px">
                            <i class="ti ti-x" style="font-size:14px"></i>
                            Mark as Lost
                        </button>
                        @endif
                    </div>
                </div>

                {{-- Tips --}}
                <div class="df-sc">
                    <div class="df-sc-title">Tips</div>
                    <div class="tip-list">
                        <div class="tip-item"><div class="tip-dot"></div><span>Stage change karne par probability auto-update hogi</span></div>
                        <div class="tip-item"><div class="tip-dot"></div><span>Won mark karne par actual close date set ho jayegi</span></div>
                        <div class="tip-item"><div class="tip-dot"></div><span>Lost reason fill karne se pipeline analysis better hoti hai</span></div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="df-sc" style="border-color:var(--red);margin-top:14px">
        <div class="df-sc-title" style="color:var(--red)">Danger Zone</div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:12px;line-height:1.5">
            Deal delete karne ke baad permanently remove ho jayega.
        </div>
        <form method="POST" action="{{ route('tenant.deals.destroy', $deal->id) }}"
              data-confirm="Delete deal '{{ $deal->title }}'?" data-confirm-ok="Delete">
            @csrf @method('DELETE')
            <button type="submit" class="btn"
                    style="width:100%;justify-content:center;background:var(--red-dim);border-color:var(--red);color:var(--red);font-size:12.5px">
                <i class="ti ti-trash" style="font-size:14px"></i>
                Delete Deal
            </button>
        </form>
    </div>
</div>

{{-- Mark Lost Modal --}}
<dialog id="lostModal" style="border:1px solid var(--border-default);border-radius:14px;padding:24px;background:var(--bg-surface);width:420px;max-width:95vw;box-shadow:0 8px 32px rgba(0,0,0,.15)">
    <form method="POST" action="{{ route('tenant.deals.mark_lost', $deal->id) }}">
        @csrf
        <div style="font-size:16px;font-weight:600;color:var(--text-100);margin-bottom:4px">
            Mark Deal as Lost
        </div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:16px">
            Optionally add a reason to improve future pipeline analysis.
        </div>
        <label style="font-size:11.5px;font-weight:600;color:var(--text-200);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:5px">
            Lost Reason (optional)
        </label>
        <textarea name="lost_reason"
                  style="width:100%;padding:9px 12px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:8px;color:var(--text-100);font-family:'DM Sans',sans-serif;font-size:13px;resize:vertical;min-height:80px;outline:none;margin-bottom:16px"
                  placeholder="e.g. Budget constraints, competitor chosen..."></textarea>
        <div style="display:flex;gap:8px;justify-content:flex-end">
            <button type="button" onclick="document.getElementById('lostModal').close()"
                    class="btn btn-secondary">Cancel</button>
            <button type="submit"
                    class="btn" style="background:var(--red-dim);border-color:var(--red);color:var(--red)">
                <i class="ti ti-x" style="font-size:14px"></i>
                Confirm Lost
            </button>
        </div>
    </form>
</dialog>
@endsection

@push('scripts')
<script>
(function(){

   const stagesConfig = @json(config('deal_fields.stages'));
    const originalStage = "{{ $originalStage }}";
    const form          = document.getElementById('dealForm');
    const changedBadge  = document.getElementById('changedBadge');
    let   isDirty       = false;

    /* ── Mark dirty on any change ── */
    form.querySelectorAll('input,select,textarea').forEach(el => {
        el.addEventListener('input',  markDirty);
        el.addEventListener('change', markDirty);
    });

    function markDirty(){
        isDirty = true;
        changedBadge.classList.add('show');
        if(typeof updateDealPreview === 'function') updateDealPreview();
    }

    /* ── Warn before leaving with unsaved changes ── */
    window.addEventListener('beforeunload', e => {
        if(isDirty && !form.dataset.submitting){
            e.preventDefault(); e.returnValue = '';
        }
    });

    /* ── Stage changed notice ── */
    const stageHidden = document.getElementById('stageHidden');
    if(stageHidden){
        const observer = new MutationObserver(() => {
            const newStage = stageHidden.value;
            const notice   = document.getElementById('stageChangedNotice');
            const text     = document.getElementById('stageChangedText');
            if(newStage !== originalStage){
                notice?.classList.add('show');
                if(text){
                    const newStgData = stagesConfig[newStage];
                    text.textContent = `Stage: ${stagesConfig[originalStage]?.label} → ${newStgData?.label} — probability ${stagesConfig[newStage]?.probability ?? '?'}% ho jayegi`;
                }
            } else {
                notice?.classList.remove('show');
            }
        });
        observer.observe(stageHidden, { attributes: true, attributeFilter: ['value'] });

        /* Also trigger on click */
        document.querySelectorAll('.sp-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                setTimeout(() => {
                    stageHidden.dispatchEvent(new Event('change'));
                    observer.takeRecords();
                    const newStage = stageHidden.value;
                    const notice   = document.getElementById('stageChangedNotice');
                    const text     = document.getElementById('stageChangedText');
                    if(newStage !== originalStage && notice && text){
                        notice.classList.add('show');
                        text.textContent = `Stage: ${stagesConfig[originalStage]?.label} → ${stagesConfig[newStage]?.label} — probability auto-update hogi`;
                    } else if(newStage === originalStage){
                        notice?.classList.remove('show');
                    }
                }, 10);
            });
        });
    }

    /* ── Live Preview ── */
    window.updateDealPreview = function(){
        const title = document.getElementById('df_title')?.value ?? '';
        const val   = parseInt(document.getElementById('df_value')?.value) || 0;
        const stage = document.getElementById('stageHidden')?.value ?? 'new';
        const stg   = stagesConfig[stage] ?? stagesConfig['new'];

        document.getElementById('previewValue').textContent = '₹' + val.toLocaleString('en-IN');

        const titleEl = document.getElementById('previewTitle');
        titleEl.textContent  = title || '{{ addslashes(\Illuminate\Support\Str::limit($deal->title,40)) }}';
        titleEl.style.color  = title ? 'var(--text-100)' : 'var(--text-300)';

        const badge = document.getElementById('previewStage');
        badge.style.background = stg.bg;
        badge.style.color      = stg.text_color;
        badge.innerHTML = `<span style="width:6px;height:6px;border-radius:50%;background:${stg.color};display:inline-block"></span> ${stg.label}`;
    };

    /* ── Submit ── */
    form.addEventListener('submit', function(){
        this.dataset.submitting = '1';
        const icon = document.getElementById('submitIcon');
        const text = document.getElementById('submitText');
        icon.style.animation = 'df-spin .7s linear infinite';
        text.textContent = 'Saving...';
        document.getElementById('submitBtn').disabled = true;
    });

    updateDealPreview();
})();
</script>
@endpush