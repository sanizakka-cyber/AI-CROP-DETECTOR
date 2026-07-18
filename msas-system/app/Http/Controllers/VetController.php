<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VetController extends Controller
{
    public function queue()
    {
        $user = auth()->user();

        if (! $user->is_verified) {
            return redirect()->route('dashboard')
                ->with('warning', 'Your account is pending verification by an administrator before you can access the consultation queue.');
        }

        // Vet sees: unassigned pending cases + cases already assigned to them
        // Agronomist only sees crop cases; Vet only sees livestock cases
        $query = \App\Models\Consultation::where('status', 'pending')
            ->where(function ($q) use ($user) {
                $q->whereNull('expert_id')->orWhere('expert_id', $user->id);
            });

        if ($user->role === 'agronomist') {
            $query->where('case_type', 'crop');
        } elseif ($user->role === 'vet') {
            $query->where('case_type', 'livestock');
        }

        $consultations = $query->with(['farmer'])->latest()->get();
        return view('vet.queue', compact('consultations'));
    }

    public function show(\App\Models\Consultation $consultation)
    {
        return view('vet.show', compact('consultation'));
    }

    public function respond(Request $request, \App\Models\Consultation $consultation)
    {
        abort_unless(auth()->user()->is_verified, 403, 'Account not yet verified.');
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

