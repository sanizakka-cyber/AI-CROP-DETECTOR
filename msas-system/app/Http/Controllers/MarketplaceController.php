<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MarketplaceController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('dealer:id,first_name,last_name,phone')
            ->where('status', 'active')
            ->where('is_approved', true)
            ->where('quantity_in_stock', '>', 0);

        if ($request->category) {
            $query->where('category', $request->category);
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name',        'ilike', "%{$request->search}%")
                  ->orWhere('description','ilike', "%{$request->search}%")
                  ->orWhere('brand',      'ilike', "%{$request->search}%");
            });
        }

        $items      = $query->latest()->paginate(12)->withQueryString();
        $categories = Product::where('is_approved', true)->where('status', 'active')
                        ->distinct()->pluck('category')->filter()->values();

        return view('marketplace.index', compact('items', 'categories'));
    }

    public function show(Product $product)
    {
        if ($product->status !== 'active' || !$product->is_approved) {
            abort(404);
        }
        $product->load('dealer:id,first_name,last_name,phone,email,state');
        $related = Product::where('category', $product->category)
            ->where('id', '!=', $product->id)
            ->where('status', 'active')->where('is_approved', true)
            ->take(4)->get();

        $cartCount = collect(session('cart', []))->sum('quantity');
        return view('marketplace.show', compact('product', 'related', 'cartCount'));
    }

    // ── Cart (session-based) ────────────────────────────────────────────────────

    public function addToCart(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1|max:999',
        ]);

        $product = Product::findOrFail($request->product_id);

        if ($product->status !== 'active' || !$product->is_approved || $product->quantity_in_stock < 1) {
            return back()->with('error', 'This product is not available.');
        }

        $qty = min($request->quantity, $product->quantity_in_stock);

        $cart = session('cart', []);
        $key  = $request->product_id;

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] = min($cart[$key]['quantity'] + $qty, $product->quantity_in_stock);
        } else {
            $cart[$key] = ['product_id' => $product->id, 'quantity' => $qty];
        }

        session(['cart' => $cart]);

        return redirect()->route('marketplace.cart')->with('success', '"' . $product->name . '" added to cart.');
    }

    public function cart()
    {
        $cart       = session('cart', []);
        $productIds = array_keys($cart);
        $products   = Product::whereIn('id', $productIds)->with('dealer:id,first_name,last_name')->get()->keyBy('id');

        $items = collect($cart)->map(function ($row) use ($products) {
            $p = $products->get($row['product_id']);
            if (!$p) return null;
            $qty = min($row['quantity'], $p->quantity_in_stock);
            return ['product' => $p, 'quantity' => $qty, 'subtotal' => $qty * $p->selling_price];
        })->filter()->values();

        $total = $items->sum('subtotal');

        return view('marketplace.cart', compact('items', 'total'));
    }

    public function updateCart(Request $request)
    {
        $request->validate(['quantities' => 'required|array', 'quantities.*' => 'integer|min:0']);

        $cart = session('cart', []);
        foreach ($request->quantities as $productId => $qty) {
            if ($qty <= 0) {
                unset($cart[$productId]);
            } else {
                if (isset($cart[$productId])) {
                    $cart[$productId]['quantity'] = (int) $qty;
                }
            }
        }
        session(['cart' => $cart]);
        return back()->with('success', 'Cart updated.');
    }

    public function removeFromCart(Request $request, int $productId)
    {
        $cart = session('cart', []);
        unset($cart[$productId]);
        session(['cart' => $cart]);
        return back()->with('success', 'Item removed from cart.');
    }

    public function clearCart()
    {
        session()->forget('cart');
        return back()->with('success', 'Cart cleared.');
    }

    // ── Checkout ────────────────────────────────────────────────────────────────

    public function checkout()
    {
        $cart = session('cart', []);
        if (empty($cart)) {
            return redirect()->route('marketplace.cart')->with('error', 'Your cart is empty.');
        }

        $productIds = array_keys($cart);
        $products   = Product::whereIn('id', $productIds)->with('dealer:id,first_name,last_name')->get()->keyBy('id');

        $items = collect($cart)->map(function ($row) use ($products) {
            $p = $products->get($row['product_id']);
            if (!$p || $p->quantity_in_stock < 1) return null;
            $qty = min($row['quantity'], $p->quantity_in_stock);
            return ['product' => $p, 'quantity' => $qty, 'subtotal' => $qty * $p->selling_price];
        })->filter()->values();

        if ($items->isEmpty()) {
            session()->forget('cart');
            return redirect()->route('marketplace')->with('error', 'No available items in cart.');
        }

        $subtotal = $items->sum('subtotal');
        $tax      = round($subtotal * 0.075, 2); // 7.5% VAT
        $total    = $subtotal + $tax;

        return view('marketplace.checkout', compact('items', 'subtotal', 'tax', 'total'));
    }

    public function placeOrder(Request $request)
    {
        $request->validate([
            'delivery_address' => 'required|string|max:500',
            'delivery_notes'   => 'nullable|string|max:500',
        ]);

        $cart = session('cart', []);
        if (empty($cart)) {
            return redirect()->route('marketplace.cart')->with('error', 'Your cart is empty.');
        }

        $productIds = array_keys($cart);
        $products   = Product::whereIn('id', $productIds)->with('dealer')->get()->keyBy('id');

        // Group items by dealer (one order per seller)
        $byDealer = [];
        foreach ($cart as $pid => $row) {
            $p = $products->get($pid);
            if (!$p || $p->quantity_in_stock < 1) continue;
            $qty = min($row['quantity'], $p->quantity_in_stock);
            $byDealer[$p->dealer_id][] = ['product' => $p, 'quantity' => $qty];
        }

        if (empty($byDealer)) {
            session()->forget('cart');
            return redirect()->route('marketplace')->with('error', 'No items were available to order.');
        }

        $createdOrders = [];
        foreach ($byDealer as $dealerId => $dealerItems) {
            $subtotal = collect($dealerItems)->sum(fn ($i) => $i['quantity'] * $i['product']->selling_price);
            $tax      = round($subtotal * 0.075, 2);
            $total    = $subtotal + $tax;

            $order = Order::create([
                'order_number'     => Order::generateNumber(),
                'buyer_id'         => auth()->id(),
                'dealer_id'        => $dealerId,
                'status'           => 'pending',
                'payment_status'   => 'unpaid',
                'subtotal'         => $subtotal,
                'tax'              => $tax,
                'total'            => $total,
                'delivery_address' => $request->delivery_address,
                'delivery_notes'   => $request->delivery_notes,
            ]);

            foreach ($dealerItems as $item) {
                $p = $item['product'];
                OrderItem::create([
                    'order_id'     => $order->id,
                    'product_id'   => $p->id,
                    'product_name' => $p->name,
                    'product_sku'  => $p->sku,
                    'unit'         => $p->unit,
                    'quantity'     => $item['quantity'],
                    'unit_price'   => $p->selling_price,
                    'total'        => $item['quantity'] * $p->selling_price,
                ]);

                $p->decrementStock($item['quantity']);
            }

            $createdOrders[] = $order;
        }

        session()->forget('cart');

        $firstOrder = $createdOrders[0];
        return redirect()->route('marketplace.orders.show', $firstOrder)
            ->with('success', count($createdOrders) . ' order(s) placed successfully! Order ' . $firstOrder->order_number . ' is now pending.');
    }

    // ── Buyer Orders ────────────────────────────────────────────────────────────

    public function myOrders(Request $request)
    {
        $query = Order::where('buyer_id', auth()->id())->with(['items', 'dealer:id,first_name,last_name,phone']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate(10)->withQueryString();

        $stats = [
            'pending'   => Order::where('buyer_id', auth()->id())->where('status', 'pending')->count(),
            'delivered' => Order::where('buyer_id', auth()->id())->where('status', 'delivered')->count(),
            'total'     => Order::where('buyer_id', auth()->id())->count(),
            'spent'     => Order::where('buyer_id', auth()->id())->where('payment_status', 'paid')->sum('total'),
        ];

        return view('marketplace.my-orders', compact('orders', 'stats'));
    }

    public function showOrder(Order $order)
    {
        abort_if($order->buyer_id !== auth()->id(), 403);
        $order->load(['items.product', 'dealer:id,first_name,last_name,phone,email']);
        return view('marketplace.order-detail', compact('order'));
    }
}
