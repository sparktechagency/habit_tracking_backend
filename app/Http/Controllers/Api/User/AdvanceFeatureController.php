<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\User\AdvanceFeatureService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdvanceFeatureController extends Controller
{
    protected $advanceFeatureService;

    public function __construct(AdvanceFeatureService $advanceFeatureService)
    {
        $this->advanceFeatureService = $advanceFeatureService;
    }

    public function basicInfo(Request $request)
    {
        try {
            $result = $this->advanceFeatureService->basicInfo($request->user_id);
            return $this->sendResponse($result, 'Get basic info fetch successfully.', true, 200);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function getSubscriptions(Request $request)
    {
        try {
            $result = $this->advanceFeatureService->getSubscriptions();
            return $this->sendResponse($result, 'Get subscriptions fetch successfully.', true, 200);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function premiumUserCheck(Request $request)
    {
        try {
            $result = $this->advanceFeatureService->premiumUserCheck();

            if ($request == '1') {
                return $this->sendResponse($result, 'Premium user check.', true);
            } else {
                return $this->sendResponse($result, 'Premium user check', false);
            }

        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function habitCalendar(Request $request)
    {
        try {
            $year = $request->input('year', now()->year);
            $month = $request->input('month', now()->month);
            $result = $this->advanceFeatureService->habitCalendar($year, $month);
            return $this->sendResponse($result, 'Get multiple habit calendar fetch successfully.', true, 200);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function modeTrackLineGraph(Request $request)
    {
        try {
            $result = $this->advanceFeatureService->modeTrackLineGraph($request->filter);
            return $this->sendResponse($result, 'Your monthly habit track over the last month.', true, 200);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function sayOnBarChart(Request $request)
    {
        try {
            $result = $this->advanceFeatureService->sayOnBarChart();
            return $this->sendResponse($result, 'Your most say no times this yearly.', true, 200);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
}
