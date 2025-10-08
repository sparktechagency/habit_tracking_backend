<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\OtpVerifyRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResendOtpRequest;
use App\Http\Requests\Auth\UpdatePasswordRequest;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

use Symfony\Component\CssSelector\Node\FunctionNode;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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
            return $this->sendResponse(['user' => $result['data'],], 'Your profile');
        } catch (Exception $e) {
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
        } catch (Exception $e) {
            return $this->sendError($e->getMessage(), 500);
        }
    }

    // public function checkToken(Request $request)
    // {
    //     try {
    //         $user = JWTAuth::setToken($request->token)->authenticate();

    //         if (!$user) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'User not found'
    //             ], 404);
    //         }

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Token is valid',
    //             'data' => $user
    //         ]);

    //     } catch (TokenExpiredException $e) {
    //         return response()->json(['status' => false, 'message' => 'Token expired'], 401);
    //     } catch (TokenInvalidException $e) {
    //         return response()->json(['status' => false, 'message' => 'Invalid token'], 401);
    //     } catch (JWTException $e) {
    //         return response()->json(['status' => false, 'message' => 'Token not provided'], 400);
    //     }
    // }

    // public function storeContact(Request $request)
    // {
    //     // Validate contact_lists as array
    //     $validator = Validator::make($request->all(), [
    //         'contact_lists' => 'required'
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $validator->errors()
    //         ], 422);
    //     }

    //     // Store contacts as JSON string
    //     $contact_lists = Contact::create([
    //         'user_id' => Auth::id(),
    //         'contact_lists' => json_encode($request->contact_lists)
    //     ]);

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Store contact lists',
    //         'data' => $contact_lists
    //     ]);
    // }

    // public function searchContact(Request $request)
    // {
    //     $search = $request->search_number;

    //     $contact = Contact::where('user_id', Auth::id())->first();

    //     if (!$contact) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'No contact list found',
    //             'data' => []
    //         ]);
    //     }

    //     // Clean contact_lists string
    //     $contact_lists = trim($contact->contact_lists);

    //     // Step 1: Remove unwanted characters: quotes, brackets, curly braces
    //     $cleaned = str_replace(['[', ']', '{', '}', '"', '\''], '', $contact_lists);

    //     // Step 2: Split by comma
    //     $contactArray = array_map('trim', explode(',', $cleaned));

    //     // Step 3: If search given, filter
    //     if ($search) {
    //         $matched = array_filter($contactArray, function ($number) use ($search) {
    //             return str_contains($number, $search);
    //         });

    //         return response()->json([
    //             'status' => !empty($matched),
    //             'message' => !empty($matched) ? 'Number found' : 'Number not found',
    //             'data' => array_values($matched)
    //         ]);
    //     }

    //     // No search, return all
    //     return response()->json([
    //         'status' => true,
    //         'message' => 'All contact numbers',
    //         'data' => $contactArray
    //     ]);

    // }


    public function synContacts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contact_lists' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $contactNumbers = $request->contact_lists;

        $matchedUsers = User::whereIn('phone_number', $contactNumbers)
            ->where('id', '!=', Auth::id())
            ->where('role', 'USER')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Matched registered users from contact list',
            'data' => $matchedUsers
        ]);
    }
    public function deleteAccount(Request $request)
    {
        Auth::user()->delete();
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'status' => true,
            'message' => 'Account deleted successfully'
        ]);
    }
}
