<?php

namespace App\Services\User;

use App\Models\Challenge;
use App\Models\ChallengeGroup;
use App\Models\ChallengeLog;
use App\Models\GroupHabit;
use App\Models\GroupMember;
use App\Models\Habit;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdvanceFeatureService
{
    public function basicInfo()
    {
        return 'basic-info';

    }

    public function getSubscriptions()
    {
        return 'getSubscriptions';

    }

    public function premiumUserCheck()
    {
        $plan = Transaction::where('user_id',Auth::id())->latest()->first();

        if($plan->renewal > Carbon::now()){
            return [
                'is_premium_check' => true,
            ];
        }else{
            return [
                'is_premium_check' => false,
            ];
        }

    }

    public function habitCalendar(int $year, int $month)
    {
        $userId = Auth::id();

        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $habits = Habit::where('user_id', $userId)
            ->whereBetween('done_at', [$startOfMonth, $endOfMonth])
            ->get();

        $groupedHabits = $habits->groupBy('habit_name');

        $result = [];

        foreach ($groupedHabits as $habitName => $habitEntries) {
            $completedDays = $habitEntries
                ->where('status', 'Completed')
                ->groupBy(fn($habit) => Carbon::parse($habit->done_at)->day);

            $daysInMonth = $startOfMonth->daysInMonth;
            $calendar = [];

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = Carbon::createFromDate($year, $month, $day)->toDateString();
                $calendar[] = [
                    'day' => $day,
                    'date' => $date,
                    'completed' => isset($completedDays[$day]),
                    'habit_name' => $habitName,
                ];
            }

            $result[] = [
                'habit_name' => $habitName,
                'total_complete_count' => $habitEntries->where('status', 'Completed')->count(),
                'calendar' => $calendar,
            ];
        }

        $total_workout = array_sum(array_column($result, 'total_complete_count'));

        

        return [
            'total_workout' => $total_workout,
            'result' => $result,
        ];
    }

    public function modeTrackLineGraph()
    {
        return 'modeTrackLineGraph';

    }

    public function sayOnBarChart()
    {
        return 'sayOnBarChart';

    }
}