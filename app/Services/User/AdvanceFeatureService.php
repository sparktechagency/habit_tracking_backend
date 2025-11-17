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
use App\Models\Plan;
use App\Models\Profile;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdvanceFeatureService
{
    public function basicInfo(?int $userId)
    {
        $authId = $userId ?? Auth::id();
        $now = Carbon::now();

        $habit_lists = Habit::where('user_id', $authId)
            ->select('id', 'habit_name')
            ->get();

        $habit_logs = HabitLog::where('user_id', $authId)->count();

        $arr = [];
        foreach ($habit_lists as $habit) {
            $completedDays = HabitLog::where('user_id', $authId)
                ->where('status', 'Completed')
                ->where('habit_id', $habit->id)
                ->whereMonth('done_at', $now->month)
                ->whereYear('done_at', $now->year)
                ->orderBy('done_at')
                ->pluck('done_at')
                ->map(fn($date) => Carbon::parse($date)->toDateString())
                ->unique()
                ->values();

            $longest = 0;
            $current = 0;
            $previousDay = null;

            foreach ($completedDays as $day) {
                if ($previousDay && Carbon::parse($previousDay)->diffInDays(Carbon::parse($day)) == 1) {
                    $current++;
                } else {
                    $current = 1;
                }
                $longest = max($longest, $current);
                $previousDay = $day;
            }

            $arr[] = [
                'habit_id' => $habit->id,
                'habit_name' => $habit->habit_name,
                'longest_streak' => $longest
            ];
        }

        $longestValues = collect($arr)->pluck('longest_streak')->toArray();

        $avg = count($longestValues) > 0
            ? round(array_sum($longestValues) / count($longestValues), 2)
            : 0;

        $max = count($longestValues) > 0
            ? max($longestValues)
            : 0; // ✅ safe default

        $user = User::where('id', $authId)
            ->select('id', 'full_name', 'role', 'avatar')
            ->first();

        $completed_group_challenge = ChallengeGroup::where('status', 'Completed')
            ->whereHas('members', function ($q) use ($authId) {
                $q->where('user_id', $authId);
            })->count();

        $profile = Profile::where('user_id', $authId)->first();

        return [
            'user' => $user,
            'level' => $profile->level ?? 0,
            'total_points' => $profile->total_points ?? 0,
            'used_points' => $profile->used_points ?? 0,
            'remaining_points' => ($profile->total_points ?? 0) - ($profile->used_points ?? 0),
            'completed_habit' => $habit_logs,
            'longest_streaks_avg' => round($avg),
            'longest_streaks_max' => $max,
            'longest_streak_month' => $now->format('F Y'),
            'completed_group_challenge' => $completed_group_challenge,
            'say_no' => Entry::where('user_id', $authId)->count()
        ];
    }
    public function getSubscriptions()
    {
        return Subscription::latest('id')->get();
    }
    public function premiumUserCheck()
    {
        $plan = Plan::where('user_id', Auth::id())->latest()->first();
        if ($plan) {
            if ($plan->renewal >= Carbon::now()) {
                $plan->features = json_decode($plan->features);
                return [
                    'is_premium_user' => true,
                    'current_plan' => $plan,
                ];
            } else {
                return [
                    'is_premium_check' => false,
                    'current_plan' => Subscription::where('plan_name', 'Free')->first(),
                ];
            }
        } else {
            return [
                'is_premium_check' => false,
                'current_plan' => Subscription::where('plan_name', 'Free')->first(),
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

    public function modeTrackLineGraph1(?string $filter)
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
    public function modeTrackLineGraphOld(?string $filter)
    {
        $userId = Auth::id();
        $months = 12;

        // current or last 6 month / 12 month
        // $months = $filter == 'current' ? 1 : 12;

        // // Filter range
        // switch ($filter) {
        //     case 'last_3_months':
        //         $months = 3;
        //         break;
        //     case 'last_6_months':
        //         $months = 6;
        //         break;
        //     case 'last_year':
        //         $months = 12;
        //         break;
        //     case 'current':
        //     default:
        //         $months = 1;
        //         break;
        // }

        $startDate = Carbon::now()->subMonths($months - 1)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $completedLogs = HabitLog::where('user_id', $userId)
            ->where('status', 'Completed')
            ->whereBetween('done_at', [$startDate, $endDate])
            ->selectRaw('YEAR(done_at) as year, MONTH(done_at) as month, COUNT(*) as completed_count')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $graphData = [];
        $period = Carbon::parse($startDate)->monthsUntil($endDate);

        foreach ($period as $date) {
            $year = $date->year;
            $month = $date->month;

            $record = $completedLogs->firstWhere(
                fn($log) =>
                $log->year == $year && $log->month == $month
            );

            $graphData[] = [
                'month' => $date->format('F Y'),
                'completed_count' => $record->completed_count ?? 0,
            ];
        }

        return [
            'start' => $startDate->format('F Y'),
            'end' => $endDate->format('F Y'),
            'data' => $graphData,
        ];
    }

    public function modeTrackLineGraph(?string $filter)
    {
        $userId = Auth::id();
        $monthsToShow = 12; // সবসময় 12 মাস দেখাবে

        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // fetch all completed logs for last 12 months
        $startDate = Carbon::now()->subMonths($monthsToShow - 1)->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        $completedLogs = HabitLog::where('user_id', $userId)
            ->where('status', 'Completed')
            ->whereBetween('done_at', [$startDate, $endDate])
            ->selectRaw('YEAR(done_at) as year, MONTH(done_at) as month, COUNT(*) as completed_count')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $graphData = [];

        for ($i = 0; $i < $monthsToShow; $i++) {
            $monthNumber = ($currentMonth + $i - 1) % 12 + 1;
            $yearNumber = $currentYear + floor(($currentMonth + $i - 1) / 12);

            $record = $completedLogs->firstWhere(function ($log) use ($monthNumber, $yearNumber) {
                return $log->month == $monthNumber && $log->year == $yearNumber;
            });

            $graphData[] = [
                'month' => Carbon::create()->year($yearNumber)->month($monthNumber)->format('M Y'),
                'completed_count' => $record->completed_count ?? 0,
            ];
        }

        return [
            'data' => $graphData,
        ];
    }

    public function sayOnBarChartOld()
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
    public function sayOnBarChart()
    {
        $userId = Auth::id();
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // সব এন্ট্রি নিয়ে আসা দুই বছরের জন্য (কারণ ডিসেম্বর পার হলে পরের বছরের জন্যও লাগবে)
        $entries = Entry::where('user_id', $userId)
            ->whereYear('date', '>=', $currentYear)
            ->whereYear('date', '<=', $currentYear + 1)
            ->selectRaw('YEAR(date) as year, MONTH(date) as month, COUNT(*) as total_say_no')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $months = [];

        for ($i = 0; $i < 12; $i++) {
            $monthNumber = ($currentMonth + $i - 1) % 12 + 1;
            $yearNumber = $currentYear + floor(($currentMonth + $i - 1) / 12);

            $found = $entries->firstWhere(function ($entry) use ($monthNumber, $yearNumber) {
                return $entry->month == $monthNumber && $entry->year == $yearNumber;
            });

            $months[] = [
                'month' => Carbon::create()->year($yearNumber)->month($monthNumber)->format('M-Y'),
                'total_say_no' => $found->total_say_no ?? 0,
            ];
        }

        return [
            'data' => $months
        ];
    }




}