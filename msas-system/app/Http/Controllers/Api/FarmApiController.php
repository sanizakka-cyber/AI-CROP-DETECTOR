<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FarmRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FarmApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $farms = FarmRecord::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json(['data' => $farms]);
    }

    public function store(Request $request): JsonResponse
    {
        // Field names match the farm_records migration exactly — a previous
        // version of this validation (farm_name/farm_size/location/soil_type)
        // referenced columns that were never created, so every create()
        // failed with a live SQL "column does not exist" 500. No mobile
        // screen calls farmsAPI.create() yet, so realigning to the real
        // schema here breaks nothing currently in use.
        $validated = $request->validate([
            'crop_type'      => ['required', 'string', 'max:100'],
            'plot_size'      => ['sometimes', 'numeric', 'min:0'],
            'planting_date'  => ['sometimes', 'date'],
            'harvest_date'   => ['sometimes', 'date'],
            'yield_kg'       => ['sometimes', 'numeric', 'min:0'],
            'growth_stage'   => ['sometimes', 'string', 'max:100'],
            'inputs_used'    => ['sometimes', 'string', 'max:1000'],
            'notes'          => ['sometimes', 'string', 'max:1000'],
        ]);

        $farm = FarmRecord::create(array_merge($validated, [
            'user_id' => $request->user()->id,
        ]));

        return response()->json(['data' => $farm, 'message' => 'Farm record created.'], 201);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $farm = FarmRecord::where('user_id', $request->user()->id)->findOrFail($id);
        return response()->json(['data' => $farm]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $farm = FarmRecord::where('user_id', $request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'crop_type'      => ['sometimes', 'string', 'max:100'],
            'plot_size'      => ['sometimes', 'numeric', 'min:0'],
            'planting_date'  => ['sometimes', 'date'],
            'harvest_date'   => ['sometimes', 'date'],
            'yield_kg'       => ['sometimes', 'numeric', 'min:0'],
            'growth_stage'   => ['sometimes', 'string', 'max:100'],
            'inputs_used'    => ['sometimes', 'string', 'max:1000'],
            'notes'          => ['sometimes', 'string', 'max:1000'],
        ]);

        $farm->update($validated);

        return response()->json(['data' => $farm, 'message' => 'Farm record updated.']);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $farm = FarmRecord::where('user_id', $request->user()->id)->findOrFail($id);
        $farm->delete();

        return response()->json(['message' => 'Farm record deleted.']);
    }
}
