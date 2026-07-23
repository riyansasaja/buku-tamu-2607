<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVisitDecisionRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $reason = is_string($this->input('decision_reason')) ? trim($this->input('decision_reason')) : null;
        $this->merge(['decision_reason' => $reason === '' ? null : $reason]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'decision' => ['required', Rule::in(['accepted', 'rejected'])],
            'decision_reason' => ['nullable', 'string', 'required_if:decision,rejected', 'min:3', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'decision' => 'keputusan',
            'decision_reason' => 'alasan penolakan',
        ];
    }
}
