<?php

namespace App\Services\Admin;

use App\Models\Challenge;
use App\Models\ChallengeGroup;
use App\Models\ChallengeLog;
use App\Models\GroupHabit;
use App\Models\HabitLog;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function dashboardInfo()
    {
        $total_users = User::where('role', '!=', 'ADMIN')->count();
        $form_yesterday_users = User::where('role', '!=', 'ADMIN')->whereDate('created_at', Carbon::now()->subDay())->count();

        $total_challenges = ChallengeGroup::all()->count();
        $form_last_week_challenges = ChallengeGroup::whereBetween('created_at', [Carbon::now()->subDays(6), Carbon::now()])->count();

        $total_revenues = Transaction::sum('amount');
        $form_last_week_revenues = Transaction::whereBetween('created_at', [Carbon::now()->subDays(6), Carbon::now()])->sum('amount');
        $form_last_week_revenues_percentage = $total_revenues > 0
            ? round(($form_last_week_revenues / $total_revenues) * 100, 2)
            : 0;

        $total_completed_challenges = ChallengeGroup::where('status', 'Completed')->count();

        $query = ChallengeGroup::where('status', 'Completed')->orderBy('created_at', 'desc');
        $groups = $query->get();

        $groups->each(function ($group) {
            $group->max_count = 100;

            $groupId = $group->id;
            $habit_count = GroupHabit::where('challenge_group_id', $groupId)->count();

            $group_completed = ChallengeLog::where('challenge_group_id', $groupId)
                ->where('status', 'Completed')
                ->count();
            $group->group_progress = $habit_count > 0 ? round(($group_completed / $habit_count) * 100) : 0;

            $group->makeHidden('members');
            $group->makeHidden('group_habits');
        });

        $total_completed_challenges_average_rate = $groups->avg('group_progress') ?? 0;

        return [
            'total_users' => $total_users,
            'from_yesterday' => $form_yesterday_users,
            'total_challenges' => $total_challenges,
            'from_last_week' => $form_last_week_challenges,
            'total_revenues' => '$'.$total_revenues,
            'form_last_week_revenues' => '+' . $form_last_week_revenues_percentage . '%',
            'challenge_completion_rate' => round($total_completed_challenges_average_rate, 2) . '%',
            'total_challenge_completed' => $total_completed_challenges,

        ];
    }

    public function userChart()
    {
        $sixMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();

        $users = User::select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"),
            DB::raw('COUNT(*) as count')
        )
            ->where('role', 'USER')
            ->where('created_at', '>=', $sixMonthsAgo)
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->keyBy('ym');

        $monthlySignupUsers = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $key = $date->format('Y-m');
            $monthlySignupUsers[] = [
                'month' => $date->format('M'),
                'count' => $users[$key]->count ?? 0
            ];
        }

        $partners = User::select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"),
            DB::raw('COUNT(*) as count')
        )
            ->where('role', 'PARTNER')
            ->where('created_at', '>=', $sixMonthsAgo)
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->keyBy('ym');

        $monthlySignupPartners = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $key = $date->format('Y-m');
            $monthlySignupPartners[] = [
                'month' => $date->format('M'),
                'count' => $partners[$key]->count ?? 0
            ];
        }

        return [
            'new_users' => $monthlySignupUsers,
            'new_partners' => $monthlySignupPartners,
        ];
    }

    public function groupChart()
    {
        $sixMonthsAgo = Carbon::now()->subMonths(5)->startOfMonth();

        $challenge_start = ChallengeGroup::select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"),
            DB::raw('COUNT(*) as count')
        )
            ->where('status', 'Active')
            ->where('created_at', '>=', $sixMonthsAgo)
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->keyBy('ym');

        $monthlyChallengeStart = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $key = $date->format('Y-m');
            $monthlyChallengeStart[] = [
                'month' => $date->format('M'),
                'count' => $challenge_start[$key]->count ?? 0
            ];
        }

        $challenge_completion = ChallengeGroup::select(
            DB::raw("DATE_FORMAT(created_at, '%Y-%m') as ym"),
            DB::raw('COUNT(*) as count')
        )
            ->where('status', 'Completed')
            ->where('created_at', '>=', $sixMonthsAgo)
            ->groupBy('ym')
            ->orderBy('ym')
            ->get()
            ->keyBy('ym');

        $monthlyChallengeCompletion = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $key = $date->format('Y-m');
            $monthlyChallengeCompletion[] = [
                'month' => $date->format('M'),
                'count' => $challenge_completion[$key]->count ?? 0
            ];
        }

        return [
            'challenge_start' => $monthlyChallengeStart,
            'challenge_completion' => $monthlyChallengeCompletion,
        ];
    }

    public function topHabitChart(?int $filter)
    {

        $days = $filter;

        $startDate = Carbon::now()->subDays($days ?? 30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // habit_logs থেকে habit-wise completed count আনা
        $topHabits = HabitLog::join('habits', 'habit_logs.habit_id', '=', 'habits.id')
            ->where('habit_logs.status', 'Completed')
            ->whereBetween('habit_logs.done_at', [$startDate, $endDate])
            ->select('habits.habit_name', DB::raw('COUNT(habit_logs.id) as completed_count'))
            ->groupBy('habits.habit_name')
            ->orderByDesc('completed_count')
            ->take(5) // top 5 habits
            ->get();

        return [
            'data' => $topHabits,
        ];
    }

    public function topChallengeChart(?int $filter)
    {
        $days = $filter;

        $startDate = Carbon::now()->subDays($days ?? 30)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // challenge_logs থেকে habit-wise completed count আনা
        $topHabits = ChallengeLog::join('group_habits', 'challenge_logs.group_habits_id', '=', 'group_habits.id')
            ->where('challenge_logs.status', 'Completed')
            ->whereBetween('challenge_logs.completed_at', [$startDate, $endDate])
            ->select('group_habits.habit_name', DB::raw('COUNT(challenge_logs.id) as completed_count'))
            ->groupBy('group_habits.habit_name')
            ->orderByDesc('completed_count')
            ->take(5) // top 5 habits
            ->get();

        return [
            'data' => $topHabits,
        ];
    }


    public function revenueChart()
    {
        $startDate = Carbon::now()->subMonths(5)->startOfMonth(); // last 6 months (including current)
        $endDate = Carbon::now()->endOfMonth();

        $feeData = Transaction::selectRaw('
            DATE_FORMAT(created_at, "%Y-%m") as month,
            COUNT(*) as total_transactions,
            SUM(amount) as total_amount
        ')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->mapWithKeys(function ($row) {
                return [
                    $row->month => [
                        'total_transactions' => $row->total_transactions,
                        'total_amount' => (float) $row->total_amount,
                    ]
                ];
            });

        $revenues_chart = collect();
        $period = CarbonPeriod::create($startDate, '1 month', $endDate);

        foreach ($period as $date) {
            $monthKey = $date->format('Y-m');
            $monthName = $date->format('M Y');

            $transactionsCount = $feeData[$monthKey]['total_transactions'] ?? 0;
            $totalAmount = $feeData[$monthKey]['total_amount'] ?? 0;

            $revenues_chart->push([
                'month' => $monthName,
                'total_transactions' => $transactionsCount >= 1000
                    ? number_format($transactionsCount / 1000, 1) . 'k'
                    : number_format($transactionsCount),
                'total_amount' => $totalAmount >= 1000
                    ? number_format($totalAmount / 1000, 2) . 'k'
                    : number_format($totalAmount, 2)
            ]);
        }

        return $revenues_chart;
    }
}