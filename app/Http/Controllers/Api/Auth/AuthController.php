<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Services\AuthService;
use App\Traits\Traits\ApiResponseTrait;
use Illuminate\Http\Request;

class AuthController extends Controller
{

     use ApiResponseTrait;

    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request){
        try {
            $user = $this->authService->createUser($request->validated());
            return $this->sendResponse($user,'User register successfully');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(),500);
        }
    }
}
