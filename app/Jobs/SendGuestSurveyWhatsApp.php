<?php

namespace App\Jobs;

use App\Contracts\WhatsAppGateway;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationType;
use App\Enums\SurveyInvitationStatus;
use App\Enums\VisitStatus;
use App\Exceptions\WhatsAppDeliveryException;
use App\Models\NotificationDelivery;
use App\Models\SurveyInvitation;
use App\Support\WhatsAppNumber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

class SendGuestSurveyWhatsApp implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public int $uniqueFor = 14400;

    public function __construct(public readonly int $invitationId)
    {
        $this->onQueue('notifications');
    }

    public function uniqueId(): string
    {
        return (string) $this->invitationId;
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(WhatsAppGateway $gateway): void
    {
        $invitation = SurveyInvitation::query()->with('visit')->findOrFail($this->invitationId);
        $visit = $invitation->visit;
        if ($invitation->status !== SurveyInvitationStatus::Scheduled || $invitation->revoked_at !== null || $visit->status !== VisitStatus::Accepted) {
            return;
        }

        $delivery = NotificationDelivery::query()->firstOrCreate(
            ['visit_id' => $visit->id, 'type' => NotificationType::GuestSurvey],
            ['channel' => 'whatsapp', 'status' => NotificationDeliveryStatus::Pending],
        );
        if ($delivery->status === NotificationDeliveryStatus::Sent) {
            return;
        }

        $target = WhatsAppNumber::normalize($visit->guest_whatsapp);
        if ($target === null) {
            throw new WhatsAppDeliveryException('invalid_guest_number');
        }

        $delivery->update(['status' => NotificationDeliveryStatus::Processing, 'attempts' => $delivery->attempts + 1, 'last_attempt_at' => now(), 'error_code' => null]);
        $plainToken = Str::random(64);
        $invitation->update(['token_hash' => hash('sha256', $plainToken)]);
        $url = URL::route('surveys.show', ['token' => $plainToken]);
        $message = "*Survei Kepuasan PTA Manado*\n\nTerima kasih atas kunjungan Anda. Mohon isi penilaian singkat melalui tautan berikut:\n{$url}\n\nTautan bersifat rahasia dan hanya dapat digunakan satu kali.";

        try {
            $result = $gateway->send($target, $message);
            $delivery->update(['status' => NotificationDeliveryStatus::Sent, 'provider_message_id' => $result->messageId, 'provider_request_id' => $result->requestId, 'sent_at' => now(), 'error_code' => null]);
            $invitation->update(['status' => SurveyInvitationStatus::Sent, 'sent_at' => now()]);
        } catch (WhatsAppDeliveryException $exception) {
            $delivery->update(['status' => NotificationDeliveryStatus::Pending, 'error_code' => $exception->errorCode]);
            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $invitation = SurveyInvitation::query()->find($this->invitationId);
        if ($invitation !== null) {
            NotificationDelivery::query()->where('visit_id', $invitation->visit_id)->where('type', NotificationType::GuestSurvey)->update([
                'status' => NotificationDeliveryStatus::Failed,
                'error_code' => $exception instanceof WhatsAppDeliveryException ? $exception->errorCode : 'job_failed',
            ]);
        }
    }
}
