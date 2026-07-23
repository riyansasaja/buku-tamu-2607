<?php

namespace Tests\Feature;

use App\Enums\VisitStatus;
use App\Events\VisitDecisionRecorded;
use App\Models\AuditLog;
use App\Models\Visit;
use App\Services\DecisionLinkService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class VisitDecisionTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_employee_can_accept_visit_atomically_and_event_is_dispatched_once(): void
    {
        Event::fake([VisitDecisionRecorded::class]);
        $visit = Visit::factory()->create();
        $issued = app(DecisionLinkService::class)->issue($visit);

        $this->post($issued->url, ['decision' => 'accepted', 'decision_reason' => 'harus diabaikan'])
            ->assertOk()->assertSee('Keputusan berhasil disimpan')->assertSee('Tamu telah diterima');

        $visit->refresh();
        $this->assertSame(VisitStatus::Accepted, $visit->status);
        $this->assertNull($visit->decision_reason);
        $this->assertNotNull($visit->decided_at);
        $this->assertNotNull($issued->decisionToken->fresh()->used_at);
        $this->assertDatabaseCount('audit_logs', 1);
        $audit = AuditLog::query()->sole();
        $this->assertSame(['status' => 'accepted'], $audit->metadata);
        $this->assertSame('decision_link', $audit->actor_type);

        Event::assertDispatchedTimes(VisitDecisionRecorded::class, 1);
        Event::assertDispatched(fn (VisitDecisionRecorded $event): bool => $event->visitId === $visit->id
            && $event->status === VisitStatus::Accepted);

        $this->post($issued->url, ['decision' => 'rejected', 'decision_reason' => 'Tidak tersedia'])
            ->assertNotFound()->assertSee('Tautan tidak tersedia');
        $this->assertSame(VisitStatus::Accepted, $visit->fresh()->status);
        $this->assertDatabaseCount('audit_logs', 1);
        Event::assertDispatchedTimes(VisitDecisionRecorded::class, 1);
    }

    public function test_employee_can_reject_visit_with_required_reason(): void
    {
        Event::fake([VisitDecisionRecorded::class]);
        $visit = Visit::factory()->create();
        $issued = app(DecisionLinkService::class)->issue($visit);

        $this->from($issued->url)->post($issued->url, ['decision' => 'rejected', 'decision_reason' => '  Pimpinan sedang dinas luar.  '])
            ->assertOk()->assertSee('Penolakan telah dicatat');

        $visit->refresh();
        $this->assertSame(VisitStatus::Rejected, $visit->status);
        $this->assertSame('Pimpinan sedang dinas luar.', $visit->decision_reason);
        Event::assertDispatched(fn (VisitDecisionRecorded $event): bool => $event->status === VisitStatus::Rejected);
    }

    public function test_rejection_without_valid_reason_does_not_change_any_state(): void
    {
        Event::fake([VisitDecisionRecorded::class]);
        $visit = Visit::factory()->create();
        $issued = app(DecisionLinkService::class)->issue($visit);

        $this->from($issued->url)->post($issued->url, ['decision' => 'rejected', 'decision_reason' => 'x'])
            ->assertRedirect($issued->url)->assertSessionHasErrors('decision_reason');

        $this->assertSame(VisitStatus::Pending, $visit->fresh()->status);
        $this->assertNull($issued->decisionToken->fresh()->used_at);
        $this->assertDatabaseCount('audit_logs', 0);
        Event::assertNotDispatched(VisitDecisionRecorded::class);
    }

    public function test_wrong_revoked_and_other_visit_tokens_cannot_decide_visit(): void
    {
        $first = Visit::factory()->create(['guest_name' => 'Tamu Pertama']);
        $second = Visit::factory()->create(['guest_name' => 'Tamu Kedua']);
        $firstLink = app(DecisionLinkService::class)->issue($first);
        $secondLink = app(DecisionLinkService::class)->issue($second);
        $secondLink->decisionToken->update(['revoked_at' => now()]);

        $this->post('/decisions/'.str_repeat('A', 64), ['decision' => 'accepted'])
            ->assertNotFound()->assertDontSee('Tamu Pertama')->assertDontSee('Tamu Kedua');
        $this->post($secondLink->url, ['decision' => 'accepted'])
            ->assertNotFound()->assertDontSee('Tamu Kedua');
        $this->post($firstLink->url, ['decision' => 'accepted'])->assertOk();

        $this->assertSame(VisitStatus::Accepted, $first->fresh()->status);
        $this->assertSame(VisitStatus::Pending, $second->fresh()->status);
    }

    public function test_decision_action_is_rate_limited_and_route_has_csrf_middleware(): void
    {
        config(['api.rate_limits.decision_actions' => 1]);
        $visit = Visit::factory()->create();
        $issued = app(DecisionLinkService::class)->issue($visit);

        $this->post($issued->url, ['decision' => 'accepted'])->assertOk();
        $this->post($issued->url, ['decision' => 'accepted'])
            ->assertStatus(429)->assertHeader('X-Request-ID')->assertSee('Tautan tidak tersedia');

        $middleware = app('router')->getRoutes()->getByName('decisions.store')?->gatherMiddleware() ?? [];
        $this->assertContains('web', $middleware);
        $this->assertSame(['POST'], app('router')->getRoutes()->getByName('decisions.store')?->methods());
    }
}
