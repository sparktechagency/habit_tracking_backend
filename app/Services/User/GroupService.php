<?php

namespace App\Services\User;

use App\Models\Challenge;
use App\Models\ChallengeGroup;
use App\Models\ChallengeLog;
use App\Models\GroupHabit;
use App\Models\GroupMember;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;

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

        $start_date = $group->start_date;
        $end_date = $group->end_date;
        $days = $this->getChallengeDaysWithDates($start_date, $end_date);
        $today = Carbon::now()->toDateString();
        $currentDay = 0;
        for ($i = 0; $i < $group->duration; $i++) {
            if ($days[$i] == $today) {
                $currentDay = $i + 1;
                $currentDate = $days[$i];
            }
        }

        $in_group_check = GroupMember::where('user_id', Auth::id())->first();

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
        $user_log = ChallengeLog::where('id', $logId)->where('user_id', Auth::id())->first();
        if ($user_log->status == 'Completed') {
            throw new Exception('This task is already completed.');
        }
        if ($user_log) {
            $user_log->status = 'Completed';
            $user_log->completed_at = Carbon::now();
            $user_log->save();
        } else {
            throw new Exception('User unauthorized. log id ' . $logId . ' is not this user.');
        }
        return $user_log;
    }
}