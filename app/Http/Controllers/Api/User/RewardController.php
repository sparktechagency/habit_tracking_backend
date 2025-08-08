<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\RedeemRequest;
use App\Services\User\RewardService;
use Illuminate\Http\Request;

class RewardController extends Controller
{
    protected $rewardService;

    public function __construct(RewardService $rewardService)
    {
        $this->rewardService = $rewardService;
    }

    public function getAvailableRewards(Request $request)
    {
        try {
            $rewards = $this->rewardService->getAvailableRewards($request->search);
            return response()->json([
                'status' => true,
                'message' => 'Available rewards fetched successfully.',
                'data' => $rewards
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function redeem(RedeemRequest $request)
    {
        try {
            $redemption = $this->rewardService->redeem($request->validated()['reward_id']);

            if (!$redemption) {
                return response()->json([
                    'status' => false,
                    'message' => 'Reward not available for redemption.'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Reward redeemed successfully.',
                'data' => $redemption
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
