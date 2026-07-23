<?php

namespace App\Contracts;

use App\Data\WhatsAppSendResult;

interface WhatsAppGateway
{
    public function send(string $target, string $message): WhatsAppSendResult;
}
