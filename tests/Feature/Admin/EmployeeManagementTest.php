<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_can_create_update_and_deactivate_reference_data(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.work-units.store'), [
            'name' => 'Kepaniteraan',
            'sort_order' => 10,
            'is_active' => true,
        ])->assertRedirect(route('admin.work-units.index'));

        $this->actingAs($admin)->post(route('admin.positions.store'), [
            'name' => 'Panitera Muda Hukum',
            'sort_order' => 20,
            'is_active' => true,
        ])->assertRedirect(route('admin.positions.index'));

        $workUnit = WorkUnit::query()->where('name', 'Kepaniteraan')->firstOrFail();
        $position = Position::query()->where('name', 'Panitera Muda Hukum')->firstOrFail();

        $this->actingAs($admin)->put(route('admin.work-units.update', $workUnit), [
            'name' => 'Kepaniteraan Perkara',
            'sort_order' => 5,
            'is_active' => true,
        ])->assertRedirect(route('admin.work-units.index'));

        $this->actingAs($admin)->patch(route('admin.positions.status', $position), [
            'is_active' => false,
        ])->assertRedirect();

        $this->assertDatabaseHas('work_units', ['id' => $workUnit->id, 'name' => 'Kepaniteraan Perkara', 'sort_order' => 5]);
        $this->assertDatabaseHas('positions', ['id' => $position->id, 'is_active' => false]);
    }

    public function test_reference_names_are_unique_and_validation_is_shown_per_field(): void
    {
        $admin = User::factory()->admin()->create();
        WorkUnit::factory()->create(['name' => 'Kesekretariatan']);
        Position::factory()->create(['name' => 'Sekretaris']);

        $this->actingAs($admin)->from(route('admin.work-units.create'))->post(route('admin.work-units.store'), [
            'name' => 'Kesekretariatan',
            'sort_order' => 0,
            'is_active' => true,
        ])->assertRedirect(route('admin.work-units.create'))->assertSessionHasErrors('name');

        $this->actingAs($admin)->from(route('admin.positions.create'))->post(route('admin.positions.store'), [
            'name' => 'Sekretaris',
            'sort_order' => 0,
            'is_active' => true,
        ])->assertRedirect(route('admin.positions.create'))->assertSessionHasErrors('name');
    }

    public function test_admin_can_create_employee_with_nullable_identifier_and_encrypted_contact(): void
    {
        $admin = User::factory()->admin()->create();
        $workUnit = WorkUnit::factory()->create();
        $position = Position::factory()->create();

        $payload = [
            'employee_no' => '',
            'name' => 'Rina Manoppo',
            'work_unit_id' => $workUnit->id,
            'position_id' => $position->id,
            'notification_contact' => '081234567890',
            'is_active' => true,
        ];

        $this->actingAs($admin)->post(route('admin.employees.store'), $payload)
            ->assertRedirect(route('admin.employees.index'));
        $this->actingAs($admin)->post(route('admin.employees.store'), [...$payload, 'name' => 'Dina Manoppo', 'notification_contact' => ''])
            ->assertRedirect(route('admin.employees.index'));

        $employee = Employee::query()->where('name', 'Rina Manoppo')->firstOrFail();
        $rawContact = DB::table('employees')->where('id', $employee->id)->value('notification_contact');

        $this->assertNull($employee->employee_no);
        $this->assertSame('081234567890', $employee->notification_contact);
        $this->assertIsString($rawContact);
        $this->assertNotSame('081234567890', $rawContact);
        $this->assertArrayNotHasKey('notification_contact', $employee->toArray());
        $this->assertSame(2, Employee::query()->whereNull('employee_no')->count());
    }

    public function test_employee_identifier_must_be_unique_but_can_be_retained_on_update(): void
    {
        $admin = User::factory()->admin()->create();
        $employee = Employee::factory()->create(['employee_no' => '198001012000011001']);
        $other = Employee::factory()->create(['employee_no' => '198001012000011002']);

        $validPayload = [
            'employee_no' => $employee->employee_no,
            'name' => 'Nama Diperbarui',
            'work_unit_id' => $employee->work_unit_id,
            'position_id' => $employee->position_id,
            'notification_contact' => null,
            'is_active' => true,
        ];

        $this->actingAs($admin)->put(route('admin.employees.update', $employee), $validPayload)
            ->assertRedirect(route('admin.employees.index'));

        $this->actingAs($admin)->from(route('admin.employees.edit', $employee))->put(route('admin.employees.update', $employee), [
            ...$validPayload,
            'employee_no' => $other->employee_no,
        ])->assertRedirect(route('admin.employees.edit', $employee))->assertSessionHasErrors('employee_no');
    }

    public function test_new_employee_can_only_select_active_references(): void
    {
        $admin = User::factory()->admin()->create();
        $inactiveWorkUnit = WorkUnit::factory()->inactive()->create();
        $inactivePosition = Position::factory()->inactive()->create();

        $this->actingAs($admin)->post(route('admin.employees.store'), [
            'name' => 'Pegawai Baru',
            'employee_no' => null,
            'work_unit_id' => $inactiveWorkUnit->id,
            'position_id' => $inactivePosition->id,
            'notification_contact' => null,
            'is_active' => true,
        ])->assertSessionHasErrors(['work_unit_id', 'position_id']);

        $this->assertDatabaseMissing('employees', ['name' => 'Pegawai Baru']);
    }

    public function test_historical_inactive_relations_remain_readable_and_can_be_retained_on_edit(): void
    {
        $admin = User::factory()->admin()->create();
        $workUnit = WorkUnit::factory()->inactive()->create(['name' => 'Unit Lama']);
        $position = Position::factory()->inactive()->create(['name' => 'Jabatan Lama']);
        $employee = Employee::factory()->create([
            'work_unit_id' => $workUnit->id,
            'position_id' => $position->id,
            'name' => 'Pegawai Historis',
        ]);

        $this->actingAs($admin)->get(route('admin.employees.edit', $employee))
            ->assertOk()->assertSee('Unit Lama')->assertSee('Jabatan Lama')->assertSee('(nonaktif)');

        $this->actingAs($admin)->put(route('admin.employees.update', $employee), [
            'employee_no' => $employee->employee_no,
            'name' => 'Pegawai Historis Diperbarui',
            'work_unit_id' => $workUnit->id,
            'position_id' => $position->id,
            'notification_contact' => null,
            'is_active' => true,
        ])->assertRedirect(route('admin.employees.index'));

        $employee->refresh();
        $this->assertSame('Unit Lama', $employee->workUnit->name);
        $this->assertSame('Jabatan Lama', $employee->position->name);
    }

    public function test_employee_active_scope_and_status_lifecycle_preserve_the_record(): void
    {
        $admin = User::factory()->admin()->create();
        $active = Employee::factory()->create(['name' => 'Pegawai Aktif']);
        Employee::factory()->inactive()->create(['name' => 'Pegawai Nonaktif']);

        $this->assertSame(['Pegawai Aktif'], Employee::query()->active()->pluck('name')->all());

        $this->actingAs($admin)->patch(route('admin.employees.status', $active), ['is_active' => false])->assertRedirect();

        $this->assertDatabaseHas('employees', ['id' => $active->id, 'is_active' => false]);
        $this->assertSame(0, Employee::query()->active()->count());
    }

    public function test_employee_list_supports_search_status_sorting_and_pagination(): void
    {
        $admin = User::factory()->admin()->create();
        $workUnit = WorkUnit::factory()->create(['name' => 'Kepaniteraan']);
        $position = Position::factory()->create(['name' => 'Panitera']);
        Employee::factory()->create(['name' => 'Budi', 'work_unit_id' => $workUnit->id, 'position_id' => $position->id]);
        Employee::factory()->inactive()->create(['name' => 'Andi', 'work_unit_id' => $workUnit->id, 'position_id' => $position->id]);
        Employee::factory()->count(14)->create(['work_unit_id' => $workUnit->id, 'position_id' => $position->id]);

        $this->actingAs($admin)->get(route('admin.employees.index', ['q' => 'Kepaniteraan', 'status' => 'active', 'sort' => 'name', 'direction' => 'asc']))
            ->assertOk()->assertSee('Budi')->assertDontSee('Andi');

        $this->actingAs($admin)->get(route('admin.employees.index', ['status' => 'inactive']))
            ->assertOk()->assertSee('Andi')->assertDontSee('Budi');

        $this->actingAs($admin)->get(route('admin.employees.index'))
            ->assertOk()->assertSee('page=2');
    }

    public function test_guest_and_non_admin_cannot_manage_employee_master_data(): void
    {
        $employeeUser = User::factory()->create(['role' => UserRole::Employee]);

        $this->get(route('admin.employees.index'))->assertRedirect(route('login'));
        $this->actingAs($employeeUser)->get(route('admin.employees.index'))->assertRedirect(route('login'));

        $this->assertFalse(Gate::forUser($employeeUser)->allows('create', Employee::class));
        $this->assertFalse(Gate::forUser($employeeUser)->allows('create', WorkUnit::class));
        $this->assertFalse(Gate::forUser($employeeUser)->allows('create', Position::class));
    }

    public function test_permanent_delete_routes_are_not_registered(): void
    {
        $routes = app('router')->getRoutes();

        $this->assertNull($routes->getByName('admin.employees.destroy'));
        $this->assertNull($routes->getByName('admin.work-units.destroy'));
        $this->assertNull($routes->getByName('admin.positions.destroy'));
    }
}
