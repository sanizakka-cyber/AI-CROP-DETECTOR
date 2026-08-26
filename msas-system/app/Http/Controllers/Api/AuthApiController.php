<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Mail\ApplicationReceivedMail;
use App\Mail\WelcomeMail;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\OtpService;
use App\Services\RegistrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthApiController extends Controller
{
    public function __construct(private RegistrationService $registration, private OtpService $otp) {}

    public function login(LoginRequest $request): JsonResponse
    {
        // Normalized to E.164 by LoginRequest::prepareForValidation() unless
        // it's an email — mirrors the web login's identifier detection so a
        // web-registered email-only account can also sign in from the app.
        $identifier = $request->phone;
        $loginType  = filter_var($identifier, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        // Same throttling shape as the web login (Auth\LoginRequest): 5/IP,
        // 20/identifier over 15 minutes — the API had no brute-force guard
        // at all before this, unlike the web form.
        $ipKey   = Str::transliterate(Str::lower($identifier)) . '|' . $request->ip();
        $userKey = 'login_user:' . Str::transliterate(Str::lower($identifier));

        if (RateLimiter::tooManyAttempts($ipKey, 5) || RateLimiter::tooManyAttempts($userKey, 20)) {
            $seconds = max(RateLimiter::availableIn($ipKey), RateLimiter::availableIn($userKey));

            // 429, not the default-422 ValidationException — "too many
            // attempts" isn't a validation problem, and the mobile app
            // distinguishes status codes to show the right message. Uses
            // 'error' (not 'message') to match this app's global API error
            // shape from bootstrap/app.php's exception renderers.
            return response()->json([
                'error' => 'Too many login attempts. Please try again in ' . ceil($seconds / 60) . ' minute(s).',
            ], 429);
        }

        $user = User::where($loginType, $identifier)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            RateLimiter::hit($ipKey);
            RateLimiter::hit($userKey, 900);
            AuditLog::record('login.failed', 'User', $user?->id, ['login_type' => $loginType, 'channel' => 'mobile']);

            // 401, not the default-422 ValidationException — bad credentials
            // are an authentication failure, not a request-validation one.
            return response()->json([
                'error' => 'Email/phone or password is incorrect.',
            ], 401);
        }

        RateLimiter::clear($ipKey);
        RateLimiter::clear($userKey);

        if (! $user->is_active) {
            return response()->json(['error' => 'Account is suspended. Please contact support.'], 403);
        }

        $user->update(['last_seen' => now()]);

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->userPayload($user),
        ]);
    }

    /**
     * Registers through the exact same RegistrationService the web form
     * uses (identifier detection, uniqueness, role/approval, trial start),
     * then mirrors the web's post-registration state machine:
     *  - non-public role  -> pending approval, no token yet
     *  - phone identifier -> logged in immediately (no OTP; SMS isn't wired
     *    up in production, and web doesn't block phone accounts on it either)
     *  - email identifier -> OTP sent, no token until verify-otp succeeds
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            ['type' => $identifierType, 'value' => $identifier] = $this->registration->resolveIdentifier($request->identifier);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            throw ValidationException::withMessages(['identifier' => [$e->getMessage()]]);
        }

        [$user, $pendingPlan, $needsApproval] = $this->registration->createAccount(
            [
                'first_name'  => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name'   => $request->last_name,
                'role'        => $request->role,
                'country'     => $request->country,
                'state'       => $request->state,
                'lga'         => $request->lga,
                'password'    => $request->password,
                'invite_code' => $request->invite_code,
                'ref'         => $request->ref,
                'plan'        => $request->input('plan', ''),
            ],
            $identifierType,
            $identifier
        );

        if ($request->filled('language')) {
            $user->update(['language' => $request->language]);
        }

        if ($needsApproval) {
            if ($identifierType === 'email') {
                try {
                    Mail::to($user->email)->send(new ApplicationReceivedMail($user));
                } catch (\Exception $e) {
                    Log::error('ApplicationReceivedMail failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                }
            }

            return response()->json([
                'needs_approval' => true,
                'message'        => 'Your application has been submitted and is pending review. We\'ll notify you once approved.',
            ], 201);
        }

        if ($identifierType === 'phone') {
            $user->update(['last_seen' => now()]);
            $token = $user->createToken('mobile')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user'  => $this->userPayload($user),
            ], 201);
        }

        // Email identifier: same OTP-before-access flow as the web form —
        // no token issued until POST /auth/verify-otp succeeds.
        $plain     = $this->otp->generate($identifier, 'registration');
        $verifyUrl = URL::temporarySignedRoute(
            'verification.link',
            now()->addMinutes(OtpService::TTL_MINUTES),
            ['user' => $user->id]
        );
        $emailFailed = ! $this->otp->sendViaEmail($identifier, $plain, $user->first_name, $user->id, 'registration', $verifyUrl);

        try {
            AuditLog::create([
                'user_id'  => $user->id,
                'action'   => 'otp.sent',
                'model'    => 'User',
                'model_id' => $user->id,
                'details'  => json_encode(['context' => 'registration', 'delivered' => ! $emailFailed, 'channel' => 'mobile']),
            ]);
        } catch (\Throwable) {}

        return response()->json([
            'needs_verification' => true,
            'identifier'         => $identifier,
            'pending_plan'       => $pendingPlan,
            'email_send_failed'  => $emailFailed,
            'message'            => $emailFailed
                ? 'Account created, but we could not send the verification email. Use "Resend code" to try again.'
                : 'Account created! Enter the 6-digit code we emailed you to verify your account.',
        ], 201);
    }

    /** POST /auth/verify-otp — confirms the code, then issues the login token (mirrors web's Auth::login after OTP success). */
    public function verifyOtp(Request $request): JsonResponse
    {
        $request->validate([
            'identifier' => ['required', 'string'],
            'code'       => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ]);

        $identifier = trim($request->identifier);

        try {
            $this->otp->verify($identifier, 'registration', trim($request->code));
        } catch (\Throwable $e) {
            throw ValidationException::withMessages(['code' => [$e->getMessage()]]);
        }

        $user = User::where('email', $identifier)->first();
        if (! $user) {
            throw ValidationException::withMessages(['identifier' => ['Account not found.']]);
        }

        $user->update(['email_verified_at' => now(), 'is_verified' => true, 'last_seen' => now()]);

        try {
            AuditLog::record('otp.verified', 'User', $user->id, ['channel' => 'mobile']);
        } catch (\Throwable) {}

        if ($user->role === 'farmer') {
            try {
                Mail::to($user->email)->send(new WelcomeMail($user));
            } catch (\Exception $e) {
                Log::error('WelcomeMail failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->userPayload($user),
        ]);
    }

    /** POST /auth/resend-otp */
    public function resendOtp(Request $request): JsonResponse
    {
        $request->validate(['identifier' => ['required', 'string']]);
        $identifier = trim($request->identifier);

        $user = User::where('email', $identifier)->first();
        if (! $user) {
            throw ValidationException::withMessages(['identifier' => ['Account not found.']]);
        }

        $plain     = $this->otp->generate($identifier, 'registration');
        $verifyUrl = URL::temporarySignedRoute(
            'verification.link',
            now()->addMinutes(OtpService::TTL_MINUTES),
            ['user' => $user->id]
        );
        $failed = ! $this->otp->sendViaEmail($identifier, $plain, $user->first_name, $user->id, 'registration', $verifyUrl);

        try {
            AuditLog::record('otp.resent', 'User', $user->id, ['channel' => 'mobile']);
        } catch (\Throwable) {}

        if ($failed) {
            throw ValidationException::withMessages(['identifier' => ['We couldn\'t resend the code. Please try again shortly.']]);
        }

        return response()->json(['message' => 'A new verification code has been sent.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    public function logout(Request $request): JsonResponse
    {
        // api_token excluded from $fillable — use direct assignment so the token is actually revoked
        $user = $request->user();
        $user->api_token       = null;
        $user->expo_push_token = null;
        $user->saveQuietly();
        return response()->json(['message' => 'Logged out']);
    }

    /** PATCH /auth/profile */
    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'first_name'  => 'sometimes|string|max:100',
            'middle_name' => 'sometimes|nullable|string|max:100',
            'last_name'   => 'sometimes|string|max:100',
            'email'       => 'sometimes|nullable|email|unique:users,email,' . $request->user()->id,
            'phone'       => 'sometimes|nullable|string|max:20',
            'country'     => 'sometimes|nullable|string|max:100',
            'state'       => 'sometimes|nullable|string|max:100',
            'lga'         => 'sometimes|nullable|string|max:100',
            'ward'        => 'sometimes|nullable|string|max:150',
            'village'     => 'sometimes|nullable|string|max:100',
            'language'    => 'sometimes|nullable|string|max:10',
        ]);

        $user = $request->user();
        $updates = $request->only([
            'first_name', 'middle_name', 'last_name', 'email', 'phone',
            'country', 'state', 'lga', 'ward', 'village', 'language',
        ]);

        // Changing the email address invalidates the old verification —
        // same rule as the web profile form (ProfileController::update).
        if (array_key_exists('email', $updates) && $updates['email'] !== $user->email) {
            $updates['email_verified_at'] = null;
        }

        $user->update($updates);

        return response()->json(['user' => $this->userPayload($user->fresh())]);
    }

    /** POST /auth/fcm-token */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $request->validate([
            'expo_push_token' => 'nullable|string|max:255',
            'fcm_token'       => 'nullable|string|max:255',
        ]);

        $request->user()->update(array_filter([
            'expo_push_token' => $request->expo_push_token,
            'fcm_token'       => $request->fcm_token,
        ], fn($v) => $v !== null));

        return response()->json(['message' => 'Push token updated.']);
    }

    private function userPayload(User $user): array
    {
        // Role display name comes from User::getRoleLabelAttribute() below
        // (the same accessor the web app uses) — not duplicated here.
        return [
            'id'                 => $user->id,
            'name'               => $user->name,
            'display_first_name' => $user->displayFirstName,
            'first_name'         => $user->first_name,
            'middle_name'        => $user->middle_name,
            'last_name'          => $user->last_name,
            'phone'              => $user->phone,
            'email'              => $user->email,
            'role'               => $user->role,
            'role_label'         => $user->roleLabel,
            'role_display'       => $user->roleLabel,
            'language'           => $user->language,
            'country'            => $user->country,
            'state'              => $user->state,
            'lga'                => $user->lga,
            'ward'               => $user->ward,
            'village'            => $user->village,
            'avatar_url'         => $user->avatarUrl,
            'profile_photo'      => $user->profile_photo,
            'is_verified'        => (bool) $user->is_verified,
            'is_active'          => (bool) $user->is_active,
            'last_seen'          => $user->last_seen,
            'department'         => $user->department ?? null,
            'job_title'          => $user->job_title ?? null,
            'created_at'         => $user->created_at,
        ];
    }
}
