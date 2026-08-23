<?php

namespace App\Http\Controllers;

use App\Models\Diagnosis;
use App\Models\DiagnosisFeedback;
use App\Services\DiagnosisResultMapper;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class DiagnosticController extends Controller
{
    public function scan()
    {
        return view('diagnostics.scan');
    }

    public function analyze(Request $request)
    {
        $request->validate([
            'scan_type'       => 'required|in:plant,animal,soil,pest',
            'image'           => 'required|mimes:jpeg,jpg,png,gif,webp|max:5120',
            'crop_type'       => 'nullable|string|max:100',
            'crop_part'       => 'nullable|string|max:100',
            'animal_type'     => 'nullable|string|max:100',
            'assessment_type' => 'nullable|string|max:100',
            'soil_context'    => 'nullable|string|max:300',
            'pest_location'   => 'nullable|string|max:100',
        ]);

        // ── Subscription scan limit check ─────────────────────────────────────
        $scanCheck = app(\App\Services\SubscriptionLimitService::class)->canScan(auth()->user());
        if (!$scanCheck['allowed']) {
            $limit = $scanCheck['limit'];
            return back()->withErrors(['image' => "You have used all {$limit} AI scans for this month. <a href=\"" . route('subscription.plans') . "\" class=\"underline font-semibold\">Upgrade your plan to continue →</a>"]);
        }

        // ── 1. Store image ────────────────────────────────────────────────────
        // Private 'local' disk (storage/app), not the publicly-symlinked 'public'
        // disk — the image is only ever served back through the authenticated
        // /diagnostics/{diagnosis}/image route, which enforces ownership.
        $uploadedFile = $request->file('image');
        $path         = $uploadedFile->store('diagnostics', 'local');
        $fullPath     = Storage::disk('local')->path($path);
        $mimeType     = $uploadedFile->getMimeType() ?? 'image/jpeg';

        // ── 2. Resolve AI engine connection ───────────────────────────────────
        $baseUrl    = rtrim(config('services.ai_engine.url', ''), '/');
        $aiKey      = config('services.ai_engine.key', '');
        $aiEndpoint = match($request->scan_type) {
            'plant'  => "{$baseUrl}/predict/crop",
            'soil'   => "{$baseUrl}/predict/soil",
            'pest'   => "{$baseUrl}/predict/pest",
            default  => "{$baseUrl}/predict/livestock",
        };

        $aiResult      = null;
        $failureReason = null;

        if (!file_exists($fullPath) || !is_readable($fullPath)) {
            $failureReason = "File unreadable: {$fullPath}";
            Log::error('AI scan: file unreadable', ['path' => $fullPath]);
        } elseif (!$baseUrl) {
            $failureReason = 'AI_ENGINE_URL not configured';
            Log::error('AI scan: missing AI_ENGINE_URL');
        } else {
            // ── 3. Build raw RFC 2046 multipart body ──────────────────────────
            $imageData = file_get_contents($fullPath);
            $boundary  = '----MSASFormBoundary' . bin2hex(random_bytes(12));
            $body      = '';

            // Only send fields that carry actual hint values — all now optional
            $textFields = match($request->scan_type) {
                'plant' => array_filter([
                    'cropType' => $request->input('crop_type'),
                    'cropPart' => $request->input('crop_part'),
                ]),
                'animal' => array_filter([
                    'animalType'     => $request->input('animal_type'),
                    'assessmentType' => $request->input('assessment_type'),
                ]),
                'pest' => array_filter([
                    'cropType' => $request->input('crop_type'),
                    'location' => $request->input('pest_location'),
                ]),
                default => array_filter([
                    'soilContext' => $request->input('soil_context'),
                ]),
            };

            // AI engine generates the diagnosis text directly in the requesting
            // locale (see LANGUAGES map in ai-engine/main.py) rather than
            // translating a stored English diagnosis after the fact.
            $locale = app()->getLocale();
            $textFields['language'] = $locale;

            foreach ($textFields as $fieldName => $fieldValue) {
                $body .= "--{$boundary}\r\n";
                $body .= "Content-Disposition: form-data; name=\"{$fieldName}\"\r\n\r\n";
                $body .= $fieldValue . "\r\n";
            }

            $filename = basename($fullPath);
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Disposition: form-data; name=\"images\"; filename=\"{$filename}\"\r\n";
            $body .= "Content-Type: {$mimeType}\r\n\r\n";
            $body .= $imageData . "\r\n";
            $body .= "--{$boundary}--\r\n";

            // ── 4. POST to AI engine ──────────────────────────────────────────
            try {
                $headers = [
                    'Content-Type'   => "multipart/form-data; boundary={$boundary}",
                    'Content-Length' => strlen($body),
                ];
                if ($aiKey) {
                    $headers['Authorization'] = "Bearer {$aiKey}";
                }

                Log::info('AI scan request', ['url' => $aiEndpoint, 'scan_type' => $request->scan_type]);

                // Pre-warm: ping /health first so Render.com starts waking up the
                // service while we finish building the request. On a cold start this
                // buys ~30 s of spin-up time before the heavy prediction POST fires.
                $guzzle = new GuzzleClient([
                    'connect_timeout' => 90,
                    'timeout'         => 180,
                    'http_errors'     => false,
                ]);

                try {
                    $warmHeaders = $aiKey ? ['Authorization' => "Bearer {$aiKey}"] : [];
                    $guzzle->get("{$baseUrl}/health", ['headers' => $warmHeaders, 'timeout' => 5]);
                } catch (\Throwable) {
                    // Non-fatal — proceed even if warm-up ping fails
                }

                $resp   = $guzzle->post($aiEndpoint, ['body' => $body, 'headers' => $headers]);
                $status = $resp->getStatusCode();
                $rbody  = (string) $resp->getBody();

                Log::info('AI scan response', ['status' => $status]);

                if ($status >= 200 && $status < 300) {
                    $aiResult = json_decode($rbody, true);
                    if (!$aiResult) {
                        $failureReason = "200 OK but non-JSON: " . substr($rbody, 0, 200);
                    }
                } else {
                    $failureReason = "HTTP {$status}: " . substr($rbody, 0, 300);
                    Log::warning('[AI] non-200 response', ['status' => $status, 'body' => substr($rbody, 0, 300)]);
                }
            } catch (\Throwable $e) {
                $failureReason = get_class($e) . ': ' . $e->getMessage();
                Log::error('[AI] exception', ['error' => $e->getMessage()]);
            }
        }

        // ── 5. Generate base64 thumbnail for persistent display ──────────────
        // Uploaded files live in ephemeral container storage. Storing a thumbnail
        // in PostgreSQL ensures images survive container restarts and redeploys.
        $thumbnail = null;
        if (file_exists($fullPath) && function_exists('imagecreatefromstring')) {
            try {
                $imgData  = file_get_contents($fullPath);
                $srcImage = imagecreatefromstring($imgData);
                if ($srcImage !== false) {
                    $srcW   = imagesx($srcImage);
                    $srcH   = imagesy($srcImage);
                    $maxDim = 400;
                    $ratio  = min($maxDim / $srcW, $maxDim / $srcH, 1.0);
                    $dstW   = max(1, (int) round($srcW * $ratio));
                    $dstH   = max(1, (int) round($srcH * $ratio));
                    $dst    = imagecreatetruecolor($dstW, $dstH);
                    imagecopyresampled($dst, $srcImage, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
                    ob_start();
                    imagejpeg($dst, null, 83);
                    $jpegBytes = ob_get_clean();
                    $thumbnail = 'data:image/jpeg;base64,' . base64_encode($jpegBytes);
                    imagedestroy($srcImage);
                    imagedestroy($dst);
                }
            } catch (\Throwable $e) {
                Log::warning('Thumbnail generation failed', ['error' => $e->getMessage()]);
            }
        }

        // ── 6. Build diagnosis record ─────────────────────────────────────────
        if ($aiResult) {
            $diagnosisData = DiagnosisResultMapper::fromAiResult($aiResult);
        } else {
            Log::warning('AI scan failed, falling back to expert review', ['reason' => $failureReason]);
            $diagnosisData = DiagnosisResultMapper::aiUnavailableFallback();
        }

        Diagnosis::create(array_merge($diagnosisData, [
            'user_id'         => auth()->id(),
            'scan_ref'        => Diagnosis::generateScanRef(),
            'type'            => $request->scan_type,
            'image_path'      => $path,
            'image_thumbnail' => $thumbnail,
            'language'        => app()->getLocale(),
        ]));

        \App\Models\SubscriptionUsage::track(auth()->id(), 'ai_scans_per_month');

        $message = $aiResult
            ? 'Scan complete! Your full AI diagnosis is ready — view it below.'
            : 'Image saved. Our experts will review your scan and respond shortly.';

        return redirect()->route('diagnostics.history')->with('success', $message);
    }

    public function history()
    {
        try {
            $feedbackReady = Schema::hasTable('diagnosis_feedbacks');

            $query = Diagnosis::where('user_id', auth()->id())->latest();

            if ($feedbackReady) {
                $query->with('myFeedback');
            }

            $diagnoses = $query->get();
        } catch (\Throwable $e) {
            Log::error('DiagnosticController::history error', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            // Fallback: show history without feedback so page doesn't 500
            $feedbackReady = false;
            $diagnoses     = Diagnosis::where('user_id', auth()->id())->latest()->get();
        }

        return view('diagnostics.history', compact('diagnoses', 'feedbackReady'));
    }

    public function translate(Request $request)
    {
        $request->validate([
            'text'            => 'required|string|max:4000',
            'target_language' => 'required|string|max:10',
        ]);

        if ($request->target_language === 'en') {
            return response()->json(['translated_text' => $request->text]);
        }

        $baseUrl = rtrim(config('services.ai_engine.url', ''), '/');
        $aiKey   = config('services.ai_engine.key', '');

        if (!$baseUrl) {
            return response()->json(['error' => 'Translation service unavailable'], 503);
        }

        try {
            $boundary  = '----MSASTranslateBoundary' . bin2hex(random_bytes(8));
            $body      = "--{$boundary}\r\n";
            $body     .= "Content-Disposition: form-data; name=\"text\"\r\n\r\n";
            $body     .= $request->text . "\r\n";
            $body     .= "--{$boundary}\r\n";
            $body     .= "Content-Disposition: form-data; name=\"target_language\"\r\n\r\n";
            $body     .= $request->target_language . "\r\n";
            $body     .= "--{$boundary}--\r\n";

            $headers = ['Content-Type' => "multipart/form-data; boundary={$boundary}"];
            if ($aiKey) {
                $headers['Authorization'] = "Bearer {$aiKey}";
            }

            $guzzle = new GuzzleClient(['timeout' => 45, 'http_errors' => false]);
            $resp   = $guzzle->post("{$baseUrl}/translate", ['body' => $body, 'headers' => $headers]);
            $data   = json_decode((string) $resp->getBody(), true);

            if ($resp->getStatusCode() >= 200 && $resp->getStatusCode() < 300 && !empty($data['translated_text'])) {
                return response()->json(['translated_text' => $data['translated_text']]);
            }

            Log::warning('Translation response error', ['status' => $resp->getStatusCode()]);
            return response()->json(['error' => 'Translation failed'], 502);
        } catch (\Throwable $e) {
            Log::error('Translation exception', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Translation service error'], 503);
        }
    }

    public function downloadReport(Diagnosis $diagnosis)
    {
        $viewer = auth()->user();
        $isOwner = $diagnosis->user_id === $viewer->id;
        $isCeoOrAdmin = in_array($viewer->role, ['ceo', 'admin'], true);
        abort_if(!$isOwner && !$isCeoOrAdmin, 403);
        $user = $diagnosis->user;

        // Prefer the stored thumbnail (survives container restarts); fall back to
        // reading the file from disk.
        $imageB64 = $diagnosis->image_thumbnail;
        if (!$imageB64 && $diagnosis->image_path) {
            $fullPath = $this->resolveImagePath($diagnosis->image_path);
            if ($fullPath) {
                $mime     = mime_content_type($fullPath) ?: 'image/jpeg';
                $imageB64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
            }
        }

        $translated = $this->translatedDiagnosisText($diagnosis);

        return view('diagnostics.report', compact('diagnosis', 'user', 'imageB64', 'translated'));
    }

    /**
     * Serves the original scanned image. Ownership-gated the same way as
     * downloadReport() — images live on the private 'local' disk (or, for
     * scans created before this route existed, the legacy public disk path)
     * and are never exposed via a directly guessable public storage URL.
     */
    public function image(Diagnosis $diagnosis)
    {
        $viewer = auth()->user();
        $isOwner = $diagnosis->user_id === $viewer->id;
        $isCeoOrAdmin = in_array($viewer->role, ['ceo', 'admin'], true);
        abort_if(!$isOwner && !$isCeoOrAdmin, 403);

        if ($diagnosis->image_path) {
            $fullPath = $this->resolveImagePath($diagnosis->image_path);
            if ($fullPath) {
                return response()->file($fullPath);
            }
        }

        if ($diagnosis->image_thumbnail && preg_match('/^data:(.+);base64,(.+)$/', $diagnosis->image_thumbnail, $m)) {
            return response(base64_decode($m[2]))->header('Content-Type', $m[1]);
        }

        abort(404);
    }

    /** Resolves a stored image_path against the private 'local' disk, then the legacy public disk. */
    private function resolveImagePath(string $path): ?string
    {
        if (Storage::disk('local')->exists($path)) {
            return Storage::disk('local')->path($path);
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->path($path);
        }

        return null;
    }

    /**
     * Translate a diagnosis's free-text fields into the current app locale,
     * in one AI-engine call. Returns [] (falling back to the stored English/
     * source-language text) when no translation is needed or possible —
     * never returns a partial/guessed translation.
     *
     * Deliberately excludes severity_level, urgency_level, and health_status:
     * report.blade.php matches those against fixed English values to pick
     * badge colors, so translating them would break that logic.
     */
    private function translatedDiagnosisText(Diagnosis $diagnosis): array
    {
        $locale = app()->getLocale();
        $sourceLanguage = $diagnosis->language ?: 'en';

        if ($locale === 'en' || $locale === $sourceLanguage) {
            return [];
        }

        $fields = [
            'disease_name', 'symptoms_identified', 'cause', 'environmental_factors',
            'nutrient_deficiencies', 'pest_detection', 'first_aid_steps',
            'recommended_medication', 'fertilizer_recommendation',
            'preventive_measures', 'best_practices', 'vet_referral_advice',
            'explanation', 'recovery_period', 'detected_part',
        ];

        $original = [];
        foreach ($fields as $field) {
            if (!empty($diagnosis->$field)) {
                $original[$field] = $diagnosis->$field;
            }
        }

        $baseUrl = rtrim(config('services.ai_engine.url', ''), '/');
        $aiKey   = config('services.ai_engine.key', '');

        if (empty($original) || !$baseUrl) {
            return [];
        }

        try {
            $payload  = json_encode($original, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP);
            $boundary = '----MSASReportTranslateBoundary' . bin2hex(random_bytes(8));
            $body     = "--{$boundary}\r\n";
            $body    .= "Content-Disposition: form-data; name=\"text\"\r\n\r\n";
            $body    .= $payload . "\r\n";
            $body    .= "--{$boundary}\r\n";
            $body    .= "Content-Disposition: form-data; name=\"target_language\"\r\n\r\n";
            $body    .= $locale . "\r\n";
            $body    .= "--{$boundary}--\r\n";

            $headers = ['Content-Type' => "multipart/form-data; boundary={$boundary}"];
            if ($aiKey) {
                $headers['Authorization'] = "Bearer {$aiKey}";
            }

            $guzzle = new GuzzleClient(['timeout' => 30, 'http_errors' => false]);
            $resp   = $guzzle->post("{$baseUrl}/translate", ['body' => $body, 'headers' => $headers]);

            if ($resp->getStatusCode() < 200 || $resp->getStatusCode() >= 300) {
                Log::warning('Report translation non-200', ['status' => $resp->getStatusCode()]);
                return [];
            }

            $data = json_decode((string) $resp->getBody(), true);
            $raw  = trim((string) ($data['translated_text'] ?? ''));
            // Strip markdown code fences in case the model adds them despite instructions.
            $raw  = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $raw);

            $translatedJson = json_decode($raw, true);
            if (!is_array($translatedJson)) {
                return [];
            }

            // Only trust values for fields we actually asked for — never let
            // the AI response introduce keys we didn't send.
            return array_filter(
                array_intersect_key($translatedJson, $original),
                fn ($v) => is_string($v) && $v !== ''
            );
        } catch (\Throwable $e) {
            Log::warning('Report translation exception', ['error' => $e->getMessage()]);
            return [];
        }
    }

    public function storeFeedback(Request $request, Diagnosis $diagnosis)
    {
        abort_if($diagnosis->user_id !== auth()->id(), 403);

        $request->validate([
            'rating'          => 'required|in:thumbs_up,thumbs_down',
            'correct_disease' => 'nullable|string|max:200',
            'notes'           => 'nullable|string|max:500',
        ]);

        DiagnosisFeedback::updateOrCreate(
            ['diagnosis_id' => $diagnosis->id, 'user_id' => auth()->id()],
            [
                'rating'          => $request->rating,
                'correct_disease' => $request->correct_disease,
                'notes'           => $request->notes,
            ]
        );

        return back()->with('success', 'Thank you for your feedback — it helps improve our AI.');
    }
}
