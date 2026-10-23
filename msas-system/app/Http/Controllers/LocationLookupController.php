<?php

namespace App\Http\Controllers;

use App\Services\LgaLookup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET ?lat=&lon= -> the State and LGA that point falls in, so the scan form
 * can fill both as soon as GPS is captured (web and app). The point is not
 * stored. A miss is { found: false }, not an error: the farmer picks by hand.
 * The scan itself repeats the lookup server side, so a form that never
 * called this (offline) still gets names.
 */
class LocationLookupController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lon' => 'required|numeric|between:-180,180',
        ]);

        $found = LgaLookup::locate((float) $data['lat'], (float) $data['lon']);

        return response()->json($found
            ? ['found' => true, 'state' => $found['state'], 'lga' => $found['lga'], 'source' => $found['source'], 'attribution' => LgaLookup::attribution()]
            : ['found' => false]);
    }
}
