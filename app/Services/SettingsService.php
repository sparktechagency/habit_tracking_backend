<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SettingsService
{
    public function editProfile(array $data): array
    {
        $user = Auth::user();

        $userData = collect($data)->only([
            'full_name',
            'phone_number',
            'address',
        ])->toArray();

        if (isset($data['avatar'])) {
            if ($user->avatar && Storage::disk('public')->exists(str_replace('/storage/', '', $user->avatar))) {
                Storage::disk('public')->delete(str_replace('/storage/', '', $user->avatar));
            }
            $path = $data['avatar']->store('avatars', 'public');
            $userData['avatar'] = '/storage/' . $path;
        }

        $profileData = collect($data)->only([
            'business_name',
            'user_name',
            'category',
            'description',
            'business_hours',
        ])->toArray();

        if (!empty($userData)) {
            $user->update($userData);
        }

        if (!empty($profileData)) {
            $user->profile()->update($profileData);
        }

        return [
            'success' => true,
            'data' => $user->load('profile')
        ];
    }
}