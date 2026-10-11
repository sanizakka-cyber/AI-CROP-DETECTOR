<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ApplicationApprovedMail;
use App\Mail\ApplicationRejectedMail;
use App\Models\User;
use App\Models\UserDocument;
use App\Services\RegistrationService;
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
        $missingDocuments = $this->missingRequiredDocuments($user, $documents);
        return view('admin.applications.show', compact('user', 'documents', 'missingDocuments'));
    }

    /**
     * Required documents this role must submit (RegistrationService's
     * shared registry) that have no corresponding uploaded UserDocument.
     * Returns labels, not raw document_type keys, for display.
     */
    private function missingRequiredDocuments(User $user, $documents): array
    {
        $required = RegistrationService::requiredDocumentLabels($user->role);
        $uploadedTypes = $documents->pluck('document_type')->all();
        $missing = [];
        foreach ($required as $type => $label) {
            if (! in_array($type, $uploadedTypes, true)) {
                $missing[] = $label;
            }
        }
        return $missing;
    }

    public function approve(User $user): RedirectResponse
    {
        // Backend enforcement, not just a disabled frontend button: a
        // regulated role (anything with a non-empty requiredDocumentLabels
        // entry) cannot be approved while any required document is
        // missing, no matter how the POST /approve request is made.
        // Nothing previously checked this at all -- any pending applicant,
        // documents or not, could be approved.
        $documents = $user->documents()->get();
        $missing = $this->missingRequiredDocuments($user, $documents);
        if (! empty($missing)) {
            return redirect()->route('admin.applications.show', $user)
                ->with('error', $user->name . ' cannot be approved yet — missing required document(s): ' . implode(', ', $missing) . '.');
        }

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
        // Previously this fell through to a 200 OK with an empty body
        // whenever content_base64 was empty (e.g. the uploaded file
        // failed to read at registration time) -- the browser's native
        // PDF/image viewer then shows a generic "failed to load" error
        // for what looks, from the server's side, like a successful
        // response. Serve an honest, visible error instead of a silently
        // empty "success" -- abort()'s message is suppressed by Laravel's
        // default error view whenever APP_DEBUG is off (i.e. always in
        // production), so render it directly rather than relying on that.
        if (empty($document->content_base64)) {
            return $this->documentErrorResponse(
                'This document was not stored correctly and cannot be displayed. The applicant may need to re-upload it.'
            );
        }

        $decoded = base64_decode($document->content_base64, true);
        if ($decoded === false || $decoded === '') {
            return $this->documentErrorResponse(
                'This document is corrupted and cannot be displayed. The applicant may need to re-upload it.'
            );
        }

        return response($decoded, 200)
            ->header('Content-Type', $document->mime_type ?: 'application/octet-stream')
            ->header('Content-Disposition', 'inline; filename="' . preg_replace('/[\r\n"\\\\]/', '_', basename($document->original_name)) . '"');
    }

    private function documentErrorResponse(string $message)
    {
        return response(
            '<!doctype html><html><head><title>Document unavailable</title></head><body style="font-family:sans-serif;padding:3rem;text-align:center;color:#334155;">'
            . '<h2>Document unavailable</h2><p>' . e($message) . '</p></body></html>',
            404
        )->header('Content-Type', 'text/html');
    }
}
