<?php

namespace App\Services\Scan;

use App\Support\Scan\ScanErrorCode as C;
use App\Support\Scan\ScanFailure;

/**
 * Turns what the AI engine answered (or how the call failed) into one
 * ScanErrorCode. The engine labels its own failures (detail.error, see
 * ai-engine/main.py _provider_error); older engine builds sent plain text,
 * which is matched as a fallback so a deploy in either order still classifies.
 *
 * Returns null for an engine "rejection" the AI itself made (it ran and
 * judged the photo: not a plant, unusable). That is a real result, stored
 * as a "retake the photo" diagnosis (IMAGE_UNUSABLE), not a failure.
 */
class ScanFailureClassifier
{
    private const PROVIDER_CODES = [
        'provider_auth_failed'     => C::AI_PROVIDER_AUTH_FAILED,
        'provider_not_configured'  => C::AI_PROVIDER_AUTH_FAILED,
        'provider_no_credit'       => C::AI_PROVIDER_NO_CREDIT,
        'provider_rate_limit'      => C::AI_PROVIDER_RATE_LIMIT,
        'provider_error'           => C::AI_PROVIDER_ERROR,
        'provider_unavailable'     => C::AI_PROVIDER_ERROR,
        'malformed_response'       => C::AI_PROVIDER_ERROR,
    ];

    private const UPLOAD_CODES = [
        'unreadable_image' => C::UPLOAD_BAD_FORMAT,
        'empty_file'       => C::UPLOAD_INTERRUPTED,
        'too_large'        => C::UPLOAD_TOO_LARGE,
    ];

    public function fromEngineResponse(int $status, ?array $body, string $raw = ''): ?ScanFailure
    {
        $detail = $body['detail'] ?? null;
        $errCode = is_array($detail) ? ($detail['error'] ?? null) : null;
        $message = is_array($detail) ? ($detail['message'] ?? json_encode($detail)) : (is_string($detail) ? $detail : $raw);
        $text = strtolower((string) $message);
        $make = fn (string $code, ?string $service = null) => new ScanFailure($code, $status, mb_substr((string) $message ?: $raw, 0, 2000), $service);

        if ($status === 401 || $status === 403) {
            return $make(C::ENGINE_AUTH_FAILED);
        }
        if ($errCode && isset(self::PROVIDER_CODES[$errCode])) {
            return $make(self::PROVIDER_CODES[$errCode]);
        }
        if ($errCode && isset(self::UPLOAD_CODES[$errCode])) {
            return $make(self::UPLOAD_CODES[$errCode]);
        }
        if ($status === 413) {
            return $make(C::UPLOAD_TOO_LARGE);
        }
        if ($status === 422) {
            if (is_array($detail) && array_key_exists('accepted', $detail)) {
                // Older engines: decode failures and AI judgements both said accepted:false.
                if (str_contains($text, 'could not be read') || str_contains($text, 'could not be processed')) {
                    return $make(C::UPLOAD_BAD_FORMAT);
                }
                return null; // the AI ran and rejected the photo: a real "retake" result
            }
            // FastAPI field validation: the website sent the engine a bad request.
            return $make(C::INTERNAL_ERROR, C::SERVICE_ENGINE);
        }
        if ($status === 404 || $status === 405) {
            return $make(C::ENGINE_UNREACHABLE);
        }
        if ($status >= 500 || $status === 429) {
            if ($detail === null) {
                // No engine JSON at all (e.g. the host's own 502/503 page): the engine is not answering.
                return $make($status === 429 ? C::AI_PROVIDER_RATE_LIMIT : C::ENGINE_UNREACHABLE);
            }
            return $make($this->providerFromText($text));
        }
        return $make(C::INTERNAL_ERROR, C::SERVICE_ENGINE);
    }

    /** Older engines sent "AI service error: <Anthropic message>". */
    private function providerFromText(string $text): string
    {
        return match (true) {
            str_contains($text, 'credit balance'), str_contains($text, 'usage limit'), str_contains($text, 'spend limit'),
            str_contains($text, 'billing') => C::AI_PROVIDER_NO_CREDIT,
            str_contains($text, 'not configured'), str_contains($text, 'authentication'), str_contains($text, 'x-api-key'),
            str_contains($text, 'invalid api key'), str_contains($text, 'permission') => C::AI_PROVIDER_AUTH_FAILED,
            str_contains($text, 'rate limit'), str_contains($text, 'rate_limit'), str_contains($text, '429') => C::AI_PROVIDER_RATE_LIMIT,
            default => C::AI_PROVIDER_ERROR,
        };
    }

    /**
     * Any exception that ends a scan request (rendered by bootstrap/app.php
     * for scan routes only). Null = not a scan failure; let Laravel answer.
     */
    public function forRequestException(\Throwable $e, \Illuminate\Http\Request $request): ?ScanFailure
    {
        if ($e instanceof \App\Exceptions\ScanFailedException) {
            return $e->failure;
        }
        if ($e instanceof \Illuminate\Http\Exceptions\HttpResponseException) {
            return null;
        }
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return $this->fromValidation($e, $request);
        }
        if ($e instanceof \Illuminate\Auth\AuthenticationException
            || $e instanceof \Illuminate\Session\TokenMismatchException
            || $e->getPrevious() instanceof \Illuminate\Session\TokenMismatchException) {
            return new ScanFailure(C::SESSION_EXPIRED, $e instanceof \Illuminate\Auth\AuthenticationException ? 401 : 419, class_basename($e));
        }
        if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
            return match ($e->getStatusCode()) {
                419     => new ScanFailure(C::SESSION_EXPIRED, 419, 'CSRF token mismatch'),
                413     => new ScanFailure(C::UPLOAD_TOO_LARGE, 413, 'Request body larger than post_max_size'),
                429     => new ScanFailure(C::TOO_MANY_SCANS, 429, 'Scan rate limit (throttle) reached'),
                default => $e->getStatusCode() >= 500
                    ? new ScanFailure(C::INTERNAL_ERROR, $e->getStatusCode(), get_class($e) . ': ' . $e->getMessage())
                    : null, // 403/404 etc.: Laravel's own answer
            };
        }
        return new ScanFailure(C::INTERNAL_ERROR, null,
            get_class($e) . ': ' . $e->getMessage() . ' at ' . basename($e->getFile()) . ':' . $e->getLine(), C::SERVICE_WEBSITE);
    }

    /** Which upload problem, or which field, made the scan request invalid. */
    public function fromValidation(\Illuminate\Validation\ValidationException $e, \Illuminate\Http\Request $request): ScanFailure
    {
        $failed = $e->validator->failed();
        $errors = $e->errors();
        foreach ($failed as $field => $rules) {
            if (!($field === 'image' || $field === 'images' || str_starts_with($field, 'images.'))) {
                continue;
            }
            $rules = array_map(fn ($r) => class_basename($r), array_keys($rules));
            $file = $request->file($field);
            if (is_array($file)) {
                $file = $file[0] ?? null;
            }
            $uploadError = $file instanceof \Illuminate\Http\UploadedFile ? $file->getError() : UPLOAD_ERR_OK;
            $code = match (true) {
                in_array($uploadError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true) => C::UPLOAD_TOO_LARGE,
                $uploadError !== UPLOAD_ERR_OK && $uploadError !== UPLOAD_ERR_NO_FILE   => C::UPLOAD_INTERRUPTED,
                in_array('Max', $rules, true)                                            => C::UPLOAD_TOO_LARGE,
                in_array('NotEmptyFile', $rules, true), in_array('Uploaded', $rules, true) => C::UPLOAD_INTERRUPTED,
                (bool) array_intersect(['Mimes', 'Mimetypes', 'Image', 'File'], $rules) => C::UPLOAD_BAD_FORMAT,
                default => null,
            };
            if ($code) {
                return new ScanFailure($code, 422, "{$field}: " . implode(', ', $rules) . " (PHP upload error {$uploadError})", C::SERVICE_WEBSITE, ['field' => $field]);
            }
        }
        $field = array_key_first($errors) ?? '';
        return new ScanFailure(C::VALIDATION_FAILED, 422, 'Invalid fields: ' . implode(', ', array_keys($errors)), C::SERVICE_WEBSITE,
            ['field' => $field, 'details' => $errors]);
    }

    /** A call that never got an answer: no connection, DNS, TLS or timeout. */
    public function fromException(\Throwable $e): ScanFailure
    {
        $connection = $e instanceof \Illuminate\Http\Client\ConnectionException
            || $e instanceof \GuzzleHttp\Exception\ConnectException
            || ($e instanceof \GuzzleHttp\Exception\RequestException && !$e->hasResponse())
            || preg_match('/cURL error (6|7|28|35|52|56)|timed out|could not resolve|connection refused/i', $e->getMessage());

        return new ScanFailure(
            $connection ? C::ENGINE_UNREACHABLE : C::INTERNAL_ERROR,
            null,
            get_class($e) . ': ' . $e->getMessage(),
            $connection ? C::SERVICE_ENGINE : C::SERVICE_WEBSITE,
        );
    }
}
