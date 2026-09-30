<?php

namespace App\Services;

use App\Models\InstagramSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramService
{
    // Instagram API "with Instagram Login" — calls go to graph.instagram.com
    // with the Instagram User access token (NOT graph.facebook.com / a Page token).
    private const GRAPH_URL = 'https://graph.instagram.com/v23.0';

    private InstagramSetting $settings;

    public function __construct(InstagramSetting $settings)
    {
        $this->settings = $settings;
    }

    public static function forTenant(int $tenantId): self
    {
        $settings = InstagramSetting::where('tenant_id', $tenantId)->firstOrFail();
        return new self($settings);
    }

    // Send DM to an Instagram user
    public function sendDm(string $recipientIgId, string $message): bool
    {
        return $this->sendDmDetailed($recipientIgId, $message)['success'];
    }

    // Same as sendDm() but returns the raw Graph API response too, so
    // callers can store the actual failure reason instead of a flat bool.
    public function sendDmDetailed(string $recipientIgId, string $message): array
    {
        $response = Http::post(self::GRAPH_URL . '/me/messages', [
            'recipient'      => ['id' => $recipientIgId],
            'message'        => ['text' => $message],
            'access_token'   => $this->settings->access_token,
        ]);

        if ($response->failed()) {
            Log::error('Instagram DM failed', [
                'tenant_id' => $this->settings->tenant_id,
                'recipient' => $recipientIgId,
                'error'     => $response->json(),
            ]);
        }

        return [
            'success' => $response->successful(),
            'status'  => $response->status(),
            'body'    => $response->json(),
        ];
    }

    // Private Reply: the only way to DM someone who commented but has not
    // messaged us in the last 24h. Addressed by comment_id, not user id.
    // Allowed once per comment, within 7 days of the comment.
    public function sendPrivateReplyDetailed(string $commentId, string $message): array
    {
        $response = Http::post(self::GRAPH_URL . '/me/messages', [
            'recipient'    => ['comment_id' => $commentId],
            'message'      => ['text' => $message],
            'access_token' => $this->settings->access_token,
        ]);

        if ($response->failed()) {
            Log::error('Instagram private reply failed', [
                'tenant_id'  => $this->settings->tenant_id,
                'comment_id' => $commentId,
                'error'      => $response->json(),
            ]);
        }

        return [
            'success' => $response->successful(),
            'status'  => $response->status(),
            'body'    => $response->json(),
        ];
    }

    // Reply to a comment on a post
    public function replyToComment(string $commentId, string $message): bool
    {
        return $this->replyToCommentDetailed($commentId, $message)['success'];
    }

    // Same as replyToComment() but returns the raw Graph API response too.
    public function replyToCommentDetailed(string $commentId, string $message): array
    {
        $response = Http::post(self::GRAPH_URL . '/' . $commentId . '/replies', [
            'message'      => $message,
            'access_token' => $this->settings->access_token,
        ]);

        if ($response->failed()) {
            Log::error('Instagram comment reply failed', [
                'tenant_id'  => $this->settings->tenant_id,
                'comment_id' => $commentId,
                'error'      => $response->json(),
            ]);
        }

        return [
            'success' => $response->successful(),
            'status'  => $response->status(),
            'body'    => $response->json(),
        ];
    }

    // Get recent posts/media for the automation post picker
    public function getRecentMedia(int $limit = 25): array
    {
        $response = Http::get(self::GRAPH_URL . '/me/media', [
            'fields'       => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp',
            'limit'        => $limit,
            'access_token' => $this->settings->access_token,
        ]);

        if ($response->failed()) {
            Log::error('Instagram media fetch failed', [
                'tenant_id' => $this->settings->tenant_id,
                'error'     => $response->json(),
            ]);
            return [];
        }

        return $response->json('data') ?? [];
    }

    // Get Instagram account info
    public function getAccountInfo(): ?array
    {
        $response = Http::get(self::GRAPH_URL . '/me', [
            'fields'       => 'user_id,username,name,followers_count,profile_picture_url,account_type',
            'access_token' => $this->settings->access_token,
        ]);

        if ($response->failed()) return null;

        $data = $response->json();

        // Older callers / views read ['id']; graph.instagram.com/me returns 'user_id'.
        if (isset($data['user_id']) && !isset($data['id'])) {
            $data['id'] = $data['user_id'];
        }

        return $data;
    }

    // Subscribe the connected Instagram Professional account to webhooks.
    public function subscribeWebhook(): bool
    {
        return $this->subscribeWebhookDetailed()['success'];
    }

    // Same as subscribeWebhook() but returns the raw Graph API response too,
    // so callers can surface the actual error instead of a flat true/false.
    public function subscribeWebhookDetailed(): array
    {
        // With Instagram Login the subscription lives on the Instagram User node
        // (/me/subscribed_apps) authorised by the Instagram User token — no Page.
        $response = Http::post(self::GRAPH_URL . '/me/subscribed_apps', [
            'subscribed_fields' => 'messages,comments',
            'access_token'      => $this->settings->access_token,
        ]);

        if ($response->failed()) {
            Log::error('Instagram webhook subscription failed', [
                'tenant_id'            => $this->settings->tenant_id,
                'instagram_account_id' => $this->settings->instagram_account_id,
                'error'                => $response->json(),
            ]);
        }

        return [
            'success' => $response->successful(),
            'status'  => $response->status(),
            'body'    => $response->json(),
        ];
    }

    // Verify token matches our stored token
    public function verifyWebhookToken(string $token): bool
    {
        return $token === $this->settings->webhook_verify_token;
    }
}
