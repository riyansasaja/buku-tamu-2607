<?php

namespace App\Data;

readonly class WhatsAppSendResult
{
    public function __construct(
        public string $messageId,
        public string $requestId,
    ) {}
}
