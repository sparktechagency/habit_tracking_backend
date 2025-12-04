<?php

namespace App\Services\Partner;

use App\Models\Reward;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

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
        $data['admin_approved'] = 'Pending';
        $data['expiration_date'] = Carbon::createFromFormat('d/m/Y', $data['expiration_date'])
            ->format('Y-m-d');

        // $data['location'] = Auth::user()->address;
        // $data['latitude'] = Auth::user()->latitude;
        // $data['longitude'] = Auth::user()->longitude;

        if (isset($data['image']) && $data['image']->isValid()) {
            $path = $data['image']->store('images', 'public');
            $data['image'] = Storage::url($path);
        }

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
    public function getRewards(?int $per_page)
    {
        $currentDate = Carbon::now()->toDateString();

        $rewards = Reward::where('partner_id', Auth::id())
            ->whereDate('expiration_date', '>=', $currentDate)
            ->latest()
            ->paginate($per_page ?? 10);

        return $rewards;

    }

    public function viewReward($id)
    {
        $reward = Reward::find($id);

        if (!$reward) {
            throw new \Exception("Reward not found.");
        }

        return $reward;
    }


    public function editReward(array $data, $id)
    {
        $reward = Reward::find($id);

        if (!$reward) {
            throw new \Exception("Reward not found.");
        }

         if (isset($data['image'])) {
            if ($reward->image && Storage::disk('public')->exists(str_replace('/storage/', '', $reward->image))) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $reward->image));
            }
            $path = $data['image']->store('images', 'public');
            $data['image'] = '/storage/' . $path;
        }

        $reward->update($data);

        return $reward;
    }


    public function deleteReward($id)
    {
        $reward = Reward::find($id);

        if (!$reward) {
            throw new \Exception("Reward not found.");
        }

        $reward->delete();

        return true;
    }
}