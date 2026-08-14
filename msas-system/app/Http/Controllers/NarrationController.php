<?php

namespace App\Http\Controllers;

use App\Data\TtsLanguages;
use App\Models\Diagnosis;
use App\Services\Tts\TtsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NarrationController extends Controller
{
    public function __construct(private TtsService $tts) {}

    /**
     * Resolve (generating if needed) server TTS audio for a diagnosis in the
     * requested language. Returns a structured response the frontend uses to
     * decide between playing real audio (server_tts) or falling back to
     * browser speechSynthesis (browser_tts) — never a silent English swap.
     */
    public function show(Request $request, Diagnosis $diagnosis): JsonResponse
    {
        abort_if($diagnosis->user_id !== auth()->id(), 403);

        $request->validate([
            'language' => ['required', 'string', 'size:2'],
        ]);

        $language = $request->input('language');
        $locale   = TtsLanguages::localeFor($language);

        if (!TtsLanguages::isServerSupported($language)) {
            return response()->json([
                'success'  => false,
                'mode'     => 'browser_tts',
                'language' => $language,
                'locale'   => $locale,
                'status'   => 'unsupported',
            ]);
        }

        try {
            $narration = $this->tts->resolve($diagnosis, $language);
        } catch (\Throwable $e) {
            return response()->json([
                'success'  => false,
                'mode'     => 'browser_tts',
                'language' => $language,
                'locale'   => $locale,
                'status'   => 'failed',
            ]);
        }

        return response()->json([
            'success'   => true,
            'mode'      => 'server_tts',
            'language'  => $language,
            'locale'    => $locale,
            'voice'     => $narration->voice,
            'audio_url' => $this->tts->audioUrl($narration),
            'transcript'=> $diagnosis->narrationText(),
            'status'    => 'ready',
        ]);
    }
}
