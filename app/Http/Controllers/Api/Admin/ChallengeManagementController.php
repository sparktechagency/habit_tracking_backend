<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\ChallengeManagementService;
use Exception;
use Illuminate\Http\Request;

class ChallengeManagementController extends Controller
{
    protected $challengeManagementService;

    public function __construct(ChallengeManagementService $challengeManagementService)
    {
        $this->challengeManagementService = $challengeManagementService;
    }
    
    public function getActiveChallenges(Request $request)
    {
        try {
            $types = $this->challengeManagementService->getActiveChallenges($request->search);
            return $this->sendResponse($types, 'Get active challenges fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function viewActiveChallenge(Request $request, $id)
    {
        try {
            $types = $this->challengeManagementService->viewActiveChallenge($id);
            return $this->sendResponse($types, 'Get active challenge fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }

    public function getCompletedChallenges(Request $request)
    {
        try {
            $types = $this->challengeManagementService->getCompletedChallenges($request->search);
            return $this->sendResponse($types, 'Get completed challenges fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function viewCompletedChallenge(Request $request, $id)
    {
        try {
            $types = $this->challengeManagementService->viewCompletedChallenge($id);
            return $this->sendResponse($types, 'Get completed challenge fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }

}
