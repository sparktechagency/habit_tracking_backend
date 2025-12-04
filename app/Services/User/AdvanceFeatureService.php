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
        $subscriptions = Subscription::latest()->get();
        foreach ($subscriptions as $subscription) {
            $calculated = $subscription->price * ($subscription->discount / 100);
            $subscription->discount_amount = round($calculated, 2);
        }
        return $subscriptions;
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
    public function modeTrackLineGraph(?int $cycle = null)
    {
        $userId = Auth::id();

        // 1. First Completed Habit Date OR default = current month
        $firstLog = HabitLog::where('user_id', $userId)
            ->where('status', 'Completed')
            ->orderBy('done_at', 'asc')
            ->first();

        if ($firstLog) {
            $firstDoneAt = Carbon::parse($firstLog->done_at)->startOfMonth();
        } else {
            // No habit_log exists → start from current month
            $firstDoneAt = Carbon::now()->startOfMonth();
        }

        // 2. Total months passed from first month → now
        $monthsPassed = $firstDoneAt->diffInMonths(Carbon::now());

        // Total cycle count
        $totalCycles = floor($monthsPassed / 12) + 1;

        // 3. Requested cycle, default = latest cycle
        $requestedCycle = $cycle ?? $totalCycles;

        if ($requestedCycle < 1 || $requestedCycle > $totalCycles) {
            return [
                'message' => "Invalid cycle number. Available cycles: 1 - {$totalCycles}"
            ];
        }

        // 4. Determine cycle start & end
        $cycleStart = $firstDoneAt->copy()->addMonths(($requestedCycle - 1) * 12);
        $cycleEnd = $cycleStart->copy()->addMonths(11)->endOfMonth();

        // 5. Fetch logs inside that cycle
        $completedLogs = HabitLog::where('user_id', $userId)
            ->where('status', 'Completed')
            ->whereBetween('done_at', [$cycleStart, $cycleEnd])
            ->selectRaw('YEAR(done_at) as year, MONTH(done_at) as month, COUNT(*) as completed_count')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // 6. Build static 12-month graph
        $graphData = [];
        $cursor = $cycleStart->copy();

        for ($i = 0; $i < 12; $i++) {

            $record = $completedLogs->firstWhere(function ($log) use ($cursor) {
                return ($log->year == $cursor->year) && ($log->month == $cursor->month);
            });

            $graphData[] = [
                'month' => $cursor->format('M'),
                'month_year' => $cursor->format('M-y'),
                'completed_count' => $record->completed_count ?? 0,
            ];

            $cursor->addMonth();
        }

        // 7. Calculate max completed count
        $maxCompletedCount = collect($graphData)->max('completed_count');

        // Build cycle dropdown array
        $cycle_arr = [];

        for ($i = 1; $i <= $totalCycles; $i++) {

            $start = $firstDoneAt->copy()->addMonths(($i - 1) * 12);
            $end = $start->copy()->addMonths(11);

            $cycle_arr[$i] = $start->format('My') . '-' . $end->format('My');
        }

        // 8. Response
        return [
            'cycle_list' => $cycle_arr,
            'max_completed_count' => $maxCompletedCount < 10 ? 10 : $maxCompletedCount,
            'current_cycle' => $requestedCycle,
            'total_cycles' => $totalCycles,
            'cycle_start' => $cycleStart->format('F Y'),
            'cycle_end' => $cycleEnd->format('F Y'),
            'data' => $graphData
        ];
    }
    public function sayOnBarChart(?int $cycle = null)
    {
        $userId = Auth::id();

        // 1. First Entry Date
        $firstEntry = Entry::where('user_id', $userId)
            ->orderBy('date', 'asc')
            ->first();

        // if (!$firstEntry) {
        //     return [
        //         'data' => [],
        //         'message' => 'No entries found.'
        //     ];
        // }

        if ($firstEntry) {
            $firstDoneAt = Carbon::parse($firstEntry->date)->startOfMonth();
        } else {
            // No habit_log exists → start from current month
            $firstDoneAt = Carbon::now()->startOfMonth();
        }

        // $firstDate = Carbon::parse($firstEntry->date)->startOfMonth();

        // 2. Total months passed from first month till now
        $monthsPassed = $firstDoneAt->diffInMonths(Carbon::now());

        // Total cycles (12 months per cycle)
        $totalCycles = floor($monthsPassed / 12) + 1;

        // 3. Requested cycle (default = latest)
        $requestedCycle = $cycle ?? $totalCycles;

        if ($requestedCycle < 1 || $requestedCycle > $totalCycles) {
            return [
                'message' => "Invalid cycle number. Available cycles: 1 - {$totalCycles}"
            ];
        }

        // 4. Determine cycle start & end
        $cycleStart = $firstDoneAt->copy()->addMonths(($requestedCycle - 1) * 12);
        $cycleEnd = $cycleStart->copy()->addMonths(11)->endOfMonth();

        // 5. Fetch entries in this cycle
        $entries = Entry::where('user_id', $userId)
            ->whereBetween('date', [$cycleStart, $cycleEnd])
            ->selectRaw('YEAR(date) as year, MONTH(date) as month, COUNT(*) as total_say_no')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // 6. Build 12-month graph
        $graphData = [];
        $cursor = $cycleStart->copy();

        for ($i = 0; $i < 12; $i++) {
            $record = $entries->firstWhere(function ($entry) use ($cursor) {
                return $entry->year == $cursor->year && $entry->month == $cursor->month;
            });

            $graphData[] = [
                'month' => $cursor->format('M'),
                'month_year' => $cursor->format('M-y'),
                'total_say_no' => $record->total_say_no ?? 0,
            ];

            $cursor->addMonth();
        }

        // 7. Max value for chart scaling
        $maxCount = collect($graphData)->max('total_say_no');
        if ($maxCount < 10)
            $maxCount = 10;

        // 8. Build cycle dropdown
        $cycle_arr = [];
        for ($i = 1; $i <= $totalCycles; $i++) {
            $start = $firstDoneAt->copy()->addMonths(($i - 1) * 12);
            $end = $start->copy()->addMonths(11);
            $cycle_arr[$i] = $start->format('My') . '-' . $end->format('My');
        }

        // 9. Response
        return [
            'cycle_list' => $cycle_arr,
            'max_total_say_no' => $maxCount,
            'current_cycle' => $requestedCycle,
            'total_cycles' => $totalCycles,
            'cycle_start' => $cycleStart->format('F Y'),
            'cycle_end' => $cycleEnd->format('F Y'),
            'data' => $graphData
        ];
    }
}