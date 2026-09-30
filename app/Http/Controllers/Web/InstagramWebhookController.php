<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ErrorLog;
use App\Models\InstagramAutomation;
use App\Models\InstagramChatbotFlow;
use App\Models\InstagramLog;
use App\Models\InstagramSetting;
use App\Models\PlatformSetting;
use App\Services\InstagramService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class InstagramWebhookController extends Controller
{
    // ── GET: Meta webhook verification ───────────────────────────
    public function verify(Request $request)
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $matched = $token && InstagramSetting::where('webhook_verify_token', $token)->exists();

        // Safe audit trail — records that a verification handshake happened and
        // whether it matched, WITHOUT ever storing the token value itself.
        $this->auditReceipt($request, [
            'phase'        => 'GET verify',
            'hub_mode'     => $mode,
            'token_given'  => $token ? 'yes' : 'no',
            'token_match'  => $matched ? 'yes' : 'no',
            'http_status'  => $mode === 'subscribe' && $matched ? 200 : 403,
        ]);

        if ($mode !== 'subscribe') {
            return response('Invalid mode', 403);
        }

        if (!$matched) {
            return response('Token mismatch', 403);
        }

        return response($challenge, 200)->header('Content-Type', 'text/plain');
    }

    // ── POST: Incoming Meta events ────────────────────────────────
    public function handle(Request $request): Response
    {
        $raw     = $request->getContent();
        $payload = $request->json()->all() ?: $request->all();

        // ── X-Hub-Signature-256 validation (HMAC-SHA256 of the raw body with
        //    the Meta app secret). We validate and record the result; a request
        //    is only DROPPED when a secret is configured, a signature header is
        //    present, and it does not match — genuine Meta traffic always sends
        //    a valid header, so this never rejects real events.
        $sigHeader = $request->header('X-Hub-Signature-256', '');
        $secret    = PlatformSetting::get('meta_ig_app_secret') ?: PlatformSetting::get('meta_app_secret');
        $sigState  = 'skipped (no secret configured)';
        $sigValid  = true;

        if ($secret && $sigHeader) {
            $expected = 'sha256=' . hash_hmac('sha256', $raw, $secret);
            $sigValid = hash_equals($expected, $sigHeader);
            $sigState = $sigValid ? 'valid' : 'INVALID';
        } elseif ($secret && !$sigHeader) {
            $sigState = 'missing header';
        }

        $summary = $this->safeSummary($payload);

        $this->auditReceipt($request, array_merge([
            'phase'       => 'POST event',
            'signature'   => $sigState,
            'sig_present' => $sigHeader ? 'yes' : 'no',
            'http_status' => 200,
        ], $summary));

        if ($secret && $sigHeader && !$sigValid) {
            // Acknowledge so Meta doesn't retry a forged/misconfigured caller,
            // but do not process it.
            return response('EVENT_RECEIVED', 200);
        }

        if (($payload['object'] ?? '') !== 'instagram') {
            return response('ok', 200);
        }

        foreach ($payload['entry'] ?? [] as $entry) {
            // Instagram Login exposes two IDs for the same account (user_id /
            // IGSID and the app-scoped id). Meta keys entry.id — and the
            // recipient.id inside DM events — on either shape depending on the
            // event, so gather every candidate and match a tenant on any of
            // them (we persist both in instagram_account_id + page_id).
            $candidates = array_values(array_unique(array_filter(array_merge(
                [$entry['id'] ?? null],
                array_map(fn ($m) => $m['recipient']['id'] ?? null, $entry['messaging'] ?? []),
                array_map(fn ($c) => $c['value']['recipient']['id'] ?? null, $entry['changes'] ?? []),
            ), fn ($v) => $v !== null && $v !== '')));

            $setting = $candidates ? InstagramSetting::where(function ($q) use ($candidates) {
                $q->whereIn('instagram_account_id', $candidates)
                  ->orWhereIn('page_id', $candidates);
            })->first() : null;

            if (!$setting) {
                // Meta reached us but no tenant owns this account — record it so
                // it is not indistinguishable from "nothing happened".
                $this->auditReceipt($request, [
                    'phase'            => 'tenant lookup',
                    'result'           => 'NO MATCH',
                    'entry_id'         => $entry['id'] ?? null,
                    'candidate_ids'    => implode(',', $candidates),
                    'known_account_ids'=> InstagramSetting::query()->pluck('instagram_account_id')->implode(','),
                ]);
                continue;
            }

            InstagramLog::create([
                'tenant_id'         => $setting->tenant_id,
                'event_type'        => 'webhook_received',
                'instagram_user_id' => $entry['id'] ?? null,
                'status'            => 'success',
                'incoming_text'     => 'fields=' . collect($entry['changes'] ?? [])->pluck('field')->implode(',')
                                       . ' messaging=' . count($entry['messaging'] ?? []),
            ]);

            // Handle messaging (DMs) — the standard Messenger-style shape
            foreach ($entry['messaging'] ?? [] as $messaging) {
                $this->handleDm($setting, $messaging);
            }

            // Handle changes: comments, and (defensively) DMs delivered under
            // the changes[] shape instead of messaging[].
            foreach ($entry['changes'] ?? [] as $change) {
                $field = $change['field'] ?? '';
                $value = $change['value'] ?? [];

                if ($field === 'comments') {
                    $this->handleComment($setting, $value);
                } elseif ($field === 'messages' && isset($value['sender']['id'], $value['message'])) {
                    $this->handleDm($setting, $value);
                }
            }
        }

        return response('EVENT_RECEIVED', 200);
    }

    private function handleDm(InstagramSetting $setting, array $messaging): void
    {
        $senderId   = $messaging['sender']['id'] ?? null;
        $messageText = $messaging['message']['text'] ?? null;

        if (!$senderId || !$messageText) return;
        if (!empty($messaging['message']['is_echo'])) return;   // our own outbound copy
        if ($senderId === $setting->instagram_account_id) return; // own messages
        if ($senderId === $setting->page_id) return;             // own messages (secondary id)

        // Log incoming DM
        $log = InstagramLog::create([
            'tenant_id'          => $setting->tenant_id,
            'event_type'         => 'dm_received',
            'instagram_user_id'  => $senderId,
            'incoming_text'      => $messageText,
            'raw_payload'        => $messaging,
            'status'             => 'success',
        ]);

        // Check automations first (DM keyword)
        $automations = InstagramAutomation::where('tenant_id', $setting->tenant_id)
            ->where('is_active', true)
            ->where('trigger_type', 'dm_keyword')
            ->get();

        foreach ($automations as $automation) {
            if (!$automation->matchesDm($messageText)) continue;

            $this->executeAutomation($setting, $automation, $senderId, null, $messageText, $log);
            return;
        }

        // Then check chatbot flows
        $flows = InstagramChatbotFlow::where('tenant_id', $setting->tenant_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $matched = null;
        foreach ($flows as $flow) {
            if (!$flow->is_default && $flow->matches($messageText)) {
                $matched = $flow;
                break;
            }
        }
        if (!$matched) {
            $matched = $flows->firstWhere('is_default', true);
        }

        if (!$matched) return;

        $matched->incrementTriggered();
        $log->update(['chatbot_flow_id' => $matched->id, 'event_type' => 'chatbot_triggered']);

        try {
            $service = new InstagramService($setting);
            $result  = $service->sendDmDetailed($senderId, $matched->response_message);
            $log->update([
                'outgoing_text' => $matched->response_message,
                'status'        => $result['success'] ? 'success' : 'failed',
                'error_message' => $result['success'] ? null : json_encode($result['body']),
            ]);
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }
    }

    private function handleComment(InstagramSetting $setting, array $value): void
    {
        $commentId   = $value['id'] ?? null;
        $commentText = $value['text'] ?? null;
        $postId      = $value['media']['id'] ?? null;
        $fromId      = $value['from']['id'] ?? null;

        if (!$commentId || !$commentText) return;
        if ($fromId && in_array($fromId, [$setting->instagram_account_id, $setting->page_id], true)) return; // own comments

        $log = InstagramLog::create([
            'tenant_id'         => $setting->tenant_id,
            'event_type'        => 'comment',
            'instagram_user_id' => $fromId,
            'post_id'           => $postId,
            'comment_id'        => $commentId,
            'incoming_text'     => $commentText,
            'raw_payload'       => $value,
            'status'            => 'success',
        ]);

        // Find matching automation
        $automations = InstagramAutomation::where('tenant_id', $setting->tenant_id)
            ->whereIn('trigger_type', ['any_post_comment', 'specific_post_comment'])
            ->get();

        $reasons = [];

        foreach ($automations as $automation) {
            if (!$automation->is_active) {
                $reasons[] = "'{$automation->name}': inactive";
                continue;
            }
            if ($automation->trigger_type === 'specific_post_comment'
                && trim((string) $automation->post_id) !== trim((string) $postId)) {
                $reasons[] = "'{$automation->name}': post_id mismatch (expected {$automation->post_id}, comment was on " . ($postId ?? 'unknown') . ')';
                continue;
            }
            if (!$automation->matchesComment($commentText)) {
                $reasons[] = "'{$automation->name}': keyword didn't match \"{$commentText}\" (keywords: " . implode(',', $automation->trigger_keywords ?? []) . ')';
                continue;
            }

            $this->executeAutomation($setting, $automation, $fromId, $commentId, $commentText, $log);
            return; // only first matching automation
        }

        $log->update([
            'status'        => 'skipped',
            'error_message' => $reasons ? implode(' | ', $reasons) : 'No comment automation configured for this tenant.',
        ]);
    }

    private function executeAutomation(
        InstagramSetting $setting,
        InstagramAutomation $automation,
        string $userId,
        ?string $commentId,
        string $text,
        InstagramLog $log
    ): void {
        $automation->incrementTriggered();
        $log->update(['automation_id' => $automation->id, 'event_type' => 'automation_triggered']);

        try {
            $service = new InstagramService($setting);

            if ($automation->action_type === 'send_dm' && $automation->dm_message) {
                // Comment-triggered → Private Reply (by comment_id); DM-triggered → normal DM.
                $result = $commentId
                    ? $service->sendPrivateReplyDetailed($commentId, $automation->dm_message)
                    : $service->sendDmDetailed($userId, $automation->dm_message);
                $log->update([
                    'outgoing_text' => $automation->dm_message,
                    'status'        => $result['success'] ? 'success' : 'failed',
                    'error_message' => $result['success'] ? null : json_encode($result['body']),
                ]);
            }

            if ($automation->action_type === 'reply_comment' && $commentId && $automation->comment_reply) {
                $result = $service->replyToCommentDetailed($commentId, $automation->comment_reply);
                $log->update([
                    'outgoing_text' => $automation->comment_reply,
                    'status'        => $result['success'] ? 'success' : 'failed',
                    'error_message' => $result['success'] ? null : json_encode($result['body']),
                ]);
            }
        } catch (\Throwable $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            Log::error('Instagram automation execution failed', ['error' => $e->getMessage()]);
        }
    }

    // ── Safe, non-sensitive receipt written to Superadmin → Error Logs ──
    //    No tokens, no secrets, no verify tokens, no OAuth codes, and no raw
    //    private message payloads — only routing/shape metadata.
    private function auditReceipt(Request $request, array $context): void
    {
        ErrorLog::create([
            'tenant_id'       => null,
            'exception_class' => 'InstagramWebhook',
            'http_status'     => $context['http_status'] ?? 200,
            'message'         => 'Instagram webhook: ' . ($context['phase'] ?? 'event'),
            'url'             => $request->path(),
            'method'          => $request->method(),
            'request_data'    => $context,
            'ip_address'      => $request->ip(),
            'user_agent'      => mb_substr($request->userAgent() ?? '', 0, 255),
            'created_at'      => now(),
        ]);
    }

    // ── Reduce a webhook payload to a safe summary (shape only) ─────────
    private function safeSummary(array $payload): array
    {
        $entries = [];

        foreach ($payload['entry'] ?? [] as $entry) {
            $msgs = [];
            foreach ($entry['messaging'] ?? [] as $m) {
                $msgs[] = [
                    'has_sender'    => isset($m['sender']['id']),
                    'has_recipient' => isset($m['recipient']['id']),
                    'is_echo'       => !empty($m['message']['is_echo']),
                    'text_len'      => mb_strlen($m['message']['text'] ?? ''),
                ];
            }

            $entries[] = [
                'id'              => $entry['id'] ?? null,
                'change_fields'   => collect($entry['changes'] ?? [])->pluck('field')->all(),
                'messaging_count' => count($entry['messaging'] ?? []),
                'messaging'       => $msgs,
            ];
        }

        return [
            'object'      => $payload['object'] ?? null,
            'entry_count' => count($payload['entry'] ?? []),
            'entries'     => $entries,
        ];
    }
}
