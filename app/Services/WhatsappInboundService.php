<?php

namespace App\Services;

use App\Models\Quotation;
use App\Models\WhatsappLog;
use App\Models\WhatsappSetting;
use Illuminate\Support\Facades\Log;

// What happens to a customer's inbound WhatsApp message once it has been
// parsed. Shared by the direct-Meta webhook (WhatsappWebhookController) and
// the WhatsApp Gateway webhook (WhatsappGatewayWebhookController), so a
// message behaves identically whichever way it reached us.
class WhatsappInboundService
{
    // $messageText is the typed text, or the title of a tapped reply
    // button / list row. $buttonId is set only for those taps.
    public function handle(WhatsappSetting $setting, string $waId, string $messageType, ?string $messageText, ?string $buttonId, ?string $contactName): void
    {
        // Log incoming message
        try {
            WhatsappLog::create([
                'tenant_id' => $setting->tenant_id,
                'to_phone'  => $waId,
                'to_name'   => $contactName,
                'message'   => $messageText ?? "[{$messageType}]",
                'status'    => 'received',
                'sent_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('WA log create failed', ['error' => $e->getMessage()]);
        }

        // A tap on a quotation Accept/Reject button sent from QuotationController@sendWhatsapp —
        // handled directly here (not via the chatbot flow engine) since it acts on the
        // quotation itself rather than replying with a message.
        if ($buttonId && (str_starts_with($buttonId, 'qacc_') || str_starts_with($buttonId, 'qrej_'))) {
            $this->handleQuotationResponse($setting, $waId, $buttonId);
            return;
        }

        // Inbound message handling — the chatbot service runs loyalty's built-in
        // replies first, then the tenant's own flows.
        if ($messageText) {
            try {
                (new WhatsappChatbotService($setting))->handleIncomingMessage($waId, $messageText, $contactName, $buttonId);
            } catch (\Throwable $e) {
                Log::error('WhatsApp inbound processing failed', ['error' => $e->getMessage()]);
            }
        }
    }

    // Mirrors the accept/reject rules in Public\QuotationController — no e-sign
    // is possible from a WhatsApp button tap, so acceptance is recorded with the
    // contact's own name as the signed name instead of a captured signature.
    private function handleQuotationResponse(WhatsappSetting $setting, string $waId, string $buttonId): void
    {
        $accept = str_starts_with($buttonId, 'qacc_');
        $token  = substr($buttonId, 5);

        $service = new WhatsappChatbotService($setting);

        $quotation = Quotation::withoutGlobalScope('tenant')
            ->where('public_token', $token)
            ->where('tenant_id', $setting->tenant_id)
            ->first();

        if (!$quotation) {
            $service->sendMessage($waId, 'Sorry, this quotation could not be found.');
            return;
        }

        if ($quotation->hasCustomerResponded()) {
            $service->sendMessage($waId, 'This quotation has already been responded to.');
            return;
        }

        if ($quotation->isExpired()) {
            $service->sendMessage($waId, 'This quotation has expired and can no longer be accepted.');
            return;
        }

        if ($accept) {
            $quotation->update([
                'status'                => 'accepted',
                'signed_name'           => $quotation->contact?->name ?: 'Accepted via WhatsApp',
                'customer_responded_at' => now(),
            ]);
            QuotationService::accept($quotation);
            $service->sendMessage($waId, "Thank you! Quotation {$quotation->number} has been accepted.");
        } else {
            $quotation->update([
                'status'                => 'rejected',
                'customer_responded_at' => now(),
            ]);
            $service->sendMessage($waId, "Quotation {$quotation->number} has been marked as rejected.");
        }
    }
}
