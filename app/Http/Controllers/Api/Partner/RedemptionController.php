<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Services\Partner\RedemptionService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RedemptionController extends Controller
{

    protected $redemptionService;

    public function __construct(RedemptionService $redemptionService)
    {
        $this->redemptionService = $redemptionService;
    }
    public function getRedeemHistory(Request $request)
    {
        try {
            $history = $this->redemptionService->getRedeemHistory($request->search);
            return $this->sendResponse($history, 'Redeem history fetched successfully.');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function getRedemptionDetails(Request $request, $id)
    {
        try {
            $redemption = $this->redemptionService->getRedemptionDetails($id);

            if (!$redemption) {
                return $this->sendError('Redemption not found.', [], 404);
            }

            return $this->sendResponse($redemption, 'Redemption details fetched successfully.');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function markAsRedeemed($id)
    {
        try {
            $data = $this->redemptionService->markAsRedeemed($id);
            if ($data == false) {
                return $this->sendResponse([], 'Redemption not found.', false);
            }
            return $this->sendResponse($data, 'Redemption marked as redeemed successfully.');
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), [], 500);
        }
    }
}
