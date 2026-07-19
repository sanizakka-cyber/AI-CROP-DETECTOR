<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CEOController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DiagnosticController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DealerProductController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\HRController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\Admin\SubscriptionManagementController;
use App\Http\Controllers\Admin\PaymentManagementController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');


// Language / Locale switcher (works logged in or out)
Route::post('/locale', [LocaleController::class, 'set'])->name('locale.set');

// Services Public Routes (Redirects to register/login for now to prevent 404s)
Route::prefix('services')->name('services.')->group(function () {
    Route::redirect('/livestock', '/register')->name('livestock');
    Route::redirect('/poultry',   '/register')->name('poultry');
    Route::redirect('/crops',     '/register')->name('crops');
    Route::redirect('/vet',       '/register')->name('vet');
    Route::redirect('/finance',   '/register')->name('finance');
});

Route::get('/dashboard', function () {
    $user = auth()->user();
    return match($user->role) {
        'ceo'                   => redirect()->route('ceo.dashboard'),
        'admin'                 => redirect()->route('admin.dashboard'),
        'farmer'                => redirect()->route('farmer.dashboard'),
        'vet'                   => redirect()->route('vet.dashboard'),
        'agronomist'            => redirect()->route('agronomist.dashboard'),
        'agro-dealer'           => redirect()->route('dealer.dashboard'),
        'equipment-dealer'      => redirect()->route('equipment-dealer.dashboard'),
        'cooperative'           => redirect()->route('cooperative.dashboard'),
        'ngo'                   => redirect()->route('ngo.dashboard'),
        'government'            => redirect()->route('government.dashboard'),
        'research-institution'  => redirect()->route('research-institution.dashboard'),
        'investor'              => redirect()->route('investor.dashboard'),
        'financial-institution' => redirect()->route('financial-institution.dashboard'),
        'extension-officer'     => redirect()->route('extension.dashboard'),
        'finance'               => redirect()->route('finance.dashboard'),
        'hr'                    => redirect()->route('hr.dashboard'),
        'operations'            => redirect()->route('operations.dashboard'),
        'data-analyst'          => redirect()->route('data-analyst.dashboard'),
        'monitoring-evaluation' => redirect()->route('monitoring-evaluation.dashboard'),
        'm-e-officer'           => redirect()->route('monitoring-evaluation.dashboard'),
        'me-officer'            => redirect()->route('monitoring-evaluation.dashboard'),
        'field-officer'         => redirect()->route('field-officer.dashboard'),
        'customer-support'      => redirect()->route('customer-support.dashboard'),
        default                 => view('dashboard'),
    };
})->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Force password reset (exempt from force.password.reset middleware by route name)
    Route::get('/change-password', [ProfileController::class, 'changePasswordForm'])->name('password.change');
    Route::post('/change-password', [ProfileController::class, 'changePassword'])->name('password.change.update');

    // Diagnostics (Smart Scan) — farmers only; analyze is throttled to protect AI engine
    Route::middleware(['role:farmer,admin,ceo'])->group(function () {
        Route::get('/diagnostics/scan', [DiagnosticController::class, 'scan'])->name('diagnostics.scan');
        Route::post('/diagnostics/analyze', [DiagnosticController::class, 'analyze'])
            ->middleware('throttle:10,1')
            ->name('diagnostics.analyze');
        Route::get('/diagnostics/history', [DiagnosticController::class, 'history'])->name('diagnostics.history');
        Route::post('/diagnostics/{diagnosis}/feedback', [DiagnosticController::class, 'storeFeedback'])->name('diagnostics.feedback');
    });
});

// Role-Specific Dashboards
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/dashboard',                [DashboardController::class, 'admin'])               ->middleware('role:admin,ceo')            ->name('admin.dashboard');
    Route::get('/farmer/dashboard',               [DashboardController::class, 'farmer'])              ->middleware('role:farmer')               ->name('farmer.dashboard');
    Route::get('/equipment-dealer/dashboard',     [DashboardController::class, 'equipmentDealer'])     ->middleware('role:equipment-dealer')     ->name('equipment-dealer.dashboard');
    Route::get('/cooperative/dashboard',          [DashboardController::class, 'cooperative'])         ->middleware('role:cooperative')          ->name('cooperative.dashboard');
    Route::get('/ngo/dashboard',                  [DashboardController::class, 'ngo'])                 ->middleware('role:ngo')                  ->name('ngo.dashboard');
    Route::get('/government/dashboard',           [DashboardController::class, 'government'])          ->middleware('role:government')           ->name('government.dashboard');
    Route::get('/research-institution/dashboard', [DashboardController::class, 'researchInstitution'])->middleware('role:research-institution') ->name('research-institution.dashboard');
    Route::get('/investor/dashboard',             [DashboardController::class, 'investor'])            ->middleware('role:investor')             ->name('investor.dashboard');
    Route::get('/financial-institution/dashboard',[DashboardController::class, 'financialInstitution'])->middleware('role:financial-institution')->name('financial-institution.dashboard');
    Route::get('/vet/dashboard', [DashboardController::class, 'vet'])->middleware('role:vet')->name('vet.dashboard');
    Route::get('/agronomist/dashboard', [DashboardController::class, 'agronomist'])->middleware('role:agronomist,ceo,admin')->name('agronomist.dashboard');
    Route::get('/dealer/dashboard', [DashboardController::class, 'dealer'])->middleware('role:agro-dealer')->name('dealer.dashboard');

    // Dealer Product Catalog & Orders (web)
    Route::middleware(['role:agro-dealer'])->prefix('dealer')->name('dealer.')->group(function () {
        Route::get('/products',                        [DealerProductController::class, 'index'])->name('products.index');
        Route::get('/products/create',                 [DealerProductController::class, 'create'])->name('products.create');
        Route::post('/products',                       [DealerProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit',         [DealerProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}',              [DealerProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}',           [DealerProductController::class, 'destroy'])->name('products.destroy');
        Route::post('/products/{product}/stock',       [DealerProductController::class, 'adjustStock'])->name('products.stock');
        Route::get('/orders',                          [DealerProductController::class, 'orders'])->name('orders');
        Route::patch('/orders/{order}/status',         [DealerProductController::class, 'updateOrderStatus'])->name('orders.status');
    });
    Route::get('/extension/dashboard', [DashboardController::class, 'extension'])->middleware('role:extension-officer')->name('extension.dashboard');
    Route::get('/finance/dashboard', [DashboardController::class, 'finance'])->middleware('role:finance,admin,ceo')->name('finance.dashboard');
    Route::get('/operations/dashboard', [DashboardController::class, 'operations'])->middleware('role:operations,admin,ceo')->name('operations.dashboard');
    Route::get('/data-analyst/dashboard', [DashboardController::class, 'dataAnalyst'])->middleware('role:data-analyst,admin,ceo')->name('data-analyst.dashboard');
    Route::get('/monitoring-evaluation/dashboard', [DashboardController::class, 'monitoringEvaluation'])->middleware('role:monitoring-evaluation,m-e-officer,me-officer,admin,ceo')->name('monitoring-evaluation.dashboard');
    Route::get('/field-officer/dashboard', [DashboardController::class, 'fieldOfficer'])->middleware('role:field-officer,extension-officer')->name('field-officer.dashboard');
    Route::get('/customer-support/dashboard', [DashboardController::class, 'customerSupport'])->middleware('role:customer-support,admin,ceo')->name('customer-support.dashboard');
});

// CEO Routes
Route::middleware(['auth', 'role:ceo,admin'])->group(function () {
    Route::get('/ceo', [CEOController::class, 'index'])->name('ceo.dashboard');
    Route::get('/ceo/users', [CEOController::class, 'users'])->name('ceo.users');
    Route::get('/ceo/reports', [CEOController::class, 'reports'])->name('ceo.reports');
});
// Report generation also accessible by data-analyst and M&E roles
Route::middleware(['auth', 'role:ceo,admin,data-analyst,monitoring-evaluation,m-e-officer'])->group(function () {
    Route::get('/ceo/reports/{type}', [CEOController::class, 'generateReport'])->name('ceo.reports.generate');
});

// Admin Routes
Route::middleware(['auth', 'role:admin,ceo'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::post('/users/{user}/toggle', [AdminController::class, 'toggleStatus'])->middleware('permission:user:suspend_account')->name('users.toggle');
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->middleware('permission:user:delete_other')->name('users.delete');
    Route::get('/staff', [AdminController::class, 'staff'])->name('staff');
    Route::get('/settings', [AdminController::class, 'settings'])->middleware('permission:admin:manage_settings')->name('settings');
    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
});

// Farmer Routes
Route::middleware(['auth', 'role:farmer'])->prefix('farmer')->name('farmer.')->group(function () {
    // Basic plan features (any active subscription)
    Route::get('/livestock',                  [\App\Http\Controllers\FarmerController::class, 'livestock'])->name('livestock');
    Route::post('/livestock',                 [\App\Http\Controllers\FarmerController::class, 'storeLivestock'])->name('livestock.store');
    Route::put('/livestock/{id}',             [\App\Http\Controllers\FarmerController::class, 'updateLivestock'])->name('livestock.update');

    Route::get('/poultry',                    [\App\Http\Controllers\FarmerController::class, 'poultry'])->name('poultry');
    Route::post('/poultry',                   [\App\Http\Controllers\FarmerController::class, 'storePoultry'])->name('poultry.store');
    Route::put('/poultry/{id}',               [\App\Http\Controllers\FarmerController::class, 'updatePoultry'])->name('poultry.update');
    Route::post('/poultry/{id}/mortality',    [\App\Http\Controllers\FarmerController::class, 'logMortality'])->name('poultry.mortality');
    Route::post('/poultry/{id}/eggs',         [\App\Http\Controllers\FarmerController::class, 'logEggs'])->name('poultry.eggs');

    Route::get('/finance',                   [\App\Http\Controllers\FarmerController::class, 'finance'])->name('finance');
    Route::post('/finance',                  [\App\Http\Controllers\FarmerController::class, 'storeFinance'])->name('finance.store');

    // Pro+ features
    Route::get('/vet-consult',               [\App\Http\Controllers\FarmerController::class, 'vetConsult'])->name('vet');
    Route::post('/vet-consult',              [\App\Http\Controllers\FarmerController::class, 'storeConsult'])->name('vet.store');
    Route::get('/vet-consult/{consultation}',[\App\Http\Controllers\FarmerController::class, 'viewConsult'])->name('vet.view');

    Route::get('/agro-request',              [\App\Http\Controllers\FarmerController::class, 'agroRequest'])->name('agro');
    Route::post('/agro-request',             [\App\Http\Controllers\FarmerController::class, 'storeAgroRequest'])->name('agro.store');

    // Reports — Pro+ (subscription gate enforced inside controller)
    Route::get('/reports',                   [\App\Http\Controllers\FarmerController::class, 'reports'])->name('reports');
    Route::get('/reports/download',          [\App\Http\Controllers\FarmerController::class, 'downloadReport'])->name('reports.download');
});


// Marketplace (Public/Authenticated)
Route::middleware(['auth'])->group(function () {
    Route::get('/marketplace', [\App\Http\Controllers\MarketplaceController::class, 'index'])->name('marketplace');
});

// Notifications
Route::middleware(['auth'])->group(function () {
    Route::get('/notifications',           [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-read',[\App\Http\Controllers\NotificationController::class, 'markRead'])->name('notifications.markRead');
});

// ── Subscription Routes (farmer-facing) ────────────────────────────────────
Route::middleware(['auth', 'role:farmer'])->prefix('subscription')->name('subscription.')->group(function () {
    Route::get('/plans',              [SubscriptionController::class, 'plans'])->name('plans');
    Route::get('/dashboard',          [SubscriptionController::class, 'dashboard'])->name('dashboard');
    Route::post('/subscribe',         [SubscriptionController::class, 'subscribe'])->name('subscribe');
    Route::post('/cancel',            [SubscriptionController::class, 'cancel'])->name('cancel');
    Route::post('/toggle-autorenew',  [SubscriptionController::class, 'toggleAutoRenew'])->name('toggle.autorenew');
    Route::get('/paystack/callback',  [SubscriptionController::class, 'paystackCallback'])->name('paystack.callback');
});

// ── Payment Routes (all authenticated users) ───────────────────────────────
Route::middleware('auth')->prefix('payment')->name('payment.')->group(function () {
    Route::get('/history',           [PaymentController::class, 'history'])->name('history');
    Route::post('/initiate',         [PaymentController::class, 'initiate'])->name('initiate');
    Route::post('/verify',           [PaymentController::class, 'verify'])->name('verify');
    Route::get('/callback',          [PaymentController::class, 'callback'])->name('callback');
    Route::get('/receipt/{payment}', [PaymentController::class, 'receipt'])->name('receipt');
});

// Paystack webhook — no auth, HMAC-verified, CSRF excluded in bootstrap/app.php
Route::post('/webhooks/paystack', [WebhookController::class, 'paystack'])
    ->name('webhooks.paystack');

// Consultation payment callback (auth only — farmer already logged in when Paystack redirects back)
Route::middleware('auth')
    ->get('/consultation/payment/callback', [App\Http\Controllers\FarmerController::class, 'consultationPaymentCallback'])
    ->name('consultation.payment.callback');

// ── Admin Payment Management ────────────────────────────────────────────────
Route::middleware(['auth', 'role:admin,ceo,finance'])->prefix('admin/payments')->name('admin.payments.')->group(function () {
    Route::get('/', [PaymentManagementController::class, 'index'])->name('index');
});

// ── Admin Subscription Management ──────────────────────────────────────────
Route::middleware(['auth', 'role:admin,ceo'])->prefix('admin/subscriptions')->name('admin.subscriptions.')->group(function () {
    Route::get('/',                                   [SubscriptionManagementController::class, 'index'])->name('index');
    Route::post('/users/{user}/activate',             [SubscriptionManagementController::class, 'activate'])->name('activate');
    Route::post('/users/{user}/trial',                [SubscriptionManagementController::class, 'grantTrial'])->name('trial');
    Route::post('/{subscription}/suspend',            [SubscriptionManagementController::class, 'suspend'])->name('suspend');
    Route::post('/{subscription}/reinstate',          [SubscriptionManagementController::class, 'reinstate'])->name('reinstate');
    Route::post('/{subscription}/terminate',          [SubscriptionManagementController::class, 'terminate'])->name('terminate');
});

// Vet Routes
Route::middleware(['auth', 'role:vet,agronomist'])->prefix('vet')->name('vet.')->group(function () {
    Route::get('/queue', [\App\Http\Controllers\VetController::class, 'queue'])->name('queue');
    Route::get('/consultation/{consultation}', [\App\Http\Controllers\VetController::class, 'show'])->name('show');
    Route::post('/consultation/{consultation}/respond', [\App\Http\Controllers\VetController::class, 'respond'])->name('respond');
});


// HR Routes
Route::middleware(['auth', 'role:hr,admin,ceo'])->prefix('hr')->name('hr.')->group(function () {
    Route::get('/dashboard',                              [HRController::class, 'dashboard'])->name('dashboard');
    Route::get('/staff',                                  [HRController::class, 'staff'])->name('staff');
    Route::post('/staff/{user}/toggle',                  [HRController::class, 'toggleStaffStatus'])->name('staff.toggle');
    Route::get('/attendance',                             [HRController::class, 'attendance'])->name('attendance');
    Route::post('/attendance',                            [HRController::class, 'markAttendance'])->name('attendance.mark');
    Route::post('/attendance/bulk',                       [HRController::class, 'bulkAttendance'])->name('attendance.bulk');
    Route::get('/leaves',                                 [HRController::class, 'leaves'])->name('leaves');
    Route::post('/leaves/{leave}/approve',                [HRController::class, 'approveLeave'])->name('leaves.approve');
    Route::post('/leaves/{leave}/reject',                 [HRController::class, 'rejectLeave'])->name('leaves.reject');
    Route::get('/payroll',                                [HRController::class, 'payroll'])->name('payroll');
    Route::post('/payroll',                               [HRController::class, 'storePayroll'])->name('payroll.store');
    Route::post('/payroll/{payroll}/paid',                [HRController::class, 'markPayrollPaid'])->name('payroll.paid');
});

// Finance Routes
Route::middleware(['auth', 'role:finance,admin,ceo'])->prefix('finance')->name('finance.')->group(function () {
    Route::get('/transactions',                           [FinanceController::class, 'transactions'])->name('transactions');
    Route::post('/transactions',                          [FinanceController::class, 'storeTransaction'])->name('transactions.store');
    Route::delete('/transactions/{finance}',              [FinanceController::class, 'deleteTransaction'])->name('transactions.delete');
    Route::get('/payroll',                                [FinanceController::class, 'payroll'])->name('payroll');
    Route::get('/reports',                                [FinanceController::class, 'reports'])->name('reports');
});

// Customer Support Routes
Route::middleware(['auth', 'role:customer-support,admin,ceo'])->prefix('support')->name('support.')->group(function () {
    Route::get('/tickets',                              [\App\Http\Controllers\SupportController::class, 'tickets'])->name('tickets');
    Route::get('/tickets/create',                       [\App\Http\Controllers\SupportController::class, 'create'])->name('tickets.create');
    Route::post('/tickets',                             [\App\Http\Controllers\SupportController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}',                     [\App\Http\Controllers\SupportController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/resolve',            [\App\Http\Controllers\SupportController::class, 'resolve'])->name('tickets.resolve');
    Route::post('/tickets/{ticket}/reply',              [\App\Http\Controllers\SupportController::class, 'reply'])->name('tickets.reply');
    Route::post('/tickets/{ticket}/close',              [\App\Http\Controllers\SupportController::class, 'close'])->name('tickets.close');
});

// Operations Routes
Route::middleware(['auth', 'role:operations,admin,ceo'])->prefix('operations')->name('operations.')->group(function () {
    Route::get('/tasks',                                [\App\Http\Controllers\OperationsController::class, 'tasks'])->name('tasks');
    Route::post('/tasks',                               [\App\Http\Controllers\OperationsController::class, 'storeTask'])->name('tasks.store');
    Route::patch('/tasks/{task}/status',                [\App\Http\Controllers\OperationsController::class, 'updateTaskStatus'])->name('tasks.status');
    Route::get('/users',                                [\App\Http\Controllers\OperationsController::class, 'users'])->name('users');
});

// Extension Officer Routes
Route::middleware(['auth', 'role:extension-officer,field-officer,admin,ceo'])->prefix('extension')->name('extension.')->group(function () {
    Route::get('/farmers',                              [\App\Http\Controllers\ExtensionController::class, 'farmers'])->name('farmers');
    Route::get('/advisory',                             [\App\Http\Controllers\ExtensionController::class, 'advisory'])->name('advisory');
    Route::post('/advisory',                            [\App\Http\Controllers\ExtensionController::class, 'storeAdvisory'])->name('advisory.store');
    Route::get('/visits',                               [\App\Http\Controllers\ExtensionController::class, 'visits'])->name('visits');
    Route::post('/visits',                              [\App\Http\Controllers\ExtensionController::class, 'storeVisit'])->name('visits.store');
});

require __DIR__.'/auth.php';
