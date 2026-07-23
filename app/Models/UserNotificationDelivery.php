<?php

namespace App\Models;

use App\Enums\NotificationDeliveryStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** @property NotificationDeliveryStatus $status */
#[Fillable(['user_id', 'user_activation_token_id', 'type', 'status', 'attempts', 'provider_message_id', 'provider_request_id', 'error_code', 'last_attempt_at', 'sent_at'])]
class UserNotificationDelivery extends Model
{
    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return ['status' => NotificationDeliveryStatus::class, 'attempts' => 'integer', 'last_attempt_at' => 'immutable_datetime', 'sent_at' => 'immutable_datetime'];
    }
}
