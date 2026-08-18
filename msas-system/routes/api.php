<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\DiagnoseApiController;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\Api\CartApiController;
use App\Http\Controllers\Api\OrderApiController;
use App\Http\Controllers\Api\AnalyticsApiController;
use App\Http\Controllers\Api\FarmApiController;
use App\Http\Controllers\Api\AnimalApiController;
use App\Http\Controllers\Api\PoultryApiController;
use App\Http\Controllers\Api\WeatherApiController;
use App\Http\Controllers\Api\NotificationApiController;
use App\Http\Controllers\Api\PaymentApiController;
use App\Http\Controllers\Api\ConsultationApiController;
use App\Http\Controllers\Api\RiderApiController;
use App\Http\Controllers\Api\WalletApiController;
use App\Http\Controllers\Api\KnowledgeBaseApiController;
use App\Http\Controllers\Api\SubscriptionApiController;
use App\Http\Controllers\Api\OrderTrackingApiController;
use App\Http\Controllers\Api\MessageApiController;
use App\Models\SubscriptionUsage;
use Illuminate\Support\Facades\Route;

// ── Health / monitoring endpoint (unauthenticated — used by Render + uptime monitors) ──
Route::get('/health', function () {
    $checks  = [];
    $healthy = true;

    // Database
    try {
        \Illuminate\Support\Facades\DB::select('SELECT 1');
        $checks['database'] = 'ok';
    } catch (\Throwable $e) {
        $checks['database'] = 'error: ' . $e->getMessage();
        $healthy = false;
    }

    // Queue depth (failed jobs are a monitoring signal)
    try {
        $checks['failed_jobs'] = (int) \Illuminate\Support\Facades\DB::table('failed_jobs')->count();
    } catch (\Throwable) {
        $checks['failed_jobs'] = null;
    }

    // Pending jobs depth
    try {
        $checks['pending_jobs'] = (int) \Illuminate\Support\Facades\DB::table('jobs')->count();
    } catch (\Throwable) {
        $checks['pending_jobs'] = null;
    }

    // AI engine reachability (fast timeout — non-fatal)
    try {
        $aiUrl = rtrim(config('services.ai_engine.url', ''), '/');
        if ($aiUrl) {
            $aiKey = config('services.ai_engine.key', '');
            $guzzle = new \GuzzleHttp\Client(['timeout' => 4, 'http_errors' => false]);
            $headers = $aiKey ? ['Authorization' => "Bearer {$aiKey}"] : [];
            $resp = $guzzle->get("{$aiUrl}/health", ['headers' => $headers]);
            $checks['ai_engine'] = $resp->getStatusCode() < 500 ? 'ok' : 'degraded';
        } else {
            $checks['ai_engine'] = 'not_configured';
        }
    } catch (\Throwable) {
        $checks['ai_engine'] = 'unreachable';
    }

    // Recent AI scan failures (last 15 min)
    try {
        $checks['ai_scan_failures_15m'] = (int) \App\Models\Diagnosis::where('status', 'needs_review')
            ->where('created_at', '>=', now()->subMinutes(15))
            ->count();
    } catch (\Throwable) {
        $checks['ai_scan_failures_15m'] = null;
    }

    // Recent payment failures (last hour)
    try {
        $checks['payment_failures_1h'] = (int) \App\Models\Payment::where('status', 'failed')
            ->where('created_at', '>=', now()->subHour())
            ->count();
    } catch (\Throwable) {
        $checks['payment_failures_1h'] = null;
    }

    // Public callers (uptime monitors, Render) only need ok/degraded.
    // Detailed metrics are restricted to authenticated staff.
    $isAuthenticated = request()->bearerToken()
        && \App\Models\User::where('api_token', hash('sha256', request()->bearerToken()))
            ->whereIn('role', ['ceo','admin','operations','finance','data-analyst'])
            ->exists();

    $payload = ['status' => $healthy ? 'ok' : 'degraded', 'time' => now()->toIso8601String()];
    if ($isAuthenticated) {
        $payload += ['app' => config('app.name'), 'env' => config('app.env'), 'checks' => $checks];
    }

    return response()->json($payload, $healthy ? 200 : 503);
});

// ── Public Auth (rate-limited: 5 attempts per minute per IP) ─────────────────
Route::prefix('auth')->middleware('throttle:5,1')->group(function () {
    Route::post('/login',    [AuthApiController::class, 'login']);
    Route::post('/register', [AuthApiController::class, 'register']);
});

// ── Sanctum-protected routes ──────────────────────────────────────────────────
Route::middleware('auth.api')->group(function () {

    // Auth
    Route::get('/auth/me',      [AuthApiController::class, 'me']);
    Route::post('/auth/logout', [AuthApiController::class, 'logout']);
    Route::patch('/auth/profile',   [AuthApiController::class, 'updateProfile']);
    Route::post('/auth/fcm-token',  [AuthApiController::class, 'updateFcmToken']);

    // Weather (cached, uses Open-Meteo)
    Route::get('/weather', [WeatherApiController::class, 'current']);

    // Notifications
    Route::get('/notifications',                [NotificationApiController::class, 'index']);
    Route::patch('/notifications/{id}/read',    [NotificationApiController::class, 'markRead']);
    Route::post('/notifications/read-all',      [NotificationApiController::class, 'markAllRead']);
    Route::delete('/notifications/{id}',        [NotificationApiController::class, 'destroy']);

    // AI Diagnosis (rate-limited: 20 scans per minute per user)
    Route::middleware('throttle:20,1')->group(function () {
        Route::post('/diagnose/crop',      [DiagnoseApiController::class, 'crop']);
        Route::post('/diagnose/livestock', [DiagnoseApiController::class, 'livestock']);
    });
    Route::get('/diagnose',            [DiagnoseApiController::class, 'history']);
    Route::get('/diagnose/{id}',       [DiagnoseApiController::class, 'show']);
    Route::patch('/diagnose/{id}/feedback', [DiagnoseApiController::class, 'feedback']);

    // Farms CRUD
    Route::get('/farms',              [FarmApiController::class, 'index']);
    Route::post('/farms',             [FarmApiController::class, 'store']);
    Route::get('/farms/{id}',         [FarmApiController::class, 'show']);
    Route::put('/farms/{id}',         [FarmApiController::class, 'update']);
    Route::delete('/farms/{id}',      [FarmApiController::class, 'destroy']);

    // Animals CRUD
    Route::get('/animals',            [AnimalApiController::class, 'index']);
    Route::post('/animals',           [AnimalApiController::class, 'store']);
    Route::get('/animals/{id}',       [AnimalApiController::class, 'show']);
    Route::put('/animals/{id}',       [AnimalApiController::class, 'update']);
    Route::delete('/animals/{id}',    [AnimalApiController::class, 'destroy']);

    // Poultry CRUD
    Route::get('/poultry',                         [PoultryApiController::class, 'index']);
    Route::post('/poultry',                        [PoultryApiController::class, 'store']);
    Route::get('/poultry/{id}',                    [PoultryApiController::class, 'show']);
    Route::put('/poultry/{id}',                    [PoultryApiController::class, 'update']);
    Route::delete('/poultry/{id}',                 [PoultryApiController::class, 'destroy']);
    Route::post('/poultry/{id}/mortality',         [PoultryApiController::class, 'logMortality']);

    // Analytics
    Route::get('/analytics/summary',       [AnalyticsApiController::class, 'summary']);
    Route::get('/analytics/admin-summary', [AnalyticsApiController::class, 'adminSummary']);
    Route::get('/analytics/outbreaks',     [AnalyticsApiController::class, 'outbreaks']);
    Route::get('/analytics/outcomes',      [AnalyticsApiController::class, 'outcomes']);
    Route::get('/analytics/insurability',  [AnalyticsApiController::class, 'insurability']);
    // ── Marketplace / Products ────────────────────────────────────────────────
    Route::get('/marketplace/products',         [ProductApiController::class, 'index']);
    Route::get('/marketplace/products/categories', [ProductApiController::class, 'categories']);
    Route::get('/marketplace/products/recommended', [ProductApiController::class, 'recommended']);
    Route::get('/marketplace/products/{product}', [ProductApiController::class, 'show']);
    Route::post('/marketplace/products/{product}/reviews', [ProductApiController::class, 'addReview']);

    // ── Dealer product management ─────────────────────────────────────────────
    Route::get('/dealer/products',              [ProductApiController::class, 'myProducts']);
    Route::post('/dealer/products',             [ProductApiController::class, 'store']);
    Route::put('/dealer/products/{product}',    [ProductApiController::class, 'update']);
    Route::delete('/dealer/products/{product}', [ProductApiController::class, 'destroy']);
    Route::patch('/dealer/products/{product}/stock', [ProductApiController::class, 'adjustStock']);
    Route::get('/dealer/orders',                [OrderApiController::class, 'dealerOrders']);
    Route::patch('/dealer/orders/{order}/status', [OrderApiController::class, 'updateStatus']);
    Route::patch('/dealer/orders/{order}/paid',   [OrderApiController::class, 'markPaid']);

    // ── Cart ──────────────────────────────────────────────────────────────────
    Route::get('/cart',                         [CartApiController::class, 'index']);
    Route::post('/cart',                        [CartApiController::class, 'add']);
    Route::put('/cart/{cartItem}',              [CartApiController::class, 'update']);
    Route::delete('/cart/{cartItem}',           [CartApiController::class, 'remove']);
    Route::delete('/cart',                      [CartApiController::class, 'clear']);

    // ── Orders ────────────────────────────────────────────────────────────────
    Route::get('/orders',                       [OrderApiController::class, 'myOrders']);
    Route::get('/orders/{order}',               [OrderApiController::class, 'show']);
    Route::post('/orders/checkout',             [OrderApiController::class, 'checkout']);
    Route::post('/orders/{order}/cancel',       [OrderApiController::class, 'cancel']);

    // Subscription status for mobile
    Route::get('/subscription/status', function () {
        $user   = auth()->user();
        $sub    = $user->activeSubscription();
        $period = now()->format('Y-m');
        $usage  = [];
        if ($sub) {
            foreach (['livestock_records', 'reports_generated', 'ai_scans_per_month'] as $key) {
                $usage[$key] = SubscriptionUsage::getCount($user->id, $key, $period);
            }
        }
        return response()->json([
            'subscription' => $sub,
            'usage'        => $usage,
        ]);
    });

    // Quick subscribe via mobile (initiates trial or returns payment URL)
    Route::post('/subscription/trial', function (Illuminate\Http\Request $request) {
        $user = auth()->user();
        if ($user->role !== 'farmer') {
            return response()->json(['error' => 'Only farmers can subscribe.'], 403);
        }
        $plan = $request->input('plan', 'basic');
        if (!array_key_exists($plan, config('subscription.plans'))) {
            return response()->json(['error' => 'Invalid plan.'], 422);
        }
        $hadTrial = $user->subscriptions()->where('plan', $plan)->where('status', 'trial')->exists();
        if ($hadTrial || $user->activeSubscription()) {
            return response()->json(['error' => 'Trial or subscription already exists.'], 409);
        }
        $sub = $user->startTrial($plan);
        return response()->json(['subscription' => $sub, 'message' => '14-day free trial started!'], 201);
    });

    // ── Direct Messaging ──────────────────────────────────────────────────────
    Route::prefix('messages')->group(function () {
        Route::get('/',        [MessageApiController::class, 'index']);
        Route::get('/{user}',  [MessageApiController::class, 'show']);
        Route::post('/{user}', [MessageApiController::class, 'send']);
    });

    // ── Consultations ─────────────────────────────────────────────────────────
    Route::prefix('consultations')->group(function () {
        Route::get('/',                           [ConsultationApiController::class, 'index']);
        Route::get('/{consultation}',             [ConsultationApiController::class, 'show']);
        Route::post('/vet',                       [ConsultationApiController::class, 'storeVet']);
        Route::post('/agro',                      [ConsultationApiController::class, 'storeAgro']);
    });
    Route::prefix('vet')->group(function () {
        Route::get('/queue',                                        [ConsultationApiController::class, 'vetQueue']);
        Route::post('/consultations/{consultation}/respond',        [ConsultationApiController::class, 'vetRespond']);
        Route::post('/consultations/{consultation}/start-video',    [ConsultationApiController::class, 'vetStartVideo']);
    });

    // ── Rider ─────────────────────────────────────────────────────────────────
    Route::prefix('rider')->group(function () {
        Route::get('/orders',                      [RiderApiController::class, 'orders']);
        Route::post('/orders/{order}/accept',      [RiderApiController::class, 'accept']);
        Route::post('/orders/{order}/decline',     [RiderApiController::class, 'decline']);
        Route::post('/orders/{order}/in-transit',  [RiderApiController::class, 'markInTransit']);
        Route::post('/orders/{order}/delivered',   [RiderApiController::class, 'markDelivered']);
        Route::patch('/availability',              [RiderApiController::class, 'updateAvailability']);
        Route::get('/status',                      [RiderApiController::class, 'status']);
    });

    // ── Wallet ────────────────────────────────────────────────────────────────
    Route::prefix('wallet')->group(function () {
        Route::get('/',            [WalletApiController::class, 'balance']);
        Route::post('/withdraw',   [WalletApiController::class, 'requestWithdrawal']);
    });

    // ── Knowledge Base (authenticated browse) ────────────────────────────────
    Route::prefix('knowledge')->group(function () {
        Route::get('/',        [KnowledgeBaseApiController::class, 'index']);
        Route::get('/{slug}',  [KnowledgeBaseApiController::class, 'show']);
    });

    // ── Subscription (full API) ───────────────────────────────────────────────
    Route::prefix('subscription')->group(function () {
        Route::get('/plans',   [SubscriptionApiController::class, 'plans']);
        Route::post('/subscribe', [SubscriptionApiController::class, 'subscribe'])->middleware('throttle:10,1');
        Route::post('/cancel', [SubscriptionApiController::class, 'cancel']);
        Route::get('/my',      [SubscriptionApiController::class, 'status']);
    });

    // ── Payments ────────────────────────────────────────────────────────────
    // Throttled: 'initiate'/'verify' accept a user-supplied reference and sit
    // behind auth only — without a limit an authenticated attacker could
    // hammer 'verify' guessing references or spam 'initiate' with pending
    // Paystack transactions.
    Route::prefix('payment')->name('api.payment.')->middleware('throttle:30,1')->group(function () {
        Route::post('/initiate',         [PaymentApiController::class, 'initiate'])->name('initiate');
        Route::post('/verify',           [PaymentApiController::class, 'verify'])->name('verify');
        Route::get('/history',           [PaymentApiController::class, 'history'])->name('history');
        Route::get('/receipt/{payment}', [PaymentApiController::class, 'receipt'])->name('receipt');
    });
});

// Mobile payment callback — Paystack redirects here after in-app browser; throttled to deter reference enumeration
Route::get('/payment/mobile-callback', [PaymentApiController::class, 'mobileCallback'])
    ->middleware('throttle:20,1')
    ->name('api.payment.mobile-callback');

// Mobile subscription callback — same pattern as the payment callback above
Route::get('/subscription/paystack-callback', [SubscriptionApiController::class, 'paystackCallback'])
    ->middleware('throttle:20,1')
    ->name('api.subscription.paystack-callback');

// ── Public Order Tracking (no auth required) ──────────────────────────────────
Route::get('/track/{orderNumber}', [OrderTrackingApiController::class, 'track'])
    ->middleware('throttle:30,1')
    ->name('api.track');

// ── Public Knowledge Base (unauthenticated read) ─────────────────────────────
Route::prefix('kb')->middleware('throttle:60,1')->group(function () {
    Route::get('/',        [KnowledgeBaseApiController::class, 'index']);
    Route::get('/{slug}',  [KnowledgeBaseApiController::class, 'show']);
});
