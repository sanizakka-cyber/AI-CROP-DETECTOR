<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductApiController extends Controller
{
    // ── Browse (farmers, anyone authenticated) ──────────────────────────────────

    public function index(Request $request)
    {
        $query = Product::active()->with('dealer:id,name');

        if ($request->filled('category')) {
            $query->byCategory($request->category);
        }
        if ($request->filled('search')) {
            $query->search($request->search);
        }
        if ($request->filled('min_price')) {
            $query->where('selling_price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('selling_price', '<=', $request->max_price);
        }
        if ($request->boolean('in_stock')) {
            $query->inStock();
        }
        if ($request->filled('sort')) {
            match($request->sort) {
                'price_asc'   => $query->orderBy('selling_price'),
                'price_desc'  => $query->orderByDesc('selling_price'),
                'rating'      => $query->orderByDesc('rating'),
                'newest'      => $query->orderByDesc('created_at'),
                default       => $query->orderByDesc('is_featured')->orderByDesc('created_at'),
            };
        } else {
            $query->orderByDesc('is_featured')->orderByDesc('created_at');
        }

        $products = $query->paginate($request->input('per_page', 20));

        return response()->json([
            'data'       => $products->items(),
            'meta'       => [
                'total'        => $products->total(),
                'per_page'     => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page'    => $products->lastPage(),
            ],
            'categories' => array_keys(Product::categories()),
        ]);
    }

    public function show(Product $product)
    {
        if ($product->status !== 'active' || !$product->is_approved) {
            return response()->json(['error' => 'Product not found'], 404);
        }
        $product->load(['dealer:id,name', 'reviews.user:id,name']);
        return response()->json(['data' => $product]);
    }

    public function recommended(Request $request)
    {
        $tags = $request->input('tags', []);
        if (empty($tags)) {
            return response()->json(['data' => []]);
        }

        $products = Product::active()->inStock()
            ->where(function ($q) use ($tags) {
                foreach ($tags as $tag) {
                    $q->orWhereJsonContains('tags', $tag);
                }
            })
            ->orderByDesc('rating')
            ->limit(10)
            ->get();

        return response()->json(['data' => $products]);
    }

    // ── Dealer product management ───────────────────────────────────────────────

    public function myProducts(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'agro-dealer') {
            return response()->json(['error' => 'Dealers only'], 403);
        }

        $products = Product::where('dealer_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json(['data' => $products->items(), 'meta' => ['total' => $products->total()]]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'agro-dealer') {
            return response()->json(['error' => 'Dealers only'], 403);
        }

        $data = $request->validate([
            'name'                 => 'required|string|max:255',
            'category'             => 'required|string|max:100',
            'subcategory'          => 'nullable|string|max:100',
            'brand'                => 'nullable|string|max:100',
            'manufacturer'         => 'nullable|string|max:100',
            'description'          => 'nullable|string',
            'usage_instructions'   => 'nullable|string',
            'dosage_instructions'  => 'nullable|string',
            'storage_requirements' => 'nullable|string',
            'unit'                 => 'required|string|max:50',
            'cost_price'           => 'nullable|numeric|min:0',
            'selling_price'        => 'required|numeric|min:0',
            'quantity_in_stock'    => 'required|integer|min:0',
            'low_stock_threshold'  => 'nullable|integer|min:0',
            'expiry_date'          => 'nullable|date|after:today',
            'tags'                 => 'nullable|array',
            'tags.*'               => 'string',
            'image'                => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $data['dealer_id'] = $user->id;
        $data['sku'] = 'SKU-' . strtoupper(substr(md5(uniqid()), 0, 8));

        $product = Product::create($data);

        return response()->json(['data' => $product, 'message' => 'Product created'], 201);
    }

    public function update(Request $request, Product $product)
    {
        $user = auth()->user();
        if ($product->dealer_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'name'                 => 'sometimes|string|max:255',
            'category'             => 'sometimes|string|max:100',
            'subcategory'          => 'nullable|string|max:100',
            'brand'                => 'nullable|string|max:100',
            'manufacturer'         => 'nullable|string|max:100',
            'description'          => 'nullable|string',
            'usage_instructions'   => 'nullable|string',
            'dosage_instructions'  => 'nullable|string',
            'storage_requirements' => 'nullable|string',
            'unit'                 => 'sometimes|string|max:50',
            'cost_price'           => 'nullable|numeric|min:0',
            'selling_price'        => 'sometimes|numeric|min:0',
            'quantity_in_stock'    => 'sometimes|integer|min:0',
            'low_stock_threshold'  => 'nullable|integer|min:0',
            'expiry_date'          => 'nullable|date',
            'tags'                 => 'nullable|array',
            'status'               => 'sometimes|in:active,inactive,draft',
            'image'                => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image) Storage::disk('public')->delete($product->image);
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        return response()->json(['data' => $product->fresh(), 'message' => 'Product updated']);
    }

    public function destroy(Product $product)
    {
        $user = auth()->user();
        if ($product->dealer_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        if ($product->image) Storage::disk('public')->delete($product->image);
        $product->delete();
        return response()->json(['message' => 'Product deleted']);
    }

    public function addReview(Request $request, Product $product)
    {
        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);
        $data['user_id'] = auth()->id();

        $review = ProductReview::updateOrCreate(
            ['product_id' => $product->id, 'user_id' => auth()->id()],
            $data
        );
        $product->updateRating();

        return response()->json(['data' => $review, 'message' => 'Review saved'], 201);
    }

    public function categories()
    {
        return response()->json(['data' => Product::categories()]);
    }

    public function adjustStock(Request $request, Product $product)
    {
        $user = auth()->user();
        if ($product->dealer_id !== $user->id && $user->role !== 'admin') {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $data = $request->validate([
            'adjustment' => 'required|integer', // positive = add, negative = remove
            'reason'     => 'nullable|string|max:255',
        ]);
        $new = max(0, $product->quantity_in_stock + $data['adjustment']);
        $product->update(['quantity_in_stock' => $new]);
        return response()->json(['data' => $product->fresh(), 'message' => 'Stock updated']);
    }
}
