<?php

namespace App\Http\Controllers;

use App\Models\Diagnosis;
use Illuminate\Http\Request;

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
            'image' => 'required|image|max:5120', // 5MB Max
        ]);

        // Simulating Image Upload
        $path = $request->file('image')->store('diagnostics', 'public');

        // SIMULATED AI DETECTION LOGIC
        // In a real production environment, we would send $path to a Python Flask/FastAPI service
        $isPlant = $request->scan_type === 'plant';

        $mockResults = $isPlant ? [
            [
                'disease_name' => 'Fungal Leaf Spot (Cercospora)',
                'confidence_score' => 92.4,
                'cause' => 'High humidity and poor air circulation favoring fungal growth.',
                'urgency_level' => 'Medium',
                'first_aid_steps' => 'Remove infected leaves immediately. Improve spacing between plants.',
                'recommended_medication' => 'Copper-based Fungicide or Neem Oil spray.',
                'vet_referral_advice' => 'Consult an Agronomist if it spreads to >30% of crops.'
            ],
            [
                'disease_name' => 'Nitrogen Deficiency',
                'confidence_score' => 88.1,
                'cause' => 'Depleted soil nutrients or poor root absorption.',
                'urgency_level' => 'Low',
                'first_aid_steps' => 'Check soil pH levels.',
                'recommended_medication' => 'Apply NPK Fertilizer (high nitrogen ratio).',
                'vet_referral_advice' => 'Soil testing recommended.'
            ]
        ] : [
            [
                'disease_name' => 'Foot Rot (Infectious Pododermatitis)',
                'confidence_score' => 94.7,
                'cause' => 'Bacterial infection (Fusobacterium necrophorum) in damp/muddy conditions.',
                'urgency_level' => 'High',
                'first_aid_steps' => 'Isolate the animal to a dry area. Clean the hoof with antiseptic.',
                'recommended_medication' => 'Penicillin or Oxytetracycline antibiotics (Consult Vet for dosage).',
                'vet_referral_advice' => 'Immediate Veterinary inspection required if lameness persists over 24h.'
            ],
            [
                'disease_name' => 'Internal Parasites (Worms)',
                'confidence_score' => 85.2,
                'cause' => 'Grazing on contaminated pastures.',
                'urgency_level' => 'Medium',
                'first_aid_steps' => 'Ensure clean drinking water. Rotate grazing pastures.',
                'recommended_medication' => 'Broad-spectrum Dewormer (Albendazole or Ivermectin).',
                'vet_referral_advice' => 'Routine deworming schedule needed.'
            ]
        ];

        // Pick a random mock result to simulate AI evaluation
        $result = $mockResults[array_rand($mockResults)];

        // Save to Database
        $diagnosis = Diagnosis::create([
            'user_id' => auth()->id(),
            'type' => $request->scan_type,
            'image_path' => $path,
            'disease_name' => $result['disease_name'],
            'confidence_score' => $result['confidence_score'],
            'cause' => $result['cause'],
            'urgency_level' => $result['urgency_level'],
            'first_aid_steps' => $result['first_aid_steps'],
            'recommended_medication' => $result['recommended_medication'],
            'vet_referral_advice' => $result['vet_referral_advice'],
            'status' => 'pending'
        ]);

        return redirect()->route('diagnostics.history')->with('success', 'Scan completed successfully! Here is your diagnosis.');
    }

    public function history()
    {
        $diagnoses = Diagnosis::where('user_id', auth()->id())->latest()->get();
        return view('diagnostics.history', compact('diagnoses'));
    }
}
