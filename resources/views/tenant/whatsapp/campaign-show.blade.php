@extends('layouts.app')
@section('title', 'WhatsApp Campaign')

@push('styles')
<style>
.kpi-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:14px; margin-bottom:20px; }
.kpi { background:var(--bg-surface); border:1px solid var(--border-subtle); border-radius:10px; padding:16px 18px; }
.kpi-label { font-size:12px; color:var(--text-300); margin-bottom:6px; }
.kpi-value { font-size:26px; font-weight:700; color:var(--text-100); line-height:1.1; }
.kpi-sub { font-size:11.5px; color:var(--text-400); margin-top:4px; }
.pag-wrap { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-top:1px solid var(--border-subtle); font-size:13px; color:var(--text-300); }
.pag-links { display:flex; gap:4px; }
.pg-btn { padding:5px 10px; border-radius:var(--r-sm); border:1px solid var(--border-default); color:var(--text-200); text-decoration:none; font-size:13px; }
.pg-btn.disabled { opacity:.4; pointer-events:none; }
.chip { padding:5px 13px; border-radius:20px; font-size:12.5px; font-weight:600; border:1.5px solid var(--border-default); color:var(--text-200); text-decoration:none; }
.chip.active { border-color:#25D366; color:#128C7E; background:#E8F5E9; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.whatsapp.campaigns') }}" style="color:var(--text-300);text-decoration:none">Campaigns</a>
            <span style="margin:0 6px">›</span> {{ $first->sent_at?->format('d M Y, h:i A') }}
        </div>
        <div class="page-title">Campaign details</div>
        <div style="font-size:12.5px;color:var(--text-300);margin-top:4px">Sent by {{ $sender ?? '—' }}</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('tenant.whatsapp.campaigns') }}" class="btn btn-ghost">Back</a>
    </div>
</div>

<div class="card" style="padding:14px 18px;margin-bottom:18px;font-size:13.5px;white-space:pre-wrap;word-break:break-word">
    @if($first->attachment_name)<div style="font-weight:600;margin-bottom:4px">📎 {{ $first->attachment_name }}</div>@endif{{ $first->message }}
    <div style="font-size:11.5px;color:var(--text-400);margin-top:8px;white-space:normal">Example — the text of the first recipient; names and other fields were filled in for each person.</div>
</div>

<div class="kpi-row">
    <div class="kpi"><div class="kpi-label">Recipients</div><div class="kpi-value">{{ $stats['total'] }}</div></div>
    <div class="kpi"><div class="kpi-label">Accepted by WhatsApp</div><div class="kpi-value">{{ $stats['accepted'] }}</div>
        <div class="kpi-sub">{{ $stats['total'] ? round($stats['accepted'] / $stats['total'] * 100) : 0 }}%</div></div>
    <div class="kpi"><div class="kpi-label">Delivered</div><div class="kpi-value">{{ $stats['delivered'] }}</div></div>
    <div class="kpi"><div class="kpi-label">Failed</div><div class="kpi-value" style="{{ $stats['failed'] ? 'color:var(--red)' : '' }}">{{ $stats['failed'] }}</div></div>
    <div class="kpi"><div class="kpi-label">Replied within 3 days</div><div class="kpi-value">{{ $stats['replied'] }}</div>
        <div class="kpi-sub">{{ $stats['total'] ? round($stats['replied'] / $stats['total'] * 100) : 0 }}% of recipients</div></div>
</div>

@if($reasons->isNotEmpty())
<div class="card" style="padding:16px 18px;margin-bottom:18px">
    <div style="font-weight:600;margin-bottom:8px">Why messages failed</div>
    @foreach($reasons as $r)
    <div style="display:flex;justify-content:space-between;gap:12px;font-size:13px;padding:5px 0;border-bottom:1px solid var(--border-subtle)">
        <span style="word-break:break-word">{{ $r->error_message }}</span><b>{{ $r->n }}</b>
    </div>
    @endforeach
</div>
@endif

<div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap">
    @foreach(['' => 'All', 'sent' => 'Sent', 'delivered' => 'Delivered', 'failed' => 'Failed'] as $key => $label)
    <a class="chip {{ ($status ?? '') === (string) $key ? 'active' : '' }}" href="{{ route('tenant.whatsapp.campaign', array_filter(['bulkId' => $bulkId, 'status' => $key])) }}">{{ $label }}</a>
    @endforeach
</div>

<div class="card">
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead><tr><th>Recipient</th><th>Phone</th><th>Status</th><th>Time</th><th>Problem</th></tr></thead>
            <tbody>
                @forelse($rows as $row)
                <tr>
                    <td>{{ $row->to_name ?: $row->lead?->name ?: $row->contact?->name ?: '—' }}</td>
                    <td>{{ $row->to_phone }}</td>
                    <td>
                        <span style="font-weight:600;color:{{ $row->status === 'failed' ? 'var(--red)' : ($row->status === 'delivered' ? '#128C7E' : 'var(--text-200)') }}">{{ ucfirst($row->status) }}</span>
                    </td>
                    <td style="white-space:nowrap">{{ $row->sent_at?->format('d M, h:i A') }}</td>
                    <td style="max-width:320px;word-break:break-word;color:var(--text-300)">{{ $row->error_message }}</td>
                </tr>
                @empty
                <tr><td colspan="5" style="padding:30px;text-align:center;color:var(--text-300)">Nothing to show for this filter.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($rows->hasPages())
    <div class="pag-wrap">
        <span>Showing {{ $rows->firstItem() }}–{{ $rows->lastItem() }} of {{ $rows->total() }}</span>
        <div class="pag-links">
            <a class="pg-btn {{ $rows->onFirstPage() ? 'disabled' : '' }}" href="{{ $rows->previousPageUrl() }}">‹ Prev</a>
            <a class="pg-btn {{ !$rows->hasMorePages() ? 'disabled' : '' }}" href="{{ $rows->nextPageUrl() }}">Next ›</a>
        </div>
    </div>
    @endif
</div>
@endsection
