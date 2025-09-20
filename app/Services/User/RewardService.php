<?php

namespace App\Services\User;

use App\Models\Profile;
use App\Models\Redemption;
use App\Models\Reward;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Nette\Utils\Random;

class RewardService
{
    public function getAvailableRewards(?string $search, ?int $per_page)
    {
        $query = Reward::where('status', 'Enable')
            ->where('admin_approved', true)
            ->where('expiration_date', '>', Carbon::now());
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('challenge_type', 'like', "%{$search}%");
                // ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $rewords = $query->latest()->paginate($per_page ?? 10);
        foreach ($rewords as $reword) {
            $reword->business_name = Profile::where('user_id', $reword->partner_id)->first()->business_name;
        }
        return $rewords;
    }
    public function viewReward(?int $id)
    {
        $reword = Reward::where('status', 'Enable')
            ->where('admin_approved', true)
            ->where('expiration_date', '>', Carbon::now())
            ->where('id', $id)
            ->first();

        $reword->business_name = Profile::where('user_id', $reword->partner_id)->first()->business_name;

        $reword->already_redeemed = Redemption::where('user_id', Auth::id())
            ->where('reward_id', $id)
            ->exists() ? true : false;

        return $reword;
    }
    public function redeem(int $rewardId)
    {
        $already_redeemed = Redemption::where('user_id', Auth::id())
            ->where('reward_id', $rewardId)
            ->exists();

        if ($already_redeemed) {
            return ['already_redeemed' => true];
        }

        $profile = Profile::where('user_id', Auth::id())->first();
        $available_points = $profile->total_points - $profile->used_points;


        $reward = Reward::where('id', $rewardId)
            ->where('status', 'Enable')
            ->where('expiration_date', '>=', Carbon::now())
            ->first();

        if (!$reward) {
            throw new \Exception("Reward not available for redemption.");
        }

        if ($available_points < $reward->purchase_point) {
            return false;
        }

        $profile->increment('used_points', $reward->purchase_point);

        return Redemption::create([
            'user_id' => Auth::id(),
            'reward_id' => $reward->id,
            'partner_id' => $reward->partner_id,
            'date' => Carbon::now(),
            'code' => 'C' . rand(100000, 999999),
            'status' => 'Redeemed',
        ]);
    }
    public function getRedeemHistory(?int $per_page)
    {
        $redeem_histories = Redemption::where('user_id', Auth::id())
            ->latest()
            ->with([
                'reward' => function ($q) {
                    $q->select('id', 'partner_id', 'title');
                },
                'reward.partner' => function ($q) {
                    $q->select('id', 'full_name', 'role', 'address', 'phone_number');
                },
                'reward.partner.profile' => function ($q) {
                    $q->select('id', 'user_id', 'user_name', 'business_name', 'category', 'description', 'business_hours');
                }
            ])
            ->paginate($per_page ?? 10);
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
                $q->select('id', 'full_name', 'role', 'address', 'phone_number');
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