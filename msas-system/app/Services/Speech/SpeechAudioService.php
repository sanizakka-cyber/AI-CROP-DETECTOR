<?php

namespace App\Services\Speech;

use App\Models\Diagnosis;
use App\Models\GlossaryTerm;
use App\Models\ScanResultAudio;
use App\Models\ScanResultTranslation;
use App\Models\SpeechLanguage;
use App\Services\Speech\Providers\ProviderRegistry;
use App\Services\Speech\Providers\TtsException;
use App\Services\Speech\Text\SpeechPreprocessor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

/**
 * Generates, caches and serves the spoken version of a stored translation.
 * Audio is cached by hash(spoken text + language + voice + provider + rate),
 * so the same words are never synthesised twice.
 */
class SpeechAudioService
{
    public function __construct(private SpeechPreprocessor $pre)
    {
    }

    public function voiceAndRate(SpeechLanguage $lang, ?string $voice, ?float $rate): array
    {
        $rate = $rate ? round(max(0.5, min(1.5, $rate)), 2) : round((float) ($lang->rate ?: config('speech.default_rate', 0.9)), 2);
        return [$voice ?: $lang->voice, $rate];
    }

    public function row(Diagnosis $d, SpeechLanguage $lang, string $voice, float $rate): ScanResultAudio
    {
        return ScanResultAudio::firstOrCreate(
            ['diagnosis_id' => $d->id, 'lang' => $lang->code, 'voice' => $voice, 'rate' => $rate],
            ['provider' => $lang->provider, 'status' => 'pending'],
        );
    }

    /**
     * @throws TtsException when every configured provider failed (the job retries)
     */
    public function generate(ScanResultAudio $audio, ScanResultTranslation $translation, SpeechLanguage $lang): ScanResultAudio
    {
        $script = $this->pre->script($translation->translated_json, $lang->code, $this->lexicon($lang->code), $this->unitWords($lang->code));
        if (!$script['segments']) {
            throw TtsException::permanent('Nothing to read aloud.');
        }

        $attempts = [[$lang->provider, $audio->voice]];
        if ($lang->fallback_provider && $lang->fallback_voice) {
            $attempts[] = [$lang->fallback_provider, $lang->fallback_voice];
        }

        $errors = [];
        foreach ($attempts as [$provider, $voice]) {
            if (!$provider || !$voice) {
                continue;
            }
            $key = hash('sha256', json_encode([$script['segments'], $lang->code, $voice, $provider, $audio->rate], JSON_UNESCAPED_UNICODE));

            if ($cached = $this->cached($key)) {
                // Same key = same sentences, so the cached sentence timings apply as they are.
                $audio->update(array_merge($cached->only(['audio_path', 'mime', 'duration_sec', 'size_bytes', 'segments']), [
                    'provider' => $provider, 'cache_key' => $key, 'status' => 'ready', 'error' => null, 'latency_ms' => 0,
                ]));
                return $audio;
            }

            $started = microtime(true);
            try {
                $out = ProviderRegistry::make($provider)->synthesize($script['segments'], $lang->code, $voice, (float) $audio->rate);
            } catch (TtsException $e) {
                $errors[] = "{$provider}: {$e->getMessage()}";
                Log::warning('TTS provider failed', ['diagnosis_id' => $audio->diagnosis_id, 'lang' => $lang->code, 'provider' => $provider, 'error' => $e->getMessage()]);
                continue;
            }
            $latency = (int) round((microtime(true) - $started) * 1000);

            $path = "speech/{$key}.{$out->extension}";
            Storage::disk($this->disk())->put($path, $out->bytes);

            $segmentTimes = [];
            foreach ($script['segments'] as $i => $seg) {
                $segmentTimes[] = ['sentence' => $seg['sentence'], 'start' => $out->segmentTimes[$i][0] ?? 0, 'end' => $out->segmentTimes[$i][1] ?? 0];
            }

            $audio->update([
                'provider' => $provider, 'cache_key' => $key, 'audio_path' => $path, 'mime' => $out->mime,
                'duration_sec' => $out->durationSec, 'size_bytes' => strlen($out->bytes),
                'segments' => $this->sentenceTimes($script, $segmentTimes),
                'status' => 'ready', 'error' => null, 'latency_ms' => $latency,
            ]);
            Log::info('TTS generated', ['diagnosis_id' => $audio->diagnosis_id, 'lang' => $lang->code, 'provider' => $provider,
                'voice' => $voice, 'latency_ms' => $latency, 'bytes' => strlen($out->bytes)]);
            return $audio;
        }

        $audio->update(['error' => substr(implode(' | ', $errors) ?: 'No voice configured.', 0, 1000), 'attempts' => $audio->attempts + 1]);
        $permanent = !$errors;
        throw $permanent ? TtsException::permanent($audio->error) : new TtsException($audio->error);
    }

    /** Signed, short-lived URL for a ready file. */
    public function url(ScanResultAudio $audio, bool $download = false): string
    {
        $disk = Storage::disk($this->disk());
        if (config("filesystems.disks.{$this->disk()}.driver") === 's3' && !$download) {
            return $disk->temporaryUrl($audio->audio_path, now()->addMinutes(30));
        }
        return URL::temporarySignedRoute('speech.file', now()->addMinutes(30), ['audio' => $audio->id, 'download' => $download ? 1 : 0]);
    }

    public function disk(): string
    {
        return config('speech.disk', 'local');
    }

    private function cached(string $key): ?ScanResultAudio
    {
        $hit = ScanResultAudio::where('cache_key', $key)->where('status', 'ready')->whereNotNull('audio_path')->first();
        return $hit && Storage::disk($this->disk())->exists($hit->audio_path) ? $hit : null;
    }

    /** Collapse clause timings into one {text, start, end} per sentence, for highlighting. */
    private function sentenceTimes(array $script, array $segmentTimes): array
    {
        $out = [];
        foreach ($segmentTimes as $t) {
            $i = $t['sentence'] ?? null;
            if ($i === null || !isset($script['sentences'][$i])) {
                continue;
            }
            $out[$i] ??= ['text' => $script['sentences'][$i]] + ($script['sentence_meta'][$i] ?? []) + ['start' => $t['start'], 'end' => $t['end']];
            $out[$i]['start'] = min($out[$i]['start'], $t['start']);
            $out[$i]['end'] = max($out[$i]['end'], $t['end']);
        }
        return array_values($out);
    }

    private function lexicon(string $lang): array
    {
        return GlossaryTerm::where('kind', 'pronunciation')->whereIn('lang', ['*', $lang])
            ->orderByRaw("CASE WHEN lang = '*' THEN 0 ELSE 1 END")
            ->pluck('local_term', 'english_term')->all();
    }

    private function unitWords(string $lang): array
    {
        return GlossaryTerm::where('kind', 'unit')->where('lang', $lang)->pluck('local_term', 'english_term')->all();
    }
}
