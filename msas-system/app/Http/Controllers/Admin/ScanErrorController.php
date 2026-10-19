<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScanError;
use App\Support\Scan\ScanErrorCode;
use Illuminate\Http\Request;

/**
 * /admin/scan-errors: every scan that could not complete, with its code,
 * user, time, reference and (masked) technical detail; filter by code or
 * reference; failures per hour for the last 24 hours.
 */
class ScanErrorController extends Controller
{
    public function index(Request $request)
    {
        $q = ScanError::with('user:id,first_name,last_name,email,role')->latest('created_at')->latest('id');
        if ($request->filled('code') && in_array($request->code, ScanErrorCode::all(), true)) {
            $q->where('code', $request->code);
        }
        if ($request->filled('reference')) {
            $q->where('reference', strtoupper(trim($request->reference)));
        }
        if ($request->filled('channel') && in_array($request->channel, ['web', 'api'], true)) {
            $q->where('channel', $request->channel);
        }

        $since = now()->subHours(23)->startOfHour();
        $perHour = [];
        for ($h = 0; $h < 24; $h++) {
            $perHour[$since->copy()->addHours($h)->format('Y-m-d H:00')] = 0;
        }
        ScanError::where('created_at', '>=', $since)
            ->when($request->filled('code'), fn ($qq) => $qq->where('code', $request->code))
            ->get(['created_at'])
            ->each(function ($e) use (&$perHour) {
                $key = $e->created_at->copy()->startOfHour()->format('Y-m-d H:00');
                if (isset($perHour[$key])) {
                    $perHour[$key]++;
                }
            });

        $totals = ScanError::where('created_at', '>=', now()->subDay())
            ->selectRaw('code, count(*) as n')->groupBy('code')->orderByDesc('n')->pluck('n', 'code');

        return view('admin.scan-errors.index', [
            'scanErrors' => $q->paginate(50)->withQueryString(),
            'codes'    => ScanErrorCode::all(),
            'perHour'  => $perHour,
            'totals'   => $totals,
            'services' => ScanErrorCode::SERVICE_LABELS,
        ]);
    }
}
