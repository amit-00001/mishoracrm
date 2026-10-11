@extends('layouts.app')
@section('title', 'WhatsApp Team Performance')

@push('styles')
<style>
.kpi-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:14px; margin-bottom:20px; }
.kpi { background:var(--bg-surface); border:1px solid var(--border-subtle); border-radius:10px; padding:16px 18px; }
.kpi-label { font-size:12px; color:var(--text-300); margin-bottom:6px; }
.kpi-value { font-size:26px; font-weight:700; color:var(--text-100); line-height:1.1; }
.kpi-sub { font-size:11.5px; color:var(--text-400); margin-top:4px; }
.range-bar { display:flex; gap:8px; flex-wrap:wrap; align-items:center; margin-bottom:20px; }
.range-chip { padding:5px 13px; border-radius:20px; font-size:12.5px; font-weight:600; border:1.5px solid var(--border-default); color:var(--text-200); text-decoration:none; }
.range-chip.active { border-color:#25D366; color:#128C7E; background:#E8F5E9; }
.filter-input { padding:6px 10px; height:34px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:13px; }
.warn { background:var(--amber-dim); border:1px solid rgba(248,184,78,.3); border-radius:var(--r-sm); padding:10px 14px; font-size:12.5px; color:var(--amber); margin-bottom:16px; }
</style>
@endpush

@php
    // 95 sec -> "1m 35s", 4000 sec -> "1h 6m"
    $fmt = function (?int $s) {
        if ($s === null) return '—';
        if ($s < 60) return $s . 's';
        if ($s < 3600) return intdiv($s, 60) . 'm ' . ($s % 60) . 's';
        return intdiv($s, 3600) . 'h ' . intdiv($s % 3600, 60) . 'm';
    };
@endphp

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.whatsapp.inbox.index') }}" style="color:var(--text-300);text-decoration:none">WhatsApp Inbox</a>
            <span style="margin:0 6px">›</span> Team performance
        </div>
        <div class="page-title">Team Performance</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('tenant.whatsapp.campaigns') }}" class="btn btn-secondary">Campaigns</a>
    </div>
</div>

<form method="GET" class="range-bar">
    @foreach(['today' => 'Today', '7d' => 'Last 7 days', '30d' => 'Last 30 days'] as $key => $label)
    <a class="range-chip {{ $range === $key ? 'active' : '' }}" href="{{ route('tenant.whatsapp.performance', ['range' => $key]) }}">{{ $label }}</a>
    @endforeach
    <input type="hidden" name="range" value="custom">
    <input type="date" name="from" class="filter-input" value="{{ $from->toDateString() }}">
    <span style="color:var(--text-300)">to</span>
    <input type="date" name="to" class="filter-input" value="{{ $to->toDateString() }}">
    <button class="btn btn-secondary btn-sm">Apply</button>
</form>

@if($capped)
<div class="warn">This period has a very large number of messages — the report covers only the first part of it. Choose a shorter period for exact numbers.</div>
@endif

<div class="kpi-row">
    <div class="kpi"><div class="kpi-label">Customers who wrote in</div><div class="kpi-value">{{ $summary['chats_in'] }}</div></div>
    <div class="kpi"><div class="kpi-label">Answered by a teammate</div><div class="kpi-value">{{ $summary['answered'] }}</div>
        <div class="kpi-sub">{{ $summary['chats_in'] ? round($summary['answered'] / $summary['chats_in'] * 100) : 0 }}% of those who wrote</div></div>
    <div class="kpi"><div class="kpi-label">Average first reply</div><div class="kpi-value">{{ $fmt($summary['avg_reply']) }}</div>
        <div class="kpi-sub">customer's message → teammate's reply</div></div>
    <div class="kpi"><div class="kpi-label">Still waiting for a reply</div><div class="kpi-value">{{ $summary['waiting'] }}</div>
        <div class="kpi-sub">no teammate has answered yet</div></div>
    <div class="kpi"><div class="kpi-label">Open, nobody assigned</div><div class="kpi-value">{{ $summary['unassigned'] }}</div><div class="kpi-sub">right now</div></div>
</div>

<div class="card">
    @if(empty($agents))
    <div style="padding:50px 20px;text-align:center;color:var(--text-300);font-size:13px">No team activity in this period.</div>
    @else
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Teammate</th>
                    <th>Messages sent</th>
                    <th>Chats replied to</th>
                    <th>Avg first reply</th>
                    <th>Open chats now</th>
                </tr>
            </thead>
            <tbody>
                @foreach($agents as $a)
                <tr>
                    <td><strong>{{ $a['name'] }}</strong></td>
                    <td>{{ $a['sent'] }}</td>
                    <td>{{ $a['chats'] }}</td>
                    <td>{{ $fmt($a['avg_reply']) }}</td>
                    <td>{{ $a['open_now'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

<div style="font-size:12px;color:var(--text-400);margin-top:12px;line-height:1.6">
    Only replies typed by a teammate count. Chatbot, automatic and bulk messages are left out. First-reply time is measured from the customer's message until the first reply from a teammate.
</div>
@endsection
