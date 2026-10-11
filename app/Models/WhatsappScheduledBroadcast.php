<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// A bulk send saved to go out later (see App\Console\Commands\SendScheduledWhatsappBroadcasts).
class WhatsappScheduledBroadcast extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'created_by', 'type', 'recipients', 'message', 'template_id',
        'meta_template', 'template_params', 'attachment_path', 'attachment_name', 'attachment_mime',
        'scheduled_for', 'status', 'bulk_id', 'sent_count', 'failed_count', 'skipped_count', 'error', 'processed_at',
    ];

    protected $casts = [
        'recipients'      => 'array',
        'template_params' => 'array',
        'scheduled_for'   => 'datetime',
        'processed_at'    => 'datetime',
    ];

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
