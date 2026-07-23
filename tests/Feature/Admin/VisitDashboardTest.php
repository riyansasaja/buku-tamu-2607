<?php

namespace Tests\Feature\Admin;

use App\Enums\VisitStatus;
use App\Models\Employee;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class VisitDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Carbon::setTestNow(Carbon::parse('2026-07-23 12:00:00', 'Asia/Makassar'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_dashboard_defaults_to_current_makassar_day_and_reconciles_status_counts(): void
    {
        $admin = User::factory()->admin()->create();
        Visit::factory()->create(['status' => VisitStatus::Pending, 'arrived_at' => '2026-07-23 00:00:00']);
        Visit::factory()->create(['status' => VisitStatus::Accepted, 'arrived_at' => '2026-07-23 12:30:00']);
        Visit::factory()->create(['status' => VisitStatus::Rejected, 'arrived_at' => '2026-07-23 23:59:59']);
        Visit::factory()->create(['status' => VisitStatus::Accepted, 'arrived_at' => '2026-07-22 23:59:59']);
        Visit::factory()->create(['status' => VisitStatus::Pending, 'arrived_at' => '2026-07-24 00:00:00']);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('value="2026-07-23"', false)
            ->assertSee('data-testid="dashboard-total" class="mt-3 text-4xl font-bold text-white">3</p>', false)
            ->assertSee('data-testid="dashboard-accepted" class="mt-3 text-4xl font-bold text-white">1</p>', false)
            ->assertSee('data-testid="dashboard-rejected" class="mt-3 text-4xl font-bold text-white">1</p>', false)
            ->assertSee('data-testid="dashboard-pending" class="mt-3 text-4xl font-bold text-white">1</p>', false)
            ->assertSee(route('admin.visits.index', ['date_from' => '2026-07-23', 'date_to' => '2026-07-23', 'status' => 'accepted']));
    }

    public function test_dashboard_and_visit_list_use_identical_inclusive_period(): void
    {
        $admin = User::factory()->admin()->create();
        $inside = Visit::factory()->create(['guest_name' => 'Dalam Periode', 'arrived_at' => '2026-07-20 00:00:00']);
        Visit::factory()->create(['guest_name' => 'Di Luar Periode', 'arrived_at' => '2026-07-19 23:59:59']);
        $query = ['date_from' => '2026-07-20', 'date_to' => '2026-07-20'];

        $this->actingAs($admin)->get(route('admin.dashboard', $query))
            ->assertOk()->assertSee('data-testid="dashboard-total" class="mt-3 text-4xl font-bold text-white">1</p>', false);
        $this->actingAs($admin)->get(route('admin.visits.index', $query))
            ->assertOk()->assertSee($inside->guest_name)->assertDontSee('Di Luar Periode');
    }

    public function test_visit_list_filters_employee_status_search_and_preserves_query_on_pagination(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = Employee::factory()->create(['name' => 'Ketua Filter']);
        $other = Employee::factory()->create(['name' => 'Pegawai Lain']);
        Visit::factory()->create([
            'employee_id' => $employee->id,
            'status' => VisitStatus::Accepted,
            'guest_name' => 'Tamu Dicari',
            'visit_number' => 'FILTER-001',
            'arrived_at' => '2026-07-23 10:00:00',
        ]);
        Visit::factory()->create([
            'employee_id' => $other->id,
            'status' => VisitStatus::Accepted,
            'guest_name' => 'Tidak Sesuai',
            'arrived_at' => '2026-07-23 10:00:00',
        ]);
        Visit::factory()->count(21)->create([
            'employee_id' => $employee->id,
            'status' => VisitStatus::Accepted,
            'arrived_at' => '2026-07-23 11:00:00',
        ]);
        $query = [
            'employee_id' => $employee->id,
            'status' => 'accepted',
            'date_from' => '2026-07-23',
            'date_to' => '2026-07-23',
            'q' => 'FILTER-001',
        ];

        $this->actingAs($admin)->get(route('admin.visits.index', $query))
            ->assertOk()->assertSee('Tamu Dicari')->assertDontSee('Tidak Sesuai');

        unset($query['q']);
        $this->actingAs($admin)->get(route('admin.visits.index', $query))
            ->assertOk()->assertSee('page=2')->assertSee('status=accepted')->assertSee('employee_id='.$employee->id);
    }

    public function test_invalid_filters_are_rejected_without_running_unbounded_result(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->from(route('admin.dashboard'))->get(route('admin.dashboard', [
            'date_from' => '2026-07-23',
            'date_to' => '2025-01-01',
        ]))->assertRedirect(route('admin.dashboard'))->assertSessionHasErrors('date_to');

        $this->actingAs($admin)->from(route('admin.visits.index'))->get(route('admin.visits.index', [
            'status' => 'waiting',
            'employee_id' => 999999,
            'date_from' => '2020-01-01',
            'date_to' => '2026-07-23',
        ]))->assertRedirect(route('admin.visits.index'))->assertSessionHasErrors(['status', 'employee_id', 'date_to']);
    }
}
