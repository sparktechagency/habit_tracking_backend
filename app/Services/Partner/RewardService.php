<?php

namespace App\Services\Partner;

use App\Models\Reward;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class RewardService
{
    public function isProfileComplete(int $userId): bool
    {
        $user = User::with('profile')->where('id', $userId)->where('role', 'PARTNER')->first();
        if (!$user || !$user->profile) {
            return false;
        }
        $fieldsToCheck = [
            $user->phone_number,
            $user->address,
            $user->profile->user_name,
            $user->profile->business_name,
            $user->profile->category,
            $user->profile->description,
            $user->profile->business_hours,
        ];
        foreach ($fieldsToCheck as $field) {
            if (empty($field)) {
                return false;
            }
        }
        return true;
    }
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
            $reward->status = $reward->status == 'Enable' ? 'Disable' : 'Enable';
            $reward->save();
        }
        return $reward;
    }
    public function getRewards()
    {
        $currentDate = Carbon::now()->toDateString();

        $rewards = Reward::where('partner_id', Auth::id())
            ->whereDate('expiration_date', '>=', $currentDate)
            ->latest()
            ->get();

        return  $rewards;
         
    }
}