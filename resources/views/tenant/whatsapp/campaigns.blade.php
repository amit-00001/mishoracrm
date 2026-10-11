@extends('layouts.app')
@section('title', 'WhatsApp Campaigns')

@push('styles')
<style>
.pag-wrap { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-top:1px solid var(--border-subtle); font-size:13px; color:var(--text-300); }
.pag-links { display:flex; gap:4px; }
.pg-btn { padding:5px 10px; border-radius:var(--r-sm); border:1px solid var(--border-default); color:var(--text-200); text-decoration:none; font-size:13px; }
.pg-btn:hover { border-color:var(--accent); color:var(--accent); }
.pg-btn.active { background:var(--accent); border-color:var(--accent); color:#fff; }
.pg-btn.disabled { opacity:.4; pointer-events:none; }
.bar { height:6px; background:var(--bg-elevated); border-radius:3px; overflow:hidden; min-width:90px; }
.bar > i { display:block; height:100%; background:#25D366; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.whatsapp.index') }}" style="color:var(--text-300);text-decoration:none">WhatsApp</a>
            <span style="margin:0 6px">›</span> Campaigns
        </div>
        <div class="page-title">Bulk Campaigns</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('tenant.whatsapp.bulk') }}" class="btn btn-primary">New bulk send</a>
    </div>
</div>

@if($scheduled->isNotEmpty())
<div class="card" style="margin-bottom:18px">
    <div style="padding:14px 18px;border-bottom:1px solid var(--border-subtle);font-weight:600">Scheduled sends</div>
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead><tr><th>Goes out</th><th>Message</th><th>By</th><th>Recipients</th><th>Status</th><th></th></tr></thead>
            <tbody>
                @foreach($scheduled as $sc)
                <tr>
                    <td style="white-space:nowrap">{{ $sc->scheduled_for->format('d M Y, h:i A') }}</td>
                    <td style="max-width:300px">@if($sc->attachment_name)📎 @endif{{ \Illuminate\Support\Str::limit($sc->message, 70) }}</td>
                    <td>{{ $sc->creator?->name ?? '—' }}</td>
                    <td>{{ count($sc->recipients) }}</td>
                    <td>
                        @if($sc->status === 'failed')
                            <span style="color:var(--red);font-weight:600">Failed</span>
                            <div style="font-size:11.5px;color:var(--text-300);max-width:260px">{{ $sc->error }}</div>
                        @elseif($sc->status === 'processing')
                            <span style="font-weight:600">Sending…</span>
                        @else
                            <span style="font-weight:600;color:#128C7E">Waiting</span>
                        @endif
                    </td>
                    <td>
                        @if($sc->status === 'pending')
                        <form method="POST" action="{{ route('tenant.whatsapp.scheduled.cancel', $sc->id) }}" onsubmit="return confirm('Cancel this scheduled send?')">
                            @csrf
                            <button class="btn btn-secondary btn-sm">Cancel</button>
                        </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

<div class="card">
    @if($campaigns->isEmpty())
    <div style="padding:60px 20px;text-align:center;color:var(--text-300);font-size:13px">
        No bulk campaigns yet. Send one from <a href="{{ route('tenant.whatsapp.bulk') }}">Bulk Send</a> and its results will appear here.
    </div>
    @else
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Sent</th>
                    <th>Message</th>
                    <th>By</th>
                    <th>Recipients</th>
                    <th>Delivered</th>
                    <th>Failed</th>
                    <th style="width:120px">Success</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($campaigns as $c)
                @php $ok = $c->total - $c->failed; $pct = $c->total ? round($ok / $c->total * 100) : 0; @endphp
                <tr>
                    <td style="white-space:nowrap">{{ \Illuminate\Support\Carbon::parse($c->started_at)->format('d M Y, h:i A') }}</td>
                    <td style="max-width:300px">
                        @if($c->attachment)<span title="Attachment">📎</span> @endif
                        {{ \Illuminate\Support\Str::limit($c->sample, 70) }}
                    </td>
                    <td>{{ $senders[$c->sent_by] ?? '—' }}</td>
                    <td>{{ $c->total }}</td>
                    <td>{{ $c->delivered }}</td>
                    <td style="{{ $c->failed ? 'color:var(--red);font-weight:600' : '' }}">{{ $c->failed }}</td>
                    <td><div class="bar"><i style="width:{{ $pct }}%"></i></div><div style="font-size:11px;color:var(--text-300);margin-top:2px">{{ $pct }}%</div></td>
                    <td><a href="{{ route('tenant.whatsapp.campaign', $c->bulk_id) }}" class="btn btn-secondary btn-sm">Details</a></td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($campaigns->hasPages())
    <div class="pag-wrap">
        <span>Showing {{ $campaigns->firstItem() }}–{{ $campaigns->lastItem() }} of {{ $campaigns->total() }}</span>
        <div class="pag-links">
            <a class="pg-btn {{ $campaigns->onFirstPage() ? 'disabled' : '' }}" href="{{ $campaigns->previousPageUrl() }}">‹ Prev</a>
            <a class="pg-btn {{ !$campaigns->hasMorePages() ? 'disabled' : '' }}" href="{{ $campaigns->nextPageUrl() }}">Next ›</a>
        </div>
    </div>
    @endif
    @endif
</div>

<div style="font-size:12px;color:var(--text-400);margin-top:12px;line-height:1.6">
    "Delivered" counts messages WhatsApp confirmed as delivered — it is filled in only for numbers linked through the WhatsApp gateway. "Success" is every message WhatsApp accepted (not failed).
</div>
@endsection
