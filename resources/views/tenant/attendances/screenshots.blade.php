@extends('layouts.app')
@section('title', 'Screenshots')

@section('content')
@php $tenantSlug = auth()->user()->tenant->subdomain; @endphp

<div class="page-head">
    <div>
        <div class="page-title">📷 Screenshots</div>
        <div class="page-sub">
            {{ $attendance->staff->name }} —
            {{ $attendance->date->format('d F Y') }} —
            {{ $screenshots->count() }} captures
        </div>
    </div>
    <div class="page-actions">
        <a href="{{ route('tenant.attendances.clock', ['tenant' => $tenantSlug]) }}"
           class="btn btn-secondary">← Back</a>
    </div>
</div>

{{-- Attendance summary --}}
<div style="display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap">
    <div style="background:var(--bg-surface);border:1px solid var(--border-default);
                border-radius:var(--r-md);padding:14px 20px;display:flex;gap:16px;align-items:center">
        <div style="text-align:center">
            <div style="font-size:11px;color:var(--text-400);margin-bottom:2px">CLOCK IN</div>
            <div style="font-size:15px;font-weight:700;font-family:var(--mono);color:var(--green)">
                {{ $attendance->clock_in?->format('h:i A') ?? '—' }}
            </div>
        </div>
        <div style="width:1px;height:30px;background:var(--border-subtle)"></div>
        <div style="text-align:center">
            <div style="font-size:11px;color:var(--text-400);margin-bottom:2px">CLOCK OUT</div>
            <div style="font-size:15px;font-weight:700;font-family:var(--mono);color:var(--red)">
                {{ $attendance->clock_out?->format('h:i A') ?? 'Active' }}
            </div>
        </div>
        <div style="width:1px;height:30px;background:var(--border-subtle)"></div>
        <div style="text-align:center">
            <div style="font-size:11px;color:var(--text-400);margin-bottom:2px">WORKED</div>
            <div style="font-size:15px;font-weight:700;font-family:var(--mono);color:var(--text-100)">
                {{ $attendance->worked_hours ?? '—' }}
            </div>
        </div>
        <div style="width:1px;height:30px;background:var(--border-subtle)"></div>
        <div style="text-align:center">
            <div style="font-size:11px;color:var(--text-400);margin-bottom:2px">SCREENSHOTS</div>
            <div style="font-size:15px;font-weight:700;font-family:var(--mono);color:var(--accent)">
                {{ $screenshots->count() }}
            </div>
        </div>
    </div>
</div>

{{-- Screenshots grid --}}
@if($screenshots->isEmpty())
<div class="card">
    <div class="empty-state">
        <div class="empty-icon">📷</div>
        <div class="empty-title">Koi screenshot nahi mila</div>
        <div class="empty-sub">Staff ne screen share allow nahi kiya hoga</div>
    </div>
</div>
@else
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px">
    @foreach($screenshots as $shot)
    <div style="background:var(--bg-surface);border:1px solid var(--border-default);
                border-radius:var(--r-lg);overflow:hidden;
                transition:border-color 0.15s var(--ease);"
         onmouseover="this.style.borderColor='var(--border-strong)'"
         onmouseout="this.style.borderColor='var(--border-default)'">

        {{-- Image --}}
        <div style="position:relative;cursor:pointer"
             onclick="openLightbox('{{ $shot->url }}')">
            <img src="{{ $shot->url }}"
                 alt="Screenshot"
                 style="width:100%;height:180px;object-fit:cover;display:block">

            {{-- Type badge --}}
            <span style="position:absolute;top:8px;left:8px;
                         padding:3px 8px;border-radius:20px;font-size:11px;font-weight:600;
                         background:{{ $shot->type === 'clockout' ? 'var(--red)' : 'var(--accent)' }};
                         color:#fff">
                {{ $shot->type === 'clockout' ? '⏹ Clock Out' : '🔄 Auto' }}
            </span>

            {{-- Fullscreen icon --}}
            <div style="position:absolute;top:8px;right:8px;
                        width:28px;height:28px;border-radius:50%;
                        background:rgba(0,0,0,0.5);display:flex;align-items:center;justify-content:center">
                <svg style="width:14px;height:14px;color:#fff" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/>
                </svg>
            </div>
        </div>

        {{-- Info --}}
        <div style="padding:12px 14px;display:flex;align-items:center;justify-content:space-between">
            <div>
                <div style="font-size:13px;font-weight:600;color:var(--text-100);font-family:var(--mono)">
                    {{ $shot->captured_at->format('h:i:s A') }}
                </div>
                <div style="font-size:11.5px;color:var(--text-400);margin-top:2px">
                    {{ $shot->file_size_human }}
                </div>
            </div>
            <form method="POST"
                  action="{{ route('tenant.screenshots.destroy', ['tenant' => $tenantSlug, 'screenshot' => $shot->id]) }}"
                  data-confirm="Delete karna chahte ho?" data-confirm-ok="Delete">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-secondary btn-sm btn-icon"
                        style="color:var(--red)">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:13px;height:13px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
    @endforeach
</div>
@endif

{{-- Lightbox --}}
<div id="lightbox" onclick="closeLightbox()"
     style="display:none;position:fixed;inset:0;z-index:9999;
            background:rgba(0,0,0,0.92);align-items:center;justify-content:center;cursor:zoom-out">
    <img id="lightboxImg" src="" alt=""
         style="max-width:92vw;max-height:90vh;border-radius:var(--r-md);box-shadow:0 20px 60px rgba(0,0,0,0.8)">
    <div style="position:absolute;top:20px;right:24px;color:#fff;font-size:24px;cursor:pointer"
         onclick="closeLightbox()">✕</div>
</div>

@endsection

@push('scripts')
<script>
function openLightbox(src) {
    document.getElementById('lightboxImg').src = src;
    const lb = document.getElementById('lightbox');
    lb.style.display = 'flex';
}
function closeLightbox() {
    document.getElementById('lightbox').style.display = 'none';
    document.getElementById('lightboxImg').src = '';
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeLightbox();
});
</script>
@endpush