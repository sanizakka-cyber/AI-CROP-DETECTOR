<?php

namespace App\Services\Speech;

/**
 * Automated safety checks on a translated scan result. A translation with any
 * problem is stored as "rejected" and never shown or spoken: dosages,
 * chemicals and diagnoses must not change in translation.
 */
class TranslationIntegrity
{
    private const UNITS = 'ml|mL|L|l|kg|g|mg|ha|cm|mm|km|m|°C|%|ppm';

    /** @return string[] problems; empty means the translation passed */
    public static function problems(array $source, array $translated, array $protectedTerms = []): array
    {
        $problems = [];

        foreach (['observations', 'recommendations'] as $k) {
            $a = count($source[$k] ?? []);
            $b = count($translated[$k] ?? []);
            if ($a !== $b) {
                $problems[] = "{$k}: {$b} items, source has {$a}";
            }
        }
        foreach (['explanation', 'safety_note'] as $k) {
            if (trim((string) ($source[$k] ?? '')) !== '' && trim((string) ($translated[$k] ?? '')) === '') {
                $problems[] = "{$k} is empty";
            }
        }

        $src = self::flatten($source);
        $dst = self::flatten($translated);

        $srcNums = self::numbers($src);
        $dstNums = self::numbers($dst);
        foreach (array_diff($srcNums, $dstNums) as $n) {
            $problems[] = "number {$n} is missing";
        }
        foreach (array_diff($dstNums, $srcNums) as $n) {
            $problems[] = "number {$n} was added";
        }

        foreach (self::quantities($src) as $q) {
            if (!in_array($q, self::quantities($dst), true)) {
                $problems[] = "quantity \"{$q}\" changed";
            }
        }

        foreach ($protectedTerms as $term) {
            if ($term !== '' && mb_strpos($src, $term) !== false && mb_strpos($dst, $term) === false) {
                $problems[] = "\"{$term}\" was not kept as written";
            }
        }

        return array_values(array_unique($problems));
    }

    private static function flatten(array $c): string
    {
        $parts = [];
        array_walk_recursive($c, function ($v) use (&$parts) {
            $parts[] = (string) $v;
        });
        return implode("\n", $parts);
    }

    /** Distinct numbers, with thousands separators removed. @return string[] */
    private static function numbers(string $s): array
    {
        $s = preg_replace_callback('/\b\d{1,3}(?:,\d{3})+\b/u', fn ($m) => str_replace(',', '', $m[0]), $s);
        preg_match_all('/\d+(?:\.\d+)?/u', $s, $m);
        return array_values(array_unique($m[0]));
    }

    /** "2.5 g", "80%", "7-10 days" style number+unit pairs, spacing normalised. @return string[] */
    private static function quantities(string $s): array
    {
        preg_match_all('/(\d+(?:\.\d+)?(?:\s*-\s*\d+(?:\.\d+)?)?)\s?(' . self::UNITS . ')(?![\p{L}\p{N}])/u', $s, $m, PREG_SET_ORDER);
        return array_values(array_unique(array_map(fn ($x) => preg_replace('/\s+/', '', $x[1]) . $x[2], $m)));
    }
}
