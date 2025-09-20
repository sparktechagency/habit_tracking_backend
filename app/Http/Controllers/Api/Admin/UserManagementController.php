<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\UserManagementService;
use Exception;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{

    protected $userManagementService;

    public function __construct(UserManagementService $userManagementService)
    {
        $this->userManagementService = $userManagementService;
    }
    public function getUsers(Request $request)
    {
        try {
            $types = $this->userManagementService->getUsers($request->search);
            return $this->sendResponse($types, 'Get users fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }

    public function viewUser(Request $request)
    {
        try {
            $types = $this->userManagementService->viewUser($request->user_id);
            return $this->sendResponse($types, 'View user fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }

    public function blockUnblockUser(Request $request)
    {
        try {
            $types = $this->userManagementService->blockUnblockUser($request->user_id);
            return $this->sendResponse($types, 'User fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
}
