<?php

namespace App\Services\Speech\Text;

/**
 * Spells whole numbers as words for the voice. Returns null when the number
 * is outside what the speller is sure about; the digits are then left as
 * written rather than guessed.
 */
abstract class NumberSpeller
{
    abstract public function integer(int $n): ?string;

    /** "2.5" etc. Default: no decimals (digits stay). */
    public function decimal(string $whole, string $fraction): ?string
    {
        return null;
    }

    public static function for(string $lang): ?self
    {
        return match ($lang) {
            'en'    => new EnglishNumbers(),
            'ha'    => new HausaNumbers(),
            'ig'    => new IgboNumbers(),
            default => null, // e.g. Yoruba: vigesimal system, needs native-speaker rules (see README)
        };
    }
}
