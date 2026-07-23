<?php

namespace App\Models;

use App\Enums\NotificationDeliveryStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/** @property NotificationDeliveryStatus $status */
#[Fillable(['user_id', 'user_password_reset_token_id', 'status', 'attempts', 'provider_message_id', 'provider_request_id', 'error_code', 'last_attempt_at', 'sent_at'])]
class UserPasswordResetDelivery extends Model
{
    protected function casts(): array
    {
        return ['status' => NotificationDeliveryStatus::class, 'attempts' => 'integer', 'last_attempt_at' => 'immutable_datetime', 'sent_at' => 'immutable_datetime'];
    }
}
