<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\OtpVerifyRequest;
use App\Http\Requests\RegisterRequest;
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

    use ApiResponseTrait;

    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request)
    {
        try {
            $user = $this->authService->register($request->validated());

            return $this->sendResponse([], 'Register successfully, OTP send you email, please verify your account.', true, 201);
        } catch (\Exception $e) {
            return $this->sendError('Registration Failed', ['error' => $e->getMessage()]);
        }
    }

    public function login(LoginRequest $request)
    {
        try {
            $token = $this->authService->login($request->validated());

            if (!$token) {
                return $this->sendError('Invalid email or password.', 401);
            }

            return $this->sendResponse([
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => Auth::factory()->getTTL() * 60,
                'user' => Auth::user()
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
}
