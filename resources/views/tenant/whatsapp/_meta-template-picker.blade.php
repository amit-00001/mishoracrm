{{--
    Approved Meta template picker for the Send / Bulk Send forms. Only rendered
    when the tenant's number is linked through the WhatsApp Gateway and has
    approved templates. The including page may define onMetaTemplateChange(text):
    called with the filled-in template text when one is picked, null when cleared.

    Vars: $metaTemplates (normalised templates), $bulk (bool).
--}}
@if(!empty($metaTemplates))
<div class="form-section">
    <div class="fs-title">Approved WhatsApp Template (Optional)</div>
    <div class="field">
        <select name="meta_template" id="metaTemplate" class="field-input field-select" onchange="metaTplChanged()">
            <option value="">— Send as a normal message —</option>
            @foreach($metaTemplates as $t)
                <option value="{{ $t['name'] }}|{{ $t['language'] }}" data-body="{{ $t['body'] }}" data-params="{{ $t['params'] }}">
                    {{ $t['name'] }} ({{ $t['language'] }})
                </option>
            @endforeach
        </select>
        <span style="font-size:11.5px;color:var(--text-400);line-height:1.5">
            A normal message only reaches a customer who wrote to you in the last 24 hours.
            An approved template can be sent to anyone, any time.
            @if($bulk)
                Template fields can use variables like @{{name}} or @{{company}} — they are filled in for each recipient.
            @endif
        </span>
    </div>
    <div id="metaTplParams" style="display:flex;flex-direction:column;gap:10px"></div>
</div>

@push('scripts')
<script>
function metaTplSelected() {
    const sel = document.getElementById('metaTemplate');
    return sel && sel.value ? sel.options[sel.selectedIndex] : null;
}

// The template text with the current field values filled in (unfilled placeholders stay visible).
function metaTplRendered() {
    const opt = metaTplSelected();
    if (!opt) return '';
    const values = [...document.querySelectorAll('#metaTplParams input')].map(i => i.value);
    return opt.dataset.body.replace(/\{\{(\d+)\}\}/g, (m, n) => values[n - 1] || m);
}

function metaTplChanged() {
    const opt = metaTplSelected();
    const box = document.getElementById('metaTplParams');
    box.innerHTML = '';

    if (opt) {
        const count = parseInt(opt.dataset.params, 10) || 0;
        for (let i = 1; i <= count; i++) {
            const input = document.createElement('input');
            input.type = 'text';
            input.name = 'template_params[]';
            input.className = 'field-input';
            input.required = true;
            input.maxLength = 1000;
            input.placeholder = 'Value for @{{' + i + '}}';
            input.addEventListener('input', metaTplSync);
            box.appendChild(input);
        }
    }

    metaTplSync();
}

function metaTplSync() {
    if (typeof onMetaTemplateChange === 'function') {
        onMetaTemplateChange(metaTplSelected() ? metaTplRendered() : null);
    }
}
</script>
@endpush
@endif
