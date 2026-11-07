<?php

/*
|--------------------------------------------------------------------------
| Reverse geocoding (place-name suggestions from a GPS point)
|--------------------------------------------------------------------------
|
| Community, Address/Landmark and Postal Code are suggested from OpenStreetMap
| data, called from our server only (never from the browser or the app):
|
| - Nominatim (https://nominatim.openstreetmap.org): the address of the point
|   (settlement name, postcode). Usage policy: at most 1 request per second
|   for the whole application, an identifying User-Agent, cached results.
| - Overpass (https://overpass-api.de): settlements, named roads and named
|   places (schools, markets, mosques, churches, health centres) near the
|   point, to check the settlement is in the same LGA and to build the
|   landmark.
|
| Data © OpenStreetMap contributors, ODbL. State and LGA never come from
| here: they stay on the GRID3 boundaries (App\Services\LgaLookup).
|
| REVERSE_GEOCODING_ENABLED=false switches every lookup off at once (the
| OSMF policy requires that we can stop without an app update); the fields
| then stay empty for the farmer to fill.
|
*/

return [

    'enabled' => env('REVERSE_GEOCODING_ENABLED', true),

    // Coordinates are rounded to this many decimals before they leave our
    // server and for the cache key (3 decimals is about 110 m).
    'round_decimals' => 3,

    'cache_days' => 30,

    // Device readings worse than this fill nothing (laptops estimate from
    // Wi-Fi or the internet connection); the farmer drags the pin instead.
    'max_auto_fill_accuracy_m' => 1000,

    'nominatim' => [
        'url'        => env('NOMINATIM_URL', 'https://nominatim.openstreetmap.org'),
        'email'      => env('NOMINATIM_EMAIL'),   // optional contact address sent with each request
        'timeout'    => 6,
        'min_interval_ms' => 1100,                // a little over 1 request per second, for the whole app
    ],

    // Public Overpass servers are often busy: each gets a short try, in order.
    // When none answers, only the landmark stays empty.
    'overpass' => [
        'urls'    => array_filter(explode(',', (string) env('OVERPASS_URLS',
            'https://overpass-api.de/api/interpreter,https://overpass.private.coffee/api/interpreter'))),
        'timeout' => 6,
    ],

    // Settlement types for Community, in order of preference.
    'community_types' => ['village', 'hamlet', 'town', 'suburb', 'neighbourhood', 'city'],

    // Half-width (degrees, about 16 km) of the box searched for a settlement
    // Nominatim names in the address, to place it in an LGA.
    'settlement_search_deg' => 0.15,

    // Landmark: a named place of these kinds within this distance, plus the
    // name of a road this close to the point.
    'landmark_radius_m' => 500,
    'road_radius_m'     => 200,

    'attribution' => '© OpenStreetMap contributors',
];
