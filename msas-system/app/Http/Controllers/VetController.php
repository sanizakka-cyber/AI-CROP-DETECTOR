<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VetController extends Controller
{
    public function queue()
    {
        // Vet views pending consultations
        $consultations = \App\Models\Consultation::where('status', 'pending')->latest()->get();
        return view('vet.queue', compact('consultations'));
    }

    public function show(\App\Models\Consultation $consultation)
    {
        return view('vet.show', compact('consultation'));
    }

    public function respond(Request $request, \App\Models\Consultation $consultation)
    {
        $request->validate([
            'expert_response' => 'required|string|min:10',
        ]);

        $consultation->update([
            'expert_id' => auth()->id(),
            'expert_response' => $request->expert_response,
            'status' => 'resolved', // or 'completed'
            'completed_at' => now(),
        ]);

        return redirect()->route('vet.queue')->with('success', 'Consultation resolved successfully.');
    }
}

