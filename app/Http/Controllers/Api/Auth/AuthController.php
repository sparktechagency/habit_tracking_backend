<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\OtpVerifyRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResendOtpRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Models\User;
use App\Services\AuthService;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

use Symfony\Component\CssSelector\Node\FunctionNode;
use Tymon\JWTAuth\Facades\JWTAuth;

use function Laravel\Prompts\error;

class AuthController extends Controller
{

    protected $authService;
    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    public function register(RegisterRequest $request)
    {
        try {
            $user = $this->authService->register($request->validated());
            return $this->sendResponse(
                [],
                'Register successfully, OTP send you email, please verify your account.',
                true,
                201
            );
        } catch (\Exception $e) {
            return $this->sendError('Registration Failed', ['error' => $e->getMessage()]);
        }
    }
    public function login(LoginRequest $request)
    {
        try {
            $data = $request->validated();
            $response = $this->authService->login($data);
            if (!$response['success']) {
                return $this->sendError($response['message'], [], $response['code']);
            }
            return $this->sendResponse([
                'token' => $response['token'],
                'token_type' => $response['token_type'],
                'expires_in' => $response['expires_in'],
                'user' => $response['user'],
            ], 'Login successful');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
    public function verifyOtp(OtpVerifyRequest $request)
    {
        try {
            $otp = $request->validated()['otp'];
            $result = $this->authService->verifyOtp($otp);
            if (!$result['success']) {
                return $this->sendError($result['message'], [], $result['code']);
            }
            $user = $result['user'];
            $tokenExpiry = now()->addDays(7);
            $customClaims = ['exp' => $tokenExpiry->timestamp];
            $token = JWTAuth::customClaims($customClaims)->fromUser($user);
            return $this->sendResponse([
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => $tokenExpiry->toDateTimeString(),
                'user' => $user
            ], 'OTP verified successfully.');
        } catch (\Exception $e) {
            return $this->sendError('Something went wrong.', [], 500);
        }
    }
    public function resendOtp(ResendOtpRequest $request)
    {
        try {
            $email = $request->validated()['email'];
            $result = $this->authService->resendOtp($email);
            if (!$result['success']) {
                return $this->sendError($result['message'], [], $result['code']);
            }
            return $this->sendResponse([], 'OTP resent to your email.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
    public function forgotPassword(ResendOtpRequest $request)
    {
        try {
            $email = $request->validated()['email'];
            $result = $this->authService->forgotPassword($email);
            if (!$result['success']) {
                return $this->sendError($result['message'], [], $result['code']);
            }
            return $this->sendResponse([], 'OTP sent to your email.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $password = $request->validated()['password'];
            $result = $this->authService->changePassword($password);
            if (!$result['success']) {
                return $this->sendError($result['message'], [], $result['code']);
            }
            return $this->sendResponse([], 'Password changed successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
    public function updatePassword(UpdatePasswordRequest $request)
    {
        try {
            $current_password = $request->validated()['current_password'];
            $password = $request->validated()['password'];
            $result = $this->authService->updatePassword($current_password, $password);
            if (!$result['success']) {
                return $this->sendError($result['message'], [], $result['code']);
            }
            return $this->sendResponse([], 'Password updated successfully.');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
    public function getProfile(Request $request)
    {
        try {
            $result = $this->authService->getProfile($request->user_id);
            if (!$result['success']) {
                return $this->sendError($result['message'], [], $result['code']);
            }
            return $this->sendResponse([
                'user' => $result['data'],
            ], 'Your profile');
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
    public function logout()
    {
        try {
            $result = $this->authService->logout();
            if (!$result['success']) {
                return $this->sendError($result['message'], [], $result['code'] ?? 500);
            }
            return $this->sendResponse([], $result['message']);
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }
}
