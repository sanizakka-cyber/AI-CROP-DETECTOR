<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with('user')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($u) => $u->where('email', 'like', "%{$search}%"));
            });
        }

        $payments = $query->paginate(30)->withQueryString();
        $modules  = Payment::select('module')->distinct()->pluck('module');

        $stats = [
            'total_revenue' => Payment::successful()->sum('amount'),
            'total_count'   => Payment::successful()->count(),
            'month_revenue' => Payment::successful()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->sum('amount'),
            'month_count'   => Payment::successful()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'pending_count' => Payment::pending()->count(),
            'failed_count'  => Payment::failed()->count(),
        ];

        $byModule = Payment::successful()
            ->select('module', DB::raw('SUM(amount) as total'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('module')
            ->orderByDesc('total')
            ->get();

        return view('admin.payments.index', compact('payments', 'modules', 'stats', 'byModule'));
    }
}
