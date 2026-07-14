<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class ExtensionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return ['auth', new Middleware('role:extension-officer,admin,ceo')];
    }

    public function farmers(Request $request)
    {
        $query = User::where('role', 'farmer');
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('first_name', 'like', "%{$request->search}%")
                  ->orWhere('last_name',  'like', "%{$request->search}%")
                  ->orWhere('email',      'like', "%{$request->search}%");
            });
        }
        if ($request->state) $query->where('state', $request->state);

        $farmers    = $query->latest()->paginate(20)->withQueryString();
        $totalCount = User::where('role', 'farmer')->count();
        $states     = User::where('role', 'farmer')->whereNotNull('state')->distinct()->pluck('state');

        return view('extension.farmers', compact('farmers', 'totalCount', 'states'));
    }

    public function advisory(Request $request)
    {
        try {
            $query = DB::table('extension_advisory')->orderByDesc('created_at');
            if ($request->farmer_id) $query->where('farmer_id', $request->farmer_id);
            $records = $query->paginate(20)->withQueryString();
        } catch (\Exception $e) {
            $records = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
        }

        $farmers = User::where('role', 'farmer')->get();
        return view('extension.advisory', compact('records', 'farmers'));
    }

    public function storeAdvisory(Request $request)
    {
        $request->validate([
            'farmer_id' => 'required|exists:users,id',
            'subject'   => 'required|string|max:200',
            'advice'    => 'required|string',
            'category'  => 'required|in:Crop,Livestock,General,Soil,Pest,Disease',
        ]);

        try {
            DB::table('extension_advisory')->insert([
                'farmer_id'    => $request->farmer_id,
                'officer_id'   => auth()->id(),
                'subject'      => $request->subject,
                'advice'       => $request->advice,
                'category'     => $request->category,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Could not save advisory. Table may need migration.');
        }

        return back()->with('success', 'Advisory record saved.');
    }

    public function visits(Request $request)
    {
        try {
            $records = DB::table('extension_visits')->orderByDesc('visit_date')->paginate(20)->withQueryString();
        } catch (\Exception $e) {
            $records = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
        }

        $farmers = User::where('role', 'farmer')->get();
        return view('extension.visits', compact('records', 'farmers'));
    }

    public function storeVisit(Request $request)
    {
        $request->validate([
            'farmer_id'  => 'required|exists:users,id',
            'visit_date' => 'required|date',
            'purpose'    => 'required|string|max:200',
            'notes'      => 'nullable|string',
            'outcome'    => 'nullable|string|max:200',
        ]);

        try {
            DB::table('extension_visits')->insert([
                'farmer_id'  => $request->farmer_id,
                'officer_id' => auth()->id(),
                'visit_date' => $request->visit_date,
                'purpose'    => $request->purpose,
                'notes'      => $request->notes,
                'outcome'    => $request->outcome,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Could not save visit. Table may need migration.');
        }

        return back()->with('success', 'Farm visit recorded.');
    }
}
