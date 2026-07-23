<?php

namespace App\Enums;

enum NotificationDeliveryStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Sent = 'sent';
    case Failed = 'failed';
}
