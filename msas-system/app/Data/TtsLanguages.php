<?php

namespace App\Data;

class TtsLanguages
{
    /**
     * Centralized language -> provider/voice/locale map for server-generated
     * narration audio. A null 'provider' means no server TTS exists for that
     * language — the frontend must fall back to browser speechSynthesis and
     * say so honestly, never silently substitute another language's audio.
     *
     * Voice names for the 'spitch' provider are drawn from its documented
     * roster; confirm against the Spitch dashboard once SPITCH_API_KEY is
     * live and adjust here if a voice name doesn't match what's provisioned.
     */
    public static function config(): array
    {
        return [
            'en' => ['locale' => 'en-US', 'label' => 'English',  'provider' => 'spitch', 'voice' => 'lina'],
            'ha' => ['locale' => 'ha-NG', 'label' => 'Hausa',    'provider' => 'spitch', 'voice' => 'amina'],
            'yo' => ['locale' => 'yo-NG', 'label' => 'Yoruba',   'provider' => 'spitch', 'voice' => 'femi'],
            'ig' => ['locale' => 'ig-NG', 'label' => 'Igbo',     'provider' => 'spitch', 'voice' => 'ngozi'],
            'fr' => ['locale' => 'fr-FR', 'label' => 'French',   'provider' => 'openai', 'voice' => 'alloy'],
            'ff' => ['locale' => 'ff-NG', 'label' => 'Fulfulde', 'provider' => null,     'voice' => null],
        ];
    }

    public static function get(string $language): ?array
    {
        return self::config()[$language] ?? null;
    }

    public static function providerFor(string $language): ?string
    {
        return self::get($language)['provider'] ?? null;
    }

    public static function voiceFor(string $language): ?string
    {
        return self::get($language)['voice'] ?? null;
    }

    public static function localeFor(string $language): string
    {
        return self::get($language)['locale'] ?? 'en-US';
    }

    public static function isServerSupported(string $language): bool
    {
        return self::providerFor($language) !== null;
    }

    /** Language codes with a server-TTS provider, for the frontend's client-side mirror. */
    public static function serverSupportedCodes(): array
    {
        return array_keys(array_filter(self::config(), fn ($c) => $c['provider'] !== null));
    }
}
