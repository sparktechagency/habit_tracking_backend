<?php

namespace App\Services\Partner;

use App\Models\Reward;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class RewardService
{
    public function addReward(array $data): Reward
    {
        $data['partner_id'] = Auth::id();
        $data['expiration_date'] = Carbon::createFromFormat('m/d/Y', $data['expiration_date'])
            ->format('Y-m-d');
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