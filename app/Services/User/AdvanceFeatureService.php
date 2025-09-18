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
    public function basicInfo()
    {




        $userId = Auth::id();
        $now = Carbon::now();

        // // এই মাসের completed habit_logs আনা হচ্ছে
        // $completedDays = HabitLog::where('user_id', $userId)
        //     ->where('status', 'Completed')
        //     ->where('habit_id', 1)
        //     ->whereMonth('done_at', $now->month)
        //     ->whereYear('done_at', $now->year)
        //     ->orderBy('done_at')
        //     ->pluck('done_at')
        //     ->map(fn($date) => Carbon::parse($date)->toDateString())
        //     ->unique()
        //     ->values();

        // $longestStreak = 0;
        // $currentStreak = 0;

        // for ($i = 0; $i < count($completedDays); $i++) {
        //     if ($i > 0 && Carbon::parse($completedDays[$i])->diffInDays(Carbon::parse($completedDays[$i - 1])) == 1) {
        //         $currentStreak++;
        //     } else {
        //         $currentStreak = 1;
        //     }
        //     $longestStreak = max($longestStreak, $currentStreak);
        // }

        // return response()->json([
        //     'month' => $now->format('F Y'),
        //     'longest_streak' => $longestStreak,
        // ]);







        $authId = Auth::id();

        $user = User::where('id', $authId)
            ->select('id', 'full_name', 'role')
            ->first();

        $completed_group_challenge = ChallengeGroup::where('status', 'Completed')
            ->whereHas('members', function ($q) use ($authId) {
                $q->where('user_id', $authId);
            })->count();

        $profile = Profile::where('user_id', $authId)->first();

        return [
            'user' => $user,
            'level' => $profile->level,
            'total_points' => $profile->total_points,
            'used_points' => $profile->used_points,
            'remaining_points' => $profile->total_points - $profile->used_points,
            'completed_habit' => Habit::where('user_id', Auth::id())->count(),
            'longest_streak' => $longestStreak??'working',
            'longest_streak_month' => $now->format('F Y')?? 'working',
            'completed_group_challenge' => $completed_group_challenge,
            'say_no' => Entry::where('user_id', Auth::id())->count()
        ];

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
    public function modeTrackLineGraph(?string $filter)
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