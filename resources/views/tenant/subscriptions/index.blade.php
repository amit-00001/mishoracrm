@extends('layouts.app')
@section('title', 'Subscriptions')

@push('styles')
<style>
.status-tabs { display:flex; gap:4px; flex-wrap:wrap; margin-bottom:16px; }
.s-tab { padding:7px 14px; border-radius:var(--r-sm); font-size:12.5px; font-weight:600; text-decoration:none; color:var(--text-300); border:1.5px solid transparent; transition:all .15s; }
.s-tab:hover { color:var(--text-100); background:var(--bg-elevated); }
.s-tab.active { background:var(--accent-dim); color:var(--accent); border-color:rgba(var(--accent-rgb),.25); }

.sub-table { width:100%; border-collapse:collapse; background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.sub-table th { padding:10px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; background:var(--bg-elevated); border-bottom:1px solid var(--border-subtle); }
.sub-table td { padding:11px 14px; border-bottom:1px solid var(--border-subtle); font-size:13.5px; color:var(--text-100); vertical-align:middle; }
.sub-table tr:last-child td { border-bottom:none; }
.sub-table tr:hover td { background:var(--bg-elevated); }
.mono { font-family:var(--mono); }

.badge-status { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11.5px; font-weight:600; }
.badge-active    { background:var(--green-dim); color:var(--green); }
.badge-expiring  { background:#FFF4E5; color:#B36B00; }
.badge-expired   { background:var(--red-dim); color:var(--red); }
.badge-cancelled { background:var(--bg-elevated); color:var(--text-400); }

@media(max-width:768px) {
    .sub-table { border:none; }
    .sub-table thead { display:none; }
    .sub-table tbody tr { display:block; margin-bottom:12px; border:1px solid var(--border-default); border-radius:var(--r-md); overflow:hidden; }
    .sub-table td { display:flex; align-items:center; justify-content:space-between; gap:12px; text-align:right; max-width:none !important; white-space:normal !important; }
    .sub-table td::before { content:attr(data-label); font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.4px; color:var(--text-400); text-align:left; flex-shrink:0; }
}
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div class="page-title">Subscriptions</div>
        <div class="page-sub">Track which customers have recurring/contract services and when they expire</div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('tenant.subscriptions.history') }}" class="btn btn-secondary">Reminder History</a>
        <a href="{{ route('tenant.subscriptions.create') }}" class="btn btn-primary">+ Add Subscription</a>
    </div>
</div>

@if(session('success'))
<div style="padding:10px 14px;background:var(--green-dim);border:1px solid rgba(52,199,89,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--green)">
    {{ session('success') }}
</div>
@endif

{{-- Customer reminder channel preferences (opt-in, off by default) --}}
<div style="background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-md);padding:14px 16px;margin-bottom:16px">
    <div style="font-size:13px;font-weight:700;color:var(--text-100);margin-bottom:2px">Customer Reminders</div>
    <div style="font-size:11.5px;color:var(--text-400);margin-bottom:10px">Expiring-soon alert already goes to your staff daily. Turn these on to also message the customer directly — or use the "Send Reminder" button on any row to send one right now.</div>
    <form method="POST" action="{{ route('tenant.subscriptions.preferences') }}">
        @csrf
        <div style="display:flex;gap:20px;flex-wrap:wrap;align-items:center;margin-bottom:12px">
            <label style="display:flex;align-items:center;gap:7px;font-size:13px;color:var(--text-200);cursor:pointer">
                <input type="checkbox" name="email" value="1" {{ $reminderPrefs['email'] ? 'checked' : '' }} style="width:15px;height:15px;cursor:pointer"/>
                Email the customer
            </label>
            <label style="display:flex;align-items:center;gap:7px;font-size:13px;{{ $whatsappConnected ? 'color:var(--text-200)' : 'color:var(--text-400)' }};cursor:{{ $whatsappConnected ? 'pointer' : 'not-allowed' }}">
                <input type="checkbox" name="whatsapp" value="1" {{ $reminderPrefs['whatsapp'] ? 'checked' : '' }} {{ $whatsappConnected ? '' : 'disabled' }} style="width:15px;height:15px;cursor:pointer"/>
                WhatsApp the customer
                @unless($whatsappConnected)
                <a href="{{ route('tenant.whatsapp.send') }}" style="color:var(--accent);text-decoration:none;font-size:11.5px">(Connect WhatsApp first)</a>
                @endunless
            </label>
            <label style="display:flex;align-items:center;gap:7px;font-size:13px;color:var(--text-200)">
                Remind
                <input type="number" name="days" value="{{ $reminderPrefs['days'] }}" min="1" max="90"
                       style="width:56px;padding:5px 7px;border:1.5px solid var(--border-default);border-radius:var(--r-sm);background:var(--bg-input);color:var(--text-100);font-size:13px;text-align:center"/>
                day(s) before expiry
            </label>
            <button type="button" class="btn btn-secondary btn-sm" onclick="document.getElementById('reminderTemplates').classList.toggle('show')">Customize message</button>
            <button type="submit" class="btn btn-primary btn-sm">Save</button>
        </div>

        <div id="reminderTemplates" style="display:none;flex-direction:column;gap:12px;padding-top:12px;border-top:1px solid var(--border-subtle)">
            <div style="font-size:11px;color:var(--text-400)">Placeholders: <code>@{{contact_name}}</code> <code>@{{service_name}}</code> <code>@{{tenant_name}}</code> <code>@{{expiry_date}}</code> — preview below uses sample data.</div>

            <div style="display:flex;flex-direction:column;gap:6px">
                <label style="font-size:12px;font-weight:600;color:var(--text-200);text-transform:uppercase;letter-spacing:.4px">Email Subject</label>
                <input type="text" name="email_subject" id="emailSubjectInput" value="{{ $reminderPrefs['email_subject'] }}" oninput="updatePreview()"
                       style="padding:8px 12px;border:1.5px solid var(--border-default);border-radius:var(--r-sm);background:var(--bg-input);color:var(--text-100);font-size:13px"/>
            </div>

            <div style="display:flex;flex-direction:column;gap:6px">
                <label style="font-size:12px;font-weight:600;color:var(--text-200);text-transform:uppercase;letter-spacing:.4px">Email Body <span style="font-weight:400;text-transform:none;color:var(--text-400)">(basic HTML allowed, e.g. &lt;br&gt;)</span></label>
                <textarea name="email_body" id="emailBodyInput" rows="4" oninput="updatePreview()"
                          style="padding:8px 12px;border:1.5px solid var(--border-default);border-radius:var(--r-sm);background:var(--bg-input);color:var(--text-100);font-size:13px;resize:vertical;font-family:var(--mono,monospace)">{{ $reminderPrefs['email_body'] }}</textarea>
                <div style="font-size:11px;font-weight:600;color:var(--text-400);text-transform:uppercase;letter-spacing:.4px;margin-top:2px">Preview</div>
                <div style="padding:10px 12px;background:var(--bg-elevated);border:1px dashed var(--border-default);border-radius:var(--r-sm);font-size:13px;color:var(--text-200)">
                    <div style="font-weight:700;margin-bottom:4px" id="emailSubjectPreview"></div>
                    <div id="emailBodyPreview"></div>
                </div>
                <button type="button" class="btn btn-secondary btn-sm" style="align-self:flex-start" onclick="sendTestEmail()">Send Test Email to Me</button>
            </div>

            <div style="display:flex;flex-direction:column;gap:6px">
                <label style="font-size:12px;font-weight:600;color:var(--text-200);text-transform:uppercase;letter-spacing:.4px">WhatsApp Message</label>
                <textarea name="whatsapp_body" id="whatsappBodyInput" rows="3" oninput="updatePreview()"
                          style="padding:8px 12px;border:1.5px solid var(--border-default);border-radius:var(--r-sm);background:var(--bg-input);color:var(--text-100);font-size:13px;resize:vertical;font-family:var(--mono,monospace)">{{ $reminderPrefs['whatsapp_body'] }}</textarea>
                <div style="font-size:11px;font-weight:600;color:var(--text-400);text-transform:uppercase;letter-spacing:.4px;margin-top:2px">Preview</div>
                <div style="padding:10px 12px;background:var(--bg-elevated);border:1px dashed var(--border-default);border-radius:var(--r-sm);font-size:13px;color:var(--text-200);white-space:pre-wrap" id="whatsappBodyPreview"></div>
            </div>
        </div>
    </form>

    <form method="POST" action="{{ route('tenant.subscriptions.preferences.test-email') }}" id="testEmailForm" style="display:none">
        @csrf
        <input type="hidden" name="email_subject" id="testEmailSubject"/>
        <input type="hidden" name="email_body" id="testEmailBody"/>
    </form>
</div>

<style>#reminderTemplates.show { display:flex !important; }</style>

<script>
const SAMPLE_DATA = {
    contact_name: 'Ramesh Kumar',
    service_name: 'Annual Membership',
    tenant_name: @json(auth()->user()->tenant->name ?? 'Your Business'),
    expiry_date: '25 Aug 2026',
};

function fillSample(template) {
    return String(template || '').replace(/\{\{(\w+)\}\}/g, (match, key) => SAMPLE_DATA[key] ?? match);
}

function updatePreview() {
    document.getElementById('emailSubjectPreview').textContent = fillSample(document.getElementById('emailSubjectInput').value);
    document.getElementById('emailBodyPreview').innerHTML = fillSample(document.getElementById('emailBodyInput').value);
    document.getElementById('whatsappBodyPreview').textContent = fillSample(document.getElementById('whatsappBodyInput').value);
}

function sendTestEmail() {
    document.getElementById('testEmailSubject').value = document.getElementById('emailSubjectInput').value;
    document.getElementById('testEmailBody').value = document.getElementById('emailBodyInput').value;
    document.getElementById('testEmailForm').submit();
}

updatePreview();
</script>

<div class="status-tabs">
    <a href="{{ route('tenant.subscriptions.index') }}" class="s-tab {{ $status === '' ? 'active' : '' }}">All ({{ $counts['all'] }})</a>
    <a href="{{ route('tenant.subscriptions.index', ['status'=>'active']) }}" class="s-tab {{ $status === 'active' ? 'active' : '' }}">Active ({{ $counts['active'] }})</a>
    <a href="{{ route('tenant.subscriptions.index', ['status'=>'expiring']) }}" class="s-tab {{ $status === 'expiring' ? 'active' : '' }}">Expiring Soon ({{ $counts['expiring'] }})</a>
    <a href="{{ route('tenant.subscriptions.index', ['status'=>'expired']) }}" class="s-tab {{ $status === 'expired' ? 'active' : '' }}">Expired ({{ $counts['expired'] }})</a>
    <a href="{{ route('tenant.subscriptions.index', ['status'=>'cancelled']) }}" class="s-tab {{ $status === 'cancelled' ? 'active' : '' }}">Cancelled ({{ $counts['cancelled'] }})</a>
</div>

{{-- Bulk action bar — hidden until at least one row is checked --}}
<div id="bulkBar" style="display:none;align-items:center;gap:12px;padding:10px 14px;background:var(--accent-dim);border:1px solid rgba(var(--accent-rgb),.25);border-radius:var(--r-md);margin-bottom:12px">
    <span id="bulkCount" style="font-size:13px;font-weight:600;color:var(--accent)"></span>
    <button type="button" class="btn btn-secondary btn-sm" onclick="submitBulk('bulk-renew', 'Renew all selected subscriptions and create a draft invoice for each?')">Bulk Renew</button>
    <button type="button" class="btn btn-sm" style="background:var(--red-dim);color:var(--red);border:1px solid rgba(255,82,87,.25)" onclick="submitBulk('bulk-cancel', 'Cancel all selected subscriptions?')">Bulk Cancel</button>
    <button type="button" class="btn btn-secondary btn-sm" onclick="clearSelection()" style="margin-left:auto">Clear</button>
</div>
<form method="POST" id="bulkForm" style="display:none">@csrf <div id="bulkIdsContainer"></div></form>

<div class="sub-table-wrap">
<table class="sub-table">
    <thead>
        <tr>
            <th style="width:32px"><input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" style="width:14px;height:14px;cursor:pointer"/></th>
            <th>Contact</th>
            <th>Service</th>
            <th>Start</th>
            <th>Expiry</th>
            <th>Days Left</th>
            <th>Usage</th>
            <th>Status</th>
            <th style="width:160px"></th>
        </tr>
    </thead>
    <tbody>
        @forelse($subscriptions as $s)
        @php
            $days = $s->daysUntilExpiry();
            $isExpired = $s->status === 'active' && $s->isExpired();
            $isExpiring = $s->status === 'active' && !$isExpired && $days !== null && $days <= $reminderPrefs['days'];
        @endphp
        <tr>
            <td data-label="">
                @if($s->status === 'active')
                <input type="checkbox" class="rowCheck" value="{{ $s->id }}" onchange="onRowCheckChange()" style="width:14px;height:14px;cursor:pointer"/>
                @endif
            </td>
            <td style="font-weight:600" data-label="Contact">
                {{ $s->contact?->name ?? '—' }}
                @if($s->contact?->company)<div style="font-size:11.5px;color:var(--text-400)">{{ $s->contact->company }}</div>@endif
            </td>
            <td data-label="Service">{{ $s->service?->name ?? '—' }}</td>
            <td class="mono" data-label="Start">{{ $s->starts_at?->format('d M Y') ?? '—' }}</td>
            <td class="mono" data-label="Expiry">{{ $s->expires_at?->format('d M Y') ?? '—' }}</td>
            <td class="mono" data-label="Days Left">
                @if($days === null) — @elseif($days < 0) {{ abs($days) }}d overdue @else {{ $days }}d @endif
            </td>
            <td class="mono" data-label="Usage">
                @if($s->hasQuantityTracking())
                    {{ $s->used_quantity }} of {{ $s->total_quantity }}
                    @if($s->isFullyUsed())<div style="font-size:11px;color:var(--red)">Fully used</div>@endif
                @else
                    —
                @endif
            </td>
            <td data-label="Status">
                @if($s->status === 'cancelled')
                    <span class="badge-status badge-cancelled">Cancelled</span>
                @elseif($isExpired)
                    <span class="badge-status badge-expired">Expired</span>
                @elseif($isExpiring)
                    <span class="badge-status badge-expiring">Expiring Soon</span>
                @else
                    <span class="badge-status badge-active">Active</span>
                @endif
                @if($s->auto_renew)
                <span class="badge-status" style="background:var(--accent-dim);color:var(--accent);margin-left:4px" title="Auto-renews on expiry">⟳ Auto</span>
                @endif
            </td>
            <td style="display:flex;gap:6px;flex-wrap:wrap">
                @if($s->status === 'active')
                <form method="POST" action="{{ route('tenant.subscriptions.send-reminder', $s->id) }}"
                      data-confirm="Send a reminder to {{ $s->contact?->name ?? 'this customer' }} now via Email/WhatsApp?" data-confirm-ok="Send" data-confirm-danger="false">
                    @csrf
                    <button class="btn btn-secondary btn-sm" type="submit" title="Email/WhatsApp this customer now">Send Reminder</button>
                </form>
                @if($s->hasQuantityTracking() && !$s->isFullyUsed())
                <form method="POST" action="{{ route('tenant.subscriptions.mark-used', $s->id) }}">
                    @csrf
                    <button class="btn btn-secondary btn-sm" type="submit" title="Record one unit as used">Mark 1 Used</button>
                </form>
                @endif
                <form method="POST" action="{{ route('tenant.subscriptions.renew', $s->id) }}"
                      data-confirm="Renew this subscription and create a draft invoice for {{ $s->contact?->name ?? 'this customer' }}?" data-confirm-ok="Renew" data-confirm-danger="false">
                    @csrf
                    <button class="btn btn-secondary btn-sm" type="submit">Renew</button>
                </form>
                <form method="POST" action="{{ route('tenant.subscriptions.cancel', $s->id) }}" data-confirm="Cancel this subscription?">
                    @csrf
                    <button class="btn btn-sm" type="submit" style="background:var(--red-dim);color:var(--red);border:1px solid rgba(255,82,87,.25)">Cancel</button>
                </form>
                @endif
                <a href="{{ route('tenant.subscriptions.edit', $s->id) }}" class="btn btn-secondary btn-sm">Edit</a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="9" style="text-align:center;padding:40px;color:var(--text-400)">
                No subscriptions yet. <a href="{{ route('tenant.subscriptions.create') }}" style="color:var(--accent)">Add your first subscription</a>.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>

<div style="margin-top:14px">{{ $subscriptions->links() }}</div>

<script>
function checkedIds(){
    return Array.from(document.querySelectorAll('.rowCheck:checked')).map(c => c.value);
}

function onRowCheckChange(){
    const ids = checkedIds();
    const bar = document.getElementById('bulkBar');
    if(ids.length > 0){
        bar.style.display = 'flex';
        document.getElementById('bulkCount').textContent = ids.length + ' selected';
    } else {
        bar.style.display = 'none';
    }
    const all = document.querySelectorAll('.rowCheck');
    document.getElementById('selectAll').checked = all.length > 0 && ids.length === all.length;
}

function toggleSelectAll(cb){
    document.querySelectorAll('.rowCheck').forEach(c => c.checked = cb.checked);
    onRowCheckChange();
}

function clearSelection(){
    document.querySelectorAll('.rowCheck').forEach(c => c.checked = false);
    document.getElementById('selectAll').checked = false;
    onRowCheckChange();
}

const BULK_URLS = {
    'bulk-renew': "{{ route('tenant.subscriptions.bulk-renew') }}",
    'bulk-cancel': "{{ route('tenant.subscriptions.bulk-cancel') }}",
};

function submitBulk(action, confirmMsg){
    const ids = checkedIds();
    if(!ids.length) return;

    confirmAction({
        message: confirmMsg,
        ok: action === 'bulk-cancel' ? 'Cancel subscriptions' : 'Renew',
        danger: action === 'bulk-cancel',
        onConfirm: () => {
            const form = document.getElementById('bulkForm');
            const container = document.getElementById('bulkIdsContainer');
            container.innerHTML = '';
            ids.forEach(id => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                container.appendChild(input);
            });
            form.action = BULK_URLS[action];
            form.submit();
        },
    });
}
</script>

@endsection
