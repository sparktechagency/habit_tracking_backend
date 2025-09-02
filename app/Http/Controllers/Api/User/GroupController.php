<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\CreateChallengeGroupRequest;
use App\Services\User\GroupService;
use Exception;
use Illuminate\Http\JsonResponse;
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
        } catch (Exception $e) {
            return $this->sendError('Failed to fetch challenge types.', [], 500);
        }
    }

    public function createGroup(CreateChallengeGroupRequest $request): JsonResponse
    {
        try {
            $group = $this->groupService->createGroup($request->validated());
            return $this->sendResponse($group, 'Challenge group created successfully.', true, 201);
        } catch (Exception $e) {
            return $this->sendError('Failed to create challenge group.', [], 500);
        }
    } 
}
