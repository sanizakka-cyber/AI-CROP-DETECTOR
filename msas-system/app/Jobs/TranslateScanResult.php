<?php

namespace App\Jobs;

use App\Models\Diagnosis;
use App\Models\ScanResultTranslation;
use App\Models\SpeechLanguage;
use App\Services\Speech\ScanTranslator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/** Translate a scan result into one language, then (optionally) queue its audio. */
class TranslateScanResult implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public int $diagnosisId,
        public string $lang,
        public bool $withAudio = false,
        public ?string $voice = null,
        public ?float $rate = null,
    ) {
        $this->onConnection(config('speech.queue_connection'))->onQueue(config('speech.queue'));
    }

    public function backoff(): array
    {
        return [10, 30, 90];
    }

    public function handle(ScanTranslator $translator): void
    {
        $d = Diagnosis::find($this->diagnosisId);
        $lang = SpeechLanguage::findCode($this->lang);
        if (!$d || !$lang) {
            return;
        }

        $row = $translator->run($translator->prepare($d, $lang), $d, $lang);

        if ($row->isReady() && $this->withAudio && $lang->hasAudio()) {
            GenerateScanAudio::dispatch($this->diagnosisId, $this->lang, $this->voice, $this->rate);
        }
    }

    public function failed(\Throwable $e): void
    {
        ScanResultTranslation::where('diagnosis_id', $this->diagnosisId)->where('lang', $this->lang)
            ->where('status', 'pending')
            ->update(['status' => 'failed', 'error' => substr($e->getMessage(), 0, 1000)]);
    }
}
