<?php

namespace App\Services\Scan;

use App\Models\ScanError;
use App\Models\User;
use App\Support\Roles;
use App\Support\Scan\ScanErrorCode as C;
use App\Support\Scan\ScanFailure;
use App\Support\SecretMasker;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

/**
 * The one place a failed scan is recorded and answered.
 *
 *  - Recorded in scan_errors with a reference (SCN-XXXXX) and the masked
 *    upstream message, and logged with the same reference.
 *  - The farmer gets a plain, translated message for the code, what to do
 *    next (retry / sign in / upgrade / choose another photo), the reference,
 *    and "Your scan was NOT counted" where that applies. Never raw exception
 *    text, URLs, key names or provider messages.
 *  - Staff roles (config scan_errors.staff_roles) also get `technical`:
 *    code, status, failing service, masked message, time and reference.
 *  - Repeated engine / provider / internal failures email the owner.
 */
class ScanErrorReporter
{
    public static function isScanRequest(Request $request): bool
    {
        return $request->is('diagnostics/analyze') || $request->is('api/diagnose/crop', 'api/diagnose/livestock', 'api/diagnose/soil', 'api/diagnose/pest');
    }

    public function respond(Request $request, ScanFailure $failure): Response
    {
        $error = $this->record($request, $failure);
        $payload = $this->payload($request, $failure, $error);
        $status = $payload['http_status'];
        unset($payload['http_status']);

        $web = !$request->is('api/*');
        if ($web && !$request->expectsJson()) {
            // Native form post (no JavaScript): back to the form with the message.
            if ($failure->code === C::SESSION_EXPIRED) {
                return redirect()->guest(route('login'))->with('status', $payload['error']);
            }
            // Field problems stay on their own fields; everything else is shown at the photo.
            $errors = $failure->code === C::VALIDATION_FAILED && !empty($payload['details'])
                ? $payload['details']
                : ['image' => $payload['error'] . ' ' . $payload['reference_label']];
            return back()->withInput($request->except('image'))->withErrors($errors)->with('scan_error', $payload);
        }

        $response = response()->json($payload, $status);
        if ($failure->code === C::TOO_MANY_SCANS) {
            $response->headers->set('Retry-After', '60');
        }
        return $response;
    }

    public function record(Request $request, ScanFailure $failure): ScanError
    {
        $user = $request->user();
        $meta = C::meta($failure->code);
        $detail = SecretMasker::mask($failure->detail);
        $error = ScanError::create([
            'reference'       => ScanError::newReference(),
            'code'            => $failure->code,
            'user_id'         => $user?->id,
            'user_role'       => $user?->role,
            'channel'         => $request->is('api/*') ? 'api' : 'web',
            'scan_type'       => $this->scanType($request),
            'service'         => $failure->service(),
            'http_status'     => $meta['http'],
            'upstream_status' => $failure->upstreamStatus,
            'detail'          => $detail,
            'context'         => array_filter([
                'path'        => $request->path(),
                'field'       => $failure->extra['field'] ?? null,
                'locale'      => $this->locale($request),
                'app_version' => $request->header('X-App-Version'),
            ]),
        ]);
        Log::warning('Scan failed', [
            'reference' => $error->reference, 'code' => $error->code, 'service' => $error->service,
            'upstream_status' => $error->upstream_status, 'user_id' => $error->user_id, 'detail' => $detail,
        ]);
        if ($meta['alert']) {
            $this->maybeAlert();
        }
        return $error;
    }

    public function payload(Request $request, ScanFailure $failure, ScanError $error): array
    {
        $meta = C::meta($failure->code);
        $locale = $this->locale($request);
        $replace = [];
        if ($failure->code === C::SCAN_QUOTA_EXCEEDED) {
            $replace = [
                'limit' => $failure->extra['limit'] ?? '',
                'date'  => Carbon::now()->startOfMonth()->addMonth()->locale($locale)->isoFormat('D MMMM YYYY'),
            ];
        }
        if ($failure->code === C::VALIDATION_FAILED) {
            $replace = ['field' => __($this->fieldLabel($failure->extra['field'] ?? ''), [], $locale)];
        }
        $message = __($meta['message'], $replace, $locale);

        $payload = [
            // `error` stays a plain string: app builds before this change show it as-is.
            'error'           => $message,
            'message'         => $message,
            'error_code'      => $failure->code,
            'reference'       => $error->reference,
            'reference_label' => __('Reference: :ref', ['ref' => $error->reference], $locale),
            'action'          => $meta['action'],
            'action_url'      => match ($meta['action']) {
                'sign_in' => route('login'),
                'upgrade' => route('subscription.plans'),
                default   => null,
            },
            'scan_counted'    => false,
            'http_status'     => $meta['http'],
        ];
        if ($failure->code === C::VALIDATION_FAILED && !empty($failure->extra['details'])) {
            $payload['details'] = $failure->extra['details'];
            $payload['field'] = $failure->extra['field'] ?? null;
        }
        if ($failure->code === C::SCAN_QUOTA_EXCEEDED && isset($failure->extra['scan_limit'])) {
            $payload['scan_limit'] = $failure->extra['scan_limit'];
        }
        if ($this->isStaff($request->user())) {
            $payload['technical'] = [
                'code'            => $failure->code,
                'http_status'     => $failure->upstreamStatus ?? $meta['http'],
                'service'         => $failure->service(),
                'service_label'   => C::SERVICE_LABELS[$failure->service()] ?? $failure->service(),
                'message'         => $error->detail,
                'timestamp'       => $error->created_at?->toIso8601String(),
                'reference'       => $error->reference,
            ];
        }
        return $payload;
    }

    public function isStaff(?User $user): bool
    {
        return $user !== null && Roles::isAny($user->role, config('scan_errors.staff_roles', []));
    }

    /** Web: the session locale (SetLocale). App/API: the user's saved language. */
    private function locale(Request $request): string
    {
        $allowed = ['en', 'ha', 'yo', 'ig', 'fr', 'ff', 'ar'];
        $locale = $request->is('api/*') ? ($request->user()?->language ?: 'en') : app()->getLocale();
        return in_array($locale, $allowed, true) ? $locale : 'en';
    }

    private function scanType(Request $request): ?string
    {
        if ($request->is('api/*')) {
            return basename($request->path());
        }
        return $request->input('scan_type');
    }

    private function fieldLabel(string $field): string
    {
        return match (true) {
            in_array($field, ['image', 'images', 'images.0'], true) || str_starts_with($field, 'images.') => 'the photo',
            $field === 'scan_type'                                   => 'what you are scanning',
            in_array($field, ['animalType', 'animal_type'], true)    => 'the animal type',
            in_array($field, ['assessmentType', 'assessment_type'], true) => 'the assessment type',
            in_array($field, ['cropType', 'crop_type', 'cropPart', 'crop_part'], true) => 'the crop details',
            str_starts_with($field, 'loc_')                          => 'the location',
            default                                                  => 'the form',
        };
    }

    private function maybeAlert(): void
    {
        $window = (int) config('scan_errors.alert_window_minutes', 10);
        $threshold = (int) config('scan_errors.alert_threshold', 5);
        $recent = ScanError::whereIn('code', C::alerting())->where('created_at', '>=', now()->subMinutes($window))->get(['code', 'reference']);
        if ($recent->count() < $threshold) {
            return;
        }
        // One email per cooldown period, whatever the volume.
        if (!Cache::add('scan-errors:alert-sent', 1, now()->addMinutes((int) config('scan_errors.alert_cooldown_minutes', 30)))) {
            return;
        }
        $to = config('scan_errors.alert_emails') ?: User::where('role', 'ceo')->where('is_active', true)->pluck('email')->filter()->all();
        $counts = $recent->countBy('code')->map(fn ($n, $c) => "{$c}: {$n}")->implode("\n");
        $body = "MSAS FarmAI: {$recent->count()} scans failed in the last {$window} minutes.\n\n{$counts}\n\n"
            . "Latest references: " . $recent->pluck('reference')->take(10)->implode(', ') . "\n\n"
            . 'Details: ' . route('admin.scan-errors.index') . "\n";
        Log::critical('Scan failure alert', ['count' => $recent->count(), 'codes' => $recent->countBy('code')->all()]);
        try {
            if ($to) {
                Mail::to($to)->send(new \App\Mail\ScanFailureAlertMail($body, $recent->count(), $window));
            }
        } catch (\Throwable $e) {
            Log::error('Scan failure alert email not sent', ['error' => $e->getMessage()]);
        }
    }
}
