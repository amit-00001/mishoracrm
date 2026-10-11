<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// A WhatsApp Team Inbox thread — one per customer phone number. The messages
// live in whatsapp_logs; see messages().
class WhatsappConversation extends Model
{
    use BelongsToTenant;

    // WhatsApp only allows free-form replies within 24h of the customer's
    // last message; after that only an approved template can be sent.
    public const REPLY_WINDOW_HOURS = 24;

    protected $fillable = [
        'tenant_id', 'wa_id', 'contact_name', 'lead_id', 'contact_id', 'assigned_to',
        'status', 'unread_count', 'last_message_preview', 'last_message_at', 'last_inbound_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'last_inbound_at' => 'datetime',
    ];

    public function lead(): BelongsTo     { return $this->belongsTo(Lead::class); }
    public function contact(): BelongsTo  { return $this->belongsTo(Contact::class); }
    public function assignee(): BelongsTo { return $this->belongsTo(User::class, 'assigned_to'); }

    // Logs store the number in whatever form was typed (+91 98…, 9198…), so
    // match on digits only.
    public function messages()
    {
        return WhatsappLog::where('tenant_id', $this->tenant_id)
            ->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(to_phone, '+', ''), ' ', ''), '-', ''), '(', '') = ?", [$this->wa_id]);
    }

    public function windowOpen(): bool
    {
        return $this->last_inbound_at
            && $this->last_inbound_at->gt(now()->subHours(self::REPLY_WINDOW_HOURS));
    }

    public function displayName(): string
    {
        return $this->lead?->name ?: $this->contact?->name ?: $this->contact_name ?: '+' . $this->wa_id;
    }

    // Called for every message in either direction. $inbound bumps the unread
    // counter, reopens a resolved chat and restarts the 24h reply window.
    public static function touchFor(int $tenantId, string $phone, ?string $name, string $preview, bool $inbound): ?self
    {
        $waId = preg_replace('/[^0-9]/', '', $phone);
        if ($waId === '') {
            return null;
        }

        $conv = static::withoutGlobalScopes()->firstOrNew(['tenant_id' => $tenantId, 'wa_id' => $waId]);

        // Outbound-only numbers (broadcasts, bulk, quotations) are not inbox
        // threads until the customer writes back.
        if (!$conv->exists && !$inbound) {
            return null;
        }

        if (!$conv->exists || ($name && !$conv->contact_name)) {
            $conv->contact_name = $name ?: $conv->contact_name;
        }

        if (!$conv->lead_id && !$conv->contact_id) {
            $tail = substr($waId, -10);
            $conv->lead_id = Lead::withoutGlobalScopes()->where('tenant_id', $tenantId)
                ->where('phone', 'like', '%' . $tail)->value('id');
            if (!$conv->lead_id) {
                $conv->contact_id = Contact::withoutGlobalScopes()->where('tenant_id', $tenantId)
                    ->where('phone', 'like', '%' . $tail)->value('id');
            }
        }

        $conv->last_message_preview = mb_substr($preview, 0, 140);
        $conv->last_message_at      = now();

        if ($inbound) {
            $conv->unread_count   = ($conv->unread_count ?? 0) + 1;
            $conv->last_inbound_at = now();
            $conv->status         = 'open';
        }

        $conv->save();

        return $conv;
    }
}
