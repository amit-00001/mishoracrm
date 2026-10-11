<?php

namespace App\Console\Commands;

use App\Models\ErrorLog;
use App\Models\Tenant;
use App\Models\User;
use App\Models\WhatsappScheduledBroadcast;
use App\Models\WhatsappSetting;
use App\Services\WhatsappBulkSender;
use App\Services\WhatsappChatbotService;
use App\Services\WhatsappGatewayClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

// Sends WhatsApp bulk messages whose scheduled time has come. Runs every
// minute (routes/console.php). Each item is claimed with an atomic
// pending → processing update, so two overlapping runs can never send twice.
class SendScheduledWhatsappBroadcasts extends Command
{
    protected $signature   = 'whatsapp:send-scheduled';
    protected $description = 'Send scheduled WhatsApp bulk messages that are due';

    public function handle(): int
    {
        // A run that died half-way leaves "processing" behind; do not retry it
        // (some recipients may already have the message) — mark it failed.
        WhatsappScheduledBroadcast::withoutGlobalScopes()
            ->where('status', 'processing')->where('updated_at', '<', now()->subMinutes(30))
            ->update(['status' => 'failed', 'error' => 'Interrupted while sending — check Campaigns for who received it.', 'processed_at' => now()]);

        $due = WhatsappScheduledBroadcast::withoutGlobalScopes()
            ->where('status', 'pending')->where('scheduled_for', '<=', now())
            ->orderBy('scheduled_for')->limit(20)->get();

        foreach ($due as $item) {
            $claimed = WhatsappScheduledBroadcast::withoutGlobalScopes()
                ->where('id', $item->id)->where('status', 'pending')->update(['status' => 'processing']);
            if (!$claimed) {
                continue;
            }

            try {
                $this->process($item);
            } catch (\Throwable $e) {
                $this->finish($item, 'failed', [], $e->getMessage());
                ErrorLog::capture($e);
            }
        }

        return self::SUCCESS;
    }

    private function process(WhatsappScheduledBroadcast $item): void
    {
        $tenant   = Tenant::find($item->tenant_id);
        $settings = WhatsappSetting::where('tenant_id', $item->tenant_id)->first();

        if (!$tenant || !$tenant->hasModuleEnabled('whatsapp_inbox')) {
            $this->finish($item, 'failed', [], 'The plan no longer includes scheduled sending.');
            return;
        }
        if (!$settings || !$settings->is_connected) {
            $this->finish($item, 'failed', [], 'WhatsApp was not connected at the scheduled time.');
            return;
        }

        $service = WhatsappChatbotService::forTenant($item->tenant_id);

        $metaTemplate = null;
        if ($item->meta_template) {
            foreach (WhatsappGatewayClient::approvedTemplates($settings->gateway_workspace_id ?? '') as $t) {
                if ($t['name'] . '|' . $t['language'] === $item->meta_template) {
                    $metaTemplate = $t;
                    break;
                }
            }
            if (!$metaTemplate) {
                $this->finish($item, 'failed', [], 'The chosen WhatsApp template is no longer approved.');
                return;
            }
        }

        $mediaId = $mediaType = null;
        if ($item->attachment_path) {
            $full = Storage::disk('local')->path($item->attachment_path);
            if (!is_file($full)) {
                $this->finish($item, 'failed', [], 'The attachment file is missing.');
                return;
            }
            $mediaType = WhatsappChatbotService::mediaTypeForMime($item->attachment_mime ?: 'application/octet-stream');
            $mediaId   = $service->uploadMedia($full, $item->attachment_mime ?: 'application/octet-stream');
            if (!$mediaId) {
                $this->finish($item, 'failed', [], 'Attachment upload failed: ' . ($service->lastError ?? 'unknown error'));
                return;
            }
        }

        $user = $item->created_by ? User::withoutGlobalScopes()->find($item->created_by) : null;

        $result = WhatsappBulkSender::run($service, [
            'tenant_id'       => $item->tenant_id,
            'user_id'         => $item->created_by,
            'type'            => $item->type,
            'ids'             => $item->recipients,
            'message'         => $item->message,
            'template_id'     => $item->template_id,
            'bulk_id'         => $item->bulk_id,
            'business'        => $tenant->name,
            'agent_name'      => $user?->name ?? '',
            'meta_template'   => $metaTemplate,
            'raw_params'      => $item->template_params ?? [],
            'media_id'        => $mediaId,
            'media_type'      => $mediaType,
            'attachment_name' => $item->attachment_name,
        ]);

        $this->finish($item, 'sent', $result);
    }

    private function finish(WhatsappScheduledBroadcast $item, string $status, array $result, ?string $error = null): void
    {
        WhatsappScheduledBroadcast::withoutGlobalScopes()->where('id', $item->id)->update([
            'status'        => $status,
            'sent_count'    => $result['sent'] ?? 0,
            'failed_count'  => $result['failed'] ?? 0,
            'skipped_count' => $result['skipped'] ?? 0,
            'error'         => $error,
            'processed_at'  => now(),
        ]);

        if ($item->attachment_path) {
            Storage::disk('local')->delete($item->attachment_path);
        }
    }
}
