<?php

namespace App\Support\Scan;

/**
 * Every way a scan can fail, with what the farmer is told and what the
 * client should offer next. One vocabulary for the web form, the app, the
 * API, the admin error page and the alert.
 *
 * `message` is an English key (translated in lang/*.json); `:limit`,
 * `:date` and `:field` are filled in by ScanErrorReporter.
 */
final class ScanErrorCode
{
    public const SCAN_QUOTA_EXCEEDED     = 'SCAN_QUOTA_EXCEEDED';
    public const SESSION_EXPIRED         = 'SESSION_EXPIRED';
    public const UPLOAD_TOO_LARGE        = 'UPLOAD_TOO_LARGE';
    public const UPLOAD_BAD_FORMAT       = 'UPLOAD_BAD_FORMAT';
    public const UPLOAD_INTERRUPTED      = 'UPLOAD_INTERRUPTED';
    public const VALIDATION_FAILED       = 'VALIDATION_FAILED';
    public const TOO_MANY_SCANS          = 'TOO_MANY_SCANS';
    public const ENGINE_UNREACHABLE      = 'ENGINE_UNREACHABLE';
    public const ENGINE_AUTH_FAILED      = 'ENGINE_AUTH_FAILED';
    public const AI_PROVIDER_AUTH_FAILED = 'AI_PROVIDER_AUTH_FAILED';
    public const AI_PROVIDER_NO_CREDIT   = 'AI_PROVIDER_NO_CREDIT';
    public const AI_PROVIDER_RATE_LIMIT  = 'AI_PROVIDER_RATE_LIMIT';
    public const AI_PROVIDER_ERROR       = 'AI_PROVIDER_ERROR';
    public const IMAGE_UNUSABLE          = 'IMAGE_UNUSABLE';
    public const INTERNAL_ERROR          = 'INTERNAL_ERROR';

    public const SERVICE_WEBSITE = 'website';
    public const SERVICE_ENGINE  = 'ai_engine';
    public const SERVICE_CLAUDE  = 'claude_api';

    private const UNAVAILABLE = 'Our analysis service is temporarily unavailable. Your scan was NOT counted. Please try again in a few minutes.';

    /**
     * http: status returned to the client. action: what the client offers
     * (retry | sign_in | upgrade | choose_photo | fix_fields | wait | retake).
     * alert: counts toward the 5-in-10-minutes email to the owner.
     */
    public const META = [
        self::SCAN_QUOTA_EXCEEDED     => ['http' => 403, 'service' => self::SERVICE_WEBSITE, 'action' => 'upgrade', 'alert' => false,
            'message' => 'You have used all :limit scans this month. Upgrade your plan or wait until :date.'],
        self::SESSION_EXPIRED         => ['http' => 401, 'service' => self::SERVICE_WEBSITE, 'action' => 'sign_in', 'alert' => false,
            'message' => 'Your login has expired. Please sign in again.'],
        self::UPLOAD_TOO_LARGE        => ['http' => 413, 'service' => self::SERVICE_WEBSITE, 'action' => 'choose_photo', 'alert' => false,
            'message' => 'This photo is too large (over 10 MB). Choose a smaller photo or take a new one.'],
        self::UPLOAD_BAD_FORMAT       => ['http' => 422, 'service' => self::SERVICE_WEBSITE, 'action' => 'choose_photo', 'alert' => false,
            'message' => 'This file is not a photo we can read. Choose a JPG, PNG, WebP or HEIC photo.'],
        self::UPLOAD_INTERRUPTED      => ['http' => 422, 'service' => self::SERVICE_WEBSITE, 'action' => 'retry', 'alert' => false,
            'message' => 'The photo did not finish uploading. Check your connection and try again.'],
        self::VALIDATION_FAILED       => ['http' => 422, 'service' => self::SERVICE_WEBSITE, 'action' => 'fix_fields', 'alert' => false,
            'message' => 'Please check this part of the form: :field.'],
        self::TOO_MANY_SCANS          => ['http' => 429, 'service' => self::SERVICE_WEBSITE, 'action' => 'wait', 'alert' => false,
            'message' => 'You are sending scans too quickly. Wait a minute, then try again.'],
        self::ENGINE_UNREACHABLE      => ['http' => 503, 'service' => self::SERVICE_ENGINE, 'action' => 'retry', 'alert' => true, 'message' => self::UNAVAILABLE],
        self::ENGINE_AUTH_FAILED      => ['http' => 503, 'service' => self::SERVICE_ENGINE, 'action' => 'retry', 'alert' => true, 'message' => self::UNAVAILABLE],
        self::AI_PROVIDER_AUTH_FAILED => ['http' => 503, 'service' => self::SERVICE_CLAUDE, 'action' => 'retry', 'alert' => true, 'message' => self::UNAVAILABLE],
        self::AI_PROVIDER_NO_CREDIT   => ['http' => 503, 'service' => self::SERVICE_CLAUDE, 'action' => 'retry', 'alert' => true, 'message' => self::UNAVAILABLE],
        self::AI_PROVIDER_RATE_LIMIT  => ['http' => 503, 'service' => self::SERVICE_CLAUDE, 'action' => 'retry', 'alert' => true, 'message' => self::UNAVAILABLE],
        self::AI_PROVIDER_ERROR       => ['http' => 503, 'service' => self::SERVICE_CLAUDE, 'action' => 'retry', 'alert' => true, 'message' => self::UNAVAILABLE],
        self::IMAGE_UNUSABLE          => ['http' => 200, 'service' => self::SERVICE_CLAUDE, 'action' => 'retake', 'alert' => false,
            'message' => 'We could not assess this photo. See the tips below and retake it.'],
        self::INTERNAL_ERROR          => ['http' => 500, 'service' => self::SERVICE_WEBSITE, 'action' => 'retry', 'alert' => true,
            'message' => 'Something went wrong on our side. Your scan was NOT counted. Please try again, and contact support with the reference below if it keeps happening.'],
    ];

    public const SERVICE_LABELS = [
        self::SERVICE_WEBSITE => 'Website (MSAS FarmAI)',
        self::SERVICE_ENGINE  => 'AI engine',
        self::SERVICE_CLAUDE  => 'Claude API (Anthropic)',
    ];

    public static function all(): array
    {
        return array_keys(self::META);
    }

    public static function meta(string $code): array
    {
        return self::META[$code] ?? self::META[self::INTERNAL_ERROR];
    }

    /** Codes that page the owner when they repeat (ENGINE_*, AI_PROVIDER_*, INTERNAL_ERROR). */
    public static function alerting(): array
    {
        return array_keys(array_filter(self::META, fn ($m) => $m['alert']));
    }
}
