{{--
────────────────────────────────────────────────────────────────
 Partial : resources/views/tenant/deals/_form.blade.php
 Used by : create.blade.php  &  edit.blade.php

 Scope variables expected:
   $dealConfig  — config('deal_fields')
   $contacts    — Collection  (id, name, company)
   $leads       — Collection  (id, name)
   $staffList   — Collection  (id, name)
   $model       — Deal|null   (null on create)
   $isEdit      — bool
────────────────────────────────────────────────────────────────
--}}

@php
$cfgStages  = $dealConfig['stages'];
$cfgFields  = collect($dealConfig['fields']);
$sections   = $dealConfig['sections'];
$grouped    = $cfgFields->groupBy('section');

/* current values helper */
$val = function (string $key) use ($model) {
    $v = $model->{$key} ?? '';
    /* Date casts come back as Carbon — <input type=date> only accepts Y-m-d, so the
       default "Y-m-d H:i:s" string rendered blank and the next save cleared the date. */
    if ($v instanceof \Carbon\CarbonInterface) {
        $v = $v->format('Y-m-d');
    }
    return old($key, $v);
};

/* pre-filled stage from query string (create page) or model (edit) */
$activeStage = old('stage', $model->stage ?? request('stage', 'new'));

/* stage probabilities JSON for JS */
$stageProbs = collect($cfgStages)->map(fn($s) => $s['probability']);

/* section icon bg colors */
$secColors = [
    'blue'   => ['bg'=>'var(--accent-dim)','ic'=>'var(--accent)'],
    'purple' => ['bg'=>'var(--purple-dim)','ic'=>'var(--purple)'],
    'teal'   => ['bg'=>'var(--green-dim)','ic'=>'var(--green)'],
    'amber'  => ['bg'=>'var(--amber-dim)','ic'=>'var(--amber)'],
    'green'  => ['bg'=>'var(--green-dim)','ic'=>'var(--green)'],
    'red'    => ['bg'=>'var(--red-dim)','ic'=>'var(--red)'],
];
@endphp

{{-- ── Sections loop ── --}}
@foreach($sections as $secKey => $sec)
@if($grouped->has($secKey))
@php $sc = $secColors[$sec['color']] ?? $secColors['blue']; @endphp

<div class="df-section" id="sec_{{ $secKey }}">
    <div class="df-sec-head">
        <div class="df-sec-icon" style="background:{{ $sc['bg'] }}">
            <i class="ti {{ $sec['icon'] }}" style="font-size:16px;color:{{ $sc['ic'] }}"></i>
        </div>
        <div>
            <div class="df-sec-title">{{ $sec['title'] }}</div>
            <div class="df-sec-sub">{{ $sec['sub'] }}</div>
        </div>
    </div>

    <div class="df-grid">
        @foreach($grouped[$secKey] as $field)
        @php
            $fVal      = $val($field['key']);
            $hasErr    = $errors->has($field['key']);
            $isFull    = ($field['span'] ?? 'half') === 'full';
            $isStage   = $field['key'] === 'stage';
            $isProb    = $field['key'] === 'probability';
            $isLost    = $field['key'] === 'lost_reason';
        @endphp

        <div class="df-field {{ $isFull ? 'span-full':'' }}"
             @if($isLost) id="lostReasonField" style="display:{{ $activeStage==='lost'?'flex':'none' }}" @endif>

            <label class="df-label" for="df_{{ $field['key'] }}">
                {{ $field['label'] }}
                @if($field['required'] ?? false)<span class="df-req">*</span>@endif
                @if(!empty($field['hint']) && !$isProb)
                {{-- hint shown below input, not in label --}}
                @endif
            </label>

            {{-- ── STAGE — visual pill picker ── --}}
            @if($isStage)
            <input type="hidden" name="stage" id="stageHidden" value="{{ $activeStage }}">
            <div class="stage-picker" id="stagePicker">
                @foreach($cfgStages as $slug => $stg)
                <button type="button"
                        class="sp-btn {{ $activeStage === $slug ? 'sp-active' : '' }}"
                        data-stage="{{ $slug }}"
                        data-prob="{{ $stg['probability'] }}"
                        style="
                            --sp-color:{{ $stg['color'] }};
                            --sp-bg:{{ $stg['bg'] }};
                            --sp-text:{{ $stg['text_color'] }};
                            {{ $activeStage === $slug ? 'background:'.$stg['bg'].';border-color:'.$stg['color'].';color:'.$stg['text_color'] : '' }}
                        ">
                    <span class="sp-dot" style="background:{{ $stg['color'] }}"></span>
                    {{ $stg['label'] }}
                </button>
                @endforeach
            </div>
            @if($hasErr)<span class="df-err">{{ $errors->first('stage') }}</span>@endif

            {{-- ── PROBABILITY — slider ── --}}
            @elseif($isProb)
            <div class="prob-slider-wrap">
                <span class="prob-display" id="probDisplay">
                    {{ $fVal !== '' ? $fVal : ($cfgStages[$activeStage]['probability'] ?? 10) }}%
                </span>
                <input type="range" name="probability" id="df_probability"
                       min="0" max="100" step="5"
                       value="{{ $fVal !== '' ? $fVal : ($cfgStages[$activeStage]['probability'] ?? 10) }}"
                       class="df-range"
                       oninput="document.getElementById('probDisplay').textContent=this.value+'%'"/>
                <div class="prob-bar-bg">
                    <div class="prob-bar-fill" id="probBarFill"
                         style="width:{{ $fVal !== '' ? $fVal : ($cfgStages[$activeStage]['probability'] ?? 10) }}%"></div>
                </div>
            </div>
            <span class="df-hint">{{ $field['hint'] ?? '' }}</span>

            {{-- ── TEXTAREA ── --}}
            @elseif($field['type'] === 'textarea')
            <textarea id="df_{{ $field['key'] }}"
                      name="{{ $field['key'] }}"
                      class="df-input df-area {{ $hasErr ? 'is-err':'' }}"
                      placeholder="{{ $field['placeholder'] ?? '' }}"
                      {{ ($field['required']??false)?'required':'' }}
                      rows="3">{{ $fVal }}</textarea>

            {{-- ── CONTACT SELECT ── --}}
            @elseif($field['key'] === 'contact_id')
            <select id="df_contact_id" name="contact_id"
                    class="df-input df-sel {{ $hasErr?'is-err':'' }}">
                <option value="">{{ $field['placeholder'] ?? '— Select Contact —' }}</option>
                @foreach($contacts as $c)
                <option value="{{ $c->id }}" {{ $fVal == $c->id ? 'selected':'' }}>
                    {{ $c->name }}@if($c->company) — {{ $c->company }}@endif
                </option>
                @endforeach
            </select>

            {{-- ── LEAD SELECT ── --}}
            @elseif($field['key'] === 'lead_id')
            <select id="df_lead_id" name="lead_id"
                    class="df-input df-sel {{ $hasErr?'is-err':'' }}">
                <option value="">{{ $field['placeholder'] ?? '— Select Lead —' }}</option>
                @foreach($leads as $l)
                <option value="{{ $l->id }}" {{ $fVal == $l->id ? 'selected':'' }}>
                    {{ $l->name }}
                </option>
                @endforeach
            </select>

            {{-- ── ASSIGNED TO ── --}}
            @elseif($field['key'] === 'assigned_to')
            <select id="df_assigned_to" name="assigned_to"
                    class="df-input df-sel {{ $hasErr?'is-err':'' }}">
                <option value="">— Unassigned —</option>
                @foreach($staffList as $s)
                <option value="{{ $s->id }}" {{ $fVal == $s->id ? 'selected':'' }}>
                    {{ $s->name }}
                </option>
                @endforeach
            </select>

            {{-- ── REGULAR INPUT (text / number / date) ── --}}
            @else
            @php $hasPrefix = !empty($field['prefix']); @endphp
            <div class="{{ $hasPrefix ? 'input-prefix-wrap':'' }}">
                @if($hasPrefix)
                <span class="input-prefix">{{ $field['prefix'] }}</span>
                @endif
                <input id="df_{{ $field['key'] }}"
                       type="{{ $field['type'] }}"
                       name="{{ $field['key'] }}"
                       class="df-input {{ $hasPrefix?'has-prefix':'' }} {{ $hasErr?'is-err':'' }}"
                       placeholder="{{ $field['placeholder'] ?? '' }}"
                       value="{{ $fVal }}"
                       {{ ($field['required']??false)?'required':'' }}
                       @if($field['key']==='title') id="df_title" @endif
                       @if($field['key']==='value') id="df_value" @endif
                />
            </div>
            @endif

            {{-- Error / hint --}}
            @if($hasErr)
            <span class="df-err">{{ $errors->first($field['key']) }}</span>
            @elseif(!empty($field['hint']) && !$isProb && !$isStage)
            <span class="df-hint">{{ $field['hint'] }}</span>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif
@endforeach

{{-- JS: stage → probability sync --}}
<script>
(function(){
    const stageProbs = @json($stageProbs);

    document.querySelectorAll('.sp-btn').forEach(btn => {
        btn.addEventListener('click', function(){
            const slug = this.dataset.stage;
            const prob = parseInt(this.dataset.prob);

            /* Update hidden input */
            document.getElementById('stageHidden').value = slug;

            /* Update pill styles */
            document.querySelectorAll('.sp-btn').forEach(b => {
                b.classList.remove('sp-active');
                b.style.background  = '';
                b.style.borderColor = '';
                b.style.color       = '';
            });
            this.classList.add('sp-active');
            this.style.background  = this.style.getPropertyValue('--sp-bg')  || getComputedStyle(this).getPropertyValue('--sp-bg');
            this.style.borderColor = this.style.getPropertyValue('--sp-color')|| getComputedStyle(this).getPropertyValue('--sp-color');
            this.style.color       = this.style.getPropertyValue('--sp-text') || getComputedStyle(this).getPropertyValue('--sp-text');

            /* Re-apply inline styles because CSS vars don't work as inline */
            const stagesMap = @json(collect($cfgStages)->map(fn($s)=>['bg'=>$s['bg'],'color'=>$s['color'],'text_color'=>$s['text_color']]));
            if(stagesMap[slug]){
                this.style.background  = stagesMap[slug].bg;
                this.style.borderColor = stagesMap[slug].color;
                this.style.color       = stagesMap[slug].text_color;
            }

            /* Sync probability slider */
            const slider  = document.getElementById('df_probability');
            const display = document.getElementById('probDisplay');
            const fill    = document.getElementById('probBarFill');
            if(slider && display){
                slider.value  = prob;
                display.textContent = prob + '%';
                if(fill) fill.style.width = prob + '%';
            }

            /* Show/hide lost reason */
            const lostField = document.getElementById('lostReasonField');
            if(lostField){
                lostField.style.display = slug === 'lost' ? 'flex' : 'none';
            }

            /* Update sidebar preview */
            if(typeof updateDealPreview === 'function') updateDealPreview();
        });
    });

    /* Slider → fill bar sync */
    const slider = document.getElementById('df_probability');
    const fill   = document.getElementById('probBarFill');
    if(slider && fill){
        slider.addEventListener('input', function(){
            fill.style.width = this.value + '%';
            if(typeof updateDealPreview === 'function') updateDealPreview();
        });
    }

    /* Title/Value → sidebar preview */
    ['df_title','df_value'].forEach(id => {
        const el = document.getElementById(id);
        if(el) el.addEventListener('input', () => {
            if(typeof updateDealPreview === 'function') updateDealPreview();
        });
    });
})();
</script>