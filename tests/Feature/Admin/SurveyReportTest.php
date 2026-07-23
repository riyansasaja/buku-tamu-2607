<?php

namespace Tests\Feature\Admin;

use App\Data\SurveyFilters;
use App\Enums\SurveyInvitationStatus;
use App\Enums\VisitStatus;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\SurveyInvitation;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Models\Visit;
use App\Models\WorkUnit;
use App\Services\SurveyReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SurveyReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-23 12:00', 'Asia/Makassar'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_only_active_admin_can_access_survey_recap_and_pdf(): void
    {
        $this->get(route('admin.surveys.index'))->assertRedirect(route('login'));
        $this->get(route('admin.reports.surveys.pdf'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('admin.surveys.index'))->assertRedirect(route('login'));

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get(route('admin.surveys.index'))->assertOk()->assertSee('Rekap Hasil Survei');
        $this->actingAs($admin)->get(route('admin.reports.surveys.pdf'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_summary_uses_current_year_and_excludes_ineligible_invitations(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = Employee::factory()->create();
        $this->invitation($employee, '2026-01-01 00:00:00', 5, 'Sangat baik');
        $this->invitation($employee, '2026-12-31 23:59:59', 3, null);
        $this->invitation($employee, '2026-07-23 10:00:00');
        $this->invitation($employee, '2025-12-31 23:59:59', 1);
        $this->invitation($employee, '2026-07-23 10:00:00', null, null, SurveyInvitationStatus::Scheduled);
        $this->invitation($employee, '2026-07-23 10:00:00', null, null, SurveyInvitationStatus::Sent, VisitStatus::Rejected);

        $response = $this->actingAs($admin)->get(route('admin.surveys.index'));

        $response->assertOk()->assertSee('value="2026-01-01"', false)->assertSee('value="2026-12-31"', false)
            ->assertSee('Sangat baik')->assertSee('66.7%')->assertSee('4.00 / 5');
        $this->assertSame(3, $response->viewData('summary')['sent']);
        $this->assertSame([1 => 0, 2 => 0, 3 => 1, 4 => 0, 5 => 1], $response->viewData('summary')['distribution']);
    }

    public function test_filters_and_service_reconcile_responded_details(): void
    {
        $admin = User::factory()->admin()->create();
        $targetUnit = WorkUnit::factory()->create(['name' => 'Unit Target']);
        $otherUnit = WorkUnit::factory()->create(['name' => 'Unit Lain']);
        $target = Employee::factory()->for($targetUnit)->create(['name' => 'Pegawai Target']);
        $other = Employee::factory()->for($otherUnit)->create();
        $match = $this->invitation($target, '2026-05-01 09:00:00', 4, 'Pelayanan ramah');
        $this->invitation($target, '2026-05-02 09:00:00');
        $this->invitation($other, '2026-05-01 09:00:00', 4);
        $query = ['date_from' => '2026-05-01', 'date_to' => '2026-05-31', 'rating' => 4, 'employee_id' => $target->id, 'work_unit_id' => $targetUnit->id, 'response_status' => 'responded'];

        $response = $this->actingAs($admin)->get(route('admin.surveys.index', $query))->assertOk()
            ->assertSee($match->visit->guest_name)->assertSee('Pelayanan ramah')->assertDontSee('BELUM MERESPONS');
        $filters = SurveyFilters::fromValidated($query);
        $service = app(SurveyReportService::class);
        $this->assertSame(1, $service->summary($filters)['sent']);
        $this->assertSame(1, $service->rows($filters)->count());
        $this->assertStringContainsString('rating=4', $response->content());
    }

    public function test_pdf_export_is_audited_without_sensitive_metadata(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = Employee::factory()->create();
        $invitation = $this->invitation($employee, '2026-07-20 10:00:00', 2, 'Komentar rahasia');
        $query = ['date_from' => '2026-07-01', 'date_to' => '2026-07-31', 'rating' => 2, 'response_status' => 'responded'];

        $this->actingAs($admin)->get(route('admin.reports.surveys.pdf', $query))->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')->assertHeaderContains('Cache-Control', 'no-store');

        $audit = AuditLog::query()->where('action', 'report.surveys.exported')->sole();
        $this->assertSame(1, $audit->metadata['row_count']);
        $serialized = json_encode($audit->metadata, JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString($invitation->visit->guest_name, $serialized);
        $this->assertStringNotContainsString($invitation->visit->address, $serialized);
        $this->assertStringNotContainsString('Komentar rahasia', $serialized);
        $this->assertStringNotContainsString($invitation->visit->guest_whatsapp, $serialized);
    }

    public function test_filter_validation_pagination_and_query_string_are_preserved(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = Employee::factory()->create();
        foreach (range(1, 11) as $index) {
            $this->invitation($employee, '2026-06-'.str_pad((string) $index, 2, '0', STR_PAD_LEFT).' 10:00:00');
        }
        $query = ['date_from' => '2026-06-01', 'date_to' => '2026-06-30', 'response_status' => 'not_responded', 'per_page' => 10];

        $this->actingAs($admin)->get(route('admin.surveys.index', $query))->assertOk()
            ->assertSee('page=2')->assertSee('response_status=not_responded')->assertSee('per_page=10');

        $this->actingAs($admin)->from(route('admin.surveys.index'))->get(route('admin.surveys.index', [
            'date_from' => '2024-01-01', 'date_to' => '2025-01-01', 'rating' => 6,
            'response_status' => 'waiting', 'employee_id' => 999999, 'work_unit_id' => 999999,
        ]))->assertRedirect(route('admin.surveys.index'))->assertSessionHasErrors(['date_to', 'rating', 'response_status', 'employee_id', 'work_unit_id']);
    }

    public function test_pdf_export_is_rate_limited(): void
    {
        $admin = User::factory()->admin()->create();
        foreach (range(1, 10) as $attempt) {
            $this->actingAs($admin)->get(route('admin.reports.surveys.pdf'))->assertOk();
        }
        $this->actingAs($admin)->get(route('admin.reports.surveys.pdf'))->assertTooManyRequests();
    }

    private function invitation(Employee $employee, string $arrivedAt, ?int $rating = null, ?string $comment = null, SurveyInvitationStatus $status = SurveyInvitationStatus::Sent, VisitStatus $visitStatus = VisitStatus::Accepted): SurveyInvitation
    {
        $visit = Visit::factory()->for($employee)->create(['status' => $visitStatus, 'decided_at' => $visitStatus === VisitStatus::Pending ? null : $arrivedAt, 'arrived_at' => $arrivedAt]);
        $sent = $status !== SurveyInvitationStatus::Scheduled;
        $invitation = SurveyInvitation::query()->create([
            'visit_id' => $visit->id, 'token_hash' => hash('sha256', Str::random(64)), 'status' => $status,
            'scheduled_at' => $arrivedAt, 'sent_at' => $sent ? $arrivedAt : null,
        ]);
        if ($rating !== null) {
            SurveyResponse::query()->create(['survey_invitation_id' => $invitation->id, 'visit_id' => $visit->id, 'rating' => $rating, 'comment' => $comment, 'submitted_at' => $arrivedAt]);
            $invitation->update(['status' => SurveyInvitationStatus::Used, 'used_at' => $arrivedAt]);
        }

        return $invitation->fresh(['visit.employee.workUnit', 'response']);
    }
}
