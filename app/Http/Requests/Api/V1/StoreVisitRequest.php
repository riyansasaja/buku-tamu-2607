<?php

namespace App\Http\Requests\Api\V1;

use App\Support\WhatsAppNumber;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreVisitRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'guest_name' => is_string($this->guest_name) ? trim($this->guest_name) : $this->guest_name,
            'address' => is_string($this->address) ? trim($this->address) : $this->address,
            'guest_whatsapp' => WhatsAppNumber::normalize(is_string($this->guest_whatsapp) ? $this->guest_whatsapp : null),
            'visit_purpose' => is_string($this->visit_purpose) ? trim($this->visit_purpose) : $this->visit_purpose,
            'idempotency_key' => $this->header('Idempotency-Key'),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'guest_name' => ['required', 'string', 'min:2', 'max:150'],
            'address' => ['required', 'string', 'min:5', 'max:500'],
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where(fn ($query) => $query->where('is_active', true)->whereNotNull('notification_contact')),
            ],
            'guest_whatsapp' => ['required', 'string', 'regex:/^[1-9][0-9]{8,14}$/'],
            'visit_purpose' => ['required', 'string', 'min:3', 'max:1000'],
            'photo' => [
                'required',
                File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(5 * 1024),
                'mimetypes:image/jpeg,image/png,image/webp',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $value instanceof UploadedFile || @getimagesize($value->getRealPath()) === false) {
                        $fail('Foto harus berupa gambar JPEG, PNG, atau WebP yang valid.');
                    }
                },
            ],
            'idempotency_key' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z0-9._:-]+$/'],
        ];
    }
}
