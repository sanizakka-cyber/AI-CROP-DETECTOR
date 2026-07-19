<?php

namespace App\Http\Controllers;

use App\Models\Diagnosis;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        // ── 3. Guard: file must be readable ───────────────────────────────────
        if (!file_exists($fullPath) || !is_readable($fullPath)) {
            $failureReason = "File unreadable: {$fullPath}";
            Log::error('AI scan: file unreadable', ['path' => $fullPath]);
        } elseif (!$baseUrl) {
            $failureReason = 'AI_ENGINE_URL not configured';
            Log::error('AI scan: missing AI_ENGINE_URL');
        } else {
            // ── 4. Build raw multipart body ───────────────────────────────────
            // Manually construct the multipart/form-data body so every byte is
            // exactly what python-multipart expects — no library surprises.
            // Text fields come first (no filename/Content-Type), file last.
            $imageData = file_get_contents($fullPath);
            $boundary  = '----MSASFormBoundary' . bin2hex(random_bytes(12));
            $body      = '';

            $textFields = match($request->scan_type) {
                'plant' => [
                    'cropType' => $request->input('crop_type') ?: 'Unknown crop',
                    'cropPart' => $request->input('crop_part') ?: 'plant',
                ],
                'animal' => [
                    'animalType'     => $request->input('animal_type') ?: 'Unknown animal',
                    'assessmentType' => $request->input('assessment_type') ?: 'general',
                ],
                default => [
                    'soilContext' => $request->input('soil_context') ?: '',
                ],
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

            // ── 5. POST to AI engine ──────────────────────────────────────────
            try {
                $headers = [
                    'Content-Type'   => "multipart/form-data; boundary={$boundary}",
                    'Content-Length' => strlen($body),
                ];
                if ($aiKey) {
                    $headers['Authorization'] = "Bearer {$aiKey}";
                }

                Log::info('AI scan request', ['url' => $aiEndpoint, 'scan_type' => $request->scan_type]);

                $guzzle = new GuzzleClient([
                    'connect_timeout' => 30,
                    'timeout'         => 90,
                    'http_errors'     => false,
                ]);

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
                }
            } catch (\Throwable $e) {
                $failureReason = get_class($e) . ': ' . $e->getMessage();
                Log::error('[AI] exception', ['error' => $e->getMessage()]);
            }
        }

        // ── 6. Build diagnosis record ─────────────────────────────────────────
        if ($aiResult) {
            $diagnosisData = [
                'disease_name'           => $aiResult['disease']    ?? $aiResult['condition'] ?? 'Requires expert review',
                'confidence_score'       => (int) ($aiResult['confidence'] ?? 0),
                'cause'                  => $aiResult['cause']      ?? null,
                'urgency_level'          => $aiResult['urgency']    ?? 'Medium',
                'first_aid_steps'        => $aiResult['first_aid']  ?? $aiResult['recommendation'] ?? null,
                'recommended_medication' => $aiResult['medication'] ?? $aiResult['suitable_crops'] ?? null,
                'vet_referral_advice'    => $aiResult['referral']   ?? null,
                'status'                 => 'reviewed',
            ];
        } else {
            $diagnosisData = [
                'disease_name'           => 'Pending Expert Review',
                'confidence_score'       => 0,
                'cause'                  => null,
                'urgency_level'          => 'Medium',
                'first_aid_steps'        => null,
                'recommended_medication' => null,
                'vet_referral_advice'    => 'Our AI engine is temporarily unavailable. An expert will review your scan and respond shortly.',
                'status'                 => 'needs_review',
            ];
        }

        Diagnosis::create(array_merge($diagnosisData, [
            'user_id'    => auth()->id(),
            'type'       => $request->scan_type,
            'image_path' => $path,
        ]));

        $message = $aiResult
            ? 'Scan complete! Your AI diagnosis is ready — view it below.'
            : 'Image saved. Our experts will review your scan and respond shortly.';

        return redirect()->route('diagnostics.history')->with('success', $message);
    }

    public function history()
    {
        $diagnoses = Diagnosis::where('user_id', auth()->id())->latest()->get();
        return view('diagnostics.history', compact('diagnoses'));
    }
}
