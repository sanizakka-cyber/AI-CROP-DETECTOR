<?php

namespace App\Http\Controllers;

use App\Models\Diagnosis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
        $baseUrl     = rtrim(config('services.ai_engine.url', ''), '/');
        $aiKey       = config('services.ai_engine.key', '');
        $aiEndpoint  = match($request->scan_type) {
            'plant'  => "{$baseUrl}/predict/crop",
            'soil'   => "{$baseUrl}/predict/soil",
            default  => "{$baseUrl}/predict/livestock",
        };

        $aiResult      = null;
        $failureReason = null;

        // ── 3. Guard: file must be readable ───────────────────────────────────
        if (!file_exists($fullPath) || !is_readable($fullPath)) {
            $failureReason = "Image file unreadable at {$fullPath}";
            Log::error('AI scan: file unreadable', ['path' => $fullPath]);
        } elseif (!$baseUrl) {
            $failureReason = 'AI_ENGINE_URL not configured';
            Log::error('AI scan: missing AI_ENGINE_URL');
        } else {
            // ── 4. Build multipart body ───────────────────────────────────────
            // Use raw Guzzle multipart (not ->attach()) so text fields carry no
            // filename and FastAPI's Form() parser accepts them correctly.
            $fileHandle = fopen($fullPath, 'r');
            $fileSize   = filesize($fullPath);

            $multipart = match($request->scan_type) {
                'plant' => [
                    [
                        'name'     => 'images',
                        'contents' => $fileHandle,
                        'filename' => basename($fullPath),
                        'headers'  => ['Content-Type' => $mimeType, 'Content-Length' => $fileSize],
                    ],
                    ['name' => 'cropType', 'contents' => $request->input('crop_type', 'Unknown crop')],
                    ['name' => 'cropPart', 'contents' => $request->input('crop_part', 'plant')],
                ],
                'animal' => [
                    [
                        'name'     => 'images',
                        'contents' => $fileHandle,
                        'filename' => basename($fullPath),
                        'headers'  => ['Content-Type' => $mimeType, 'Content-Length' => $fileSize],
                    ],
                    ['name' => 'animalType',     'contents' => $request->input('animal_type', 'Unknown animal')],
                    ['name' => 'assessmentType', 'contents' => $request->input('assessment_type', 'general')],
                ],
                default => [
                    [
                        'name'     => 'images',
                        'contents' => $fileHandle,
                        'filename' => basename($fullPath),
                        'headers'  => ['Content-Type' => $mimeType, 'Content-Length' => $fileSize],
                    ],
                    ['name' => 'soilContext', 'contents' => $request->input('soil_context', '')],
                ],
            };

            // ── 5. Call AI engine ─────────────────────────────────────────────
            try {
                $client = Http::connectTimeout(10)
                    ->timeout(90)
                    ->withOptions(['multipart' => $multipart]);

                if ($aiKey) {
                    $client = $client->withToken($aiKey);
                }

                Log::info('AI engine request', ['url' => $aiEndpoint, 'type' => $request->scan_type]);

                $response = $client->post($aiEndpoint);

                if ($response->successful()) {
                    $aiResult = $response->json();
                    Log::info('AI engine success', ['disease' => $aiResult['disease'] ?? $aiResult['condition'] ?? '?']);
                } else {
                    $failureReason = "HTTP {$response->status()}: " . substr($response->body(), 0, 500);
                    Log::error('AI engine non-2xx', [
                        'status'   => $response->status(),
                        'body'     => $response->body(),
                        'endpoint' => $aiEndpoint,
                    ]);
                }
            } catch (\Throwable $e) {
                $failureReason = get_class($e) . ': ' . $e->getMessage();
                Log::error('AI engine exception', ['error' => $e->getMessage(), 'endpoint' => $aiEndpoint]);
            } finally {
                if (is_resource($fileHandle)) {
                    fclose($fileHandle);
                }
            }
        }

        // ── 6. Build diagnosis record ─────────────────────────────────────────
        if ($aiResult) {
            $diagnosisData = [
                'disease_name'           => $aiResult['disease']   ?? $aiResult['condition'] ?? 'Requires expert review',
                'confidence_score'       => (int) ($aiResult['confidence'] ?? 0),
                'cause'                  => $aiResult['cause']     ?? null,
                'urgency_level'          => $aiResult['urgency']   ?? 'Medium',
                'first_aid_steps'        => $aiResult['first_aid'] ?? $aiResult['recommendation'] ?? null,
                'recommended_medication' => $aiResult['medication'] ?? $aiResult['suitable_crops'] ?? null,
                'vet_referral_advice'    => $aiResult['referral']  ?? null,
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
