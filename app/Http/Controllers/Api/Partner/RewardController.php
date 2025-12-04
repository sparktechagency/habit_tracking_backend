<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Partner\EditRewardRequest;
use App\Http\Requests\Partner\RewardRequest;
use App\Models\User;
use App\Notifications\NewRewardCreatedNotification;
use App\Services\Partner\RewardService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RewardController extends Controller
{
    protected $rewardService;

    public function __construct(RewardService $rewardService)
    {
        $this->rewardService = $rewardService;
    }

    public function checkProfileCompletion()
    {
        try {
            $isComplete = $this->rewardService->isProfileComplete(Auth::id());
            return $this->sendResponse([
                'is_complete' => $isComplete
            ], $isComplete ? 'Profile is complete.' : 'Profile is incomplete.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function addReward(RewardRequest $request)
    {
        try {
            // $isComplete = $this->rewardService->isProfileComplete(Auth::id());
            // if ($isComplete == false) {
            //     return $this->sendResponse([
            //         'is_complete' => $isComplete
            //     ], 'Profile is incomplete.');
            // }
            $reward = $this->rewardService->addReward($request->validated());

            $from = Auth::user()->full_name;
            $message = "Keep shining, you did amazing!";

            $admin = User::find(1);
            $users = User::where('id', '!=', Auth::id())->get();

            $admin->notify(new NewRewardCreatedNotification($from, $message));

            return $this->sendResponse($reward, 'Reward added successfully.');
        } catch (Exception $e) {
            return $this->sendError('Failed to add reward.', [$e->getMessage()], 500);
        }
    }
    public function enableDisableReward($id)
    {
        try {
            $reward = $this->rewardService->enableDisableReward($id);
            if (!$reward) {
                return $this->sendError('Reward not found or unauthorized.', [], 404);
            }
            return $this->sendResponse($reward, 'Reward status toggled successfully.');
        } catch (Exception $e) {
            return $this->sendError('Failed to toggle reward status.', [$e->getMessage()], 500);
        }
    }
    public function getRewards(Request $request)
    {
        try {
            $rewards = $this->rewardService->getRewards($request->per_page);
            return $this->sendResponse($rewards, 'Rewards fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Failed to fetch reward.', [$e->getMessage()], 500);
        }
    }

    public function viewReward($id)
    {
        try {
            $reward = $this->rewardService->viewReward($id);
            return $this->sendResponse($reward, 'Reward details fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Failed to fetch rewards.', [$e->getMessage()], 500);
        }
    }

    public function editReward(EditRewardRequest $request, $id)
    {
        try {
            $reward = $this->rewardService->editReward($request->validated(), $id);
            return $this->sendResponse($reward, 'Reward updated successfully.');
        } catch (Exception $e) {
            return $this->sendError('Failed to updated reward.', [$e->getMessage()], 500);
        }
    }

    public function deleteReward($id)
    {
        try {
            $this->rewardService->deleteReward($id);
            return $this->sendResponse([], 'Reward deleted successfully.');
        } catch (Exception $e) {
            return $this->sendError('Failed to deleted reward.', [$e->getMessage()], 500);
        }
    }
}
