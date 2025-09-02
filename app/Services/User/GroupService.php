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
        $startDate = Carbon::now()->toDateString();
        $endDate = Carbon::now()->addDays((int) $data['duration'])->toDateString();

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
}