<?php

namespace App\Services\Speech;

use App\Models\Diagnosis;
use App\Models\GlossaryTerm;
use App\Models\ScanResultTranslation;
use App\Models\SpeechLanguage;
use App\Services\Speech\Text\SpeechPreprocessor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Translates a scan result once per (scan, language, source text) and stores
 * it. Playback never translates. A translation that fails the integrity
 * checks is stored as "rejected" and never shown or spoken.
 */
class ScanTranslator
{
    /** Current stored translation, or a fresh "pending" row when the source changed or none exists. */
    public function prepare(Diagnosis $d, SpeechLanguage $lang): ScanResultTranslation
    {
        $src = ScanResultSource::fromDiagnosis($d);
        $hash = hash('sha256', json_encode([$src, $lang->code, $lang->safety_note], JSON_UNESCAPED_UNICODE));

        $row = ScanResultTranslation::firstOrNew(['diagnosis_id' => $d->id, 'lang' => $lang->code]);
        if ($row->exists && $row->source_hash === $hash && $row->status !== 'failed') {
            return $row;
        }
        $row->fill([
            'source_lang' => $src['source_lang'], 'source_hash' => $hash, 'source_json' => $src['content'],
            'translated_json' => null, 'status' => 'pending', 'check_problems' => null, 'error' => null,
            'reviewed_by' => null, 'reviewed_at' => null,
        ])->save();
        return $row;
    }

    /**
     * Run the translation for a pending row.
     *
     * @throws \RuntimeException on a transient translator failure (the job retries)
     */
    public function run(ScanResultTranslation $row, Diagnosis $d, SpeechLanguage $lang): ScanResultTranslation
    {
        if ($row->status !== 'pending') {
            return $row;
        }
        $src = ScanResultSource::fromDiagnosis($d);
        $source = $src['content'];
        $sameLanguage = $src['source_lang'] === $lang->code;

        if ($sameLanguage) {
            // Nothing to translate except, in a non-English language, the safety note.
            $translated = $source;
            if ($lang->code !== 'en' && !$lang->safety_note) {
                $note = $this->callTranslator(
                    ['observations' => [], 'explanation' => '', 'recommendations' => [], 'safety_note' => $source['safety_note']],
                    'en', $lang, []
                );
                $translated['safety_note'] = $note['safety_note'];
            }
        } else {
            $protected = array_values(array_unique(array_merge($src['protected_terms'], $this->protectedTerms($lang->code))));
            $translated = $this->callTranslator($source, $src['source_lang'], $lang, $protected);
            $problems = TranslationIntegrity::problems($source, $translated, $protected);
            if ($problems) {
                Log::warning('Scan translation rejected', ['diagnosis_id' => $d->id, 'lang' => $lang->code, 'problems' => $problems]);
                $row->update(['status' => 'rejected', 'check_problems' => $problems, 'translated_json' => $this->nfc($translated)]);
                return $row;
            }
        }

        if ($lang->safety_note) {
            $translated['safety_note'] = $lang->safety_note; // reviewed wording wins
        }

        $row->update(['status' => 'ready', 'translated_json' => $this->nfc($translated), 'check_problems' => []]);
        return $row;
    }

    private function callTranslator(array $content, string $sourceLang, SpeechLanguage $target, array $protected): array
    {
        $base = rtrim((string) config('services.ai_engine.url'), '/');
        $http = Http::timeout((int) config('speech.translator.timeout', 60))->connectTimeout(30)->acceptJson();
        if ($key = config('services.ai_engine.key')) {
            $http = $http->withToken($key);
        }

        $res = $http->post("{$base}/translate/scan-result", [
            'source_language_name' => SpeechLanguage::findCode($sourceLang)?->name_english ?? $sourceLang,
            'target_language_name' => $target->name_english,
            'content'              => $content,
            'glossary'             => GlossaryTerm::where('lang', $target->code)->where('kind', 'glossary')
                ->get(['english_term', 'local_term'])->toArray(),
            'protected_terms'      => $protected,
        ]);

        if (!$res->successful() || !is_array($res->json('translated'))) {
            throw new \RuntimeException("Translator error: HTTP {$res->status()} " . substr((string) json_encode($res->json('detail')), 0, 200));
        }
        return $res->json('translated');
    }

    private function protectedTerms(string $lang): array
    {
        return GlossaryTerm::where('kind', 'protected')->whereIn('lang', [$lang, '*'])->pluck('english_term')->all();
    }

    private function nfc(array $c): array
    {
        array_walk_recursive($c, function (&$v) {
            if (is_string($v)) {
                $v = SpeechPreprocessor::nfc($v);
            }
        });
        return $c;
    }
}
