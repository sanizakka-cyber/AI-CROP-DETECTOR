<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Diagnosis;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DiagnoseApiController extends Controller
{
    private function aiBase(): string
    {
        return rtrim(config('services.ai_engine.url', 'http://127.0.0.1:8001'), '/');
    }

    private function aiHttp(): \Illuminate\Http\Client\PendingRequest
    {
        $http = Http::timeout(30);
        $key  = config('services.ai_engine.key');
        if ($key) {
            $http = $http->withToken($key);
        }
        return $http;
    }

    public function crop(Request $request): JsonResponse
    {
        $request->validate([
            'cropType' => ['required', 'string'],
            'cropPart' => ['sometimes', 'string'],
            'images'   => ['required', 'array', 'min:1'],
            'images.*' => ['file', 'image', 'max:10240'],
        ]);

        try {
            $pending = $this->aiHttp()
                ->attach('cropType', $request->cropType)
                ->attach('cropPart', $request->cropPart ?? 'crop');

            foreach ($request->file('images', []) as $i => $file) {
                $pending = $pending->attach('images', file_get_contents($file->getRealPath()), "img_{$i}.jpg");
            }

            $response = $pending->post($this->aiBase() . '/predict/crop');
        } catch (\Exception $e) {
            return response()->json(['message' => 'AI engine unavailable. Please try again later.'], 503);
        }

        if (! $response->successful()) {
            return response()->json($response->json(), $response->status());
        }

        $payload = array_merge($response->json(), [
            'type'     => 'crop',
            'cropType' => $request->cropType,
            'cropPart' => $request->cropPart ?? 'crop',
            'userId'   => $request->user()->id,
        ]);

        $imagePath = null;
        if ($request->hasFile('images')) {
            $imagePath = $request->file('images')[0]->store('diagnoses', 'public');
        }

        $diagnosis = Diagnosis::create([
            'user_id'               => $request->user()->id,
            'type'                  => 'plant',
            'image_path'            => $imagePath,
            'disease_name'          => $payload['disease'] ?? $payload['prediction'] ?? 'Unknown',
            'confidence_score'      => $payload['confidence'] ?? 0,
            'cause'                 => $payload['cause'] ?? null,
            'urgency_level'         => $payload['urgency'] ?? 'Low',
            'first_aid_steps'       => $payload['first_aid'] ?? null,
            'recommended_medication'=> $payload['medication'] ?? null,
            'vet_referral_advice'   => $payload['referral'] ?? null,
            'status'                => 'pending',
        ]);

        $diagnosisId = $diagnosis->id;

        cache()->put("diagnosis:{$diagnosisId}", array_merge($payload, [
            'diagnosisId' => $diagnosisId,
            'createdAt'   => $diagnosis->created_at->toISOString(),
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
            $pending = $this->aiHttp()
                ->attach('animalType', $request->animalType)
                ->attach('assessmentType', $request->assessmentType);

            $files = $request->file('images', []);

            if (count($files) > 0) {
                foreach ($files as $i => $file) {
                    $pending = $pending->attach('images', file_get_contents($file->getRealPath()), "img_{$i}.jpg");
                }
            } else {
                $placeholder = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
                $pending = $pending->attach('images', $placeholder, 'placeholder.png');
            }

            $response = $pending->post($this->aiBase() . '/predict/livestock');
        } catch (\Exception $e) {
            return response()->json(['message' => 'AI engine unavailable. Please try again later.'], 503);
        }

        if (! $response->successful()) {
            return response()->json($response->json(), $response->status());
        }

        $payload = array_merge($response->json(), [
            'type'           => 'livestock',
            'animalType'     => $request->animalType,
            'assessmentType' => $request->assessmentType,
            'symptoms'       => $request->symptoms ? json_decode($request->symptoms, true) : [],
            'userId'         => $request->user()->id,
        ]);

        $imagePath = null;
        if ($request->hasFile('images')) {
            $imagePath = $request->file('images')[0]->store('diagnoses', 'public');
        }

        $diagnosis = Diagnosis::create([
            'user_id'               => $request->user()->id,
            'type'                  => 'animal',
            'image_path'            => $imagePath,
            'disease_name'          => $payload['disease'] ?? $payload['prediction'] ?? 'Unknown',
            'confidence_score'      => $payload['confidence'] ?? 0,
            'cause'                 => $payload['cause'] ?? null,
            'urgency_level'         => $payload['urgency'] ?? 'Low',
            'first_aid_steps'       => $payload['first_aid'] ?? null,
            'recommended_medication'=> $payload['medication'] ?? null,
            'vet_referral_advice'   => $payload['referral'] ?? null,
            'status'                => 'pending',
        ]);

        $diagnosisId = $diagnosis->id;

        cache()->put("diagnosis:{$diagnosisId}", array_merge($payload, [
            'diagnosisId' => $diagnosisId,
            'createdAt'   => $diagnosis->created_at->toISOString(),
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
        $diagnoses = Diagnosis::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        return response()->json([
            'data'  => $diagnoses->map(fn ($d) => [
                'id'                    => $d->id,
                'type'                  => $d->type,
                'disease_name'          => $d->disease_name,
                'confidence_score'      => $d->confidence_score,
                'urgency_level'         => $d->urgency_level,
                'cause'                 => $d->cause,
                'first_aid_steps'       => $d->first_aid_steps,
                'recommended_medication'=> $d->recommended_medication,
                'vet_referral_advice'   => $d->vet_referral_advice,
                'status'                => $d->status,
                'image_path'            => $d->image_path ? asset('storage/' . $d->image_path) : null,
                'created_at'            => $d->created_at->toISOString(),
            ]),
            'total' => $diagnoses->total(),
            'per_page' => $diagnoses->perPage(),
            'current_page' => $diagnoses->currentPage(),
            'last_page' => $diagnoses->lastPage(),
        ]);
    }

    public function feedback(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'outcome'  => ['required', 'in:accurate,inaccurate,partially_accurate'],
            'comments' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $diagnosis = Diagnosis::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $diagnosis) {
            return response()->json(['message' => 'Diagnosis not found.'], 404);
        }

        $diagnosis->update([
            'status' => $request->outcome === 'accurate' ? 'confirmed' : 'reviewed',
        ]);

        return response()->json(['message' => 'Feedback recorded. Thank you.']);
    }
}
