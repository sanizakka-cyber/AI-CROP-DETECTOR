<?php

namespace App\Services\Speech\Text;

/**
 * Per-language spoken-form rules: unit words, where the unit goes relative to
 * the number, and the word for number ranges.
 *
 * Hausa, Yoruba and Igbo put the noun before the numeral ("kilo biyar"), so
 * units move in front of the spelled number. The Hausa/Igbo unit words here
 * are common loanwords and are UNREVIEWED defaults: native-speaker review may
 * replace any of them through the admin pronunciation lexicon (kind "unit"),
 * which overrides these. A language with no entry here keeps digits and units
 * as written.
 */
class TextRules
{
    public static function for(string $lang): array
    {
        return self::RULES[$lang] ?? ['units' => [], 'unit_position' => 'after', 'range' => null, 'percent' => null];
    }

    private const RULES = [
        'en' => [
            'unit_position' => 'after',
            'range'         => 'to',
            'percent'       => '{n} percent',
            'units'         => [
                'ml' => ['millilitre', 'millilitres'], 'mL' => ['millilitre', 'millilitres'],
                'l' => ['litre', 'litres'], 'L' => ['litre', 'litres'],
                'kg' => ['kilogram', 'kilograms'], 'g' => ['gram', 'grams'], 'mg' => ['milligram', 'milligrams'],
                'ha' => ['hectare', 'hectares'], 'cm' => ['centimetre', 'centimetres'], 'mm' => ['millimetre', 'millimetres'],
                'm' => ['metre', 'metres'], 'km' => ['kilometre', 'kilometres'],
                '°C' => ['degree Celsius', 'degrees Celsius'], 'ppm' => ['part per million', 'parts per million'],
            ],
        ],
        'ha' => [
            'unit_position' => 'before',
            'range'         => 'zuwa',
            'percent'       => 'kashi {n} cikin ɗari',
            'units'         => [
                'ml' => 'mililita', 'mL' => 'mililita', 'l' => 'lita', 'L' => 'lita',
                'kg' => 'kilogiram', 'g' => 'giram', 'mg' => 'miligiram', 'ha' => 'hekta',
                'cm' => 'santimita', 'mm' => 'milimita', 'm' => 'mita', 'km' => 'kilomita',
                '°C' => 'digiri selshiyas',
            ],
        ],
        'ig' => [
            'unit_position' => 'before',
            'range'         => 'ruo',
            'percent'       => 'pasentị {n}',
            'units'         => [
                'ml' => 'mililita', 'mL' => 'mililita', 'l' => 'lita', 'L' => 'lita',
                'kg' => 'kilogram', 'g' => 'gram', 'mg' => 'miligram', 'ha' => 'hekta',
                'cm' => 'sentimita', 'mm' => 'milimita', 'm' => 'mita', 'km' => 'kilomita',
            ],
        ],
    ];

    /** Lexicon entries applied in every language unless overridden. */
    public const DEFAULT_LEXICON = [
        'MSAS FarmAI' => 'em es ey es fam ey ay',
        'MSAS'        => 'em es ey es',
    ];

    /** English letter names, for acronyms such as GPS or LGA. */
    public const EN_LETTERS = [
        'A' => 'ay', 'B' => 'bee', 'C' => 'see', 'D' => 'dee', 'E' => 'ee', 'F' => 'ef', 'G' => 'jee',
        'H' => 'aitch', 'I' => 'eye', 'J' => 'jay', 'K' => 'kay', 'L' => 'el', 'M' => 'em', 'N' => 'en',
        'O' => 'oh', 'P' => 'pee', 'Q' => 'cue', 'R' => 'ar', 'S' => 'ess', 'T' => 'tee', 'U' => 'you',
        'V' => 'vee', 'W' => 'double-you', 'X' => 'ex', 'Y' => 'why', 'Z' => 'zed',
    ];
}
