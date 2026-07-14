<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class DiagnoseApiController extends Controller
{
    private const AI_ENGINE = 'http://127.0.0.1:8001';

    public function crop(Request $request): JsonResponse
    {
        $request->validate([
            'cropType' => ['required', 'string'],
            'cropPart' => ['sometimes', 'string'],
            'images'   => ['required', 'array', 'min:1'],
            'images.*' => ['file', 'image', 'max:10240'],
        ]);

        try {
            $pending = Http::timeout(30)
                ->attach('cropType', $request->cropType)
                ->attach('cropPart', $request->cropPart ?? 'crop');

            foreach ($request->file('images', []) as $i => $file) {
                $pending = $pending->attach('images', file_get_contents($file->getRealPath()), "img_{$i}.jpg");
            }

            $response = $pending->post(self::AI_ENGINE . '/predict/crop');
        } catch (\Exception $e) {
            return response()->json(['message' => 'AI engine unavailable. Please try again later.'], 503);
        }

        if (! $response->successful()) {
            return response()->json($response->json(), $response->status());
        }

        $diagnosisId = (string) Str::uuid();

        cache()->put("diagnosis:{$diagnosisId}", array_merge($response->json(), [
            'diagnosisId' => $diagnosisId,
            'type'        => 'crop',
            'cropType'    => $request->cropType,
            'cropPart'    => $request->cropPart ?? 'crop',
            'userId'      => $request->user()->id,
            'createdAt'   => now()->toISOString(),
        ]), now()->addHours(24));

        return response()->json(['diagnosisId' => $diagnosisId]);
    }

    public function livestock(Request $request): JsonResponse
    {
        $request->validate([
            'animalType'     => ['required', 'string'],
            'assessmentType' => ['required', 'string'],
        ]);

        try {
            $pending = Http::timeout(30)
                ->attach('animalType', $request->animalType)
                ->attach('assessmentType', $request->assessmentType);

            $files = $request->file('images', []);

            if (count($files) > 0) {
                foreach ($files as $i => $file) {
                    $pending = $pending->attach('images', file_get_contents($file->getRealPath()), "img_{$i}.jpg");
                }
            } else {
                // Behavioral-only: send a 1×1 transparent PNG placeholder
                $placeholder = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
                $pending = $pending->attach('images', $placeholder, 'placeholder.png');
            }

            $response = $pending->post(self::AI_ENGINE . '/predict/livestock');
        } catch (\Exception $e) {
            return response()->json(['message' => 'AI engine unavailable. Please try again later.'], 503);
        }

        if (! $response->successful()) {
            return response()->json($response->json(), $response->status());
        }

        $diagnosisId = (string) Str::uuid();

        cache()->put("diagnosis:{$diagnosisId}", array_merge($response->json(), [
            'diagnosisId'    => $diagnosisId,
            'type'           => 'livestock',
            'animalType'     => $request->animalType,
            'assessmentType' => $request->assessmentType,
            'symptoms'       => $request->symptoms ? json_decode($request->symptoms, true) : [],
            'userId'         => $request->user()->id,
            'createdAt'      => now()->toISOString(),
        ]), now()->addHours(24));

        return response()->json(['diagnosisId' => $diagnosisId]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $result = cache()->get("diagnosis:{$id}");

        if (! $result) {
            return response()->json(['message' => 'Diagnosis not found or expired.'], 404);
        }

        if ($result['userId'] !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json($result);
    }

    public function history(Request $request): JsonResponse
    {
        return response()->json(['data' => [], 'total' => 0]);
    }

    public function feedback(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Feedback recorded.']);
    }
}
