<?php

namespace App\Http\Requests\Api;

use App\Traits\NormalizesPhone;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    use NormalizesPhone;

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'phone'    => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    /**
     * The mobile login field is labeled "phone", but a user who registered
     * on the web with an email-only account has no phone number at all —
     * without this they could never log in from the app. Only normalize
     * when it isn't an email, so email identifiers pass through untouched
     * (mirrors the web login's identifier detection in Auth\LoginRequest).
     */
    protected function prepareForValidation(): void
    {
        $identifier = trim((string) $this->input('phone'));
        if ($identifier !== '' && ! filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $this->merge(['phone' => $this->normalizePhone($identifier)]);
        }
    }

    public function messages(): array
    {
        return [
            'phone.required'    => 'Phone number is required.',
            'password.required' => 'Password is required.',
        ];
    }
}
