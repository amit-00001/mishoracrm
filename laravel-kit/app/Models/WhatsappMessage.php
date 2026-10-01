<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappMessage extends Model
{
    protected $fillable = [
        'tenant_id', 'gateway_id', 'wamid', 'direction', 'phone', 'contact_name', 'type', 'text', 'content',
        'status', 'error_code', 'error_message', 'client_reference',
    ];

    protected $casts = ['content' => 'array'];

    // Builds/updates a row from the gateway's message object (webhook "data.message" or an API response).
    public static function syncFromGateway(string|int $tenantId, array $m): self
    {
        return static::updateOrCreate(
            ['gateway_id' => $m['id']],
            [
                'tenant_id'        => (string) $tenantId,
                'wamid'            => $m['wamid'] ?? null,
                'direction'        => $m['direction'],
                'phone'            => $m['phone'],
                'contact_name'     => $m['contact_name'] ?? null,
                'type'             => $m['type'],
                'text'             => $m['text'] ?? null,
                'content'          => $m['content'] ?? null,
                'status'           => $m['status'],
                'error_code'       => $m['error']['code'] ?? null,
                'error_message'    => $m['error']['message'] ?? null,
                'client_reference' => $m['client_reference'] ?? null,
            ]
        );
    }
}
