<?php

namespace App\Http\Requests\Api;

use App\Services\RegistrationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Mirrors Auth\RegisteredUserController's web validation rules exactly —
 * same field names, same 'identifier' (email-or-phone) field, same password
 * policy — so the mobile app is a client of the same registration rules,
 * not a separate, looser implementation. Identifier type detection and
 * uniqueness checks happen in RegistrationService (used by both the web
 * controller and Api\AuthApiController), not duplicated here.
 */
class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'first_name'  => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name'   => ['required', 'string', 'max:255'],
            'identifier'  => ['required', 'string', 'max:255'],
            'password'    => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'role'        => ['nullable', 'string', 'in:' . implode(',', RegistrationService::PUBLIC_ROLES)],
            'country'     => ['nullable', 'string', 'max:100'],
            'state'       => ['nullable', 'string', 'max:100'],
            'lga'         => ['nullable', 'string', 'max:100'],
            'invite_code' => ['nullable', 'string', 'max:20'],
            'ref'         => ['nullable', 'string', 'max:20'],
            'plan'        => ['nullable', 'string', 'max:50'],
            'language'    => ['sometimes', 'string', 'in:en,ha'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'last_name.required'  => 'Last name is required.',
            'identifier.required' => 'Enter your email address or phone number.',
            'password.confirmed'  => 'Password and confirmation do not match.',
        ];
    }
}
