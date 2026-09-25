<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<title>Ticket — {{ $ticket->subject }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) ?: '1' }}"/>
<style>
body {
  background:var(--bg-base);
  background-image:radial-gradient(circle at 15% 0%, var(--accent-dim), transparent 40%), radial-gradient(circle at 85% 100%, var(--accent-dim), transparent 40%);
  min-height:100vh; display:flex; align-items:flex-start; justify-content:center; padding:48px 16px;
}
.bk-wrap { width:100%; max-width:620px; margin:0 auto; display:flex; flex-direction:column; gap:14px; }

.bk-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-xl); overflow:hidden; box-shadow:0 30px 60px -20px rgba(20,20,45,.22), 0 8px 24px rgba(20,20,45,.06); }
.bk-hero { position:relative; padding:28px 28px 40px; background:linear-gradient(135deg, var(--accent) 0%, var(--accent-hover) 100%); overflow:hidden; }
.bk-hero::before { content:''; position:absolute; width:220px; height:220px; border-radius:50%; background:rgba(255,255,255,.08); top:-110px; right:-60px; }
.bk-hero::after { content:''; position:absolute; width:160px; height:160px; border-radius:50%; background:rgba(255,255,255,.06); bottom:-100px; left:-40px; }
.bk-hero-row { position:relative; z-index:1; display:flex; justify-content:space-between; align-items:flex-start; gap:12px; }
.bk-hero-subject { font-size:18px; font-weight:800; color:#fff; letter-spacing:-.2px; }
.bk-hero-num { font-family:var(--mono); font-size:12px; color:rgba(255,255,255,.8); margin-top:4px; }
.bk-hero-meta { position:relative; z-index:1; font-size:12px; color:rgba(255,255,255,.8); margin-top:10px; }

.bk-body { position:relative; margin-top:-24px; background:var(--bg-surface); border-radius:22px 22px 0 0; padding:26px 26px 28px; }

.bk-flash { padding:12px 16px; border-radius:var(--r-md); font-size:13.5px; font-weight:500; }
.bk-flash.success { background:var(--green-dim); color:var(--green); border:1px solid var(--green); }
.bk-flash.error   { background:var(--red-dim); color:var(--red); border:1px solid var(--red); }
.bk-flash.info    { background:var(--accent-dim); color:var(--accent); border:1px solid var(--accent); }

.bk-badge { display:inline-flex; padding:6px 15px; border-radius:20px; font-size:11.5px; font-weight:700; letter-spacing:.2px; text-transform:uppercase; white-space:nowrap; }
.bk-badge.open, .bk-badge.in_progress { background:rgba(255,255,255,.22); color:#fff; }
.bk-badge.resolved { background:rgba(255,255,255,.22); color:#fff; }
.bk-badge.closed { background:rgba(255,255,255,.16); color:rgba(255,255,255,.85); }

.bk-section-label { font-size:11px; font-weight:700; color:var(--text-300); text-transform:uppercase; letter-spacing:.6px; display:flex; align-items:center; gap:10px; margin:22px 0 14px; }
.bk-section-label:first-child { margin-top:0; }
.bk-section-label::after { content:''; flex:1; height:1px; background:var(--border-subtle); }

.msg { padding:14px 16px; border-radius:var(--r-lg); margin-bottom:10px; font-size:13.5px; line-height:1.55; }
.msg.customer { background:var(--accent-dim); border:1px solid transparent; }
.msg.staff { background:var(--bg-elevated); border:1px solid var(--border-subtle); }
.msg-meta { font-size:11px; color:var(--text-400); margin-bottom:5px; font-weight:700; text-transform:uppercase; letter-spacing:.3px; }

.bk-input { width:100%; padding:12px 14px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-md); color:var(--text-100); font-family:var(--font); font-size:13.5px; outline:none; transition:border-color .15s, box-shadow .15s; resize:vertical; }
.bk-input:focus { border-color:var(--accent); box-shadow:0 0 0 4px var(--accent-glow); }
.bk-btn { padding:12px 24px; border-radius:var(--r-lg); border:none; background:linear-gradient(135deg,var(--accent),var(--accent-hover)); color:#fff; font-size:13.5px; font-weight:700; letter-spacing:.2px; cursor:pointer; margin-top:12px; box-shadow:0 10px 24px -4px var(--accent-glow); transition:transform .15s, box-shadow .15s; display:inline-flex; align-items:center; gap:8px; }
.bk-btn:hover { transform:translateY(-1px); box-shadow:0 14px 28px -4px var(--accent-glow); }

.bk-attach-list { display:flex; flex-wrap:wrap; gap:8px; margin-top:8px; }
.bk-attach { display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:var(--r-sm); background:var(--bg-input); border:1px solid var(--border-default); color:var(--accent); font-size:12px; font-weight:600; text-decoration:none; transition:all .15s; }
.bk-attach:hover { border-color:var(--accent); background:var(--accent-dim); }

.bk-closed-note { margin-top:18px; padding:14px 16px; border-radius:var(--r-md); background:var(--bg-elevated); font-size:12.5px; color:var(--text-300); text-align:center; }

.bk-trust { text-align:center; font-size:11.5px; color:var(--text-300); }
</style>
</head>
<body>

<div class="bk-wrap">
    @foreach(['success','error','info'] as $type)
    @if(session($type))
    <div class="bk-flash {{ $type }}">{{ session($type) }}</div>
    @endif
    @endforeach

    <div class="bk-card">
        <div class="bk-hero">
            <div class="bk-hero-row">
                <div>
                    <div class="bk-hero-subject">{{ $ticket->subject }}</div>
                    @if($ticket->ticket_number)
                    <div class="bk-hero-num">{{ $ticket->ticket_number }}</div>
                    @endif
                </div>
                <span class="bk-badge {{ $ticket->status }}">{{ \App\Models\Ticket::statuses()[$ticket->status] ?? ucfirst($ticket->status) }}</span>
            </div>
            <div class="bk-hero-meta">with {{ $ticket->tenant->name ?? '' }} · opened {{ $ticket->created_at->format('d M Y') }}</div>
        </div>

        <div class="bk-body">
            <div class="bk-section-label">Conversation</div>

            @if($ticket->description)
            <div class="msg customer">
                <div class="msg-meta">{{ $ticket->contact?->name ?? 'You' }} · {{ $ticket->created_at->format('d M Y, h:i A') }}</div>
                {{ $ticket->description }}
            </div>
            @endif

            @foreach($replies as $r)
            <div class="msg {{ $r->is_customer_reply ? 'customer' : 'staff' }}">
                <div class="msg-meta">{{ $r->authorLabel() }} · {{ $r->created_at->format('d M Y, h:i A') }}</div>
                {{ $r->body }}
            </div>
            @endforeach

            @if($ticket->attachments->isNotEmpty())
            <div class="bk-attach-list">
                @foreach($ticket->attachments as $a)
                <a href="{{ $a->url }}" target="_blank" class="bk-attach">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
                    {{ $a->original_name }}
                </a>
                @endforeach
            </div>
            @endif

            @if($ticket->status !== 'closed')
            <div class="bk-section-label">Add a Reply</div>
            <form method="POST" action="{{ route('public.support.reply', $ticket->public_token) }}">
                @csrf
                <textarea name="body" class="bk-input" rows="3" placeholder="Type your reply..." required></textarea>
                <button type="submit" class="bk-btn">
                    Send Reply
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                </button>
            </form>
            @else
            <div class="bk-closed-note">This ticket is closed. Submit a new one if you need further help.</div>
            @endif
        </div>
    </div>

    <div class="bk-trust">Secure ticket tracking</div>
</div>

</body>
</html>
