<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\UserDocument;
use App\Rules\NotEmptyFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

/**
 * Public, signed-URL-only entry point for an applicant to replace a
 * rejected/resubmission-required document, without logging in --
 * pending/rejected applicants cannot authenticate at all (see
 * AuthenticatedSessionController), and that block is an intentional,
 * already-audited security property this controller does not touch.
 * Access is solely the possession of the exact signed link emailed by
 * DocumentResubmissionRequiredMail (routes/auth.php-style 'signed'
 * middleware, matching the existing verification.link/staff.first-login
 * pattern).
 */
class DocumentResubmissionController extends Controller
{
    /** Single route (GET shows the form, POST submits it) so the one signed URL mailed to the applicant works for both. */
    public function handle(Request $request, UserDocument $document): View|RedirectResponse
    {
        return $request->isMethod('post') ? $this->submit($request, $document) : $this->show($request, $document);
    }

    private function show(Request $request, UserDocument $document): View|RedirectResponse
    {
        if (! $this->canStillResubmit($document)) {
            return $this->alreadyHandled($document);
        }

        return view('documents.resubmit', ['document' => $document]);
    }

    private function submit(Request $request, UserDocument $document): View|RedirectResponse
    {
        if (! $this->canStillResubmit($document)) {
            return $this->alreadyHandled($document);
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', new NotEmptyFile(), 'max:5120'],
        ]);

        $file = $request->file('file');
        $contents = @file_get_contents($file->getRealPath());
        if ($contents === false || $contents === '') {
            Log::error('Document resubmission: failed to read file contents', ['document_id' => $document->id]);
            return back()->withErrors(['file' => 'We could not read that file. Please try a different one.']);
        }

        // Lock the old document row for the duration of the check+create so
        // two near-simultaneous submits via the same link (e.g. a double
        // click) can't both pass canStillResubmit() and create two
        // replacements -- the second request blocks until the first
        // transaction commits, then sees the replacement that now exists.
        $replacement = DB::transaction(function () use ($document, $file, $contents) {
            $locked = UserDocument::whereKey($document->id)->lockForUpdate()->first();
            if (! $this->canStillResubmit($locked)) {
                return null;
            }

            $new = UserDocument::create([
                'user_id'              => $locked->user_id,
                'document_type'        => $locked->document_type,
                'document_label'       => $locked->document_label,
                'original_name'        => $file->getClientOriginalName(),
                'mime_type'            => $file->getMimeType(),
                'file_size'            => $file->getSize(),
                'content_base64'       => base64_encode($contents),
                'status'               => UserDocument::STATUS_PENDING_REVIEW,
                'replaces_document_id' => $locked->id,
            ]);

            AuditLog::record('document.resubmitted', 'UserDocument', $new->id, [
                'replaces_document_id' => $locked->id,
                'document_type'        => $locked->document_type,
                'user_id'              => $locked->user_id,
            ]);

            return $new;
        });

        if ($replacement === null) {
            return $this->alreadyHandled($document);
        }

        return view('documents.resubmit-success', ['document' => $replacement]);
    }

    /**
     * A replacement was already uploaded (e.g. the applicant used this
     * same link twice), or an admin has since changed the status another
     * way -- either case means this specific link's job is already done,
     * so re-using it must not create a second, duplicate replacement.
     */
    private function canStillResubmit(UserDocument $document): bool
    {
        return $document->isAwaitingResubmission()
            && ! UserDocument::where('replaces_document_id', $document->id)->exists();
    }

    /** The link was valid but this document is no longer waiting on a resubmission. */
    private function alreadyHandled(UserDocument $document): View
    {
        return view('documents.resubmit-closed', ['document' => $document]);
    }
}
