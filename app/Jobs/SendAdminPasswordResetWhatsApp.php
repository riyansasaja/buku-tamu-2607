<?php

namespace App\Jobs;

use App\Contracts\WhatsAppGateway;
use App\Enums\NotificationDeliveryStatus;
use App\Exceptions\WhatsAppDeliveryException;
use App\Models\User;
use App\Models\UserPasswordResetDelivery;
use App\Models\UserPasswordResetToken;
use App\Support\WhatsAppNumber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class SendAdminPasswordResetWhatsApp implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public int $uniqueFor = 3600;

    public function __construct(public int $userId, public int $tokenId, public string $encryptedToken)
    {
        $this->onQueue('notifications');
    }

    public function uniqueId(): string
    {
        return 'admin-reset|'.$this->tokenId;
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(WhatsAppGateway $gateway): void
    {
        $user = User::query()->findOrFail($this->userId);
        $token = UserPasswordResetToken::query()->findOrFail($this->tokenId);
        $delivery = UserPasswordResetDelivery::query()->firstOrCreate(['user_password_reset_token_id' => $token->id], ['user_id' => $user->id, 'status' => NotificationDeliveryStatus::Pending]);
        if ($delivery->status === NotificationDeliveryStatus::Sent || $token->revoked_at !== null || $token->used_at !== null) {
            return;
        }
        $target = WhatsAppNumber::normalize($user->whatsapp);
        if ($target === null) {
            throw new WhatsAppDeliveryException('invalid_admin_number');
        }
        $delivery->update(['status' => NotificationDeliveryStatus::Processing, 'attempts' => $delivery->attempts + 1, 'last_attempt_at' => now(), 'error_code' => null]);
        try {
            $url = route('password.reset', Crypt::decryptString($this->encryptedToken));
            $result = $gateway->send($target, "Reset Password Admin Buku Tamu PTA Manado\n\nHalo {$user->name}, gunakan tautan berikut untuk membuat password baru:\n{$url}\n\nTautan ini rahasia, sekali pakai, dan berlaku selama 60 menit.");
            $delivery->update(['status' => NotificationDeliveryStatus::Sent, 'provider_message_id' => $result->messageId, 'provider_request_id' => $result->requestId, 'sent_at' => now(), 'error_code' => null]);
        } catch (WhatsAppDeliveryException $exception) {
            $delivery->update(['status' => NotificationDeliveryStatus::Pending, 'error_code' => $exception->errorCode]);
            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        UserPasswordResetDelivery::query()->where('user_password_reset_token_id', $this->tokenId)->update(['status' => NotificationDeliveryStatus::Failed, 'error_code' => $exception instanceof WhatsAppDeliveryException ? $exception->errorCode : 'job_failed']);
    }
}
