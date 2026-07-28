<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class VetController extends Controller
{
    public function queue()
    {
        $user = auth()->user();

        if (! $user->is_verified) {
            return redirect()->route('dashboard')
                ->with('warning', 'Your account is pending verification by an administrator before you can access the consultation queue.');
        }

        // Vet sees: paid+unassigned cases ('open') + cases already assigned to them
        // Agronomist only sees crop cases; Vet only sees livestock cases
        $query = \App\Models\Consultation::where('status', 'open')
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
        $user = auth()->user();
        // Only the assigned expert or an unassigned open consultation visible to matching role
        $isAssignedToMe = $consultation->expert_id === $user->id;
        $isOpenAndMatchingRole = is_null($consultation->expert_id)
            && $consultation->status === 'open'
            && (
                ($user->role === 'vet' && $consultation->case_type === 'livestock')
                || ($user->role === 'agronomist' && $consultation->case_type === 'crop')
            );

        abort_unless($isAssignedToMe || $isOpenAndMatchingRole, 403, 'You do not have permission to view this consultation.');

        return view('vet.show', compact('consultation'));
    }

    public function respond(Request $request, \App\Models\Consultation $consultation)
    {
        abort_unless(auth()->user()->is_verified, 403, 'Account not yet verified.');

        // Prevent overwriting another expert's response
        abort_if(
            $consultation->expert_id && $consultation->expert_id !== auth()->id(),
            403,
            'This consultation is assigned to another expert.'
        );
        abort_if($consultation->status === 'resolved', 422, 'This consultation has already been resolved.');

        $request->validate([
            'expert_response' => 'required|string|min:10',
        ]);

        $consultation->update([
            'expert_id' => auth()->id(),
            'expert_response' => $request->expert_response,
            'status' => 'resolved',
            'completed_at' => now(),
        ]);

        return redirect()->route('vet.queue')->with('success', 'Consultation resolved successfully.');
    }

    // POST /vet/consultation/{consultation}/start-video — vet initiates a Jitsi video room
    public function startVideo(\App\Models\Consultation $consultation)
    {
        abort_unless(auth()->user()->is_verified, 403, 'Account not yet verified.');
        abort_if(
            $consultation->expert_id && $consultation->expert_id !== auth()->id(),
            403,
            'This consultation is assigned to another expert.'
        );

        if (! $consultation->video_room_id) {
            $consultation->update([
                'video_room_id' => Str::uuid()->toString(),
                'expert_id'     => auth()->id(),
            ]);
        }

        return redirect()->route('consultation.video', $consultation);
    }
}

