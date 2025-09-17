<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Services\User\AdvanceFeatureService;
use Exception;
use Illuminate\Http\Request;

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
            $result = $this->advanceFeatureService->basicInfo();
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
            $result = $this->advanceFeatureService->modeTrackLineGraph();
            return $this->sendResponse($result, 'Get mode track line graph fetch successfully.', true, 200);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function sayOnBarChart(Request $request)
    {
        try {
            $result = $this->advanceFeatureService->sayOnBarChart();
            return $this->sendResponse($result, 'Get say on bar chart fetch successfully.', true, 200);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }

}
