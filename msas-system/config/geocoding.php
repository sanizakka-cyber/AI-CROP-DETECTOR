<?php

/*
|--------------------------------------------------------------------------
| Reverse geocoding (place-name suggestions from a GPS point)
|--------------------------------------------------------------------------
|
| Community, Address/Landmark and Postal Code are suggested from OpenStreetMap
| data, on our server only (never from the browser or the app):
|
| - Nominatim (https://nominatim.openstreetmap.org): the address of the point
|   (settlement name, road, postcode) and its settlement. Usage policy: at
|   most 1 request per second for the whole application, an identifying
|   User-Agent, cached results.
| - Our own copy of named places (resources/geo/places): the landmark.
|   Nothing is sent out for it. (The public Overpass servers were tried
|   first, 7 Nov 2026; they never answered from Render.)
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

    // Our own copy of named schools, markets, mosques, churches and health
    // centres (built by scripts/geo/build_place_index.py), for the landmark.
    // Read locally; nothing is sent out.
    'places_path' => resource_path('geo/places'),

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
