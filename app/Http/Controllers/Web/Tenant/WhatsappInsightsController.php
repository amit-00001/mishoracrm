<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WhatsappConversation;
use App\Models\WhatsappLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

// WhatsApp Team Inbox reports: how the team handles chats, and how each bulk
// campaign did. Read-only — everything is derived from whatsapp_logs and
// whatsapp_conversations. Gated by module:whatsapp_inbox (see routes/web.php).
class WhatsappInsightsController extends Controller
{
    // Safety cap on messages scanned for one report (keeps the page fast).
    private const MAX_ROWS = 100000;

    private function tenantId(): int
    {
        return auth()->user()->tenant_id;
    }

    // ── Team performance ──────────────────────────────────────────
    public function performance(Request $request): View
    {
        [$from, $to, $range] = $this->range($request);

        $users = User::where('tenant_id', $this->tenantId())->orderBy('name')->get(['id', 'name'])->keyBy('id');

        // Walk the messages in time order, per customer number:
        //   customer writes  → they are now "waiting" (remember since when)
        //   a teammate replies → that wait ends; the gap is the first-reply time,
        //                        credited to that teammate
        // Chatbot / automatic messages have no sent_by, so they do not count as
        // a human answer.
        $perAgent = [];   // user_id => [sent, chats(set), reply_secs(list)]
        $waiting  = [];   // phone => since (Carbon)
        $chatsIn  = [];   // phones that wrote in range (set)
        $answered = [];   // phones answered by a human in range (set)
        $firstReplies = [];
        $capped = false;
        $scanned = 0;

        $rows = WhatsappLog::where('tenant_id', $this->tenantId())
            ->whereBetween('sent_at', [$from, $to])
            ->whereIn('status', ['received', 'sent', 'delivered', 'failed'])
            ->orderBy('id')
            ->select(['id', 'to_phone', 'status', 'sent_by', 'is_bulk', 'sent_at'])
            ->cursor();

        foreach ($rows as $r) {
            if (++$scanned > self::MAX_ROWS) { $capped = true; break; }

            $phone = preg_replace('/[^0-9]/', '', (string) $r->to_phone);
            if ($phone === '') continue;

            if ($r->status === 'received') {
                $chatsIn[$phone] = true;
                $waiting[$phone] ??= $r->sent_at;
                continue;
            }

            if (!$r->sent_by || $r->is_bulk || $r->status === 'failed') continue;

            $perAgent[$r->sent_by]['sent'] = ($perAgent[$r->sent_by]['sent'] ?? 0) + 1;
            $perAgent[$r->sent_by]['chats'][$phone] = true;

            if (isset($waiting[$phone])) {
                $secs = max(0, $r->sent_at->diffInSeconds($waiting[$phone], true));
                $perAgent[$r->sent_by]['replies'][] = $secs;
                $firstReplies[] = $secs;
                $answered[$phone] = true;
                unset($waiting[$phone]);
            }
        }

        // Open chats per owner right now (not limited by the date range).
        $open = WhatsappConversation::where('tenant_id', $this->tenantId())->where('status', 'open')
            ->select('assigned_to', DB::raw('COUNT(*) as n'))->groupBy('assigned_to')->pluck('n', 'assigned_to')->all();

        $agents = [];
        foreach ($users as $id => $u) {
            $a = $perAgent[$id] ?? [];
            if (!$a && empty($open[$id])) continue;
            $replies = $a['replies'] ?? [];
            $agents[] = [
                'name'      => $u->name,
                'sent'      => $a['sent'] ?? 0,
                'chats'     => count($a['chats'] ?? []),
                'avg_reply' => $replies ? (int) round(array_sum($replies) / count($replies)) : null,
                'open_now'  => (int) ($open[$id] ?? 0),
            ];
        }
        usort($agents, fn ($x, $y) => $y['sent'] <=> $x['sent']);

        $summary = [
            'chats_in'   => count($chatsIn),
            'answered'   => count($answered),
            'waiting'    => count($waiting),
            'avg_reply'  => $firstReplies ? (int) round(array_sum($firstReplies) / count($firstReplies)) : null,
            'unassigned' => (int) ($open[''] ?? 0),
        ];

        return view('tenant.whatsapp.performance', [
            'agents' => $agents,
            'summary' => $summary,
            'range' => $range,
            'from' => $from,
            'to' => $to,
            'capped' => $capped,
        ]);
    }

    private function range(Request $request): array
    {
        $range = $request->input('range', '7d');

        if ($range === 'custom' && $request->filled('from') && $request->filled('to')) {
            $from = Carbon::parse($request->input('from'))->startOfDay();
            $to   = Carbon::parse($request->input('to'))->endOfDay();
        } else {
            $days  = ['today' => 0, '7d' => 6, '30d' => 29][$range] ?? 6;
            $range = isset(['today' => 1, '7d' => 1, '30d' => 1][$range]) ? $range : '7d';
            $from  = now()->subDays($days)->startOfDay();
            $to    = now()->endOfDay();
        }

        return [$from, $to, $range];
    }

    // ── Campaigns (bulk sends) ────────────────────────────────────
    public function campaigns(): View
    {
        $campaigns = WhatsappLog::where('tenant_id', $this->tenantId())
            ->where('is_bulk', true)->whereNotNull('bulk_id')
            ->select(
                'bulk_id',
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed"),
                DB::raw("SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered"),
                DB::raw('MIN(sent_at) as started_at'),
                DB::raw('MIN(sent_by) as sent_by'),
                DB::raw('MIN(message) as sample'),
                DB::raw('MAX(attachment_name) as attachment')
            )
            ->groupBy('bulk_id')
            ->orderByDesc('started_at')
            ->paginate(20);

        $senders = User::whereIn('id', $campaigns->pluck('sent_by')->filter()->unique())->pluck('name', 'id');

        return view('tenant.whatsapp.campaigns', compact('campaigns', 'senders'));
    }

    public function campaign(Request $request, string $bulkId): View
    {
        $base = fn () => WhatsappLog::where('tenant_id', $this->tenantId())->where('is_bulk', true)->where('bulk_id', $bulkId);

        abort_unless($base()->exists(), 404);

        $total     = $base()->count();
        $failed    = $base()->where('status', 'failed')->count();
        $delivered = $base()->where('status', 'delivered')->count();
        $first     = $base()->orderBy('sent_at')->first();

        $reasons = $base()->where('status', 'failed')->whereNotNull('error_message')
            ->select('error_message', DB::raw('COUNT(*) as n'))->groupBy('error_message')
            ->orderByDesc('n')->limit(10)->get();

        // Customers who wrote back within 3 days of the campaign going out.
        $phones = $base()->pluck('to_phone')
            ->map(fn ($p) => preg_replace('/[^0-9]/', '', (string) $p))->filter()->unique()->flip();
        $replied = 0;
        if ($phones->isNotEmpty() && $first?->sent_at) {
            $seen = [];
            WhatsappLog::where('tenant_id', $this->tenantId())->where('status', 'received')
                ->whereBetween('sent_at', [$first->sent_at, $first->sent_at->copy()->addDays(3)])
                ->select('to_phone')->cursor()
                ->each(function ($r) use ($phones, &$seen) {
                    $p = preg_replace('/[^0-9]/', '', (string) $r->to_phone);
                    if (isset($phones[$p])) $seen[$p] = true;
                });
            $replied = count($seen);
        }

        $status = $request->input('status');
        $rows = $base()->with(['lead:id,name', 'contact:id,name'])
            ->when(in_array($status, ['sent', 'delivered', 'failed'], true), fn ($q) => $q->where('status', $status))
            ->orderBy('id')->paginate(50)->withQueryString();

        return view('tenant.whatsapp.campaign-show', [
            'bulkId'    => $bulkId,
            'first'     => $first,
            'sender'    => $first?->sent_by ? User::find($first->sent_by)?->name : null,
            'stats'     => ['total' => $total, 'failed' => $failed, 'delivered' => $delivered, 'accepted' => $total - $failed, 'replied' => $replied],
            'reasons'   => $reasons,
            'rows'      => $rows,
            'status'    => $status,
        ]);
    }
}
