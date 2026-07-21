<?php

namespace App\Http\Controllers;

use App\Models\Diagnosis;
use App\Models\DiagnosisFeedback;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DiagnosticController extends Controller
{
    public function scan()
    {
        return view('diagnostics.scan');
    }

    public function analyze(Request $request)
    {
        $request->validate([
            'scan_type'       => 'required|in:plant,animal,soil',
            'image'           => 'required|image|max:5120',
            'crop_type'       => 'nullable|string|max:100',
            'crop_part'       => 'nullable|string|max:100',
            'animal_type'     => 'nullable|string|max:100',
            'assessment_type' => 'nullable|string|max:100',
            'soil_context'    => 'nullable|string|max:300',
        ]);

        // ── 1. Store image ────────────────────────────────────────────────────
        $uploadedFile = $request->file('image');
        $path         = $uploadedFile->store('diagnostics', 'public');
        $fullPath     = storage_path('app/public/' . $path);
        $mimeType     = $uploadedFile->getMimeType() ?? 'image/jpeg';

        // ── 2. Resolve AI engine connection ───────────────────────────────────
        $baseUrl    = rtrim(config('services.ai_engine.url', ''), '/');
        $aiKey      = config('services.ai_engine.key', '');
        $aiEndpoint = match($request->scan_type) {
            'plant'  => "{$baseUrl}/predict/crop",
            'soil'   => "{$baseUrl}/predict/soil",
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
                default => array_filter([
                    'soilContext' => $request->input('soil_context'),
                ]),
            };

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
            $diagnosisData = [
                // Subject identification (auto-detected)
                'subject_name'             => $aiResult['subject_name']     ?? $aiResult['species']     ?? $aiResult['crop']  ?? $aiResult['animal']  ?? null,
                'scientific_name'          => $aiResult['scientific_name']   ?? $aiResult['latin_name']  ?? null,
                'detected_part'            => $aiResult['detected_part']     ?? $aiResult['body_part']   ?? null,
                'health_status'            => $aiResult['health_status']     ?? $aiResult['status']      ?? null,
                'severity_level'           => $aiResult['severity']          ?? $aiResult['severity_level'] ?? null,
                // Core
                'disease_name'             => $aiResult['disease']           ?? $aiResult['condition']   ?? $aiResult['diagnosis'] ?? $aiResult['disease_name'] ?? $aiResult['label'] ?? 'Requires expert review',
                'confidence_score'         => (float) ($aiResult['confidence']    ?? 0),
                'urgency_level'            => $aiResult['urgency']                ?? 'Medium',
                // Findings
                'symptoms_identified'      => $aiResult['symptoms_identified']    ?? null,
                'cause'                    => $aiResult['cause']                  ?? null,
                'environmental_factors'    => $aiResult['environmental_factors']  ?? null,
                'nutrient_deficiencies'    => $aiResult['nutrient_deficiencies']  ?? null,
                'pest_detection'           => $aiResult['pest_detection']         ?? null,
                // Treatment
                'first_aid_steps'          => $aiResult['first_aid']              ?? null,
                'recommended_medication'   => $aiResult['medication']             ?? $aiResult['fertilizer_recommendation'] ?? $aiResult['amendment_recommendation'] ?? null,
                'preventive_measures'      => $aiResult['preventive_measures']    ?? null,
                'fertilizer_recommendation'=> $aiResult['fertilizer_recommendation'] ?? null,
                'recovery_period'          => $aiResult['recovery_period']        ?? null,
                'best_practices'           => $aiResult['best_practices']         ?? null,
                'vet_referral_advice'      => $aiResult['referral']               ?? $aiResult['vet_recommendation'] ?? null,
                // Explainability
                'explanation'              => $aiResult['explanation']            ?? null,
                'status'                   => 'reviewed',
            ];
        } else {
            Log::warning('AI scan failed, falling back to expert review', ['reason' => $failureReason]);
            $diagnosisData = [
                'subject_name'              => null,
                'scientific_name'           => null,
                'detected_part'             => null,
                'health_status'             => null,
                'severity_level'            => null,
                'disease_name'              => 'Pending Expert Review',
                'confidence_score'          => 0,
                'urgency_level'             => 'Medium',
                'symptoms_identified'       => null,
                // these three were NOT NULL in original schema — safe empty string until migration runs
                'cause'                     => '',
                'first_aid_steps'           => '',
                'recommended_medication'    => '',
                'environmental_factors'     => null,
                'nutrient_deficiencies'     => null,
                'pest_detection'            => null,
                'preventive_measures'       => null,
                'fertilizer_recommendation' => null,
                'recovery_period'           => null,
                'best_practices'            => null,
                'vet_referral_advice'       => 'Our AI engine is temporarily unavailable. An expert will review your scan and respond shortly.',
                'explanation'               => null,
                'status'                    => 'needs_review',
            ];
        }

        Diagnosis::create(array_merge($diagnosisData, [
            'user_id'         => auth()->id(),
            'type'            => $request->scan_type,
            'image_path'      => $path,
            'image_thumbnail' => $thumbnail,
        ]));

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
        abort_if($diagnosis->user_id !== auth()->id(), 403);
        $user = auth()->user();

        // Prefer the stored thumbnail (survives container restarts); fall back to
        // reading the file from disk (works if storage symlink exists and file not deleted).
        $imageB64 = $diagnosis->image_thumbnail;
        if (!$imageB64 && $diagnosis->image_path) {
            $fullPath = storage_path('app/public/' . $diagnosis->image_path);
            if (file_exists($fullPath) && is_readable($fullPath)) {
                $mime     = mime_content_type($fullPath) ?: 'image/jpeg';
                $imageB64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($fullPath));
            }
        }

        return view('diagnostics.report', compact('diagnosis', 'user', 'imageB64'));
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
