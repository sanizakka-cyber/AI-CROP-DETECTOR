<?php

namespace App\Services\Tts;

use App\Data\TtsLanguages;
use App\Models\Diagnosis;
use App\Models\DiagnosisNarration;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TtsService
{
    /**
     * Thrown when no server TTS exists for the language at all (e.g. Fulfulde) —
     * distinct from a provider call failing, so callers can message accordingly.
     */
    public const UNSUPPORTED = 'unsupported';

    /**
     * Resolve narration audio for a diagnosis+language: return the cached row
     * if ready, otherwise generate, store, and cache it. Returns null if the
     * language has no server provider at all — caller falls back to browser TTS.
     *
     * @throws \RuntimeException if the provider is configured but generation fails
     */
    public function resolve(Diagnosis $diagnosis, string $language): ?DiagnosisNarration
    {
        if (!TtsLanguages::isServerSupported($language)) {
            return null;
        }

        $existing = DiagnosisNarration::where('diagnosis_id', $diagnosis->id)
            ->where('language', $language)
            ->first();

        if ($existing && $existing->isReady()) {
            return $existing;
        }

        $providerName = TtsLanguages::providerFor($language);
        $voice        = TtsLanguages::voiceFor($language);

        $narration = $existing ?? new DiagnosisNarration([
            'diagnosis_id' => $diagnosis->id,
            'language'     => $language,
        ]);
        $narration->provider = $providerName;
        $narration->voice    = $voice;
        $narration->status   = 'pending';
        $narration->error    = null;
        $narration->save();

        try {
            $provider = $this->makeProvider($providerName);
            $result   = $provider->generate($diagnosis->narrationText(), $voice);

            $path = "narrations/{$diagnosis->id}/{$language}.{$result['format']}";
            Storage::disk('r2')->put($path, $result['bytes'], 'public');

            $narration->storage_path = $path;
            $narration->audio_format = $result['format'];
            $narration->status       = 'ready';
            $narration->save();

            return $narration;
        } catch (\Throwable $e) {
            Log::error('TTS generation failed', [
                'diagnosis_id' => $diagnosis->id,
                'language'     => $language,
                'provider'     => $providerName,
                'error'        => $e->getMessage(),
            ]);
            $narration->status = 'failed';
            $narration->error  = substr($e->getMessage(), 0, 500);
            $narration->save();

            throw $e;
        }
    }

    public function audioUrl(DiagnosisNarration $narration): string
    {
        return Storage::disk('r2')->url($narration->storage_path);
    }

    private function makeProvider(string $name): TtsProvider
    {
        return match ($name) {
            'spitch' => new SpitchProvider(),
            'openai' => new OpenAiTtsProvider(),
            default  => throw new \RuntimeException("Unknown TTS provider: {$name}"),
        };
    }
}
