<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $login     = trim($this->input('login'));
        $loginType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if ($loginType === 'phone') {
            $clean = preg_replace('/\D/', '', $login);
            if (str_starts_with($clean, '0')) {
                $clean = '234' . substr($clean, 1);
            }
            $login = '+' . ltrim($clean, '+');
        }

        $credentials = [
            $loginType => $login,
            'password' => $this->input('password'),
        ];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            // Per-identifier counter: locks out after 20 failures regardless of IP rotation
            RateLimiter::hit($this->throttleKeyByUser(), 900);

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->throttleKeyByUser());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        // Per-IP check (5 attempts)
        if (RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            event(new Lockout($this));
            $seconds = RateLimiter::availableIn($this->throttleKey());
            throw ValidationException::withMessages([
                'login' => trans('auth.throttle', [
                    'seconds' => $seconds,
                    'minutes' => ceil($seconds / 60),
                ]),
            ]);
        }

        // Per-identifier check (20 attempts across any IPs, 15-min lockout)
        if (RateLimiter::tooManyAttempts($this->throttleKeyByUser(), 20)) {
            $seconds = RateLimiter::availableIn($this->throttleKeyByUser());
            throw ValidationException::withMessages([
                'login' => 'Too many failed login attempts. Please try again in ' . ceil($seconds / 60) . ' minutes.',
            ]);
        }
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('login')).'|'.$this->ip());
    }

    public function throttleKeyByUser(): string
    {
        return 'login_user:' . Str::transliterate(Str::lower($this->string('login')));
    }
}
