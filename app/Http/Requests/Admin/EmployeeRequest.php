<?php

namespace App\Http\Requests\Admin;

use App\Models\Employee;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $employee = $this->route('employee');

        return $employee instanceof Employee
            ? $this->user()?->can('update', $employee) === true
            : $this->user()?->can('create', Employee::class) === true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $employee = $this->route('employee');
        $employeeId = $employee instanceof Employee ? $employee->getKey() : null;
        $currentWorkUnitId = $employee instanceof Employee ? $employee->work_unit_id : null;
        $currentPositionId = $employee instanceof Employee ? $employee->position_id : null;

        return [
            'employee_no' => ['nullable', 'string', 'max:50', Rule::unique('employees')->ignore($employeeId)],
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'work_unit_id' => [
                'required',
                'integer',
                Rule::exists('work_units', 'id')->where(
                    fn (Builder $query): Builder => $query
                        ->where('is_active', true)
                        ->when($currentWorkUnitId, fn (Builder $query): Builder => $query->orWhere('id', $currentWorkUnitId)),
                ),
            ],
            'position_id' => [
                'required',
                'integer',
                Rule::exists('positions', 'id')->where(
                    fn (Builder $query): Builder => $query
                        ->where('is_active', true)
                        ->when($currentPositionId, fn (Builder $query): Builder => $query->orWhere('id', $currentPositionId)),
                ),
            ],
            'notification_contact' => ['nullable', 'string', 'max:100'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'employee_no' => $this->nullableTrimmed('employee_no'),
            'name' => trim((string) $this->input('name')),
            'notification_contact' => $this->nullableTrimmed('notification_contact'),
        ]);
    }

    public function attributes(): array
    {
        return [
            'employee_no' => 'NIP/identifier',
            'name' => 'nama pegawai',
            'work_unit_id' => 'unit kerja',
            'position_id' => 'jabatan',
            'notification_contact' => 'kontak notifikasi',
            'is_active' => 'status aktif',
        ];
    }

    private function nullableTrimmed(string $key): ?string
    {
        $value = trim((string) $this->input($key));

        return $value === '' ? null : $value;
    }
}
