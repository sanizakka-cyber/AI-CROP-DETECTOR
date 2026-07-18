<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Diagnosis;
use App\Models\MobileNotification;
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

        $cachePayload = array_merge($payload, [
            'diagnosisId' => $diagnosisId,
            'status'      => 'processed',
            'createdAt'   => $diagnosis->created_at->toISOString(),
            'image_path'  => $imagePath ? asset('storage/' . $imagePath) : null,
            'treatmentPlan' => [
                'immediateActions' => $payload['first_aid']
                    ? [['action' => $payload['first_aid']]]
                    : [],
                'chemicalTreatments' => $payload['medication']
                    ? [['product' => $payload['medication']]]
                    : [],
            ],
            'aiResult' => [
                'primaryDiagnosis' => $payload['disease'] ?? 'Unknown',
                'confidence'       => $payload['confidence'] ?? 0,
                'severity'         => strtolower($payload['urgency'] ?? 'low'),
                'likelyCauses'     => $payload['cause'] ? [$payload['cause']] : [],
            ],
        ]);

        cache()->put("diagnosis:{$diagnosisId}", $cachePayload, now()->addHours(24));

        // Push notification — scan complete
        $disease = $payload['disease'] ?? 'Scan complete';
        MobileNotification::send(
            $request->user()->id,
            '🔬 Crop Scan Result Ready',
            "Diagnosis: {$disease} · Tap to view treatment plan",
            'scan',
            ['diagnosis_id' => $diagnosisId]
        );

        $diagnosis->update(['status' => 'confirmed']);

        return response()->json(['diagnosisId' => $diagnosisId]);
    }

    public function livestock(Request $request): JsonResponse
    {
        $request->validate([
            'animalType'     => ['required', 'string'],
            'assessmentType' => ['required', 'string'],
            'images'         => ['sometimes', 'array', 'max:5'],
            'images.*'       => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
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

        $cachePayload = array_merge($payload, [
            'diagnosisId' => $diagnosisId,
            'status'      => 'processed',
            'createdAt'   => $diagnosis->created_at->toISOString(),
            'image_path'  => $imagePath ? asset('storage/' . $imagePath) : null,
            'treatmentPlan' => [
                'immediateActions' => $payload['first_aid']
                    ? [['action' => $payload['first_aid']]]
                    : [],
                'chemicalTreatments' => $payload['medication']
                    ? [['product' => $payload['medication']]]
                    : [],
            ],
            'aiResult' => [
                'primaryDiagnosis' => $payload['disease'] ?? 'Unknown',
                'confidence'       => $payload['confidence'] ?? 0,
                'severity'         => strtolower($payload['urgency'] ?? 'low'),
                'likelyCauses'     => $payload['cause'] ? [$payload['cause']] : [],
            ],
        ]);

        cache()->put("diagnosis:{$diagnosisId}", $cachePayload, now()->addHours(24));

        // Push notification — scan complete
        $disease = $payload['disease'] ?? 'Scan complete';
        MobileNotification::send(
            $request->user()->id,
            '🐄 Livestock Scan Result Ready',
            "Diagnosis: {$disease} · Tap to view treatment plan",
            'scan',
            ['diagnosis_id' => $diagnosisId]
        );

        $diagnosis->update(['status' => 'confirmed']);

        return response()->json(['diagnosisId' => $diagnosisId]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        // 1. Try hot cache first (populated immediately after scan)
        $result = cache()->get("diagnosis:{$id}");

        if ($result) {
            if ((int) $result['userId'] !== $request->user()->id) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
            return response()->json(['diagnosis' => $result]);
        }

        // 2. Fall back to DB (cache TTL 24h; older scans live in DB)
        $d = Diagnosis::where('id', $id)->where('user_id', $request->user()->id)->first();
        if (! $d) {
            return response()->json(['message' => 'Diagnosis not found.'], 404);
        }

        $diagnosis = [
            'diagnosisId'    => $d->id,
            'userId'         => $d->user_id,
            'type'           => $d->type === 'plant' ? 'crop' : 'livestock',
            'status'         => $d->status === 'confirmed' ? 'processed' : $d->status,
            'createdAt'      => $d->created_at->toISOString(),
            'image_path'     => $d->image_path ? asset('storage/' . $d->image_path) : null,
            'aiResult' => [
                'primaryDiagnosis' => $d->disease_name,
                'confidence'       => $d->confidence_score,
                'severity'         => $d->urgency_level ? strtolower($d->urgency_level) : 'low',
                'likelyCauses'     => $d->cause ? [$d->cause] : [],
            ],
            'treatmentPlan' => [
                'immediateActions' => $d->first_aid_steps
                    ? [['action' => $d->first_aid_steps]]
                    : [],
                'chemicalTreatments' => $d->recommended_medication
                    ? [['product' => $d->recommended_medication]]
                    : [],
            ],
        ];

        return response()->json(['diagnosis' => $diagnosis]);
    }

    public function history(Request $request): JsonResponse
    {
        $diagnoses = Diagnosis::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        $shaped = $diagnoses->map(fn ($d) => [
            'id'                    => $d->id,
            'type'                  => $d->type === 'plant' ? 'crop' : 'livestock',
            'disease_name'          => $d->disease_name,
            'confidence_score'      => $d->confidence_score,
            'urgency_level'         => $d->urgency_level,
            'cause'                 => $d->cause,
            'first_aid_steps'       => $d->first_aid_steps,
            'recommended_medication'=> $d->recommended_medication,
            'vet_referral_advice'   => $d->vet_referral_advice,
            'status'                => $d->status === 'confirmed' ? 'processed' : $d->status,
            'image_path'            => $d->image_path ? asset('storage/' . $d->image_path) : null,
            'created_at'            => $d->created_at->toISOString(),
        ]);

        return response()->json([
            'data'         => $shaped,
            'diagnoses'    => $shaped, // alias for mobile compatibility
            'total'        => $diagnoses->total(),
            'per_page'     => $diagnoses->perPage(),
            'current_page' => $diagnoses->currentPage(),
            'last_page'    => $diagnoses->lastPage(),
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
