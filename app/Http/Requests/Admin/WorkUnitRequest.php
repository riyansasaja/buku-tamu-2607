<?php

namespace App\Http\Requests\Admin;

use App\Models\WorkUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WorkUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workUnit = $this->route('work_unit');

        return $workUnit instanceof WorkUnit
            ? $this->user()?->can('update', $workUnit) === true
            : $this->user()?->can('create', WorkUnit::class) === true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $workUnit = $this->route('work_unit');

        return [
            'name' => ['required', 'string', 'min:2', 'max:120', Rule::unique('work_units')->ignore($workUnit)],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => trim((string) $this->input('name'))]);
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama unit kerja',
            'sort_order' => 'urutan tampil',
            'is_active' => 'status aktif',
        ];
    }
}
