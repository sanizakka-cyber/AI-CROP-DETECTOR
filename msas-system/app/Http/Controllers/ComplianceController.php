<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ComplianceController extends Controller
{
    // ── Audit Log Viewer (CEO/Admin) ──────────────────────────────────────────

    public function auditLog(Request $request)
    {
        $query = AuditLog::with('user:id,first_name,last_name,role')->latest();

        if ($request->filled('action')) {
            $query->where('action', 'like', '%'.$request->action.'%');
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $logs = $query->paginate(50)->withQueryString();

        $actionGroups = AuditLog::select('action')
            ->distinct()->orderBy('action')->pluck('action');

        return view('ceo.audit-log', compact('logs', 'actionGroups'));
    }

    // ── NDPR Data Export (farmer self-serve) ─────────────────────────────────

    public function requestExport(Request $request)
    {
        $user = auth()->user();

        // Rate-limit: one export request per 30 days
        if ($user->data_export_requested_at &&
            $user->data_export_requested_at->gt(now()->subDays(30))) {
            return back()->with('error', 'You can only request a data export once every 30 days.');
        }

        $user->update([
            'data_export_requested_at' => now(),
            'data_export_completed_at' => null,
        ]);

        // Queue the job
        \App\Jobs\ExportUserDataJob::dispatch($user->id);

        AuditLog::record('compliance.data_export_requested', 'User', $user->id);

        return back()->with('success', 'Your data export has been queued. You will receive a download link within 15 minutes.');
    }

    // ── Public legal pages (no auth required) ────────────────────────────────

    public function publicPrivacy()
    {
        return view('legal.privacy');
    }

    public function terms()
    {
        return view('legal.terms');
    }

    public function refund()
    {
        return view('legal.refund');
    }

    public function faq()
    {
        return view('legal.faq');
    }

    public function help()
    {
        return view('legal.help');
    }

    // ── Privacy & Consent page (authenticated farmer) ─────────────────────────

    public function privacy()
    {
        return view('farmer.privacy');
    }

    public function recordConsent(Request $request)
    {
        $user = auth()->user();
        if (!$user->consent_given_at) {
            $user->update(['consent_given_at' => now()]);
            AuditLog::record('compliance.consent_given', 'User', $user->id, ['ip' => $request->ip()]);
        }
        return response()->json(['ok' => true]);
    }

    // ── Account Deletion Request ──────────────────────────────────────────────

    public function requestDeletion(Request $request)
    {
        $request->validate(['confirm' => 'required|in:DELETE']);
        $user = auth()->user();

        AuditLog::record('compliance.deletion_requested', 'User', $user->id, [
            'email' => $user->email,
            'role'  => $user->role,
        ]);

        // Mark account for deletion review — CEO must approve
        $user->update(['is_active' => false]);

        Log::warning('Account deletion requested', ['user_id' => $user->id, 'email' => $user->email]);

        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('info', 'Your account deletion request has been received. Your account has been deactivated and will be permanently deleted within 30 days per our data retention policy.');
    }
}