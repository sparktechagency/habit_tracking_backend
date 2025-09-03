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
        $query = ChallengeGroup::withCount('members')->with('group_habits')->with('members')
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
        $group->max_count = 100;
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
    public function taskCompleted(int $groupId, int $habitId)
    {
        $group = ChallengeGroup::where('id', $groupId)->first();
        if (!$group) {
            throw new Exception("Group with ID {$groupId} not found.");
        }

        $start_date = $group->start_date;
        $end_date = $group->end_date;

        $days = $this->getChallengeDaysWithDates($start_date, $end_date);
        $today = Carbon::now()->toDateString();
        $currentDayNumber = 0;

        for ($i = 0; $i < $group->duration; $i++) {
            if ($days[$i] == $today) {
                $currentDayNumber = $i + 1;
            }
        }

        return ChallengeLog::create([
            'challenge_group_id' => $groupId,
            'group_habits_id' => $habitId,
            'user_id' => Auth::id(),
            'day' => $currentDayNumber,
            'status' => 'Completed',
            'completed_at' => Carbon::now()
        ]);
    }




}