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

    public function getRedeemHistory()
    {
        try {
            $history = $this->rewardService->getRedeemHistory();
            return $this->sendResponse($history, 'Redeem history fetched successfully.');
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
