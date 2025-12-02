<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\DashboardService;
use Exception;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function dashboardInfo(Request $request)
    {
        try {
            $challenge = $this->dashboardService->dashboardInfo();
            return $this->sendResponse($challenge, 'Get dashboard info fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function userChart(Request $request)
    {
        try {
            $challenge = $this->dashboardService->userChart();
            return $this->sendResponse($challenge, 'Get user chart fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function groupChart(Request $request)
    {
        try {
            $challenge = $this->dashboardService->groupChart();
            return $this->sendResponse($challenge, 'Get group chart fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function topChallengeChart(Request $request)
    {
        try {
            $challenge = $this->dashboardService->topChallengeChart($request->filter);
            return $this->sendResponse($challenge, 'Get top challenge chart fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function revenueChart(Request $request)
    {
        try {
            $challenge = $this->dashboardService->revenueChart();
            return $this->sendResponse($challenge, 'Get revenue chart fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
}
