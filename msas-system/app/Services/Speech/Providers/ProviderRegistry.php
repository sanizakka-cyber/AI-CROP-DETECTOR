<?php

namespace App\Services\Speech\Providers;

class ProviderRegistry
{
    /** Providers an admin may choose: configured, licensed for commercial use, and not switched off. */
    public static function selectable(): array
    {
        return array_filter(config('speech.providers', []), fn ($p) => !empty($p['commercial_use']) && ($p['enabled'] ?? true));
    }

    public static function make(string $name): TtsProvider
    {
        $cfg = config("speech.providers.{$name}");
        if (!$cfg) {
            throw TtsException::permanent("Unknown voice provider: {$name}");
        }
        if (empty($cfg['commercial_use'])) {
            throw TtsException::permanent("{$name} is not licensed for commercial use ({$cfg['license']}).");
        }
        if (!($cfg['enabled'] ?? true)) {
            throw TtsException::permanent("{$name} is switched off until its licence is confirmed.");
        }

        return match ($cfg['driver']) {
            'self_hosted' => new SelfHostedProvider($cfg['engine'], $cfg['url'] ?? null, $cfg['key'] ?? null),
            'elevenlabs'  => new ElevenLabsProvider(),
            default       => throw TtsException::permanent("No driver for {$name}"),
        };
    }

    /** Credits to show in the app's About screen for the providers in use. */
    public static function credits(array $providerNames): array
    {
        $out = [];
        foreach (array_unique(array_filter($providerNames)) as $name) {
            $cfg = config("speech.providers.{$name}");
            if ($cfg) {
                $out[] = ['provider' => $name, 'label' => $cfg['label'], 'license' => $cfg['license'],
                    'credit' => $cfg['credit'] ?? null, 'source' => $cfg['source'] ?? null];
            }
        }
        return $out;
    }
}
