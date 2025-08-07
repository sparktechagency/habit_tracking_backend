<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Partner\RewardRequest;
use App\Services\Partner\RewardService;
use Illuminate\Http\Request;

class RewardController extends Controller
{
    protected $rewardService;

    public function __construct(RewardService $rewardService)
    {
        $this->rewardService = $rewardService;
    }

    public function addReward(RewardRequest $request)
    {
        try {
            $reward = $this->rewardService->addReward($request->validated());
            return $this->sendResponse($reward, 'Reward added successfully.');
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
            return $this->sendError('Failed to toggle reward status.', [$e->getMessage()], 500);
        }
    }

    public function getRewards()
    {
        try {
            $rewards = $this->rewardService->getRewards();
            return $this->sendResponse($rewards, 'Rewards fetched successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to fetch rewards.', [$e->getMessage()], 500);
        }
    }
}
