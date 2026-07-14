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
        $order->load(['items', 'buyer:id,name,email', 'dealer:id,name']);
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
            'payment_method'    => 'required|in:paystack,flutterwave,transfer,ussd,card,wallet',
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

        return DB::transaction(function () use ($cartItems, $user, $data) {
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
                    // Deduct stock
                    $item->product->decrementStock($item->quantity);
                }
                $orders[] = $order->load('items');
            }

            // Clear cart
            CartItem::where('user_id', $user->id)->delete();

            return response()->json([
                'data'    => $orders,
                'message' => 'Order placed successfully',
                'payment' => [
                    'method' => $data['payment_method'],
                    'channel' => $data['payment_method'] === 'transfer' ? $data['payment_channel'] : null,
                    'instructions' => $data['payment_method'] === 'transfer'
                        ? 'Use the selected bank, wallet, or transfer channel and confirm payment with the dealer.'
                        : 'Continue with the selected payment provider.',
                ],
                'total'   => $total,
            ], 201);
        });
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

        $query = Order::with(['items', 'buyer:id,name,email,phone'])
            ->where('dealer_id', $user->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->orderByDesc('created_at')->paginate(20);

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
        $user = auth()->user();
        if ($order->dealer_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['error' => 'Forbidden'], 403);
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


