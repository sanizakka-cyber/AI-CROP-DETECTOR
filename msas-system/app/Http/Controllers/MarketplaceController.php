<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\Product::with('dealer:id,first_name,last_name,phone')
            ->where('status', 'active')
            ->where('is_approved', true)
            ->where('quantity_in_stock', '>', 0);

        if ($request->category) {
            $query->where('category', $request->category);
        }
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        $items      = $query->latest()->paginate(12)->withQueryString();
        $categories = \App\Models\Product::where('is_approved', true)
                        ->distinct()->pluck('category')->filter()->values();

        return view('marketplace.index', compact('items', 'categories'));
    }
}
