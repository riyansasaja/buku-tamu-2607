<?php

namespace Tests\Feature\Admin;

use App\Data\VisitFilters;
use App\Enums\VisitStatus;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\User;
use App\Models\Visit;
use App\Services\VisitReportService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-23 12:00:00', 'Asia/Makassar'));
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_only_active_admin_can_export_visit_report(): void
    {
        $route = route('admin.reports.visits.pdf');

        $this->get($route)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get($route)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->admin()->inactive()->create())->get($route)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->admin()->create())->get($route)->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_pdf_export_uses_filters_and_records_sanitized_audit(): void
    {
        $admin = User::factory()->admin()->create();
        $target = Employee::factory()->create();
        $other = Employee::factory()->create();

        Visit::factory()->for($target)->create([
            'status' => VisitStatus::Accepted,
            'arrived_at' => CarbonImmutable::parse('2026-07-20 09:00', 'Asia/Makassar'),
            'guest_whatsapp' => '628111111111',
            'photo_path' => 'visits/private-photo.jpg',
        ]);
        Visit::factory()->for($target)->create([
            'status' => VisitStatus::Rejected,
            'arrived_at' => CarbonImmutable::parse('2026-07-20 10:00', 'Asia/Makassar'),
        ]);
        Visit::factory()->for($other)->create([
            'status' => VisitStatus::Accepted,
            'arrived_at' => CarbonImmutable::parse('2026-07-20 11:00', 'Asia/Makassar'),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.visits.pdf', [
            'date_from' => '2026-07-20',
            'date_to' => '2026-07-20',
            'status' => VisitStatus::Accepted->value,
            'employee_id' => $target->id,
        ]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
            ->assertHeader('X-Request-ID');
        $this->assertStringContainsString('attachment; filename="laporan-kunjungan-2026-07-20-sampai-2026-07-20.pdf"', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());

        $this->assertDatabaseHas('audit_logs', [
            'actor_type' => 'admin',
            'action' => 'report.visits.exported',
            'auditable_type' => User::class,
            'auditable_id' => $admin->id,
        ]);
        $audit = AuditLog::query()->where('action', 'report.visits.exported')->sole();
        $this->assertEqualsCanonicalizing([
            'date_from' => '2026-07-20',
            'date_to' => '2026-07-20',
            'status' => 'accepted',
            'employee_id' => $target->id,
            'row_count' => 1,
        ], $audit->metadata);
        $this->assertStringNotContainsString('628111111111', json_encode($audit->metadata, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('private-photo.jpg', json_encode($audit->metadata, JSON_THROW_ON_ERROR));
    }

    public function test_report_data_reconciles_summary_and_details_for_identical_filter(): void
    {
        $employee = Employee::factory()->create();
        Visit::factory()->count(2)->for($employee)->create([
            'status' => VisitStatus::Accepted,
            'arrived_at' => CarbonImmutable::parse('2026-07-23 08:00', 'Asia/Makassar'),
        ]);
        Visit::factory()->for($employee)->create([
            'status' => VisitStatus::Rejected,
            'arrived_at' => CarbonImmutable::parse('2026-07-23 09:00', 'Asia/Makassar'),
        ]);

        $filters = VisitFilters::fromValidated([
            'date_from' => '2026-07-23',
            'date_to' => '2026-07-23',
            'status' => 'accepted',
            'employee_id' => $employee->id,
        ]);
        $report = app(VisitReportService::class)->data($filters);

        $this->assertSame(2, $report['summary']['total']);
        $this->assertSame(2, $report['summary']['accepted']);
        $this->assertSame(2, $report['visits']->count());
    }

    public function test_invalid_or_excessive_report_period_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->from(route('admin.visits.index'))->get(route('admin.reports.visits.pdf', [
            'date_from' => '2024-01-01',
            'date_to' => '2026-07-23',
            'status' => 'unknown',
        ]))->assertRedirect(route('admin.visits.index'))->assertSessionHasErrors(['date_to', 'status']);
    }
}
