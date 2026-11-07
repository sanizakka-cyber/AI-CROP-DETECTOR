<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Suggestions for Community, Address/Landmark and Postal Code from a GPS
 * point, from OpenStreetMap, called from our server only. See
 * config/geocoding.php and resources/geo/README.md.
 *
 * Rules (owner decision, 7 Nov 2026):
 * - Community: the nearest village, hamlet, town, suburb, neighbourhood or
 *   city (in that order), and only when that settlement lies in the same
 *   LGA as the point (GRID3 boundaries). Otherwise empty. From Nominatim:
 *   its settlement-level answer (zoom 14), else the first of those names in
 *   the point's address, located with a bounded settlement search.
 * - Postal Code: only a 6-digit code Nominatim returns for the point. Never
 *   guessed, never taken from the LGA or State.
 * - Address/Landmark: the nearest named school, market, mosque, church or
 *   health centre within about 500 m ("Road, near Place" with a named road
 *   within 200 m). From Overpass, which is often busy: when it does not
 *   answer, only the landmark stays empty.
 * - Any failure leaves its fields empty; State/LGA are not affected.
 *
 * Coordinates are rounded (about 110 m) before they leave the server; each
 * provider's answers are cached by that rounded point.
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

        $nominatim = $this->cached("revgeo:v3:nom:{$rLat}:{$rLon}", fn () => $this->nominatimData($rLat, $rLon));
        if ($nominatim !== null) {
            $out['ok'] = true;
            $out['community'] = $this->community($nominatim, $rLat, $rLon, $pointState, $pointLga);
            $out['postal_code'] = $this->postalCode((string) ($nominatim['point_postcode'] ?? ''));
        }

        // After Overpass fails, it is skipped for a while, so a busy server
        // does not make every lookup wait for its timeouts.
        $nearby = Cache::has('revgeo:overpass-resting') ? null
            : $this->cached("revgeo:v2:ovp:{$rLat}:{$rLon}", function () use ($rLat, $rLon) {
                $data = $this->overpassData($rLat, $rLon);
                if ($data === null) {
                    Cache::put('revgeo:overpass-resting', true, now()->addMinutes((int) config('geocoding.overpass.rest_minutes', 10)));
                }
                return $data;
            });
        if ($nearby !== null) {
            $out['ok'] = true;
            $out['landmark'] = $this->landmark($nearby);
        }

        return $out;
    }

    // â”€â”€ Field rules â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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
            $found = $this->cached('revgeo:v2:find:' . md5(mb_strtolower($name) . "|{$lat}|{$lon}"), fn () => $this->findSettlement($name, $lat, $lon));

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

    private function landmark(array $nearby): ?string
    {
        $place = collect($nearby['places'] ?? [])
            ->filter(fn ($p) => $p['distance'] <= config('geocoding.landmark_radius_m'))
            ->sortBy('distance')
            ->first();
        if ($place === null) {
            return null;
        }
        $road = collect($nearby['roads'] ?? [])
            ->filter(fn ($r) => $r['distance'] <= config('geocoding.road_radius_m'))
            ->sortBy('distance')
            ->first();

        $near = __('near :place', ['place' => $place['name']]);

        return $road && mb_strtolower($road['name']) !== mb_strtolower($place['name'])
            ? $road['name'] . ', ' . $near
            : mb_strtoupper(mb_substr($near, 0, 1)) . mb_substr($near, 1);
    }

    private function postalCode(string $postcode): ?string
    {
        $code = preg_replace('/\s+/', '', $postcode);

        return preg_match('/^\d{6}$/', $code) ? $code : null;
    }

    // â”€â”€ Providers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

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

    /** @return array{address: array, settlement: ?array}|null */
    private function nominatimData(float $lat, float $lon): ?array
    {
        try {
            $point = $this->nominatim('reverse', ['lat' => $lat, 'lon' => $lon, 'zoom' => 18, 'addressdetails' => 1]);
            $place = $this->nominatim('reverse', ['lat' => $lat, 'lon' => $lon, 'zoom' => 14, 'addressdetails' => 1]);
        } catch (Throwable $e) {
            Log::warning('REVERSE_GEOCODE_FAILED', ['provider' => 'nominatim', 'error' => $e->getMessage()]);
            return null;
        }

        $type = (string) ($place['addresstype'] ?? $place['type'] ?? '');

        return [
            // The settlement fields of both answers, the point's own first.
            'address'    => array_merge((array) ($place['address'] ?? []), (array) ($point['address'] ?? [])),
            // The postcode Nominatim gives for the exact point only (never the settlement's).
            'point_postcode' => (string) ($point['address']['postcode'] ?? ''),
            'settlement' => isset($place['lat'], $place['lon']) ? [
                'type' => $type, 'name' => trim((string) ($place['name'] ?? '')),
                'lat' => (float) $place['lat'], 'lon' => (float) $place['lon'],
            ] : null,
        ];
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

    /** Named places within 500 m and named roads within 200 m, from the first Overpass server that answers. */
    private function overpassData(float $lat, float $lon): ?array
    {
        $p = (int) config('geocoding.landmark_radius_m');
        $r = (int) config('geocoding.road_radius_m');
        $query = <<<OQL
[out:json][timeout:8];
(
  nwr(around:{$p},{$lat},{$lon})[name][amenity~"^(school|college|university|kindergarten|marketplace|place_of_worship|clinic|hospital|doctors|health_post)$"];
  nwr(around:{$p},{$lat},{$lon})[name][healthcare];
)->.near;
way(around:{$r},{$lat},{$lon})[highway][name]->.roads;
.near out tags center;
.roads out tags geom;
OQL;
        foreach ((array) config('geocoding.overpass.urls') as $url) {
            try {
                $res = Http::withHeaders(['User-Agent' => $this->userAgent()])
                    ->asForm()
                    ->timeout((int) config('geocoding.overpass.timeout', 6))
                    ->post($url, ['data' => $query])
                    ->throw();
                $elements = $res->json('elements');
                if (!is_array($elements)) {
                    throw new \RuntimeException('not an Overpass answer');
                }

                return $this->parseOverpass($elements, $lat, $lon);
            } catch (Throwable $e) {
                Log::warning('REVERSE_GEOCODE_FAILED', ['provider' => 'overpass', 'url' => $url, 'error' => $e->getMessage()]);
            }
        }

        return null;
    }

    private function parseOverpass(array $elements, float $lat, float $lon): array
    {
        $out = ['places' => [], 'roads' => []];
        foreach ($elements as $el) {
            $tags = $el['tags'] ?? [];
            $name = trim((string) ($tags['name:en'] ?? $tags['name'] ?? ''));
            if ($name === '') {
                continue;
            }
            if (isset($tags['highway']) && ($el['type'] ?? '') === 'way') {
                $out['roads'][] = ['name' => $name, 'distance' => self::distanceToLine($lat, $lon, $el['geometry'] ?? [])];
                continue;
            }
            [$eLat, $eLon] = self::centre($el);
            if ($eLat !== null) {
                $out['places'][] = ['name' => $name, 'distance' => self::metres($lat, $lon, $eLat, $eLon)];
            }
        }

        return $out;
    }

    private function userAgent(): string
    {
        return 'MSAS-FarmAI/1.0 (+' . rtrim((string) config('app.url'), '/') . ')';
    }

    // â”€â”€ Geometry â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /** @return array{0: ?float, 1: ?float} */
    private static function centre(array $el): array
    {
        if (isset($el['lat'], $el['lon'])) {
            return [(float) $el['lat'], (float) $el['lon']];
        }
        if (isset($el['center']['lat'], $el['center']['lon'])) {
            return [(float) $el['center']['lat'], (float) $el['center']['lon']];
        }

        return [null, null];
    }

    public static function metres(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($a)));
    }

    /** Shortest distance (m) from the point to a road drawn as a line of points. */
    private static function distanceToLine(float $lat, float $lon, array $geometry): float
    {
        $best = INF;
        $k = cos(deg2rad($lat)) * 111320;   // metres per degree of longitude here
        $prev = null;
        foreach ($geometry as $pt) {
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
