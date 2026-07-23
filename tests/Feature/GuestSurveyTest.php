<?php

namespace Tests\Feature;

use App\Contracts\WhatsAppGateway;
use App\Data\WhatsAppSendResult;
use App\Enums\NotificationDeliveryStatus;
use App\Enums\NotificationType;
use App\Enums\SurveyInvitationStatus;
use App\Enums\VisitStatus;
use App\Events\VisitDecisionRecorded;
use App\Exceptions\WhatsAppDeliveryException;
use App\Jobs\SendGuestSurveyWhatsApp;
use App\Models\NotificationDelivery;
use App\Models\SurveyInvitation;
use App\Models\User;
use App\Models\Visit;
use App\Services\SurveyInvitationService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuestSurveyTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_accepted_visit_schedules_one_survey_three_hours_after_decision(): void
    {
        Queue::fake();
        $decidedAt = CarbonImmutable::parse('2026-07-23 10:00:00');
        $accepted = Visit::factory()->create(['status' => VisitStatus::Accepted, 'decided_at' => $decidedAt]);
        $rejected = Visit::factory()->create(['status' => VisitStatus::Rejected, 'decided_at' => $decidedAt]);
        $service = app(SurveyInvitationService::class);
        $first = $service->schedule($accepted->id);
        $second = $service->schedule($accepted->id);

        $this->assertNotNull($first);
        $this->assertSame($first->id, $second?->id);
        $this->assertTrue($first->scheduled_at->equalTo($decidedAt->addHours(3)));
        $this->assertNull($service->schedule($rejected->id));
        $this->assertDatabaseCount('survey_invitations', 1);
        Queue::assertPushed(SendGuestSurveyWhatsApp::class, 1);
    }

    public function test_accepted_decision_event_dispatches_delayed_survey_job(): void
    {
        Queue::fake();
        $visit = Visit::factory()->create(['status' => VisitStatus::Accepted, 'decided_at' => now()]);

        VisitDecisionRecorded::dispatch($visit->id, VisitStatus::Accepted);

        Queue::assertPushed(SendGuestSurveyWhatsApp::class, 1);
        $this->assertDatabaseHas('survey_invitations', ['visit_id' => $visit->id, 'status' => SurveyInvitationStatus::Scheduled->value]);
    }

    public function test_survey_job_sends_once_and_stores_no_plain_token_or_number(): void
    {
        $gateway = new class implements WhatsAppGateway
        {
            /** @var array<int, array{string, string}> */
            public array $sent = [];

            public function send(string $target, string $message): WhatsAppSendResult
            {
                $this->sent[] = [$target, $message];

                return new WhatsAppSendResult('message-id', 'request-id');
            }
        };
        $visit = Visit::factory()->create(['status' => VisitStatus::Accepted, 'decided_at' => now(), 'guest_whatsapp' => '081234567890']);
        $invitation = SurveyInvitation::query()->create(['visit_id' => $visit->id, 'status' => SurveyInvitationStatus::Scheduled, 'scheduled_at' => now()]);
        $job = new SendGuestSurveyWhatsApp($invitation->id);
        $job->handle($gateway);
        $job->handle($gateway);

        $this->assertCount(1, $gateway->sent);
        $this->assertSame('6281234567890', $gateway->sent[0][0]);
        $this->assertMatchesRegularExpression('~/surveys/[A-Za-z0-9]{64}~', $gateway->sent[0][1]);
        $this->assertSame(SurveyInvitationStatus::Sent, $invitation->fresh()->status);
        $delivery = NotificationDelivery::query()->sole();
        $this->assertSame(NotificationDeliveryStatus::Sent, $delivery->status);
        $this->assertSame(NotificationType::GuestSurvey, $delivery->type);
        $stored = json_encode([$invitation->fresh()->getAttributes(), $delivery->getAttributes()], JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('6281234567890', $stored);
        preg_match('~/surveys/([A-Za-z0-9]{64})~', $gateway->sent[0][1], $matches);
        $this->assertStringNotContainsString($matches[1], $stored);
    }

    public function test_survey_job_retries_with_sanitized_failure_state(): void
    {
        $gateway = new class implements WhatsAppGateway
        {
            public function send(string $target, string $message): WhatsAppSendResult
            {
                throw new WhatsAppDeliveryException('connection_failed');
            }
        };
        $visit = Visit::factory()->create(['status' => VisitStatus::Accepted, 'decided_at' => now()]);
        $invitation = SurveyInvitation::query()->create(['visit_id' => $visit->id, 'status' => SurveyInvitationStatus::Scheduled, 'scheduled_at' => now()]);
        $job = new SendGuestSurveyWhatsApp($invitation->id);

        foreach ([1, 2] as $attempt) {
            try {
                $job->handle($gateway);
            } catch (WhatsAppDeliveryException) {
                $this->assertSame($attempt, NotificationDelivery::query()->sole()->attempts);
            }
        }
        $job->failed(new WhatsAppDeliveryException('connection_failed'));

        $delivery = NotificationDelivery::query()->sole();
        $this->assertSame(NotificationDeliveryStatus::Failed, $delivery->status);
        $this->assertSame('connection_failed', $delivery->error_code);
        $this->assertSame([60, 300, 900], $job->backoff());
    }

    public function test_guest_can_submit_valid_survey_once(): void
    {
        [$visit, $token] = $this->sentInvitation();
        $this->get(route('surveys.show', $token))->assertOk()->assertSee('Survei Kepuasan')->assertDontSee($visit->guest_name)->assertHeader('Referrer-Policy', 'no-referrer');
        $this->post(route('surveys.store', $token), ['rating' => 5, 'comment' => 'Pelayanannya sangat baik.'])->assertOk()->assertSee('Terima kasih');
        $this->assertDatabaseHas('survey_responses', ['visit_id' => $visit->id, 'rating' => 5, 'comment' => 'Pelayanannya sangat baik.']);
        $this->assertSame(SurveyInvitationStatus::Used, $visit->surveyInvitation()->firstOrFail()->status);
        $this->post(route('surveys.store', $token), ['rating' => 1])->assertNotFound()->assertSee('Survei tidak tersedia');
    }

    public function test_survey_validation_and_invalid_tokens_are_safe(): void
    {
        [, $token] = $this->sentInvitation();
        $this->from(route('surveys.show', $token))->post(route('surveys.store', $token), ['rating' => 6, 'comment' => str_repeat('x', 1001)])->assertRedirect(route('surveys.show', $token))->assertSessionHasErrors(['rating', 'comment']);
        $this->assertDatabaseCount('survey_responses', 0);
        $this->get(route('surveys.show', str_repeat('A', 64)))->assertNotFound()->assertSee('Survei tidak tersedia');
        $other = Visit::factory()->create(['status' => VisitStatus::Accepted, 'decided_at' => now()]);
        $other->surveyInvitation()->create(['token_hash' => hash('sha256', str_repeat('B', 64)), 'status' => SurveyInvitationStatus::Revoked, 'scheduled_at' => now(), 'revoked_at' => now()]);
        $this->get(route('surveys.show', str_repeat('B', 64)))->assertNotFound()->assertSee('Survei tidak tersedia');
    }

    public function test_admin_can_view_survey_result(): void
    {
        [$visit, $token] = $this->sentInvitation();
        $this->post(route('surveys.store', $token), ['rating' => 4, 'comment' => 'Pertahankan keramahan.']);
        $this->actingAs(User::factory()->admin()->create())->get(route('admin.visits.show', $visit))->assertOk()->assertSee('Pertahankan keramahan.')->assertSee('4 dari 5 bintang');
    }

    public function test_retention_removes_survey_token_response_and_delivery(): void
    {
        Storage::fake('local');
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2029-01-02 02:30', 'Asia/Makassar'));
        [$visit, $token] = $this->sentInvitation(['arrived_at' => CarbonImmutable::parse('2026-12-31 23:59', 'Asia/Makassar')]);
        $this->post(route('surveys.store', $token), ['rating' => 3]);
        NotificationDelivery::query()->create(['visit_id' => $visit->id, 'type' => NotificationType::GuestSurvey, 'status' => NotificationDeliveryStatus::Sent]);
        $this->artisan('visits:purge-expired --dry-run')->assertSuccessful();
        $this->assertDatabaseCount('survey_responses', 1);
        $this->artisan('visits:purge-expired')->assertSuccessful();
        $this->assertDatabaseCount('survey_invitations', 0);
        $this->assertDatabaseCount('survey_responses', 0);
        $this->assertDatabaseCount('notification_deliveries', 0);
        CarbonImmutable::setTestNow();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array{0: Visit, 1: string}
     */
    private function sentInvitation(array $attributes = []): array
    {
        $token = str_repeat('S', 64);
        $visit = Visit::factory()->create(array_merge(['status' => VisitStatus::Accepted, 'decided_at' => now()], $attributes));
        $visit->surveyInvitation()->create(['token_hash' => hash('sha256', $token), 'status' => SurveyInvitationStatus::Sent, 'scheduled_at' => now(), 'sent_at' => now()]);

        return [$visit, $token];
    }
}
