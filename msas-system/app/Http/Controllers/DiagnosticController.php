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
            'scan_type' => 'required|in:plant,animal',
            'image'     => 'required|image|max:5120',
        ]);

        $path     = $request->file('image')->store('diagnostics', 'public');
        $fullPath = storage_path('app/public/' . $path);

        $aiEndpoint = $request->scan_type === 'plant'
            ? 'http://127.0.0.1:8001/predict/crop'
            : 'http://127.0.0.1:8001/predict/livestock';

        $aiResult = null;

        try {
            $response = Http::timeout(15)
                ->attach('images', file_get_contents($fullPath), basename($fullPath))
                ->post($aiEndpoint);

            if ($response->successful()) {
                $aiResult = $response->json();
            }
        } catch (\Throwable) {
            // AI engine offline — handled below
        }

        if ($aiResult) {
            $diagnosisData = [
                'disease_name'           => $aiResult['disease'] ?? $aiResult['prediction'] ?? 'Requires expert review',
                'confidence_score'       => $aiResult['confidence'] ?? 0,
                'cause'                  => $aiResult['cause'] ?? null,
                'urgency_level'          => $aiResult['urgency'] ?? 'Medium',
                'first_aid_steps'        => $aiResult['first_aid'] ?? null,
                'recommended_medication' => $aiResult['medication'] ?? null,
                'vet_referral_advice'    => $aiResult['referral'] ?? null,
                'status'                 => ($aiResult['confidence'] ?? 0) < 60 ? 'needs_review' : 'pending',
            ];
        } else {
            // AI engine unavailable — save image and flag for expert review
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
            ? 'Scan analysed successfully. View your diagnosis below.'
            : 'Image saved. Our experts will review your scan and respond shortly.';

        return redirect()->route('diagnostics.history')->with('success', $message);
    }

    public function history()
    {
        $diagnoses = Diagnosis::where('user_id', auth()->id())->latest()->get();
        return view('diagnostics.history', compact('diagnoses'));
    }
}
