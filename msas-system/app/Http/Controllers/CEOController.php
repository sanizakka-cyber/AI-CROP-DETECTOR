<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Animal;
use App\Models\Feedback;
use App\Models\Finance;
use App\Models\Consultation;
use App\Models\InviteCode;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Diagnosis;
use App\Models\EggProduction;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Subscription;
use Illuminate\Http\Request;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class CEOController extends Controller
{
    public function index()
    {
        // ── KPI Metrics ────────────────────────────────────────────
        try {
            $totalUsers     = User::count();
            $activeUsers    = User::where('is_active', true)->count();
            $pendingExperts = User::whereIn('role', ['vet','agronomist'])->where('is_verified', false)->count();
        } catch (\Exception $e) {
            Log::error('[CEO] KPI user counts failed: ' . $e->getMessage());
            $totalUsers = $activeUsers = $pendingExperts = 0;
        }
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
        try {
            $usersByRole = User::select('role', DB::raw('count(*) as count'))
                              ->groupBy('role')
                              ->pluck('count', 'role');
        } catch (\Exception $e) {
            Log::error('[CEO] usersByRole query failed: ' . $e->getMessage());
            $usersByRole = collect();
        }

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
        try {
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
        } catch (\Exception $e) {
            Log::error('[CEO] monthlyGrowth cache/query failed: ' . $e->getMessage());
            $monthlyGrowth = collect(range(5, 0))->map(fn($i) => [
                'label' => now()->subMonths($i)->format('M'), 'farmers' => 0, 'experts' => 0, 'total' => 0,
            ]);
        }

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
        try {
            $recentUsers = User::latest()->take(8)->get();
        } catch (\Exception $e) {
            Log::error('[CEO] recentUsers query failed: ' . $e->getMessage());
            $recentUsers = collect();
        }

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

        // ── Subscription Analytics ─────────────────────────────────
        try {
            $planKeys = array_keys(config('subscription.plans', []));
            $subStats = Cache::remember('ceo:sub_stats', 120, function () use ($planKeys) {
                $all = Subscription::all();
                $active  = $all->whereIn('status', ['active', 'trial']);
                $byPlan  = $active->groupBy('plan')
                                  ->map(fn($g) => $g->count());
                $revenue = Subscription::where('status', 'active')
                    ->selectRaw('plan, SUM(amount_paid) as total')
                    ->groupBy('plan')
                    ->pluck('total', 'plan');

                $thisMonth = now()->format('Y-m');
                $lastMonth = now()->subMonth()->format('Y-m');
                $newThisMonth = Subscription::where('status', 'active')
                    ->whereRaw("TO_CHAR(created_at, 'YYYY-MM') = ?", [$thisMonth])
                    ->count();
                $newLastMonth = Subscription::where('status', 'active')
                    ->whereRaw("TO_CHAR(created_at, 'YYYY-MM') = ?", [$lastMonth])
                    ->count();

                return [
                    'total'         => $all->count(),
                    'active'        => $all->where('status', 'active')->count(),
                    'trial'         => $all->where('status', 'trial')->count(),
                    'expired'       => $all->where('status', 'expired')->count(),
                    'cancelled'     => $all->where('status', 'cancelled')->count(),
                    'suspended'     => $all->where('status', 'suspended')->count(),
                    'by_plan'       => $byPlan->toArray(),
                    'revenue_total' => $all->sum('amount_paid'),
                    'revenue_month' => Subscription::whereRaw("TO_CHAR(created_at, 'YYYY-MM') = ?", [$thisMonth])->sum('amount_paid'),
                    'revenue_by_plan' => $revenue->toArray(),
                    'new_this_month'  => $newThisMonth,
                    'growth_pct'    => $newLastMonth > 0 ? round((($newThisMonth - $newLastMonth) / $newLastMonth) * 100, 1) : 0,
                ];
            });
        } catch (\Exception $e) {
            Log::warning('[CEO] Subscription stats failed: ' . $e->getMessage());
            $subStats = [
                'total' => 0, 'active' => 0, 'trial' => 0, 'expired' => 0,
                'cancelled' => 0, 'suspended' => 0, 'by_plan' => [],
                'revenue_total' => 0, 'revenue_month' => 0, 'revenue_by_plan' => [],
                'new_this_month' => 0, 'growth_pct' => 0,
            ];
        }

        // ── Marketplace / Orders ───────────────────────────────────────
        try {
            $orderStats = Cache::remember('ceo:order_stats', 120, function () {
                return [
                    'total'      => Order::count(),
                    'pending'    => Order::where('status', 'pending')->count(),
                    'processing' => Order::where('status', 'processing')->count(),
                    'shipped'    => Order::where('status', 'shipped')->count(),
                    'delivered'  => Order::where('status', 'delivered')->count(),
                    'cancelled'  => Order::where('status', 'cancelled')->count(),
                    'gmv'        => Order::where('payment_status', 'paid')->sum('total'),
                    'gmv_month'  => Order::where('payment_status', 'paid')
                                        ->whereMonth('created_at', now()->month)
                                        ->whereYear('created_at', now()->year)
                                        ->sum('total'),
                ];
            });
        } catch (\Exception $e) {
            Log::warning('[CEO] orderStats failed: ' . $e->getMessage());
            $orderStats = ['total'=>0,'pending'=>0,'processing'=>0,'shipped'=>0,'delivered'=>0,'cancelled'=>0,'gmv'=>0,'gmv_month'=>0];
        }
        try {
            $topProducts = Product::select('id', 'name', 'selling_price', 'category')
                ->selectRaw('(SELECT COUNT(*) FROM order_items WHERE order_items.product_id = products.id) as order_count')
                ->where('status', 'active')
                ->orderByDesc('order_count')
                ->take(5)
                ->get();
        } catch (\Exception $e) { $topProducts = collect(); }

        // ── AI Smart Scan Analytics ────────────────────────────────
        try {
            $aiStats = Cache::remember('ceo:ai_stats', 120, function () {
                return [
                    'today'       => Diagnosis::whereDate('created_at', today())->count(),
                    'this_month'  => Diagnosis::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                    'total'       => Diagnosis::count(),
                    'avg_conf'    => round((float) Diagnosis::whereNotNull('confidence_score')->avg('confidence_score'), 1),
                    'crop_total'  => Diagnosis::where('type', 'crop')->count(),
                    'live_total'  => Diagnosis::where('type', 'livestock')->count(),
                    'soil_total'  => Diagnosis::where('type', 'soil')->count(),
                    'top_diseases'=> Diagnosis::select('disease_name', DB::raw('count(*) as cnt'))
                                        ->whereNotNull('disease_name')
                                        ->where('disease_name', '!=', 'Pending Expert Review')
                                        ->where('created_at', '>=', now()->subDays(30))
                                        ->groupBy('disease_name')
                                        ->orderByDesc('cnt')
                                        ->take(6)
                                        ->get(),
                ];
            });
        } catch (\Exception $e) {
            Log::warning('[CEO] aiStats failed: ' . $e->getMessage());
            $aiStats = ['today'=>0,'this_month'=>0,'total'=>0,'avg_conf'=>0,'crop_total'=>0,'live_total'=>0,'soil_total'=>0,'top_diseases'=>collect()];
        }

        // ── Consultation Detail Stats ──────────────────────────────
        try {
            $consultStats = [
                'pending'     => Consultation::where('status', 'pending')->count(),
                'in_progress' => Consultation::where('status', 'in-progress')->count(),
                'completed'   => Consultation::where('status', 'completed')->count(),
                'avg_hours'   => round((float) Consultation::whereNotNull('completed_at')
                                    ->selectRaw('AVG(EXTRACT(EPOCH FROM (completed_at - created_at)) / 3600) as avg_h')
                                    ->value('avg_h'), 1),
            ];
        } catch (\Exception $e) {
            $consultStats = ['pending'=>$pendingConsults,'in_progress'=>0,'completed'=>0,'avg_hours'=>0];
        }

        // ── Logistics Stats ────────────────────────────────────────
        try {
            $logisticsStats = [
                'pending_dispatch' => Order::whereIn('status', ['confirmed','processing'])->whereNull('rider_id')->count(),
                'riders_available' => User::where('role', 'rider')->where('rider_status', 'available')->count(),
                'riders_busy'      => User::where('role', 'rider')->where('rider_status', 'busy')->count(),
                'in_transit'       => Order::where('rider_status', 'in_transit')->count(),
                'delivered'        => Order::where('status', 'delivered')->count(),
            ];
        } catch (\Exception $e) {
            $logisticsStats = ['pending_dispatch'=>0,'riders_available'=>0,'riders_busy'=>0,'in_transit'=>0,'delivered'=>0];
        }

        // ── Granular Revenue (Payment model) ──────────────────────
        try {
            $payRevenue = Cache::remember('ceo:pay_revenue', 120, function () {
                return [
                    'today' => Payment::successful()->whereDate('paid_at', today())->sum('amount'),
                    'week'  => Payment::successful()->where('paid_at', '>=', now()->startOfWeek())->sum('amount'),
                    'month' => Payment::successful()->whereMonth('paid_at', now()->month)->whereYear('paid_at', now()->year)->sum('amount'),
                    'year'  => Payment::successful()->whereYear('paid_at', now()->year)->sum('amount'),
                    'total' => Payment::successful()->sum('amount'),
                ];
            });
        } catch (\Exception $e) {
            $payRevenue = ['today'=>0,'week'=>0,'month'=>0,'year'=>0,'total'=>0];
        }

        // ── Revenue Time-Series (daily 14 days / weekly 8 weeks / monthly 12 mo) ─
        try {
            $revTimeSeries = Cache::remember('ceo:rev_timeseries', 120, function () {
                // Daily: last 14 days
                $dailyRaw = Payment::successful()
                    ->where('paid_at', '>=', now()->subDays(13)->startOfDay())
                    ->selectRaw("DATE(paid_at) as d, SUM(amount) as total")
                    ->groupBy(DB::raw('DATE(paid_at)'))
                    ->pluck('total', 'd');
                $daily = collect(range(13, 0))->map(fn($i) => [
                    'label' => now()->subDays($i)->format('M d'),
                    'value' => (float) ($dailyRaw[now()->subDays($i)->format('Y-m-d')] ?? 0),
                ]);

                // Weekly: last 8 weeks
                $weekly = collect(range(7, 0))->map(function ($i) {
                    $start = now()->startOfWeek()->subWeeks($i);
                    $end   = (clone $start)->endOfWeek();
                    return [
                        'label' => $start->format('M d'),
                        'value' => (float) Payment::successful()->whereBetween('paid_at', [$start, $end])->sum('amount'),
                    ];
                });

                // Monthly: last 12 months
                $monthly = collect(range(11, 0))->map(function ($i) {
                    $m = now()->subMonths($i);
                    return [
                        'label' => $m->format('M Y'),
                        'value' => (float) Payment::successful()->whereYear('paid_at', $m->year)->whereMonth('paid_at', $m->month)->sum('amount'),
                    ];
                });

                return compact('daily', 'weekly', 'monthly');
            });
        } catch (\Exception $e) {
            Log::warning('[CEO] revTimeSeries failed: ' . $e->getMessage());
            $revTimeSeries = [
                'daily'   => collect(range(13,0))->map(fn($i) => ['label' => now()->subDays($i)->format('M d'), 'value' => 0]),
                'weekly'  => collect(range(7,0))->map(fn($i) => ['label' => now()->subWeeks($i)->format('M d'), 'value' => 0]),
                'monthly' => collect(range(11,0))->map(fn($i) => ['label' => now()->subMonths($i)->format('M Y'), 'value' => 0]),
            ];
        }

        // ── Geographic: top 8 states — users + diagnoses ──────────
        try {
            $geoChart = Cache::remember('ceo:geo_chart', 300, function () {
                $states = User::whereNotNull('state')
                    ->select('state', DB::raw('count(*) as users'))
                    ->groupBy('state')
                    ->orderByDesc('users')
                    ->take(8)
                    ->pluck('users', 'state');

                $diseaseCounts = Diagnosis::join('users', 'diagnoses.user_id', '=', 'users.id')
                    ->whereIn('users.state', $states->keys()->toArray())
                    ->select('users.state', DB::raw('count(*) as diagnoses'))
                    ->groupBy('users.state')
                    ->pluck('diagnoses', 'users.state');

                return $states->mapWithKeys(fn($cnt, $state) => [
                    $state => ['users' => $cnt, 'diagnoses' => (int)($diseaseCounts[$state] ?? 0)],
                ]);
            });
        } catch (\Exception $e) {
            $geoChart = collect();
        }

        // ── MRR / ARR / Churn / Conversion ────────────────────────
        try {
            $plans = config('subscription.plans', []);
            $activeSubsByPlan = Subscription::where('status', 'active')
                ->select('plan', DB::raw('count(*) as cnt'))
                ->groupBy('plan')
                ->pluck('cnt', 'plan');

            $mrr = 0;
            foreach ($plans as $key => $plan) {
                $mrr += (($plan['price']['monthly'] ?? 0) * ($activeSubsByPlan[$key] ?? 0));
            }
            $arr = $mrr * 12;

            $expiredThisMonth    = Subscription::whereIn('status', ['expired','cancelled'])
                ->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->count();
            $activeStartOfMonth  = Subscription::where('status', 'active')
                ->where('created_at', '<', now()->startOfMonth())->count();
            $churnRate           = $activeStartOfMonth > 0
                ? round(($expiredThisMonth / $activeStartOfMonth) * 100, 1) : 0;

            $totalSubs           = Subscription::whereIn('status', ['active','trial','expired','cancelled'])->count();
            $activeSubs          = Subscription::where('status', 'active')->count();
            $conversionRate      = $totalSubs > 0 ? round(($activeSubs / $totalSubs) * 100, 1) : 0;
        } catch (\Exception $e) {
            Log::warning('[CEO] MRR/churn calc failed: ' . $e->getMessage());
            $mrr = $arr = 0;
            $churnRate = $conversionRate = 0;
        }

        // ── User Registration Breakdown ────────────────────────────
        try {
            $newUsersToday = User::whereDate('created_at', today())->count();
            $newUsersWeek  = User::where('created_at', '>=', now()->startOfWeek())->count();
            $verifiedUsers = User::where('is_verified', true)->count();
            $verifyRate    = $totalUsers > 0 ? round(($verifiedUsers / $totalUsers) * 100) : 0;
        } catch (\Exception $e) {
            $newUsersToday = $newUsersWeek = $verifiedUsers = $verifyRate = 0;
        }

        return view('ceo.dashboard', compact(
            'totalUsers','activeUsers','pendingExperts',
            'totalAnimals','totalDiagnoses','pendingConsults',
            'totalRevenue','totalExpenses','netProfit',
            'thisMonthRevenue','revenueGrowth',
            'usersByRole','revenueChart','recentUsers',
            'presentToday','staffCount','pendingLeaves',
            'marketItems','pendingListings','diseaseAlerts',
            'monthlyGrowth','cropDiagnoses','livestockDiagnoses',
            'stateActivity','platformHealth','resolutionRate','activePct',
            'subStats',
            // New metrics
            'orderStats','topProducts',
            'aiStats',
            'consultStats',
            'logisticsStats',
            'payRevenue',
            'mrr','arr','churnRate','conversionRate',
            'newUsersToday','newUsersWeek','verifiedUsers','verifyRate',
            'revTimeSeries','geoChart'
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

    // ── System Audit ───────────────────────────────────────────────
    public function audit()
    {
        $checks = [];

        // 1. Database tables
        $tables = [
            'users', 'animals', 'consultations', 'diagnoses', 'finance',
            'products', 'subscriptions', 'subscription_usages', 'notifications',
            'attendances', 'leave_requests', 'egg_productions',
        ];
        $tableGroup = [];
        foreach ($tables as $t) {
            $exists = \Illuminate\Support\Facades\Schema::hasTable($t);
            $tableGroup[] = ['name' => $t, 'ok' => $exists, 'detail' => $exists ? 'exists' : 'MISSING'];
        }
        $checks['Database Tables'] = $tableGroup;

        // 2. Subscription plans
        $plans = config('subscription.plans', []);
        $planGroup = [];
        $requiredPlans = ['basic', 'basic_pro', 'premium', 'enterprise', 'enterprise_plus'];
        foreach ($requiredPlans as $p) {
            $found = isset($plans[$p]);
            $price = $found ? '₦' . number_format($plans[$p]['price']['monthly'] ?? 0) . '/mo' : '—';
            $planGroup[] = ['name' => $p, 'ok' => $found, 'detail' => $found ? $price : 'MISSING from config'];
        }
        $checks['Subscription Plans'] = $planGroup;

        // 3. Language files
        $langGroup = [];
        $locales = ['en' => 'English', 'ha' => 'Hausa', 'yo' => 'Yorùbá', 'ig' => 'Igbo', 'fr' => 'Français'];
        $enKeys  = [];
        foreach ($locales as $code => $name) {
            $path   = lang_path($code . '.json');
            $exists = file_exists($path);
            $count  = 0;
            if ($exists) {
                $data  = json_decode(file_get_contents($path), true) ?: [];
                $count = count($data);
                if ($code === 'en') $enKeys = array_keys($data);
            }
            $missing = ($code !== 'en' && $enKeys) ? count(array_diff($enKeys, array_keys(json_decode(file_exists($path) ? file_get_contents($path) : '{}', true) ?: []))) : 0;
            $ok      = $exists && $count > 0 && $missing === 0;
            $detail  = $exists ? "{$count} keys" . ($missing > 0 ? ", {$missing} missing" : '') : 'FILE MISSING';
            $langGroup[] = ['name' => $name . " ({$code})", 'ok' => $ok, 'detail' => $detail];
        }
        $checks['Language Files'] = $langGroup;

        // 4. Key routes
        $routeGroup = [];
        $namedRoutes = [
            'ceo.dashboard', 'ceo.users', 'ceo.audit', 'ceo.reports',
            'farmer.dashboard', 'subscription.plans', 'subscription.dashboard',
            'diagnostics.history', 'diagnostics.translate',
            'locale.set', 'login', 'register',
        ];
        foreach ($namedRoutes as $r) {
            $ok = \Illuminate\Support\Facades\Route::has($r);
            $routeGroup[] = ['name' => $r, 'ok' => $ok, 'detail' => $ok ? 'registered' : 'MISSING'];
        }
        $checks['Named Routes'] = $routeGroup;

        // 5. App environment
        $envGroup = [];
        $envChecks = [
            'APP_KEY'      => config('app.key') !== null && config('app.key') !== '',
            'APP_URL'      => config('app.url') !== 'http://localhost',
            'DB_HOST'      => config('database.connections.pgsql.host') !== null,
            'PAYSTACK_KEY' => config('services.paystack.secret_key') && !str_contains(config('services.paystack.secret_key', ''), 'REPLACE'),
        ];
        foreach ($envChecks as $key => $ok) {
            $envGroup[] = ['name' => $key, 'ok' => $ok, 'detail' => $ok ? 'set' : 'not configured'];
        }
        $checks['Environment'] = $envGroup;

        // 6. Recent errors from log (last 20 lines)
        $logPath   = storage_path('logs/laravel.log');
        $logErrors = [];
        if (file_exists($logPath)) {
            $lines = array_slice(file($logPath), -80);
            foreach ($lines as $line) {
                if (str_contains($line, '.ERROR') || str_contains($line, '.CRITICAL')) {
                    $logErrors[] = trim($line);
                    if (count($logErrors) >= 10) break;
                }
            }
        }

        // 7. User stats
        $userStats = [
            'total'    => \App\Models\User::count(),
            'active'   => \App\Models\User::where('is_active', true)->count(),
            'trial'    => \App\Models\User::whereHas('subscriptions', fn($q) => $q->where('status','trial'))->count(),
            'paid'     => \App\Models\User::whereHas('subscriptions', fn($q) => $q->where('status','active'))->count(),
        ];

        $auditAt = now()->format('d M Y H:i:s T');

        return view('ceo.audit', compact('checks', 'logErrors', 'userStats', 'auditAt'));
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

    // ── CSV Export ─────────────────────────────────────────────────
    public function exportCsv(string $type)
    {
        $data = match($type) {
            'financial' => [
                'filename' => 'financial-report',
                'columns'  => ['Description', 'Type', 'Amount', 'Date'],
                'records'  => Finance::latest()->get(),
                'row'      => fn($r) => [$r->description, $r->type, $r->amount, $r->transaction_date],
            ],
            'users' => [
                'filename' => 'users-report',
                'columns'  => ['Name', 'Role', 'Email', 'Phone', 'State', 'Active', 'Joined'],
                'records'  => User::latest()->get(),
                'row'      => fn($r) => [$r->first_name.' '.$r->last_name, $r->role, $r->email, $r->phone, $r->state, $r->is_active ? 'Yes' : 'No', $r->created_at->toDateString()],
            ],
            'farmers' => [
                'filename' => 'farmers-report',
                'columns'  => ['Name', 'Email', 'Phone', 'State', 'LGA', 'Verified', 'Joined'],
                'records'  => User::where('role', 'farmer')->latest()->get(),
                'row'      => fn($r) => [$r->first_name.' '.$r->last_name, $r->email, $r->phone, $r->state, $r->lga, $r->is_verified ? 'Yes' : 'No', $r->created_at->toDateString()],
            ],
            'diseases' => [
                'filename' => 'disease-report',
                'columns'  => ['Farmer', 'Case Type', 'Status', 'Submitted', 'Completed'],
                'records'  => Consultation::with('farmer')->latest()->get(),
                'row'      => fn($r) => [$r->farmer?->first_name.' '.$r->farmer?->last_name, $r->case_type, $r->status, $r->created_at->toDateString(), $r->completed_at?->toDateString() ?? '-'],
            ],
            'geographic' => [
                'filename' => 'geographic-report',
                'columns'  => ['State', 'Total Users'],
                'records'  => User::select('state', DB::raw('count(*) as count'))->groupBy('state')->orderByDesc('count')->get(),
                'row'      => fn($r) => [$r->state ?? 'Unknown', $r->count],
            ],
            default => abort(404),
        };

        $filename = $data['filename'] . '-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($data) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $data['columns']);
            foreach ($data['records'] as $record) {
                fputcsv($handle, ($data['row'])($record));
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function aiStatus()
    {
        $baseUrl = rtrim(config('services.ai_engine.url', ''), '/');
        $aiKey   = config('services.ai_engine.key', '');

        // ── Health check (no auth required) ───────────────────────────────────
        $health     = null;
        $latency    = null;
        $error      = null;
        $rawBody    = null;
        $httpStatus = null;

        // ── Auth test (POST to /predict/crop with 1×1 JPEG, checks Bearer key) ─
        $authStatus  = null;
        $authBody    = null;
        $authError   = null;
        $authLatency = null;

        if ($baseUrl) {
            $guzzle = new GuzzleClient(['connect_timeout' => 10, 'timeout' => 30, 'http_errors' => false]);
            $authHeaders = [];
            if ($aiKey && $aiKey !== 'REPLACE_WITH_AI_ENGINE_KEY') {
                $authHeaders['Authorization'] = "Bearer {$aiKey}";
            }

            // 1. Health check
            try {
                $t0      = microtime(true);
                $resp    = $guzzle->get("{$baseUrl}/health", ['headers' => $authHeaders]);
                $latency = round((microtime(true) - $t0) * 1000);

                $httpStatus = $resp->getStatusCode();
                $rawBody    = (string) $resp->getBody();
                $health     = json_decode($rawBody, true);
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }

            // 2. Auth test — POST a small but valid green-leaf JPEG to /predict/crop.
            // This verifies the Bearer key is accepted AND that Claude can process an image.
            // 8×8 solid green JPEG — small enough to be fast, large enough for Claude.
            $minimalJpeg = base64_decode(
                '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAMCAgMCAgMDAwMEAwMEBQgFBQQEBQoH'
                . 'BwYIDAoMCwsKCwsNCxAQDQ4RDgsLEBYQERMUFRUVDA8XGBYUGBIUFRT/2wBDAQME'
                . 'BAUEBQkFBQkUDQsNFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQU'
                . 'FBQUFBQUFBT/wAARCAAIAAgDASIAAhEBAxEB/8QAFgABAQEAAAAAAAAAAAAAAAAA'
                . 'CAUD/8QAIhAAAQQCAgMBAAAAAAAAAAAAAQIDBBESITFBUWH/xAAUAQEAAAAAAAAA'
                . 'AAAAAAAAAAD/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwCt2tpVW'
                . 'ZqJibLXHGxrGNDWtaMAD2AHoiICIiAiIgIiIP/9k='
            );

            try {
                $boundary = '----MSASAuthTest' . bin2hex(random_bytes(8));
                $body     = "--{$boundary}\r\n"
                    . "Content-Disposition: form-data; name=\"images\"; filename=\"test.jpg\"\r\n"
                    . "Content-Type: image/jpeg\r\n\r\n"
                    . $minimalJpeg . "\r\n"
                    . "--{$boundary}--\r\n";

                $t0 = microtime(true);
                $resp = $guzzle->post("{$baseUrl}/predict/crop", [
                    'headers' => array_merge($authHeaders, [
                        'Content-Type' => "multipart/form-data; boundary={$boundary}",
                    ]),
                    'body' => $body,
                ]);
                $authLatency = round((microtime(true) - $t0) * 1000);
                $authStatus  = $resp->getStatusCode();
                $authBody    = (string) $resp->getBody();
            } catch (\Throwable $e) {
                $authError = $e->getMessage();
            }
        }

        $recentFailed = \App\Models\Diagnosis::where('status', 'needs_review')
            ->whereNull('subject_name')
            ->latest()
            ->take(5)
            ->get();

        return view('ceo.ai-status', compact(
            'baseUrl', 'aiKey', 'health', 'latency', 'error',
            'rawBody', 'httpStatus',
            'authStatus', 'authBody', 'authError', 'authLatency',
            'recentFailed'
        ));
    }

    // ── Order Oversight ──────────────────────────────────────────────────────────

    public function orders(\Illuminate\Http\Request $request)
    {
        $query = \App\Models\Order::with([
            'buyer:id,first_name,last_name,phone',
            'dealer:id,first_name,last_name',
            'rider:id,first_name,last_name,phone',
            'assignedBy:id,first_name,last_name',
        ])->latest();

        if ($status = $request->status) {
            $query->where('status', $status);
        }
        if ($riderStatus = $request->rider_status) {
            $query->where('rider_status', $riderStatus);
        }
        if ($search = $request->search) {
            $query->where('order_number', 'ilike', "%{$search}%");
        }

        $orders = $query->paginate(25)->withQueryString();

        $stats = [
            'total'      => \App\Models\Order::count(),
            'pending'    => \App\Models\Order::where('status', 'pending')->count(),
            'unassigned' => \App\Models\Order::whereIn('status', ['confirmed','processing'])->whereNull('rider_id')->count(),
            'in_transit' => \App\Models\Order::where('rider_status', 'in_transit')->count(),
            'delivered'  => \App\Models\Order::where('status', 'delivered')->count(),
            'cancelled'  => \App\Models\Order::where('status', 'cancelled')->count(),
            'revenue'    => \App\Models\Order::where('payment_status', 'paid')->sum('total'),
        ];

        $riders = \App\Models\User::where('role', 'rider')->where('is_active', true)
            ->orderBy('first_name')->get(['id','first_name','last_name','rider_status']);

        return view('ceo.orders', compact('orders', 'stats', 'riders'));
    }

    public function assignOrderRider(\Illuminate\Http\Request $request, \App\Models\Order $order)
    {
        $request->validate(['rider_id' => 'required|exists:users,id']);
        $rider = \App\Models\User::findOrFail($request->rider_id);

        if ($order->rider_id && $order->rider_id !== $rider->id) {
            optional($order->rider)->update(['rider_status' => 'available']);
        }

        $order->update([
            'rider_id'          => $rider->id,
            'assigned_by'       => auth()->id(),
            'rider_status'      => 'assigned',
            'rider_assigned_at' => now(),
            'status'            => 'processing',
        ]);

        $rider->update(['rider_status' => 'busy']);

        \App\Models\Notification::create([
            'user_id' => $rider->id,
            'title'   => 'CEO Assignment',
            'message' => "Order {$order->order_number} has been assigned to you by the CEO.",
            'type'    => 'info',
            'link'    => '/rider/orders/' . $order->id,
        ]);

        \App\Models\AuditLog::record('order.rider_assigned_by_ceo', 'Order', $order->id, [
            'rider_id' => $rider->id, 'ceo_id' => auth()->id(),
        ]);

        return back()->with('success', "Order {$order->order_number} assigned to {$rider->first_name} {$rider->last_name}.");
    }

    // ── Phase 2: Pilot Program ─────────────────────────────────────────────────

    public function pilot()
    {
        $pilots = User::where('role', 'farmer')
            ->where(fn($q) => $q->where('is_pilot', true)->orWhere('created_at', '>=', now()->subDays(30)))
            ->with(['subscriptions' => fn($q) => $q->latest()])
            ->withCount(['diagnoses as scan_count', 'consultations as consult_count'])
            ->latest()
            ->paginate(25);

        $pilotCount = User::where('is_pilot', true)->count();
        $newThisWeek = User::where('role', 'farmer')->where('created_at', '>=', now()->subDays(7))->count();

        return view('ceo.pilot', compact('pilots', 'pilotCount', 'newThisWeek'));
    }

    public function flagPilot(Request $request, User $user)
    {
        $user->update(['is_pilot' => ! $user->is_pilot]);
        $action = $user->fresh()->is_pilot ? 'flagged as pilot' : 'removed from pilot';
        return back()->with('success', "{$user->first_name} {$user->last_name} has been {$action}.");
    }

    public function feedback(Request $request)
    {
        $query = Feedback::with('user:id,first_name,last_name,role')->latest();
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('type'))   $query->where('type', $request->type);
        $feedbacks = $query->paginate(30);
        $counts = [
            'total'    => Feedback::count(),
            'new'      => Feedback::where('status', 'new')->count(),
            'reviewed' => Feedback::where('status', 'reviewed')->count(),
            'resolved' => Feedback::where('status', 'resolved')->count(),
        ];
        return view('ceo.feedback', compact('feedbacks', 'counts'));
    }

    public function updateFeedback(Request $request, Feedback $feedback)
    {
        $data = $request->validate([
            'status'      => 'required|in:new,reviewed,resolved',
            'admin_notes' => 'nullable|string|max:1000',
        ]);
        $feedback->update($data);
        return back()->with('success', 'Feedback updated.');
    }

    public function inviteCodes()
    {
        $codes = InviteCode::with('creator:id,first_name,last_name')
            ->latest()
            ->paginate(30);
        $plans = config('subscription.plans', []);
        return view('ceo.invite-codes', compact('codes', 'plans'));
    }

    public function storeInviteCode(Request $request)
    {
        $validPlans = implode(',', array_keys(config('subscription.plans', [])));
        $data = $request->validate([
            'plan'       => "required|in:{$validPlans}",
            'max_uses'   => 'required|integer|min:1|max:500',
            'expires_at' => 'nullable|date|after:today',
        ]);

        InviteCode::create([
            'code'       => InviteCode::generate(),
            'created_by' => auth()->id(),
            'plan'       => $data['plan'],
            'max_uses'   => $data['max_uses'],
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return back()->with('success', 'Invite code created successfully.');
    }

    public function deleteInviteCode(InviteCode $code)
    {
        $code->delete();
        return back()->with('success', 'Invite code deleted.');
    }
}
