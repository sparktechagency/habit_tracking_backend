<?php

namespace App\Services\User;

use App\Models\Habit;
use App\Models\HabitLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class HabitService
{
    public function addNewHabit(array $data): Habit
    {
        return Habit::create([
            'user_id' => Auth::id(),
            'habit_name' => $data['habit_name']
        ]);
    }
    public function getHabits($isArchived = null)
    {
        $query = Habit::where('user_id', Auth::id())
            ->orderByDesc('created_at');

        if ($isArchived == 1) {
            $query->where('isArchived', true);
        } else {
            $query->where('isArchived', false);
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

        return HabitLog::create([
            'habit_id' => $habitId,
            'status' => 'Completed',
            'done_at' => Carbon::now()
        ]);
    }

}