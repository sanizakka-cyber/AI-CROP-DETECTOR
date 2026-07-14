<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    public function index()
    {
        // Simple stub for marketplace
        $items = \App\Models\MarketplaceItem::latest()->get();
        return view('marketplace.index', compact('items'));
    }
}
