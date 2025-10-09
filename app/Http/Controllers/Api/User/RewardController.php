<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\RedeemRequest;
use App\Services\User\RewardService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            $rewards = $this->rewardService->getAvailableRewards($request->search, $request->per_page, $request->radius);
            return response()->json([
                'status' => true,
                'message' => 'Available rewards fetched successfully.',
                'data' => $rewards
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function viewReward(Request $request, ?int $id)
    {
        try {
            $rewards = $this->rewardService->viewReward($id);
            return response()->json([
                'status' => true,
                'message' => 'View reward fetched successfully.',
                'data' => $rewards
            ]);
        } catch (Exception $e) {
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
            if ($redemption == false) {
                return $this->sendResponse([], "You don't have enough points.", false, 200);
            }

            if ($redemption['already_redeemed'] == true) {
                return $this->sendResponse([], "You are already redeem this reward.", false, 200);
            }
            return $this->sendResponse($redemption, 'Reward redeemed successfully.');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function getRedeemHistory(Request $request)
    {
        try {
            $history = $this->rewardService->getRedeemHistory($request->per_page,$request->search);
            return $this->sendResponse($history, 'Redeem history fetched successfully.');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function getRedemptionDetails(Request $request, $id)
    {
        try {
            $redemption = $this->rewardService->getRedemptionDetails($id);
            if (!$redemption) {
                return $this->sendError('Redemption not found.', [], 404);
            }
            return $this->sendResponse($redemption, 'Redemption details fetched successfully.');
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function markAsCompleted($id)
    {
        try {
            $data = $this->rewardService->markAsCompleted($id);
            if ($data == false) {
                return $this->sendResponse([], 'Redemption not found.', false);
            }
            return $this->sendResponse($data, 'Redemption marked as Completed successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
}
