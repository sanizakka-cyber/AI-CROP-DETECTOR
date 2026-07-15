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
        $validated = $request->validate([
            'farm_name'  => ['required', 'string', 'max:255'],
            'farm_size'  => ['sometimes', 'numeric', 'min:0'],
            'location'   => ['sometimes', 'string', 'max:255'],
            'crop_type'  => ['sometimes', 'string', 'max:100'],
            'soil_type'  => ['sometimes', 'string', 'max:100'],
            'notes'      => ['sometimes', 'string', 'max:1000'],
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
            'farm_name' => ['sometimes', 'string', 'max:255'],
            'farm_size' => ['sometimes', 'numeric', 'min:0'],
            'location'  => ['sometimes', 'string', 'max:255'],
            'crop_type' => ['sometimes', 'string', 'max:100'],
            'soil_type' => ['sometimes', 'string', 'max:100'],
            'notes'     => ['sometimes', 'string', 'max:1000'],
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
