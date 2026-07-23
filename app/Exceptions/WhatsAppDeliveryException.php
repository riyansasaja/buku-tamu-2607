<?php

namespace App\Exceptions;

use RuntimeException;

class WhatsAppDeliveryException extends RuntimeException
{
    public function __construct(public readonly string $errorCode)
    {
        parent::__construct('Pengiriman WhatsApp gagal: '.$errorCode);
    }
}
