<?php

namespace App\Services\Admin;

use App\Models\Challenge;
use App\Models\Plan;
use App\Models\Reward;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Type\Decimal;

class UserManagementService
{
    public function getUsers(?string $search, ?int $per_page)
    {
        $query = User::query();

        $query->with('profile')->where('role', 'USER');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%");
            });
        }

        $users = $query->latest()->paginate($per_page ?? 10);

        foreach ($users as $user) {
            $is_premium_user = false;
            $current_plan_name = 'Free';

            $plan = Plan::where('user_id', $user->id)->latest()->first();

            if ($plan && $plan->renewal >= Carbon::now()) {
                $is_premium_user = true;
                $current_plan_name = $plan->plan_name;
            }

            $user->subscription_plan_name = $is_premium_user ? $current_plan_name : 'Free';
        }

        return $users;
    }
    public function viewUser(?int $id)
    {
        $user = User::with('profile')
            ->where('id', $id)
            ->first();

        if ($user->role == 'PARTNER') {
            $user->total_rewards = Reward::where('partner_id', $id)->count();
            $user->types = 'Physical';
        }

        return $user;
    }
    public function blockUnblockUser(?int $id)
    {
        $user = User::where('id', $id)
            ->first();

        if ($user) {
            $user->status = $user->status == 'Active' ? 'Blocked' : 'Active';
            $user->save();
        }

        return $user;
    }
}