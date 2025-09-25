<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\CreateChallengeGroupRequest;
use App\Models\ChallengeLog;
use App\Models\GroupMember;
use App\Models\User;
use App\Notifications\CelebrationNotification;
use App\Notifications\NewChallengeCreatedNotification;
use App\Services\User\GroupService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

            $from = Auth::user()->full_name;
            $message = "Keep shining, you did amazing!";

            $admin = User::find(1);
            $users = User::where('id','!=',Auth::id())->get();

            $admin->notify(new NewChallengeCreatedNotification($from, $message));

            return $this->sendResponse($group, 'Challenge group created successfully.', true, 201);
        } catch (Exception $e) {
            return $this->sendError('Failed to create challenge group.', [], 500);
        }
    }
    public function getGroups(Request $request)
    {
        try {
            $search = $request->query('search');
            $groups = $this->groupService->getGroups($search, $request->per_page);
            return $this->sendResponse($groups, 'Groups fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Failed to fetch groups.', [$e->getMessage()], 500);
        }
    }
    public function viewGroup(Request $request, $id)
    {
        try {
            $group = $this->groupService->viewGroup($id);
            if (!$group) {
                return $this->sendError('Group not found.', [], 404);
            }
            return $this->sendResponse($group, 'Group fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Failed to fetch group.', [$e->getMessage()], 500);
        }
    }
    public function joinGroup(Request $request)
    {
        try {
            $member = $this->groupService->joinGroup($request->challenge_group_id);
            return $this->sendResponse($member, 'Joined group successfully.');
        } catch (Exception $e) {
            return $this->sendError('Failed to join group.', [$e->getMessage()], 500);
        }
    }
    public function logProgress(Request $request)
    {
        try {
            $result = $this->groupService->logProgress($request->challenge_group_id);
            if ($result == null) {
                return $this->sendResponse([], 'Your today logs already stored.');
            }
            return $this->sendResponse($result, 'Tasks added successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [$e->getMessage()], 500);
        }
    }
    public function getTodayLogs(Request $request)
    {
        try {
            $group = $this->groupService->getTodayLogs($request->challenge_group_id);
            return $this->sendResponse($group, 'Get today logs fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [$e->getMessage()], 500);
        }
    }
    public function taskCompleted(Request $request)
    {
        try {
            $result = $this->groupService->taskCompleted($request->challenge_log_id);
            return $this->sendResponse($result, 'Task completed successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [$e->getMessage()], 500);
        }
    }
    public function getDailySummaries(Request $request)
    {
        try {
            $group = $this->groupService->getDailySummaries($request->challenge_group_id);
            return $this->sendResponse($group, 'Get logs fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [$e->getMessage()], 500);
        }
    }
    public function sendCelebration(Request $request)
    {
        try {
            $user = User::find($request->user_id);

            if (!$user) {
                throw new Exception('User not found.');
            }

            $from = Auth::user()->full_name;
            $message = "Keep shining, you did amazing!";

            $user->notify(new CelebrationNotification($from, $message));

            return $this->sendResponse([], 'Notification send successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [$e->getMessage()], 500);
        }
    }
    public function getOverallProgress(Request $request)
    {
        try {
            $groups = $this->groupService->getOverallProgress($request->challenge_group_id);
            return $this->sendResponse($groups, 'My overall progress fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Failed to fetch groups.', [$e->getMessage()], 500);
        }
    }
    public function getMyCompletedGroups(Request $request)
    {
        try {
            $search = $request->query('search');
            $groups = $this->groupService->getMyCompletedGroups($search,$request->per_page);
            return $this->sendResponse($groups, 'My completed groups fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Failed to fetch groups.', [$e->getMessage()], 500);
        }
    }

    public function checkGroupMember(Request $request)
    {
        $is_member = GroupMember::where('challenge_group_id', $request->challenge_group_id)
            ->where('user_id', Auth::id())
            ->exists();
        return ['is_join' => $is_member];
    }

    public function groupArray()
    {
        $arr = GroupMember::where('user_id', Auth::id())->pluck('challenge_group_id')->toArray();
        return ['join_group_ids' => $arr];
    }
}
