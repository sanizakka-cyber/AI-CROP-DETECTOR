<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateScanAudio;
use App\Jobs\TranslateScanResult;
use App\Models\Diagnosis;
use App\Models\ScanResultAudio;
use App\Models\ScanResultTranslation;
use App\Models\SpeechLanguage;
use App\Models\TtsFeedback;
use App\Services\Speech\Providers\ProviderRegistry;
use App\Services\Speech\ScanTranslator;
use App\Services\Speech\SpeechAudioService;
use App\Support\SpeechAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * "Listen to your result" for the app (bearer token) and the web (session).
 * Translation and audio are generated once in the background and cached;
 * clients poll the GET endpoints until status is "ready".
 */
class ScanSpeechController extends Controller
{
    private const STALE_MINUTES = 5;

    public function __construct(private ScanTranslator $translator, private SpeechAudioService $audio)
    {
    }

    public function languages(Request $request): JsonResponse
    {
        $user = $request->user();
        $langs = SpeechLanguage::availableFor($user);
        $codes = $langs->pluck('code')->all();
        $default = collect([$user->preferred_language, $user->language, 'en'])->first(fn ($c) => $c && in_array($c, $codes, true)) ?? ($codes[0] ?? 'en');

        return response()->json([
            'languages' => $langs->map->toPicker()->values(),
            'preferred' => $user->preferred_language,
            'default'   => $default,
            // Credits for the voices actually spoken (languages with audio on).
            'credits'   => ProviderRegistry::credits($langs->filter->hasAudio()->flatMap(fn ($l) => [$l->provider, $l->fallback_provider])->all()),
        ]);
    }

    public function translate(Request $request, Diagnosis $diagnosis): JsonResponse
    {
        $lang = $this->authorizeLanguage($request, $diagnosis);
        $data = $request->validate(['remember' => 'sometimes|boolean', 'audio' => 'sometimes|boolean']);

        if (!empty($data['remember'])) {
            $request->user()->forceFill(['preferred_language' => $lang->code])->save();
        }

        $row = $this->translator->prepare($diagnosis, $lang);
        if ($row->status === 'pending' && $this->needsJob($row)) {
            $this->queue(new TranslateScanResult($diagnosis->id, $lang->code, (bool) ($data['audio'] ?? true)));
        }

        return response()->json($this->translationPayload($row->refresh(), $request), $row->status === 'pending' ? 202 : 200);
    }

    public function showTranslation(Request $request, Diagnosis $diagnosis, string $lang): JsonResponse
    {
        $language = $this->authorizeLanguage($request, $diagnosis, $lang);
        $row = ScanResultTranslation::where('diagnosis_id', $diagnosis->id)->where('lang', $language->code)->first();
        if (!$row) {
            return response()->json(['lang' => $language->code, 'status' => 'none'], 404);
        }
        return response()->json($this->translationPayload($row, $request));
    }

    public function requestAudio(Request $request, Diagnosis $diagnosis): JsonResponse
    {
        $lang = $this->authorizeLanguage($request, $diagnosis);
        $data = $request->validate([
            'voice' => 'sometimes|nullable|string|max:60',
            'rate'  => 'sometimes|nullable|numeric|min:0.5|max:1.5',
        ]);
        if (!$lang->hasAudio()) {
            return response()->json($this->unavailable($lang), 200);
        }
        $voice = $data['voice'] ?? null;
        if ($voice && !in_array($voice, $this->voicesFor($lang), true)) {
            return response()->json(['error' => 'Unknown voice for this language.', 'details' => ['voice' => ['Unknown voice.']]], 422);
        }
        [$voice, $rate] = $this->audio->voiceAndRate($lang, $voice, isset($data['rate']) ? (float) $data['rate'] : null);

        $translation = $this->translator->prepare($diagnosis, $lang);
        if (!$translation->isReady()) {
            if ($translation->status === 'pending') {
                if ($this->needsJob($translation)) {
                    $this->queue(new TranslateScanResult($diagnosis->id, $lang->code, true, $voice, $rate));
                }
                return response()->json(['lang' => $lang->code, 'status' => 'translating', 'message' => 'Preparing your result in this language.'], 202);
            }
            return response()->json($this->audioPayload(null, $lang, $translation), 200);
        }

        $row = $this->audio->row($diagnosis, $lang, $voice, $rate);
        if ($row->status === 'ready' && !$row->isReady()) {
            $row->update(['status' => 'pending']);
        }
        if ($row->status === 'failed' || ($row->status === 'pending' && ($row->wasRecentlyCreated || $this->stale($row)))) {
            $row->update(['status' => 'pending', 'error' => null]);
            $row->touch();
            $this->queue(new GenerateScanAudio($diagnosis->id, $lang->code, $voice, $rate));
            $row->refresh();
        }

        return response()->json($this->audioPayload($row, $lang, $translation), $row->isReady() ? 200 : 202);
    }

    public function showAudio(Request $request, Diagnosis $diagnosis, string $lang): JsonResponse
    {
        $language = $this->authorizeLanguage($request, $diagnosis, $lang);
        if (!$language->hasAudio()) {
            return response()->json($this->unavailable($language));
        }
        [$voice, $rate] = $this->audio->voiceAndRate($language, $request->query('voice'), $request->query('rate') ? (float) $request->query('rate') : null);
        $translation = ScanResultTranslation::where('diagnosis_id', $diagnosis->id)->where('lang', $language->code)->first();
        $row = ScanResultAudio::where(['diagnosis_id' => $diagnosis->id, 'lang' => $language->code, 'voice' => $voice])
            ->where('rate', $rate)->first();

        if (!$row && (!$translation || $translation->status === 'pending')) {
            return response()->json(['lang' => $language->code, 'status' => $translation ? 'translating' : 'none',
                'message' => 'Preparing your result in this language.']);
        }
        return response()->json($this->audioPayload($row, $language, $translation));
    }

    public function feedback(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scan_id' => 'required|integer|exists:diagnoses,id',
            'lang'    => 'required|string|max:10',
            'clear'   => 'required|boolean',
            'comment' => 'nullable|string|max:1000',
        ]);
        $diagnosis = Diagnosis::findOrFail($data['scan_id']);
        abort_unless(SpeechAccess::canListen($request->user(), $diagnosis), 403);

        $audio = ScanResultAudio::where('diagnosis_id', $diagnosis->id)->where('lang', $data['lang'])
            ->where('status', 'ready')->latest('updated_at')->first();
        $row = TtsFeedback::updateOrCreate(
            ['user_id' => $request->user()->id, 'diagnosis_id' => $diagnosis->id, 'lang' => $data['lang']],
            ['clear' => $data['clear'], 'comment' => $data['comment'] ?? null, 'voice' => $audio?->voice, 'provider' => $audio?->provider],
        );
        Log::info('TTS feedback', ['lang' => $row->lang, 'voice' => $row->voice, 'provider' => $row->provider, 'clear' => $row->clear]);

        return response()->json(['saved' => true], 201);
    }

    /** Extension staff confirm a translation reads correctly (counts toward the language's review target). */
    public function review(Request $request, Diagnosis $diagnosis, string $lang): JsonResponse
    {
        abort_unless(SpeechAccess::canReview($request->user()), 403);
        $row = ScanResultTranslation::where('diagnosis_id', $diagnosis->id)->where('lang', $lang)->firstOrFail();
        if (!$row->isReady()) {
            return response()->json(['error' => 'Only a finished translation can be marked reviewed.'], 422);
        }
        $row->update(['reviewed_by' => $request->user()->id, 'reviewed_at' => now()]);
        $language = SpeechLanguage::findCode($lang);

        return response()->json([
            'reviewed'  => true,
            'reviewedCount' => $language?->reviewedCount(),
            'target'    => (int) config('speech.review_threshold', 50),
        ]);
    }

    /** Signed URL target: streams a generated file (local disks, and downloads). */
    public function file(Request $request, ScanResultAudio $audio)
    {
        abort_unless($audio->isReady(), 404);
        $disk = Storage::disk($this->audio->disk());
        abort_unless($disk->exists($audio->audio_path), 404);

        $headers = ['Content-Type' => $audio->mime ?: 'audio/mpeg', 'Cache-Control' => 'private, max-age=86400'];
        if ($request->boolean('download')) {
            $name = "msas-result-{$audio->diagnosis_id}-{$audio->lang}." . pathinfo($audio->audio_path, PATHINFO_EXTENSION);
            return $disk->download($audio->audio_path, $name, $headers);
        }
        return $disk->response($audio->audio_path, null, $headers);
    }

    /** Signed URL target: streams one finished sentence file. */
    public function chunk(ScanResultAudio $audio, int $index)
    {
        $chunk = collect($audio->readyChunks())->firstWhere('index', $index);
        abort_unless($chunk, 404);
        $disk = Storage::disk($this->audio->disk());
        abort_unless($disk->exists($chunk['path']), 404);
        return $disk->response($chunk['path'], null, ['Content-Type' => $chunk['mime'] ?: 'audio/mpeg', 'Cache-Control' => 'private, max-age=86400']);
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function authorizeLanguage(Request $request, Diagnosis $diagnosis, ?string $code = null): SpeechLanguage
    {
        $user = $request->user();
        abort_unless($user && SpeechAccess::canListen($user, $diagnosis), 403);
        $code ??= $request->validate(['lang' => 'required|string|max:10'])['lang'];
        $lang = SpeechLanguage::availableFor($user)->firstWhere('code', $code);
        if (!$lang) {
            abort(response()->json(['error' => 'This language is not available yet.', 'details' => ['lang' => ['Not available.']]], 422));
        }
        return $lang;
    }

    private function voicesFor(SpeechLanguage $lang): array
    {
        $voices = [$lang->voice, $lang->fallback_voice];
        foreach ([$lang->provider, $lang->fallback_provider] as $p) {
            $voices = array_merge($voices, config("speech.providers.{$p}.voices.{$lang->code}", []));
        }
        return array_values(array_filter(array_unique($voices)));
    }

    private function translationPayload(ScanResultTranslation $row, Request $request): array
    {
        $messages = [
            'pending'  => 'Preparing your result in this language.',
            'rejected' => 'This result could not be translated safely, so it is shown in its original language.',
            'failed'   => 'Translation is temporarily unavailable. Showing the original result.',
        ];
        $payload = [
            'lang'     => $row->lang,
            'status'   => $row->status,
            'content'  => $row->isReady() ? $row->translated_json : null,
            'source'   => ['lang' => $row->source_lang, 'content' => $row->source_json],
            'reviewed' => $row->reviewed_at !== null,
            'message'  => $messages[$row->status] ?? null,
        ];
        if (SpeechAccess::canReview($request->user())) {
            $payload['problems'] = $row->check_problems ?? [];
        }
        return $payload;
    }

    private function audioPayload(?ScanResultAudio $row, SpeechLanguage $lang, ?ScanResultTranslation $translation): array
    {
        if ($translation && in_array($translation->status, ['rejected', 'failed'], true)) {
            return ['lang' => $lang->code, 'status' => 'unavailable',
                'message' => 'Audio is not available because this result could not be translated. The original result is shown.'];
        }
        if (!$row) {
            return ['lang' => $lang->code, 'status' => 'none'];
        }
        $status = $row->isReady() ? 'ready' : ($row->status === 'failed' ? 'failed' : ($row->attempts > 0 ? 'retrying' : 'pending'));
        $payload = [
            'lang'     => $lang->code,
            'status'   => $status,
            'voice'    => $row->voice,
            'provider' => $row->provider,
            'rate'     => (float) $row->rate,
            'message'  => match ($status) {
                'pending'  => 'Audio is being prepared…',
                'retrying' => 'Audio is temporarily unavailable. Read the result below; we are still trying.',
                'failed'   => 'Audio is temporarily unavailable. Read the result below and try again later.',
                default    => null,
            },
        ];
        // Sentence files play in order as soon as the first one exists, so the
        // farmer hears speech before the whole result has been generated.
        $chunks = $row->readyChunks();
        if ($chunks || $row->chunks_total) {
            $payload['chunksTotal'] = (int) ($row->chunks_total ?: count($chunks));
            $payload['chunks'] = array_map(fn ($c) => [
                'index'       => $c['index'],
                'sentence'    => $c['sentence'],
                'url'         => $this->audio->chunkUrl($row, $c),
                'durationSec' => $c['duration_sec'],
            ], $chunks);
            if (!$row->isReady()) {
                $payload['sentences'] = $row->segments ?? [];
            }
        }
        if ($row->isReady()) {
            $payload += [
                'url'         => $this->audio->url($row),
                'downloadUrl' => $this->audio->url($row, true),
                'mime'        => $row->mime,
                'sizeBytes'   => $row->size_bytes,
                'durationSec' => $row->duration_sec,
                'sentences'   => $row->segments ?? [],
            ];
        }
        return $payload;
    }

    private function unavailable(SpeechLanguage $lang): array
    {
        return ['lang' => $lang->code, 'status' => 'unavailable', 'textOnly' => true, 'message' => 'Text only in this language for now.'];
    }

    /**
     * Queue a job only for a row this request just created or reset (new, a
     * changed result, or a failed one being retried), or one whose job seems
     * lost. A row already pending from an earlier click keeps its queued job.
     */
    private function needsJob($row): bool
    {
        return $row->wasRecentlyCreated || $row->wasChanged('status') || $row->wasChanged('source_hash') || $this->stale($row);
    }

    private function stale($row): bool
    {
        return $row->updated_at && $row->updated_at->lt(now()->subMinutes(self::STALE_MINUTES));
    }

    /** Queue failures must never break the response: the row's status tells the client. */
    private function queue(object $job): void
    {
        try {
            dispatch($job);
        } catch (\Throwable $e) {
            Log::warning('Speech job failed to run', ['job' => $job::class, 'error' => $e->getMessage()]);
        }
    }
}
