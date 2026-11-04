<?php

namespace App\Services;

use App\Models\LegalAcceptance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Terms/Privacy agreement, the minimum-age rule and the subscription
 * "no refund" acknowledgement, shared by the web and mobile API flows so
 * both enforce and record exactly the same thing.
 */
class LegalConsentService
{
    public function minimumAge(): int
    {
        return (int) config('legal.minimum_age', 18);
    }

    public function version(string $document): string
    {
        return (string) config("legal.versions.{$document}");
    }

    /**
     * Validation rules for registration. $required = false only for mobile
     * clients too old to send the fields, and only while
     * legal.require_api_consent is switched off.
     */
    public function registrationRules(bool $required = true): array
    {
        $min = $this->minimumAge();

        return $required
            ? [
                'terms_accepted' => ['accepted'],
                'age'            => ['required', 'integer', "min:{$min}", 'max:120'],
                'age_confirmed'  => ['accepted'],
            ]
            : [
                'terms_accepted' => ['sometimes', 'accepted'],
                'age'            => ['nullable', 'integer', "min:{$min}", 'max:120'],
                'age_confirmed'  => ['sometimes', 'accepted'],
            ];
    }

    public function registrationMessages(): array
    {
        return [
            'terms_accepted.accepted' => __('You must agree to the Terms of Service and Privacy Policy to continue.'),
            'age.required'            => __('Please enter your age.'),
            'age.integer'             => __('Please enter your age as a number, for example 35.'),
            'age.min'                 => $this->underAgeMessage(),
            'age.max'                 => __('Please enter a valid age.'),
            'age_confirmed.accepted'  => __('Please confirm that you are 18 years or older.'),
        ];
    }

    public function underAgeMessage(): string
    {
        return __('You must be 18 or older to create an MSAS FarmAI account. Please ask a parent or guardian to register instead.');
    }

    /** True when an age was given and is below the minimum. */
    public function isUnderAge(mixed $age): bool
    {
        return is_numeric($age) && (int) $age < $this->minimumAge();
    }

    /** Record Terms + Privacy agreement and the declared age for a new account. */
    public function recordRegistration(User $user, Request $request, string $context): void
    {
        $now   = now();
        $terms = $this->version('terms');
        $priv  = $this->version('privacy');

        $update = [];
        if ($request->boolean('terms_accepted')) {
            $update += [
                'terms_accepted_at' => $now,
                'terms_version'     => $terms,
                'privacy_version'   => $priv,
                'consent_given_at'  => $user->consent_given_at ?? $now,
            ];
        }
        if ($request->filled('age')) {
            $update += [
                'age_declared'    => (int) $request->input('age'),
                'age_declared_at' => $now,
            ];
        }
        if ($update) {
            $user->forceFill($update)->saveQuietly();
        }

        if ($request->boolean('terms_accepted')) {
            $this->log($user, 'terms', $terms, $context, $request);
            $this->log($user, 'privacy', $priv, $context, $request);
        }
        if ($request->boolean('age_confirmed')) {
            $this->log($user, 'age', 'min-' . $this->minimumAge(), $context, $request);
        }
    }

    /** Re-acceptance of new Terms/Privacy versions by an existing user. */
    public function recordReacceptance(User $user, Request $request): void
    {
        $now = now();
        $user->forceFill([
            'terms_accepted_at' => $now,
            'terms_version'     => $this->version('terms'),
            'privacy_version'   => $this->version('privacy'),
            'consent_given_at'  => $user->consent_given_at ?? $now,
        ])->saveQuietly();

        $this->log($user, 'terms', $this->version('terms'), 'reacceptance_web', $request);
        $this->log($user, 'privacy', $this->version('privacy'), 'reacceptance_web', $request);
    }

    /** Whether the user still has to accept the current Terms/Privacy. */
    public function needsReacceptance(User $user): bool
    {
        return $user->terms_version !== $this->version('terms')
            || $user->privacy_version !== $this->version('privacy');
    }

    /** Record the "subscription payments are non-refundable" acknowledgement. */
    public function recordRefundAcknowledgement(User $user, Request $request, string $reference, string $context): LegalAcceptance
    {
        return $this->log($user, 'refund', $this->version('refund'), $context, $request, $reference);
    }

    private function log(User $user, string $document, string $version, string $context, Request $request, ?string $reference = null): LegalAcceptance
    {
        return LegalAcceptance::create([
            'user_id'     => $user->id,
            'document'    => $document,
            'version'     => $version,
            'context'     => $context,
            'reference'   => $reference,
            'ip_address'  => $request->ip(),
            'user_agent'  => Str::limit((string) $request->userAgent(), 490, ''),
            'app_version' => Str::limit((string) ($request->header('X-App-Version') ?? $request->input('app_version', '')), 38, '') ?: null,
            'accepted_at' => now(),
        ]);
    }
}
