<?php

namespace App\Events;

use App\Models\WhatsappMessage;
use Illuminate\Foundation\Events\Dispatchable;

// Fired for every customer message that reaches one of your tenants' WhatsApp numbers.
// This is where YOUR bot / inbox / CRM logic plugs in — see App\Listeners\ExampleAutoReply.
class WhatsappMessageReceived
{
    use Dispatchable;

    public function __construct(
        public string $tenantId,
        public WhatsappMessage $message,   // ->phone, ->text, ->type, ->content (button id, media id, ...)
    ) {
    }
}
