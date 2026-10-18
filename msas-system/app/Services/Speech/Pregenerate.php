<?php

namespace App\Services\Speech;

use App\Jobs\TranslateScanResult;
use App\Models\Diagnosis;
use App\Models\SpeechLanguage;
use Illuminate\Support\Facades\Log;

/**
 * SPEECH_PREGENERATE (on since 2026-10-18): right after a scan, prepare the
 * result in the farmer's saved (preferred) language, text and audio, in the
 * background, so "Listen" plays at once when they open it.
 *
 * Only the preferred language, and only one that is live with audio
 * switched on (English and Hausa at launch): text-only languages are
 * translated when the farmer asks. Queued after the scan is committed on the
 * speech queue, so the scan result never waits for it; until the audio
 * exists the app shows the text and "Audio is being prepared".
 */
class Pregenerate
{
    public static function afterScan(Diagnosis $d): void
    {
        if (!config('speech.pregenerate') || !in_array($d->status, ['reviewed', 'processed'], true)) {
            return;
        }
        try {
            $code = $d->user?->preferred_language;
            $lang = $code ? SpeechLanguage::where('code', $code)->where('status', 'live')->first() : null;
            if (!$lang || !$lang->hasAudio()) {
                return;
            }
            dispatch((new TranslateScanResult($d->id, $lang->code, true))->afterCommit());
        } catch (\Throwable $e) {
            // Never let this affect the scan itself.
            Log::warning('Speech pre-generation not queued', ['diagnosis_id' => $d->id, 'error' => $e->getMessage()]);
        }
    }
}
