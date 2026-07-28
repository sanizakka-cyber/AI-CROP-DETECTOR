<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\MobileNotification;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReferralController extends Controller
{
    // Farmer: view their referral code + history
    public function index()
    {
        $user = auth()->user();
        if (!$user->referral_code) {
            $user->update(['referral_code' => strtoupper(Str::random(8))]);
            $user->refresh();
        }
        $referrals = Referral::where('referrer_id', $user->id)
            ->with('referred:id,first_name,last_name,created_at,role')
            ->latest()->get();
        $referralLink = config('app.url').'/register?ref='.$user->referral_code;
        return view('farmer.referral', compact('user', 'referrals', 'referralLink'));
    }

    // CEO: referral leaderboard
    public function leaderboard()
    {
        $leaders = User::select('users.id','users.first_name','users.last_name','users.state',
                \Illuminate\Support\Facades\DB::raw('COUNT(referrals.id) as referral_count'))
            ->join('referrals','referrals.referrer_id','=','users.id')
            ->groupBy('users.id','users.first_name','users.last_name','users.state')
            ->orderByDesc('referral_count')
            ->take(20)->get();

        $totalReferrals = Referral::count();
        $pendingRewards = Referral::where('status','pending')->count();

        return view('ceo.referrals', compact('leaders','totalReferrals','pendingRewards'));
    }
}