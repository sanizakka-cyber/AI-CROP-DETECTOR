<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentService
{
    private string $secretKey;
    private string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret_key');
        $this->baseUrl   = config('services.paystack.payment_url', 'https://api.paystack.co');
    }

    /**
     * Initialize a Paystack transaction and return authorization URL.
     * Used for redirect-based flow (mobile in-app browser, email links).
     */
    public function initialize(
        User   $user,
        int    $amountKobo,
        string $reference,
        string $callbackUrl,
        array  $metadata = []
    ): array {
        $response = Http::withToken($this->secretKey)
            ->post("{$this->baseUrl}/transaction/initialize", [
                'email'        => $user->email,
                'amount'       => $amountKobo,
                'reference'    => $reference,
                'currency'     => 'NGN',
                'callback_url' => $callbackUrl,
                'metadata'     => array_merge($metadata, ['user_id' => $user->id]),
            ]);

        if ($response->successful() && $response->json('status')) {
            return [
                'success'           => true,
                'authorization_url' => $response->json('data.authorization_url'),
                'access_code'       => $response->json('data.access_code'),
                'reference'         => $reference,
            ];
        }

        Log::error('Paystack init failed', ['response' => $response->json(), 'ref' => $reference]);
        return ['success' => false, 'message' => 'Payment initialization failed.'];
    }

    /**
     * Verify a transaction reference with Paystack.
     */
    public function verify(string $reference): array
    {
        $response = Http::withToken($this->secretKey)
            ->get("{$this->baseUrl}/transaction/verify/{$reference}");

        if (!$response->successful() || !$response->json('status')) {
            return ['success' => false, 'message' => 'Verification request failed.'];
        }

        $data = $response->json('data');

        if ($data['status'] !== 'success') {
            return ['success' => false, 'message' => 'Transaction was not successful.', 'data' => $data];
        }

        return ['success' => true, 'data' => $data];
    }

    /**
     * Record a payment in the database (pending state, before verification).
     */
    public function createPending(
        User   $user,
        string $reference,
        float  $amount,
        string $module,
        string $description,
        ?int   $moduleId = null,
        array  $metadata = []
    ): Payment {
        return Payment::create([
            'user_id'             => $user->id,
            'user_type'           => $user->role,
            'reference'           => $reference,
            'amount'              => $amount,
            'currency'            => 'NGN',
            'status'              => 'pending',
            'module'              => $module,
            'module_id'           => $moduleId,
            'description'         => $description,
            'metadata'            => $metadata,
            'verification_status' => 'unverified',
        ]);
    }

    /**
     * Mark a payment successful after Paystack verification, and generate receipt.
     */
    public function markSuccessful(Payment $payment, array $paystackData): Payment
    {
        // Prevent double-processing
        if ($payment->status === 'success') {
            return $payment;
        }

        $payment->update([
            'status'              => 'success',
            'transaction_id'      => (string) ($paystackData['id'] ?? ''),
            'payment_method'      => $paystackData['authorization']['card_type'] ?? $paystackData['channel'] ?? null,
            'channel'             => $paystackData['channel'] ?? null,
            'gateway_response'    => $paystackData['gateway_response'] ?? null,
            'verification_status' => 'verified',
            'verified_at'         => now(),
            'paid_at'             => isset($paystackData['paid_at'])
                ? \Carbon\Carbon::parse($paystackData['paid_at'])
                : now(),
            'receipt_number'      => Payment::generateReceiptNumber(),
            'metadata'            => array_merge(
                $payment->metadata ?? [],
                ['paystack' => $paystackData]
            ),
        ]);

        return $payment->fresh();
    }

    /**
     * Mark a payment as failed.
     */
    public function markFailed(Payment $payment, string $reason = ''): Payment
    {
        $payment->update([
            'status'              => 'failed',
            'verification_status' => 'failed',
            'gateway_response'    => $reason,
        ]);
        return $payment->fresh();
    }

    /**
     * Full initialize+record flow: creates pending record then returns authorization URL.
     */
    public function initiatePayment(
        User   $user,
        float  $amount,
        string $module,
        string $description,
        string $callbackUrl,
        ?int   $moduleId = null,
        array  $metadata = []
    ): array {
        $reference = Payment::generateReference('MSAS');
        $amountKobo = (int) round($amount * 100);

        // Create pending record
        $payment = $this->createPending($user, $reference, $amount, $module, $description, $moduleId, $metadata);

        // Initialize with Paystack
        $result = $this->initialize($user, $amountKobo, $reference, $callbackUrl, $metadata);

        if (!$result['success']) {
            $payment->update(['status' => 'failed']);
            return $result;
        }

        return array_merge($result, ['payment_id' => $payment->id]);
    }

    /**
     * Handle callback: verify + mark successful.
     * Returns ['success' => bool, 'payment' => Payment|null, 'message' => string]
     */
    public function handleCallback(string $reference, ?User $user = null): array
    {
        // Prevent duplicate processing
        $payment = Payment::where('reference', $reference)->first();

        if ($payment && $payment->status === 'success') {
            return ['success' => true, 'payment' => $payment, 'message' => 'Already processed.', 'duplicate' => true];
        }

        $verification = $this->verify($reference);
        if (!$verification['success']) {
            if ($payment) $this->markFailed($payment, $verification['message']);
            return ['success' => false, 'payment' => $payment, 'message' => $verification['message']];
        }

        $data = $verification['data'];

        // Create record if it doesn't exist (e.g. webhook arrived before callback)
        if (!$payment) {
            $meta   = $data['metadata'] ?? [];
            $userId = $meta['user_id'] ?? null;
            $owner  = $user ?? ($userId ? User::find($userId) : null);

            if (!$owner) {
                return ['success' => false, 'payment' => null, 'message' => 'User not found.'];
            }

            $payment = $this->createPending(
                $owner,
                $reference,
                ($data['amount'] ?? 0) / 100,
                $meta['module'] ?? 'unknown',
                $meta['description'] ?? 'Payment',
                $meta['module_id'] ?? null,
                $meta
            );
        }

        $payment = $this->markSuccessful($payment, $data);

        return ['success' => true, 'payment' => $payment, 'message' => 'Payment verified.'];
    }

    /**
     * Validate Paystack webhook signature.
     */
    public function validateWebhookSignature(string $payload, string $signature): bool
    {
        return hash_hmac('sha512', $payload, $this->secretKey) === $signature;
    }
}
