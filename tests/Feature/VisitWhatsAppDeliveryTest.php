<?php

namespace Tests\Feature;

use App\Contracts\WhatsAppGateway;
use App\Data\WhatsAppSendResult;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationType;
use App\Enums\VisitStatus;
use App\Exceptions\WhatsAppDeliveryException;
use App\Jobs\SendVisitWhatsApp;
use App\Models\NotificationDelivery;
use App\Models\User;
use App\Models\Visit;
use App\Services\DecisionLinkService;
use App\Services\VisitWhatsAppMessageFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitWhatsAppDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_arrival_job_sends_link_and_stores_only_sanitized_delivery_metadata(): void
    {
        $gateway = new class implements WhatsAppGateway
        {
            /** @var array<int, array{string, string}> */
            public array $sent = [];

            public function send(string $target, string $message): WhatsAppSendResult
            {
                $this->sent[] = [$target, $message];

                return new WhatsAppSendResult('provider-message', 'provider-request');
            }
        };
        $visit = Visit::factory()->create();
        $job = new SendVisitWhatsApp($visit->id, NotificationType::EmployeeArrival);

        $job->handle($gateway, app(DecisionLinkService::class), app(VisitWhatsAppMessageFactory::class));

        $delivery = NotificationDelivery::query()->sole();
        $this->assertSame(NotificationDeliveryStatus::Sent, $delivery->status);
        $this->assertSame(1, $delivery->attempts);
        $this->assertSame('provider-message', $delivery->provider_message_id);
        $this->assertCount(1, $gateway->sent);
        $this->assertSame($visit->employee->notification_contact, $gateway->sent[0][0]);
        $this->assertStringContainsString($visit->guest_name, $gateway->sent[0][1]);
        $this->assertStringContainsString('/decisions/', $gateway->sent[0][1]);
        $this->assertDatabaseCount('visit_decision_tokens', 1);

        $stored = json_encode($delivery->getAttributes(), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString($gateway->sent[0][0], $stored);
        $this->assertStringNotContainsString($visit->guest_name, $stored);
        $this->assertStringNotContainsString('/decisions/', $stored);

        $job->handle($gateway, app(DecisionLinkService::class), app(VisitWhatsAppMessageFactory::class));
        $this->assertCount(1, $gateway->sent);
        $this->assertDatabaseCount('notification_deliveries', 1);
    }

    public function test_reception_jobs_build_messages_for_accepted_and_rejected_decisions(): void
    {
        config(['services.fonnte.reception_number' => '081234567899']);
        $gateway = new class implements WhatsAppGateway
        {
            /** @var array<int, array{string, string}> */
            public array $sent = [];

            public function send(string $target, string $message): WhatsAppSendResult
            {
                $this->sent[] = [$target, $message];

                return new WhatsAppSendResult('id', 'request');
            }
        };
        $accepted = Visit::factory()->create(['status' => VisitStatus::Accepted, 'guest_name' => 'Tamu Diterima']);
        $rejected = Visit::factory()->create([
            'status' => VisitStatus::Rejected,
            'guest_name' => 'Tamu Ditolak',
            'decision_reason' => 'Pegawai sedang dinas luar.',
        ]);

        (new SendVisitWhatsApp($accepted->id, NotificationType::ReceptionAccepted))
            ->handle($gateway, app(DecisionLinkService::class), app(VisitWhatsAppMessageFactory::class));
        (new SendVisitWhatsApp($rejected->id, NotificationType::ReceptionRejected))
            ->handle($gateway, app(DecisionLinkService::class), app(VisitWhatsAppMessageFactory::class));

        $this->assertCount(2, $gateway->sent);
        $this->assertSame('6281234567899', $gateway->sent[0][0]);
        $this->assertStringContainsString('telah diterima', $gateway->sent[0][1]);
        $this->assertStringContainsString('tidak dapat diterima', $gateway->sent[1][1]);
        $this->assertStringContainsString('Pegawai sedang dinas luar.', $gateway->sent[1][1]);
    }

    public function test_failed_attempts_are_counted_and_final_failure_is_sanitized(): void
    {
        $gateway = new class implements WhatsAppGateway
        {
            public function send(string $target, string $message): WhatsAppSendResult
            {
                throw new WhatsAppDeliveryException('connection_failed');
            }
        };
        $visit = Visit::factory()->create();
        $job = new SendVisitWhatsApp($visit->id, NotificationType::EmployeeArrival);

        foreach ([1, 2] as $attempt) {
            try {
                $job->handle($gateway, app(DecisionLinkService::class), app(VisitWhatsAppMessageFactory::class));
            } catch (WhatsAppDeliveryException) {
                $this->assertSame($attempt, NotificationDelivery::query()->sole()->attempts);
            }
        }
        $job->failed(new WhatsAppDeliveryException('connection_failed'));

        $delivery = NotificationDelivery::query()->sole();
        $this->assertSame(NotificationDeliveryStatus::Failed, $delivery->status);
        $this->assertSame('connection_failed', $delivery->error_code);
        $this->assertSame([60, 300, 900], $job->backoff());
        $this->assertSame(3, $job->tries);
    }

    public function test_only_admin_can_view_delivery_monitoring(): void
    {
        $visit = Visit::factory()->create();
        NotificationDelivery::query()->create([
            'visit_id' => $visit->id,
            'type' => NotificationType::EmployeeArrival,
            'status' => NotificationDeliveryStatus::Failed,
            'attempts' => 3,
            'error_code' => 'connection_failed',
        ]);

        $this->get(route('admin.visits.show', $visit))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('admin.visits.show', $visit))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.visits.show', $visit))
            ->assertOk()->assertSee('connection_failed')->assertSee('3 percobaan');
    }
}
