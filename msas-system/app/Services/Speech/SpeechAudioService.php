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
     * Speaks the result one sentence at a time. Each sentence file is saved
     * as soon as it is made, so the app and web can start playing the first
     * sentence while the rest are still being generated. When every sentence
     * is done they are joined into one file for download and offline use.
     *
     * A retry after a temporary failure keeps the sentences already made.
     *
     * @throws TtsException when every configured provider failed (the job retries)
     */
    public function generate(ScanResultAudio $audio, ScanResultTranslation $translation, SpeechLanguage $lang): ScanResultAudio
    {
        $script = $this->pre->script($translation->translated_json, $lang->code, $this->lexicon($lang->code), $this->unitWords($lang->code));
        if (!$script['segments']) {
            throw TtsException::permanent('Nothing to read aloud.');
        }
        $groups = self::sentenceGroups($script['segments']);

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
                // Same key = same sentences, so the cached files and timings apply as they are.
                $audio->update(array_merge($cached->only(['audio_path', 'mime', 'duration_sec', 'size_bytes', 'segments', 'chunks', 'chunks_total']), [
                    'provider' => $provider, 'cache_key' => $key, 'status' => 'ready', 'error' => null, 'latency_ms' => 0, 'first_chunk_ms' => 0,
                ]));
                return $audio;
            }

            if ($audio->cache_key !== $key) {
                // New text, voice or provider: start over. Sentences without
                // times let clients show the text while the audio arrives.
                $audio->update([
                    'cache_key' => $key, 'provider' => $provider, 'chunks' => [], 'chunks_total' => count($groups),
                    'segments' => $this->sentenceTimes($script, []), 'first_chunk_ms' => null,
                ]);
            }
            $chunks = [];
            foreach ($audio->chunks ?? [] as $c) {
                $chunks[$c['index']] = $c;
            }

            $started = microtime(true);
            try {
                $tts = ProviderRegistry::make($provider);
                foreach ($groups as $n => [$sentence, $segments]) {
                    if (isset($chunks[$n]) && Storage::disk($this->disk())->exists($chunks[$n]['path'])) {
                        continue;
                    }
                    $out = $tts->synthesize($segments, $lang->code, $voice, (float) $audio->rate);
                    $path = "speech/{$key}/{$n}.{$out->extension}";
                    Storage::disk($this->disk())->put($path, $out->bytes);
                    $chunks[$n] = [
                        'index' => $n, 'sentence' => $sentence, 'path' => $path, 'mime' => $out->mime, 'extension' => $out->extension,
                        'duration_sec' => round($out->durationSec, 2), 'size_bytes' => strlen($out->bytes),
                        'speech_start' => (float) ($out->segmentTimes[0][0] ?? 0),
                        'speech_end' => (float) ($out->segmentTimes[count($segments) - 1][1] ?? $out->durationSec),
                    ];
                    ksort($chunks);
                    $update = ['chunks' => array_values($chunks)];
                    if ($audio->first_chunk_ms === null) {
                        $update['first_chunk_ms'] = (int) round((microtime(true) - $started) * 1000);
                    }
                    $audio->update($update);
                }
            } catch (TtsException $e) {
                Log::warning('TTS provider failed', ['diagnosis_id' => $audio->diagnosis_id, 'lang' => $lang->code, 'provider' => $provider,
                    'sentences_done' => count($chunks), 'error' => $e->getMessage()]);
                if ($chunks && !$e->permanent) {
                    // Some sentences are already playable: retry this voice rather than switch voices mid-result.
                    $audio->update(['error' => substr("{$provider}: {$e->getMessage()}", 0, 1000), 'attempts' => $audio->attempts + 1]);
                    throw $e;
                }
                $errors[] = "{$provider}: {$e->getMessage()}";
                continue;
            }

            return $this->finish($audio, $script, $chunks, $provider, $voice, (int) round((microtime(true) - $started) * 1000));
        }

        $audio->update(['error' => substr(implode(' | ', $errors) ?: 'No voice configured.', 0, 1000), 'attempts' => $audio->attempts + 1]);
        $permanent = !$errors;
        throw $permanent ? TtsException::permanent($audio->error) : new TtsException($audio->error);
    }

    /** [[sentence index, [segments]]] in reading order: one audio file per sentence. */
    public static function sentenceGroups(array $segments): array
    {
        $groups = [];
        foreach ($segments as $seg) {
            $s = $seg['sentence'] ?? count($groups);
            if (!$groups || $groups[count($groups) - 1][0] !== $s) {
                $groups[] = [$s, []];
            }
            $groups[count($groups) - 1][1][] = $seg;
        }
        return $groups;
    }

    /** Join the sentence files into one (MP3 frames and Ogg pages concatenate) and mark the row ready. */
    private function finish(ScanResultAudio $audio, array $script, array $chunks, string $provider, string $voice, int $latency): ScanResultAudio
    {
        $disk = Storage::disk($this->disk());
        $bytes = '';
        $t = 0.0;
        $times = [];
        foreach ($chunks as $c) {
            $bytes .= $disk->get($c['path']);
            $times[] = ['sentence' => $c['sentence'], 'start' => round($t + $c['speech_start'], 2), 'end' => round($t + $c['speech_end'], 2)];
            $t += $c['duration_sec'];
        }
        $first = reset($chunks);
        $path = "speech/{$audio->cache_key}.{$first['extension']}";
        $disk->put($path, $bytes);

        $audio->update([
            'provider' => $provider, 'audio_path' => $path, 'mime' => $first['mime'],
            'duration_sec' => round($t, 2), 'size_bytes' => strlen($bytes),
            'segments' => $this->sentenceTimes($script, $times),
            'status' => 'ready', 'error' => null, 'latency_ms' => $latency,
        ]);
        Log::info('TTS generated', ['diagnosis_id' => $audio->diagnosis_id, 'lang' => $audio->lang, 'provider' => $provider,
            'voice' => $voice, 'sentences' => count($chunks), 'first_chunk_ms' => $audio->first_chunk_ms,
            'latency_ms' => $latency, 'bytes' => strlen($bytes)]);
        return $audio;
    }

    /** Signed, short-lived URL for one finished sentence file. */
    public function chunkUrl(ScanResultAudio $audio, array $chunk): string
    {
        if (config("filesystems.disks.{$this->disk()}.driver") === 's3') {
            return Storage::disk($this->disk())->temporaryUrl($chunk['path'], now()->addMinutes(30));
        }
        return URL::temporarySignedRoute('speech.chunk', now()->addMinutes(30), ['audio' => $audio->id, 'index' => $chunk['index']]);
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

    /**
     * One {text, section, item, start, end} per sentence, for highlighting.
     * Without timings (audio still being made) start and end are null.
     */
    private function sentenceTimes(array $script, array $segmentTimes): array
    {
        if (!$segmentTimes) {
            $out = [];
            foreach ($script['sentences'] as $i => $text) {
                $out[] = ['text' => $text] + ($script['sentence_meta'][$i] ?? []) + ['start' => null, 'end' => null];
            }
            return $out;
        }
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
