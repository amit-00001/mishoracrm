@extends('layouts.app')
@section('title', 'Sales Enquiries')

@push('styles')
<style>
.stat-row { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:24px; }
@media(max-width:700px){ .stat-row { grid-template-columns:1fr 1fr; } }
.stat-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); padding:14px 18px; text-decoration:none; display:block; }
.stat-card.is-active { border-color:var(--accent); box-shadow:0 0 0 1px var(--accent); }
.stat-card .s-label { font-size:12px; color:var(--text-400); font-weight:600; text-transform:uppercase; letter-spacing:.04em; }
.stat-card .s-value { font-size:24px; font-weight:800; color:var(--text-100); margin-top:4px; }

.table-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.data-table { width:100%; border-collapse:collapse; }
.data-table th { padding:11px 16px; text-align:left; font-size:11.5px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.05em; border-bottom:1px solid var(--border-subtle); background:var(--bg-elevated); white-space:nowrap; }
.data-table td { padding:14px 16px; font-size:13.5px; color:var(--text-200); border-bottom:1px solid var(--border-subtle); vertical-align:top; }
.data-table tr:last-child td { border-bottom:none; }
.data-table tr:hover td { background:var(--bg-hover); }

.enq-name { font-size:14px; font-weight:700; color:var(--text-100); }
.enq-company { font-size:12px; color:var(--text-400); margin-top:2px; }
.enq-contact a { color:var(--accent); text-decoration:none; display:block; font-size:12.5px; }
.enq-msg { max-width:320px; font-size:12.5px; color:var(--text-300); line-height:1.5; white-space:pre-wrap; }

.badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:100px; font-size:11px; font-weight:700; }
.badge-green { background:var(--green-dim); color:var(--green); }
.badge-gray  { background:var(--bg-input); color:var(--text-400); }
.badge-blue  { background:var(--accent-dim); color:var(--accent); }

.action-btns { display:flex; gap:6px; flex-wrap:wrap; }
.empty-state { text-align:center; padding:56px; color:var(--text-400); font-size:14px; }

.settings-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); padding:18px 20px; margin-bottom:24px; }
.settings-card .sc-title { font-size:14px; font-weight:700; color:var(--text-100); }
.settings-card .sc-sub { font-size:12.5px; color:var(--text-400); margin:2px 0 14px; }
.settings-grid { display:grid; grid-template-columns:repeat(3,1fr) auto; gap:12px; align-items:end; }
@media(max-width:800px){ .settings-grid { grid-template-columns:1fr 1fr; } }
.settings-grid label { font-size:11.5px; font-weight:600; color:var(--text-400); display:block; margin-bottom:4px; }
.settings-grid input { width:100%; padding:8px 10px; font-size:13px; background:var(--bg-input); border:1px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); }
</style>
@endpush

@section('content')

    <div class="page-head">
        <div class="page-title">Sales Enquiries</div>
    </div>

    @if(session('success'))
    <div style="padding:12px 16px;background:var(--green-dim);border:1px solid rgba(29,158,117,.2);border-radius:var(--r-sm);font-size:13px;color:var(--green);margin-bottom:16px">
        ✓ {{ session('success') }}
    </div>
    @endif

    {{-- Sales contact details --}}
    <div class="settings-card">
        <div class="sc-title">Sales contact details</div>
        <div class="sc-sub">Shown on the public <code>/contact-sales</code> page. An email address here also receives a copy of every new enquiry.</div>
        <form action="{{ route('superadmin.contact-enquiries.settings') }}" method="POST">
            @csrf
            <div class="settings-grid">
                <div>
                    <label>Sales email</label>
                    <input type="email" name="sales_email" value="{{ old('sales_email', $salesEmail) }}" placeholder="sales@yourcompany.com">
                </div>
                <div>
                    <label>Sales phone</label>
                    <input type="text" name="sales_phone" value="{{ old('sales_phone', $salesPhone) }}" placeholder="+91 98765 43210">
                </div>
                <div>
                    <label>WhatsApp number</label>
                    <input type="text" name="sales_whatsapp" value="{{ old('sales_whatsapp', $salesWhatsapp) }}" placeholder="+91 98765 43210">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">Save</button>
            </div>
        </form>
    </div>

    {{-- Status filter / stats --}}
    <div class="stat-row">
        <a href="{{ route('superadmin.contact-enquiries.index') }}" class="stat-card {{ !$activeStatus ? 'is-active' : '' }}">
            <div class="s-label">All</div>
            <div class="s-value">{{ $counts['all'] }}</div>
        </a>
        <a href="{{ route('superadmin.contact-enquiries.index', ['status' => 'new']) }}" class="stat-card {{ $activeStatus === 'new' ? 'is-active' : '' }}">
            <div class="s-label">New</div>
            <div class="s-value">{{ $counts['new'] }}</div>
        </a>
        <a href="{{ route('superadmin.contact-enquiries.index', ['status' => 'contacted']) }}" class="stat-card {{ $activeStatus === 'contacted' ? 'is-active' : '' }}">
            <div class="s-label">Contacted</div>
            <div class="s-value">{{ $counts['contacted'] }}</div>
        </a>
        <a href="{{ route('superadmin.contact-enquiries.index', ['status' => 'closed']) }}" class="stat-card {{ $activeStatus === 'closed' ? 'is-active' : '' }}">
            <div class="s-label">Closed</div>
            <div class="s-value">{{ $counts['closed'] }}</div>
        </a>
    </div>

    {{-- Enquiries --}}
    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>From</th>
                    <th>Contact</th>
                    <th>Team</th>
                    <th>Message</th>
                    <th>Received</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($enquiries as $enquiry)
                <tr>
                    <td>
                        <div class="enq-name">{{ $enquiry->name }}</div>
                        <div class="enq-company">{{ $enquiry->company }}</div>
                    </td>
                    <td class="enq-contact">
                        <a href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a>
                        @if($enquiry->phone)<a href="tel:{{ $enquiry->phone }}">{{ $enquiry->phone }}</a>@endif
                    </td>
                    <td>{{ $enquiry->team_size ?: '—' }}</td>
                    <td><div class="enq-msg">{{ $enquiry->message ?: '—' }}</div></td>
                    <td style="white-space:nowrap;color:var(--text-400)">{{ $enquiry->created_at->format('d M Y') }}</td>
                    <td>
                        <span class="badge {{ $enquiry->status_badge }}">{{ ucfirst($enquiry->status) }}</span>
                    </td>
                    <td>
                        <div class="action-btns">
                            @if($enquiry->status !== 'contacted')
                            <form action="{{ route('superadmin.contact-enquiries.status', $enquiry) }}" method="POST">
                                @csrf
                                <input type="hidden" name="status" value="contacted">
                                <button type="submit" class="btn btn-secondary btn-sm">Mark contacted</button>
                            </form>
                            @endif
                            @if($enquiry->status !== 'closed')
                            <form action="{{ route('superadmin.contact-enquiries.status', $enquiry) }}" method="POST">
                                @csrf
                                <input type="hidden" name="status" value="closed">
                                <button type="submit" class="btn btn-secondary btn-sm">Close</button>
                            </form>
                            @endif
                            <form action="{{ route('superadmin.contact-enquiries.destroy', $enquiry) }}" method="POST"
                                  data-confirm="Delete this enquiry? This cannot be undone." data-confirm-ok="Delete">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--red)">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7">
                        <div class="empty-state">No enquiries yet.</div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:16px">{{ $enquiries->links() }}</div>

@endsection
