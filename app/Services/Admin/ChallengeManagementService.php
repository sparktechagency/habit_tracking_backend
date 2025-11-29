<?php

namespace App\Services\Admin;

use App\Models\Challenge;
use App\Models\ChallengeGroup;
use App\Models\ChallengeLog;
use App\Models\Reward;

class ChallengeManagementService
{
    public function getActiveChallenges(?string $search, ?int $per_page)
    {
        $query = ChallengeGroup::withCount('members')
            ->with('group_habits')
            ->where('status', 'Active')
            ->orderBy('created_at', 'desc');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('group_name', 'LIKE', "%{$search}%")
                    ->orWhere('challenge_type', 'LIKE', "%{$search}%");
            });
        }

        $groups = $query->paginate($per_page ?? 10);

        $groups->each(function ($group) {
            $group->max_count = 100;

            $group->makeHidden('members');
            $group->makeHidden('group_habits');
        });
        return $groups;
    }
    public function viewActiveChallenge(?int $id)
    {
        $query = ChallengeGroup::withCount('members')
            ->with('group_habits')
            ->where('id', $id)
            ->where('status', 'Active')
            ->orderBy('created_at', 'desc');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('group_name', 'LIKE', "%{$search}%")
                    ->orWhere('challenge_type', 'LIKE', "%{$search}%");
            });
        }

        $group = $query->first();

        $group->max_count = 100;

        $totalTasks = $group->group_habits->count();
        $totalMembers = $group->members_count;
        $completedCount = ChallengeLog::where('challenge_group_id', $group->id)
            // ->whereDate('date', $today)
            ->where('status', 'Completed')
            ->count();
        $expectedGroupTasks = $totalTasks * $totalMembers;
        $group->completetion_rate = $expectedGroupTasks > 0
            ? round(($completedCount / $expectedGroupTasks) * 100)
            : 0;

        $group->tasks = $group->group_habits()->pluck('habit_name')->toArray();

        $group->makeHidden('members');
        $group->makeHidden('group_habits');

        return $group;
    }
    public function getCompletedChallenges(?string $search, ?int $per_page)
    {
        $query = ChallengeGroup::withCount('members')
            ->with('group_habits')
            ->where('status', 'Completed')
            ->orderBy('created_at', 'desc');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('group_name', 'LIKE', "%{$search}%")
                    ->orWhere('challenge_type', 'LIKE', "%{$search}%");
            });
        }

        $groups = $query->paginate($per_page ?? 10);

        $groups->each(function ($group) {
            $group->max_count = 100;

            $totalTasks = $group->group_habits->count();
            $totalMembers = $group->members_count;
            $completedCount = ChallengeLog::where('challenge_group_id', $group->id)
                // ->whereDate('date', $today)
                ->where('status', 'Completed')
                ->count();
            $expectedGroupTasks = $totalTasks * $totalMembers;
            $group->completetion_rate = $expectedGroupTasks > 0
                ? round(($completedCount / $expectedGroupTasks) * 100)
                : 0;

            $group->tasks = $group->group_habits()->pluck('habit_name')->toArray();


            $group->makeHidden('members');
            $group->makeHidden('group_habits');
        });

        return $groups;
    }
    public function viewCompletedChallenge(?int $id)
    {
        $query = ChallengeGroup::withCount('members')
            ->with('group_habits')
            ->where('id', $id)
            ->where('status', 'Completed')
            ->orderBy('created_at', 'desc');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('group_name', 'LIKE', "%{$search}%")
                    ->orWhere('challenge_type', 'LIKE', "%{$search}%");
            });
        }

        $group = $query->first();

        $group->max_count = 100;

        $totalTasks = $group->group_habits->count();
        $totalMembers = $group->members_count;
        $completedCount = ChallengeLog::where('challenge_group_id', $group->id)
            // ->whereDate('date', $today)
            ->where('status', 'Completed')
            ->count();
        $expectedGroupTasks = $totalTasks * $totalMembers;
        $group->completetion_rate = $expectedGroupTasks > 0
            ? round(($completedCount / $expectedGroupTasks) * 100)
            : 0;

        $group->tasks = $group->group_habits()->pluck('habit_name')->toArray();

        $group->makeHidden('members');
        $group->makeHidden('group_habits');

        return $group;
    }
}