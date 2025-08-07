<?php

namespace App\Services\Partner;

use App\Models\Reward;
use Illuminate\Support\Facades\Auth;

class RewardService
{
    public function addReward(array $data): Reward
    {
        return Reward::create($data);
    }

    public function enableDisableReward(int $id): ?Reward
    {
        $reward = Reward::where('id', $id)
            ->where('partner_id', Auth::id())
            ->first();

        if ($reward) {
            $reward->status = $reward->status === 'Enable' ? 'disable' : 'Enable';
            $reward->save();
        }

        return $reward;
    }

    public function getRewards()
    {
        return Reward::where('partner_id', Auth::id())->latest()->get();
    }
}