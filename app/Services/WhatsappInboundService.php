<?php

namespace App\Services;

use App\Models\ErrorLog;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\WhatsappOptOut;
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
        $conv = null;
        try {
            $conv = $this->isolated(fn () => WhatsappConversation::touchFor(
                $setting->tenant_id, $waId, $contactName, $messageText ?? "[{$messageType}]", true
            ));
        } catch (\Throwable $e) {
            Log::error('WA conversation update failed', ['error' => $e->getMessage()]);
        }

        // STOP / START — the customer opting out of (or back into) broadcasts.
        // Typed words only, never a button tap; answered here, not by the chatbot.
        if ($messageText && !$buttonId) {
            try {
                if ($this->isolated(fn () => $this->handleOptOut($setting, $waId, $messageText))) {
                    return;
                }
            } catch (\Throwable $e) {
                Log::error('WA opt-out handling failed', ['error' => $e->getMessage()]);
            }
        }

        // A first message from a number we do not know becomes a Lead.
        if ($conv) {
            try {
                $this->isolated(fn () => $this->autoCreateLead($setting, $conv, $messageText ?? "[{$messageType}]", $contactName));
            } catch (\Throwable $e) {
                Log::error('WA auto-create lead failed', ['error' => $e->getMessage()]);
                ErrorLog::capture($e);
            }
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

    // "STOP" adds the number to the broadcast opt-out list, "START" takes it off
    // again (only when it is on the list — so a chatbot keyword "start" still
    // works for everyone else). Returns true when the message was one of these.
    private function handleOptOut(WhatsappSetting $setting, string $waId, string $text): bool
    {
        if (!($setting->optout_enabled ?? true)) {
            return false;
        }

        $tenantId = (int) $setting->tenant_id;

        if (WhatsappOptOut::isStopWord($text)) {
            WhatsappOptOut::add($tenantId, $waId, 'keyword');
            $this->reply($setting, $waId, "You have been unsubscribed from our broadcast messages. You can still message us any time. Reply START to receive them again.");
            return true;
        }

        if (WhatsappOptOut::isStartWord($text) && WhatsappOptOut::isOptedOut($tenantId, $waId)) {
            WhatsappOptOut::remove($tenantId, $waId);
            $this->reply($setting, $waId, "You are subscribed again. Thank you!");
            return true;
        }

        return false;
    }

    // One short automatic reply, logged like any other outgoing message.
    private function reply(WhatsappSetting $setting, string $waId, string $text): void
    {
        $service = new WhatsappChatbotService($setting);
        $ok      = $service->sendMessage($waId, $text);

        WhatsappLog::create([
            'tenant_id'     => $setting->tenant_id,
            'to_phone'      => $waId,
            'message'       => $text,
            'status'        => $ok ? 'sent' : 'failed',
            'error_message' => $ok ? null : $service->lastError,
            'wamid'         => $ok ? $service->lastMessageId : null,
            'sent_at'       => now(),
        ]);
    }

    // Creates a Lead (source "WhatsApp") for a number that is not yet a Lead or
    // Contact, links it to the Inbox chat and keeps the chat's owner in step with
    // the lead's. Only for tenants with the WhatsApp Team Inbox module, and only
    // while their "auto-create lead" switch is on (default on).
    private function autoCreateLead(WhatsappSetting $setting, WhatsappConversation $conv, string $preview, ?string $name): void
    {
        if ($conv->lead_id || $conv->contact_id || !($setting->auto_create_lead ?? true)) {
            return;
        }

        $tenant = Tenant::find($setting->tenant_id);
        if (!$tenant || !$tenant->hasModuleEnabled('whatsapp_inbox')) {
            return;
        }

        $phone = '+' . $conv->wa_id;

        if ($lead = DuplicateMatcher::findExistingLead($tenant->id, $phone, null)) {
            $conv->update(['lead_id' => $lead->id]);
            return;
        }
        if ($contact = DuplicateMatcher::findExistingContact($tenant->id, $phone, null)) {
            $conv->update(['contact_id' => $contact->id]);
            return;
        }

        $lead = Lead::create([
            'tenant_id'   => $tenant->id,
            'name'        => $name ?: $phone,
            'phone'       => $phone,
            'source'      => 'whatsapp',
            'status'      => 'new',
            'priority'    => 'medium',
            'notes'       => 'Started a WhatsApp chat: "' . mb_substr($preview, 0, 500) . '"',
            'assigned_to' => $conv->assigned_to,
            'created_by'  => null,
        ]);

        // LeadCreated may auto-assign the lead; let the chat follow its owner.
        $lead->refresh();
        $conv->update([
            'lead_id'     => $lead->id,
            'assigned_to' => $conv->assigned_to ?: $lead->assigned_to,
        ]);
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
