<?php

namespace App\Models;

use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property NotificationType $type
 * @property NotificationDeliveryStatus $status
 * @property int $attempts
 */
#[Fillable([
    'visit_id', 'type', 'channel', 'status', 'attempts', 'provider_message_id',
    'provider_request_id', 'error_code', 'last_attempt_at', 'sent_at',
])]
class NotificationDelivery extends Model
{
    /** @return BelongsTo<Visit, $this> */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => NotificationType::class,
            'status' => NotificationDeliveryStatus::class,
            'attempts' => 'integer',
            'last_attempt_at' => 'immutable_datetime',
            'sent_at' => 'immutable_datetime',
        ];
    }
}
