<?php

namespace App\Http\Requests\Admin;

use App\Enums\VisitStatus;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class VisitFilterRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'q' => $this->filled('q') ? trim((string) $this->input('q')) : null,
            'date_from' => $this->filled('date_from') ? $this->input('date_from') : null,
            'date_to' => $this->filled('date_to') ? $this->input('date_to') : null,
            'status' => $this->filled('status') ? $this->input('status') : null,
            'employee_id' => $this->filled('employee_id') ? $this->input('employee_id') : null,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true && $this->user()->isActive();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'status' => ['nullable', Rule::enum(VisitStatus::class)],
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'q' => ['nullable', 'string', 'max:150'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->filled('date_from') || ! $this->filled('date_to')) {
                return;
            }

            $from = CarbonImmutable::createFromFormat('!Y-m-d', (string) $this->input('date_from'));
            $to = CarbonImmutable::createFromFormat('!Y-m-d', (string) $this->input('date_to'));
            if ($from !== null && $to !== null && $from->diffInDays($to) > 366) {
                $validator->errors()->add('date_to', 'Rentang tanggal maksimum adalah 366 hari.');
            }
        });
    }
}
