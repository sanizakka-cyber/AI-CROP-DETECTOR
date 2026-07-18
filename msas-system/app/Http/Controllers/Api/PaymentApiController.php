<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentApiController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    /**
     * Initialize payment — returns authorization URL for mobile in-app browser.
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

        $user = $request->user();

        $result = $this->paymentService->initiatePayment(
            user:        $user,
            amount:      (float) $request->amount,
            module:      $request->module,
            description: $request->description,
            callbackUrl: config('app.url') . '/api/payment/mobile-callback',
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
            'success'           => true,
            'authorization_url' => $result['authorization_url'],
            'reference'         => $result['reference'],
            'payment_id'        => $result['payment_id'],
        ]);
    }

    /**
     * Verify payment after mobile in-app browser completes.
     */
    public function verify(Request $request)
    {
        $request->validate(['reference' => 'required|string']);

        $result = $this->paymentService->handleCallback($request->reference, $request->user());

        if (!$result['success']) {
            return response()->json(['success' => false, 'message' => $result['message']], 422);
        }

        $payment = $result['payment'];

        return response()->json([
            'success'    => true,
            'payment'    => $this->formatPayment($payment),
            'duplicate'  => $result['duplicate'] ?? false,
        ]);
    }

    /**
     * User payment history.
     */
    public function history(Request $request)
    {
        $query = $request->user()->payments()->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        $payments = $query->paginate(20);

        return response()->json([
            'success'  => true,
            'payments' => $payments->items(),
            'meta'     => [
                'total'        => $payments->total(),
                'current_page' => $payments->currentPage(),
                'last_page'    => $payments->lastPage(),
            ],
        ]);
    }

    /**
     * Get a specific payment receipt.
     */
    public function receipt(Request $request, Payment $payment)
    {
        if ($payment->user_id !== $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        return response()->json(['success' => true, 'payment' => $this->formatPayment($payment)]);
    }

    /**
     * Mobile callback — called after in-app browser finishes (GET redirect).
     * Verifies and returns JSON for the app to parse.
     */
    public function mobileCallback(Request $request)
    {
        $reference = $request->query('reference') ?? $request->query('trxref');

        if (!$reference) {
            return response()->json(['success' => false, 'message' => 'No reference provided.'], 400);
        }

        $result = $this->paymentService->handleCallback($reference);

        return response()->json([
            'success'   => $result['success'],
            'message'   => $result['message'],
            'payment'   => $result['payment'] ? $this->formatPayment($result['payment']) : null,
        ], $result['success'] ? 200 : 422);
    }

    private function formatPayment(Payment $payment): array
    {
        return [
            'id'                  => $payment->id,
            'reference'           => $payment->reference,
            'transaction_id'      => $payment->transaction_id,
            'amount'              => $payment->amount,
            'formatted_amount'    => $payment->formattedAmount(),
            'currency'            => $payment->currency,
            'status'              => $payment->status,
            'module'              => $payment->module,
            'module_id'           => $payment->module_id,
            'description'         => $payment->description,
            'payment_method'      => $payment->payment_method,
            'channel'             => $payment->channel,
            'receipt_number'      => $payment->receipt_number,
            'verification_status' => $payment->verification_status,
            'paid_at'             => $payment->paid_at?->toIso8601String(),
            'created_at'          => $payment->created_at->toIso8601String(),
        ];
    }
}
