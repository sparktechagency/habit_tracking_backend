<?php

namespace App\Services\Admin;

use App\Models\Challenge;
use App\Models\Subscription;
use App\Models\User;
use Ramsey\Uuid\Type\Decimal;

class UserManagementService
{
    public function getUsers(?string $search)
    {
        $query = User::query();

        $query->with('profile')->where('role', 'USER');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%");
            });
        }

        return $query->get();
    }

    public function viewUser(?int $id)
    {
        $user = User::with('profile')
            ->where('id', $id)
            ->first();
        return $user;
    }

    public function blockUnblockUser(?int $id){
         $user = User::where('id', $id)
            ->first();

        if ($user) {
            $user->status = $user->status == 'Active' ? 'Blocked' : 'Active';
            $user->save();
        }

        return $user;
    }
}