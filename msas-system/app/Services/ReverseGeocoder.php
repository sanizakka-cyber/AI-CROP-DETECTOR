<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Suggestions for Community, Address/Landmark and Postal Code from a GPS
 * point, from OpenStreetMap (Nominatim + Overpass), called from our server
 * only. See config/geocoding.php and resources/geo/README.md.
 *
 * Rules (owner decision, 7 Nov 2026):
 * - Community: the settlement Nominatim gives for the point, preferring
 *   village, hamlet, town, suburb, neighbourhood, city in that order, and
 *   only when that settlement lies in the same LGA as the point (GRID3
 *   boundaries). Otherwise empty.
 * - Address/Landmark: the nearest named school, market, mosque, church or
 *   health centre within about 500 m, with the name of a road next to the
 *   point when there is one. No such place: empty.
 * - Postal Code: only a 6-digit code the provider returns for the point.
 *   Never guessed, never taken from the LGA or State.
 * - Any failure leaves all three empty; State/LGA are not affected.
 *
 * Coordinates are rounded (about 110 m) before they leave the server; the
 * provider answers are cached by that rounded point.
 */
class ReverseGeocoder
{
    /**
     * @return array{ok: bool, community: ?string, landmark: ?string, postal_code: ?string}
     */
    public function suggest(float $lat, float $lon, ?string $pointLga, ?string $pointState): array
    {
        $empty = ['ok' => false, 'community' => null, 'landmark' => null, 'postal_code' => null];
        if (!config('geocoding.enabled') || $pointLga === null || $pointState === null) {
            return $empty;
        }

        $data = $this->providerData($lat, $lon);
        if ($data === null) {
            return $empty;
        }

        return [
            'ok'          => true,
            'community'   => $this->community($data, $pointState, $pointLga),
            'landmark'    => $this->landmark($data),
            'postal_code' => $this->postalCode($data),
        ];
    }

    // ── Field rules ─────────────────────────────────────────────────────────

    private function community(array $data, string $state, string $lga): ?string
    {
        $address = $data['address'] ?? [];
        foreach (config('geocoding.community_types') as $type) {
            $name = trim((string) ($address[$type] ?? ''));
            if ($name === '') {
                continue;
            }
            // The nearest settlement element with that name, placed in an LGA by the boundaries.
            $match = collect($data['settlements'] ?? [])
                ->filter(fn ($s) => mb_strtolower($s['name']) === mb_strtolower($name))
                ->sortBy('distance')
                ->first();
            if ($match === null) {
                return null;   // cannot tell which LGA it is in: leave it to the farmer
            }
            $where = LgaLookup::locate($match['lat'], $match['lon']);

            return $where && $where['state'] === $state && $where['lga'] === $lga ? $name : null;
        }

        return null;
    }

    private function landmark(array $data): ?string
    {
        $place = collect($data['places'] ?? [])
            ->filter(fn ($p) => $p['distance'] <= config('geocoding.landmark_radius_m'))
            ->sortBy('distance')
            ->first();
        if ($place === null) {
            return null;
        }
        $road = collect($data['roads'] ?? [])
            ->filter(fn ($r) => $r['distance'] <= config('geocoding.road_radius_m'))
            ->sortBy('distance')
            ->first();

        $near = __('near :place', ['place' => $place['name']]);

        return $road && mb_strtolower($road['name']) !== mb_strtolower($place['name'])
            ? $road['name'] . ', ' . $near
            : ucfirst($near);
    }

    private function postalCode(array $data): ?string
    {
        $code = preg_replace('/\s+/', '', (string) ($data['address']['postcode'] ?? ''));

        return preg_match('/^\d{6}$/', $code) ? $code : null;
    }

    // ── Providers (cached) ──────────────────────────────────────────────────

    /** Parsed provider answers for the rounded point, or null when a lookup failed. */
    private function providerData(float $lat, float $lon): ?array
    {
        $d = (int) config('geocoding.round_decimals', 3);
        $rLat = round($lat, $d);
        $rLon = round($lon, $d);
        $key = "revgeo:v1:{$rLat}:{$rLon}";

        if (($cached = Cache::get($key)) !== null) {
            return $cached;
        }

        try {
            $address = $this->nominatim($rLat, $rLon);
            $nearby = $this->overpass($rLat, $rLon);
        } catch (Throwable $e) {
            Log::warning('REVERSE_GEOCODE_FAILED', ['error' => $e->getMessage()]);
            return null;
        }

        $data = ['address' => $address] + $nearby;
        Cache::put($key, $data, now()->addDays((int) config('geocoding.cache_days', 30)));

        return $data;
    }

    private function nominatim(float $lat, float $lon): array
    {
        $this->waitForNominatimSlot();

        $query = ['format' => 'jsonv2', 'lat' => $lat, 'lon' => $lon, 'zoom' => 18, 'addressdetails' => 1, 'accept-language' => 'en'];
        if ($email = config('geocoding.nominatim.email')) {
            $query['email'] = $email;
        }
        $res = Http::withHeaders(['User-Agent' => $this->userAgent()])
            ->timeout((int) config('geocoding.nominatim.timeout', 6))
            ->get(config('geocoding.nominatim.url'), $query)
            ->throw();

        return (array) ($res->json('address') ?? []);
    }

    /** At most one Nominatim request per second for the whole application. */
    private function waitForNominatimSlot(): void
    {
        $interval = (int) config('geocoding.nominatim.min_interval_ms', 1100);
        Cache::lock('revgeo:nominatim-slot', 5)->block(5, function () use ($interval) {
            $last = (int) Cache::get('revgeo:nominatim-last-ms', 0);
            $waitMs = $last + $interval - (int) floor(microtime(true) * 1000);
            if ($waitMs > 0) {
                usleep($waitMs * 1000);
            }
            Cache::put('revgeo:nominatim-last-ms', (int) floor(microtime(true) * 1000), 60);
        });
    }

    /** @return array{settlements: array, places: array, roads: array} */
    private function overpass(float $lat, float $lon): array
    {
        $types = implode('|', config('geocoding.community_types'));
        $s = (int) config('geocoding.settlement_radius_m');
        $p = (int) config('geocoding.landmark_radius_m');
        $r = (int) config('geocoding.road_radius_m');
        $query = <<<OQL
[out:json][timeout:10];
(
  nwr(around:{$s},{$lat},{$lon})[place~"^({$types})$"][name];
  nwr(around:{$p},{$lat},{$lon})[name][amenity~"^(school|college|university|kindergarten|marketplace|place_of_worship|clinic|hospital|doctors|health_post)$"];
  nwr(around:{$p},{$lat},{$lon})[name][healthcare];
)->.near;
way(around:{$r},{$lat},{$lon})[highway][name]->.roads;
.near out tags center;
.roads out tags geom;
OQL;
        $res = Http::withHeaders(['User-Agent' => $this->userAgent()])
            ->asForm()
            ->timeout((int) config('geocoding.overpass.timeout', 10))
            ->post(config('geocoding.overpass.url'), ['data' => $query])
            ->throw();

        $out = ['settlements' => [], 'places' => [], 'roads' => []];
        foreach ((array) $res->json('elements', []) as $el) {
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
            if ($eLat === null) {
                continue;
            }
            $distance = self::metres($lat, $lon, $eLat, $eLon);
            if (isset($tags['place'])) {
                $out['settlements'][] = ['name' => $name, 'type' => $tags['place'], 'lat' => $eLat, 'lon' => $eLon, 'distance' => $distance];
            } else {
                $out['places'][] = ['name' => $name, 'distance' => $distance];
            }
        }

        return $out;
    }

    private function userAgent(): string
    {
        return 'MSAS-FarmAI/1.0 (+' . rtrim((string) config('app.url'), '/') . ')';
    }

    // ── Geometry ────────────────────────────────────────────────────────────

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
