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
            'date' => Carbon::now(),
            'code' => 'C' . rand(100000, 999999),
            'status' => 'Redeemed',
        ]);
    }
}