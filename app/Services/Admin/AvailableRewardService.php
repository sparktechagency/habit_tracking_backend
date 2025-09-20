<?php

namespace App\Services\Admin;

use App\Models\Challenge;
use App\Models\Reward;

class AvailableRewardService
{
    public function getRewards(?string $search)
    {
        $query = Reward::where('admin_approved', '!=', 'Canceled');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('challenge_type', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");

            });
        }

        return $query->latest()->paginate(10);
    }
    public function viewReward(?int $id)
    {
        return Reward::where('admin_approved', '!=', 'Canceled')
            ->where('id', $id)
            ->first();
    }
    public function approvedReward(?int $rewardId)
    {
        $reward = Reward::where('id', $rewardId)->first();
        $reward->admin_approved = 'Accepted';
        $reward->save();
        return $reward;
    }
    public function canceledReward(?int $rewardId)
    {
        $reward = Reward::where('id', $rewardId)->first();
        $reward->admin_approved = 'Canceled';
        $reward->save();
        return $reward;
    }
}