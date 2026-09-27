@extends('layouts.app')
@section('title', 'Time Tracking')

@push('styles')
<style>
.sub-table { width:100%; border-collapse:collapse; background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.sub-table th { padding:10px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; background:var(--bg-elevated); border-bottom:1px solid var(--border-subtle); }
.sub-table td { padding:11px 14px; border-bottom:1px solid var(--border-subtle); font-size:13.5px; color:var(--text-100); vertical-align:middle; }
.sub-table tr:last-child td { border-bottom:none; }
.sub-table tr:hover td { background:var(--bg-elevated); }
.mono { font-family:var(--mono); }
.badge-status { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11.5px; font-weight:600; }
.badge-invoiced { background:var(--accent-dim); color:var(--accent); }
.badge-pending  { background:var(--bg-elevated); color:var(--text-400); }
.field    { display:flex; flex-direction:column; gap:6px; }
.fl       { font-size:11px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.4px; }
.fi       { padding:8px 12px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:13px; outline:none; width:100%; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div class="page-title">Time Tracking</div>
        <div class="page-sub">Logged hours — convert billable time into invoices</div>
    </div>
</div>

@if(session('success'))
<div style="padding:10px 14px;background:var(--green-dim);border:1px solid rgba(52,199,89,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--green)">
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="padding:10px 14px;background:var(--red-dim);border:1px solid rgba(255,82,87,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--red)">
    {{ session('error') }}
</div>
@endif

{{-- Timer widget --}}
<div style="background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-md);padding:16px;margin-bottom:16px">
    @if($running)
    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
        <div style="width:10px;height:10px;border-radius:50%;background:var(--red);animation:pulse 1.5s infinite"></div>
        <div>
            <div style="font-size:13px;font-weight:600;color:var(--text-100)">Timer running{{ $running->task ? ' — ' . $running->task->title : '' }}{{ $running->contact ? ' (' . $running->contact->name . ')' : '' }}</div>
            <div class="mono" id="liveTimer" style="font-size:20px;font-weight:700;color:var(--accent)" data-started="{{ $running->started_at->toIso8601String() }}">00:00:00</div>
        </div>
        <form method="POST" action="{{ route('tenant.time-entries.stop', $running->id) }}" style="margin-left:auto">
            @csrf
            <button class="btn btn-primary btn-sm" type="submit">Stop Timer</button>
        </form>
    </div>
    <style>@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.3} }</style>
    <script>
    (function(){
        const el = document.getElementById('liveTimer');
        const started = new Date(el.dataset.started).getTime();
        setInterval(() => {
            const diff = Math.floor((Date.now() - started) / 1000);
            const h = String(Math.floor(diff/3600)).padStart(2,'0');
            const m = String(Math.floor((diff%3600)/60)).padStart(2,'0');
            const s = String(diff%60).padStart(2,'0');
            el.textContent = `${h}:${m}:${s}`;
        }, 1000);
    })();
    </script>
    @else
    <form method="POST" action="{{ route('tenant.time-entries.start') }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
        @csrf
        <div class="field" style="min-width:180px">
            <label class="fl">Contact (optional)</label>
            <select name="contact_id" class="fi">
                <option value="">— None —</option>
                @foreach($contacts as $c)
                <option value="{{ $c->id }}">{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-primary btn-sm" type="submit">▶ Start Timer</button>
        <span style="font-size:12px;color:var(--text-400)">Task-specific timers start from the Task page itself.</span>
    </form>
    @endif
</div>

{{-- Filters --}}
<form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:14px;align-items:center">
    <select name="contact_id" class="fi" style="width:200px" onchange="this.form.submit()">
        <option value="">All Contacts</option>
        @foreach($contacts as $c)
        <option value="{{ $c->id }}" {{ request('contact_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
        @endforeach
    </select>
    <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text-200)">
        <input type="checkbox" name="uninvoiced_only" value="1" {{ request('uninvoiced_only') ? 'checked' : '' }} onchange="this.form.submit()"/>
        Uninvoiced billable only
    </label>
</form>

{{-- Bulk convert bar --}}
<div id="bulkBar" style="display:none;align-items:center;gap:12px;padding:10px 14px;background:var(--accent-dim);border:1px solid rgba(var(--accent-rgb),.25);border-radius:var(--r-md);margin-bottom:12px">
    <span id="bulkCount" style="font-size:13px;font-weight:600;color:var(--accent)"></span>
    <button type="button" class="btn btn-primary btn-sm" onclick="convertSelected()">Convert to Invoice</button>
    <span style="font-size:11.5px;color:var(--text-400)">(sab entries ek hi Contact ke hone chahiye)</span>
</div>
<form method="POST" id="convertForm" action="{{ route('tenant.time-entries.convert-to-invoice') }}" style="display:none">@csrf <div id="convertIdsContainer"></div></form>

<div class="sub-table-wrap">
<table class="sub-table">
    <thead>
        <tr>
            <th style="width:32px"></th>
            <th>Date</th>
            <th>Staff</th>
            <th>Contact</th>
            <th>Task / Service</th>
            <th>Duration</th>
            <th>Rate</th>
            <th>Billable</th>
            <th>Status</th>
            <th style="width:130px"></th>
        </tr>
    </thead>
    <tbody>
        @forelse($entries as $e)
        <tr>
            <td>
                @if($e->is_billable && !$e->is_invoiced && $e->contact_id)
                <input type="checkbox" class="rowCheck" value="{{ $e->id }}" onchange="onCheckChange()" style="width:14px;height:14px;cursor:pointer"/>
                @endif
            </td>
            <td class="mono" data-label="Date">{{ $e->started_at->format('d M Y') }}</td>
            <td data-label="Staff">{{ $e->user?->name ?? '—' }}</td>
            <td data-label="Contact">{{ $e->contact?->name ?? '—' }}</td>
            <td data-label="Task/Service">{{ $e->task?->title ?? $e->service?->name ?? '—' }}</td>
            <td class="mono" data-label="Duration" id="durationDisplay{{ $e->id }}">{{ $e->durationHours() }}h</td>
            <td class="mono" data-label="Rate">{{ $e->hourly_rate ? '₹' . number_format($e->hourly_rate, 2) : '—' }}</td>
            <td data-label="Billable">{{ $e->is_billable ? 'Yes' : 'No' }}</td>
            <td data-label="Status">
                @if($e->is_invoiced)
                <span class="badge-status badge-invoiced">Invoiced</span>
                @else
                <span class="badge-status badge-pending">Pending</span>
                @endif
            </td>
            <td style="display:flex;gap:6px">
                @if(!$e->is_invoiced)
                <button type="button" class="btn btn-secondary btn-sm" onclick="toggleEdit({{ $e->id }})">Edit</button>
                <form method="POST" action="{{ route('tenant.time-entries.destroy', $e->id) }}" data-confirm="Delete this time entry?" data-confirm-ok="Delete">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm" type="submit" style="background:var(--red-dim);color:var(--red);border:1px solid rgba(255,82,87,.25)">Del</button>
                </form>
                @endif
            </td>
        </tr>
        @if(!$e->is_invoiced)
        <tr id="editRow{{ $e->id }}" style="display:none">
            <td colspan="10" style="background:var(--bg-elevated);padding:12px 14px">
                <form method="POST" action="{{ route('tenant.time-entries.update', $e->id) }}" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
                    @csrf @method('PUT')
                    <div>
                        <label style="font-size:11px;color:var(--text-400);display:block;margin-bottom:4px">Hours</label>
                        <input type="number" name="hours" step="0.25" min="0.25" max="24" value="{{ $e->durationHours() }}" required class="fi" style="width:90px"/>
                    </div>
                    <div>
                        <label style="font-size:11px;color:var(--text-400);display:block;margin-bottom:4px">Rate (₹/hr)</label>
                        <input type="number" name="hourly_rate" step="0.01" min="0" value="{{ $e->hourly_rate }}" class="fi" style="width:110px"/>
                    </div>
                    <label style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--text-200);padding-bottom:8px">
                        <input type="checkbox" name="is_billable" value="1" {{ $e->is_billable ? 'checked' : '' }}/> Billable
                    </label>
                    <button class="btn btn-primary btn-sm" type="submit">Save</button>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="toggleEdit({{ $e->id }})">Cancel</button>
                </form>
            </td>
        </tr>
        @endif
        @empty
        <tr>
            <td colspan="10" style="text-align:center;padding:40px;color:var(--text-400)">No time entries logged yet.</td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>

<div style="margin-top:14px">{{ $entries->links() }}</div>

<script>
function toggleEdit(id){
    const row = document.getElementById('editRow' + id);
    row.style.display = row.style.display === 'none' ? 'table-row' : 'none';
}

function checkedIds(){ return Array.from(document.querySelectorAll('.rowCheck:checked')).map(c => c.value); }

function onCheckChange(){
    const ids = checkedIds();
    const bar = document.getElementById('bulkBar');
    if(ids.length > 0){
        bar.style.display = 'flex';
        document.getElementById('bulkCount').textContent = ids.length + ' selected';
    } else {
        bar.style.display = 'none';
    }
}

function convertSelected(){
    const ids = checkedIds();
    if(!ids.length) return;

    confirmAction({
        message: 'Create a draft invoice from ' + ids.length + ' time entries?',
        ok: 'Create invoice',
        danger: false,
        onConfirm: () => {
            const container = document.getElementById('convertIdsContainer');
            container.innerHTML = '';
            ids.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                container.appendChild(input);
            });
            document.getElementById('convertForm').submit();
        },
    });
}
</script>

@endsection
