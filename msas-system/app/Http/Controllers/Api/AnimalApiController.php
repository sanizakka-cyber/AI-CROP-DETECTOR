<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Animal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnimalApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $animals = Animal::where('user_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json(['data' => $animals]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tag_number'    => ['required', 'string', 'max:100'],
            'species'       => ['required', 'string', 'max:100'],
            'breed'         => ['sometimes', 'string', 'max:100'],
            'gender'        => ['sometimes', 'string', 'in:Male,Female,Unknown'],
            'age_months'    => ['sometimes', 'integer', 'min:0'],
            'weight_kg'     => ['sometimes', 'numeric', 'min:0'],
            'health_status' => ['sometimes', 'string', 'in:healthy,sick,under_treatment,deceased'],
            'notes'         => ['sometimes', 'string', 'max:1000'],
        ]);

        $animal = Animal::create(array_merge($validated, [
            'user_id' => $request->user()->id,
        ]));

        return response()->json(['data' => $animal, 'message' => 'Animal record created.'], 201);
    }

    public function show(Request $request, $id): JsonResponse
    {
        $animal = Animal::where('user_id', $request->user()->id)->findOrFail($id);
        return response()->json(['data' => $animal]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $animal = Animal::where('user_id', $request->user()->id)->findOrFail($id);

        $validated = $request->validate([
            'tag_number'    => ['sometimes', 'string', 'max:100'],
            'species'       => ['sometimes', 'string', 'max:100'],
            'breed'         => ['sometimes', 'string', 'max:100'],
            'gender'        => ['sometimes', 'string', 'in:Male,Female,Unknown'],
            'age_months'    => ['sometimes', 'integer', 'min:0'],
            'weight_kg'     => ['sometimes', 'numeric', 'min:0'],
            'health_status' => ['sometimes', 'string', 'in:healthy,sick,under_treatment,deceased'],
            'notes'         => ['sometimes', 'string', 'max:1000'],
        ]);

        $animal->update($validated);

        return response()->json(['data' => $animal, 'message' => 'Animal record updated.']);
    }

    public function destroy(Request $request, $id): JsonResponse
    {
        $animal = Animal::where('user_id', $request->user()->id)->findOrFail($id);
        $animal->delete();

        return response()->json(['message' => 'Animal record deleted.']);
    }
}
