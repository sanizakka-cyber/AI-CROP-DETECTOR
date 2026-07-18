<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    /**
     * Handle all Paystack webhook events.
     * CSRF-exempt (configured in bootstrap/app.php).
     */
    public function paystack(Request $request)
    {
        $signature = $request->header('x-paystack-signature');
        $body      = $request->getContent();

        if (!$this->paymentService->validateWebhookSignature($body, $signature ?? '')) {
            Log::warning('Paystack webhook: invalid signature');
            return response()->json(['status' => 'invalid signature'], 401);
        }

        $event = $request->json('event');
        $data  = $request->json('data');

        Log::info('Paystack webhook received', ['event' => $event, 'ref' => $data['reference'] ?? null]);

        match ($event) {
            'charge.success'       => $this->handleChargeSuccess($data),
            'transfer.success'     => $this->handleTransferSuccess($data),
            'transfer.failed'      => $this->handleTransferFailed($data),
            'refund.processed'     => $this->handleRefund($data),
            default                => null,
        };

        return response()->json(['status' => 'ok']);
    }

    private function handleChargeSuccess(array $data): void
    {
        $reference = $data['reference'] ?? null;
        if (!$reference) return;

        $payment = Payment::where('reference', $reference)->first();

        // Already processed
        if ($payment && $payment->status === 'success') return;

        $meta   = $data['metadata'] ?? [];
        $userId = $meta['user_id'] ?? null;
        $user   = $userId ? \App\Models\User::find($userId) : null;

        $result = $this->paymentService->handleCallback($reference, $user);

        if ($result['success'] && $result['payment'] && !($result['duplicate'] ?? false)) {
            $this->activateService($result['payment']);
        }
    }

    private function handleTransferSuccess(array $data): void
    {
        // Future: handle payout/transfer confirmations
        Log::info('Paystack transfer.success', ['data' => $data]);
    }

    private function handleTransferFailed(array $data): void
    {
        Log::warning('Paystack transfer.failed', ['data' => $data]);
    }

    private function handleRefund(array $data): void
    {
        $reference = $data['reference'] ?? null;
        if (!$reference) return;

        Payment::where('reference', $reference)
            ->update(['status' => 'refunded']);

        Log::info('Refund processed', ['reference' => $reference]);
    }

    private function activateService(Payment $payment): void
    {
        try {
            match ($payment->module) {
                'subscription' => $this->activateSubscription($payment),
                'consultation' => $this->activateConsultation($payment),
                'marketplace'  => $this->activateOrder($payment),
                default        => null,
            };
        } catch (\Throwable $e) {
            Log::error('Webhook service activation failed', [
                'payment_id' => $payment->id,
                'module'     => $payment->module,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    private function activateSubscription(Payment $payment): void
    {
        $meta  = $payment->metadata ?? [];
        $plan  = $meta['plan'] ?? null;
        $cycle = $meta['billing_cycle'] ?? 'monthly';
        if (!$plan) return;

        $user   = $payment->user;
        $months = $cycle === 'yearly' ? 12 : 1;

        $exists = $user->subscriptions()->where('payment_reference', $payment->reference)->exists();
        if ($exists) return;

        $active = $user->activeSubscription();
        if ($active && $active->plan !== $plan) {
            $active->update(['status' => 'cancelled', 'cancelled_at' => now(), 'cancellation_reason' => 'Upgraded via webhook']);
        }

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

    private function activateConsultation(Payment $payment): void
    {
        if (!$payment->module_id) return;
        \App\Models\Consultation::where('id', $payment->module_id)
            ->update(['payment_status' => 'paid', 'status' => 'open']);
    }

    private function activateOrder(Payment $payment): void
    {
        if (!$payment->module_id) return;
        \App\Models\Order::where('id', $payment->module_id)
            ->update(['status' => 'confirmed', 'paid_at' => now()]);
    }
}
