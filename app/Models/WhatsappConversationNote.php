<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Private team note inside an Inbox chat — never sent to the customer.
class WhatsappConversationNote extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'conversation_id', 'user_id', 'body'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
