<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    /**
     * Initiate a payment via Paystack Inline JS.
     * Returns JSON with access_code and public key for the frontend popup.
     */
    public function initiate(Request $request)
    {
        $request->validate([
            'module'      => 'required|string',
            'module_id'   => 'nullable|integer',
            'amount'      => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
            'metadata'    => 'nullable|array',
        ]);

        $user   = auth()->user();
        $amount = (float) $request->amount;

        // Server-side amount validation — never trust client amount for real services
        $validatedAmount = $this->validateAmount($request->module, $request->module_id, $amount, $request->metadata ?? []);
        if ($validatedAmount === false) {
            return response()->json(['success' => false, 'message' => 'Invalid payment amount.'], 422);
        }

        $result = $this->paymentService->initiatePayment(
            user:        $user,
            amount:      $validatedAmount,
            module:      $request->module,
            description: $request->description,
            callbackUrl: route('payment.callback'),
            moduleId:    $request->module_id,
            metadata:    array_merge($request->metadata ?? [], [
                'module'      => $request->module,
                'module_id'   => $request->module_id,
                'description' => $request->description,
            ])
        );

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], 500);
        }

        return response()->json([
            'success'      => true,
            'access_code'  => $result['access_code'],
            'reference'    => $result['reference'],
            'public_key'   => config('services.paystack.public_key'),
            'email'        => $user->email,
            'amount_kobo'  => (int) round($validatedAmount * 100),
            'description'  => $request->description,
        ]);
    }

    /**
     * Paystack redirects here after payment (for redirect-based flow).
     */
    public function callback(Request $request)
    {
        $reference = $request->query('reference') ?? $request->query('trxref');

        if (!$reference) {
            return redirect()->back()->with('error', 'No payment reference received.');
        }

        $result = $this->paymentService->handleCallback($reference, auth()->user());

        if (!$result['success']) {
            return redirect()->route('payment.history')
                ->with('error', 'Payment verification failed. If debited, contact support with ref: ' . $reference);
        }

        if ($result['duplicate'] ?? false) {
            return redirect()->route('payment.receipt', $result['payment']->id)
                ->with('info', 'This payment was already processed.');
        }

        $payment = $result['payment'];
        $this->activateService($payment);

        return redirect()->route('payment.receipt', $payment->id)
            ->with('success', 'Payment successful! Your service has been activated.');
    }

    /**
     * Verify via AJAX (Inline JS success callback calls this).
     */
    public function verify(Request $request)
    {
        $request->validate(['reference' => 'required|string']);

        $result = $this->paymentService->handleCallback($request->reference, auth()->user());

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], 422);
        }

        $payment = $result['payment'];

        if (!($result['duplicate'] ?? false)) {
            $this->activateService($payment);
        }

        return response()->json([
            'success'    => true,
            'payment_id' => $payment->id,
            'receipt_url'=> route('payment.receipt', $payment->id),
            'message'    => 'Payment verified successfully.',
        ]);
    }

    /**
     * Show receipt for a payment.
     */
    public function receipt(Payment $payment)
    {
        $this->authorize('view', $payment);
        return view('payments.receipt', compact('payment'));
    }

    /**
     * User payment history.
     */
    public function history(Request $request)
    {
        $query = auth()->user()->payments()->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        $payments = $query->paginate(20)->withQueryString();

        $modules = auth()->user()->payments()
            ->select('module')->distinct()->pluck('module');

        return view('payments.history', compact('payments', 'modules'));
    }

    /**
     * Activate the purchased service after successful payment.
     */
    private function activateService(Payment $payment): void
    {
        try {
            match ($payment->module) {
                'subscription'  => $this->activateSubscription($payment),
                'consultation'  => $this->activateConsultation($payment),
                'marketplace'   => $this->activateMarketplaceOrder($payment),
                default         => Log::info("Payment for module '{$payment->module}' recorded; no auto-activation."),
            };
        } catch (\Throwable $e) {
            Log::error('Service activation failed after payment', [
                'payment_id' => $payment->id,
                'module'     => $payment->module,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    private function activateSubscription(Payment $payment): void
    {
        $meta = $payment->metadata ?? [];
        if (empty($meta['plan'])) return;

        $user = $payment->user;
        $plan  = $meta['plan'];
        $cycle = $meta['billing_cycle'] ?? 'monthly';
        $months = $cycle === 'yearly' ? 12 : 1;

        $activeSub = $user->activeSubscription();
        if ($activeSub && $activeSub->plan !== $plan) {
            $activeSub->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancellation_reason' => 'Upgraded']);
        }

        // Avoid duplicate activation
        $exists = $user->subscriptions()->where('payment_reference', $payment->reference)->exists();
        if (!$exists) {
            $user->subscriptions()->create([
                'plan'              => $plan,
                'status'            => 'active',
                'billing_cycle'     => $cycle,
                'starts_at'         => now(),
                'ends_at'           => now()->addMonths($months),
                'amount_paid'       => $payment->amount,
                'payment_reference' => $payment->reference,
                'payment_method'    => 'paystack',
            ]);
        }
    }

    private function activateConsultation(Payment $payment): void
    {
        if (!$payment->module_id) return;

        \App\Models\Consultation::where('id', $payment->module_id)
            ->update(['payment_status' => 'paid', 'status' => 'open']);
    }

    private function activateMarketplaceOrder(Payment $payment): void
    {
        if (!$payment->module_id) return;

        \App\Models\Order::where('id', $payment->module_id)
            ->update(['status' => 'confirmed', 'confirmed_at' => now()]);
    }

    /**
     * Validate amount server-side based on module.
     * Returns correct amount in NGN, or false if invalid.
     */
    private function validateAmount(string $module, ?int $moduleId, float $clientAmount, array $metadata = []): float|false
    {
        return match ($module) {
            'subscription' => $this->validateSubscriptionAmount($clientAmount, $metadata),
            'consultation' => $clientAmount > 0 ? $clientAmount : false,
            'marketplace'  => $moduleId
                ? (\App\Models\Order::find($moduleId)?->total ?? false)
                : false,
            default => $clientAmount > 0 ? $clientAmount : false,
        };
    }

    private function validateSubscriptionAmount(float $clientAmount, array $metadata): float|false
    {
        $plan  = $metadata['plan']          ?? null;
        $cycle = $metadata['billing_cycle'] ?? 'monthly';

        if (! $plan) {
            return $clientAmount > 0 ? $clientAmount : false;
        }

        $price = config("subscription.plans.{$plan}.price.{$cycle}");
        if (! $price) {
            return false;
        }

        // Allow ±1 NGN tolerance for rounding differences
        if (abs($clientAmount - $price) > 1) {
            Log::warning('Subscription payment amount mismatch', [
                'client_amount' => $clientAmount,
                'expected'      => $price,
                'plan'          => $plan,
                'cycle'         => $cycle,
            ]);
            return false;
        }

        return (float) $price;
    }
}
