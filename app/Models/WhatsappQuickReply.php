<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

// A saved reply the team inserts in the Inbox by typing "/shortcut".
class WhatsappQuickReply extends Model
{
    use BelongsToTenant;

    protected $fillable = ['tenant_id', 'shortcut', 'body', 'created_by'];
}
