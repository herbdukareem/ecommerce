<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\ReferralReward;
use App\Models\User;
use App\Services\ReferralSettingsService;
use App\Services\RewardWalletService;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function index(Request $request)
    {
        $query = Referral::query()
            ->with('referrer:id,name,email', 'referredUser:id,name,email', 'referralCode', 'rewards')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    public function rewards(Request $request)
    {
        $query = ReferralReward::query()
            ->with('referral', 'referral.referrer:id,name,email', 'referral.referredUser:id,name,email')
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json($query->paginate($request->integer('per_page', 20)));
    }

    public function updateRewardStatus(Request $request, int $id, RewardWalletService $walletService)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,approved,cancelled,reversed,paid,redeemed',
        ]);

        $reward = \DB::transaction(function () use ($id, $data, $walletService) {
            $reward = ReferralReward::query()->lockForUpdate()->findOrFail($id);
            $oldStatus = $reward->status;
            $reward->update([
                'status' => $data['status'],
                'approved_at' => $data['status'] === 'approved' ? ($reward->approved_at ?: now()) : $reward->approved_at,
                'paid_at' => in_array($data['status'], ['paid', 'redeemed'], true) ? ($reward->paid_at ?: now()) : $reward->paid_at,
                'cancelled_at' => $data['status'] === 'cancelled' ? now() : $reward->cancelled_at,
                'reversed_at' => $data['status'] === 'reversed' ? now() : $reward->reversed_at,
            ]);

            if ($oldStatus !== 'approved' && $data['status'] === 'approved') {
                $walletService->credit(User::findOrFail($reward->referrer_id), (float) $reward->amount, $reward);
            }

            if (in_array($oldStatus, ['approved', 'paid', 'redeemed'], true) && in_array($data['status'], ['cancelled', 'reversed'], true)) {
                $walletService->reverse(User::findOrFail($reward->referrer_id), (float) $reward->amount, $reward);
            }

            return $reward->fresh();
        }, 3);

        return response()->json([
            'message' => 'Referral reward updated.',
            'reward' => $reward,
        ]);
    }

    public function settings(ReferralSettingsService $settings)
    {
        return response()->json($settings->settings());
    }

    public function updateSettings(Request $request, ReferralSettingsService $settings)
    {
        $data = $request->validate([
            'referral_enabled' => 'sometimes|boolean',
            'referral_reward_basis' => 'sometimes|in:fixed,percentage',
            'referral_reward_value' => 'sometimes|numeric|min:0',
            'referral_first_order_only' => 'sometimes|boolean',
            'referral_minimum_order_amount' => 'sometimes|numeric|min:0',
            'referral_trigger' => 'sometimes|in:paid_order,delivered_order',
            'referral_approval_mode' => 'sometimes|in:automatic,manual',
            'referral_wallet_redemption_enabled' => 'sometimes|boolean',
        ]);

        return response()->json($settings->update($data));
    }
}
