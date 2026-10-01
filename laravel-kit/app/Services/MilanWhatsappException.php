<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use RuntimeException;

// Thrown when Milan CRM's WhatsApp Gateway answers with an error.
//   $code      gateway error code: not_connected, whatsapp_error, network_error, ...
//   $metaCode  WhatsApp's own error number when it is a "whatsapp_error" (131047 = 24-hour window closed)
//   $data      for a rejected send, the message that was recorded (status "failed")
class MilanWhatsappException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $httpStatus = 0,
        public readonly ?string $errorCode = null,
        public readonly ?int $metaCode = null,
        public readonly ?array $data = null,
    ) {
        parent::__construct($message, $httpStatus);
    }

    public static function fromResponse(Response $res): self
    {
        return new self(
            (string) ($res->json('message') ?? 'WhatsApp Gateway error (HTTP ' . $res->status() . ')'),
            $res->status(),
            $res->json('error.code'),
            $res->json('error.meta_code'),
            $res->json('data'),
        );
    }

    public function isNotConnected(): bool
    {
        return $this->errorCode === 'not_connected';
    }

    // WhatsApp refused a free-form message because the customer has not written in the last 24 hours.
    public function isOutsideWindow(): bool
    {
        return $this->metaCode === 131047;
    }
}
