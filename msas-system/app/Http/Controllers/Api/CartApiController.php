<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

class CartApiController extends Controller
{
    public function index()
    {
        $items = CartItem::with('product')
            ->where('user_id', auth()->id())
            ->get()
            ->map(function ($item) {
                return [
                    'id'         => $item->id,
                    'product'    => $item->product,
                    'quantity'   => $item->quantity,
                    'line_total' => $item->quantity * ($item->product->selling_price ?? 0),
                ];
            });

        $total = $items->sum('line_total');

        return response()->json(['data' => $items, 'total' => $total, 'count' => $items->count()]);
    }

    public function add(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'sometimes|integer|min:1|max:999',
        ]);

        $product = Product::active()->findOrFail($data['product_id']);
        $qty     = $data['quantity'] ?? 1;

        if ($product->quantity_in_stock < $qty) {
            return response()->json(['error' => 'Insufficient stock'], 422);
        }

        $item = CartItem::updateOrCreate(
            ['user_id' => auth()->id(), 'product_id' => $product->id],
            ['quantity' => \DB::raw("quantity + {$qty}")]
        );

        return response()->json(['data' => $item, 'message' => 'Added to cart'], 201);
    }

    public function update(Request $request, CartItem $cartItem)
    {
        if ($cartItem->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $data = $request->validate(['quantity' => 'required|integer|min:1|max:999']);
        $cartItem->update($data);
        return response()->json(['data' => $cartItem, 'message' => 'Cart updated']);
    }

    public function remove(CartItem $cartItem)
    {
        if ($cartItem->user_id !== auth()->id()) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $cartItem->delete();
        return response()->json(['message' => 'Item removed']);
    }

    public function clear()
    {
        CartItem::where('user_id', auth()->id())->delete();
        return response()->json(['message' => 'Cart cleared']);
    }
}
