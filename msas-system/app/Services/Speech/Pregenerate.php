<?php

namespace App\Services\Speech;

use App\Jobs\TranslateScanResult;
use App\Models\Diagnosis;
use App\Models\SpeechLanguage;
use Illuminate\Support\Facades\Log;

/**
 * Optional (SPEECH_PREGENERATE=true): right after a scan, prepare the result
 * in the farmer's saved language, text and audio, in the background, so
 * "Listen" plays at once when they open it. Costs one translation and one
 * synthesis per scan, which is why it is off by default.
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
            if (!$lang) {
                return;
            }
            dispatch((new TranslateScanResult($d->id, $lang->code, (bool) $lang->provider))->afterCommit());
        } catch (\Throwable $e) {
            // Never let this affect the scan itself.
            Log::warning('Speech pre-generation not queued', ['diagnosis_id' => $d->id, 'error' => $e->getMessage()]);
        }
    }
}
