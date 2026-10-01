<?php

namespace App\Listeners;

use App\Events\WhatsappMessageReceived;
use App\Services\MilanWhatsapp;
use App\Services\MilanWhatsappException;
use Illuminate\Contracts\Queue\ShouldQueue;

// EXAMPLE only — copy it, rename it, put your own bot logic here.
// A customer just wrote to a tenant's number, so the 24-hour window is open and plain text is allowed.
//
// Register it (Laravel 11 discovers listeners by type-hint; on 10 add it to EventServiceProvider::$listen).
class ExampleAutoReply implements ShouldQueue
{
    public function __construct(private MilanWhatsapp $whatsapp)
    {
    }

    public function handle(WhatsappMessageReceived $event): void
    {
        $m = $event->message;

        $reply = match (true) {
            $m->type === 'text' && str_contains(strtolower((string) $m->text), 'price') => 'Our plans start at Rs 999/month. Reply HELP to talk to a person.',
            $m->type === 'text' && strtolower(trim((string) $m->text)) === 'help'       => 'A team member will message you shortly.',
            default                                                                       => null,
        };

        if (!$reply) {
            return;
        }

        try {
            // "reply-<id>" makes a queue retry of this job harmless: the gateway will not send it twice.
            $this->whatsapp->sendText($event->tenantId, $m->phone, $reply, 'reply-' . $m->gateway_id);
        } catch (MilanWhatsappException $e) {
            report($e);
        }
    }
}
