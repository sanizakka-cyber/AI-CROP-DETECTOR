<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Animal;
use App\Models\Finance;
use App\Models\Consultation;
use App\Models\MarketplaceItem;
use App\Models\EggProduction;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CEOController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'role:ceo,admin']);
    }

    public function index()
    {
        // ── KPI Metrics ────────────────────────────────────────────
        $totalUsers       = User::count();
        $activeUsers      = User::where('is_active', true)->count();
        $pendingExperts   = User::whereIn('role', ['vet','agronomist'])->where('is_verified', false)->count();
        $totalAnimals     = Animal::count();
        $totalDiagnoses   = Consultation::count();
        $pendingConsults  = Consultation::where('status','pending')->count();

        // ── Revenue ────────────────────────────────────────────────
        $totalRevenue     = Finance::where('type','Income')->sum('amount');
        $totalExpenses    = Finance::where('type','Expense')->sum('amount');
        $netProfit        = $totalRevenue - $totalExpenses;
        $thisMonthRevenue = Finance::where('type','Income')
                              ->whereMonth('transaction_date', now()->month)
                              ->sum('amount');
        $lastMonthRevenue = Finance::where('type','Income')
                              ->whereMonth('transaction_date', now()->subMonth()->month)
                              ->sum('amount');
        $revenueGrowth    = $lastMonthRevenue > 0
                              ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
                              : 0;

        // ── Users by Role ──────────────────────────────────────────
        $usersByRole = User::select('role', DB::raw('count(*) as count'))
                          ->groupBy('role')
                          ->pluck('count', 'role');

        // ── Monthly Revenue Chart (last 6 months) ──────────────────
        $revenueChart = collect(range(5, 0))->map(function ($i) {
            $month = now()->subMonths($i);
            return [
                'month'   => $month->format('M'),
                'income'  => Finance::where('type','Income')->whereMonth('transaction_date', $month->month)->whereYear('transaction_date', $month->year)->sum('amount'),
                'expense' => Finance::where('type','Expense')->whereMonth('transaction_date', $month->month)->whereYear('transaction_date', $month->year)->sum('amount'),
            ];
        });

        // ── Recent User Activity ────────────────────────────────────
        $recentUsers = User::latest()->take(8)->get();

        // ── Attendance Today ────────────────────────────────────────
        $presentToday = Attendance::whereDate('date', today())->where('status','present')->count();
        $staffCount   = User::whereNotIn('role', ['farmer','agro-dealer'])->count();

        // ── Pending Leave Requests ──────────────────────────────────
        $pendingLeaves = LeaveRequest::where('status','pending')->count();

        // ── Marketplace Stats ───────────────────────────────────────
        $marketItems    = MarketplaceItem::where('status','active')->count();
        $pendingListings = MarketplaceItem::where('is_approved', false)->count();

        // ── Disease Alerts (Stub) ───────────────────────────────────
        $diseaseAlerts = [
            ['disease' => 'Fall Armyworm',       'region' => 'Katsina North',    'severity' => 'high',   'crop'   => 'Maize'],
            ['disease' => 'Foot & Mouth Disease', 'region' => 'Katsina Central', 'severity' => 'medium', 'animal' => 'Cattle'],
        ];

        return view('ceo.dashboard', compact(
            'totalUsers','activeUsers','pendingExperts',
            'totalAnimals','totalDiagnoses','pendingConsults',
            'totalRevenue','totalExpenses','netProfit',
            'thisMonthRevenue','revenueGrowth',
            'usersByRole','revenueChart','recentUsers',
            'presentToday','staffCount','pendingLeaves',
            'marketItems','pendingListings','diseaseAlerts'
        ));
    }

    // ── User Management ────────────────────────────────────────────
    public function users()
    {
        $users = User::latest()->paginate(20);
        return view('ceo.users', compact('users'));
    }

    public function toggleUser(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);
        return back()->with('success', 'User status updated.');
    }

    public function approveExpert(User $user)
    {
        $user->update(['is_verified' => true]);
        // TODO: Send notification
        return back()->with('success', "{$user->name} approved as {$user->role}.");
    }

    // ── Reports ────────────────────────────────────────────────────
    public function reports()
    {
        return view('ceo.reports');
    }

    public function generateReport($type)
    {
        $data = match($type) {
            'financial' => [
                'title'    => 'Financial Summary Report',
                'income'   => Finance::where('type','Income')->sum('amount'),
                'expenses' => Finance::where('type','Expense')->sum('amount'),
                'records'  => Finance::latest()->take(50)->get(),
            ],
            'users' => [
                'title'   => 'User Report',
                'records' => User::latest()->get(),
            ],
            'animals' => [
                'title'   => 'Livestock Report',
                'records' => Animal::with('user')->latest()->get(),
            ],
            default => abort(404),
        };

        return view('ceo.report-preview', compact('data','type'));
    }
}
