<?php

namespace App\Services;

use App\Models\InviteCode;
use App\Models\Referral;
use App\Models\User;
use App\Traits\NormalizesPhone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Single source of truth for "create an MSAS account" — extracted from
 * Auth\RegisteredUserController so the mobile API registers accounts through
 * the exact same identifier rules, role/approval logic, and trial-start
 * behavior as the web form, instead of a second, drift-prone reimplementation.
 * Session handling, document uploads, and OTP/mail dispatch stay in each
 * caller since those genuinely differ between the web (session + file
 * uploads) and API (token-based) registration flows.
 */
class RegistrationService
{
    use NormalizesPhone;

    public const PUBLIC_ROLES = [
        'farmer', 'vet', 'agronomist', 'agro-dealer',
        'equipment-dealer', 'agribusiness-owner', 'cooperative',
        'government-agency', 'ngo', 'research-institution',
        'input-supplier', 'logistics-provider', 'investor', 'general-user',
    ];

    /**
     * Detects whether $identifier is an email or phone, normalizes phones,
     * and enforces the same "no duplicate account" rule as the web form.
     *
     * @return array{type: 'email'|'phone', value: string}
     * @throws \InvalidArgumentException if it's neither a valid email nor phone
     * @throws \RuntimeException if an account already exists for it
     */
    public function resolveIdentifier(string $identifier): array
    {
        $identifier = trim($identifier);
        $isEmail    = (bool) filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $isPhone    = ! $isEmail && $this->looksLikePhone($identifier);

        if (! $isEmail && ! $isPhone) {
            throw new \InvalidArgumentException(
                'Enter a valid email address or phone number (e.g. 08012345678 or +2348012345678).'
            );
        }

        if ($isEmail) {
            if (User::where('email', $identifier)->exists()) {
                throw new \RuntimeException('This email is already registered. Please sign in instead.');
            }
            return ['type' => 'email', 'value' => $identifier];
        }

        $normalized = $this->normalizePhone($identifier);
        if (User::where('phone', $normalized)->exists()) {
            throw new \RuntimeException('This phone number is already registered. Please sign in instead.');
        }

        return ['type' => 'phone', 'value' => $normalized];
    }

    /**
     * Creates the user (+ invite/referral redemption + 14-day trial start) in
     * one transaction. $data keys mirror the web form's fields: first_name,
     * middle_name, last_name, role, country, state, lga, ward, password,
     * invite_code, ref, plan.
     *
     * $withinTransaction, if given, runs inside the same transaction after
     * the trial starts — e.g. the web form's document-upload storage, which
     * must roll back together with account creation on failure rather than
     * leaving a half-created account with no documents.
     *
     * @return array{0: User, 1: string, 2: bool} [$user, $pendingPlan, $needsApproval]
     */
    public function createAccount(array $data, string $identifierType, string $identifierValue, ?callable $withinTransaction = null): array
    {
        $role          = in_array($data['role'] ?? null, self::PUBLIC_ROLES, true) ? $data['role'] : 'farmer';
        $needsApproval = ! in_array($role, ['farmer', 'general-user'], true);

        $userData = [
            'first_name'         => $data['first_name'],
            'middle_name'        => $data['middle_name'] ?? null,
            'last_name'          => $data['last_name'],
            'role'               => $role,
            'country'            => $data['country'] ?: 'Nigeria',
            'state'              => $data['state'] ?? null,
            'lga'                => $data['lga'] ?? null,
            'ward'               => $data['ward'] ?? null,
            'password'           => Hash::make($data['password']),
            'application_status' => $needsApproval ? 'pending' : 'approved',
            'is_active'          => ! $needsApproval,
        ];

        if ($identifierType === 'email') {
            $userData['email'] = $identifierValue;
        } else {
            $userData['phone'] = $identifierValue;
            // Never mark a phone number verified here — no SMS OTP has been
            // sent or confirmed at this point.
            $userData['phone_verified_at'] = null;
        }

        $user = null;
        $pendingPlan = DB::transaction(function () use ($data, $userData, $role, $withinTransaction, &$user) {
            $user = User::create($userData);

            if ($role === 'farmer' && ! empty($data['invite_code'])) {
                $inviteCode = InviteCode::where('code', strtoupper(trim($data['invite_code'])))->first();
                if ($inviteCode && $inviteCode->isValid()) {
                    $inviteCode->increment('used_count');
                    $user->update(['is_pilot' => true]);
                    $data['plan'] = $inviteCode->plan;
                }
            }

            if ($role === 'farmer' && ! empty($data['ref'])) {
                $referrer = User::where('referral_code', strtoupper(trim($data['ref'])))->first();
                if ($referrer && $referrer->id !== $user->id) {
                    Referral::firstOrCreate(['referrer_id' => $referrer->id, 'referred_id' => $user->id]);
                }
            }

            $pendingPlan = $data['plan'] ?? '';
            $validPlans  = array_keys(config('subscription.plans', []));
            if (! in_array($pendingPlan, $validPlans, true)) {
                $pendingPlan = '';
            }

            // Every role that uses the subscription system gets its 14-day
            // trial automatically at registration, available from day one.
            if ($role !== 'general-user') {
                $defaultPlan = $role === 'farmer' ? 'basic' : 'professional_starter';
                $user->startTrial($pendingPlan ?: $defaultPlan);
            }

            if ($withinTransaction) {
                $withinTransaction($user, $role);
            }

            return $pendingPlan;
        });

        return [$user, $pendingPlan, $needsApproval];
    }
}
