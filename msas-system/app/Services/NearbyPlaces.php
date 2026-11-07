<?php

namespace App\Services;

/**
 * The nearest named school, market, mosque, church or health centre to a
 * point, from our own copy of OpenStreetMap places (resources/geo/places,
 * built by scripts/geo/build_place_index.py). Read on our server only:
 * nothing is sent anywhere. Data © OpenStreetMap contributors, ODbL.
 *
 * The places are split into half-degree cells ("<row>_<col>.json", row =
 * floor(lat * 2), col = floor(lon * 2)); a lookup reads only the cells
 * around the point.
 */
final class NearbyPlaces
{
    private const CELL = 0.5;

    /** @var array<string, array> decoded cells, kept for the request */
    private static array $cells = [];

    /**
     * @return array{name: string, kind: string, distance: float}|null
     */
    public static function nearest(float $lat, float $lon, float $radiusM): ?array
    {
        $dLat = $radiusM / 110540;
        $dLon = $radiusM / max(1, 111320 * cos(deg2rad($lat)));
        $best = null;
        for ($row = (int) floor(($lat - $dLat) / self::CELL); $row <= (int) floor(($lat + $dLat) / self::CELL); $row++) {
            for ($col = (int) floor(($lon - $dLon) / self::CELL); $col <= (int) floor(($lon + $dLon) / self::CELL); $col++) {
                foreach (self::cell("{$row}_{$col}") as [$pLat, $pLon, $kind, $name]) {
                    if (abs($pLat - $lat) > $dLat || abs($pLon - $lon) > $dLon) {
                        continue;
                    }
                    $d = ReverseGeocoder::metres($lat, $lon, (float) $pLat, (float) $pLon);
                    if ($d <= $radiusM && ($best === null || $d < $best['distance'])) {
                        $best = ['name' => (string) $name, 'kind' => (string) $kind, 'distance' => $d];
                    }
                }
            }
        }

        return $best;
    }

    private static function cell(string $key): array
    {
        if (!array_key_exists($key, self::$cells)) {
            $file = rtrim((string) config('geocoding.places_path'), '/\\') . DIRECTORY_SEPARATOR . $key . '.json';
            self::$cells[$key] = is_file($file) ? (json_decode((string) file_get_contents($file), true) ?: []) : [];
        }

        return self::$cells[$key];
    }

    /** For tests that swap the data folder. */
    public static function forget(): void
    {
        self::$cells = [];
    }
}
