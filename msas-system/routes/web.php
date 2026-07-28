<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ImpersonationController;
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
use App\Http\Controllers\Admin\ApplicationController;
use App\Http\Controllers\LogisticsController;
use App\Http\Controllers\MarketplaceSellController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

// Application submitted confirmation page (no auth required)
Route::view('/application-submitted', 'auth.application-submitted')->name('application.submitted');

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

Route::get('/dashboard', [DashboardController::class, 'dispatch'])->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Force password reset (exempt from force.password.reset middleware by route name)
    Route::get('/change-password', [ProfileController::class, 'changePasswordForm'])->name('password.change');
    Route::post('/change-password', [ProfileController::class, 'changePassword'])->name('password.change.update');

    // Diagnostics (Smart Scan) — farmers, vets, agronomists, admin, ceo
    Route::middleware(['role:farmer,admin,ceo,vet,agronomist'])->group(function () {
        Route::get('/diagnostics/scan', [DiagnosticController::class, 'scan'])->name('diagnostics.scan');
        Route::post('/diagnostics/analyze', [DiagnosticController::class, 'analyze'])
            ->middleware('throttle:10,1')
            ->name('diagnostics.analyze');
        Route::get('/diagnostics/history', [DiagnosticController::class, 'history'])->name('diagnostics.history');
        Route::post('/diagnostics/{diagnosis}/feedback', [DiagnosticController::class, 'storeFeedback'])->name('diagnostics.feedback');
        Route::post('/diagnostics/translate', [DiagnosticController::class, 'translate'])->name('diagnostics.translate');
        Route::get('/diagnostics/{diagnosis}/report', [DiagnosticController::class, 'downloadReport'])->name('diagnostics.report');
    });
});

// ── Impersonation (CEO/Admin only) ───────────────────────────────────────────
Route::middleware(['auth', 'role:ceo,admin'])->group(function () {
    Route::get('/admin/impersonate/{user}', [ImpersonationController::class, 'impersonate'])->name('impersonate.start');
    Route::get('/admin/impersonate/leave',  [ImpersonationController::class, 'leave'])->name('impersonate.leave');
});

// Role-Specific Dashboards
Route::middleware(['auth'])->group(function () {
    Route::get('/admin/dashboard',                [DashboardController::class, 'admin'])               ->middleware('role:admin,ceo')            ->name('admin.dashboard');
    Route::get('/farmer/dashboard',               [DashboardController::class, 'farmer'])              ->middleware('role:farmer,student')       ->name('farmer.dashboard');
    Route::get('/equipment-dealer/dashboard',     [DashboardController::class, 'equipmentDealer'])     ->middleware('role:equipment-dealer')     ->name('equipment-dealer.dashboard');
    Route::get('/cooperative/dashboard',          [DashboardController::class, 'cooperative'])         ->middleware('role:cooperative')          ->name('cooperative.dashboard');
    Route::get('/ngo/dashboard',                  [DashboardController::class, 'ngo'])                 ->middleware('role:ngo')                  ->name('ngo.dashboard');
    Route::get('/government/dashboard',           [DashboardController::class, 'government'])          ->middleware('role:government,government-agency') ->name('government.dashboard');
    Route::get('/research-institution/dashboard', [DashboardController::class, 'researchInstitution'])->middleware('role:research-institution,researcher') ->name('research-institution.dashboard');
    Route::get('/investor/dashboard',             [DashboardController::class, 'investor'])            ->middleware('role:investor')             ->name('investor.dashboard');
    Route::get('/financial-institution/dashboard',[DashboardController::class, 'financialInstitution'])->middleware('role:financial-institution')->name('financial-institution.dashboard');
    Route::get('/vet/dashboard', [DashboardController::class, 'vet'])->middleware('role:vet')->name('vet.dashboard');
    Route::get('/agronomist/dashboard', [DashboardController::class, 'agronomist'])->middleware('role:agronomist,ceo,admin')->name('agronomist.dashboard');
    Route::get('/dealer/dashboard', [DashboardController::class, 'dealer'])->middleware('role:agro-dealer')->name('dealer.dashboard');

    // Equipment Dealer Product Catalog & Orders
    Route::middleware(['role:equipment-dealer', 'subscription'])->prefix('equipment-dealer')->name('equipment-dealer.')->group(function () {
        Route::get('/products',                  [DealerProductController::class, 'index'])->name('products.index');
        Route::get('/products/create',           [DealerProductController::class, 'create'])->name('products.create');
        Route::post('/products',                 [DealerProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit',   [DealerProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}',        [DealerProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}',     [DealerProductController::class, 'destroy'])->name('products.destroy');
        Route::post('/products/{product}/stock', [DealerProductController::class, 'adjustStock'])->name('products.stock');
        Route::get('/orders',                    [DealerProductController::class, 'orders'])->name('orders');
        Route::patch('/orders/{order}/status',   [DealerProductController::class, 'updateOrderStatus'])->name('orders.status');
    });

    // Dealer Product Catalog & Orders (web)
    Route::middleware(['role:agro-dealer', 'subscription'])->prefix('dealer')->name('dealer.')->group(function () {
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
    // New role dashboards
    Route::get('/logistics/dashboard',        [DashboardController::class, 'logistics'])      ->middleware('role:logistics-provider')   ->name('logistics.dashboard');
    Route::get('/agribusiness/dashboard',     [DashboardController::class, 'agribusiness'])   ->middleware('role:agribusiness-owner')   ->name('agribusiness.dashboard');
    Route::get('/input-supplier/dashboard',   [DashboardController::class, 'inputSupplier'])  ->middleware('role:input-supplier')       ->name('input-supplier.dashboard');

    // Logistics sub-pages
    Route::middleware(['role:logistics-provider', 'subscription'])->prefix('logistics')->name('logistics.')->group(function () {
        Route::get('/vehicles',                     [LogisticsController::class, 'vehicles'])             ->name('vehicles');
        Route::post('/vehicles',                    [LogisticsController::class, 'storeVehicle'])          ->name('vehicles.store');
        Route::put('/vehicles/{vehicle}',           [LogisticsController::class, 'updateVehicle'])         ->name('vehicles.update');
        Route::delete('/vehicles/{vehicle}',        [LogisticsController::class, 'deleteVehicle'])         ->name('vehicles.delete');
        Route::get('/drivers',                      [LogisticsController::class, 'drivers'])               ->name('drivers');
        Route::post('/drivers',                     [LogisticsController::class, 'storeDriver'])            ->name('drivers.store');
        Route::put('/drivers/{driver}',             [LogisticsController::class, 'updateDriver'])           ->name('drivers.update');
        Route::delete('/drivers/{driver}',          [LogisticsController::class, 'deleteDriver'])           ->name('drivers.delete');
        Route::get('/deliveries',                   [LogisticsController::class, 'deliveries'])             ->name('deliveries');
        Route::post('/deliveries',                  [LogisticsController::class, 'storeDelivery'])          ->name('deliveries.store');
        Route::patch('/deliveries/{delivery}/status',[LogisticsController::class, 'updateDeliveryStatus']) ->name('deliveries.status');
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
    Route::get('/ceo',              [CEOController::class, 'index'])->name('ceo.dashboard');
    Route::get('/ceo/users',        [CEOController::class, 'users'])->name('ceo.users');
    Route::get('/ceo/users/{user}',      [CEOController::class, 'showUser'])->name('ceo.users.show');
    Route::get('/ceo/users/{user}/edit', [CEOController::class, 'editUser'])->name('ceo.users.edit');
    Route::patch('/ceo/users/{user}',    [CEOController::class, 'updateUser'])->name('ceo.users.update');
    Route::post('/ceo/users/{user}/toggle', [CEOController::class, 'toggleUser'])->name('ceo.users.toggle');
    Route::delete('/ceo/users/{user}',   [CEOController::class, 'deleteUser'])->name('ceo.users.delete');
    Route::get('/ceo/reports', [CEOController::class, 'reports'])->name('ceo.reports');
    Route::get('/ceo/audit',   [CEOController::class, 'audit'])->name('ceo.audit');
    Route::get('/ceo/ai-status', [CEOController::class, 'aiStatus'])->name('ceo.ai-status');
});

// ── CEO: Staff Role Management (CEO only) ─────────────────────────────────────
Route::middleware(['auth', 'role:ceo'])->prefix('ceo')->name('ceo.')->group(function () {
    // Custom RBAC role CRUD
    Route::get('/staff-roles',              [\App\Http\Controllers\CEO\StaffRoleController::class, 'index'])       ->name('staff-roles.index');
    Route::get('/staff-roles/create',       [\App\Http\Controllers\CEO\StaffRoleController::class, 'create'])      ->name('staff-roles.create');
    Route::post('/staff-roles',             [\App\Http\Controllers\CEO\StaffRoleController::class, 'store'])       ->name('staff-roles.store');
    Route::get('/staff-roles/{staffRole}',  [\App\Http\Controllers\CEO\StaffRoleController::class, 'show'])        ->name('staff-roles.show');
    Route::get('/staff-roles/{staffRole}/edit', [\App\Http\Controllers\CEO\StaffRoleController::class, 'edit'])   ->name('staff-roles.edit');
    Route::patch('/staff-roles/{staffRole}',    [\App\Http\Controllers\CEO\StaffRoleController::class, 'update']) ->name('staff-roles.update');
    Route::patch('/staff-roles/{staffRole}/toggle', [\App\Http\Controllers\CEO\StaffRoleController::class, 'toggleActive'])->name('staff-roles.toggle');
    Route::delete('/staff-roles/{staffRole}',   [\App\Http\Controllers\CEO\StaffRoleController::class, 'destroy'])->name('staff-roles.destroy');

    // Staff account management
    Route::get('/staff',                        [\App\Http\Controllers\CEO\StaffController::class, 'index'])           ->name('staff.index');
    Route::get('/staff/create',                 [\App\Http\Controllers\CEO\StaffController::class, 'create'])          ->name('staff.create');
    Route::post('/staff',                       [\App\Http\Controllers\CEO\StaffController::class, 'store'])           ->name('staff.store');
    Route::get('/staff/{user}',                 [\App\Http\Controllers\CEO\StaffController::class, 'show'])            ->name('staff.show');
    Route::get('/staff/{user}/edit',            [\App\Http\Controllers\CEO\StaffController::class, 'edit'])            ->name('staff.edit');
    Route::patch('/staff/{user}',               [\App\Http\Controllers\CEO\StaffController::class, 'update'])          ->name('staff.update');
    Route::patch('/staff/{user}/toggle',        [\App\Http\Controllers\CEO\StaffController::class, 'toggle'])          ->name('staff.toggle');
    Route::patch('/staff/{user}/reset-password',[\App\Http\Controllers\CEO\StaffController::class, 'resetPassword'])   ->name('staff.reset-password');
    Route::post('/staff/{user}/assign-roles',   [\App\Http\Controllers\CEO\StaffController::class, 'assignRoles'])     ->name('staff.assign-roles');
    Route::delete('/staff/{user}/remove-role',  [\App\Http\Controllers\CEO\StaffController::class, 'removeRole'])      ->name('staff.remove-role');
});
// Report generation also accessible by data-analyst and M&E roles
Route::middleware(['auth', 'role:ceo,admin,data-analyst,monitoring-evaluation,m-e-officer'])->group(function () {
    Route::get('/ceo/reports/{type}',     [CEOController::class, 'generateReport'])->name('ceo.reports.generate');
    Route::get('/ceo/reports/{type}/csv', [CEOController::class, 'exportCsv'])->name('ceo.reports.csv');
});

// ── CEO Monitoring + BI (ceo + admin) ─────────────────────────────────────────
Route::middleware(['auth', 'role:ceo,admin'])->prefix('ceo')->name('ceo.')->group(function () {
    Route::get('/monitoring',              [\App\Http\Controllers\MonitoringController::class, 'index'])      ->name('monitoring');
    Route::get('/monitoring/health.json',  [\App\Http\Controllers\MonitoringController::class, 'healthJson']) ->name('monitoring.health');
    Route::get('/bi',                      [\App\Http\Controllers\BiController::class, 'index'])              ->name('bi');
});

// ── CEO Pilot Program routes ──────────────────────────────────────────────────
Route::middleware(['auth', 'role:ceo,admin'])->prefix('ceo')->name('ceo.')->group(function () {
    Route::get('/pilot',                         [\App\Http\Controllers\CEOController::class, 'pilot'])            ->name('pilot');
    Route::post('/pilot/{user}/flag',            [\App\Http\Controllers\CEOController::class, 'flagPilot'])        ->name('pilot.flag');
    Route::get('/feedback',                      [\App\Http\Controllers\CEOController::class, 'feedback'])         ->name('feedback');
    Route::patch('/feedback/{feedback}/status',  [\App\Http\Controllers\CEOController::class, 'updateFeedback'])   ->name('feedback.update');
    Route::get('/invite-codes',                  [\App\Http\Controllers\CEOController::class, 'inviteCodes'])      ->name('invite-codes');
    Route::post('/invite-codes',                 [\App\Http\Controllers\CEOController::class, 'storeInviteCode'])  ->name('invite-codes.store');
    Route::delete('/invite-codes/{code}',        [\App\Http\Controllers\CEOController::class, 'deleteInviteCode']) ->name('invite-codes.delete');
    // Support tickets (staff side)
    Route::get('/support',                       [\App\Http\Controllers\SupportTicketController::class, 'adminIndex'])  ->name('support');
    Route::get('/support/{ticket}',              [\App\Http\Controllers\SupportTicketController::class, 'adminShow'])   ->name('support.show');
    Route::patch('/support/{ticket}',            [\App\Http\Controllers\SupportTicketController::class, 'adminUpdate']) ->name('support.update');
    Route::post('/support/{ticket}/reply',       [\App\Http\Controllers\SupportTicketController::class, 'adminReply'])  ->name('support.reply');
    // Broadcast
    Route::get('/broadcast',                     [\App\Http\Controllers\SupportTicketController::class, 'broadcastForm'])->name('broadcast');
    Route::post('/broadcast',                    [\App\Http\Controllers\SupportTicketController::class, 'broadcastSend'])->name('broadcast.send');
});

// Support tickets (farmer side)
Route::middleware(['auth'])->name('support.')->prefix('support')->group(function () {
    Route::get('/',              [\App\Http\Controllers\SupportTicketController::class, 'index'])  ->name('index');
    Route::get('/create',        [\App\Http\Controllers\SupportTicketController::class, 'create']) ->name('create');
    Route::post('/',             [\App\Http\Controllers\SupportTicketController::class, 'store'])  ->name('store');
    Route::get('/{ticket}',      [\App\Http\Controllers\SupportTicketController::class, 'show'])   ->name('show');
    Route::post('/{ticket}/reply',[\App\Http\Controllers\SupportTicketController::class,'reply'])  ->name('reply');
});

// Notification centre (farmer)
Route::middleware(['auth'])->get('/notifications', [\App\Http\Controllers\SupportTicketController::class, 'notificationCentre'])->name('notifications.index');

// ── Compliance (farmer) ───────────────────────────────────────────────────────
Route::middleware(['auth'])->prefix('account')->name('compliance.')->group(function () {
    Route::get('/privacy',  [\App\Http\Controllers\ComplianceController::class, 'privacy'])        ->name('privacy');
    Route::post('/export',  [\App\Http\Controllers\ComplianceController::class, 'requestExport'])  ->name('export');
    Route::delete('/delete',[\App\Http\Controllers\ComplianceController::class, 'requestDeletion'])->name('delete');
    Route::post('/consent', [\App\Http\Controllers\ComplianceController::class, 'recordConsent'])  ->name('consent');
});

// ── Audit Log + BI CEO routes ─────────────────────────────────────────────────
Route::middleware(['auth','role:ceo,admin'])->prefix('ceo')->name('ceo.')->group(function () {
    Route::get('/audit-log',  [\App\Http\Controllers\ComplianceController::class, 'auditLog'])->name('audit-log');
    Route::get('/referrals',  [\App\Http\Controllers\ReferralController::class, 'leaderboard'])->name('referrals');
    Route::get('/nps',        [\App\Http\Controllers\NpsController::class, 'dashboard'])       ->name('nps');
});

// ── Referral (farmer) ─────────────────────────────────────────────────────────
Route::middleware(['auth'])->get('/referral', [\App\Http\Controllers\ReferralController::class, 'index'])->name('referral.index');

// ── NPS (AJAX, auth) ──────────────────────────────────────────────────────────
Route::middleware(['auth'])->post('/nps', [\App\Http\Controllers\NpsController::class, 'store'])->name('nps.store');

// ── Changelog (public) ────────────────────────────────────────────────────────
Route::get('/changelog', fn() => view('changelog'))->name('changelog');

// Admin Routes
Route::middleware(['auth', 'role:admin,ceo'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::post('/users/{user}/toggle', [AdminController::class, 'toggleStatus'])->middleware('permission:user:suspend_account')->name('users.toggle');
    Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->middleware('permission:user:delete_other')->name('users.delete');
    Route::get('/staff', [AdminController::class, 'staff'])->name('staff');
    Route::get('/settings', [AdminController::class, 'settings'])->middleware('permission:admin:manage_settings')->name('settings');
    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');

    // Application review
    Route::get('/applications',                     [ApplicationController::class, 'index'])   ->name('applications.index');
    Route::get('/applications/document/{document}', [ApplicationController::class, 'document'])->name('applications.document');
    Route::get('/applications/{user}',              [ApplicationController::class, 'show'])    ->name('applications.show');
    Route::post('/applications/{user}/approve',     [ApplicationController::class, 'approve']) ->name('applications.approve');
    Route::post('/applications/{user}/reject',      [ApplicationController::class, 'reject'])  ->name('applications.reject');
});

// ── Admin: Consultation Management ────────────────────────────────────────────
Route::middleware(['auth', 'role:admin,ceo'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/consultations',                     [\App\Http\Controllers\Admin\ConsultationController::class, 'index'])        ->name('consultations.index');
    Route::get('/consultations/{consultation}',      [\App\Http\Controllers\Admin\ConsultationController::class, 'show'])         ->name('consultations.show');
    Route::post('/consultations/{consultation}/assign', [\App\Http\Controllers\Admin\ConsultationController::class, 'assign'])    ->name('consultations.assign');
    Route::patch('/consultations/{consultation}/status', [\App\Http\Controllers\Admin\ConsultationController::class, 'updateStatus'])->name('consultations.status');
    Route::post('/consultations/{consultation}/expert-accept', [\App\Http\Controllers\Admin\ConsultationController::class, 'expertAccept'])->name('consultations.expert.accept');
    Route::post('/consultations/{consultation}/expert-decline', [\App\Http\Controllers\Admin\ConsultationController::class, 'expertDecline'])->name('consultations.expert.decline');
});

// ── Admin: Order & Rider Management ───────────────────────────────────────────
Route::middleware(['auth', 'role:admin,ceo'])->prefix('admin')->name('admin.')->group(function () {
    // Orders
    Route::get('/orders',                            [\App\Http\Controllers\Admin\OrderManagementController::class, 'index'])       ->name('orders.index');
    Route::get('/orders/{order}',                    [\App\Http\Controllers\Admin\OrderManagementController::class, 'show'])        ->name('orders.show');
    Route::post('/orders/{order}/assign-rider',      [\App\Http\Controllers\Admin\OrderManagementController::class, 'assignRider']) ->name('orders.assign');
    Route::post('/orders/{order}/reassign',          [\App\Http\Controllers\Admin\OrderManagementController::class, 'reassignRider'])->name('orders.reassign');
    Route::patch('/orders/{order}/status',           [\App\Http\Controllers\Admin\OrderManagementController::class, 'updateStatus'])->name('orders.status');
    Route::post('/orders/{order}/note',              [\App\Http\Controllers\Admin\OrderManagementController::class, 'addNote'])     ->name('orders.note');

    // Riders
    Route::get('/riders',                            [\App\Http\Controllers\Admin\RiderManagementController::class, 'index'])       ->name('riders.index');
    Route::get('/riders/create',                     [\App\Http\Controllers\Admin\RiderManagementController::class, 'create'])      ->name('riders.create');
    Route::post('/riders',                           [\App\Http\Controllers\Admin\RiderManagementController::class, 'store'])       ->name('riders.store');
    Route::get('/riders/{user}',                     [\App\Http\Controllers\Admin\RiderManagementController::class, 'show'])        ->name('riders.show');
    Route::patch('/riders/{user}/toggle',            [\App\Http\Controllers\Admin\RiderManagementController::class, 'toggleStatus'])->name('riders.toggle');
    Route::patch('/riders/{user}/status',            [\App\Http\Controllers\Admin\RiderManagementController::class, 'updateRiderStatus'])->name('riders.status');
});

// ── Rider Routes ───────────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:rider'])->prefix('rider')->name('rider.')->group(function () {
    Route::get('/dashboard',                         [\App\Http\Controllers\RiderController::class, 'dashboard'])   ->name('dashboard');
    Route::get('/orders',                            [\App\Http\Controllers\RiderController::class, 'orders'])      ->name('orders');
    Route::get('/orders/{order}',                    [\App\Http\Controllers\RiderController::class, 'showOrder'])   ->name('orders.show');
    Route::post('/orders/{order}/accept',            [\App\Http\Controllers\RiderController::class, 'accept'])      ->name('orders.accept');
    Route::post('/orders/{order}/decline',           [\App\Http\Controllers\RiderController::class, 'decline'])     ->name('orders.decline');
    Route::post('/orders/{order}/transit',           [\App\Http\Controllers\RiderController::class, 'markInTransit'])->name('orders.transit');
    Route::post('/orders/{order}/delivered',         [\App\Http\Controllers\RiderController::class, 'markDelivered'])->name('orders.delivered');
    Route::patch('/status',                          [\App\Http\Controllers\RiderController::class, 'updateStatus'])->name('status');
});

// ── CEO: Order Oversight ───────────────────────────────────────────────────────
Route::middleware(['auth', 'role:ceo'])->group(function () {
    Route::get('/ceo/orders',                        [\App\Http\Controllers\CEOController::class, 'orders'])            ->name('ceo.orders');
    Route::post('/ceo/orders/{order}/assign',        [\App\Http\Controllers\CEOController::class, 'assignOrderRider'])  ->name('ceo.orders.assign');
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

    // Onboarding checklist dismiss
    Route::post('/onboarding/dismiss',       [\App\Http\Controllers\FarmerController::class, 'dismissOnboarding'])->name('onboarding.dismiss');
});


// Marketplace (Public/Authenticated)
Route::middleware(['auth'])->group(function () {
    Route::get('/marketplace',                               [\App\Http\Controllers\MarketplaceController::class, 'index'])->name('marketplace');
    Route::get('/marketplace/cart',                          [\App\Http\Controllers\MarketplaceController::class, 'cart'])->name('marketplace.cart');
    Route::post('/marketplace/cart/add',                     [\App\Http\Controllers\MarketplaceController::class, 'addToCart'])->name('marketplace.cart.add');
    Route::post('/marketplace/cart/update',                  [\App\Http\Controllers\MarketplaceController::class, 'updateCart'])->name('marketplace.cart.update');
    Route::post('/marketplace/cart/remove/{productId}',      [\App\Http\Controllers\MarketplaceController::class, 'removeFromCart'])->name('marketplace.cart.remove');
    Route::post('/marketplace/cart/clear',                   [\App\Http\Controllers\MarketplaceController::class, 'clearCart'])->name('marketplace.cart.clear');
    Route::get('/marketplace/checkout',                      [\App\Http\Controllers\MarketplaceController::class, 'checkout'])->name('marketplace.checkout');
    Route::post('/marketplace/order',                        [\App\Http\Controllers\MarketplaceController::class, 'placeOrder'])->name('marketplace.order.place');
    Route::get('/marketplace/orders',                        [\App\Http\Controllers\MarketplaceController::class, 'myOrders'])->name('marketplace.orders');
    Route::get('/marketplace/orders/{order}',                [\App\Http\Controllers\MarketplaceController::class, 'showOrder'])->name('marketplace.orders.show');
    Route::post('/marketplace/orders/{order}/confirm',       [\App\Http\Controllers\MarketplaceController::class, 'confirmDelivery'])->name('marketplace.orders.confirm');
    Route::post('/marketplace/orders/{order}/report-issue',  [\App\Http\Controllers\MarketplaceController::class, 'reportIssue'])->name('marketplace.orders.report');
    Route::get('/marketplace/{product}',                        [\App\Http\Controllers\MarketplaceController::class, 'show'])->name('marketplace.show')->whereNumber('product');
    Route::post('/marketplace/{product}/reviews',               [\App\Http\Controllers\MarketplaceController::class, 'storeReview'])->name('marketplace.reviews.store');
    Route::delete('/marketplace/reviews/{review}',              [\App\Http\Controllers\MarketplaceController::class, 'deleteReview'])->name('marketplace.reviews.delete');
});

// Notifications
Route::middleware(['auth'])->group(function () {
    Route::get('/notifications',           [\App\Http\Controllers\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-read',[\App\Http\Controllers\NotificationController::class, 'markRead'])->name('notifications.markRead');
});

// ── Subscription Routes (all authenticated roles) ──────────────────────────
Route::middleware(['auth'])->prefix('subscription')->name('subscription.')->group(function () {
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
Route::middleware(['auth', 'role:vet,agronomist', 'subscription'])->prefix('vet')->name('vet.')->group(function () {
    Route::get('/queue', [\App\Http\Controllers\VetController::class, 'queue'])->name('queue');
    Route::get('/consultation/{consultation}', [\App\Http\Controllers\VetController::class, 'show'])->name('show');
    Route::post('/consultation/{consultation}/respond', [\App\Http\Controllers\VetController::class, 'respond'])->name('respond');
    Route::post('/consultation/{consultation}/start-video', [\App\Http\Controllers\VetController::class, 'startVideo'])->name('start-video');
    Route::get('/vaccinations',    [\App\Http\Controllers\VetController::class, 'vaccinations'])->name('vaccinations');
    Route::post('/vaccinations',   [\App\Http\Controllers\VetController::class, 'storeVaccination'])->name('vaccinations.store');
    Route::get('/disease-alerts',  [\App\Http\Controllers\VetController::class, 'diseaseAlerts'])->name('disease-alerts');
});

// ── Video Consultation join page (vet + farmer) ───────────────────────────
Route::middleware(['auth'])->get('/consultation/{consultation}/video', function (\App\Models\Consultation $consultation) {
    $user = auth()->user();
    $isFarmer = $consultation->farmer_id === $user->id;
    $isExpert = $consultation->expert_id === $user->id;
    abort_unless($isFarmer || $isExpert || in_array($user->role, ['admin','ceo']), 403);
    abort_unless($consultation->video_room_id, 404, 'No video room has been started for this consultation.');
    $jitsiUrl = 'https://meet.jit.si/msas-' . $consultation->video_room_id;
    return view('consultation.video', compact('consultation', 'jitsiUrl'));
})->name('consultation.video');

// Marketplace Seller Routes (agribusiness-owner, input-supplier, farmer)
Route::middleware(['auth', 'subscription'])->prefix('marketplace/sell')->name('marketplace.')->group(function () {
    Route::get('/',                        [MarketplaceSellController::class, 'index'])->name('sell');
    Route::get('/create',                  [MarketplaceSellController::class, 'create'])->name('sell.create');
    Route::post('/',                       [MarketplaceSellController::class, 'store'])->name('sell.store');
    Route::get('/{product}/edit',          [MarketplaceSellController::class, 'edit'])->name('sell.edit');
    Route::put('/{product}',               [MarketplaceSellController::class, 'update'])->name('sell.update');
    Route::delete('/{product}',            [MarketplaceSellController::class, 'destroy'])->name('sell.destroy');
    Route::post('/{product}/stock',        [MarketplaceSellController::class, 'adjustStock'])->name('sell.stock');
    Route::get('/orders',                  [MarketplaceSellController::class, 'orders'])->name('sell.orders');
    Route::patch('/orders/{order}/status', [MarketplaceSellController::class, 'updateOrderStatus'])->name('sell.orders.status');
    Route::post('/orders/{order}/payout',  [MarketplaceSellController::class, 'requestPayout'])->name('sell.orders.payout');
});

// Marketplace payment callback (auth, after Paystack redirect)
Route::middleware('auth')
    ->get('/marketplace/payment/callback', [\App\Http\Controllers\MarketplaceController::class, 'paymentCallback'])
    ->name('marketplace.payment.callback');

// Admin payout management
Route::middleware(['auth', 'role:admin,ceo,finance'])->prefix('admin/payouts')->name('admin.payouts.')->group(function () {
    Route::get('/',                         [\App\Http\Controllers\AdminPayoutController::class, 'index'])->name('index');
    Route::post('/{order}/approve',         [\App\Http\Controllers\AdminPayoutController::class, 'approve'])->name('approve');
    Route::post('/{order}/reject',          [\App\Http\Controllers\AdminPayoutController::class, 'reject'])->name('reject');
    Route::post('/{order}/mark-paid',       [\App\Http\Controllers\AdminPayoutController::class, 'markPaid'])->name('markPaid');
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

// ── Public Order Tracking ─────────────────────────────────────────────────
Route::get('/track/{orderNumber}', [\App\Http\Controllers\TrackOrderController::class, 'show'])->name('track.order');

// ── Digital Wallet ────────────────────────────────────────────────────────
Route::middleware(['auth'])->prefix('wallet')->name('wallet.')->group(function () {
    Route::get('/',        [\App\Http\Controllers\WalletController::class, 'show'])->name('show');
    Route::post('/withdraw', [\App\Http\Controllers\WalletController::class, 'requestWithdrawal'])->name('withdraw');
});

// ── Admin Wallet Management ───────────────────────────────────────────────
Route::middleware(['auth', 'role:admin,ceo,finance'])->prefix('admin/wallets')->name('admin.wallets.')->group(function () {
    Route::get('/',                                    [\App\Http\Controllers\Admin\WalletManagementController::class, 'index'])->name('index');
    Route::get('/withdrawals',                         [\App\Http\Controllers\Admin\WalletManagementController::class, 'withdrawals'])->name('withdrawals');
    Route::post('/withdrawals/{transaction}/approve',  [\App\Http\Controllers\Admin\WalletManagementController::class, 'approveWithdrawal'])->name('withdrawals.approve');
    Route::post('/withdrawals/{transaction}/reject',   [\App\Http\Controllers\Admin\WalletManagementController::class, 'rejectWithdrawal'])->name('withdrawals.reject');
});

// ── AI Widget Proxy (authenticated) ───────────────────────────────────────
Route::middleware(['auth'])->group(function () {
    Route::post('/ai/weather', [\App\Http\Controllers\AiWidgetController::class, 'weather'])->name('ai.weather');
    Route::post('/ai/market',  [\App\Http\Controllers\AiWidgetController::class, 'market'])->name('ai.market');
    Route::post('/ai/chat',    [\App\Http\Controllers\AiWidgetController::class, 'chat'])->name('ai.chat');
});

// ── Two-Factor Authentication ──────────────────────────────────────────────
Route::get('/2fa/verify',  [\App\Http\Controllers\TwoFactorController::class, 'showVerify'])->name('2fa.verify');
Route::post('/2fa/verify', [\App\Http\Controllers\TwoFactorController::class, 'verify'])->name('2fa.verify.post')->middleware('throttle:10,1');
Route::post('/2fa/resend', [\App\Http\Controllers\TwoFactorController::class, 'resend'])->name('2fa.resend')->middleware('throttle:3,1');

// ── Login History & 2FA Toggle (auth required) ────────────────────────────
Route::middleware(['auth'])->group(function () {
    Route::get('/profile/security',   [\App\Http\Controllers\ProfileController::class, 'security'])->name('profile.security');
    Route::post('/profile/2fa/toggle',[\App\Http\Controllers\TwoFactorController::class, 'toggle'])->name('2fa.toggle');
});

// ── Scheduler Webhook (external cron trigger) ──────────────────────────────
// Protected by SCHEDULER_KEY env var. Hit this URL every minute from cron-job.org.
Route::get('/scheduler/run', function (\Illuminate\Http\Request $request) {
    $key = config('app.scheduler_key');
    if (!$key || $request->query('key') !== $key) {
        abort(403, 'Forbidden');
    }
    \Illuminate\Support\Facades\Artisan::call('schedule:run');
    return response('OK ' . now()->toIso8601String(), 200)
        ->header('Content-Type', 'text/plain');
})->name('scheduler.run');

// ── Knowledge Base (public read, admin CRUD) ──────────────────────────────
Route::middleware(['auth'])->prefix('knowledge')->name('knowledge.')->group(function () {
    Route::get('/',         [\App\Http\Controllers\KnowledgeBaseController::class, 'index'])->name('index');
    Route::get('/{slug}',   [\App\Http\Controllers\KnowledgeBaseController::class, 'show'])->name('show');
});

Route::middleware(['auth', 'role:admin,ceo,extension-officer'])->prefix('admin/knowledge')->name('admin.knowledge.')->group(function () {
    Route::get('/',                          [\App\Http\Controllers\Admin\KnowledgeBaseController::class, 'index'])->name('index');
    Route::get('/create',                    [\App\Http\Controllers\Admin\KnowledgeBaseController::class, 'create'])->name('create');
    Route::post('/',                         [\App\Http\Controllers\Admin\KnowledgeBaseController::class, 'store'])->name('store');
    Route::get('/{knowledgeArticle}/edit',   [\App\Http\Controllers\Admin\KnowledgeBaseController::class, 'edit'])->name('edit');
    Route::patch('/{knowledgeArticle}',      [\App\Http\Controllers\Admin\KnowledgeBaseController::class, 'update'])->name('update');
    Route::delete('/{knowledgeArticle}',     [\App\Http\Controllers\Admin\KnowledgeBaseController::class, 'destroy'])->name('destroy');
});

// ── Direct Messaging (all authenticated users) ────────────────────────────
Route::middleware(['auth'])->prefix('messages')->name('messages.')->group(function () {
    Route::get('/',         [\App\Http\Controllers\MessageController::class, 'index'])->name('index');
    Route::get('/{user}',   [\App\Http\Controllers\MessageController::class, 'show'])->name('show');
    Route::post('/{user}',  [\App\Http\Controllers\MessageController::class, 'send'])->name('send');
});

// ── In-app Feedback (all authenticated users) ─────────────────────────────
Route::middleware(['auth'])->post('/feedback', [\App\Http\Controllers\FeedbackController::class, 'store'])->name('feedback.store');

require __DIR__.'/auth.php';
