<?php

namespace App\Models;

use App\Services\WhatsappGatewayClient;
use Illuminate\Database\Eloquent\Model;

class WhatsappSetting extends Model
{
    protected $fillable = [
        'tenant_id',
        'connection_mode',
        'gateway_workspace_id',
        'gateway_warning',
        'gateway_warning_url',
        'phone_number_id',
        'waba_id',
        'display_phone_number',
        'verified_name',
        'access_token',
        'registration_pin',
        'webhook_verify_token',
        'chatbot_enabled',
        'is_connected',
        'hours_enabled',
        'hours_start',
        'hours_end',
        'hours_days',
        'after_hours_message',
        'inbox_auto_assign',
        'inbox_auto_assign_users',
        'inbox_last_assigned_to',
        'optout_enabled',
        'auto_create_lead',
    ];

    protected $casts = [
        'chatbot_enabled' => 'boolean',
        'is_connected'    => 'boolean',
        'hours_enabled'   => 'boolean',
        'hours_days'      => 'array',
        'inbox_auto_assign'       => 'boolean',
        'inbox_auto_assign_users' => 'array',
        'optout_enabled'          => 'boolean',
        'auto_create_lead'        => 'boolean',
    ];

    protected $hidden = ['access_token', 'registration_pin'];

    public static function forTenant(int $tenantId): self
    {
        return static::firstOrNew(['tenant_id' => $tenantId]);
    }

    // Business hours for the chatbot's after-hours message. Always "open"
    // while the feature is off. Days are 0 (Sunday) – 6; a window that ends
    // before it starts (22:00 – 06:00) runs overnight.
    public function isOpenNow(?\Carbon\CarbonInterface $at = null): bool
    {
        if (!$this->hours_enabled) {
            return true;
        }

        $at    = $at ?? now();
        $days  = $this->hours_days ?: [0, 1, 2, 3, 4, 5, 6];
        $start = $this->hours_start ?: '09:00';
        $end   = $this->hours_end ?: '18:00';
        $time  = $at->format('H:i');

        if ($start <= $end) {
            return in_array($at->dayOfWeek, $days, false) && $time >= $start && $time < $end;
        }

        // Overnight: the evening part belongs to today, the morning part to the day that started it.
        return ($time >= $start && in_array($at->dayOfWeek, $days, false))
            || ($time < $end && in_array($at->copy()->subDay()->dayOfWeek, $days, false));
    }

    // True when this tenant's number is linked through the WhatsApp Gateway
    // (Milan CRM relays Meta for us) rather than our own Meta app.
    public function viaGateway(): bool
    {
        return $this->connection_mode === 'gateway';
    }

    // Every "is WhatsApp connected?" check in the app reads this attribute, so
    // a number linked through the gateway counts as connected only while the
    // superadmin has the gateway switched on — flip it off and those tenants
    // fall back to "not connected" instead of failing on every send.
    public function getIsConnectedAttribute($value): bool
    {
        if (!$value) {
            return false;
        }

        return !$this->viaGateway() || WhatsappGatewayClient::enabled();
    }
}
