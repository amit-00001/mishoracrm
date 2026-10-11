<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\WhatsappLog;
use App\Models\WhatsappOptOut;
use App\Models\WhatsappTemplate;

// Sends one bulk WhatsApp message to many leads / contacts. Used both by the
// "send now" form (WhatsappController::sendBulk) and by scheduled sends
// (SendScheduledWhatsappBroadcasts), so the two behave identically.
//
// Numbers that opted out of broadcasts are skipped, not failed.
class WhatsappBulkSender
{
    // $o keys:
    //   tenant_id, user_id (nullable), type ('leads'|'contacts'), ids (array),
    //   message, template_id (nullable), bulk_id,
    //   business, agent_name  (for the {{business}} / {{agent_name}} variables),
    //   meta_template (normalised template array or null), raw_params (array or null),
    //   media_id / media_type / attachment_name (already uploaded, or null)
    // Returns ['sent' => n, 'failed' => n, 'skipped' => n].
    public static function run(WhatsappChatbotService $service, array $o): array
    {
        $tenantId = (int) $o['tenant_id'];
        $optedOut = WhatsappOptOut::setFor($tenantId);
        $model    = $o['type'] === 'leads' ? Lead::class : Contact::class;
        $today    = now()->format('d M Y');

        $sent = $failed = $skipped = 0;

        foreach ($o['ids'] as $id) {
            $record = $model::withoutGlobalScopes()->where('tenant_id', $tenantId)->find($id);

            if (!$record || !$record->phone) {
                continue;
            }

            if (isset($optedOut[WhatsappOptOut::digits($record->phone)])) {
                $skipped++;
                continue;
            }

            $waId = WhatsappOptOut::digits($record->phone);

            $variables = [
                'name'       => $record->name ?? '',
                'company'    => $record->company ?? '',
                'phone'      => $record->phone ?? '',
                'email'      => $record->email ?? '',
                'amount'     => $record->lead_value ?? '',
                'date'       => $today,
                'business'   => $o['business'] ?? '',
                'agent_name' => $o['agent_name'] ?? '',
            ];
            $message = WhatsappTemplate::substituteVariables($o['message'], $variables);

            $error = null;
            if (!empty($o['meta_template'])) {
                // Template fields take the same {{variables}}, filled per recipient.
                $params  = array_map(fn ($v) => WhatsappTemplate::substituteVariables($v, $variables), $o['raw_params'] ?? []);
                $ok      = $service->sendTemplate($waId, $o['meta_template']['name'], $o['meta_template']['language'], $params);
                $error   = $ok ? null : ($service->lastError ?? 'WhatsApp API rejected the template message.');
                $message = WhatsappGatewayClient::renderTemplateBody($o['meta_template']['body'], $params);
            } elseif (!empty($o['media_id'])) {
                $ok    = $service->sendMediaMessage($waId, $o['media_id'], $o['media_type'], $message, $o['attachment_name'] ?? null);
                $error = $ok ? null : ($service->lastError ?? 'WhatsApp API rejected the media message.');
            } else {
                $ok    = $service->sendMessage($waId, $message);
                $error = $ok ? null : ($service->lastError ?? 'WhatsApp API rejected the message.');
            }

            $ok ? $sent++ : $failed++;

            WhatsappLog::create([
                'tenant_id'       => $tenantId,
                'template_id'     => $o['template_id'] ?? null,
                'lead_id'         => $o['type'] === 'leads' ? $id : null,
                'contact_id'      => $o['type'] === 'contacts' ? $id : null,
                'sent_by'         => $o['user_id'] ?? null,
                'to_phone'        => $record->phone,
                'to_name'         => $record->name,
                'message'         => $message,
                'status'          => $ok ? 'sent' : 'failed',
                'error_message'   => $error,
                'media_type'      => $o['media_type'] ?? null,
                'media_id'        => $o['media_id'] ?? null,
                'attachment_name' => $o['attachment_name'] ?? null,
                'wamid'           => $ok ? $service->lastMessageId : null,
                'is_bulk'         => true,
                'bulk_id'         => $o['bulk_id'],
                'sent_at'         => now(),
            ]);
        }

        return ['sent' => $sent, 'failed' => $failed, 'skipped' => $skipped];
    }
}
