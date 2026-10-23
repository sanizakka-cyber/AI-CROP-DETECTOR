<?php

namespace App\Providers;

use App\Models\Payment;
use App\Models\User;
use App\Observers\UserObserver;
use App\Policies\PaymentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        Gate::policy(Payment::class, PaymentPolicy::class);
        User::observe(UserObserver::class);

        $this->constrainIdRouteParameters();
        $this->refuseLivePaymentKeysOutsideProduction();
    }

    /**
     * Phase 12.2: staging and every other non-production environment take
     * payments in test mode only. A live Paystack key there (copied by
     * mistake from production) is dropped, so payments are switched off
     * rather than charging a real card from a QA run.
     */
    private function refuseLivePaymentKeysOutsideProduction(): void
    {
        if ($this->app->isProduction()) {
            return;
        }
        $secret = (string) config('services.paystack.secret_key');
        $public = (string) config('services.paystack.public_key');
        if (str_starts_with($secret, 'sk_live_') || str_starts_with($public, 'pk_live_')) {
            config(['services.paystack.secret_key' => null, 'services.paystack.public_key' => null]);
            \Illuminate\Support\Facades\Log::warning('Live Paystack keys are set outside production; payments disabled. Use test keys (sk_test_/pk_test_).');
        }
    }

    /**
     * Phase 9 §A2: every one of these route parameters is a numeric
     * bigint primary key (either a plain id or an Eloquent route-bound
     * model) in every route across routes/web.php and routes/api.php
     * that uses it -- confirmed by reading each call site individually,
     * not assumed from the name. A non-numeric value like "abc" used to
     * reach the controller/database unconstrained, surfacing as a
     * Postgres 22P02 "invalid input syntax for type bigint" QueryException
     * or an int-typed controller argument TypeError -- both resulting in
     * an unhandled 500 instead of a clean 404, and both getting logged to
     * ErrorLog as if they were real application bugs.
     *
     * Route::pattern() applies globally to every route using that exact
     * parameter name: a non-matching segment now fails route matching
     * itself (-> a normal 404 via NotFoundHttpException) before the
     * controller or a database query ever sees it.
     *
     * Deliberately NOT included here because they are genuinely
     * non-numeric in this app: 'slug' (KnowledgeBaseController, a
     * string slug), 'code' (CEOController::deleteInviteCode, a string
     * invite code), 'type' (CEOController report type, a string enum),
     * and 'orderNumber' (TrackOrderController -- a 10-char generated
     * reference, not a database id; see Order::generateNumber()).
     */
    private function constrainIdRouteParameters(): void
    {
        $numeric = [
            'id', 'order', 'productId', 'product', 'consultation', 'diagnosis',
            'user', 'document', 'subscription', 'ticket', 'staffRole',
            'transaction', 'payment', 'finance', 'payroll', 'feedback',
            'review', 'cartItem', 'knowledgeArticle', 'vehicle', 'driver',
            'device', 'delivery', 'leave', 'task',
        ];

        foreach ($numeric as $param) {
            Route::pattern($param, '[0-9]+');
        }
    }
}
