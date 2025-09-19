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

     public function getPartners(Request $request)
    {
        try {
            $types = $this->userManagementService->getPartners($request->search);
            return $this->sendResponse($types, 'Get partner fetched successfully.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
}
