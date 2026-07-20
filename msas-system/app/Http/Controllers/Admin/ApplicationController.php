<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ApplicationApprovedMail;
use App\Mail\ApplicationRejectedMail;
use App\Models\User;
use App\Models\UserDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->get('status', 'pending');

        $applications = User::where('application_status', $status)
            ->whereNotIn('role', ['farmer', 'general-user', 'ceo', 'admin'])
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        $counts = [
            'pending'  => User::where('application_status', 'pending')->whereNotIn('role', ['farmer', 'general-user', 'ceo', 'admin'])->count(),
            'approved' => User::where('application_status', 'approved')->whereNotIn('role', ['farmer', 'general-user', 'ceo', 'admin'])->count(),
            'rejected' => User::where('application_status', 'rejected')->whereNotIn('role', ['farmer', 'general-user', 'ceo', 'admin'])->count(),
        ];

        return view('admin.applications.index', compact('applications', 'status', 'counts'));
    }

    public function show(User $user): View
    {
        $documents = $user->documents()->orderBy('created_at')->get();
        return view('admin.applications.show', compact('user', 'documents'));
    }

    public function approve(User $user): RedirectResponse
    {
        $user->update([
            'application_status' => 'approved',
            'is_active'          => true,
            'reviewed_at'        => now(),
            'reviewed_by'        => auth()->id(),
        ]);

        if ($user->email) {
            try {
                Mail::to($user->email)->send(new ApplicationApprovedMail($user));
            } catch (\Exception $e) {
                Log::error('ApplicationApprovedMail failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }
        }

        return redirect()->route('admin.applications.index')
            ->with('success', $user->name . ' has been approved and their account is now active.');
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        $user->update([
            'application_status' => 'rejected',
            'is_active'          => false,
            'rejection_reason'   => $request->reason,
            'reviewed_at'        => now(),
            'reviewed_by'        => auth()->id(),
        ]);

        if ($user->email) {
            try {
                Mail::to($user->email)->send(new ApplicationRejectedMail($user, $request->reason));
            } catch (\Exception $e) {
                Log::error('ApplicationRejectedMail failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            }
        }

        return redirect()->route('admin.applications.index')
            ->with('success', $user->name . "'s application has been rejected.");
    }

    public function document(UserDocument $document)
    {
        return response($document->content_base64 ? base64_decode($document->content_base64) : '', 200)
            ->header('Content-Type', $document->mime_type)
            ->header('Content-Disposition', 'inline; filename="' . $document->original_name . '"');
    }
}
