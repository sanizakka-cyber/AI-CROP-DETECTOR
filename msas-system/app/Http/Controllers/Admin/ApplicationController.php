<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ApplicationApprovedMail;
use App\Mail\ApplicationRejectedMail;
use App\Mail\DocumentResubmissionRequiredMail;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\UserDocument;
use App\Services\RegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
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
        $missingDocuments = $this->unapprovedRequiredDocuments($user, $documents);
        return view('admin.applications.show', compact('user', 'documents', 'missingDocuments'));
    }

    /**
     * For each document type this role is required to submit
     * (RegistrationService's shared registry), look at only the LATEST
     * version on file -- a document can be uploaded, rejected, and
     * replaced more than once, and every prior attempt is kept as history
     * rather than overwritten, so "has this been uploaded" must mean "does
     * the newest row for this type exist and carry status=approved", not
     * merely "does any row of this type exist" (which an old rejected
     * upload would satisfy forever even after a better one replaces it).
     * Returns labels, not raw document_type keys, for display.
     */
    private function unapprovedRequiredDocuments(User $user, $documents): array
    {
        $required = RegistrationService::requiredDocumentLabels($user->role);
        $latestByType = $documents->sortBy('created_at')->groupBy('document_type')
            ->map(fn ($group) => $group->last());

        $unapproved = [];
        foreach ($required as $type => $label) {
            $latest = $latestByType->get($type);
            if (! $latest || $latest->status !== UserDocument::STATUS_APPROVED) {
                $unapproved[] = ['type' => $type, 'label' => $label, 'neverUploaded' => ! $latest];
            }
        }
        return $unapproved;
    }

    /** @return string[] Labels only, for the approve() block message. */
    private function unapprovedRequiredDocumentLabels(User $user, $documents): array
    {
        return array_column($this->unapprovedRequiredDocuments($user, $documents), 'label');
    }

    public function approve(User $user): RedirectResponse
    {
        // Backend enforcement, not just a disabled frontend button: a
        // regulated role (anything with a non-empty requiredDocumentLabels
        // entry) cannot be approved while any required document is
        // missing or not yet individually approved, no matter how the
        // POST /approve request is made. Nothing previously checked this
        // at all -- any pending applicant, documents or not, could be
        // approved.
        $documents = $user->documents()->get();
        $missing = $this->unapprovedRequiredDocumentLabels($user, $documents);
        if (! empty($missing)) {
            return redirect()->route('admin.applications.show', $user)
                ->with('error', $user->name . ' cannot be approved yet — these document(s) are missing or not yet approved: ' . implode(', ', $missing) . '.');
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

    /**
     * For a required document type the applicant never uploaded at all
     * (as opposed to one that was uploaded and then rejected) -- the
     * registration form itself tells applicants "you can still submit
     * without documents, they can be added later" (register.blade.php),
     * but until this there was no actual "later" path for a type with no
     * row to reject in the first place. Creates an empty placeholder in
     * the same 'resubmission_required' state a real rejection would leave
     * behind, so the existing signed-link upload flow handles both cases
     * identically with no separate code path.
     */
    public function requestDocument(Request $request, User $user): RedirectResponse
    {
        $request->validate(['document_type' => 'required|string']);
        $required = RegistrationService::requiredDocumentLabels($user->role);
        $type = $request->input('document_type');
        if (! array_key_exists($type, $required)) {
            return back()->with('error', 'That is not a recognized document requirement for this role.');
        }

        $placeholder = UserDocument::create([
            'user_id'        => $user->id,
            'document_type'  => $type,
            'document_label' => $required[$type],
            'original_name'  => '(not yet uploaded)',
            'mime_type'      => '',
            'file_size'      => 0,
            'content_base64' => '',
            'status'         => UserDocument::STATUS_RESUBMISSION_REQUIRED,
            'review_reason'  => 'This document was not submitted with the original application and is required for approval.',
        ]);
        AuditLog::record('document.requested', 'UserDocument', $placeholder->id, [
            'user_id' => $user->id, 'document_type' => $type,
        ]);

        $notice = $required[$type] . ' requested.';
        if ($user->email) {
            $resubmitUrl = URL::temporarySignedRoute('documents.resubmit', now()->addDays(7), ['document' => $placeholder->id]);
            try {
                Mail::to($user->email)->send(new DocumentResubmissionRequiredMail($placeholder, $resubmitUrl));
            } catch (\Exception $e) {
                Log::error('DocumentResubmissionRequiredMail failed', ['document_id' => $placeholder->id, 'error' => $e->getMessage()]);
                $notice .= ' Could not email the upload link — notify the applicant another way.';
            }
        } else {
            $notice .= ' This applicant has no email on file, so no upload link could be sent — contact them directly.';
        }

        return back()->with('success', $notice);
    }

    public function approveDocument(UserDocument $document): RedirectResponse
    {
        $updated = DB::transaction(function () use ($document) {
            $locked = UserDocument::whereKey($document->id)->lockForUpdate()->first();
            if (! $locked->canTransitionTo(UserDocument::STATUS_APPROVED)) {
                return false;
            }
            $locked->update([
                'status'        => UserDocument::STATUS_APPROVED,
                'reviewed_at'   => now(),
                'reviewed_by'   => auth()->id(),
                'review_reason' => null,
            ]);
            AuditLog::record('document.approved', 'UserDocument', $locked->id, [
                'user_id' => $locked->user_id, 'document_type' => $locked->document_type,
            ]);
            return true;
        });

        if (! $updated) {
            return back()->with('error', 'This document was already reviewed (possibly by another admin) and is no longer awaiting review.');
        }

        return back()->with('success', $document->document_label . ' approved.');
    }

    public function rejectDocument(Request $request, UserDocument $document): RedirectResponse
    {
        $request->validate([
            'reason'        => 'required|string|max:1000',
            'new_status'    => 'required|in:' . UserDocument::STATUS_REJECTED . ',' . UserDocument::STATUS_RESUBMISSION_REQUIRED,
        ]);

        $newStatus = $request->input('new_status');

        $updated = DB::transaction(function () use ($document, $newStatus, $request) {
            $locked = UserDocument::whereKey($document->id)->lockForUpdate()->first();
            if (! $locked->canTransitionTo($newStatus)) {
                return null;
            }
            $locked->update([
                'status'        => $newStatus,
                'reviewed_at'   => now(),
                'reviewed_by'   => auth()->id(),
                'review_reason' => $request->reason,
            ]);
            AuditLog::record('document.' . $newStatus, 'UserDocument', $locked->id, [
                'user_id' => $locked->user_id, 'document_type' => $locked->document_type, 'reason' => $request->reason,
            ]);
            return $locked;
        });

        if (! $updated) {
            return back()->with('error', 'This document was already reviewed (possibly by another admin) and is no longer awaiting review.');
        }

        $applicant = $updated->user;
        $notice = $document->document_label . ' marked as ' . str_replace('_', ' ', $newStatus) . '.';

        if ($applicant && $applicant->email) {
            $resubmitUrl = URL::temporarySignedRoute('documents.resubmit', now()->addDays(7), ['document' => $updated->id]);
            try {
                Mail::to($applicant->email)->send(new DocumentResubmissionRequiredMail($updated, $resubmitUrl));
            } catch (\Exception $e) {
                Log::error('DocumentResubmissionRequiredMail failed', ['document_id' => $updated->id, 'error' => $e->getMessage()]);
                $notice .= ' Could not email the resubmission link — notify the applicant another way.';
            }
        } else {
            // Phone-only applicants have no channel this link can be sent
            // through today (no SMS provider configured in production,
            // per OtpService) -- flagging this to the admin rather than
            // silently doing nothing is the honest behavior.
            $notice .= ' This applicant has no email on file, so no resubmission link could be sent — contact them directly.';
        }

        return back()->with('success', $notice);
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
