<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\CreateChallengeGroupRequest;
use App\Models\ChallengeGroup;
use App\Models\ChallengeLog;
use App\Models\GroupMember;
use App\Models\SendInvite;
use App\Models\User;
use App\Notifications\CelebrationNotification;
use App\Notifications\NewChallengeCreatedNotification;
use App\Notifications\SendInviteNotification;
use App\Services\User\GroupService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\PushNotificationService;

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
    public function createGroup(CreateChallengeGroupRequest $request, PushNotificationService $firebase): JsonResponse
    {
        try {
            $group = $this->groupService->createGroup($request->validated(), $firebase);
            return $this->sendResponse($group, 'Challenge group created successfully.', true, 201);
        } catch (Exception $e) {
            return $this->sendError('Failed to create challenge group.', [$e->getMessage()], 500);
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
    public function getActiveGroups(Request $request)
    {
        try {
            $search = $request->query('search');
            $groups = $this->groupService->getActiveGroups($search, $request->per_page);
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
            $group = $this->groupService->getDailySummaries($request->challenge_group_id, $request->day);
            return $this->sendResponse($group, 'Get logs fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [$e->getMessage()], 500);
        }
    }
    public function sendCelebration(Request $request, PushNotificationService $firebase)
    {
        try {
            $user = User::find($request->user_id);

            if (!$user) {
                throw new Exception('User not found.');
            }

            // notification
            $from = Auth::user()->full_name;
            $message = "is celebrating your success 🎉🥳";

            $user->notify(new CelebrationNotification($from, $message));

            $device_token = $user->device_token;
            $firebase->sendNotification(
                $device_token,
                'You are congratulated.',
                Auth::user()->full_name . ' is celebrating your success 🎉🥳',
                [
                    'user_id' => (string) Auth::id(),
                ]
            );

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
            $groups = $this->groupService->getMyCompletedGroups($search, $request->per_page);
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
    public function viewCelebrationMember(Request $request)
    {
        return [
            'user' => User::where('id', $request->user_id)->select('id', 'full_name', 'role', 'avatar')->first(),
            'last_completed_time' => ChallengeLog::where('challenge_group_id', $request->challenge_group_id)
                ->where('user_id', $request->user_id)
                ->whereDate('date', Carbon::now())
                ->latest()
                ->first()
                ->completed_at,
            'challenge_group_name' => ChallengeGroup::where('id', $request->challenge_group_id)->first()->group_name,
        ];
    }
    public function getUsers(Request $request)
    {

        $query = User::where('id', '!=', Auth::id())
            ->where('role', 'USER')
            ->where('id', '!=', 1)
            ->select('id', 'full_name', 'role', 'avatar');


        if (!empty($request->search)) {
            $query->where(function ($q) use ($request) {
                $q->where('full_name', 'LIKE', "%{$request->search}%");
            });
        }

        $users = $query->paginate($per_page ?? 10);

        return [
            'status' => true,
            'message' => 'Get users.',
            'data' => $users,
        ];
    }
    public function myGroupLists(Request $request)
    {

         $request->validate([
            'invitee_id' => 'required|exists:users,id'
        ]);

        $arr = GroupMember::where('user_id', Auth::id())->pluck('challenge_group_id')->toArray();

        $groups = ChallengeGroup::whereIn('id', $arr)->paginate($request->per_page ?? 10);

        foreach ($groups as $group) {

            $inviter_id = Auth::id();
            $invitee_id = $request->invitee_id;
            $group_id = $group->id;

            $is_invited = SendInvite::where('inviter_id', $inviter_id)
                ->where('invitee_id', $invitee_id)
                ->where('invitee_challenge_group_id', $group_id)
                ->exists();

            $is_join = GroupMember::where('challenge_group_id', $group->id)
                ->where('user_id', $invitee_id)
                ->exists();

            if ($is_join) {
                $group->button_state = 'already joined';
            } else {
                $group->button_state = $is_invited == true ? 'invited' : 'invite';
            }
        }

        return [
            'status' => true,
            'message' => 'Get my groups.',
            'data' => $groups,
        ];
    }
    public function sendInvite(Request $request, PushNotificationService $firebase)
    {
        try {
            $user = User::find($request->user_id);

            if (!$user) {
                throw new Exception('User not found.');
            }

            $group = ChallengeGroup::where('id', $request->challenge_group_id)->first();

            SendInvite::create([
                'inviter_id' => Auth::id(),
                'invitee_id' => $user->id,
                'invitee_challenge_group_id' => $group->id,
            ]);

            // notification
            $from = Auth::user()->full_name;
            $message = "invited you to the " . $group->group_name . " group.";

            $user->notify(new SendInviteNotification($from, $message, $group));

            $device_token = $user->device_token;
            $firebase->sendNotification(
                $device_token,
                'Invitation letter.',
                Auth::user()->full_name . ' invited you to the ' . $group->group_name . ' group.',
                [
                    'user_id' => (string) Auth::id(),
                    'group_challenge_id' => (string) $group->id,
                    'redirect' => 'challenge/[id]'
                ]
            );

            return $this->sendResponse([], 'Notification send successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [$e->getMessage()], 500);
        }
    }
}
