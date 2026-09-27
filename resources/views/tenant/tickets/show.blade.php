@extends('layouts.app')
@section('title', $ticket->subject)

@push('styles')
<style>
.tk-grid { display:grid; grid-template-columns:1fr 300px; gap:18px; align-items:start; }
@media(max-width:900px) { .tk-grid { grid-template-columns:1fr; } }
.tk-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); padding:20px; }
.msg { padding:12px 14px; border-radius:10px; margin-bottom:10px; font-size:13.5px; line-height:1.5; }
.msg.customer { background:var(--accent-dim); }
.msg.staff { background:var(--bg-elevated); }
.msg.internal { background:#FFF4E5; border:1px dashed #B36B00; }
.msg-meta { font-size:11px; color:var(--text-400); margin-bottom:4px; font-weight:600; }
.fi { padding:9px 13px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:13.5px; outline:none; width:100%; }
.fl { font-size:11px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.4px; display:block; margin-bottom:6px; }
.side-block { margin-bottom:18px; }
.badge-status { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11.5px; font-weight:600; }
.badge-open, .badge-in_progress { background:var(--accent-dim); color:var(--accent); }
.badge-resolved { background:var(--green-dim); color:var(--green); }
.badge-closed { background:var(--bg-elevated); color:var(--text-400); }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.tickets.index') }}" style="color:var(--text-300);text-decoration:none">Tickets</a>
            › {{ $ticket->ticket_number ?? '#' . $ticket->id }}
        </div>
        <div class="page-title">{{ $ticket->subject }}</div>
    </div>
    <span class="badge-status badge-{{ $ticket->status }}">{{ \App\Models\Ticket::statuses()[$ticket->status] ?? ucfirst($ticket->status) }}</span>
</div>

@if(session('success'))
<div style="padding:10px 14px;background:var(--green-dim);border:1px solid rgba(52,199,89,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--green)">
    {{ session('success') }}
</div>
@endif

<div class="tk-grid">
    <div>
        <div class="tk-card">
            @if($ticket->description)
            <div class="msg customer">
                <div class="msg-meta">{{ $ticket->contact?->name ?? 'Customer' }} · {{ $ticket->created_at->format('d M Y, h:i A') }}</div>
                {{ $ticket->description }}
            </div>
            @endif

            @foreach($ticket->replies as $r)
            <div class="msg {{ $r->is_internal_note ? 'internal' : ($r->is_customer_reply ? 'customer' : 'staff') }}">
                <div class="msg-meta">
                    {{ $r->authorLabel() }} · {{ $r->created_at->format('d M Y, h:i A') }}
                    @if($r->is_internal_note) · <strong>Internal Note</strong> @endif
                </div>
                {{ $r->body }}
            </div>
            @endforeach

            @if($ticket->attachments->isNotEmpty())
            <div style="margin:14px 0;font-size:12px;color:var(--text-400)">
                Attachments:
                @foreach($ticket->attachments as $a)
                <a href="{{ $a->url }}" target="_blank" style="color:var(--accent);margin-right:8px">{{ $a->original_name }}</a>
                @endforeach
            </div>
            @endif

            <form method="POST" action="{{ route('tenant.tickets.reply', $ticket->id) }}" style="margin-top:16px">
                @csrf
                <textarea name="body" class="fi" rows="3" placeholder="Write a reply..." required style="margin-bottom:8px"></textarea>
                <div style="display:flex;justify-content:space-between;align-items:center">
                    <label style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--text-300)">
                        <input type="checkbox" name="is_internal_note" value="1"/> Internal note (customer won't see this)
                    </label>
                    <button class="btn btn-primary btn-sm" type="submit">Send</button>
                </div>
            </form>
        </div>

        <div class="tk-card" style="margin-top:16px">
            <div class="fl" style="margin-bottom:10px">Add Attachment</div>
            <form method="POST" action="{{ route('tenant.tickets.attachments.store', $ticket->id) }}" enctype="multipart/form-data" style="display:flex;gap:8px">
                @csrf
                <input type="file" name="attachments[]" class="fi" multiple/>
                <button class="btn btn-secondary btn-sm" type="submit">Upload</button>
            </form>
        </div>
    </div>

    <div>
        <div class="tk-card side-block">
            <div class="fl">Status</div>
            <form method="POST" action="{{ route('tenant.tickets.status', $ticket->id) }}" onchange="this.submit()">
                @csrf
                <select name="status" class="fi">
                    @foreach(\App\Models\Ticket::statuses() as $val => $label)
                    <option value="{{ $val }}" {{ $ticket->status === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="tk-card side-block">
            <div class="fl">Priority</div>
            <form method="POST" action="{{ route('tenant.tickets.priority', $ticket->id) }}" onchange="this.submit()">
                @csrf
                <select name="priority" class="fi">
                    @foreach(\App\Models\Ticket::priorities() as $val => $label)
                    <option value="{{ $val }}" {{ $ticket->priority === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="tk-card side-block">
            <div class="fl">Assigned To</div>
            <form method="POST" action="{{ route('tenant.tickets.assign', $ticket->id) }}" onchange="this.submit()">
                @csrf
                <select name="assigned_to" class="fi">
                    <option value="">— Unassigned —</option>
                    @foreach($staff as $s)
                    <option value="{{ $s->id }}" {{ $ticket->assigned_to === $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="tk-card side-block">
            <div class="fl">Contact</div>
            <div style="font-size:13.5px;color:var(--text-100);font-weight:600">{{ $ticket->contact?->name ?? '—' }}</div>
            @if($ticket->contact?->phone)<div style="font-size:12.5px;color:var(--text-300)">{{ $ticket->contact->phone }}</div>@endif
            @if($ticket->contact?->email)<div style="font-size:12.5px;color:var(--text-300)">{{ $ticket->contact->email }}</div>@endif
            @if($ticket->service)<div style="font-size:12px;color:var(--text-400);margin-top:6px">Service: {{ $ticket->service->name }}</div>@endif
        </div>

        <form method="POST" action="{{ route('tenant.tickets.destroy', $ticket->id) }}" data-confirm="Delete this ticket?" data-confirm-ok="Delete">
            @csrf @method('DELETE')
            <button class="btn btn-sm" type="submit" style="width:100%;background:var(--red-dim);color:var(--red);border:1px solid rgba(255,82,87,.25)">Delete Ticket</button>
        </form>
    </div>
</div>

@endsection
