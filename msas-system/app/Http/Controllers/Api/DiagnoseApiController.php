<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Diagnosis;
use App\Models\MobileNotification;
use App\Services\DiagnosisResultMapper;
use App\Services\SubscriptionLimitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DiagnoseApiController extends Controller
{
    private function aiBase(): string
    {
        return rtrim(config('services.ai_engine.url', 'http://127.0.0.1:8001'), '/');
    }

    /**
     * 180s, matching the web scan flow (DiagnosticController) — the mobile
     * endpoint previously used a 30s timeout, which meant a Render.com
     * cold-start on the AI engine (routine on a free/low tier, and the
     * exact scenario the web flow's pre-warm ping below exists for) failed
     * on mobile in cases where the identical scan succeeded on web.
     */
    private function aiHttp(): \Illuminate\Http\Client\PendingRequest
    {
        $http = Http::timeout(180)->connectTimeout(90);
        $key  = config('services.ai_engine.key');
        if ($key) {
            $http = $http->withToken($key);
        }
        return $http;
    }

    /** Best-effort — lets a cold Render instance start waking up before the heavy prediction call. Mirrors DiagnosticController::analyze(). */
    private function warmAiEngine(): void
    {
        try {
            Http::timeout(5)->withToken(config('services.ai_engine.key'))->get($this->aiBase() . '/health');
        } catch (\Throwable) {
            // Non-fatal — proceed even if the warm-up ping fails.
        }
    }

    /** Base64 thumbnail so the image survives Render's ephemeral storage being wiped on redeploy/restart — same reasoning as DiagnosticController::analyze(). */
    private function makeThumbnail(string $fullPath): ?string
    {
        if (! file_exists($fullPath) || ! function_exists('imagecreatefromstring')) {
            return null;
        }
        try {
            $srcImage = imagecreatefromstring(file_get_contents($fullPath));
            if ($srcImage === false) {
                return null;
            }
            $srcW = imagesx($srcImage);
            $srcH = imagesy($srcImage);
            $maxDim = 400;
            $ratio = min($maxDim / $srcW, $maxDim / $srcH, 1.0);
            $dstW = max(1, (int) round($srcW * $ratio));
            $dstH = max(1, (int) round($srcH * $ratio));
            $dst = imagecreatetruecolor($dstW, $dstH);
            imagecopyresampled($dst, $srcImage, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
            ob_start();
            imagejpeg($dst, null, 83);
            $jpegBytes = ob_get_clean();
            imagedestroy($srcImage);
            imagedestroy($dst);
            return 'data:image/jpeg;base64,' . base64_encode($jpegBytes);
        } catch (\Throwable $e) {
            Log::warning('Mobile scan thumbnail generation failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function crop(Request $request): JsonResponse
    {
        $scanCheck = app(SubscriptionLimitService::class)->canScan($request->user());
        if (! $scanCheck['allowed']) {
            return response()->json([
                'error' => "You have used all {$scanCheck['limit']} AI scans for this month. Upgrade your plan to continue.",
                'scan_limit' => $scanCheck,
            ], 403);
        }

        // cropType/cropPart are optional hints, not requirements — matches
        // the web scan form exactly (DiagnosticController::analyze() only
        // sends them when non-empty via array_filter). The AI engine is
        // fully capable of identifying the crop and plant part on its own;
        // forcing a selection here was a mobile-only restriction the web
        // flow never had.
        $request->validate([
            'cropType' => ['sometimes', 'nullable', 'string'],
            'cropPart' => ['sometimes', 'nullable', 'string'],
            'images'   => ['required', 'array', 'min:1'],
            'images.*' => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
        ]);

        $this->warmAiEngine();

        try {
            $pending = $this->aiHttp();
            if ($request->filled('cropType')) $pending = $pending->attach('cropType', $request->cropType);
            if ($request->filled('cropPart')) $pending = $pending->attach('cropPart', $request->cropPart);

            foreach ($request->file('images', []) as $i => $file) {
                $pending = $pending->attach('images', file_get_contents($file->getRealPath()), "img_{$i}.jpg");
            }

            $response = $pending->post($this->aiBase() . '/predict/crop');
        } catch (\Exception $e) {
            Log::error('Mobile crop scan: AI engine exception', ['error' => $e->getMessage()]);
            return $this->storeUnavailable($request, 'plant', null, '🔬 Crop Scan — Expert Review Needed');
        }

        if (! $response->successful()) {
            Log::warning('Mobile crop scan: AI engine non-2xx', ['status' => $response->status()]);
            return $this->storeUnavailable($request, 'plant', null, '🔬 Crop Scan — Expert Review Needed');
        }

        $aiResult = $response->json();

        $imagePath = null;
        $fullPath  = null;
        if ($request->hasFile('images')) {
            $file      = $request->file('images')[0];
            $imagePath = $file->store('diagnoses', 'public');
            $fullPath  = Storage::disk('public')->path($imagePath);
        }

        return $this->finishScan($request, 'plant', $aiResult, $imagePath, $fullPath, '🔬 Crop Scan Result Ready');
    }

    public function livestock(Request $request): JsonResponse
    {
        $scanCheck = app(SubscriptionLimitService::class)->canScan($request->user());
        if (! $scanCheck['allowed']) {
            return response()->json([
                'error' => "You have used all {$scanCheck['limit']} AI scans for this month. Upgrade your plan to continue.",
                'scan_limit' => $scanCheck,
            ], 403);
        }

        $request->validate([
            'animalType'     => ['required', 'string'],
            'assessmentType' => ['required', 'string'],
            'images'         => ['sometimes', 'array', 'max:5'],
            'images.*'       => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ]);

        $this->warmAiEngine();

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
            Log::error('Mobile livestock scan: AI engine exception', ['error' => $e->getMessage()]);
            return $this->storeUnavailable($request, 'animal', null, '🐄 Livestock Scan — Expert Review Needed');
        }

        if (! $response->successful()) {
            Log::warning('Mobile livestock scan: AI engine non-2xx', ['status' => $response->status()]);
            return $this->storeUnavailable($request, 'animal', null, '🐄 Livestock Scan — Expert Review Needed');
        }

        $aiResult = $response->json();

        $imagePath = null;
        $fullPath  = null;
        if ($request->hasFile('images')) {
            $file      = $request->file('images')[0];
            $imagePath = $file->store('diagnoses', 'public');
            $fullPath  = Storage::disk('public')->path($imagePath);
        }

        return $this->finishScan($request, 'animal', $aiResult, $imagePath, $fullPath, '🐄 Livestock Scan Result Ready');
    }

    public function soil(Request $request): JsonResponse
    {
        $scanCheck = app(SubscriptionLimitService::class)->canScan($request->user());
        if (! $scanCheck['allowed']) {
            return response()->json([
                'error' => "You have used all {$scanCheck['limit']} AI scans for this month. Upgrade your plan to continue.",
                'scan_limit' => $scanCheck,
            ], 403);
        }

        $request->validate([
            'soilContext' => ['sometimes', 'nullable', 'string', 'max:300'],
            'images'      => ['required', 'array', 'min:1'],
            'images.*'    => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
        ]);

        $this->warmAiEngine();

        try {
            $pending = $this->aiHttp();
            if ($request->filled('soilContext')) $pending = $pending->attach('soilContext', $request->soilContext);

            foreach ($request->file('images', []) as $i => $file) {
                $pending = $pending->attach('images', file_get_contents($file->getRealPath()), "img_{$i}.jpg");
            }

            $response = $pending->post($this->aiBase() . '/predict/soil');
        } catch (\Exception $e) {
            Log::error('Mobile soil scan: AI engine exception', ['error' => $e->getMessage()]);
            return $this->storeUnavailable($request, 'soil', null, '🧪 Soil Scan — Expert Review Needed');
        }

        if (! $response->successful()) {
            Log::warning('Mobile soil scan: AI engine non-2xx', ['status' => $response->status()]);
            return $this->storeUnavailable($request, 'soil', null, '🧪 Soil Scan — Expert Review Needed');
        }

        $aiResult = $response->json();

        $imagePath = null;
        $fullPath  = null;
        if ($request->hasFile('images')) {
            $file      = $request->file('images')[0];
            $imagePath = $file->store('diagnoses', 'public');
            $fullPath  = Storage::disk('public')->path($imagePath);
        }

        return $this->finishScan($request, 'soil', $aiResult, $imagePath, $fullPath, '🧪 Soil Scan Result Ready');
    }

    public function pest(Request $request): JsonResponse
    {
        $scanCheck = app(SubscriptionLimitService::class)->canScan($request->user());
        if (! $scanCheck['allowed']) {
            return response()->json([
                'error' => "You have used all {$scanCheck['limit']} AI scans for this month. Upgrade your plan to continue.",
                'scan_limit' => $scanCheck,
            ], 403);
        }

        $request->validate([
            'cropType'    => ['sometimes', 'nullable', 'string'],
            'location'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'images'      => ['required', 'array', 'min:1'],
            'images.*'    => ['file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
        ]);

        $this->warmAiEngine();

        try {
            $pending = $this->aiHttp();
            if ($request->filled('cropType')) $pending = $pending->attach('cropType', $request->cropType);
            if ($request->filled('location')) $pending = $pending->attach('location', $request->location);

            foreach ($request->file('images', []) as $i => $file) {
                $pending = $pending->attach('images', file_get_contents($file->getRealPath()), "img_{$i}.jpg");
            }

            $response = $pending->post($this->aiBase() . '/predict/pest');
        } catch (\Exception $e) {
            Log::error('Mobile pest scan: AI engine exception', ['error' => $e->getMessage()]);
            return $this->storeUnavailable($request, 'pest', null, '🔍 Pest ID — Expert Review Needed');
        }

        if (! $response->successful()) {
            Log::warning('Mobile pest scan: AI engine non-2xx', ['status' => $response->status()]);
            return $this->storeUnavailable($request, 'pest', null, '🔍 Pest ID — Expert Review Needed');
        }

        $aiResult = $response->json();

        $imagePath = null;
        $fullPath  = null;
        if ($request->hasFile('images')) {
            $file      = $request->file('images')[0];
            $imagePath = $file->store('diagnoses', 'public');
            $fullPath  = Storage::disk('public')->path($imagePath);
        }

        return $this->finishScan($request, 'pest', $aiResult, $imagePath, $fullPath, '🔍 Pest ID Result Ready');
    }

    /**
     * Shared by crop()/livestock()/soil()/pest(): builds the full-field Diagnosis row via
     * DiagnosisResultMapper (same mapper the web scan flow uses), caches an
     * immediately-renderable payload, and notifies the user. Replaces the
     * narrow 6-field version each endpoint previously built independently.
     */
    private function finishScan(Request $request, string $type, array $aiResult, ?string $imagePath, ?string $fullPath, string $notifTitle): JsonResponse
    {
        $data = DiagnosisResultMapper::fromAiResult($aiResult);
        $thumbnail = $fullPath ? $this->makeThumbnail($fullPath) : null;

        $diagnosis = Diagnosis::create(array_merge($data, [
            'user_id'         => $request->user()->id,
            'scan_ref'        => Diagnosis::generateScanRef(),
            'type'            => $type,
            'image_path'      => $imagePath,
            'image_thumbnail' => $thumbnail,
            'language'        => app()->getLocale(),
        ]));

        \App\Models\SubscriptionUsage::track($request->user()->id, 'ai_scans_per_month');

        $this->cacheScanPayload($diagnosis, $imagePath);

        MobileNotification::send(
            $request->user()->id,
            $notifTitle,
            "Diagnosis: {$diagnosis->disease_name} · Tap to view treatment plan",
            'scan',
            ['diagnosis_id' => $diagnosis->id]
        );

        return response()->json(['diagnosisId' => $diagnosis->id, 'scan_ref' => $diagnosis->scan_ref]);
    }

    /** The AI genuinely didn't respond — still creates a real, honestly-labeled record instead of silently failing the request with no trace of the attempted scan. */
    private function storeUnavailable(Request $request, string $type, ?string $imagePath, string $notifTitle): JsonResponse
    {
        $diagnosis = Diagnosis::create(array_merge(DiagnosisResultMapper::aiUnavailableFallback(), [
            'user_id'    => $request->user()->id,
            'scan_ref'   => Diagnosis::generateScanRef(),
            'type'       => $type,
            'image_path' => $imagePath,
            'language'   => app()->getLocale(),
        ]));

        $this->cacheScanPayload($diagnosis, $imagePath);

        MobileNotification::send(
            $request->user()->id,
            $notifTitle,
            'Our AI engine was temporarily unavailable — an expert will review your scan shortly.',
            'scan',
            ['diagnosis_id' => $diagnosis->id]
        );

        return response()->json(['diagnosisId' => $diagnosis->id, 'scan_ref' => $diagnosis->scan_ref], 202);
    }

    /** One place building the cache payload mobile's show() serves immediately after a scan — kept in sync with the full field set both success and fallback paths now populate. */
    private function cacheScanPayload(Diagnosis $diagnosis, ?string $imagePath): void
    {
        cache()->put("diagnosis:{$diagnosis->id}", $this->shapeDiagnosis($diagnosis, $imagePath), now()->addHours(24));
    }

    /** Shared response shape for show()/history()/the post-scan cache — one mapping from a Diagnosis row to the JSON mobile actually renders. */
    private function shapeDiagnosis(Diagnosis $d, ?string $imagePathForUrl = null): array
    {
        $imageUrl = $d->image_thumbnail
            ?? ($imagePathForUrl ? asset('storage/' . $imagePathForUrl) : ($d->image_path ? asset('storage/' . $d->image_path) : null));

        $myFeedback = \App\Models\DiagnosisFeedback::where('diagnosis_id', $d->id)
            ->where('user_id', $d->user_id)
            ->first();

        return [
            'diagnosisId'      => $d->id,
            'userId'           => $d->user_id,
            'scanRef'          => $d->scan_ref,
            'type'             => match ($d->type) {
                'plant'  => 'crop',
                'animal' => 'livestock',
                default  => $d->type, // 'soil' | 'pest' — passed through as-is, no legacy rename needed
            },
            'status'           => $d->status === 'confirmed' ? 'processed' : $d->status,
            'statusLabel'      => $d->statusLabel,
            'createdAt'        => $d->created_at->toISOString(),
            'image_path'       => $imageUrl,
            'subjectName'      => $d->subject_name,
            'scientificName'   => $d->scientific_name,
            'detectedPart'     => $d->detected_part,
            'aiResult' => [
                'primaryDiagnosis'    => $d->disease_name,
                'confidence'          => $d->confidence_score,
                'confidenceLabel'     => $d->confidenceLabel,
                'severity'            => $d->urgency_level ? strtolower($d->urgency_level) : null,
                'severityLevel'       => $d->severity_level,
                'healthStatus'        => $d->health_status,
                'likelyCauses'        => $d->cause ? [$d->cause] : [],
                'symptomsIdentified'  => $d->symptoms_identified,
                'environmentalFactors'=> $d->environmental_factors,
                'nutrientDeficiencies'=> $d->nutrient_deficiencies,
                'pestDetection'       => $d->pest_detection,
                'explanation'         => $d->explanation,
            ],
            'treatmentPlan' => [
                'immediateActions' => $d->first_aid_steps ? [['action' => $d->first_aid_steps]] : [],
                'chemicalTreatments' => $d->recommended_medication ? [['product' => $d->recommended_medication]] : [],
                'preventiveMeasures' => $d->preventive_measures,
                'fertilizerRecommendation' => $d->fertilizer_recommendation,
                'recoveryPeriod' => $d->recovery_period,
                'bestPractices' => $d->best_practices,
                'vetReferralAdvice' => $d->vet_referral_advice,
            ],
            'myFeedback' => $myFeedback ? ['rating' => $myFeedback->rating] : null,
        ];
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

        return response()->json(['diagnosis' => $this->shapeDiagnosis($d)]);
    }

    public function history(Request $request): JsonResponse
    {
        $diagnoses = Diagnosis::where('user_id', $request->user()->id)
            ->latest()
            ->paginate(20);

        $shaped = $diagnoses->map(fn ($d) => [
            'id'                    => $d->id,
            'scan_ref'              => $d->scan_ref,
            'type'                  => match ($d->type) {
                'plant'  => 'crop',
                'animal' => 'livestock',
                default  => $d->type,
            },
            'subject_name'          => $d->subject_name,
            'disease_name'          => $d->disease_name,
            'confidence_score'      => $d->confidence_score,
            'confidence_label'      => $d->confidenceLabel,
            'urgency_level'         => $d->urgency_level,
            'severity_level'        => $d->severity_level,
            'cause'                 => $d->cause,
            'first_aid_steps'       => $d->first_aid_steps,
            'recommended_medication'=> $d->recommended_medication,
            'vet_referral_advice'   => $d->vet_referral_advice,
            'status'                => $d->status === 'confirmed' ? 'processed' : $d->status,
            'status_label'          => $d->statusLabel,
            'image_path'            => $d->image_thumbnail ?? ($d->image_path ? asset('storage/' . $d->image_path) : null),
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

    /**
     * Matches the web feedback contract exactly (DiagnosticController::
     * feedback() / 'rating' => thumbs_up|thumbs_down, App\Models\
     * DiagnosisFeedback) — the mobile endpoint previously validated a
     * different field ('outcome' => accurate|inaccurate|partially_accurate)
     * and only mutated Diagnosis.status, never writing an actual
     * DiagnosisFeedback row at all. That meant "Was this helpful?" had no
     * persisted effect compatible with the rest of the system, and nothing
     * to look up to prevent a duplicate submission (updateOrCreate here
     * upserts on [diagnosis_id, user_id], same as web).
     */
    public function feedback(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'rating'          => ['required', 'in:thumbs_up,thumbs_down'],
            'correct_disease' => ['sometimes', 'nullable', 'string', 'max:200'],
            'notes'           => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $diagnosis = Diagnosis::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $diagnosis) {
            return response()->json(['message' => 'Diagnosis not found.'], 404);
        }

        \App\Models\DiagnosisFeedback::updateOrCreate(
            ['diagnosis_id' => $diagnosis->id, 'user_id' => $request->user()->id],
            [
                'rating'          => $request->rating,
                'correct_disease' => $request->correct_disease,
                'notes'           => $request->notes,
            ]
        );

        return response()->json(['message' => 'Thank you for your feedback — it helps improve our AI.']);
    }
}
