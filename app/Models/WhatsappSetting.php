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
        'phone_number_id',
        'waba_id',
        'display_phone_number',
        'verified_name',
        'access_token',
        'registration_pin',
        'webhook_verify_token',
        'chatbot_enabled',
        'is_connected',
    ];

    protected $casts = [
        'chatbot_enabled' => 'boolean',
        'is_connected'    => 'boolean',
    ];

    protected $hidden = ['access_token', 'registration_pin'];

    public static function forTenant(int $tenantId): self
    {
        return static::firstOrNew(['tenant_id' => $tenantId]);
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
