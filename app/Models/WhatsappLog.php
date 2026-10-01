<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'template_id', 'lead_id', 'contact_id',
        'sent_by', 'to_phone', 'to_name', 'message',
        'status', 'error_message', 'is_bulk', 'bulk_id', 'sent_at',
        'media_type', 'media_id', 'attachment_name', 'wamid',
    ];

    protected $casts = [
        'sent_at'  => 'datetime',
        'is_bulk'  => 'boolean',
    ];

    public function template(): BelongsTo  { return $this->belongsTo(WhatsappTemplate::class); }
    public function lead(): BelongsTo      { return $this->belongsTo(Lead::class); }
    public function contact(): BelongsTo   { return $this->belongsTo(Contact::class); }
    public function sentBy(): BelongsTo    { return $this->belongsTo(User::class, 'sent_by'); }

    // Build WhatsApp URL
    public function getWaUrlAttribute(): string
    {
        $phone = preg_replace('/[^0-9]/', '', $this->to_phone);
        return 'https://wa.me/' . $phone . '?text=' . urlencode($this->message);
    }
}