<?php

namespace App\Services\User;

use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\Plan;
use App\Models\Profile;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

use function PHPUnit\Framework\isArray;

class HabitService
{
    public function addNewHabit(array $data): Habit
    {
        return Habit::create([
            'user_id' => Auth::id(),
            'habit_name' => $data['habit_name']
        ]);
    }
    public function getHabits($isArchived = null, ?string $search = null)
    {
        $today = Carbon::now();

        $query = Habit::with([
            'logs' => function ($q) use ($today) {
                $q->whereDate('done_at', $today);
            }
        ])->where('user_id', Auth::id())
            ->orderByDesc('created_at');

        if ($isArchived == 1) {
            $query->where('isArchived', true);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('habit_name', 'LIKE', "%{$search}%");
            });
        }

        return $query->get();
    }
    public function viewHabit(int $id): ?Habit
    {
        return Habit::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();
    }
    public function deleteHabit(int $id): bool
    {
        $habit = Habit::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();
        if (!$habit)
            return false;
        return $habit->delete();
    }
    public function archivedHabit(int $id): ?Habit
    {
        $habit = Habit::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();
        if ($habit) {
            $habit->isArchived = $habit->isArchived === 0 ? true : false;
            $habit->save();
        }
        return $habit;
    }
    public function doneHabit(int $habitId)
    {
        $today = Carbon::today();

        $log = HabitLog::where('habit_id', $habitId)
            ->whereDate('created_at', $today)
            ->first();

        if ($log) {
            return false;
        }

        $profile = Profile::where('user_id', Auth::id())->first();

        $plan = Plan::where('user_id', Auth::id())->latest()->first();

        if ($plan) {
            if ($plan->renewal >= Carbon::now()) {
                $is_premium_check = true;
            } else {
                $is_premium_check = false;
            }
        } else {
            $is_premium_check = false;
        }

        if ($is_premium_check == false) {
            $free = Subscription::where('plan_name', 'Free')->first();
            if (in_array("Premium rewards (earn point 2x)", $free->features)) {
                // return '2x';
                $profile->increment('total_points', 2);
            } else {
                // return '1x';
                $profile->increment('total_points', 1);
            }
        } elseif ($is_premium_check == true) {
            if (in_array("Premium rewards (earn point 2x)", json_decode($plan->features))) {
                // return 'primium user 2x';
                $profile->increment('total_points', 2);
            }else{
                // return '1x';
                $profile->increment('total_points', 1);
            }
        }

        $totalPoints = $profile->total_points;

        $profile->level = match (true) {
            $totalPoints >= 1 && $totalPoints <= 100 => 1,
            $totalPoints >= 101 && $totalPoints <= 300 => 2,
            $totalPoints >= 301 && $totalPoints <= 600 => 3,
            $totalPoints >= 601 && $totalPoints <= 1000 => 4,
            $totalPoints >= 1001 && $totalPoints <= 1500 => 5,
            default => 0,
        };

        $profile->save();

        return HabitLog::create([
            'habit_id' => $habitId,
            'user_id' => Auth::id(),
            'status' => 'Completed',
            'done_at' => Carbon::now()
        ]);
    }

}