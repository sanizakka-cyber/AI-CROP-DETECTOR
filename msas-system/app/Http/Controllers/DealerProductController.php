<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;

class DealerProductController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('role:agro-dealer,equipment-dealer'),
        ];
    }

    private function productsIndexRoute(): string
    {
        return auth()->user()->role === 'equipment-dealer'
            ? 'equipment-dealer.products.index'
            : 'dealer.products.index';
    }

    public function index(Request $request)
    {
        $query = Product::where('dealer_id', auth()->id());

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name',     'ilike', "%{$request->search}%")
                  ->orWhere('category','ilike', "%{$request->search}%")
                  ->orWhere('sku',     'ilike', "%{$request->search}%");
            });
        }

        if ($request->category) $query->where('category', $request->category);
        if ($request->stock === 'out_of_stock') $query->where('quantity_in_stock', 0);
        elseif ($request->stock === 'low_stock') $query->whereBetween('quantity_in_stock', [1, 10]);
        elseif ($request->stock === 'in_stock')  $query->where('quantity_in_stock', '>', 10);

        $products = $query->latest()->paginate(20)->withQueryString();
        $categories = array_keys(Product::categories());

        $base = Product::where('dealer_id', auth()->id());
        $stats = [
            'total'     => (clone $base)->count(),
            'active'    => (clone $base)->where('status', 'active')->count(),
            'low_stock' => (clone $base)->whereBetween('quantity_in_stock', [1, 10])->count(),
            'out_stock' => (clone $base)->where('quantity_in_stock', 0)->count(),
        ];

        // Both roles use the same view (role-aware $rp variable inside)
        return view('dealer.products.index', compact('products', 'categories', 'stats'));
    }

    public function create()
    {
        $categories = array_keys(Product::categories());
        return view('dealer.products.form', ['product' => null, 'categories' => $categories]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'category'           => 'required|string',
            'subcategory'        => 'nullable|string',
            'description'        => 'nullable|string',
            'brand'              => 'nullable|string',
            'manufacturer'       => 'nullable|string',
            'unit'               => 'required|string',
            'cost_price'         => 'required|numeric|min:0',
            'selling_price'      => 'required|numeric|min:0',
            'quantity_in_stock'  => 'required|integer|min:0',
            'low_stock_threshold'=> 'nullable|integer|min:0',
            'sku'                => 'nullable|string|unique:products,sku',
            'tags'               => 'nullable|string',
            'usage_instructions' => 'nullable|string',
            'dosage_instructions'=> 'nullable|string',
            'storage_requirements'=>'nullable|string',
            'expiry_date'        => 'nullable|date',
            'status'             => 'nullable|in:active,inactive,draft',
        ]);

        $data['status']    = $data['status'] ?? 'active';
        $data['dealer_id'] = auth()->id();
        $data['tags']      = $data['tags'] ? array_map('trim', explode(',', $data['tags'])) : [];
        $data['sku']       = $data['sku'] ?: 'SKU-' . strtoupper(Str::random(8));

        Product::create($data);

        return redirect()->route($this->productsIndexRoute())
            ->with('success', 'Product "' . $data['name'] . '" added successfully.');
    }

    public function edit(Product $product)
    {
        abort_if($product->dealer_id !== auth()->id(), 403);
        $categories = array_keys(Product::categories());
        return view('dealer.products.form', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        abort_if($product->dealer_id !== auth()->id(), 403);

        $data = $request->validate([
            'name'               => 'required|string|max:255',
            'category'           => 'required|string',
            'subcategory'        => 'nullable|string',
            'description'        => 'nullable|string',
            'brand'              => 'nullable|string',
            'manufacturer'       => 'nullable|string',
            'unit'               => 'required|string',
            'cost_price'         => 'required|numeric|min:0',
            'selling_price'      => 'required|numeric|min:0',
            'quantity_in_stock'  => 'required|integer|min:0',
            'low_stock_threshold'=> 'nullable|integer|min:0',
            'usage_instructions' => 'nullable|string',
            'dosage_instructions'=> 'nullable|string',
            'storage_requirements'=>'nullable|string',
            'expiry_date'        => 'nullable|date',
            'tags'               => 'nullable|string',
            'status'             => 'nullable|in:active,inactive,draft',
        ]);

        $data['tags'] = $data['tags'] ? array_map('trim', explode(',', $data['tags'])) : [];
        $product->update($data);

        return redirect()->route($this->productsIndexRoute())
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        abort_if($product->dealer_id !== auth()->id(), 403);
        $product->delete();
        return back()->with('success', 'Product deleted.');
    }

    public function adjustStock(Request $request, Product $product)
    {
        abort_if($product->dealer_id !== auth()->id(), 403);
        $request->validate(['adjustment' => 'required|integer', 'reason' => 'nullable|string']);

        $newQty = max(0, $product->quantity_in_stock + $request->adjustment);
        $product->update(['quantity_in_stock' => $newQty]);

        return back()->with('success', "Stock updated to {$newQty} units.");
    }

    public function orders(Request $request)
    {
        $query = Order::where('dealer_id', auth()->id())->with(['buyer', 'items']);

        if ($request->status) $query->where('status', $request->status);

        $orders = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'pending'   => Order::where('dealer_id', auth()->id())->where('status', 'pending')->count(),
            'confirmed' => Order::where('dealer_id', auth()->id())->where('status', 'confirmed')->count(),
            'total'     => Order::where('dealer_id', auth()->id())->count(),
            'revenue'   => Order::where('dealer_id', auth()->id())->where('payment_status', 'paid')->sum('total'),
        ];

        $view = auth()->user()->role === 'equipment-dealer' ? 'equipment-dealer.orders' : 'dealer.orders';
        return view($view, compact('orders', 'stats'));
    }

    public function updateOrderStatus(Request $request, Order $order)
    {
        abort_if($order->dealer_id !== auth()->id(), 403);
        $request->validate(['status' => 'required|in:confirmed,processing,shipped,delivered,cancelled']);
        $order->update(['status' => $request->status]);
        return back()->with('success', 'Order status updated to ' . ucfirst($request->status) . '.');
    }
}
