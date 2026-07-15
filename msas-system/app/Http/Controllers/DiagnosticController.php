<?php

namespace App\Http\Controllers;

use App\Models\Diagnosis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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

        $path     = $request->file('image')->store('diagnostics', 'public');
        $fullPath = storage_path('app/public/' . $path);

        $baseUrl = rtrim(config('services.ai_engine.url', env('AI_ENGINE_URL', 'http://127.0.0.1:8001')), '/');
        $aiKey   = config('services.ai_engine.key', env('AI_ENGINE_KEY', ''));

        $aiEndpoint = match($request->scan_type) {
            'plant' => "{$baseUrl}/predict/crop",
            'soil'  => "{$baseUrl}/predict/soil",
            default => "{$baseUrl}/predict/livestock",
        };

        $aiResult = null;

        try {
            $http = Http::timeout(30);
            if ($aiKey) {
                $http = $http->withToken($aiKey);
            }

            $multipart = $http->attach('images', file_get_contents($fullPath), basename($fullPath));

            // Add scan-specific context fields required by the AI engine
            if ($request->scan_type === 'plant') {
                $multipart = $multipart
                    ->asMultipart()
                    ->attach('cropType', $request->input('crop_type', 'Unknown crop'))
                    ->attach('cropPart', $request->input('crop_part', 'plant'));
                // Re-attach image since asMultipart resets
                $response = Http::timeout(30)
                    ->when($aiKey, fn($h) => $h->withToken($aiKey))
                    ->attach('images', file_get_contents($fullPath), basename($fullPath))
                    ->attach('cropType', $request->input('crop_type', 'Unknown crop'))
                    ->attach('cropPart', $request->input('crop_part', 'plant'))
                    ->post($aiEndpoint);
            } elseif ($request->scan_type === 'animal') {
                $response = Http::timeout(30)
                    ->when($aiKey, fn($h) => $h->withToken($aiKey))
                    ->attach('images', file_get_contents($fullPath), basename($fullPath))
                    ->attach('animalType', $request->input('animal_type', 'Unknown animal'))
                    ->attach('assessmentType', $request->input('assessment_type', 'general'))
                    ->post($aiEndpoint);
            } else {
                // soil
                $response = Http::timeout(30)
                    ->when($aiKey, fn($h) => $h->withToken($aiKey))
                    ->attach('images', file_get_contents($fullPath), basename($fullPath))
                    ->attach('soilContext', $request->input('soil_context', ''))
                    ->post($aiEndpoint);
            }

            if ($response->successful()) {
                $aiResult = $response->json();
            }
        } catch (\Throwable) {
            // AI engine offline — handled below
        }

        if ($aiResult) {
            $diagnosisData = [
                'disease_name'           => $aiResult['disease'] ?? $aiResult['condition'] ?? $aiResult['prediction'] ?? 'Requires expert review',
                'confidence_score'       => (int) ($aiResult['confidence'] ?? 0),
                'cause'                  => $aiResult['cause'] ?? null,
                'urgency_level'          => $aiResult['urgency'] ?? 'Medium',
                'first_aid_steps'        => $aiResult['first_aid'] ?? $aiResult['recommendation'] ?? null,
                'recommended_medication' => $aiResult['medication'] ?? $aiResult['suitable_crops'] ?? null,
                'vet_referral_advice'    => $aiResult['referral'] ?? null,
                'status'                 => (int) ($aiResult['confidence'] ?? 0) < 60 ? 'needs_review' : 'pending',
            ];
        } else {
            $diagnosisData = [
                'disease_name'           => 'Pending Expert Review',
                'confidence_score'       => 0,
                'cause'                  => null,
                'urgency_level'          => 'Medium',
                'first_aid_steps'        => null,
                'recommended_medication' => null,
                'vet_referral_advice'    => 'AI engine unavailable. An expert will review this scan shortly.',
                'status'                 => 'needs_review',
            ];
        }

        Diagnosis::create(array_merge($diagnosisData, [
            'user_id'    => auth()->id(),
            'type'       => $request->scan_type,
            'image_path' => $path,
        ]));

        $message = $aiResult
            ? 'Scan complete. Your AI diagnosis is ready — view it below.'
            : 'Image saved. Our experts will review your scan and respond shortly.';

        return redirect()->route('diagnostics.history')->with('success', $message);
    }

    public function history()
    {
        $diagnoses = Diagnosis::where('user_id', auth()->id())->latest()->get();
        return view('diagnostics.history', compact('diagnoses'));
    }
}
