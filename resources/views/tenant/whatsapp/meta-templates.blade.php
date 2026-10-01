@extends('layouts.app')
@section('title', 'WhatsApp Message Templates')

@push('styles')
<style>
.mt-grid { display:grid; grid-template-columns:minmax(0,1fr) 360px; gap:16px; align-items:start; }
@media(max-width:1024px){ .mt-grid { grid-template-columns:1fr; } }
.mt-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.mt-card-head { padding:14px 16px; border-bottom:1px solid var(--border-subtle); font-size:13px; font-weight:700; color:var(--text-100); }
.mt-row { padding:14px 16px; border-bottom:1px solid var(--border-subtle); }
.mt-row:last-child { border-bottom:none; }
.mt-name { font-size:13.5px; font-weight:700; color:var(--text-100); font-family:var(--mono); }
.mt-meta { display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-top:6px; }
.mt-pill { font-size:11px; font-weight:600; padding:2px 8px; border-radius:20px; background:var(--bg-elevated); color:var(--text-300); border:1px solid var(--border-default); }
.mt-status-approved { background:var(--green-dim); color:var(--green); border-color:transparent; }
.mt-status-pending, .mt-status-in_appeal { background:var(--amber-dim); color:var(--amber); border-color:transparent; }
.mt-status-rejected, .mt-status-disabled, .mt-status-paused { background:var(--red-dim); color:var(--red); border-color:transparent; }
.mt-body { margin-top:10px; font-size:13px; color:var(--text-200); line-height:1.6; white-space:pre-wrap; word-break:break-word; }
.mt-form { padding:16px; display:flex; flex-direction:column; gap:14px; }
.field { display:flex; flex-direction:column; gap:7px; }
.field-label { font-size:12.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.3px; }
.field-input { padding:10px 13px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:14px; outline:none; }
.field-input:focus { border-color:var(--accent); }
.field-hint { font-size:11.5px; color:var(--text-400); line-height:1.5; }
</style>
@endpush

@section('content')
<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.whatsapp.index') }}" style="color:var(--text-300);text-decoration:none">WhatsApp</a>
            <span style="margin:0 6px">›</span>
            <a href="{{ route('tenant.whatsapp.templates') }}" style="color:var(--text-300);text-decoration:none">Templates</a>
            <span style="margin:0 6px">›</span> Meta Templates
        </div>
        <div class="page-title">WhatsApp Message Templates</div>
    </div>
    <a href="{{ route('tenant.whatsapp.templates') }}" class="btn btn-secondary">Saved Messages</a>
</div>

@if($errors->any())
    <div class="alert alert-error" style="margin-bottom:16px">{{ $errors->first() }}</div>
@endif

<div style="background:var(--bg-elevated);border:1px solid var(--border-subtle);border-radius:var(--r-md);padding:12px 16px;font-size:12.5px;color:var(--text-300);line-height:1.6;margin-bottom:16px">
    WhatsApp only lets you message a customer freely within <strong style="color:var(--text-200)">24 hours of their last message</strong>.
    To start a conversation, or reply after that, you must send a <strong style="color:var(--text-200)">template that Meta has approved</strong>.
    Create one here — Meta usually reviews it within minutes to a day — then pick it on the
    <a href="{{ route('tenant.whatsapp.send') }}" style="color:var(--accent)">Send</a> or
    <a href="{{ route('tenant.whatsapp.bulk') }}" style="color:var(--accent)">Bulk Send</a> screen.
</div>

<div class="mt-grid">

    {{-- Existing templates --}}
    <div class="mt-card">
        <div class="mt-card-head">Your templates ({{ count($templates) }})</div>

        @if($loadError)
            <div style="padding:20px 16px;font-size:13px;color:var(--red)">Could not load templates: {{ $loadError }}</div>
        @elseif(empty($templates))
            <div style="padding:40px 16px;text-align:center;font-size:13px;color:var(--text-300)">No templates yet — create your first one.</div>
        @else
            @foreach($templates as $t)
            <div class="mt-row">
                <div style="display:flex;justify-content:space-between;gap:10px;align-items:flex-start">
                    <div>
                        <div class="mt-name">{{ $t['name'] }}</div>
                        <div class="mt-meta">
                            <span class="mt-pill mt-status-{{ $t['status'] }}">{{ ucfirst(str_replace('_', ' ', $t['status'] ?: 'unknown')) }}</span>
                            <span class="mt-pill">{{ $t['language'] }}</span>
                            @if($t['category'])<span class="mt-pill">{{ ucfirst(strtolower($t['category'])) }}</span>@endif
                            @unless($t['sendable'])<span class="mt-pill" title="This template has a media header or a header variable, which the send forms can't fill in">Not sendable here</span>@endunless
                        </div>
                    </div>
                    @can('whatsapp.manage_templates')
                    <form method="POST" action="{{ route('tenant.whatsapp.meta-templates.destroy', $t['name']) }}"
                          onsubmit="return confirm('Delete template {{ $t['name'] }}? Every language version of it is removed.');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--red)">Delete</button>
                    </form>
                    @endcan
                </div>
                <div class="mt-body">{{ $t['body'] }}</div>
            </div>
            @endforeach
        @endif
    </div>

    {{-- Create --}}
    @can('whatsapp.manage_templates')
    <div class="mt-card">
        <div class="mt-card-head">New template</div>
        <form method="POST" action="{{ route('tenant.whatsapp.meta-templates.store') }}" class="mt-form">
            @csrf

            <div class="field">
                <label class="field-label">Name</label>
                <input type="text" name="name" class="field-input" value="{{ old('name') }}" placeholder="order_shipped" required maxlength="100">
                <span class="field-hint">Lowercase letters, numbers and underscores only. Can't be changed later.</span>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <div class="field">
                    <label class="field-label">Language</label>
                    <select name="language" class="field-input">
                        @foreach($languages as $code => $label)
                            <option value="{{ $code }}" @selected(old('language', 'en') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label class="field-label">Category</label>
                    <select name="category" class="field-input">
                        @foreach($categories as $code => $label)
                            <option value="{{ $code }}" @selected(old('category', 'UTILITY') === $code)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="field">
                <label class="field-label">Message</label>
                <textarea name="body" id="mtBody" class="field-input" rows="5" maxlength="1024" required
                          placeholder="Hi @{{1}}, your order @{{2}} has shipped." oninput="mtBuildExamples()">{{ old('body') }}</textarea>
                <span class="field-hint">Use @{{1}}, @{{2}} … for the parts that change per customer (name, order number…). Up to 1024 characters.</span>
            </div>

            <div class="field" id="mtExamples" style="display:none">
                <label class="field-label">Sample values</label>
                <div id="mtExampleInputs" style="display:flex;flex-direction:column;gap:8px"></div>
                <span class="field-hint">An example for each variable, so Meta's reviewers can see a real message.</span>
            </div>

            <button type="submit" class="btn btn-primary" style="background:#25D366;border-color:#25D366">Submit to Meta for review</button>
        </form>
    </div>
    @endcan

</div>
@endsection

@push('scripts')
<script>
const mtOld = @json(old('examples', []));

function mtBuildExamples() {
    const body = document.getElementById('mtBody');
    if (!body) return;
    const nums = [...body.value.matchAll(/\{\{(\d+)\}\}/g)].map(m => parseInt(m[1], 10));
    const count = nums.length ? Math.max(...nums) : 0;
    const wrap  = document.getElementById('mtExamples');
    const box   = document.getElementById('mtExampleInputs');

    // Keep what's already typed while the count changes.
    const current = [...box.querySelectorAll('input')].map(i => i.value);
    box.innerHTML = '';
    for (let i = 0; i < count; i++) {
        const input = document.createElement('input');
        input.type = 'text';
        input.name = 'examples[]';
        input.className = 'field-input';
        input.required = true;
        input.maxLength = 200;
        input.placeholder = 'Sample for @{{' + (i + 1) + '}}';
        input.value = current[i] ?? mtOld[i] ?? '';
        box.appendChild(input);
    }
    wrap.style.display = count ? 'flex' : 'none';
}
mtBuildExamples();
</script>
@endpush
