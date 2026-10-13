<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CollectionLocation;
use App\Models\Diagnosis;
use App\Models\MobileNotification;
use App\Rules\NotEmptyFile;
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

    /**
     * The web scan form's blank fields arrive as real NULLs (Laravel's
     * ConvertEmptyStringsToNull middleware, applied to the 'web' group).
     * The API group does not carry that same guarantee for every client,
     * so a mobile client that always sends `loc_capture_method` /
     * `loc_state` etc. as an empty string rather than omitting the key
     * entirely would otherwise fail the 'nullable|in:...' / 'nullable|
     * string' rules below on a value that should be treated as "not
     * provided". Normalizing here keeps both clients' location contract
     * identical regardless of what empty-value convention either sends.
     */
    private function normalizeEmptyLocationStrings(Request $request): void
    {
        $locKeys = [
            'loc_country', 'loc_state', 'loc_lga', 'loc_ward', 'loc_community',
            'loc_postal_code', 'loc_address', 'loc_latitude', 'loc_longitude',
            'loc_accuracy_meters', 'loc_capture_method', 'loc_collected_at',
        ];

        $normalized = [];
        foreach ($locKeys as $key) {
            if ($request->has($key) && $request->input($key) === '') {
                $normalized[$key] = null;
            }
        }

        if ($normalized) {
            $request->merge($normalized);
        }
    }

    /**
     * Sample-collection-location validation rules (spec Section 5), shared
     * across crop/livestock/soil/pest — same field contract as the web
     * scan form (DiagnosticController::analyze()) so a scan submitted from
     * either client produces the same richness of location record.
     */
    private function locationRules(): array
    {
        return [
            'loc_country'           => ['sometimes', 'nullable', 'string', 'max:100'],
            'loc_state'              => ['sometimes', 'nullable', 'string', 'max:100'],
            'loc_lga'                => ['sometimes', 'nullable', 'string', 'max:100'],
            'loc_ward'               => ['sometimes', 'nullable', 'string', 'max:150'],
            'loc_community'          => ['sometimes', 'nullable', 'string', 'max:150'],
            'loc_postal_code'        => ['sometimes', 'nullable', 'string', 'max:20'],
            'loc_address'            => ['sometimes', 'nullable', 'string', 'max:500'],
            'loc_latitude'           => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'loc_longitude'          => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'loc_accuracy_meters'    => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'loc_capture_method'     => ['sometimes', 'nullable', 'in:gps_device,manual_coordinates,administrative_selection,address_entry'],
            'loc_user_confirmed'     => ['sometimes', 'nullable', 'boolean'],
            'loc_differs_from_scan'  => ['sometimes', 'nullable', 'boolean'],
            'loc_collected_at'       => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * Phase 11: one scan flow for crop/livestock/soil/pest (previously four
     * near-identical copies). Differences from before, all deliberate:
     *  - The photo is stored BEFORE the engine call, so a failed or rejected
     *    scan still keeps the farmer's image (it used to be discarded).
     *  - An engine 4xx (it read the request and rejected the image) is stored
     *    as a "retake photo" result, not as "AI engine unavailable".
     *  - The request locale is forwarded, as the web scan flow already did.
     *  - A repeated submission carrying the same client_scan_id returns the
     *    scan already created, so a retry after a network timeout cannot
     *    create (and count) a duplicate.
     */
    private function runScan(Request $request, string $type, string $endpoint, array $rules, array $textFields, array $notif): JsonResponse
    {
        $this->normalizeEmptyLocationStrings($request);
        $request->validate(array_merge($rules, [
            'client_scan_id' => ['sometimes', 'nullable', 'string', 'max:64'],
        ], $this->locationRules()));

        if ($request->filled('client_scan_id')) {
            $existing = Diagnosis::where('user_id', $request->user()->id)
                ->where('client_scan_id', $request->client_scan_id)
                ->first();
            if ($existing) {
                return response()->json(['diagnosisId' => $existing->id, 'scan_ref' => $existing->scan_ref, 'duplicate' => true]);
            }
        }

        $scanCheck = app(SubscriptionLimitService::class)->canScan($request->user());
        if (! $scanCheck['allowed']) {
            return response()->json([
                'error' => "You have used all {$scanCheck['limit']} AI scans for this month. Upgrade your plan to continue.",
                'scan_limit' => $scanCheck,
            ], 403);
        }

        $files     = $request->file('images', []);
        $imagePath = null;
        $fullPath  = null;
        if (count($files) > 0) {
            $imagePath = $files[0]->store('diagnoses', 'public');
            $fullPath  = Storage::disk('public')->path($imagePath);
        }

        $this->warmAiEngine();

        try {
            $pending = $this->aiHttp()->attach('language', app()->getLocale());
            foreach ($textFields as $field => $value) {
                if ($value !== null && $value !== '') {
                    $pending = $pending->attach($field, (string) $value);
                }
            }
            foreach ($files as $i => $file) {
                $pending = $pending->attach('images', file_get_contents($file->getRealPath()), "img_{$i}.jpg");
            }
            $response = $pending->post($this->aiBase() . $endpoint);
        } catch (\Exception $e) {
            Log::error("Mobile {$type} scan: AI engine exception", ['error' => $e->getMessage()]);
            return $this->storeUnavailable($request, $type, $imagePath, $fullPath, $notif['failed']);
        }

        if (! $response->successful()) {
            Log::warning("Mobile {$type} scan: AI engine non-2xx", ['status' => $response->status()]);
            $rejection = DiagnosisResultMapper::fromEngineRejection($response->status(), $response->json());
            if ($rejection !== null) {
                return $this->finishScan($request, $type, null, $imagePath, $fullPath, $notif['ready'], $rejection);
            }
            return $this->storeUnavailable($request, $type, $imagePath, $fullPath, $notif['failed']);
        }

        $aiResult = $response->json();
        if (! is_array($aiResult)) {
            return $this->storeUnavailable($request, $type, $imagePath, $fullPath, $notif['failed']);
        }

        return $this->finishScan($request, $type, $aiResult, $imagePath, $fullPath, $notif['ready']);
    }

    private function imageRules(bool $required = true): array
    {
        return [
            'images'   => $required ? ['required', 'array', 'min:1', 'max:5'] : ['sometimes', 'array', 'max:5'],
            'images.*' => ['file', 'image', 'mimes:jpeg,jpg,png,webp', new NotEmptyFile(), 'max:10240'],
        ];
    }

    public function crop(Request $request): JsonResponse
    {
        // cropType/cropPart are optional hints, matching the web scan form.
        return $this->runScan($request, 'plant', '/predict/crop', array_merge([
            'cropType' => ['sometimes', 'nullable', 'string'],
            'cropPart' => ['sometimes', 'nullable', 'string'],
        ], $this->imageRules()), [
            'cropType' => $request->input('cropType'),
            'cropPart' => $request->input('cropPart'),
        ], ['ready' => '🔬 Crop Scan Result Ready', 'failed' => '🔬 Crop Scan — Expert Review Needed']);
    }

    public function livestock(Request $request): JsonResponse
    {
        // No placeholder image any more: with no photo the engine returns an
        // honest "add a photo" result instead of being made to guess.
        return $this->runScan($request, 'animal', '/predict/livestock', array_merge([
            'animalType'     => ['required', 'string'],
            'assessmentType' => ['required', 'string'],
        ], $this->imageRules(false)), [
            'animalType'     => $request->input('animalType'),
            'assessmentType' => $request->input('assessmentType'),
            'symptoms'       => $request->input('symptoms'),
        ], ['ready' => '🐄 Livestock Scan Result Ready', 'failed' => '🐄 Livestock Scan — Expert Review Needed']);
    }

    public function soil(Request $request): JsonResponse
    {
        return $this->runScan($request, 'soil', '/predict/soil', array_merge([
            'soilContext' => ['sometimes', 'nullable', 'string', 'max:300'],
        ], $this->imageRules()), [
            'soilContext' => $request->input('soilContext'),
        ], ['ready' => '🧪 Soil Scan Result Ready', 'failed' => '🧪 Soil Scan — Expert Review Needed']);
    }

    public function pest(Request $request): JsonResponse
    {
        return $this->runScan($request, 'pest', '/predict/pest', array_merge([
            'cropType' => ['sometimes', 'nullable', 'string'],
            'location' => ['sometimes', 'nullable', 'string', 'max:100'],
        ], $this->imageRules()), [
            'cropType' => $request->input('cropType'),
            'location' => $request->input('location'),
        ], ['ready' => '🔍 Pest ID Result Ready', 'failed' => '🔍 Pest ID — Expert Review Needed']);
    }

    /**
     * Shared by crop()/livestock()/soil()/pest(): builds the full-field Diagnosis row via
     * DiagnosisResultMapper (same mapper the web scan flow uses), caches an
     * immediately-renderable payload, and notifies the user. Replaces the
     * narrow 6-field version each endpoint previously built independently.
     */
    private function finishScan(Request $request, string $type, ?array $aiResult, ?string $imagePath, ?string $fullPath, string $notifTitle, ?array $mapped = null): JsonResponse
    {
        $data = $mapped ?? DiagnosisResultMapper::fromAiResult($aiResult ?? []);
        $thumbnail = $fullPath ? $this->makeThumbnail($fullPath) : null;

        $diagnosis = Diagnosis::create(array_merge($data, [
            'user_id'         => $request->user()->id,
            'scan_ref'        => Diagnosis::generateScanRef(),
            'type'            => $type,
            'image_path'      => $imagePath,
            'image_thumbnail' => $thumbnail,
            'language'        => app()->getLocale(),
            'client_scan_id'  => $request->input('client_scan_id') ?: null,
        ]));

        CollectionLocation::createFromRequest($request, $diagnosis);

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
    private function storeUnavailable(Request $request, string $type, ?string $imagePath, ?string $fullPath, string $notifTitle): JsonResponse
    {
        $diagnosis = Diagnosis::create(array_merge(DiagnosisResultMapper::aiUnavailableFallback(), [
            'user_id'         => $request->user()->id,
            'scan_ref'        => Diagnosis::generateScanRef(),
            'type'            => $type,
            'image_path'      => $imagePath,
            'image_thumbnail' => $fullPath ? $this->makeThumbnail($fullPath) : null,
            'language'        => app()->getLocale(),
            'client_scan_id'  => $request->input('client_scan_id') ?: null,
        ]));

        CollectionLocation::createFromRequest($request, $diagnosis);

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

        $loc = $d->collectionLocation;

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
            // Confidence provenance (spec 4.5) — always present so the
            // client can render an honest "what does this number mean"
            // explanation instead of showing a bare percentage.
            'confidenceProvenance' => [
                'aiModelName'             => $d->ai_model_name,
                'aiModelVersion'          => $d->ai_model_version,
                'confidenceInterpretation'=> $d->confidence_interpretation,
                'confidenceDecision'      => $d->confidence_decision,
                'decisionThreshold'       => $d->decision_threshold,
                'validationStatus'        => $d->validation_status,
                'generatedBy'             => $d->generated_by,
            ],
            // Sample collection location (spec 6C) — null when the client
            // never submitted one; never backfilled from account/device/IP.
            'collectionLocation' => $loc ? [
                'country'                 => $loc->country,
                'state'                   => $loc->state,
                'lga'                     => $loc->lga,
                'ward'                    => $loc->ward,
                'community'               => $loc->community,
                'postalCode'              => $loc->postal_code,
                'addressLandmark'         => $loc->address_landmark,
                'latitude'                => $loc->latitude,
                'longitude'               => $loc->longitude,
                'accuracyMeters'          => $loc->accuracy_meters,
                'provenanceLabel'         => $loc->provenanceLabel,
                'verificationStatus'      => $loc->verification_status,
                'differsFromScanLocation' => $loc->differs_from_scan_location,
                'collectedAt'             => $loc->collected_at?->toISOString(),
            ] : null,
            // Phase 11 canonical contract: which recoverable state the result
            // is in, and what to do next. outcome is null on legacy records.
            'assessment' => [
                'outcome'               => $d->assessment_outcome,
                'imageQuality'          => $d->image_quality,
                'imageIssues'           => $d->image_issues ?? [],
                'photoTips'             => $d->photo_tips ?? [],
                'alternativeFindings'   => $d->alternative_findings ?? [],
                'whatCannotBeConfirmed' => $d->what_cannot_be_confirmed,
                'contractVersion'       => $d->contract_version,
            ],
            'dataQualityStatus' => $d->dataQualityStatus,
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
            // Eager-loaded, not accessed lazily below -- the per-row
            // ->collectionLocation inside the map() would otherwise fire
            // one query per diagnosis on a 20-row page.
            ->with('collectionLocation:id,diagnosis_id,state,lga')
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
            // The history list previously carried no location information
            // at all, despite the single-diagnosis detail endpoint (below)
            // having a full location section -- a lightweight label is
            // enough for a list row; the detail screen still has the full
            // picture (coordinates, provenance, verification status).
            'location_label'        => $d->collectionLocation
                ? (trim(implode(', ', array_filter([$d->collectionLocation->lga, $d->collectionLocation->state]))) ?: 'Location recorded')
                : null,
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
