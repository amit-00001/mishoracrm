@extends('layouts.app')
@section('title', $campaign->name)

@push('styles')
<style>
.c-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; max-width:640px; margin-bottom:18px; }
.c-cell { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-md); padding:12px 14px; }
.c-cell .k { font-size:11px; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; }
.c-cell .v { font-size:14px; font-weight:600; color:var(--text-100); margin-top:3px; }
.sub-table { width:100%; border-collapse:collapse; background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.sub-table th { padding:9px 13px; text-align:left; font-size:11px; font-weight:700; color:var(--text-400); text-transform:uppercase; background:var(--bg-elevated); border-bottom:1px solid var(--border-subtle); }
.sub-table td { padding:10px 13px; border-bottom:1px solid var(--border-subtle); font-size:13px; }
.mono { font-family:var(--mono,monospace); font-weight:700; letter-spacing:1px; }
.st { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:700; }
.st.draft{background:var(--amber-dim);color:var(--amber)} .st.active{background:var(--green-dim);color:var(--green)} .st.ended{background:var(--bg-elevated);color:var(--text-400)}
@media(max-width:640px){ .c-grid{ grid-template-columns:1fr } }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.loyalty.campaigns.index') }}" style="color:var(--text-300);text-decoration:none">Campaigns</a> › {{ $campaign->name }}
        </div>
        <div class="page-title">{{ $campaign->name }} <span class="st {{ $campaign->status }}">{{ ucfirst($campaign->status) }}</span></div>
    </div>
    <div style="display:flex;gap:8px">
        @if($campaign->status === 'draft')
        <form method="POST" action="{{ route('tenant.loyalty.campaigns.launch', $campaign->id) }}" data-confirm="Launch this campaign now? Recipients and codes will be created." data-confirm-ok="Launch" data-confirm-danger="false">@csrf<button class="btn btn-primary">Launch</button></form>
        <form method="POST" action="{{ route('tenant.loyalty.campaigns.destroy', $campaign->id) }}" data-confirm="Delete this draft?" data-confirm-ok="Delete">@csrf @method('DELETE')<button class="btn btn-secondary">Delete</button></form>
        @elseif($campaign->status === 'active')
        <form method="POST" action="{{ route('tenant.loyalty.campaigns.end', $campaign->id) }}" data-confirm="End this campaign? Codes stop working." data-confirm-ok="End">@csrf<button class="btn btn-secondary">End Campaign</button></form>
        @else
        <form method="POST" action="{{ route('tenant.loyalty.campaigns.destroy', $campaign->id) }}" data-confirm="Delete this campaign and its recipient records?" data-confirm-ok="Delete">@csrf @method('DELETE')<button class="btn btn-secondary">Delete</button></form>
        @endif
    </div>
</div>

@if(session('success'))<div style="padding:10px 14px;background:var(--green-dim);border:1px solid rgba(52,199,89,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--green)">{{ session('success') }}</div>@endif
@if(session('error'))<div style="padding:10px 14px;background:var(--red-dim);border:1px solid rgba(255,82,87,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--red)">{{ session('error') }}</div>@endif

<div class="c-grid">
    <div class="c-cell"><div class="k">Reward</div><div class="v">{{ $campaign->rewardLabel() }}</div></div>
    <div class="c-cell"><div class="k">Segment</div><div class="v">{{ ucfirst($campaign->segment_type) }}</div></div>
    <div class="c-cell"><div class="k">Code</div><div class="v">{{ $campaign->code_mode === 'shared' ? $campaign->shared_code : 'Unique per customer' }}</div></div>
    <div class="c-cell"><div class="k">Expires</div><div class="v">{{ $campaign->expires_at?->format('d M Y') ?? 'No expiry' }}</div></div>
    <div class="c-cell"><div class="k">Per-customer uses</div><div class="v">{{ $campaign->usage_limit_per_customer }}</div></div>
    <div class="c-cell"><div class="k">Redeemed</div><div class="v">{{ number_format($campaign->redeemed_count) }}{{ $campaign->total_redemption_cap ? ' / ' . number_format($campaign->total_redemption_cap) : '' }}</div></div>
</div>

@if($campaign->status === 'draft')
<div style="background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-md);padding:18px 20px;max-width:640px">
    <div style="font-size:13px;color:var(--text-300)">This segment currently matches</div>
    <div style="font-size:32px;font-weight:800;color:var(--text-100);margin:4px 0">{{ number_format($previewCount) }}</div>
    <div style="font-size:12.5px;color:var(--text-400)">customers. Launching freezes this list and issues codes.</div>
</div>
@else
<div style="font-size:12px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Recipients</div>
<div class="sub-table-wrap">
<table class="sub-table">
    <thead><tr><th>Customer</th><th>Code</th><th>Sent</th><th>Redeemed</th></tr></thead>
    <tbody>
        @forelse($recipients as $r)
        <tr>
            <td>{{ $r->contact?->name ?? '—' }}</td>
            <td class="mono">{{ $r->code }}</td>
            <td>{{ $r->sent_at?->format('d M Y') ?? '—' }}</td>
            <td>{{ $r->redeemed_at ? $r->redeemed_at->format('d M Y') . ' (₹' . number_format($r->redeemed_value, 0) . ')' : '—' }}</td>
        </tr>
        @empty
        <tr><td colspan="4" style="text-align:center;padding:30px;color:var(--text-400)">No recipients.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
<div style="margin-top:14px">{{ $recipients->links() }}</div>
@endif

@endsection
