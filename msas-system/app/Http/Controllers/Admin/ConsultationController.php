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

        $vets        = User::whereIn('role', ['vet'])->where('is_active', true)
            ->where(fn($q) => $q->where('application_status', 'approved')->orWhereNull('application_status'))
            ->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'state', 'is_verified']);
        $agronomists = User::whereIn('role', ['agronomist', 'extension-officer'])->where('is_active', true)
            ->where(fn($q) => $q->where('application_status', 'approved')->orWhereNull('application_status'))
            ->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'role', 'state', 'is_verified']);

        return view('admin.consultations.index', compact('consultations', 'stats', 'vets', 'agronomists'));
    }

    public function show(Consultation $consultation)
    {
        $consultation->load(['farmer', 'expert']);

        $cropRoles      = ['agronomist', 'extension-officer', 'research-institution'];
        $livestockRoles = ['vet'];
        $roles  = $consultation->case_type === 'crop' ? $cropRoles : $livestockRoles;
        $experts = User::whereIn('role', $roles)->where('is_active', true)
            ->where(fn($q) => $q->where('application_status', 'approved')->orWhereNull('application_status'))
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name', 'role', 'state', 'is_verified']);

        return view('admin.consultations.show', compact('consultation', 'experts'));
    }

    public function assign(Request $request, Consultation $consultation)
    {
        $request->validate(['expert_id' => 'required|exists:users,id']);

        $expert = User::findOrFail($request->expert_id);

        $oldExpertId = $consultation->expert_id;

        $consultation->update([
            'expert_id'          => $expert->id,
            'status'             => 'open',
            'consultant_status'  => 'pending_acceptance',
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

    // Called by expert (vet/agronomist) to accept their assigned consultation
    public function expertAccept(Consultation $consultation)
    {
        abort_if($consultation->expert_id !== auth()->id(), 403);

        $consultation->update([
            'consultant_status'      => 'accepted',
            'consultant_accepted_at' => now(),
        ]);

        // Notify admin + farmer
        foreach (User::whereIn('role', ['admin', 'ceo'])->pluck('id') as $id) {
            Notification::create([
                'user_id' => $id,
                'title'   => 'Consultation Accepted by Expert',
                'message' => "{$consultation->expert->first_name} {$consultation->expert->last_name} accepted consultation #{$consultation->id}.",
                'type'    => 'success',
                'link'    => '/admin/consultations/' . $consultation->id,
            ]);
        }
        Notification::create([
            'user_id' => $consultation->farmer_id,
            'title'   => 'Expert Accepted Your Case',
            'message' => "Your consultation has been accepted by {$consultation->expert->first_name} {$consultation->expert->last_name}. They will respond shortly.",
            'type'    => 'success',
            'link'    => '#',
        ]);

        return back()->with('success', 'You have accepted this consultation.');
    }

    // Called by expert to decline — returns case to unassigned
    public function expertDecline(Request $request, Consultation $consultation)
    {
        abort_if($consultation->expert_id !== auth()->id(), 403);

        $reason = $request->input('reason', 'Declined by expert');

        $consultation->update([
            'expert_id'                  => null,
            'consultant_status'          => null,
            'consultant_decline_reason'  => $reason,
        ]);

        foreach (User::whereIn('role', ['admin', 'ceo'])->pluck('id') as $id) {
            Notification::create([
                'user_id' => $id,
                'title'   => 'Consultation Declined — Needs Reassignment',
                'message' => "Consultation #{$consultation->id} was declined. Reason: {$reason}. Please reassign.",
                'type'    => 'warning',
                'link'    => '/admin/consultations/' . $consultation->id,
            ]);
        }

        AuditLog::record('consultation.expert_declined', 'Consultation', $consultation->id, [
            'reason' => $reason,
            'by'     => auth()->id(),
        ]);

        return back()->with('info', 'Consultation declined. Admin has been notified.');
    }
}
