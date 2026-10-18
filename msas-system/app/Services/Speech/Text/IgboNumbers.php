<?php

namespace App\Services\Speech\Text;

/**
 * Igbo cardinal numbers (decimal system): iri na ise (15), iri abụọ na ise (25),
 * narị abụọ na iri ise (250), puku abụọ (2000).
 * Decimals are left as digits until reviewed by native speakers.
 */
class IgboNumbers extends NumberSpeller
{
    private const ONES = [0 => 'efu', 'otu', 'abụọ', 'atọ', 'anọ', 'ise', 'isii', 'asaa', 'asatọ', 'itoolu', 'iri'];

    public function integer(int $n): ?string
    {
        if ($n < 0 || $n >= 1_000_000) {
            return null;
        }
        if ($n <= 10) {
            return self::ONES[$n];
        }
        if ($n < 100) {
            $t = intdiv($n, 10);
            $head = $t === 1 ? 'iri' : 'iri ' . self::ONES[$t];
            return $head . ($n % 10 ? ' na ' . self::ONES[$n % 10] : '');
        }
        if ($n < 1000) {
            $h = intdiv($n, 100);
            $head = $h === 1 ? 'otu narị' : 'narị ' . self::ONES[$h];
            return $head . ($n % 100 ? ' na ' . $this->integer($n % 100) : '');
        }
        $k = intdiv($n, 1000);
        $head = $k === 1 ? 'otu puku' : 'puku ' . $this->integer($k);
        return $head . ($n % 1000 ? ' na ' . $this->integer($n % 1000) : '');
    }
}
