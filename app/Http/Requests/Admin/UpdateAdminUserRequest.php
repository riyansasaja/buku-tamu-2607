<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Validation\Rule;

class UpdateAdminUserRequest extends StoreAdminUserRequest
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $rules = parent::rules();
        $user = $this->route('user');
        $userId = $user instanceof User ? $user->id : null;
        $rules['email'] = ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)];
        $rules['whatsapp_hash'] = ['required', 'string', Rule::unique('users', 'whatsapp_hash')->ignore($userId)];

        return $rules;
    }
}
