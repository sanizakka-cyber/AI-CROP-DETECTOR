<?php

namespace App\Support;

/**
 * Plain words for the farmer's view of a result.
 *
 * The engine is asked to write farmer-facing text without Latin names or
 * technical terms, and to keep those for the technical fields. This is the
 * safety net for what still slips through (and for older scans): a short
 * English glossary, and removal of brackets that only repeat a word in
 * technical form or hold a Latin name. "stem (pseudostem)" becomes "stem",
 * "Natural leaf sheath senescence" becomes "Natural leaf sheath ageing".
 *
 * Only for display on the farmer's screens. Admin technical details and the
 * PDF technical appendix show the stored text unchanged.
 */
final class FarmerText
{
    /** Technical term => everyday words (English; other languages are written plain by the engine). */
    public const GLOSSARY = [
        'pseudostems'  => 'stems',
        'pseudostem'   => 'stem',
        'senescence'   => 'ageing',
        'senescent'    => 'ageing',
        'senescing'    => 'ageing',
        'necrosis'     => 'dead patches',
        'necrotic'     => 'dead',
        'chlorosis'    => 'yellowing',
        'chlorotic'    => 'yellowing',
        'lesions'      => 'spots',
        'lesion'       => 'spot',
        'petioles'     => 'leaf stalks',
        'petiole'      => 'leaf stalk',
        'abscission'   => 'dropping off',
        'desiccation'  => 'drying',
        'desiccated'   => 'dried out',
        'foliar'       => 'leaf',
        'pathogens'    => 'germs',
        'pathogen'     => 'germ',
        'mycelium'     => 'fungus threads',
        'hyphae'       => 'fungus threads',
        'sporulation'  => 'spore growth',
        'inflorescence' => 'flower head',
        'etiolation'   => 'pale, stretched growth',
    ];

    /** Words that stay technical even after the glossary (checked by tests on the farmer view). */
    public const JARGON = ['pseudostem', 'senescence', 'senescent', 'necrosis', 'necrotic', 'chlorosis', 'chlorotic',
        'lesion', 'petiole', 'abscission', 'desiccation', 'foliar', 'pathogen', 'mycelium', 'hyphae', 'sporulation'];

    public static function plain(?string $text): ?string
    {
        if ($text === null || trim($text) === '') {
            return null;
        }
        $out = $text;
        foreach (self::GLOSSARY as $term => $plain) {
            $out = preg_replace_callback('/\b' . preg_quote($term, '/') . '\b/i', function ($m) use ($plain) {
                return ctype_upper($m[0][0]) ? ucfirst($plain) : $plain;
            }, $out);
        }
        // Brackets that repeat the words before them, or hold a Latin name, add nothing for a farmer.
        $out = preg_replace_callback('/\s*\(([^()]*)\)/u', function ($m) {
            $inner = trim($m[1]);
            return self::isLatin($inner) || $inner === '' ? '' : $m[0];
        }, $out);
        $out = preg_replace('/\b(\w+)\s*\(\s*\1\s*\)/iu', '$1', $out);
        $out = preg_replace('/\s{2,}/', ' ', trim($out));
        return $out === '' ? null : $out;
    }

    /** Second words that make "Panama disease" English, not a Latin binomial. */
    private const ENGLISH_SECOND_WORDS = ['disease', 'wilt', 'rot', 'blight', 'spot', 'spots', 'virus', 'streak', 'mosaic',
        'rust', 'mildew', 'smut', 'scab', 'canker', 'fever', 'plague', 'sickness', 'worm', 'worms', 'mite', 'mites', 'fly', 'beetle'];

    /** "Musa spp.", "Phytophthora infestans", "Fusarium oxysporum f. sp. cubense"; not "Panama disease". */
    public static function isLatin(string $s): bool
    {
        if (!preg_match('/^[A-Z][a-z]+\s+(spp?\.|([a-z]{3,}))((\s+(f\.\s*sp\.|pv\.|var\.|subsp\.))?\s+[a-z]{3,})?$/u', trim($s), $m)) {
            return false;
        }
        return !in_array($m[2] ?? '', self::ENGLISH_SECOND_WORDS, true);
    }

    /** True when the text still contains a word from JARGON (used by tests and the staff view hint). */
    public static function hasJargon(?string $text): bool
    {
        if ($text === null) {
            return false;
        }
        foreach (self::JARGON as $w) {
            if (preg_match('/\b' . preg_quote($w, '/') . '/i', $text)) {
                return true;
            }
        }
        return false;
    }
}
