<?php

namespace App\Services\User;

use App\Models\Challenge;
use App\Models\ChallengeGroup;
use App\Models\ChallengeLog;
use App\Models\GroupHabit;
use App\Models\GroupMember;
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
        $query = ChallengeGroup::withCount('members')->with('group_habits')->with('members.user.profile')
            ->orderBy('created_at', 'desc');
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('group_name', 'LIKE', "%{$search}%")
                    ->orWhere('challenge_type', 'LIKE', "%{$search}%");
            });
        }
        $groups = $query->get();
        $groups->each(function ($group) {
            $group->max_count = 100;
        });
        return $groups;
    }
    public function viewGroup(int $id): ?ChallengeGroup
    {
        $group = ChallengeGroup::withcount('members')->with('group_habits')->where('id', $id)->with('members')
            ->where('user_id', Auth::id())
            ->first();
        if ($group) {
            $group->max_count = 100;
        }
        return $group;
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
        $today = Carbon::now()->toDateString();
        $my = ChallengeLog::where('challenge_group_id', $groupId)
            ->whereDate('date', $today)
            ->where('user_id', Auth::id())
            ->get();
        $others = ChallengeLog::where('challenge_group_id', $groupId)
            ->whereDate('date', $today)
            ->where('user_id', '!=', Auth::id())
            ->get()
            ->groupBy('user_id');
        return [
            'my_logs' => $my,
            'others_logs' => $others,
        ];
    }
    public function taskCompleted(int $logId)
    {
        $log_id = ChallengeLog::find($logId);
        if (!$log_id) {
            throw new Exception('Challenge log id is not valid.');
        }
        $user_log = ChallengeLog::where('id', $logId)->where('user_id', Auth::id())->first();
        if ($user_log->status == 'Completed') {
            throw new Exception('This task is already completed.');
        }
        if (!$user_log) {
            throw new Exception('User unauthorized. log id ' . $logId . ' is not this user.');
        }
        $user_log->status = 'Completed';
        $user_log->completed_at = Carbon::now();
        $user_log->save();
        return $user_log;
    }
    public function getDailySummaries(int $groupId): array
    {
        $group = ChallengeGroup::find($groupId);
        if (!$group) {
            throw new Exception("Group with ID {$groupId} not found.");
        }
        $start_date = Carbon::parse($group->start_date)->toDateString();
        $end_date = Carbon::parse($group->end_date)->toDateString();
        $daysArray = $this->getChallengeDaysWithDates($start_date, $end_date);
        Log::info('Days Array:', $daysArray);
        $logs = ChallengeLog::with('user')
            ->where('challenge_group_id', $groupId)
            ->whereBetween('date', [$start_date, $end_date])
            ->get()
            ->groupBy('date');
        Log::info('Log dates:', $logs->keys()->toArray());
        $groupUserIds = ChallengeLog::where('challenge_group_id', $groupId)
            ->pluck('user_id')
            ->unique()
            ->toArray();
        $summaries = [];
        foreach ($logs as $date => $dayLogs) {
            $formattedDate = Carbon::parse($date)->toDateString(); //(Y-m-d)
            $dayNumber = null;
            foreach ($daysArray as $index => $dayDate) {
                $formattedDayDate = Carbon::parse($dayDate)->toDateString();
                if ($formattedDayDate === $formattedDate) {
                    $dayNumber = $index + 1;
                    break;
                }
            }
            $usersByLog = $dayLogs->groupBy('user_id');
            $userProgress = [];
            $totalPercent = 0;
            foreach ($groupUserIds as $userId) {
                $userLogs = $usersByLog->get($userId, collect());
                $totalTasks = $userLogs->count();
                $completedTasks = $userLogs->where('status', 'Completed')->count();
                $progressPercent = $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100) : 0;
                $userModel = $userLogs->first()->user ?? User::find($userId);
                $statusList = ChallengeLog::where('challenge_group_id', $groupId)
                    ->where('user_id', $userId)
                    ->where('day', $dayNumber)
                    ->pluck('status')
                    ->toArray();
                if (empty($statusList)) {
                    $taskCount = GroupHabit::where('challenge_group_id', $groupId)->count();
                    $statusList = array_fill(0, $taskCount, 'Incompleted');
                }
                $userProgress[] = [
                    'user_id' => $userId,
                    'user_name' => $userModel->full_name ?? 'Unknown',
                    'progress' => $progressPercent,
                    'status' => $statusList
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
}