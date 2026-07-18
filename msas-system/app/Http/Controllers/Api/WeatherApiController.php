<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class WeatherApiController extends Controller
{
    // Nigerian state → [lat, lon]
    private const STATE_COORDS = [
        'Abia'           => [5.4527, 7.5248],
        'Adamawa'        => [9.3265, 12.3984],
        'Akwa Ibom'      => [5.0077, 7.8536],
        'Anambra'        => [6.2209, 6.9370],
        'Bauchi'         => [10.3158, 9.8442],
        'Bayelsa'        => [4.7719, 6.0699],
        'Benue'          => [7.3369, 8.7400],
        'Borno'          => [11.8846, 13.1571],
        'Cross River'    => [5.9631, 8.3302],
        'Delta'          => [5.8904, 5.6797],
        'Ebonyi'         => [6.2649, 8.0137],
        'Edo'            => [6.3350, 5.6037],
        'Ekiti'          => [7.7190, 5.3110],
        'Enugu'          => [6.4584, 7.5464],
        'FCT'            => [9.0579, 7.4951],
        'Abuja'          => [9.0579, 7.4951],
        'Gombe'          => [10.2897, 11.1673],
        'Imo'            => [5.4896, 7.0275],
        'Jigawa'         => [12.2280, 9.5616],
        'Kaduna'         => [10.5264, 7.4388],
        'Kano'           => [12.0022, 8.5920],
        'Katsina'        => [12.9908, 7.6018],
        'Kebbi'          => [12.4539, 4.1975],
        'Kogi'           => [7.7337, 6.6906],
        'Kwara'          => [8.4966, 4.5426],
        'Lagos'          => [6.5244, 3.3792],
        'Nasarawa'       => [8.5380, 8.3255],
        'Niger'          => [9.9309, 5.5983],
        'Ogun'           => [7.1600, 3.3489],
        'Ondo'           => [7.2526, 5.1994],
        'Osun'           => [7.5629, 4.5200],
        'Oyo'            => [7.8500, 3.9300],
        'Plateau'        => [9.2182, 9.5179],
        'Rivers'         => [4.8156, 7.0498],
        'Sokoto'         => [13.0059, 5.2476],
        'Taraba'         => [7.9990, 10.7740],
        'Yobe'           => [12.2939, 11.4390],
        'Zamfara'        => [12.1700, 6.6620],
    ];

    // WMO weather code → description + emoji
    private const WEATHER_CODES = [
        0  => ['Clear sky',          '☀️'],
        1  => ['Mainly clear',       '🌤'],
        2  => ['Partly cloudy',      '⛅'],
        3  => ['Overcast',           '☁️'],
        45 => ['Foggy',              '🌫'],
        48 => ['Freezing fog',       '🌫'],
        51 => ['Light drizzle',      '🌦'],
        53 => ['Drizzle',            '🌦'],
        55 => ['Heavy drizzle',      '🌧'],
        61 => ['Slight rain',        '🌧'],
        63 => ['Moderate rain',      '🌧'],
        65 => ['Heavy rain',         '🌧'],
        71 => ['Slight snow',        '🌨'],
        73 => ['Moderate snow',      '🌨'],
        75 => ['Heavy snow',         '❄️'],
        77 => ['Snow grains',        '❄️'],
        80 => ['Slight showers',     '🌦'],
        81 => ['Moderate showers',   '🌧'],
        82 => ['Violent showers',    '⛈'],
        85 => ['Slight snow shower', '🌨'],
        86 => ['Heavy snow shower',  '🌨'],
        95 => ['Thunderstorm',       '⛈'],
        96 => ['Thunderstorm+hail',  '⛈'],
        99 => ['Thunderstorm+hail',  '⛈'],
    ];

    /** GET /weather?state=Katsina  (or ?lat=12.99&lon=7.60) */
    public function current(Request $request): JsonResponse
    {
        [$lat, $lon, $locationName] = $this->resolveLocation($request);

        $cacheKey = "weather:{$lat}:{$lon}";

        $data = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($lat, $lon) {
            return $this->fetchOpenMeteo($lat, $lon);
        });

        if (! $data) {
            return response()->json(['message' => 'Weather data unavailable. Try again later.'], 503);
        }

        return response()->json(array_merge($data, ['location' => $locationName]));
    }

    private function resolveLocation(Request $request): array
    {
        // Explicit coordinates take priority (for precise GPS)
        if ($request->filled('lat') && $request->filled('lon')) {
            return [(float) $request->lat, (float) $request->lon, 'Your Location'];
        }

        $state = $request->get('state', 'Katsina');
        // Strip "State" suffix if present
        $state = preg_replace('/\s+State$/i', '', trim($state));

        $coords = self::STATE_COORDS[$state] ?? self::STATE_COORDS['Katsina'];

        return [$coords[0], $coords[1], $state . ' State'];
    }

    private function fetchOpenMeteo(float $lat, float $lon): ?array
    {
        try {
            $res = Http::timeout(10)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude'                    => $lat,
                'longitude'                   => $lon,
                'current'                     => 'temperature_2m,relative_humidity_2m,precipitation,weather_code,wind_speed_10m,apparent_temperature,uv_index',
                'daily'                       => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max,uv_index_max',
                'hourly'                      => 'temperature_2m,precipitation_probability',
                'wind_speed_unit'             => 'kmh',
                'timezone'                    => 'Africa/Lagos',
                'forecast_days'               => 7,
            ]);

            if (! $res->successful()) return null;

            $raw     = $res->json();
            $current = $raw['current']        ?? [];
            $daily   = $raw['daily']          ?? [];
            $hourly  = $raw['hourly']         ?? [];

            $code     = (int) ($current['weather_code'] ?? 0);
            $codeInfo = self::WEATHER_CODES[$code] ?? ['Weather', '🌡'];

            // Build 7-day forecast
            $forecast = [];
            $days     = $daily['time']                     ?? [];
            foreach ($days as $i => $date) {
                $dc = (int) ($daily['weather_code'][$i] ?? 0);
                $di = self::WEATHER_CODES[$dc] ?? ['—', '🌡'];
                $forecast[] = [
                    'date'          => $date,
                    'day'           => date('D', strtotime($date)),
                    'weather_code'  => $dc,
                    'description'   => $di[0],
                    'emoji'         => $di[1],
                    'temp_max'      => round($daily['temperature_2m_max'][$i] ?? 0),
                    'temp_min'      => round($daily['temperature_2m_min'][$i] ?? 0),
                    'precipitation' => round($daily['precipitation_sum'][$i] ?? 0, 1),
                    'rain_chance'   => (int) ($daily['precipitation_probability_max'][$i] ?? 0),
                    'uv_max'        => round($daily['uv_index_max'][$i] ?? 0, 1),
                ];
            }

            // Next 6 hours temperature trend
            $now     = now()->setTimezone('Africa/Lagos');
            $hourTimes = $hourly['time'] ?? [];
            $hourTemps = $hourly['temperature_2m'] ?? [];
            $hourRain  = $hourly['precipitation_probability'] ?? [];
            $hourlyNext = [];
            foreach ($hourTimes as $j => $ht) {
                if (count($hourlyNext) >= 6) break;
                try {
                    $t = \Carbon\Carbon::parse($ht, 'Africa/Lagos');
                    if ($t->greaterThan($now)) {
                        $hourlyNext[] = [
                            'time'      => $t->format('H:i'),
                            'temp'      => round($hourTemps[$j] ?? 0),
                            'rain_chance' => (int) ($hourRain[$j] ?? 0),
                        ];
                    }
                } catch (\Exception) {}
            }

            $temp  = round($current['temperature_2m'] ?? 0);
            $feels = round($current['apparent_temperature'] ?? $temp);
            $uv    = round($current['uv_index'] ?? 0, 1);

            // Agricultural advisory
            $advisory = $this->buildAdvisory(
                $code,
                $current['precipitation'] ?? 0,
                $uv,
                $current['wind_speed_10m'] ?? 0
            );

            return [
                'current' => [
                    'temperature'   => $temp,
                    'feels_like'    => $feels,
                    'humidity'      => (int) ($current['relative_humidity_2m'] ?? 0),
                    'precipitation' => round($current['precipitation'] ?? 0, 1),
                    'wind_speed'    => round($current['wind_speed_10m'] ?? 0, 1),
                    'uv_index'      => $uv,
                    'weather_code'  => $code,
                    'description'   => $codeInfo[0],
                    'emoji'         => $codeInfo[1],
                ],
                'forecast'     => $forecast,
                'hourly_next6' => $hourlyNext,
                'advisory'     => $advisory,
                'fetched_at'   => now()->toISOString(),
            ];
        } catch (\Exception) {
            return null;
        }
    }

    private function buildAdvisory(int $code, float $rain, float $uv, float $wind): array
    {
        $tips = [];
        $alerts = [];

        if ($rain > 5) {
            $alerts[] = '⚠️ Heavy rainfall — postpone spraying and harvesting.';
            $tips[]   = 'Cover stored grain and protect seedlings from waterlogging.';
        } elseif ($rain > 0) {
            $tips[] = 'Light rain expected — natural irrigation. Check field drainage.';
        }

        if ($uv > 8) {
            $alerts[] = '☀️ Extreme UV index — apply agro-chemicals early morning or evening.';
        } elseif ($uv > 5) {
            $tips[] = 'High UV — good weather for drying harvested produce.';
        }

        if ($wind > 40) {
            $alerts[] = '💨 Strong winds — secure crop covers and irrigation equipment.';
        }

        if (in_array($code, [95, 96, 99])) {
            $alerts[] = '⛈ Thunderstorm forecast — stay indoors and protect livestock.';
        }

        if (empty($tips) && empty($alerts)) {
            $tips[] = 'Favourable conditions for field work and livestock management.';
        }

        return ['tips' => $tips, 'alerts' => $alerts];
    }
}
