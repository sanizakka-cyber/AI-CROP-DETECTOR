<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Consultation;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;

class ConsultationController extends Controller
{
    public function index(Request $request)
    {
        $query = Consultation::with(['farmer:id,first_name,last_name,phone', 'expert:id,first_name,last_name,role'])
            ->latest();

        if ($status = $request->status) {
            $query->where('status', $status);
        }
        if ($type = $request->case_type) {
            $query->where('case_type', $type);
        }
        if ($assigned = $request->assigned) {
            $assigned === 'unassigned'
                ? $query->whereNull('expert_id')
                : $query->whereNotNull('expert_id');
        }
        if ($search = $request->search) {
            $query->whereHas('farmer', fn ($q) =>
                $q->where('first_name', 'ilike', "%{$search}%")
                  ->orWhere('last_name', 'ilike', "%{$search}%")
            );
        }

        $consultations = $query->paginate(20)->withQueryString();

        $stats = [
            'total'      => Consultation::count(),
            'open'       => Consultation::where('status', 'open')->count(),
            'resolved'   => Consultation::where('status', 'resolved')->count(),
            'unassigned' => Consultation::whereNull('expert_id')->where('status', 'open')->count(),
            'livestock'  => Consultation::where('case_type', 'livestock')->count(),
            'crop'       => Consultation::where('case_type', 'crop')->count(),
        ];

        $vets        = User::where('role', 'vet')->where('is_active', true)->where('is_verified', true)->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $agronomists = User::where('role', 'agronomist')->where('is_active', true)->where('is_verified', true)->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return view('admin.consultations.index', compact('consultations', 'stats', 'vets', 'agronomists'));
    }

    public function show(Consultation $consultation)
    {
        $consultation->load(['farmer', 'expert']);

        $experts = $consultation->case_type === 'crop'
            ? User::where('role', 'agronomist')->where('is_active', true)->where('is_verified', true)->orderBy('first_name')->get(['id', 'first_name', 'last_name'])
            : User::where('role', 'vet')->where('is_active', true)->where('is_verified', true)->orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return view('admin.consultations.show', compact('consultation', 'experts'));
    }

    public function assign(Request $request, Consultation $consultation)
    {
        $request->validate(['expert_id' => 'required|exists:users,id']);

        $expert = User::findOrFail($request->expert_id);

        $oldExpertId = $consultation->expert_id;

        $consultation->update([
            'expert_id' => $expert->id,
            'status'    => 'open',
        ]);

        // Notify the assigned expert
        Notification::create([
            'user_id' => $expert->id,
            'title'   => 'New Consultation Assigned',
            'message' => "You have been assigned a {$consultation->case_type} consultation case. Please review and respond.",
            'type'    => 'info',
            'link'    => '/vet/consultations/' . $consultation->id,
        ]);

        // Notify the farmer
        Notification::create([
            'user_id' => $consultation->farmer_id,
            'title'   => 'Expert Assigned',
            'message' => "An expert has been assigned to your consultation request. You will receive a response shortly.",
            'type'    => 'success',
            'link'    => '#',
        ]);

        AuditLog::record('consultation.assigned', 'Consultation', $consultation->id, [
            'expert_id'     => $expert->id,
            'expert_name'   => $expert->first_name . ' ' . $expert->last_name,
            'old_expert_id' => $oldExpertId,
            'assigned_by'   => auth()->id(),
        ]);

        return back()->with('success', "Consultation assigned to {$expert->first_name} {$expert->last_name}.");
    }

    public function updateStatus(Request $request, Consultation $consultation)
    {
        $request->validate(['status' => 'required|in:open,resolved,cancelled']);
        $consultation->update(['status' => $request->status]);

        AuditLog::record('consultation.status_updated', 'Consultation', $consultation->id, [
            'status'     => $request->status,
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', 'Consultation status updated.');
    }
}
