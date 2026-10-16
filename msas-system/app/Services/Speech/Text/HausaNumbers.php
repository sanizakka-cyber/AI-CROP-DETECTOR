<?php

namespace App\Services\Speech\Text;

/**
 * Hausa cardinal numbers (decimal system): goma sha biyar (15),
 * ashirin da biyar (25), ɗari biyu da hamsin (250), dubu biyu (2000).
 * Decimals are left as digits until reviewed by native speakers.
 */
class HausaNumbers extends NumberSpeller
{
    private const ONES = [0 => 'sifili', 'ɗaya', 'biyu', 'uku', 'huɗu', 'biyar', 'shida', 'bakwai', 'takwas', 'tara', 'goma'];
    private const TENS = [2 => 'ashirin', 'talatin', "arba'in", 'hamsin', 'sittin', "saba'in", 'tamanin', "casa'in"];

    public function integer(int $n): ?string
    {
        if ($n < 0 || $n >= 1_000_000) {
            return null;
        }
        if ($n <= 10) {
            return self::ONES[$n];
        }
        if ($n < 20) {
            return 'goma sha ' . self::ONES[$n - 10];
        }
        if ($n < 100) {
            return self::TENS[intdiv($n, 10)] . ($n % 10 ? ' da ' . self::ONES[$n % 10] : '');
        }
        if ($n < 1000) {
            $h = intdiv($n, 100);
            $head = $h === 1 ? 'ɗari' : 'ɗari ' . self::ONES[$h];
            return $head . ($n % 100 ? ' da ' . $this->integer($n % 100) : '');
        }
        $k = intdiv($n, 1000);
        $head = $k === 1 ? 'dubu' : 'dubu ' . $this->integer($k);
        return $head . ($n % 1000 ? ' da ' . $this->integer($n % 1000) : '');
    }
}
