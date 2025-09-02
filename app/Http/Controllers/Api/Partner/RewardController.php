<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Partner\RewardRequest;
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
    public function getRewards()
    {
        try {
            $rewards = $this->rewardService->getRewards();
            return $this->sendResponse($rewards, 'Rewards fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Failed to fetch rewards.', [$e->getMessage()], 500);
        }
    }
}
