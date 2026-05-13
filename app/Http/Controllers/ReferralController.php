<?php

namespace App\Http\Controllers;

use App\Models\ReferralReward;
use App\Services\ReferralService;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function me(Request $request, ReferralService $referralService)
    {
        $user = $request->user();
        $code = $referralService->activeCodeFor($user);

        return response()->json([
            'code' => $code,
            'invite_url' => url('/register?ref=' . urlencode($code->code)),
            'stats' => [
                'referrals' => $user->referralsMade()->count(),
                'pending_rewards' => (float) $user->referralRewards()->where('status', 'pending')->sum('amount'),
                'approved_rewards' => (float) $user->referralRewards()->whereIn('status', ['approved', 'paid', 'redeemed'])->sum('amount'),
                'wallet_balance' => (float) optional($user->rewardWallet)->balance,
            ],
            'referrals' => $user->referralsMade()->with('referredUser:id,name,email', 'rewards')->latest()->limit(50)->get(),
        ]);
    }

    public function rewards(Request $request)
    {
        $rewards = ReferralReward::query()
            ->where('referrer_id', $request->user()->id)
            ->with('referral.referredUser:id,name,email')
            ->latest()
            ->paginate($request->integer('per_page', 20));

        return response()->json($rewards);
    }
}
