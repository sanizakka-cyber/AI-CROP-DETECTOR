<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Animal;
use App\Models\Finance;
use App\Models\Consultation;
use App\Models\Product;
use App\Models\Diagnosis;
use App\Models\EggProduction;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CEOController extends Controller
{
    public function index()
    {
        // ── KPI Metrics ────────────────────────────────────────────
        $totalUsers      = User::count();
        $activeUsers     = User::where('is_active', true)->count();
        $pendingExperts  = User::whereIn('role', ['vet','agronomist'])->where('is_verified', false)->count();
        try {
            $totalAnimals = Animal::count();
        } catch (\Exception $e) { $totalAnimals = 0; }
        try {
            $totalDiagnoses  = Consultation::count();
            $pendingConsults = Consultation::where('status','pending')->count();
        } catch (\Exception $e) { $totalDiagnoses = 0; $pendingConsults = 0; }

        // ── Revenue ────────────────────────────────────────────────
        try {
            $totalRevenue     = Finance::where('type','Income')->sum('amount');
            $totalExpenses    = Finance::where('type','Expense')->sum('amount');
            $thisMonthRevenue = Finance::where('type','Income')
                                  ->whereMonth('transaction_date', now()->month)
                                  ->sum('amount');
            $lastMonthRevenue = Finance::where('type','Income')
                                  ->whereMonth('transaction_date', now()->subMonth()->month)
                                  ->sum('amount');
        } catch (\Exception $e) {
            $totalRevenue = $totalExpenses = $thisMonthRevenue = $lastMonthRevenue = 0;
        }
        $netProfit     = $totalRevenue - $totalExpenses;
        $revenueGrowth = $lastMonthRevenue > 0
                           ? round((($thisMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
                           : 0;

        // ── Users by Role ──────────────────────────────────────────
        $usersByRole = User::select('role', DB::raw('count(*) as count'))
                          ->groupBy('role')
                          ->pluck('count', 'role');

        // ── Monthly Revenue Chart (last 6 months) — cached 5 min ──
        try {
            $revenueChart = Cache::remember('ceo:revenue_chart', 300, function () {
                return collect(range(5, 0))->map(function ($i) {
                    $month = now()->subMonths($i);
                    $rows  = Finance::selectRaw("type, SUM(amount) as total")
                        ->whereMonth('transaction_date', $month->month)
                        ->whereYear('transaction_date', $month->year)
                        ->groupBy('type')
                        ->pluck('total', 'type');
                    return [
                        'month'   => $month->format('M'),
                        'income'  => $rows['Income']  ?? 0,
                        'expense' => $rows['Expense'] ?? 0,
                    ];
                });
            });
        } catch (\Exception $e) { $revenueChart = collect(); }

        // ── Monthly User Growth (last 6 months) — cached 5 min ────
        $monthlyGrowth = Cache::remember('ceo:monthly_growth', 300, function () {
            return collect(range(5, 0))->map(function ($i) {
                $month = now()->subMonths($i);
                $rows  = User::selectRaw("role, COUNT(*) as count")
                    ->whereMonth('created_at', $month->month)
                    ->whereYear('created_at', $month->year)
                    ->groupBy('role')
                    ->pluck('count', 'role');
                return [
                    'label'   => $month->format('M'),
                    'farmers' => $rows['farmer'] ?? 0,
                    'experts' => ($rows['vet'] ?? 0) + ($rows['agronomist'] ?? 0),
                    'total'   => $rows->sum(),
                ];
            });
        });

        // ── Diagnosis Type Split ────────────────────────────────────
        try {
            $cropDiagnoses      = Consultation::where('case_type','crop')->count();
            $livestockDiagnoses = Consultation::where('case_type','livestock')->count();
        } catch (\Exception $e) {
            $cropDiagnoses = 0; $livestockDiagnoses = 0;
        }

        // ── State Activity ──────────────────────────────────────────
        try {
            $stateActivity = User::select('state', DB::raw('count(*) as count'))
                ->whereNotNull('state')->groupBy('state')
                ->orderByDesc('count')->take(6)->pluck('count','state')->toArray();
        } catch (\Exception $e) { $stateActivity = []; }

        // ── Platform Health Score (composite) ──────────────────────
        try {
            $resolvedCases = Consultation::where('status','resolved')->count();
        } catch (\Exception $e) { $resolvedCases = 0; }
        $resolutionRate = $totalDiagnoses > 0 ? round(($resolvedCases / $totalDiagnoses) * 100) : 0;
        $activePct        = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100) : 0;
        $platformHealth   = (int) round(($resolutionRate * 0.4) + ($activePct * 0.4) + 20);
        $platformHealth   = min(100, max(0, $platformHealth));

        // ── Recent User Activity ────────────────────────────────────
        $recentUsers = User::latest()->take(8)->get();

        // ── Attendance Today ────────────────────────────────────────
        try {
            $presentToday = Attendance::whereDate('date', today())->where('status','present')->count();
            $staffCount   = User::whereNotIn('role', ['farmer','agro-dealer'])->count();
        } catch (\Exception $e) { $presentToday = 0; $staffCount = 0; }

        // ── Pending Leave Requests ──────────────────────────────────
        try {
            $pendingLeaves = LeaveRequest::where('status','pending')->count();
        } catch (\Exception $e) { $pendingLeaves = 0; }

        // ── Marketplace Stats ───────────────────────────────────────
        try {
            $marketItems     = Product::where('status','active')->where('is_approved', true)->count();
            $pendingListings = Product::where('is_approved', false)->count();
        } catch (\Exception $e) { $marketItems = 0; $pendingListings = 0; }

        // ── Disease Alerts (live — top diseases needing review, last 30 days) ──
        try {
            $diseaseAlerts = Diagnosis::select('disease_name', 'type', DB::raw('count(*) as cases'))
                ->where('created_at', '>=', now()->subDays(30))
                ->whereIn('status', ['pending','reviewed'])
                ->whereNotNull('disease_name')
                ->where('disease_name', '!=', 'Pending Expert Review')
                ->groupBy('disease_name', 'type')
                ->orderByDesc('cases')
                ->take(5)
                ->get()
                ->map(fn($d) => [
                    'disease'  => $d->disease_name,
                    'cases'    => $d->cases,
                    'severity' => $d->cases >= 5 ? 'high' : ($d->cases >= 2 ? 'medium' : 'low'),
                    'type'     => $d->type,
                ])
                ->toArray();
        } catch (\Exception $e) { $diseaseAlerts = []; }

        return view('ceo.dashboard', compact(
            'totalUsers','activeUsers','pendingExperts',
            'totalAnimals','totalDiagnoses','pendingConsults',
            'totalRevenue','totalExpenses','netProfit',
            'thisMonthRevenue','revenueGrowth',
            'usersByRole','revenueChart','recentUsers',
            'presentToday','staffCount','pendingLeaves',
            'marketItems','pendingListings','diseaseAlerts',
            'monthlyGrowth','cropDiagnoses','livestockDiagnoses',
            'stateActivity','platformHealth','resolutionRate','activePct'
        ));
    }

    // ── User Management ────────────────────────────────────────────
    public function users()
    {
        $query = User::latest();

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name',  'ilike', "%{$search}%")
                  ->orWhere('last_name',  'ilike', "%{$search}%")
                  ->orWhere('email',      'ilike', "%{$search}%")
                  ->orWhere('phone',      'ilike', "%{$search}%");
            });
        }
        if ($role = request('role')) {
            $query->where('role', $role);
        }
        if (request('status') !== null && request('status') !== '') {
            $query->where('is_active', (bool) request('status'));
        }

        $users = $query->paginate(20)->withQueryString();
        $roles = User::select('role')->distinct()->orderBy('role')->pluck('role');

        return view('ceo.users', compact('users', 'roles'));
    }

    public function showUser(User $user)
    {
        return view('ceo.users.show', compact('user'));
    }

    public function editUser(User $user)
    {
        $allRoles = [
            'farmer','vet','agronomist','agro-dealer','equipment-dealer','logistics-provider',
            'agribusiness-owner','input-supplier','cooperative','government-agency','ngo',
            'research-institution','investor','extension-officer','field-officer',
            'data-analyst','m-e-officer','customer-support','hr','finance','operations',
            'general-user','admin','ceo',
        ];
        return view('ceo.users.edit', compact('user', 'allRoles'));
    }

    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'first_name'         => 'required|string|max:100',
            'last_name'          => 'required|string|max:100',
            'middle_name'        => 'nullable|string|max:100',
            'email'              => 'required|email|max:200|unique:users,email,'.$user->id,
            'phone'              => 'nullable|string|max:30',
            'role'               => 'required|string',
            'state'              => 'nullable|string|max:100',
            'lga'                => 'nullable|string|max:100',
            'is_active'          => 'boolean',
            'is_verified'        => 'boolean',
            'application_status' => 'nullable|in:pending,approved,rejected',
        ]);

        // Prevent demoting the only CEO
        if ($user->role === 'ceo' && $request->role !== 'ceo') {
            $ceoCount = User::where('role', 'ceo')->count();
            if ($ceoCount <= 1) {
                return back()->with('error', 'Cannot change role: this is the only CEO account.');
            }
        }

        $user->update($request->only([
            'first_name','last_name','middle_name','email','phone',
            'role','state','lga','is_active','is_verified','application_status',
        ]));

        return redirect()->route('ceo.users.show', $user)
            ->with('success', "Profile for {$user->name} updated successfully.");
    }

    public function toggleUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot suspend your own account.');
        }
        $user->update(['is_active' => !$user->is_active]);
        $state = $user->is_active ? 'activated' : 'suspended';
        return back()->with('success', "{$user->name} has been {$state}.");
    }

    public function deleteUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        if ($user->role === 'ceo') {
            $ceoCount = User::where('role', 'ceo')->count();
            if ($ceoCount <= 1) {
                return back()->with('error', 'Cannot delete the only CEO account.');
            }
        }
        $name = $user->name;
        $user->delete();
        return redirect()->route('ceo.users')->with('success', "User \"{$name}\" has been permanently deleted.");
    }

    public function approveExpert(User $user)
    {
        $user->update(['is_verified' => true]);
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
                'columns'  => ['Description', 'Type', 'Amount', 'Date'],
                'income'   => Finance::where('type','Income')->sum('amount'),
                'expenses' => Finance::where('type','Expense')->sum('amount'),
                'records'  => Finance::latest()->take(50)->get(),
                'row_keys' => ['description', 'type', 'amount', 'transaction_date'],
            ],
            'users' => [
                'title'   => 'User Activity Report',
                'columns' => ['Name', 'Role', 'Email', 'State', 'Active', 'Joined'],
                'records' => User::latest()->get(),
                'row_keys'=> ['name', 'role', 'email', 'state', 'is_active', 'created_at'],
            ],
            'farmers' => [
                'title'   => 'Farmer Registration Report',
                'columns' => ['Name', 'Email', 'State', 'LGA', 'Phone', 'Verified', 'Joined'],
                'records' => User::where('role','farmer')->latest()->get(),
                'row_keys'=> ['name', 'email', 'state', 'lga', 'phone', 'is_verified', 'created_at'],
            ],
            'animals', 'livestock' => [
                'title'   => 'Livestock Report',
                'columns' => ['Name', 'Species', 'Breed', 'Owner', 'Health Status', 'Registered'],
                'records' => Animal::with('user')->latest()->get(),
                'row_keys'=> ['name', 'species', 'breed', 'user.name', 'health_status', 'created_at'],
            ],
            'diseases' => [
                'title'   => 'Disease Incidence & Expert Interventions Report',
                'columns' => ['Farmer', 'Case Type', 'Status', 'Submitted', 'Updated'],
                'records' => Consultation::with('farmer')->latest()->get(),
                'row_keys'=> ['farmer.name', 'case_type', 'status', 'created_at', 'updated_at'],
            ],
            'geographic' => [
                'title'   => 'Geographic Distribution Report',
                'columns' => ['State', 'Total Users'],
                'records' => User::select('state', DB::raw('count(*) as count'))->groupBy('state')->orderByDesc('count')->get(),
                'row_keys'=> ['state', 'count'],
            ],
            default => abort(404),
        };

        return view('ceo.report-preview', compact('data','type'));
    }
}
