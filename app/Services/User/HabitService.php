<?php

namespace App\Services\User;

use App\Models\Habit;
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
    public function getHabits()
    {
        return Habit::where('user_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();
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
            $habit->status = $habit->status === 'Archived' ? null : 'Archived';
            $habit->save();
        }
        return $habit;
    }
}