<?php

namespace App\Http\Requests\Admin;

use App\Support\WhatsAppNumber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true && $this->user()->isActive();
    }

    protected function prepareForValidation(): void
    {
        $number = WhatsAppNumber::normalize((string) $this->input('whatsapp'));
        $this->merge(['name' => trim((string) $this->input('name')), 'email' => mb_strtolower(trim((string) $this->input('email'))), 'whatsapp' => $number, 'whatsapp_hash' => $number ? hash('sha256', $number) : null]);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'whatsapp' => ['required', 'string', 'max:20', fn (string $attribute, mixed $value, \Closure $fail) => WhatsAppNumber::isValid((string) $value) ?: $fail('Nomor WhatsApp tidak valid.')],
            'whatsapp_hash' => ['required', 'string', Rule::unique('users', 'whatsapp_hash')],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
