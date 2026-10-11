<?php

namespace App\Services;

use App\Models\ErrorLog;
use App\Models\Quotation;
use App\Models\WhatsappConversation;
use App\Models\WhatsappLog;
use App\Models\WhatsappSetting;
use Illuminate\Support\Facades\DB;
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
            $this->isolated(fn () => WhatsappLog::create([
                'tenant_id' => $setting->tenant_id,
                'to_phone'  => $waId,
                'to_name'   => $contactName,
                'message'   => $messageText ?? "[{$messageType}]",
                'status'    => 'received',
                'sent_at'   => now(),
            ]));
        } catch (\Throwable $e) {
            Log::error('WA log create failed', ['error' => $e->getMessage()]);
        }

        // Team Inbox thread — kept for every tenant (cheap) so history is already
        // there the day the WhatsApp Team Inbox module is switched on.
        try {
            $this->isolated(fn () => WhatsappConversation::touchFor(
                $setting->tenant_id, $waId, $contactName, $messageText ?? "[{$messageType}]", true
            ));
        } catch (\Throwable $e) {
            Log::error('WA conversation update failed', ['error' => $e->getMessage()]);
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
                $this->isolated(fn () => (new WhatsappChatbotService($setting))->handleIncomingMessage($waId, $messageText, $contactName, $buttonId));
            } catch (\Throwable $e) {
                Log::error('WhatsApp inbound processing failed', ['error' => $e->getMessage()]);
                // Swallowed so the webhook still answers 2xx — but a chatbot that
                // dies here would otherwise be invisible, so show it in Error Logs.
                ErrorLog::capture($e);
            }
        }
    }

    // Runs a step whose failure is caught and ignored by the caller. When the
    // gateway webhook has an open transaction (it records the event id and
    // handles the event atomically), the step gets its own savepoint: on
    // PostgreSQL ANY failed statement aborts the whole transaction and the
    // final COMMIT silently becomes a ROLLBACK — which would drop the event row
    // and every later query (chatbot included) even though the failure was
    // "handled". A savepoint rolls back just this step and leaves the
    // transaction usable. Outside a transaction (direct-Meta webhook) it just
    // runs the step.
    private function isolated(\Closure $step): mixed
    {
        return DB::transactionLevel() > 0 ? DB::transaction($step) : $step();
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
