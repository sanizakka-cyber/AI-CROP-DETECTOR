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
            // ── 4. Build multipart parts ──────────────────────────────────────
            // Using Guzzle directly (not Laravel Http facade) so the multipart
            // body is built exactly as Guzzle expects — no Laravel wrapper
            // injecting a competing json:[] option.
            $fileHandle = fopen($fullPath, 'r');

            $multipart = match($request->scan_type) {
                'plant' => [
                    [
                        'name'     => 'images',
                        'contents' => $fileHandle,
                        'filename' => basename($fullPath),
                        'headers'  => ['Content-Type' => $mimeType],
                    ],
                    ['name' => 'cropType', 'contents' => $request->input('crop_type', 'Unknown crop')],
                    ['name' => 'cropPart', 'contents' => $request->input('crop_part', 'plant')],
                ],
                'animal' => [
                    [
                        'name'     => 'images',
                        'contents' => $fileHandle,
                        'filename' => basename($fullPath),
                        'headers'  => ['Content-Type' => $mimeType],
                    ],
                    ['name' => 'animalType',     'contents' => $request->input('animal_type', 'Unknown animal')],
                    ['name' => 'assessmentType', 'contents' => $request->input('assessment_type', 'general')],
                ],
                default => [
                    [
                        'name'     => 'images',
                        'contents' => $fileHandle,
                        'filename' => basename($fullPath),
                        'headers'  => ['Content-Type' => $mimeType],
                    ],
                    ['name' => 'soilContext', 'contents' => $request->input('soil_context', '')],
                ],
            };

            // ── 5. Call AI engine via Guzzle ──────────────────────────────────
            try {
                $guzzle = new GuzzleClient([
                    'connect_timeout' => 30,
                    'timeout'         => 90,
                    'http_errors'     => false,  // handle status codes manually
                ]);

                $guzzleOpts = ['multipart' => $multipart];
                if ($aiKey) {
                    $guzzleOpts['headers'] = ['Authorization' => "Bearer {$aiKey}"];
                }

                Log::error('[AI] sending request', ['url' => $aiEndpoint, 'scan_type' => $request->scan_type, 'base_url' => $baseUrl]);

                $resp   = $guzzle->post($aiEndpoint, $guzzleOpts);
                $status = $resp->getStatusCode();
                $body   = (string) $resp->getBody();

                Log::error('[AI] response received', ['status' => $status, 'body_preview' => substr($body, 0, 200)]);

                if ($status >= 200 && $status < 300) {
                    $aiResult = json_decode($body, true);
                    if (!$aiResult) {
                        $failureReason = "200 OK but non-JSON body: " . substr($body, 0, 300);
                    }
                } else {
                    $failureReason = "HTTP {$status}: " . substr($body, 0, 300);
                }
            } catch (\GuzzleHttp\Exception\ConnectException $e) {
                $failureReason = 'Connect error: ' . $e->getMessage();
                Log::error('[AI] connect exception', ['error' => $e->getMessage()]);
            } catch (\Throwable $e) {
                $failureReason = get_class($e) . ': ' . $e->getMessage();
                Log::error('[AI] exception', ['error' => $e->getMessage()]);
            } finally {
                if (is_resource($fileHandle)) {
                    fclose($fileHandle);
                }
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
                // Temporarily surface failure reason for debugging.
                // Replace with generic message once scan is confirmed working.
                'vet_referral_advice'    => $failureReason
                    ? '[DBG] ' . substr($failureReason, 0, 300)
                    : 'AI engine unavailable. An expert will review your scan shortly.',
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
