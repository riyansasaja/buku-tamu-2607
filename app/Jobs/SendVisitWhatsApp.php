<?php

namespace App\Jobs;

use App\Contracts\WhatsAppGateway;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationType;
use App\Exceptions\WhatsAppDeliveryException;
use App\Models\NotificationDelivery;
use App\Models\Visit;
use App\Services\DecisionLinkService;
use App\Services\VisitWhatsAppMessageFactory;
use App\Support\WhatsAppNumber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SendVisitWhatsApp implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public int $uniqueFor = 3600;

    public function __construct(
        public readonly int $visitId,
        public readonly NotificationType $type,
    ) {
        $this->onQueue('notifications');
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function uniqueId(): string
    {
        return $this->visitId.'|'.$this->type->value;
    }

    public function handle(
        WhatsAppGateway $gateway,
        DecisionLinkService $decisionLinks,
        VisitWhatsAppMessageFactory $messages,
    ): void {
        $visit = Visit::query()->with(['employee'])->findOrFail($this->visitId);
        $delivery = NotificationDelivery::query()->firstOrCreate(
            ['visit_id' => $visit->id, 'type' => $this->type],
            ['channel' => 'whatsapp', 'status' => NotificationDeliveryStatus::Pending],
        );

        if ($delivery->status === NotificationDeliveryStatus::Sent) {
            return;
        }

        $delivery->update([
            'status' => NotificationDeliveryStatus::Processing,
            'attempts' => $delivery->attempts + 1,
            'last_attempt_at' => now(),
            'error_code' => null,
        ]);

        try {
            [$target, $message] = match ($this->type) {
                NotificationType::EmployeeArrival => $this->employeeMessage($visit, $decisionLinks, $messages),
                NotificationType::ReceptionAccepted, NotificationType::ReceptionRejected => $this->receptionMessage($visit, $messages),
                default => throw new WhatsAppDeliveryException('unsupported_type'),
            };
            $result = $gateway->send($target, $message);
            $delivery->update([
                'status' => NotificationDeliveryStatus::Sent,
                'provider_message_id' => $result->messageId,
                'provider_request_id' => $result->requestId,
                'sent_at' => now(),
                'error_code' => null,
            ]);
        } catch (WhatsAppDeliveryException $exception) {
            $delivery->update([
                'status' => NotificationDeliveryStatus::Pending,
                'error_code' => $exception->errorCode,
            ]);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        NotificationDelivery::query()
            ->where('visit_id', $this->visitId)
            ->where('type', $this->type)
            ->update([
                'status' => NotificationDeliveryStatus::Failed,
                'error_code' => $exception instanceof WhatsAppDeliveryException ? $exception->errorCode : 'job_failed',
            ]);
    }

    /** @return array{string, string} */
    private function employeeMessage(Visit $visit, DecisionLinkService $links, VisitWhatsAppMessageFactory $messages): array
    {
        $target = WhatsAppNumber::normalize($visit->employee->notification_contact);
        if ($target === null) {
            throw new WhatsAppDeliveryException('invalid_employee_number');
        }

        $decisionLink = $links->issue($visit);

        return [$target, $messages->employeeArrival($visit, $decisionLink->url)];
    }

    /** @return array{string, string} */
    private function receptionMessage(Visit $visit, VisitWhatsAppMessageFactory $messages): array
    {
        $target = WhatsAppNumber::normalize(config('services.fonnte.reception_number'));
        if ($target === null) {
            throw new WhatsAppDeliveryException('invalid_reception_number');
        }

        return [$target, $messages->receptionDecision($visit, $this->type)];
    }
}
