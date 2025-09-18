<?php

namespace App\Services\User;

use App\Models\Challenge;
use App\Models\ChallengeGroup;
use App\Models\ChallengeLog;
use App\Models\Entry;
use App\Models\GroupHabit;
use App\Models\GroupMember;
use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\Subscription;
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
        return Subscription::latest('id')->get();
    }
    public function premiumUserCheck()
    {
        $plan = Transaction::where('user_id', Auth::id())->latest()->first();

        if ($plan->renewal > Carbon::now()) {
            return [
                'is_premium_check' => true,
            ];
        } else {
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
            ->with([
                'logs' => function ($query) use ($startOfMonth, $endOfMonth) {
                    $query->whereBetween('done_at', [$startOfMonth, $endOfMonth]);
                }
            ])
            ->get();

        $result = [];
        foreach ($habits as $habit) {
            $completedDays = $habit->logs
                ->where('status', 'Completed')
                ->groupBy(fn($log) => Carbon::parse($log->done_at)->day);

            $daysInMonth = $startOfMonth->daysInMonth;
            $calendar = [];
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = Carbon::createFromDate($year, $month, $day)->toDateString();
                $calendar[] = [
                    'day' => $day,
                    'date' => $date,
                    'completed' => isset($completedDays[$day])
                ];
            }

            $result[] = [
                'habit_name' => $habit->habit_name,
                'total_complete_count' => $habit->logs->where('status', 'Completed')->count(),
                'calendar' => $calendar,
            ];
        }

        $total_workout = array_sum(array_column($result, 'total_complete_count'));

        return [
            'total_workout' => $total_workout,
            'result' => $result,
        ];
    }
    public function modeTrackLineGraph(string $filter)
    {
        $userId = Auth::id();
        $month = $filter == 'current' ? Carbon::now() : Carbon::now()->subMonth();

        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $completedLogs = HabitLog::where('user_id', $userId)
            // ->where('habit_id', $habitId)
            ->where('status', 'Completed')
            ->whereBetween('done_at', [$startOfMonth, $endOfMonth])
            ->selectRaw('DATE(done_at) as day, COUNT(*) as completed_count')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $daysInMonth = $startOfMonth->daysInMonth;
        $graphData = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = Carbon::createFromDate($month->year, $month->month, $day)->toDateString();

            $count = $completedLogs->firstWhere('day', $date)->completed_count ?? 0;

            $graphData[] = [
                'day' => $day,
                'date' => $date,
                'completed_count' => $count,
            ];
        }

        return [
            'month' => $month->format('F Y'),
            'data' => $graphData,
        ];
    }
    public function sayOnBarChart()
    {
        $userId = Auth::id();
        $currentYear = Carbon::now()->year;

        $entries = Entry::where('user_id', $userId)
            ->whereYear('date', $currentYear)
            ->selectRaw('MONTH(date) as month, COUNT(*) as total_say_no')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $found = $entries->firstWhere('month', $m);
            $months[] = [
                'month' => Carbon::create()->month($m)->format('M'),
                'total_say_no' => $found->total_say_no ?? 0,
            ];
        }

        return [
            'year' => $currentYear,
            'data' => $months
        ];

    }
}