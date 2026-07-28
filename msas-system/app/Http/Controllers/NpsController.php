<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class NpsController extends Controller
{
    // AJAX endpoint: submit NPS score
    public function store(Request $request)
    {
        $data = $request->validate(['score' => 'required|integer|min:0|max:10']);
        $user = auth()->user();
        $user->update([
            'nps_score'    => $data['score'],
            'nps_rated_at' => now(),
        ]);
        AuditLog::record('nps.rated', 'User', $user->id, ['score' => $data['score']]);
        return response()->json(['ok' => true]);
    }

    // CEO: NPS dashboard
    public function dashboard()
    {
        $scored   = User::whereNotNull('nps_score');
        $total    = $scored->count();
        $promoters  = (clone $scored)->where('nps_score','>=',9)->count();
        $detractors = (clone $scored)->where('nps_score','<=',6)->count();
        $passives   = $total - $promoters - $detractors;
        $nps = $total > 0 ? round(($promoters/$total - $detractors/$total) * 100) : null;
        $avg = $total > 0 ? round(User::whereNotNull('nps_score')->avg('nps_score'), 1) : null;
        $dist = [];
        for ($i = 0; $i <= 10; $i++) {
            $dist[$i] = User::where('nps_score', $i)->count();
        }
        return view('ceo.nps', compact('total','promoters','detractors','passives','nps','avg','dist'));
    }
}