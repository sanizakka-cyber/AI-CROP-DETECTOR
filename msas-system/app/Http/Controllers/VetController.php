<?php

namespace App\Http\Controllers;

use App\Models\Diagnosis;
use App\Models\User;
use App\Models\Vaccination;
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

    // GET /vet/vaccinations — list + form
    public function vaccinations()
    {
        $vet = auth()->user();

        $upcoming = Vaccination::where('vet_id', $vet->id)
            ->whereNotNull('next_due_at')
            ->where('next_due_at', '>=', now())
            ->where('next_due_at', '<=', now()->addDays(30))
            ->with('farmer')
            ->orderBy('next_due_at')
            ->get();

        $records = Vaccination::where('vet_id', $vet->id)
            ->with('farmer')
            ->latest('administered_at')
            ->paginate(20);

        $farmers = User::where('role', 'farmer')->where('is_active', true)
            ->select('id', 'first_name', 'last_name', 'phone')
            ->orderBy('first_name')
            ->get();

        return view('vet.vaccinations', compact('upcoming', 'records', 'farmers'));
    }

    // POST /vet/vaccinations — record a new vaccination
    public function storeVaccination(Request $request)
    {
        abort_unless(auth()->user()->is_verified, 403, 'Account not yet verified.');

        $data = $request->validate([
            'farmer_id'      => 'required|exists:users,id',
            'animal_type'    => 'required|string|max:100',
            'vaccine_name'   => 'required|string|max:150',
            'batch_number'   => 'nullable|string|max:100',
            'administered_at'=> 'required|date|before_or_equal:today',
            'next_due_at'    => 'nullable|date|after:administered_at',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $data['vet_id'] = auth()->id();

        Vaccination::create($data);

        return redirect()->route('vet.vaccinations')->with('success', 'Vaccination record saved successfully.');
    }

    // GET /vet/disease-alerts — outbreak data from diagnoses
    public function diseaseAlerts()
    {
        $vet = auth()->user();

        try {
            $outbreaks = Diagnosis::select('disease_name', 'type', \Illuminate\Support\Facades\DB::raw('count(*) as cases'))
                ->where('created_at', '>=', now()->subDays(30))
                ->whereNotNull('disease_name')
                ->where('disease_name', '!=', 'Pending Expert Review')
                ->where('disease_name', '!=', '')
                ->where('type', $vet->role === 'agronomist' ? 'plant' : 'animal')
                ->groupBy('disease_name', 'type')
                ->orderByDesc('cases')
                ->take(15)
                ->get()
                ->map(fn($d) => [
                    'disease'  => $d->disease_name,
                    'type'     => $d->type,
                    'cases'    => $d->cases,
                    'severity' => $d->cases >= 10 ? 'high' : ($d->cases >= 4 ? 'medium' : 'low'),
                ]);
        } catch (\Exception $e) {
            $outbreaks = collect([]);
        }

        $recentCases = Diagnosis::where('type', $vet->role === 'agronomist' ? 'plant' : 'animal')
            ->whereNotNull('disease_name')
            ->where('disease_name', '!=', 'Pending Expert Review')
            ->latest()
            ->take(10)
            ->get(['id', 'disease_name', 'type', 'status', 'created_at']);

        return view('vet.disease-alerts', compact('outbreaks', 'recentCases'));
    }
}

