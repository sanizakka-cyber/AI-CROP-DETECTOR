<?php

namespace App\Http\Controllers;

use App\Services\LgaLookup;
use App\Services\ReverseGeocoder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET ?lat=&lon=[&accuracy=] -> the State and LGA that point falls in, plus
 * suggestions for Community, Address/Landmark and Postal Code, so the scan
 * and profile forms can fill them as soon as a location is captured (web and
 * app). The point is not stored. A miss is { found: false }, not an error:
 * the farmer picks by hand. The scan itself repeats the State/LGA lookup
 * server side, so a form that never called this (offline) still gets them.
 *
 * accuracy is the device's reported accuracy in metres; a reading worse than
 * config('geocoding.max_auto_fill_accuracy_m') fills nothing (laptops only
 * estimate their position). A point the farmer placed on the map is sent
 * without accuracy.
 */
class LocationLookupController extends Controller
{
    public function __invoke(Request $request, ReverseGeocoder $geocoder): JsonResponse
    {
        $data = $request->validate([
            'lat'      => 'required|numeric|between:-90,90',
            'lon'      => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0',
            'suggest'  => 'nullable|boolean',
        ]);

        if (isset($data['accuracy']) && (float) $data['accuracy'] > (float) config('geocoding.max_auto_fill_accuracy_m', 1000)) {
            return response()->json(['found' => false, 'low_accuracy' => true]);
        }

        $lat = (float) $data['lat'];
        $lon = (float) $data['lon'];
        $found = LgaLookup::locate($lat, $lon);
        if (!$found) {
            return response()->json(['found' => false]);
        }

        $body = [
            'found'       => true,
            'state'       => $found['state'],
            'lga'         => $found['lga'],
            'source'      => $found['source'],
            'attribution' => LgaLookup::attribution(),
        ];

        // Place-name suggestions take a few seconds (outside services), so
        // forms ask for them in a second call (suggest=1) after State/LGA are
        // shown; app builds that never ask keep the fast answer.
        if ($request->boolean('suggest')) {
            $suggest = $geocoder->suggest($lat, $lon, $found['lga'], $found['state']);
            // Suggestions only; the farmer checks, edits or clears them.
            $body['suggestions'] = [
                'ok'          => $suggest['ok'],
                'community'   => $suggest['community'],
                'landmark'    => $suggest['landmark'],
                'postal_code' => $suggest['postal_code'],
                'attribution' => config('geocoding.attribution'),
            ];
        }

        return response()->json($body);
    }
}
