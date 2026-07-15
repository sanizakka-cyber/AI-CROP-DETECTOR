<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:160'],
            'phone'    => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:6'],
            'role'     => ['sometimes', 'string', 'in:farmer,vet,agronomist,agro-dealer,extension-officer'],
            'language' => ['sometimes', 'string', 'in:en,ha'],
            'state'    => ['sometimes', 'string', 'max:100'],
            'email'    => ['sometimes', 'nullable', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'A user with this phone number already exists.',
            'name.required' => 'Full name is required.',
        ];
    }
}
