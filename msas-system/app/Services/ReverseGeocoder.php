<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Suggestions for Community, Address/Landmark and Postal Code from a GPS
 * point, from OpenStreetMap. See config/geocoding.php and
 * resources/geo/README.md.
 *
 * Rules (owner decisions, 7 Nov 2026):
 * - Community: the nearest village, hamlet, town, suburb, neighbourhood or
 *   city (in that order), and only when that settlement lies in the same
 *   LGA as the point (GRID3 boundaries). Otherwise empty. From Nominatim:
 *   its settlement-level answer (zoom 14), else the first of those names in
 *   the point's address, located with a bounded settlement search. Names
 *   that are plainly a facility or a street are never suggested.
 * - Postal Code: only a 6-digit code Nominatim returns for the exact point.
 *   Never guessed, never taken from the settlement, LGA or State.
 * - Address/Landmark: the nearest named school, market, mosque, church or
 *   health centre within about 500 m, from our own copy of OpenStreetMap
 *   places (App\Services\NearbyPlaces, nothing sent out), written "Road,
 *   near Place" when Nominatim's road for the point is within 200 m.
 * - Any failure leaves its fields empty; State/LGA are not affected.
 *
 * Only Nominatim is called, from our server only, with the point rounded to
 * about 110 m; its answers are cached by that rounded point.
 */
class ReverseGeocoder
{
    /**
     * @return array{ok: bool, community: ?string, landmark: ?string, postal_code: ?string}
     */
    public function suggest(float $lat, float $lon, ?string $pointLga, ?string $pointState): array
    {
        $out = ['ok' => false, 'community' => null, 'landmark' => null, 'postal_code' => null];
        if (!config('geocoding.enabled') || $pointLga === null || $pointState === null) {
            return $out;
        }

        $d = (int) config('geocoding.round_decimals', 3);
        $rLat = round($lat, $d);
        $rLon = round($lon, $d);

        $nominatim = $this->cached("revgeo:v4:nom:{$rLat}:{$rLon}", fn () => $this->nominatimData($rLat, $rLon));
        if ($nominatim !== null) {
            $out['ok'] = true;
            $out['community'] = $this->community($nominatim, $rLat, $rLon, $pointState, $pointLga);
            $out['postal_code'] = $this->postalCode((string) ($nominatim['point_postcode'] ?? ''), $pointState);
        }

        // The landmark comes from our own copy of the places, at the exact point.
        $out['landmark'] = $this->landmark($lat, $lon, $nominatim['road'] ?? null);
        $out['ok'] = $out['ok'] || $out['landmark'] !== null;

        return $out;
    }

    // ── Field rules ─────────────────────────────────────────────────────────

    private function community(array $nom, float $lat, float $lon, string $state, string $lga): ?string
    {
        $types = config('geocoding.community_types');
        $inLga = function (?float $sLat, ?float $sLon) use ($state, $lga): bool {
            $where = $sLat === null ? null : LgaLookup::locate($sLat, $sLon);
            return $where !== null && $where['state'] === $state && $where['lga'] === $lga;
        };

        // 1. Nominatim's own settlement for the point, with its coordinates.
        $s = $nom['settlement'] ?? null;
        if ($s && in_array($s['type'], $types, true) && $s['name'] !== '') {
            return $inLga($s['lat'], $s['lon']) && !self::looksLikeAFacility($s['name']) ? $s['name'] : null;
        }

        // 2. The first settlement name in the address, in the order of preference.
        foreach ($types as $type) {
            $name = trim((string) ($nom['address'][$type] ?? ''));
            if ($name === '') {
                continue;
            }
            if (self::looksLikeAFacility($name)) {
                return null;   // mis-tagged map data (e.g. a city named after its stadium)
            }
            $found = $this->cached('revgeo:v4:find:' . md5(mb_strtolower($name) . "|{$lat}|{$lon}"), fn () => $this->findSettlement($name, $lat, $lon));

            return $found !== null && $inLga($found['lat'], $found['lon']) ? $name : null;
        }

        return null;
    }

    /**
     * A "settlement" name that is plainly a facility or a street is map data
     * tagged wrongly (7 Nov 2026: Katsina city is named "Muhammadu Dikko
     * Stadium" in OpenStreetMap). Such names are never suggested.
     */
    private static function looksLikeAFacility(string $name): bool
    {
        return (bool) preg_match('/\b(stadium|school|hospital|clinic|mosque|church|hotel|bank|plaza|university|college|office|street|road|avenue|junction|filling station|petrol station)\b/i', $name);
    }

    /** @param array{name: string, line: array}|null $road Nominatim's road for the point */
    private function landmark(float $lat, float $lon, ?array $road): ?string
    {
        $place = NearbyPlaces::nearest($lat, $lon, (float) config('geocoding.landmark_radius_m', 500));
        if ($place === null) {
            return null;
        }
        $roadName = $road !== null && self::distanceToLine($lat, $lon, $road['line']) <= (float) config('geocoding.road_radius_m', 200)
            ? $road['name'] : null;

        $near = __('near :place', ['place' => $place['name']]);

        return $roadName !== null && mb_strtolower($roadName) !== mb_strtolower($place['name'])
            ? $roadName . ', ' . $near
            : mb_strtoupper(mb_substr($near, 0, 1)) . mb_substr($near, 1);
    }

    /** A 6-digit code whose first digits belong to the point's State (config geocoding.postcode_prefixes), else null. */
    private function postalCode(string $postcode, string $state): ?string
    {
        $code = preg_replace('/\s+/', '', $postcode);
        if (!preg_match('/^\d{6}$/', $code)) {
            return null;
        }
        $prefixes = (array) (config('geocoding.postcode_prefixes')[$state] ?? []);

        return in_array(substr($code, 0, 2), $prefixes, true) ? $code : null;
    }

    // ── Nominatim ───────────────────────────────────────────────────────────

    /** The cached answer, or the fetched one (cached only when it succeeded). */
    private function cached(string $key, callable $fetch): ?array
    {
        if (($hit = Cache::get($key)) !== null) {
            return $hit;
        }
        $value = $fetch();
        if ($value !== null) {
            Cache::put($key, $value, now()->addDays((int) config('geocoding.cache_days', 30)));
        }

        return $value;
    }

    /** @return array{address: array, point_postcode: string, settlement: ?array, road: ?array}|null */
    private function nominatimData(float $lat, float $lon): ?array
    {
        try {
            // The point itself, with the shape of the object found (a road, when it is one).
            $point = $this->nominatim('reverse', ['lat' => $lat, 'lon' => $lon, 'zoom' => 18, 'addressdetails' => 1,
                'polygon_geojson' => 1, 'polygon_threshold' => 0.00005]);
            $place = $this->nominatim('reverse', ['lat' => $lat, 'lon' => $lon, 'zoom' => 14, 'addressdetails' => 1]);
        } catch (Throwable $e) {
            Log::warning('REVERSE_GEOCODE_FAILED', ['provider' => 'nominatim', 'error' => $e->getMessage()]);
            return null;
        }

        $type = (string) ($place['addresstype'] ?? $place['type'] ?? '');

        return [
            // The settlement fields of both answers, the point's own first.
            'address'        => array_merge((array) ($place['address'] ?? []), (array) ($point['address'] ?? [])),
            // The postcode Nominatim gives for the exact point only (never the settlement's).
            'point_postcode' => (string) ($point['address']['postcode'] ?? ''),
            'settlement'     => isset($place['lat'], $place['lon']) ? [
                'type' => $type, 'name' => trim((string) ($place['name'] ?? '')),
                'lat' => (float) $place['lat'], 'lon' => (float) $place['lon'],
            ] : null,
            'road'           => self::roadOf($point),
        ];
    }

    /** The named road Nominatim answered with, as a line of [lat, lon] points (at most 200). */
    private static function roadOf(array $point): ?array
    {
        $name = trim((string) ($point['name'] ?? ''));
        $geo = $point['geojson'] ?? null;
        if (($point['category'] ?? '') !== 'highway' || $name === '' || !is_array($geo)) {
            return null;
        }
        $coords = match ($geo['type'] ?? '') {
            'LineString' => $geo['coordinates'] ?? [],
            'MultiLineString', 'Polygon' => array_merge(...array_values((array) ($geo['coordinates'] ?? [[]]))),
            'Point' => [$geo['coordinates'] ?? []],
            default => [],
        };
        $line = [];
        foreach (array_slice($coords, 0, 200) as $c) {
            if (isset($c[0], $c[1])) {
                $line[] = ['lat' => (float) $c[1], 'lon' => (float) $c[0]];
            }
        }

        return $line ? ['name' => $name, 'line' => $line] : null;
    }

    /** A settlement with this name near the point (bounded search), or null. */
    private function findSettlement(string $name, float $lat, float $lon): ?array
    {
        $r = (float) config('geocoding.settlement_search_deg', 0.15);
        try {
            $hits = $this->nominatim('search', [
                'q' => $name, 'featureType' => 'settlement', 'bounded' => 1, 'limit' => 5,
                'viewbox' => implode(',', [$lon - $r, $lat + $r, $lon + $r, $lat - $r]),
            ]);
        } catch (Throwable $e) {
            Log::warning('REVERSE_GEOCODE_FAILED', ['provider' => 'nominatim-search', 'error' => $e->getMessage()]);
            return null;
        }

        return collect($hits)
            ->filter(fn ($h) => mb_strtolower(trim((string) ($h['name'] ?? ''))) === mb_strtolower($name) && isset($h['lat'], $h['lon']))
            ->map(fn ($h) => ['lat' => (float) $h['lat'], 'lon' => (float) $h['lon'], 'distance' => self::metres($lat, $lon, (float) $h['lat'], (float) $h['lon'])])
            ->sortBy('distance')
            ->first();
    }

    private function nominatim(string $endpoint, array $query): array
    {
        $this->waitForNominatimSlot();

        $query += ['format' => 'jsonv2', 'accept-language' => 'en'];
        if ($email = config('geocoding.nominatim.email')) {
            $query['email'] = $email;
        }
        $base = rtrim((string) config('geocoding.nominatim.url'), '/');

        return (array) Http::withHeaders(['User-Agent' => $this->userAgent()])
            ->timeout((int) config('geocoding.nominatim.timeout', 6))
            ->get("{$base}/{$endpoint}", $query)
            ->throw()
            ->json();
    }

    /** At most one Nominatim request per second for the whole application. */
    private function waitForNominatimSlot(): void
    {
        $interval = (int) config('geocoding.nominatim.min_interval_ms', 1100);
        if ($interval <= 0) {
            return;
        }
        Cache::lock('revgeo:nominatim-slot', 10)->block(10, function () use ($interval) {
            $last = (int) Cache::get('revgeo:nominatim-last-ms', 0);
            $waitMs = $last + $interval - (int) floor(microtime(true) * 1000);
            if ($waitMs > 0) {
                usleep($waitMs * 1000);
            }
            Cache::put('revgeo:nominatim-last-ms', (int) floor(microtime(true) * 1000), 60);
        });
    }

    private function userAgent(): string
    {
        return 'MSAS-FarmAI/1.0 (+' . rtrim((string) config('app.url'), '/') . ')';
    }

    // ── Geometry ────────────────────────────────────────────────────────────

    public static function metres(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($a)));
    }

    /** Shortest distance (m) from the point to a road drawn as a line of points. */
    private static function distanceToLine(float $lat, float $lon, array $line): float
    {
        $best = INF;
        $k = cos(deg2rad($lat)) * 111320;   // metres per degree of longitude here
        $prev = null;
        foreach ($line as $pt) {
            $cur = [((float) $pt['lon'] - $lon) * $k, ((float) $pt['lat'] - $lat) * 110540];
            if ($prev === null) {
                $best = min($best, hypot($cur[0], $cur[1]));
            } else {
                [$dx, $dy] = [$cur[0] - $prev[0], $cur[1] - $prev[1]];
                $len2 = $dx * $dx + $dy * $dy;
                $t = $len2 > 0 ? max(0, min(1, -($prev[0] * $dx + $prev[1] * $dy) / $len2)) : 0;
                $best = min($best, hypot($prev[0] + $t * $dx, $prev[1] + $t * $dy));
            }
            $prev = $cur;
        }

        return $best;
    }
}
