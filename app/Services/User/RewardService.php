<?php

namespace App\Services\User;

use App\Models\Redemption;
use App\Models\Reward;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Nette\Utils\Random;

class RewardService
{
    public function getAvailableRewards(?string $search)
    {
        $query = Reward::where('status', 'Enable')
            ->where('expiration_date', '>=', Carbon::now());

        // Search filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('challenge_type', 'like', "%{$search}%");
                // ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return $query->latest()->get();
    }
    public function redeem(int $rewardId): ?Redemption
    {
        $reward = Reward::where('id', $rewardId)
            ->where('status', 'Enable')
            ->where('expiration_date', '>=', Carbon::now())
            ->first();

        if (!$reward) {
            return null;
        }

        // এখানে তুমি চাইলে user-এর points কাটতে পারো
        // Auth::user()->decrement('points', $reward->purchase_point);

        return Redemption::create([
            'user_id' => Auth::id(),
            'reward_id' => $reward->id,
            'partner_id' => $reward->partner_id,
            'date' => Carbon::now(),
            'code' => 'C' . rand(100000, 999999),
            'status' => 'Redeemed',
        ]);
    }
    public function getRedeemHistory()
    {
        $redeem_histories = Redemption::where('user_id', Auth::id())
            ->latest()
            ->with([
                'reward' => function ($q) {
                    $q->select('id', 'partner_id', 'title');
                },
                'reward.partner' => function ($q) {
                    $q->select('id', 'full_name', 'role', 'avatar');
                },
                'reward.partner.profile' => function ($q) {
                    $q->select('id', 'user_id', 'user_name', 'business_name', 'category', 'description', 'business_hours');
                }
            ])
            ->get();

        foreach ($redeem_histories as $history) {
            $history->status = $history->status == 'Redeemed' ? 'Pending' : 'Redeemed';

            $history->reward->partner->avatar = $history->reward->partner->avatar
                ? asset($history->reward->partner->avatar)
                : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($history->reward->partner->full_name);

        }

        return $redeem_histories;
    }
    public function getRedemptionDetails(int $id): ?Redemption
    {
        $details = Redemption::with([
            'reward' => function ($q) {
                $q->select('id', 'partner_id', 'title');
            },
            'reward.partner' => function ($q) {
                $q->select('id', 'full_name', 'role');
            },
            'reward.partner.profile' => function ($q) {
                $q->select('id', 'user_id', 'user_name', 'business_name', 'category', 'description', 'business_hours');
            }
        ])
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        $details->status = $details->status == 'Redeemed' ? 'Pending' : 'Redeemed';
        $details->reward->partner->avatar = $details->reward->partner->avatar
            ? asset($details->reward->partner->avatar)
            : 'https://ui-avatars.com/api/?background=random&name=' . urlencode($details->reward->partner->full_name);


        return $details;
    }
    public function markAsCompleted(int $id)
    {
        $redemption = Redemption::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$redemption) {
            return false;
        }

        $redemption->status = 'Completed';
        $redemption->save();

        return $redemption;
    }
}