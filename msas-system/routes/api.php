<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\DiagnoseApiController;
use App\Http\Controllers\Api\ProductApiController;
use App\Http\Controllers\Api\CartApiController;
use App\Http\Controllers\Api\OrderApiController;
use App\Models\SubscriptionUsage;
use Illuminate\Support\Facades\Route;

// ── Health / connectivity check (unauthenticated) ────────────────────────────
Route::get('/health', function () {
    return response()->json([
        'status'  => 'ok',
        'app'     => config('app.name'),
        'version' => '1.0',
        'time'    => now()->toIso8601String(),
        'env'     => app()->environment(),
    ]);
});

// ── Public Auth ───────────────────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/login',    [AuthApiController::class, 'login']);
    Route::post('/register', [AuthApiController::class, 'register']);
});

// ── Sanctum-protected routes ──────────────────────────────────────────────────
Route::middleware('auth.api')->group(function () {

    // Auth
    Route::get('/auth/me',   [AuthApiController::class, 'me']);
    Route::post('/auth/logout', [AuthApiController::class, 'logout']);

    // AI Diagnosis
    Route::post('/diagnose/crop',      [DiagnoseApiController::class, 'crop']);
    Route::post('/diagnose/livestock', [DiagnoseApiController::class, 'livestock']);
    Route::get('/diagnose',            [DiagnoseApiController::class, 'history']);
    Route::get('/diagnose/{id}',       [DiagnoseApiController::class, 'show']);
    Route::patch('/diagnose/{id}/feedback', [DiagnoseApiController::class, 'feedback']);

    // Placeholder stubs — return empty data so the app doesn't crash
    Route::get('/farms',          fn() => response()->json(['data' => []]));
    Route::post('/farms',         fn() => response()->json(['message' => 'Coming soon'], 501));
    Route::get('/animals',        fn() => response()->json(['data' => []]));
    Route::post('/animals',       fn() => response()->json(['message' => 'Coming soon'], 501));
    Route::get('/analytics/summary',       fn() => response()->json(['summary' => ['total'=>0,'processed'=>0,'crop'=>0,'livestock'=>0,'recent'=>[]]]));
    Route::get('/analytics/admin-summary', fn() => response()->json(['summary' => ['users'=>['total'=>0,'farmers'=>0,'vets'=>0,'agronomists'=>0,'pendingExperts'=>0,'activeMonthly'=>0],'scans'=>['total'=>0,'processed'=>0,'expertReviews'=>0,'processingRate'=>0],'consultations'=>['total'=>0,'completed'=>0,'completionRate'=>0],'treatment'=>['successRate'=>0]]]));
    Route::get('/analytics/outbreaks',     fn() => response()->json(['outbreaks' => []]));
    Route::get('/analytics/outcomes',      fn() => response()->json(['outcomes'  => []]));
    Route::get('/analytics/insurability',  fn() => response()->json(['creditScore' => null, 'tier' => null]));
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
});
