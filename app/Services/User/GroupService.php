<?php

namespace App\Services\User;

use App\Models\Challenge;
use App\Models\ChallengeGroup;
use App\Models\ChallengeLog;
use App\Models\GroupHabit;
use App\Models\GroupMember;
use App\Models\Profile;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class GroupService
{
    public function getChallengeTypeLists()
    {
        $query = Challenge::query();
        return $query->latest()
            ->get()
            ->pluck('challenge_type');
    }
    public function createGroup(array $data): ChallengeGroup
    {
        $startDate = Carbon::now();
        $endDate = Carbon::now()->addDays(((int) $data['duration']) - 1);
        $group = ChallengeGroup::create([
            'user_id' => Auth::id(),
            'group_name' => $data['group_name'],
            'challenge_type' => $data['challenge_type'],
            'duration' => $data['duration'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'Active',
        ]);
        GroupMember::create([
            'challenge_group_id' => $group->id,
            'user_id' => Auth::id(),
            'status' => 'Active',
            'joined_at' => now(),
        ]);
        if (!empty($data['focus_on']) && is_array($data['focus_on'])) {
            foreach ($data['focus_on'] as $item) {
                GroupHabit::create([
                    'challenge_group_id' => $group->id,
                    'habit_name' => $item,
                ]);
            }
        }
        $days = $this->getChallengeDaysWithDates($group->start_date, $group->end_date);
        $today = Carbon::now()->toDateString();
        $currentDay = 0;
        for ($i = 0; $i < $group->duration; $i++) {
            if ($days[$i] == $today) {
                $currentDay = $i + 1;
                $currentDate = $days[$i];
            }
        }
        $total_habit_count = GroupHabit::where('challenge_group_id', $group->id)->pluck('id');
        for ($i = 0; $i < $total_habit_count->count(); $i++) {
            ChallengeLog::create([
                'challenge_group_id' => $group->id,
                'group_habits_id' => $total_habit_count[$i],
                'user_id' => Auth::id(),
                'day' => $currentDay,
                'date' => $currentDate,
                'status' => 'Incompleted'
            ]);
        }
        return $group;
    }
    public function getGroups(?string $search = null)
    {
        $authId = Auth::id();
        $today = now()->toDateString();
        $query = ChallengeGroup::withCount('members')
            ->with('group_habits')
            ->orderBy('created_at', 'desc');
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('group_name', 'LIKE', "%{$search}%")
                    ->orWhere('challenge_type', 'LIKE', "%{$search}%");
            });
        }

        $groups = $query->get();

        $groups->each(function ($group) use ($authId, $today) {
            $group->max_count = 100;
            $totalTasks = $group->group_habits->count();
            $totalMembers = $group->members_count;
            $completedCount = ChallengeLog::where('challenge_group_id', $group->id)
                ->whereDate('date', $today)
                ->where('status', 'Completed')
                ->count();
            $expectedGroupTasks = $totalTasks * $totalMembers;
            $group->group_daily_progress = $expectedGroupTasks > 0
                ? round(($completedCount / $expectedGroupTasks) * 100)
                : 0;
            $myCompleted = ChallengeLog::where('challenge_group_id', $group->id)
                ->where('user_id', $authId)
                ->whereDate('date', $today)
                ->where('status', 'Completed')
                ->count();
            $group->my_daily_progress = $totalTasks > 0
                ? round(($myCompleted / $totalTasks) * 100)
                : 0;
            $group->makeHidden('members');
            $group->makeHidden('group_habits');
        });
        return $groups;
    }
    public function viewGroup(int $id)
    {
        $authId = Auth::id();
        $today = now()->toDateString();
        $query = ChallengeGroup::withCount('members')
            ->with('group_habits')
            ->where('id', $id)
            ->orderBy('created_at', 'desc');
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('group_name', 'LIKE', "%{$search}%")
                    ->orWhere('challenge_type', 'LIKE', "%{$search}%");
            });
        }
        $groups = $query->get();
        $groups->each(function ($group) use ($authId, $today) {
            $group->max_count = 100;
            $totalTasks = $group->group_habits->count();
            $totalMembers = $group->members_count;
            $completedCount = ChallengeLog::where('challenge_group_id', $group->id)
                ->whereDate('date', $today)
                ->where('status', 'Completed')
                ->count();
            $expectedGroupTasks = $totalTasks * $totalMembers;
            $group->group_daily_progress = $expectedGroupTasks > 0
                ? round(($completedCount / $expectedGroupTasks) * 100)
                : 0;
            $myCompleted = ChallengeLog::where('challenge_group_id', $group->id)
                ->where('user_id', $authId)
                ->whereDate('date', $today)
                ->where('status', 'Completed')
                ->count();
            $group->my_daily_progress = $totalTasks > 0
                ? round(($myCompleted / $totalTasks) * 100)
                : 0;
            $group->makeHidden('members');
            $group->makeHidden('group_habits');
        });
        return $groups;
    }
    public function joinGroup(int $groupId): GroupMember|string
    {
        if (!ChallengeGroup::where('id', $groupId)->exists()) {
            throw new Exception("Group with ID {$groupId} does not exist.");
        }
        if (
            GroupMember::where('challenge_group_id', $groupId)
                ->where('user_id', Auth::id())->exists()
        ) {
            throw new Exception('You have already joined this group.');
        }
        $memberCount = GroupMember::where('challenge_group_id', $groupId)->count();
        if ($memberCount >= 100) {
            throw new Exception('Group is full. Maximum 100 members allowed.');
        }
        return GroupMember::create([
            'challenge_group_id' => $groupId,
            'user_id' => Auth::id(),
            'status' => 'Active',
            'joined_at' => now(),
        ]);
    }
    public function getChallengeDaysWithDates($startDate, $endDate)
    {
        $dates = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        while ($current <= $end) {
            $dates[] = $current->format('Y-m-d');
            $current->addDay();
        }

        return $dates;
    }
    public function logProgress(int $groupId)
    {
        $group = ChallengeGroup::where('id', $groupId)->first();
        if (!$group) {
            throw new Exception("Group with ID {$groupId} not found.");
        }

        if ($group->status == 'Completed') {
            throw new Exception('Group is expired.');
        }

        $days = $this->getChallengeDaysWithDates($group->start_date, $group->end_date);
        $today = Carbon::now()->toDateString();
        $currentDay = 0;
        for ($i = 0; $i < $group->duration; $i++) {
            if ($days[$i] == $today) {
                $currentDay = $i + 1;
                $currentDate = $days[$i];
            }
        }
        $in_group_check = GroupMember::where('user_id', Auth::id())->where('challenge_group_id', $groupId)->first();
        if (!$in_group_check) {
            throw new Exception('Your are not group member. please join this group.');
        }
        $day_check = ChallengeLog::where('challenge_group_id', $groupId)
            ->where('day', $currentDay)
            ->where('user_id', Auth::id())
            ->first();
        if ($day_check) {
            return null;
        }
        $total_habit_count = GroupHabit::where('challenge_group_id', $groupId)->pluck('id');
        for ($i = 0; $i < $total_habit_count->count(); $i++) {
            $create_log = ChallengeLog::create([
                'challenge_group_id' => $groupId,
                'group_habits_id' => $total_habit_count[$i],
                'user_id' => Auth::id(),
                'day' => $currentDay,
                'date' => $currentDate,
                'status' => 'Incompleted'
            ]);
        }
        return $create_log;
    }
    public function getTodayLogs(int $groupId): array
    {
        $authId = Auth::id();
        $today = Carbon::today()->toDateString();
        $myLogs = ChallengeLog::where('challenge_group_id', $groupId)
            ->whereDate('date', $today)
            ->where('user_id', $authId)
            ->get();
        $othersLogs = ChallengeLog::where('challenge_group_id', $groupId)
            ->whereDate('date', $today)
            ->where('user_id', '!=', $authId)
            ->get()
            ->groupBy('user_id');
        $members = GroupMember::where('challenge_group_id', $groupId)->get();
        $members = $members->sortBy(function ($member) use ($authId) {
            return $member->user_id == $authId ? 0 : 1;
        })->values();
        $habits = GroupHabit::where('challenge_group_id', $groupId)->get();
        $habit_count = GroupHabit::where('challenge_group_id', $groupId)->count();
        return [
            'group_members' => $members,
            'group_habits' => $habits,
            'my_logs' => $myLogs,
            'habit_count' => $habit_count,
            'others_logs' => $othersLogs
        ];
    }
    public function taskCompleted(int $logId)
    {
        $userLog = ChallengeLog::where('id', $logId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$userLog) {
            throw new Exception("User unauthorized. Log ID {$logId} is not this user.");
        }

        if ($userLog->status === 'Completed') {
            throw new Exception('This task is already completed.');
        }

        $userLog->update([
            'status' => 'Completed',
            'completed_at' => Carbon::now(),
        ]);

       
        $profile = Profile::where('user_id', Auth::id())->first();
        $profile->increment('total_points');

        $totalPoints = $profile->total_points;

        $profile->level = match (true) {
            $totalPoints >= 1 && $totalPoints <= 100 => 1,
            $totalPoints >= 101 && $totalPoints <= 300 => 2,
            $totalPoints >= 301 && $totalPoints <= 600 => 3,
            $totalPoints >= 601 && $totalPoints <= 1000 => 4,
            $totalPoints >= 1001 && $totalPoints <= 1500 => 5,
            default => 0,
        };

        $profile->save();

        return $userLog;
    }
    public function getDailySummaries(int $groupId)
    {
        $group = ChallengeGroup::find($groupId);
        if (!$group) {
            throw new Exception("Group with ID {$groupId} not found.");
        }

        $start_date = Carbon::parse($group->start_date)->toDateString();
        $end_date = Carbon::parse($group->end_date)->toDateString();

        $daysArray = $this->getChallengeDaysWithDates($start_date, $end_date);

        $logs = ChallengeLog::with('user')
            ->where('challenge_group_id', $groupId)
            ->whereBetween('date', [
                Carbon::parse($start_date)->startOfDay(),
                Carbon::parse($end_date)->endOfDay()
            ])
            ->get()
            ->groupBy(function ($log) {
                return Carbon::parse($log->date)->toDateString();
            });

        $today = Carbon::now()->toDateString();

        $daysArray = collect($daysArray)
            ->filter(fn($date) => $date <= $today)
            ->values()
            ->toArray();

        $groupUserIds = ChallengeLog::where('challenge_group_id', $groupId)
            ->pluck('user_id')
            ->unique()
            ->toArray();

        $summaries = [];

        foreach ($daysArray as $index => $dayDate) {
            $formattedDate = Carbon::parse($dayDate)->toDateString();
            $dayNumber = $index + 1;

            $dayLogs = $logs->get($formattedDate, collect());
            $usersByLog = $dayLogs->groupBy('user_id');

            $userProgress = [];
            $totalPercent = 0;

            foreach ($groupUserIds as $userId) {
                $userLogs = $usersByLog->get($userId, collect());

                if ($userLogs->isNotEmpty()) {
                    $totalTasks = $userLogs->count();
                    $completedTasks = $userLogs->where('status', 'Completed')->count();
                } else {
                    $totalTasks = GroupHabit::where('challenge_group_id', $groupId)->count();
                    $completedTasks = 0;
                }

                $progressPercent = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

                $userModel = $userLogs->first()?->user ?? User::find($userId);

                $statusList = $userLogs->pluck('status')->toArray();
                if (empty($statusList)) {
                    $statusList = array_fill(0, $totalTasks, 'Incompleted');
                }

                $userProgress[] = [
                    'user_id' => $userId,
                    'user_name' => $userModel->full_name ?? 'Unknown',
                    'progress' => $progressPercent,
                    'status' => $statusList,
                    'is_all_completed' => !in_array('Incompleted', $statusList),
                ];

                $totalPercent += $progressPercent;
            }

            $authId = Auth::id();
            usort($userProgress, function ($a, $b) use ($authId) {
                if ($a['user_id'] == $authId)
                    return -1;
                if ($b['user_id'] == $authId)
                    return 1;
                return 0;
            });

            $groupCompletion = count($groupUserIds) > 0 ? round($totalPercent / count($groupUserIds)) : 0;

            $summaries[] = [
                'date' => $formattedDate,
                'day' => $dayNumber,
                'group_completion' => $groupCompletion,
                'members' => $userProgress,
            ];
        }

        $achieved_point = ChallengeLog::where('challenge_group_id', $groupId)
            ->where('user_id', Auth::id())
            ->where('status', 'Completed')
            ->count();

        return [
            'my_achieved_point' => $achieved_point,
            'summaries' => $summaries
        ];
    }
    public function getOverallProgress(int $groupId)
    {
        $group = ChallengeGroup::find($groupId);
        if (!$group) {
            throw new Exception("Group with ID {$groupId} not found.");
        }

        $start_date = Carbon::parse($group->start_date)->toDateString();
        $end_date = Carbon::parse($group->end_date)->toDateString();

        $daysArray = $this->getChallengeDaysWithDates($start_date, $end_date);

        $today = Carbon::now()->toDateString();

        $total_day = count($daysArray);

        $current_day = 0;
        foreach ($daysArray as $i => $date) {
            if ($date == $today) {
                $current_day = $i + 1;
                break;
            }
        }

        $habit_count = GroupHabit::where('challenge_group_id', $groupId)->count();

        $completed = ChallengeLog::where('user_id', Auth::id())
            ->where('challenge_group_id', $groupId)
            ->where('status', 'Completed')
            ->count();

        $overall_progress = $habit_count > 0 ? round(($completed / $habit_count) * 100) : 0;

        $logs = ChallengeLog::where('challenge_group_id', $groupId)
            ->whereBetween('date', [$start_date, $end_date])
            ->get()
            ->groupBy(function ($log) {
                return Carbon::parse($log->date)->toDateString();
            });

        $summaries = [];
        foreach ($daysArray as $index => $dayDate) {
            $formattedDate = Carbon::parse($dayDate)->toDateString();
            $dayNumber = $index + 1;

            $dayLogs = $logs->get($formattedDate, collect());

            $totalTasks = GroupHabit::where('challenge_group_id', $groupId)->count();
            $completedTasks = $dayLogs->where('user_id', Auth::id())->where('status', 'Completed')->count();

            $progressPercent = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;

            $summaries[] = [
                'date' => $formattedDate,
                'day' => $dayNumber,
                'progress' => $progressPercent,
            ];
        }

        return [
            'overall_progress' => $overall_progress,
            'current_day' => $current_day,
            'total_day' => $total_day,
            'summaries' => $summaries
        ];
    }
    public function getMyCompletedGroups(?string $search = null)
    {
        $authId = Auth::id();
        $today = now()->toDateString();
        $query = ChallengeGroup::withCount('members')
            ->with('group_habits')
            ->where('status', 'Completed')
            ->whereHas('members', function ($q) use ($authId) {
                $q->where('user_id', $authId);
            })
            ->orderBy('created_at', 'desc');
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('group_name', 'LIKE', "%{$search}%")
                    ->orWhere('challenge_type', 'LIKE', "%{$search}%");
            });
        }
        $groups = $query->get();

        $groups->each(function ($group) use ($authId, $today) {
            $group->max_count = 100;

            $groupId = $group->id;
            $habit_count = GroupHabit::where('challenge_group_id', $groupId)->count();

            $my_completed = ChallengeLog::where('user_id', Auth::id())
                ->where('challenge_group_id', $groupId)
                ->where('status', 'Completed')
                ->count();
            $group->my_progress = $habit_count > 0 ? round(($my_completed / $habit_count) * 100) : 0;

            $group_completed = ChallengeLog::where('challenge_group_id', $groupId)
                ->where('status', 'Completed')
                ->count();
            $group->group_progress = $habit_count > 0 ? round(($group_completed / $habit_count) * 100) : 0;

            $group->makeHidden('members');
            $group->makeHidden('group_habits');
        });
        return $groups;
    }
}