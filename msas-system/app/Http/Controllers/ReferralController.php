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
            // referral_code has a DB-level unique constraint
            // (2026_07_28_700003_add_referral_nps_to_users.php), so a
            // collision can't silently create a duplicate -- but nothing
            // here retried on one, so the unlucky user who did collide
            // would see an uncaught 500 on their first visit to this page
            // instead of simply getting a different code.
            for ($attempt = 0; $attempt < 5; $attempt++) {
                try {
                    $user->update(['referral_code' => strtoupper(Str::random(8))]);
                    break;
                } catch (\Illuminate\Database\QueryException $e) {
                    if ($attempt === 4) {
                        throw $e;
                    }
                }
            }
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