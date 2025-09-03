<?php

namespace App\Services\User;

use App\Models\Challenge;
use App\Models\ChallengeGroup;
use Carbon\Carbon;
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
        $endDate = Carbon::now()->addDays((int) $data['duration']);

        return ChallengeGroup::create([
            'user_id' => Auth::id(),
            'group_name' => $data['group_name'],
            'challenge_type' => $data['challenge_type'],
            'duration' => $data['duration'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'Active',
        ]);
    }
    public function getGroups(?string $search = null)
    {
        $query = ChallengeGroup::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc');
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('group_name', 'LIKE', "%{$search}%")
                    ->orWhere('challenge_type', 'LIKE', "%{$search}%");
            });
        }
        $groups = $query->get();
        return $groups;
    }
    public function viewGroup(int $id): ?ChallengeGroup
    {
        $group = ChallengeGroup::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();
        return $group;
    }
}