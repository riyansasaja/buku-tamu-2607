<?php

namespace App\Http\Requests\Admin;

use App\Models\Position;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $position = $this->route('position');

        return $position instanceof Position
            ? $this->user()?->can('update', $position) === true
            : $this->user()?->can('create', Position::class) === true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $position = $this->route('position');

        return [
            'name' => ['required', 'string', 'min:2', 'max:120', Rule::unique('positions')->ignore($position)],
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
            'name' => 'nama jabatan',
            'sort_order' => 'urutan tampil',
            'is_active' => 'status aktif',
        ];
    }
}
