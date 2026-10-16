<?php

namespace App\Services\Speech\Providers;

class ProviderRegistry
{
    /** Providers an admin may choose: configured and licensed for commercial use. */
    public static function selectable(): array
    {
        return array_filter(config('speech.providers', []), fn ($p) => !empty($p['commercial_use']));
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

        return match ($cfg['driver']) {
            'self_hosted' => new SelfHostedProvider($cfg['engine']),
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
