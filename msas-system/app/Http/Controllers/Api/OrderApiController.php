<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Finance;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderApiController extends Controller
{
    // ── Buyer routes ────────────────────────────────────────────────────────────

    public function myOrders(Request $request)
    {
        if (auth()->user()->role !== 'farmer') {
            return response()->json(['error' => 'Only farmers can view buyer orders.'], 403);
        }

        $orders = Order::with('items')
            ->where('buyer_id', auth()->id())
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json([
            'data' => $orders->items(),
            'meta' => ['total' => $orders->total()],
        ]);
    }

    public function show(Order $order)
    {
        $user = auth()->user();
        if ($order->buyer_id !== $user->id && $order->dealer_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        // 'name' on User is a computed accessor (first/middle/last), not a DB column —
        // selecting it in a constrained eager-load throws "column users.name does not exist".
        $order->load(['items', 'buyer:id,first_name,middle_name,last_name,email', 'dealer:id,first_name,middle_name,last_name']);
        $order->buyer?->append('name');
        $order->dealer?->append('name');
        return response()->json(['data' => $order]);
    }

    public function checkout(Request $request)
    {
        if (auth()->user()->role !== 'farmer') {
            return response()->json(['error' => 'Only farmers can place marketplace orders.'], 403);
        }

        $data = $request->validate([
            'delivery_address'  => 'nullable|string|max:500',
            'delivery_notes'    => 'nullable|string|max:500',
            // 'wallet' was accepted here but never implemented -- checkout()
            // has no branch that debits a buyer's wallet for any payment
            // method, so a client sending payment_method=wallet got an
            // order stuck pending/unpaid with nothing to ever resolve it.
            // No app UI sends this value (mobile offers only paystack and
            // transfer); removed until a real wallet-debit flow exists.
            //
            // 'flutterwave' was the same bug, worse: the mobile cart UI
            // actively offered it as a selectable option (unlike 'wallet'),
            // but there is no Flutterwave integration anywhere in this
            // codebase at all -- no HTTP calls, no controller logic, and
            // FLUTTERWAVE_SECRET_KEY is unset even in production
            // (render.yaml). A buyer who picked it got an order stuck
            // unpaid with literally no code path that could ever resolve
            // it. Removed from both this validation and the mobile UI's
            // PAYMENT_METHODS list together. 'ussd'/'card' are left as-is:
            // dead the same way, but no app UI sends them today, unlike
            // this one.
            'payment_method'    => 'required|in:paystack,transfer,ussd,card',
            'payment_channel'   => 'nullable|string|in:gtbank,access,uba,firstbank,zenith,opay,palmpay,kuda,moniepoint,bank_app,pos',
            'discount_code'     => 'nullable|string',
        ]);

        if ($data['payment_method'] === 'transfer' && empty($data['payment_channel'])) {
            return response()->json([
                'error' => 'Please select a supported bank, wallet, or transfer channel before placing this order.',
                'payment_channels' => self::transferChannels(),
            ], 422);
        }

        $user      = auth()->user();
        $cartItems = CartItem::with('product')->where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['error' => 'Cart is empty'], 422);
        }

        // Validate stock for all items
        foreach ($cartItems as $item) {
            if (!$item->product || $item->product->status !== 'active') {
                return response()->json(['error' => "Product '{$item->product?->name}' is no longer available"], 422);
            }
            if ($item->product->quantity_in_stock < $item->quantity) {
                return response()->json(['error' => "Insufficient stock for '{$item->product->name}'"], 422);
            }
        }

        try {
            [$orders, $total] = DB::transaction(function () use ($cartItems, $user, $data) {
            $subtotal = $cartItems->sum(fn($i) => $i->quantity * $i->product->selling_price);
            $tax      = round($subtotal * 0.075, 2); // 7.5% VAT
            $discount = 0;
            $total    = $subtotal + $tax - $discount;

            // Group by dealer (for multi-dealer cart, create one order per dealer)
            $byDealer = $cartItems->groupBy(fn($i) => $i->product->dealer_id);

            $orders = [];
            foreach ($byDealer as $dealerId => $items) {
                $sub = $items->sum(fn($i) => $i->quantity * $i->product->selling_price);
                $t   = round($sub + ($sub * 0.075), 2);

                $order = Order::create([
                    'order_number'     => Order::generateNumber(),
                    'buyer_id'         => $user->id,
                    'dealer_id'        => $dealerId,
                    'status'           => 'pending',
                    'payment_status'   => 'unpaid',
                    'payment_method'   => $data['payment_method'],
                    'payment_channel'  => $data['payment_method'] === 'transfer' ? $data['payment_channel'] : null,
                    'subtotal'         => $sub,
                    'tax'              => round($sub * 0.075, 2),
                    'discount'         => 0,
                    'total'            => $t,
                    'delivery_address' => $data['delivery_address'] ?? null,
                    'delivery_notes'   => $data['delivery_notes'] ?? null,
                ]);

                foreach ($items as $item) {
                    OrderItem::create([
                        'order_id'     => $order->id,
                        'product_id'   => $item->product->id,
                        'product_name' => $item->product->name,
                        'product_sku'  => $item->product->sku,
                        'unit'         => $item->product->unit,
                        'quantity'     => $item->quantity,
                        'unit_price'   => $item->product->selling_price,
                        'total'        => $item->quantity * $item->product->selling_price,
                    ]);
                    // Deduct stock -- the pre-check above (line ~82) isn't
                    // atomic with this decrement; a concurrent checkout on
                    // the same product between that check and this point
                    // could have already taken the remaining stock.
                    // decrementStock() now guards the UPDATE itself
                    // (WHERE quantity_in_stock >= qty), so a failed
                    // decrement here means stock genuinely ran out in that
                    // window -- throwing rolls back this entire
                    // transaction (order, items, any earlier dealer's
                    // order in the same checkout) rather than completing
                    // an order for stock that no longer exists.
                    if (! $item->product->decrementStock($item->quantity)) {
                        throw new \RuntimeException("'{$item->product->name}' sold out while placing this order. Please review your cart and try again.");
                    }
                }
                $orders[] = $order->load('items');
            }

            // Clear cart
            CartItem::where('user_id', $user->id)->delete();

            return [$orders, $total];
            });
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        $payment = [
            'method' => $data['payment_method'],
            'channel' => $data['payment_method'] === 'transfer' ? $data['payment_channel'] : null,
            'instructions' => $data['payment_method'] === 'transfer'
                ? 'Use the selected bank, wallet, or transfer channel and confirm payment with the dealer.'
                : 'Continue with the selected payment provider.',
            'authorization_url' => null,
        ];

        // The web buyer-facing checkout (MarketplaceController::placeOrder())
        // has always initialized a single combined Paystack transaction for
        // the grand total across every dealer's order and returned its
        // authorization_url for the browser to follow -- this mobile
        // endpoint never did either, so selecting Paystack (the default
        // payment method) created an order the buyer had no way to actually
        // pay for through the app at all. Mirrors that exact pattern: one
        // transaction for the summed total, every created order tagged with
        // the same reference, outside the DB transaction above since an
        // external HTTP call has no business holding those row locks open.
        if ($data['payment_method'] === 'paystack' && config('services.paystack.secret_key')) {
            $reference = 'ORD-' . strtoupper(Str::random(12));
            $orderIds  = collect($orders)->pluck('id')->toArray();

            $response = Http::withToken(config('services.paystack.secret_key'))
                ->post(config('services.paystack.payment_url') . '/transaction/initialize', [
                    'email'        => $user->email,
                    'amount'       => (int) round($total * 100),
                    'reference'    => $reference,
                    'currency'     => 'NGN',
                    // NOT route('marketplace.payment.callback') -- that route
                    // reads auth()->id() from a web session, which an
                    // in-app browser opened by WebBrowser.openBrowserAsync()
                    // on mobile never has. This dedicated, stateless
                    // endpoint verifies by reference alone instead, the
                    // same precedent PaymentApiController::mobileCallback()
                    // already established for exactly this reason (a
                    // cryptographically random, unguessable reference is
                    // the actual security boundary here, not a session).
                    'callback_url' => config('app.url') . '/api/payment/mobile-marketplace-callback',
                    'metadata'     => [
                        'order_ids' => $orderIds,
                        'user_id'   => $user->id,
                    ],
                ]);

            if ($response->successful() && $response->json('status')) {
                Order::whereIn('id', $orderIds)->update(['payment_reference' => $reference]);
                $payment['authorization_url'] = $response->json('data.authorization_url');
                $payment['reference']         = $reference;
            } else {
                Log::error('[Mobile checkout] Paystack init failed', ['response' => $response->json()]);
                $payment['instructions'] = 'Your order was placed but online payment could not be started. Please try again from your Orders screen, or contact support.';
            }
        }

        return response()->json([
            'data'    => $orders,
            'message' => 'Order placed successfully',
            'payment' => $payment,
            'total'   => $total,
        ], 201);
    }

    /**
     * Paystack redirect target for the mobile checkout() flow above --
     * public by necessity (an in-app browser has no web session to read
     * auth()->id() from), mirroring MarketplaceController::
     * paymentCallback()'s verification logic exactly but scoped only by
     * the reference, the same precedent already established for
     * Api\PaymentApiController::mobileCallback(). The reference is
     * Str::random(12) over a 62-character alphabet, generated server-side
     * and never guessable by a third party.
     */
    public function mobilePaymentCallback(Request $request)
    {
        $reference = $request->query('reference') ?? $request->query('trxref');
        if (!$reference) {
            return response()->json(['success' => false, 'message' => 'No reference provided.'], 400);
        }

        $response = Http::withToken(config('services.paystack.secret_key'))
            ->get(config('services.paystack.payment_url') . "/transaction/verify/{$reference}");

        if (!$response->successful() || $response->json('data.status') !== 'success') {
            return response()->json(['success' => false, 'message' => 'Payment verification failed.'], 422);
        }

        $orders = Order::where('payment_reference', $reference)->get();
        if ($orders->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No matching order found for this reference.'], 404);
        }

        foreach ($orders as $order) {
            $order->update([
                'payment_status'  => 'paid',
                'payment_method'  => 'paystack',
                'payment_channel' => 'online',
            ]);
            \App\Models\AuditLog::record('order.payment_confirmed', 'Order', $order->id, [
                'reference' => $reference,
                'amount'    => $order->total,
                'source'    => 'mobile',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Payment confirmed. Your ' . $orders->count() . ' order(s) are now being processed.',
            'order_ids' => $orders->pluck('id')->values(),
        ]);
    }

    public static function transferChannels(): array
    {
        return [
            ['id' => 'gtbank', 'label' => 'GTBank'],
            ['id' => 'access', 'label' => 'Access Bank'],
            ['id' => 'uba', 'label' => 'UBA'],
            ['id' => 'firstbank', 'label' => 'First Bank'],
            ['id' => 'zenith', 'label' => 'Zenith Bank'],
            ['id' => 'opay', 'label' => 'OPay Wallet'],
            ['id' => 'palmpay', 'label' => 'PalmPay Wallet'],
            ['id' => 'kuda', 'label' => 'Kuda Bank'],
            ['id' => 'moniepoint', 'label' => 'Moniepoint'],
            ['id' => 'bank_app', 'label' => 'Other Bank App'],
            ['id' => 'pos', 'label' => 'POS Transfer'],
        ];
    }

    public function cancel(Order $order)
    {
        if (auth()->user()->role !== 'farmer') {
            return response()->json(['error' => 'Only farmers can cancel buyer orders.'], 403);
        }

        if ($order->buyer_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        if (!in_array($order->status, ['pending', 'confirmed'])) {
            return response()->json(['error' => 'Order cannot be cancelled at this stage'], 422);
        }

        // Restore stock
        foreach ($order->items as $item) {
            if ($item->product) {
                $item->product->increment('quantity_in_stock', $item->quantity);
            }
        }
        $order->update(['status' => 'cancelled']);

        return response()->json(['message' => 'Order cancelled']);
    }

    // ── Dealer routes ────────────────────────────────────────────────────────────

    public function dealerOrders(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'agro-dealer') {
            return response()->json(['error' => 'Dealers only'], 403);
        }

        $query = Order::with(['items', 'buyer:id,first_name,middle_name,last_name,email,phone'])
            ->where('dealer_id', $user->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderByDesc('created_at')->paginate(20);
        $orders->getCollection()->each(fn ($o) => $o->buyer?->append('name'));

        return response()->json([
            'data' => $orders->items(),
            'meta' => ['total' => $orders->total()],
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $user = auth()->user();
        if ($order->dealer_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'status' => 'required|in:confirmed,processing,shipped,delivered,cancelled',
        ]);

        $updates = ['status' => $data['status']];
        if ($data['status'] === 'confirmed')  $updates['confirmed_at'] = now();
        if ($data['status'] === 'delivered')  $updates['delivered_at'] = now();

        $order->update($updates);

        return response()->json(['data' => $order->fresh(), 'message' => 'Order status updated']);
    }

    public function markPaid(Request $request, Order $order)
    {
        // A genuine Paystack-verified payment is already marked paid
        // automatically by the webhook (WebhookController::activateOrder())
        // -- this manual endpoint exists only for offline channels (bank
        // transfer, cash, POS) where gateway verification is impossible.
        // It previously allowed the order's own dealer to self-certify
        // payment with an arbitrary, unverified payment_reference, then
        // self-set status=delivered (updateStatus() above), then request
        // a real-money payout (MarketplaceSellController::requestPayout())
        // on a payment_status/status pair they fully controlled end to
        // end -- a dealer could fabricate a sale and get paid for an
        // order that was never actually bought. Restricting to admin
        // forces a neutral third party (who doesn't benefit from the
        // payout) to confirm the offline payment genuinely happened
        // before it's recorded as paid, closing that loop at its
        // weakest link rather than attempting gateway verification that
        // doesn't apply to these channels in the first place.
        if (auth()->user()->role !== 'admin') {
            return response()->json(['error' => 'Only an administrator can confirm an offline payment.'], 403);
        }
        $data = $request->validate([
            'payment_reference' => 'nullable|string',
            'payment_method'    => 'nullable|string',
            'payment_channel'   => 'nullable|string|in:gtbank,access,uba,firstbank,zenith,opay,palmpay,kuda,moniepoint,bank_app,pos',
        ]);
        $order->update(array_merge($data, ['payment_status' => 'paid']));
        return response()->json(['message' => 'Order marked as paid']);
    }
}


