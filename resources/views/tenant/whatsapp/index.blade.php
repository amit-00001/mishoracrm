@extends('layouts.app')
@section('title', 'WhatsApp')

@push('styles')
<style>
.wa-stats { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; margin-bottom:20px; }
@media(max-width:900px) { .wa-stats { grid-template-columns:repeat(3,1fr); } }
@media(max-width:500px) { .wa-stats { grid-template-columns:repeat(2,1fr); } }

.wa-stat {
    background:var(--bg-surface); border:1px solid var(--border-default);
    border-radius:var(--r-md); padding:16px; text-align:center;
}
.wa-stat-num { font-size:24px; font-weight:800; color:var(--text-100); font-family:var(--mono); }
.wa-stat-lbl { font-size:11.5px; color:var(--text-300); margin-top:3px; }

.quick-actions { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:24px; }
@media(max-width:700px) { .quick-actions { grid-template-columns:1fr; } }

.qa-card {
    display:flex; align-items:center; gap:14px;
    padding:18px 20px; border-radius:var(--r-lg);
    border:1.5px solid var(--border-default);
    background:var(--bg-surface); text-decoration:none;
    transition:all .15s var(--ease);
}
.qa-card:hover { border-color:var(--accent); transform:translateY(-1px); background:var(--bg-hover); }
.qa-icon { width:44px; height:44px; border-radius:var(--r-md); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.qa-icon svg { width:20px; height:20px; }
.qa-label { font-size:14px; font-weight:700; color:var(--text-100); margin-bottom:2px; }
.qa-sub   { font-size:12px; color:var(--text-300); }

.recent-table { width:100%; border-collapse:collapse; }
.recent-table th { padding:10px 16px; text-align:left; font-size:11px; font-weight:600; color:var(--text-400); text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid var(--border-subtle); }
.recent-table td { padding:12px 16px; font-size:13px; color:var(--text-100); border-bottom:1px solid var(--border-subtle); }
.recent-table tr:last-child td { border-bottom:none; }
.recent-table tbody tr:hover td { background:var(--bg-elevated); }

/* ── MOBILE WHATSAPP CARDS (list view, <768px) ────────────────── */
.wa-mobile-list{display:none}
@media(max-width:768px){
    .wa-table-wrap{display:none}
    .wa-mobile-list{display:flex;flex-direction:column;gap:10px;padding:14px}
}
.wa-card{background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-md);padding:14px}
.wa-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:10px}
.wa-to{font-size:14px;font-weight:700;color:var(--text-100);line-height:1.3;word-break:break-word}
.wa-phone{font-size:11.5px;color:var(--text-400);margin-top:2px;font-family:var(--mono)}
.wa-status{font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;white-space:nowrap;flex-shrink:0}
.wa-message{font-size:12.5px;color:var(--text-200);margin-bottom:10px;padding:9px 11px;background:var(--bg-elevated);border-radius:8px;word-break:break-word}
.wa-meta{display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;font-size:11.5px;padding-top:10px;border-top:1px solid var(--border-subtle)}
.wa-meta-lbl{color:var(--text-400)}
.wa-meta-val{color:var(--text-200);font-weight:600}
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div class="page-title">
            <span style="color:#25D366">●</span> WhatsApp
        </div>
        <div class="page-sub">Send messages & manage campaigns</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('tenant.whatsapp.send') }}" class="btn btn-primary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Send Message
        </a>
    </div>
</div>

{{-- Stats --}}
<div class="wa-stats">
    <div class="wa-stat">
        <div class="wa-stat-num" style="color:#25D366">{{ $stats['total_sent'] }}</div>
        <div class="wa-stat-lbl">Total Sent</div>
    </div>
    <div class="wa-stat">
        <div class="wa-stat-num">{{ $stats['today'] }}</div>
        <div class="wa-stat-lbl">Today</div>
    </div>
    <div class="wa-stat">
        <div class="wa-stat-num">{{ $stats['this_month'] }}</div>
        <div class="wa-stat-lbl">This Month</div>
    </div>
    <div class="wa-stat">
        <div class="wa-stat-num" style="color:var(--red)">{{ $stats['failed'] }}</div>
        <div class="wa-stat-lbl">Failed</div>
    </div>
    <div class="wa-stat">
        <div class="wa-stat-num" style="color:var(--accent)">{{ $stats['templates'] }}</div>
        <div class="wa-stat-lbl">Templates</div>
    </div>
</div>

{{-- Quick actions --}}
<div class="quick-actions">
    <a href="{{ route('tenant.whatsapp.send') }}" class="qa-card">
        <div class="qa-icon" style="background:#E8F5E9;color:#25D366">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 01-.923 1.785A5.969 5.969 0 006 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337z"/></svg>
        </div>
        <div>
            <div class="qa-label">Send Message</div>
            <div class="qa-sub">Single contact pe bhejo</div>
        </div>
    </a>
    <a href="{{ route('tenant.whatsapp.bulk') }}" class="qa-card">
        <div class="qa-icon" style="background:var(--accent-dim);color:var(--accent)">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
        </div>
        <div>
            <div class="qa-label">Bulk Send</div>
            <div class="qa-sub">Multiple contacts ko bhejo</div>
        </div>
    </a>
    <a href="{{ route('tenant.whatsapp.templates') }}" class="qa-card">
        <div class="qa-icon" style="background:var(--amber-dim);color:var(--amber)">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
        </div>
        <div>
            <div class="qa-label">Templates</div>
            <div class="qa-sub">Message templates manage karo</div>
        </div>
    </a>
</div>

{{-- Recent messages --}}
<div class="card">
    <div class="card-header">
        <div class="card-title">Recent Messages</div>
        <a href="{{ route('tenant.whatsapp.logs') }}" class="btn btn-secondary btn-sm">View All →</a>
    </div>
    @if($recentLogs->isEmpty())
    <div style="padding:40px;text-align:center;color:var(--text-300);font-size:13px">
        No messages sent yet
    </div>
    @else
    <div class="wa-table-wrap" style="overflow-x:auto">
        <table class="recent-table data-table">
            <thead>
                <tr>
                    <th>To</th>
                    <th>Message</th>
                    <th>Template</th>
                    <th>Sent By</th>
                    <th>Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentLogs as $log)
                <tr>
                    <td data-label="To">
                        <div style="font-weight:600">{{ $log->to_name ?? $log->to_phone }}</div>
                        <div style="font-size:11.5px;color:var(--text-400);font-family:var(--mono)">{{ $log->to_phone }}</div>
                    </td>
                    <td style="max-width:220px" data-label="Message">
                        <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12.5px;color:var(--text-200)">
                            {{ $log->message }}
                        </div>
                    </td>
                    <td style="font-size:12px;color:var(--text-300)" data-label="Template">{{ $log->template?->name ?? '—' }}</td>
                    <td style="font-size:12.5px;color:var(--text-200)" data-label="Sent By">{{ $log->sentBy?->name ?? '—' }}</td>
                    <td style="font-size:11.5px;color:var(--text-400);font-family:var(--mono)" data-label="Time">{{ $log->created_at->diffForHumans() }}</td>
                    <td data-label="Status">
                        @php $sc = match($log->status) { 'sent'=>['green','Sent'], 'delivered'=>['green','Delivered'], 'received'=>['green','Received'], 'failed'=>['red','Failed'], 'pending'=>['amber','Pending'], default=>['text-300','—'] }; @endphp
                        <span style="background:var(--{{ $sc[0] }}-dim);color:var(--{{ $sc[0] }});font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px">
                            {{ $sc[1] }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile card list (shown only <768px, table above hides itself) --}}
    <div class="wa-mobile-list">
    @foreach($recentLogs as $log)
    @php $sc = match($log->status) { 'sent'=>['green','Sent'], 'delivered'=>['green','Delivered'], 'received'=>['green','Received'], 'failed'=>['red','Failed'], 'pending'=>['amber','Pending'], default=>['text-300','—'] }; @endphp
    <div class="wa-card">
        <div class="wa-top">
            <div style="min-width:0">
                <div class="wa-to">{{ $log->to_name ?? $log->to_phone }}</div>
                <div class="wa-phone">{{ $log->to_phone }}</div>
            </div>
            <span class="wa-status" style="background:var(--{{ $sc[0] }}-dim);color:var(--{{ $sc[0] }})">{{ $sc[1] }}</span>
        </div>
        <div class="wa-message">{{ $log->message }}</div>
        <div class="wa-meta">
            <span><span class="wa-meta-lbl">Template: </span><span class="wa-meta-val">{{ $log->template?->name ?? '—' }}</span></span>
            <span><span class="wa-meta-lbl">Sent by: </span><span class="wa-meta-val">{{ $log->sentBy?->name ?? '—' }}</span></span>
            <span style="color:var(--text-400);font-family:var(--mono)">{{ $log->created_at->diffForHumans() }}</span>
        </div>
    </div>
    @endforeach
    </div>
    @endif
</div>

@endsection