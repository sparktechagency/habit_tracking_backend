<?php

namespace App\Services\Admin;

use App\Models\Challenge;

class ChallengeTypeService
{
    public function addType(array $data): Challenge
    {
        return Challenge::create($data);
    }
    public function getTypes(?string $search = null)
    {
        $query = Challenge::query();
        if ($search) {
            $query->where('challenge_type', 'like', "%{$search}%");
        }
        return $query->latest()->get();
    }
    public function viewType(int $id): ?Challenge
    {
        return Challenge::find($id);
    }
    public function editType(int $id, array $data): ?Challenge
    {
        $challenge = Challenge::find($id);
        if ($challenge) {
            $challenge->update($data);
        }
        return $challenge;
    }
    public function deleteType(int $id): bool
    {
        $challenge = Challenge::find($id);
        if ($challenge) {
            return $challenge->delete();
        }
        return false;
    }
}
