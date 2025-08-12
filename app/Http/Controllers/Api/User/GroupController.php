<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\User\GroupService;
use Illuminate\Http\Request;

class GroupController extends Controller
{

    protected $groupService;

    public function __construct(GroupService $groupService)
    {
        $this->groupService = $groupService;
    }

    public function getChallengeTypeLists(Request $request)
    {
        try {
            $types = $this->groupService->getChallengeTypeLists();
            return $this->sendResponse($types, 'Challenge types fetched successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to fetch challenge types.', [], 500);
        }
    }
}
