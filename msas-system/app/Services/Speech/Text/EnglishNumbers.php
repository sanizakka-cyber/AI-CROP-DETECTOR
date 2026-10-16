<?php

namespace App\Services\Speech\Text;

class EnglishNumbers extends NumberSpeller
{
    private const ONES = ['zero', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten',
        'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];
    private const TENS = [2 => 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

    public function integer(int $n): ?string
    {
        if ($n < 0 || $n >= 1_000_000_000) {
            return null;
        }
        if ($n < 20) {
            return self::ONES[$n];
        }
        if ($n < 100) {
            return self::TENS[intdiv($n, 10)] . ($n % 10 ? '-' . self::ONES[$n % 10] : '');
        }
        if ($n < 1000) {
            $rest = $n % 100;
            return self::ONES[intdiv($n, 100)] . ' hundred' . ($rest ? ' and ' . $this->integer($rest) : '');
        }
        foreach ([1_000_000 => 'million', 1000 => 'thousand'] as $unit => $word) {
            if ($n >= $unit) {
                $rest = $n % $unit;
                $tail = '';
                if ($rest) {
                    $tail = ($rest < 100 ? ' and ' : ' ') . $this->integer($rest);
                }
                return $this->integer(intdiv($n, $unit)) . " {$word}" . $tail;
            }
        }
        return null;
    }

    public function decimal(string $whole, string $fraction): ?string
    {
        $w = $this->integer((int) $whole);
        if ($w === null || strlen($fraction) > 4) {
            return null;
        }
        $digits = array_map(fn ($d) => self::ONES[(int) $d], str_split($fraction));
        return $w . ' point ' . implode(' ', $digits);
    }
}
