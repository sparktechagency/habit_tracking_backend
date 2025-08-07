<?php

namespace App\Http\Controllers\Api\Admin\Challenge_management;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AddTypeRequest;
use App\Http\Requests\Admin\EditTypeRequest;
use App\Services\Admin\ChallengeTypeService;
use Illuminate\Http\Request;

class ChallengeTypeController extends Controller
{
    protected $challengeTypeService;

    public function __construct(ChallengeTypeService $challengeTypeService)
    {
        $this->challengeTypeService = $challengeTypeService;
    }

    public function addType(AddTypeRequest $request)
    {
        try {
            $challenge = $this->challengeTypeService->addType($request->validated());
            return $this->sendResponse($challenge, 'Challenge type added successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to add challenge type.', [], 500);
        }
    }

    public function getTypes(Request $request)
    {
        try {
            $types = $this->challengeTypeService->getTypes($request->search);
            return $this->sendResponse($types, 'Challenge types fetched successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to fetch challenge types.', [], 500);
        }
    }

    public function viewType($id)
    {
        try {
            $challenge = $this->challengeTypeService->viewType($id);
            if (!$challenge) {
                return $this->sendError('Challenge type not found.', [], 404);
            }
            return $this->sendResponse($challenge, 'Challenge type fetched successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to view challenge type.', [], 500);
        }
    }

    public function editType(EditTypeRequest $request, $id)
    {
        try {
            $challenge = $this->challengeTypeService->editType($id, $request->validated());
            if (!$challenge) {
                return $this->sendError('Challenge type not found.', [], 404);
            }
            return $this->sendResponse($challenge, 'Challenge type updated successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to update challenge type.', [], 500);
        }
    }

    public function deleteType($id)
    {
        try {
            $deleted = $this->challengeTypeService->deleteType($id);
            if (!$deleted) {
                return $this->sendError('Challenge type not found.', [], 404);
            }
            return $this->sendResponse([], 'Challenge type deleted successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Failed to delete challenge type.', [], 500);
        }
    }
}
