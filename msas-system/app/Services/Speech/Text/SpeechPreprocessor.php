<?php

namespace App\Services\Speech\Text;

/**
 * Turns a translated scan result into a speech script:
 *  - NFC-normalises (special letters and tone marks are kept, never stripped);
 *  - strips markdown and emoji;
 *  - applies the pronunciation lexicon, spells numbers/units/ranges/dates in
 *    the target language, and reads acronyms letter by letter;
 *  - splits into sentences (for on-screen highlighting) and clauses of at
 *    most `maxChars` characters, each with the pause that follows it.
 *
 * Framework-free on purpose, so it can be unit-tested in isolation.
 */
class SpeechPreprocessor
{
    public function __construct(
        private int $maxChars = 200,
        private int $commaPauseMs = 250,
        private int $stopPauseMs = 450,
        private int $sectionPauseMs = 700,
    ) {
    }

    /**
     * @param array{observations: string[], explanation: string, recommendations: string[], safety_note: string} $content
     * @param array<string,string> $lexicon   written form => spoken form (admin pronunciation entries)
     * @param array<string,string> $unitWords unit symbol => spoken word (admin "unit" entries)
     * @return array{sentences: string[], sentence_meta: array<int, array{section: string, item: int}>, segments: array<int, array{text: string, sentence: int, pause_ms: int}>}
     */
    public function script(array $content, string $lang, array $lexicon = [], array $unitWords = []): array
    {
        // [section, item number, text] in reading order.
        $items = [];
        foreach (array_values($content['observations'] ?? []) as $i => $o) {
            $items[] = ['observations', $i, $o];
        }
        $items[] = ['explanation', 0, $content['explanation'] ?? ''];
        foreach (array_values($content['recommendations'] ?? []) as $i => $r) {
            $items[] = ['recommendations', $i, $r];
        }
        $items[] = ['safety_note', 0, $content['safety_note'] ?? ''];

        $sentences = [];
        $meta = [];
        $segments = [];
        foreach ($items as [$section, $itemNo, $item]) {
            $clean = $this->clean((string) $item);
            if ($clean === '') {
                continue;
            }
            foreach ($this->splitSentences($clean) as $sentence) {
                $sentences[] = $sentence;
                $meta[] = ['section' => $section, 'item' => $itemNo];
                $index = count($sentences) - 1;
                $clauses = $this->splitClauses($this->spoken($sentence, $lang, $lexicon, $unitWords));
                foreach ($clauses as $i => $clause) {
                    $segments[] = ['text' => $clause, 'sentence' => $index,
                        'pause_ms' => $i < count($clauses) - 1 ? $this->commaPauseMs : $this->stopPauseMs];
                }
            }
            $segments[count($segments) - 1]['pause_ms'] = $this->sectionPauseMs;
        }
        if ($segments) {
            $segments[count($segments) - 1]['pause_ms'] = 0;
        }

        return ['sentences' => $sentences, 'sentence_meta' => $meta, 'segments' => $segments];
    }

    public static function nfc(string $s): string
    {
        if (class_exists(\Normalizer::class)) {
            $n = \Normalizer::normalize($s, \Normalizer::FORM_C);
            if ($n !== false) {
                return $n;
            }
        }
        return $s;
    }

    /** Markdown, emoji and stray whitespace out; letters and tone marks untouched. */
    public function clean(string $s): string
    {
        $s = self::nfc($s);
        $s = preg_replace('/\[([^\]]+)\]\([^)]*\)/u', '$1', $s);            // [text](url)
        $s = preg_replace('/(\*\*|__|`|~~)/u', '', $s);
        $s = preg_replace('/(?<![\p{L}\p{N}])[*_](?=\S)|(?<=\S)[*_](?![\p{L}\p{N}])/u', '', $s);
        $s = preg_replace('/^\s*(#{1,6}\s+|[-*•]\s+|\d+[.)]\s+)/mu', '', $s);
        // Emoji and pictographs, flags, keycaps, variation selectors, ZWJ.
        // Combining marks (U+0300-036F, tone marks) are deliberately NOT in this list.
        $s = preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{1F1E6}-\x{1F1FF}\x{FE0E}\x{FE0F}\x{200D}\x{20E3}\x{E0020}-\x{E007F}]/u', '', $s);
        $s = preg_replace('/\s+/u', ' ', $s);
        $s = preg_replace('/\s+([,.;:!?])/u', '$1', $s); // "water 🌱, every" -> "water, every"
        return trim($s);
    }

    /** @return string[] */
    public function splitSentences(string $s): array
    {
        $parts = preg_split('/(?<=[.!?])\s+(?=\S)/u', $s, -1, PREG_SPLIT_NO_EMPTY);
        return array_values(array_filter(array_map('trim', $parts), fn ($p) => $p !== ''));
    }

    /** Clauses at ", " "; " ": ", then hard-wrapped at maxChars on a word boundary. */
    public function splitClauses(string $s): array
    {
        $out = [];
        foreach (preg_split('/(?<=[,;:])\s+/u', $s, -1, PREG_SPLIT_NO_EMPTY) as $clause) {
            while (mb_strlen($clause) > $this->maxChars) {
                $cut = mb_strrpos(mb_substr($clause, 0, $this->maxChars), ' ');
                $cut = $cut ?: $this->maxChars;
                $out[] = trim(mb_substr($clause, 0, $cut));
                $clause = trim(mb_substr($clause, $cut));
            }
            if ($clause !== '') {
                $out[] = $clause;
            }
        }
        return $out;
    }

    /** One sentence in spoken form. */
    public function spoken(string $s, string $lang, array $lexicon = [], array $unitWords = []): string
    {
        // 1. Lexicon, longest entries first so "MSAS FarmAI" wins over "MSAS".
        $lexicon = array_merge(TextRules::DEFAULT_LEXICON, $lexicon);
        uksort($lexicon, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        foreach ($lexicon as $written => $say) {
            $s = preg_replace('/(?<![\p{L}\p{N}])' . preg_quote($written, '/') . '(?![\p{L}\p{N}])/u', $say, $s);
        }

        $rules = TextRules::for($lang);
        $units = array_merge($rules['units'], $unitWords);
        $speller = NumberSpeller::for($lang);

        // 2. Thousands separators: 2,500 -> 2500.
        $s = preg_replace_callback('/\b\d{1,3}(?:,\d{3})+\b/u', fn ($m) => str_replace(',', '', $m[0]), $s);

        // 3. Dates (English only; other languages keep them as written until reviewed).
        if ($lang === 'en' && $speller) {
            $s = preg_replace_callback('/\b(\d{1,2})\/(\d{1,2})\/(\d{4})\b/u', function ($m) use ($speller) {
                $months = [1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                $month = $months[(int) $m[2]] ?? null;
                return $month ? $speller->integer((int) $m[1]) . " {$month} " . $speller->integer((int) $m[3]) : $m[0];
            }, $s);
        }

        if ($speller) {
            $num = '(\d+(?:\.\d+)?)';
            $symbols = array_keys($units);
            usort($symbols, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a)); // "mm" before "m"
            $unitAlt = $symbols ? implode('|', array_map(fn ($u) => preg_quote($u, '/'), $symbols)) : null;

            // 4. Percentages.
            if ($rules['percent']) {
                $s = preg_replace_callback("/{$num}(?:\s*-\s*{$num})?\s?%/u", function ($m) use ($speller, $rules) {
                    $n = $this->say($speller, $m[1]);
                    if (!empty($m[2])) {
                        $to = $this->say($speller, $m[2]);
                        $n = ($n && $to) ? "{$n} {$rules['range']} {$to}" : null;
                    }
                    return $n ? str_replace('{n}', $n, $rules['percent']) : $m[0];
                }, $s);
            }

            // 5. Number (or range) followed by a unit.
            if ($unitAlt) {
                $s = preg_replace_callback("/{$num}(?:\s*-\s*{$num})?\s?({$unitAlt})(?![\p{L}\p{N}])/u", function ($m) use ($speller, $rules, $units) {
                    $a = $this->say($speller, $m[1]);
                    $b = !empty($m[2]) ? $this->say($speller, $m[2]) : null;
                    if ($a === null || (!empty($m[2]) && $b === null)) {
                        return $m[0];
                    }
                    $amount = $b ? "{$a} {$rules['range']} {$b}" : $a;
                    $word = $units[$m[3]];
                    if (is_array($word)) { // [singular, plural]
                        $word = ($m[1] === '1' && empty($m[2])) ? $word[0] : $word[1];
                    }
                    return $rules['unit_position'] === 'before' ? "{$word} {$amount}" : "{$amount} {$word}";
                }, $s);
            }

            // 6. Ranges and plain numbers.
            if ($rules['range']) {
                $s = preg_replace_callback("/(?<![\p{L}\d.]){$num}\s*-\s*{$num}(?![\d\p{L}])/u", function ($m) use ($speller, $rules) {
                    $a = $this->say($speller, $m[1]);
                    $b = $this->say($speller, $m[2]);
                    return ($a && $b) ? "{$a} {$rules['range']} {$b}" : $m[0];
                }, $s);
            }
            $s = preg_replace_callback("/(?<![\p{L}\d.]){$num}(?![\d\p{L}])/u", fn ($m) => $this->say($speller, $m[1]) ?? $m[0], $s);
        }

        // 7. Acronyms (GPS, LGA): letter by letter.
        $s = preg_replace_callback('/\b[A-Z]{2,5}\b/u', function ($m) use ($lang) {
            $letters = str_split($m[0]);
            return $lang === 'en'
                ? implode(' ', array_map(fn ($l) => TextRules::EN_LETTERS[$l], $letters))
                : implode(' ', $letters);
        }, $s);

        return trim(preg_replace('/\s+/u', ' ', $s));
    }

    private function say(NumberSpeller $speller, string $n): ?string
    {
        if (str_contains($n, '.')) {
            [$w, $f] = explode('.', $n, 2);
            return $speller->decimal($w, $f);
        }
        return strlen($n) > 9 ? null : $speller->integer((int) $n);
    }
}
