<?php

namespace App\Services;

/**
 * Which Nigerian State and LGA a GPS point falls in, named exactly as the
 * State/LGA dropdowns (App\Data\NigeriaLocations), so a captured location can
 * fill both fields. Point-in-polygon against resources/geo/nga-lgas.json:
 * GRID3 boundaries (2022) via geoBoundaries, CC BY 4.0, built by
 * scripts/geo/build_lga_index.py.
 *
 * Community cannot be looked up: there are no open community boundaries.
 * A point outside every LGA (offshore, outside Nigeria, or in a sliver the
 * simplified boundaries miss) returns null; the farmer then picks by hand.
 */
final class LgaLookup
{
    private const FILE = 'geo/nga-lgas.json';

    private static ?array $index = null;

    /** @return array{state: string, lga: string, source: string}|null */
    public static function locate(?float $lat, ?float $lon): ?array
    {
        if ($lat === null || $lon === null || !LocationValidationService::isPlausiblyInNigeria($lat, $lon)) {
            return null;
        }
        foreach (self::index()['features'] as $f) {
            [$minX, $minY, $maxX, $maxY] = $f['bbox'];
            if ($lon < $minX || $lon > $maxX || $lat < $minY || $lat > $maxY) {
                continue;
            }
            if (self::inPolygons($lon, $lat, $f['polygons'])) {
                return ['state' => $f['state'], 'lga' => $f['lga'], 'source' => self::index()['version']];
            }
        }
        return null;
    }

    /** One LGA's outline (for the map preview), or null. */
    public static function outline(string $state, string $lga): ?array
    {
        foreach (self::index()['features'] as $f) {
            if ($f['state'] === $state && $f['lga'] === $lga) {
                return ['bbox' => $f['bbox'], 'polygons' => $f['polygons']];
            }
        }
        return null;
    }

    public static function attribution(): string
    {
        return self::index()['attribution'] . ' ' . self::index()['licence'];
    }

    private static function index(): array
    {
        return self::$index ??= json_decode(file_get_contents(resource_path(self::FILE)), true);
    }

    /** Even-odd ray casting; the first ring of each polygon is the outline, the rest are holes. */
    private static function inPolygons(float $x, float $y, array $polygons): bool
    {
        foreach ($polygons as $rings) {
            if (self::inRing($x, $y, $rings[0])) {
                $inHole = false;
                for ($h = 1, $n = count($rings); $h < $n; $h++) {
                    if (self::inRing($x, $y, $rings[$h])) {
                        $inHole = true;
                        break;
                    }
                }
                if (!$inHole) {
                    return true;
                }
            }
        }
        return false;
    }

    private static function inRing(float $x, float $y, array $ring): bool
    {
        $inside = false;
        $n = count($ring);
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            [$xi, $yi] = $ring[$i];
            [$xj, $yj] = $ring[$j];
            if ((($yi > $y) !== ($yj > $y)) && ($x < ($xj - $xi) * ($y - $yi) / ($yj - $yi) + $xi)) {
                $inside = !$inside;
            }
        }
        return $inside;
    }
}
