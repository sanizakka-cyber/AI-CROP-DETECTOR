<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;

class FeedbackController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'type'    => 'required|in:general,bug,feature,praise',
            'rating'  => 'nullable|integer|min:1|max:5',
            'message' => 'required|string|min:10|max:1000',
            'page'    => 'nullable|string|max:255',
        ]);

        $data['user_id'] = auth()->id();

        $feedback = Feedback::create($data);

        $ref = 'FB-' . now()->format('Ymd') . '-' . str_pad($feedback->id, 5, '0', STR_PAD_LEFT);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Thank you for your feedback!', 'ref' => $ref]);
        }

        return back()->with('success', 'Thank you for your feedback! Reference: ' . $ref);
    }
}