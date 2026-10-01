<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantWhatsappAccount extends Model
{
    protected $fillable = [
        'tenant_id', 'connected', 'display_phone_number', 'verified_name', 'quality_rating', 'last_warning', 'connected_at',
    ];

    protected $casts = ['connected' => 'boolean', 'connected_at' => 'datetime'];

    public static function forTenant(string|int $tenantId): self
    {
        return static::firstOrCreate(['tenant_id' => (string) $tenantId]);
    }

    public static function isConnected(string|int $tenantId): bool
    {
        return (bool) static::where('tenant_id', (string) $tenantId)->value('connected');
    }
}
