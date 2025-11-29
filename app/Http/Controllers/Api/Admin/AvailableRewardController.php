<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AvailableRewardService;
use Exception;
use Illuminate\Http\Request;

class AvailableRewardController extends Controller
{
    protected $availableRewardService;

    public function __construct(AvailableRewardService $availableRewardService)
    {
        $this->availableRewardService = $availableRewardService;
    }
   
    public function getRewards(Request $request)
    {
        try {
            $types = $this->availableRewardService->getRewards($request->search,$request->per_page);
            return $this->sendResponse($types, 'Get Rewards fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }

    public function viewReward(Request $request,$id)
    {
        try {
            $types = $this->availableRewardService->viewReward($id);
            return $this->sendResponse($types, 'Get Rewards fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }

    public function approvedReward(Request $request)
    {
        try {
            $types = $this->availableRewardService->approvedReward($request->reward_id);
            return $this->sendResponse($types, 'Reward approved successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }

    public function canceledReward(Request $request)
    {
        try {
            $types = $this->availableRewardService->canceledReward($request->reward_id);
            return $this->sendResponse($types, 'Get Rewards fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
}
