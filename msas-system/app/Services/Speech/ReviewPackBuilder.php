<?php

namespace App\Services\Speech;

use App\Models\Diagnosis;
use App\Models\SpeechLanguage;
use App\Models\SpeechReviewItem;
use App\Models\SpeechReviewPack;
use App\Services\Speech\Providers\ProviderRegistry;
use App\Services\Speech\Providers\TtsException;
use App\Services\Speech\Text\SpeechPreprocessor;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Builds a native-speaker review pack for one language:
 *  - N real scan results per scan type (Plant/Crop, Livestock, Soil Sample,
 *    Pest ID), anonymised, each with its stored translation and one audio
 *    file per candidate voice, labelled A/B/C in a different order per item;
 *  - numbers and units as they will be spoken, the screen labels, the spoken
 *    safety note, and the pronunciation lexicon (always incl. MSAS FarmAI,
 *    GPS and LGA), each spoken by every candidate.
 *
 * Translation uses the production translator and integrity checks; a scan
 * whose translation is rejected is skipped (and logged), never "fixed".
 */
class ReviewPackBuilder
{
    /** Scan types as reviewers see them, and the stored diagnoses.type values for each. */
    public const SCAN_TYPES = [
        'crop'      => ['plant', 'crop'],
        'livestock' => ['livestock', 'animal'],
        'soil'      => ['soil'],
        'pest'      => ['pest'],
    ];

    /** Numbers, doses and units of the kind scan results contain (English source, spoken in the language). */
    public const NUMBER_SAMPLES = [
        'Apply 2.5 g per litre of water.', 'Mix 10 ml in 1 litre.', 'Use 250 kg per hectare.',
        'Repeat after 7 days.', 'Wait 14 days before harvest.', 'Give 1 tablet per 50 kg of body weight.',
        'The AI is 78% sure of this.', 'Spray 3 times, 10 days apart.', 'Keep the soil at pH 6.5.',
        'Use 0.5 litre per 20 litre sprayer.',
    ];

    /** Must be in every lexicon review, whether or not a glossary entry exists yet. */
    public const REQUIRED_TERMS = ['MSAS FarmAI', 'GPS', 'LGA'];

    /** Screen labels farmers see around the spoken result (web lang/*.json keys). */
    public const LABEL_KEYS = [
        'Listen to your result', 'Listen', 'Pause', 'Replay', 'Speed', 'Download audio', 'Remember my choice',
        'Observations', 'Explanation', 'Recommendations', 'Safety note',
        'Audio is being prepared…', 'Audio is being prepared… It will play when ready.',
        'Preparing your result in this language…', 'Was this audio clear?', 'What was unclear? (optional)', 'Thank you!',
        'Audio is not available for this language yet. Read the result below.',
        'Audio is temporarily unavailable. Read the result below; we are still trying.',
        'This is taking longer than usual. Read the result below; the audio will be ready soon.',
        'Not certain', 'Possible conditions', 'Please confirm with a vet or extension officer before treating.',
        'AI guidance can be wrong; confirm with an expert.',
    ];

    public function __construct(
        private ScanTranslator $translator,
        private SpeechAudioService $audio,
        private SpeechPreprocessor $pre,
    ) {
    }

    /**
     * Scan results per scan type for a pack of $size (decision 2026-10-18:
     * packs of 25), spread as evenly as possible, extra ones to the first types.
     *
     * @return array<string, int> scan type => count
     */
    public static function quotasFor(int $size): array
    {
        $types = array_keys(self::SCAN_TYPES);
        $base = intdiv(max(0, $size), count($types));
        $extra = max(0, $size) % count($types);
        $out = [];
        foreach ($types as $i => $t) {
            $out[$t] = $base + ($i < $extra ? 1 : 0);
        }
        return $out;
    }

    /**
     * @param string[] $candidates "provider:voice" (1-6)
     * @param int|array<string,int> $perType scans per type, or a quota per type (quotasFor())
     * @param int[]|null $scanIds use exactly these scans instead of choosing
     */
    public function build(string $lang, array $candidates, int|array $perType = 5, ?int $createdBy = null, ?string $title = null, ?array $scanIds = null, ?callable $progress = null): SpeechReviewPack
    {
        $quota = is_array($perType) ? $perType : array_fill_keys(array_keys(self::SCAN_TYPES), $perType);
        $language = SpeechLanguage::findCode($lang) ?? throw new InvalidArgumentException("Unknown language {$lang}.");
        $parsed = $this->parseCandidates($candidates, $lang);
        $say = $progress ?? fn () => null;

        $pack = SpeechReviewPack::create([
            'lang' => $lang, 'status' => 'open', 'created_by' => $createdBy, 'candidates' => $parsed,
            'title' => $title ?: "{$language->name_english} review pack " . now()->format('Y-m-d'),
        ]);

        $position = 0;
        $safetyNote = null;
        foreach ($this->chooseScans($lang, max($quota), $scanIds) as $type => $scans) {
            $want = (int) ($quota[$type] ?? 0);
            $added = 0;
            foreach ($scans as $d) {
                if (!$scanIds && $added >= $want) {
                    break;
                }
                $row = $this->translator->prepare($d, $language);
                if (!$row->isReady()) {
                    $row = $this->translator->run($row, $d, $language);
                }
                if (!$row->isReady()) {
                    Log::info('Review pack: scan skipped, translation not usable', ['diagnosis_id' => $d->id, 'lang' => $lang, 'status' => $row->status]);
                    $say("skipped scan {$d->id} ({$row->status})");
                    continue;
                }
                $item = $this->addItem($pack, ++$position, 'scan', $type, $d->id, $row->source_lang,
                    $this->anonymise($row->source_json, $d), $this->anonymise($row->translated_json, $d));
                $added++;
                $safetyNote ??= $row->translated_json['safety_note'] ?? null;
                $say("item {$position}: {$type} scan, " . count($item->playableLabels()) . ' voices');
            }
            if ($added < $want && !$scanIds) {
                $say("only {$added} usable {$type} scans (wanted {$want})");
            }
        }

        foreach ($this->extraItems($language, $safetyNote) as $kind => $rows) {
            if ($rows) {
                $this->addItem($pack, ++$position, $kind, null, null, 'en', ['rows' => $rows], ['rows' => $rows]);
                $say("item {$position}: {$kind}");
            }
        }
        return $pack->refresh();
    }

    /** @return array<int, array{provider: string, voice: string, license: string}> */
    public function parseCandidates(array $candidates, string $lang): array
    {
        $selectable = ProviderRegistry::selectable();
        $out = [];
        foreach ($candidates as $c) {
            [$provider, $voice] = array_pad(explode(':', $c, 2), 2, '');
            if (!isset($selectable[$provider])) {
                throw new InvalidArgumentException("Provider {$provider} is not selectable (unknown, or not licensed for commercial use).");
            }
            $known = config("speech.providers.{$provider}.voices.{$lang}");
            if ($voice === '' || (is_array($known) && $known && !in_array($voice, $known, true))) {
                throw new InvalidArgumentException("Voice '{$voice}' is not a {$provider} voice for {$lang}.");
            }
            $out[] = ['provider' => $provider, 'voice' => $voice, 'license' => $selectable[$provider]['license'] ?? ''];
        }
        if (count($out) < 1 || count($out) > 6) {
            throw new InvalidArgumentException('Give between 1 and 6 candidate voices.');
        }
        return $out;
    }

    /** @return array<string, Diagnosis[]> scan type => scans */
    private function chooseScans(string $lang, int $perType, ?array $scanIds): array
    {
        $used = SpeechReviewItem::whereHas('pack', fn ($q) => $q->where('lang', $lang))->whereNotNull('diagnosis_id')->pluck('diagnosis_id');
        $out = [];
        foreach (self::SCAN_TYPES as $type => $stored) {
            $q = Diagnosis::whereIn('type', $stored)->whereIn('status', ['reviewed', 'processed'])
                ->where(fn ($q) => $q->whereNull('assessment_outcome')->orWhere('assessment_outcome', 'assessed'));
            // Three times as many as needed: build() keeps the first $perType whose translation passes.
            $q = $scanIds ? $q->whereIn('id', $scanIds) : $q->whereNotIn('id', $used)->latest()->limit($perType * 3);
            $out[$type] = $q->get()->all();
        }
        return $out;
    }

    /** Remove the farmer's name, contact details and place names from review copies. */
    public function anonymise(array $content, Diagnosis $d): array
    {
        $needles = [];
        $u = $d->user;
        if ($u) {
            foreach ([$u->name, $u->email, $u->phone, $u->village, $u->ward] as $v) {
                if (is_string($v) && mb_strlen(trim($v)) >= 3) {
                    $needles[trim($v)] = '[name]';
                }
            }
            foreach (preg_split('/\s+/', (string) $u->name) as $part) {
                if (mb_strlen($part) >= 3) {
                    $needles[$part] = '[name]';
                }
            }
        }
        $loc = $d->collectionLocation;
        if ($loc) {
            foreach (['community', 'ward', 'address_landmark', 'postal_code', 'lga'] as $f) {
                if (is_string($loc->$f) && mb_strlen(trim($loc->$f)) >= 3) {
                    $needles[trim($loc->$f)] = '[place]';
                }
            }
        }
        uksort($needles, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a)); // longest first
        $scrub = function ($s) use ($needles) {
            if (!is_string($s)) {
                return $s;
            }
            foreach ($needles as $find => $with) {
                $s = preg_replace('/' . preg_quote($find, '/') . '/iu', $with, $s);
            }
            return preg_replace('/\+?\d[\d\s\-]{8,}\d/', '[number]', $s); // phone numbers
        };
        array_walk_recursive($content, function (&$v) use ($scrub) { $v = $scrub($v); });
        return $content;
    }

    private function addItem(SpeechReviewPack $pack, int $position, string $kind, ?string $scanType, ?int $diagnosisId, ?string $sourceLang, array $source, array $translation): SpeechReviewItem
    {
        $labels = range('A', 'Z');
        $order = array_keys($pack->candidates);
        shuffle($order);
        $labelMap = [];
        foreach ($order as $k => $candidateIndex) {
            $labelMap[$labels[$k]] = $candidateIndex;
        }
        ksort($labelMap);

        $item = SpeechReviewItem::create([
            'pack_id' => $pack->id, 'position' => $position, 'kind' => $kind, 'scan_type' => $scanType,
            'diagnosis_id' => $diagnosisId, 'source_lang' => $sourceLang,
            'source_json' => $source, 'translation_json' => $translation, 'label_map' => $labelMap,
        ]);

        $speak = $kind === 'scan' ? $translation
            : ['observations' => array_column($translation['rows'], 'text'), 'explanation' => '', 'recommendations' => [], 'safety_note' => ''];
        $rate = (float) (SpeechLanguage::findCode($pack->lang)?->rate ?: config('speech.default_rate', 0.9));
        $audio = [];
        foreach ($labelMap as $label => $i) {
            $c = $pack->candidates[$i];
            try {
                $out = $this->audio->synthesizeJoined($c['provider'], $c['voice'], $speak, $pack->lang, $rate);
                // The stored name carries no provider or voice: only admins can map a label back.
                $path = "speech/review/{$pack->id}/{$item->id}/" . bin2hex(random_bytes(8)) . ".{$out['extension']}";
                Storage::disk(config('speech.disk', 'local'))->put($path, $out['bytes']);
                $audio[$label] = ['path' => $path, 'mime' => $out['mime'], 'duration_sec' => $out['duration_sec'], 'size_bytes' => strlen($out['bytes'])];
            } catch (TtsException $e) {
                $audio[$label] = ['error' => substr($e->getMessage(), 0, 300)];
                Log::warning('Review pack: voice failed', ['item' => $item->id, 'provider' => $c['provider'], 'voice' => $c['voice'], 'error' => $e->getMessage()]);
            }
        }
        $item->update(['audio' => $audio]);
        return $item;
    }

    /** @return array<string, array<int, array{source: string, text: string}>> */
    private function extraItems(SpeechLanguage $language, ?string $safetyNote): array
    {
        $lang = $language->code;
        $lexicon = $this->audio->lexicon($lang);
        $units = $this->audio->unitWords($lang);
        $spoken = fn (string $s) => $this->pre->spoken($this->pre->clean($s), $lang, $lexicon, $units);

        // How numbers, doses and units will be spoken (the speller and unit glossary are what is reviewed).
        $numbers = array_map(fn ($s) => ['source' => $s, 'text' => $spoken($s)], self::NUMBER_SAMPLES);

        $strings = is_file(lang_path("{$lang}.json")) ? (json_decode(file_get_contents(lang_path("{$lang}.json")), true) ?: []) : [];
        $labels = [];
        foreach (self::LABEL_KEYS as $key) {
            if (isset($strings[$key]) && $strings[$key] !== '') {
                $labels[] = ['source' => $key, 'text' => $strings[$key]];
            }
        }

        $note = $language->safety_note ?: $safetyNote;
        $safety = $note ? [['source' => config('speech.safety_note_en'), 'text' => $note]] : [];

        $terms = array_unique(array_merge(self::REQUIRED_TERMS, array_keys($lexicon)));
        $lex = array_map(fn ($t) => ['source' => $t, 'text' => $spoken($t)], array_values($terms));

        return ['numbers' => $numbers, 'labels' => $labels, 'safety_note' => $safety, 'lexicon' => $lex];
    }
}
