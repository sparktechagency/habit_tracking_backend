<?php

namespace App\Services\Admin;

use App\Models\Challenge;
use App\Models\ChallengeGroup;
use App\Models\ChallengeLog;
use App\Models\GroupHabit;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardService
{
    public function dashboardInfo()
    {
        $total_users = User::all()->count();
        $form_yesterday_users = User::whereDate('created_at', Carbon::now()->subDay())->count();

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

        $total_completed_challenges_average_rate = $groups->avg('group_progress');
        $total_completed_challenges_average_rate;

        return [
            'total_users' => $total_users,
            'from_yesterday' => $form_yesterday_users,
            'total_challenges' => $total_challenges,
            'from_last_week' => $form_last_week_challenges,
            'total_revenues' => $total_revenues,
            'form_last_week_revenues' => '+' . $form_last_week_revenues_percentage . '%',
            'challenge_completion_rate' => $total_completed_challenges,
            'total_challenge_completed' => $total_completed_challenges_average_rate . '%',

        ];
    }

    public function userChart()
    {
        return 'user chart';
    }

    public function groupChart()
    {
        return 'group chart';
    }

    public function topChallengeChart()
    {
        return 'top challenge chart';
    }

    public function revenueChart()
    {
        return 'revenue chart';
    }
}