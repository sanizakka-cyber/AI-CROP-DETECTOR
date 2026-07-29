<?php

namespace App\Http\Controllers\Auth;

use App\Exceptions\OtpExpiredException;
use App\Exceptions\OtpInvalidException;
use App\Exceptions\OtpLockedException;
use App\Http\Controllers\Controller;
use App\Mail\OtpLockedMail;
use App\Mail\WelcomeMail;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\OtpService;
use App\Traits\NormalizesPhone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class OtpVerificationController extends Controller
{
    use NormalizesPhone;

    public function __construct(private OtpService $otp) {}

    /** Show the OTP entry screen with no-store cache headers to prevent back-button replay. */
    public function show(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('otp_identifier')) {
            return redirect()->route('login');
        }

        $identifier = $request->session()->get('otp_identifier');
        $expiresAt  = $request->session()->get('otp_expires_at');

        return response()->view('auth.verify-otp', [
            'identifier'     => $identifier,
            'context'        => $request->session()->get('otp_context', 'registration'),
            'smsFailed'      => $request->session()->get('otp_sms_failed', false),
            'emailFailed'    => $request->session()->get('otp_email_failed', false),
            'deliveryMethod' => $request->session()->get('otp_delivery_method', 'email'),
            'expiresAt'      => $expiresAt,
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'Pragma'        => 'no-cache',
            'Expires'       => '0',
        ]);
    }

    /** Verify the submitted OTP. */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/']]);

        $identifier = $request->session()->get('otp_identifier');
        $context    = $request->session()->get('otp_context', 'registration');

        // Resolve userId from either registration or password_reset session key
        $userId = $request->session()->get('otp_user_id')
               ?? $request->session()->get('reset_user_id');

        if (! $identifier) {
            return redirect()->route('login')->withErrors(['code' => 'Session expired. Please start again.']);
        }

        try {
            $this->otp->verify($identifier, $context, $request->code);
        } catch (OtpLockedException $e) {
            $this->auditOtpEvent('otp.locked', $userId, $context, $identifier);
            $this->sendLockoutEmail($userId, request()->ip());
            return back()->withErrors(['code' => $e->getMessage()]);
        } catch (OtpExpiredException $e) {
            $this->auditOtpEvent('otp.expired', $userId, $context, $identifier);
            return back()->withErrors(['code' => $e->getMessage()]);
        } catch (OtpInvalidException $e) {
            $this->auditOtpEvent('otp.failed', $userId, $context, $identifier, ['attempts_left' => $e->attemptsLeft]);
            return back()->withErrors(['code' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            // "No pending OTP found" — session mismatch, no user to attribute to
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        if ($context === 'registration') {
            $pendingPlan = $request->session()->get('pending_plan', '');
            $user        = User::findOrFail($userId);

            $updates = ['email_verified_at' => now(), 'is_verified' => true];

            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                $user->update($updates);
            } else {
                $user->update(['phone_verified_at' => now(), 'is_verified' => true]);
            }

            $this->auditOtpEvent('otp.verified', $userId, $context, $identifier);

            $this->clearOtpSession($request);
            Auth::login($user);
            $request->session()->regenerate();

            if ($user->email && $user->role === 'farmer') {
                try {
                    Mail::to($user->email)->send(new WelcomeMail($user));
                } catch (\Exception $e) {
                    Log::error('WelcomeMail failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                }
            }

            if ($pendingPlan && $user->role === 'farmer') {
                return redirect()->route('subscription.plans')
                    ->with('status', 'Account verified! Choose a plan to start your free 14-day trial.');
            }

            return redirect()->route('dashboard')->with('status', 'Account verified! Welcome to MSAS.');
        }

        if ($context === 'password_reset') {
            $this->auditOtpEvent('otp.verified', $userId, $context, $identifier);

            $token = \Illuminate\Support\Str::random(64);
            $request->session()->put('reset_token', $token);
            $request->session()->forget('otp_context');

            return redirect()->route('password.reset.form');
        }

        return redirect()->route('login');
    }

    /** Cancel OTP verification, clear the session, and audit the cancellation. */
    public function cancel(Request $request): RedirectResponse
    {
        $context    = $request->session()->get('otp_context', 'registration');
        $identifier = $request->session()->get('otp_identifier', '');
        $userId     = $request->session()->get('otp_user_id')
                   ?? $request->session()->get('reset_user_id');

        if ($identifier) {
            $this->auditOtpEvent('otp.cancelled', $userId, $context, $identifier);
        }

        $this->clearOtpSession($request);

        return $context === 'password_reset'
            ? redirect()->route('password.request')
            : redirect()->route('register');
    }

    /** Resend OTP to the current identifier (same channel). */
    public function resend(Request $request): RedirectResponse
    {
        $identifier = $request->session()->get('otp_identifier');
        $context    = $request->session()->get('otp_context', 'registration');
        $method     = $request->session()->get('otp_delivery_method', 'email');
        $uid        = $request->session()->get('otp_user_id')
                   ?? $request->session()->get('reset_user_id');

        if (! $identifier) {
            return redirect()->route('login');
        }

        $firstName = 'User';
        if ($uid) {
            $firstName = User::find($uid)?->first_name ?? 'User';
        }

        $plain     = $this->otp->generate($identifier, $context);
        $expiresAt = $this->otp->expiresAt($identifier, $context);

        $failed = false;

        if ($method === 'sms') {
            $normalized = $this->looksLikePhone($identifier)
                ? $this->normalizePhone($identifier)
                : $identifier;
            $failed = ! $this->otp->sendViaSms($normalized, $plain, $uid, $context);
        } else {
            $failed = ! $this->otp->sendViaEmail($identifier, $plain, $firstName, $uid, $context);
        }

        $request->session()->put([
            'otp_sms_failed'   => $method === 'sms' && $failed,
            'otp_email_failed' => $method === 'email' && $failed,
            'otp_expires_at'   => $expiresAt?->toISOString(),
        ]);

        if ($uid) {
            $this->auditOtpEvent('otp.resent', $uid, $context, $identifier);
        }

        if ($failed) {
            return back()->withErrors(['code' => 'We couldn\'t resend the code. Please try again or switch to a different method.']);
        }

        return back()->with('status', 'A new verification code has been sent.');
    }

    /**
     * Switch from failed SMS to email verification.
     */
    public function switchToEmail(Request $request): RedirectResponse
    {
        $request->validate([
            'fallback_email' => 'required|email|max:255',
        ]);

        $userId  = $request->session()->get('otp_user_id');
        $context = $request->session()->get('otp_context', 'registration');

        if (! $userId) {
            return redirect()->route('login')->withErrors(['fallback_email' => 'Session expired. Please register again.']);
        }

        $user  = User::findOrFail($userId);
        $email = strtolower(trim($request->fallback_email));

        $taken = User::where('email', $email)->where('id', '!=', $user->id)->exists();
        if ($taken) {
            return back()->withErrors(['fallback_email' => 'That email address is already in use by another account.']);
        }

        $user->update(['email' => $email]);

        $plain     = $this->otp->generate($email, $context);
        $expiresAt = $this->otp->expiresAt($email, $context);
        $sent      = $this->otp->sendViaEmail($email, $plain, $user->first_name, $userId, $context);

        Log::info('OTP fallback to email', [
            'user_id'    => $user->id,
            'email_hint' => substr($email, 0, 3) . '***@' . explode('@', $email)[1],
            'sent'       => $sent,
        ]);

        $this->auditOtpEvent('otp.sent', $userId, $context, $email);

        $request->session()->put([
            'otp_identifier'      => $email,
            'otp_sms_failed'      => false,
            'otp_email_failed'    => ! $sent,
            'otp_delivery_method' => 'email',
            'otp_expires_at'      => $expiresAt?->toISOString(),
        ]);

        if (! $sent) {
            return redirect()->route('otp.verify')
                ->withErrors(['code' => 'Failed to send verification email. Please check the address and try again.']);
        }

        return redirect()->route('otp.verify')
            ->with('status', 'Verification code sent to ' . $email . '. Please check your inbox.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function clearOtpSession(Request $request): void
    {
        $request->session()->forget([
            'otp_identifier', 'otp_context', 'otp_user_id', 'reset_user_id',
            'otp_sms_failed', 'otp_email_failed',
            'otp_expires_at', 'otp_delivery_method', 'pending_plan',
        ]);
    }

    private function auditOtpEvent(string $action, ?int $userId, string $context, string $identifier, array $extra = []): void
    {
        try {
            AuditLog::create([
                'user_id'    => $userId,
                'action'     => $action,
                'model'      => 'User',
                'model_id'   => $userId,
                'details'    => json_encode(array_merge([
                    'context'         => $context,
                    'identifier_hint' => $this->hintIdentifier($identifier),
                ], $extra)),
                'ip_address' => request()->ip(),
            ]);
        } catch (\Throwable) {}
    }

    private function sendLockoutEmail(?int $userId, string $ip): void
    {
        if (! $userId) return;

        $user = User::find($userId);
        if (! $user || ! $user->email) return;

        try {
            Mail::to($user->notification_email ?? $user->email)->send(
                new OtpLockedMail($user, $ip, now()->format('M d, Y g:i A T'))
            );
        } catch (\Throwable $e) {
            Log::error('OtpLockedMail failed', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }

    private function hintIdentifier(string $id): string
    {
        if (str_contains($id, '@')) {
            [$local, $domain] = explode('@', $id, 2);
            return substr($local, 0, 2) . '***@' . $domain;
        }
        $clean = preg_replace('/\D/', '', $id);
        return substr($clean, 0, 3) . '***' . substr($clean, -3);
    }
}
