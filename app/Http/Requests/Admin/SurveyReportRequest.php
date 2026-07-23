<?php

namespace App\Http\Requests\Admin;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SurveyReportRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        foreach (['date_from', 'date_to', 'rating', 'employee_id', 'work_unit_id', 'response_status', 'per_page'] as $key) {
            $this->merge([$key => $this->filled($key) ? $this->input($key) : null]);
        }
    }

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true && $this->user()->isActive() && $this->user()->activated_at !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],
            'work_unit_id' => ['nullable', 'integer', Rule::exists('work_units', 'id')],
            'response_status' => ['nullable', Rule::in(['responded', 'not_responded'])],
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
            if ($from !== null && $to !== null && $from->diffInDays($to) >= 366) {
                $validator->errors()->add('date_to', 'Rentang tanggal maksimum adalah 366 tanggal inklusif.');
            }
        });
    }
}
