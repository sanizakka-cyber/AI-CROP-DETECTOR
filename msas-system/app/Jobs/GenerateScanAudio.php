<?php

namespace App\Jobs;

use App\Models\Diagnosis;
use App\Models\ScanResultAudio;
use App\Models\ScanResultTranslation;
use App\Models\SpeechLanguage;
use App\Services\Speech\Providers\TtsException;
use App\Services\Speech\SpeechAudioService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Speak a stored translation. While this retries, the app shows the
 * translated text with "audio temporarily unavailable".
 */
class GenerateScanAudio implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;
    public int $timeout = 200;

    public function __construct(
        public int $diagnosisId,
        public string $lang,
        public ?string $voice = null,
        public ?float $rate = null,
    ) {
        $this->onConnection(config('speech.queue_connection'))->onQueue(config('speech.queue'));
    }

    public function backoff(): array
    {
        return [15, 60, 180];
    }

    public function handle(SpeechAudioService $service): void
    {
        $d = Diagnosis::find($this->diagnosisId);
        $lang = SpeechLanguage::findCode($this->lang);
        $translation = ScanResultTranslation::where('diagnosis_id', $this->diagnosisId)->where('lang', $this->lang)->first();
        if (!$d || !$lang || !$translation?->isReady()) {
            return;
        }

        [$voice, $rate] = $service->voiceAndRate($lang, $this->voice, $this->rate);
        $audio = $service->row($d, $lang, $voice, $rate);
        if ($audio->isReady()) {
            return;
        }

        try {
            $service->generate($audio, $translation, $lang);
        } catch (TtsException $e) {
            if ($e->permanent) {
                $this->fail($e); // unknown voice / no provider: retrying cannot help
                return;
            }
            throw $e;
        }
    }

    public function failed(\Throwable $e): void
    {
        ScanResultAudio::where('diagnosis_id', $this->diagnosisId)->where('lang', $this->lang)
            ->where('status', 'pending')
            ->update(['status' => 'failed', 'error' => substr($e->getMessage(), 0, 1000)]);
    }
}
