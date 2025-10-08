<?php

namespace App\Services\Auth;

use App\Mail\VerifyOTPMail;
use App\Models\Profile;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthService
{
    public function register(array $data): User
    {
        $otp = rand(100000, 999999);
        $otp_expires_at = Carbon::now()->addMinutes(10);

        $email_otp = [
            'userName' => explode('@', $data['email'])[0],
            'otp' => $otp,
            'validity' => '10 minute'
        ];
        $user = User::create([
            'role' => $data['role'],
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'],
            'password' => Hash::make($data['password']),
            'otp' => $otp,
            'otp_expires_at' => $otp_expires_at,
        ]);
        Profile::create([
            'user_id' => $user->id,
        ]);
        try {
            Mail::to($user->email)->send(new VerifyOTPMail($email_otp));
        } catch (Exception $e) {
            Log::error($e->getMessage());
        }
        return $user;
    }
    public function login(array $data): array
    {
        $user = User::where('email', $data['email'])->first();
        if (!$user) {
            return ['success' => false, 'message' => 'User not found', 'code' => 404];
        }
        if ($user->status == 'Inactive') {
            return ['success' => false, 'message' => 'Your account is inactive. Please verify your account.', 'code' => 403];
        }

        if ($user->status == 'Blocked') {
            return ['success' => false, 'message' => 'Your account is blocked. Please contact to admin.', 'code' => 403];
        }

        if (!Hash::check($data['password'], $user->password)) {
            return ['success' => false, 'message' => 'Invalid password', 'code' => 401];
        }
        $tokenExpiry = isset($data['remember_me']) && $data['remember_me']
            ? Carbon::now()->addDays(30)
            : Carbon::now()->addDays(7);
        $customClaims = ['exp' => $tokenExpiry->timestamp];
        $token = JWTAuth::customClaims($customClaims)->fromUser($user);
        return [
            'success' => true,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $tokenExpiry->toDateTimeString(),
            'user' => $user,
        ];
    }
    public function verifyOtp(string $otp): array
    {
        $user = User::where('otp', $otp)->first();
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid OTP.', 'code' => 401];
        }
        if (now()->greaterThan($user->otp_expires_at)) {
            return ['success' => false, 'message' => 'OTP expired.', 'code' => 410];
        }
        $user->update([
            'otp' => null,
            'otp_expires_at' => null,
            'otp_verified_at' => now(),
            'status' => 'active',
        ]);
        return ['success' => true, 'user' => $user];
    }
    public function resendOtp(string $email): array
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            return ['success' => false, 'message' => 'User not found', 'code' => 404];
        }
        $otp = rand(100000, 999999);
        $otp_expires_at = Carbon::now()->addMinutes(10);
        $user->update([
            'otp' => $otp,
            'otp_expires_at' => $otp_expires_at,
            'otp_verified_at' => null
        ]);
        $email_otp = [
            'userName' => explode('@', $user->email)[0],
            'otp' => $otp,
            'validity' => '10 minute'
        ];
        try {
            Mail::to($user->email)->send(new VerifyOTPMail($email_otp));
        } catch (Exception $e) {
            Log::error('OTP email failed: ' . $e->getMessage());
        }
        return ['success' => true];
    }
    public function forgotPassword(string $email): array
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            return ['success' => false, 'message' => 'User not found', 'code' => 404];
        }
        $otp = rand(100000, 999999);
        $otp_expires_at = Carbon::now()->addMinutes(10);
        $user->update([
            'otp' => $otp,
            'otp_expires_at' => $otp_expires_at,
            'otp_verified_at' => null
        ]);
        $email_otp = [
            'userName' => explode('@', $user->email)[0],
            'otp' => $otp,
            'validity' => '10 minute'
        ];
        try {
            Mail::to($user->email)->send(new VerifyOTPMail($email_otp));
        } catch (Exception $e) {
            Log::error('OTP email failed: ' . $e->getMessage());
        }
        return ['success' => true];
    }
    public function changePassword(string $newPassword): array
    {
        $user = Auth::user();
        if (!$user) {
            return ['success' => false, 'message' => 'Unauthenticated user', 'code' => 401];
        }
        if ($user->status !== 'Active') {
            return ['success' => false, 'message' => 'Unauthorized user', 'code' => 403];
        }
        $user->password = Hash::make($newPassword);
        $user->save();
        return ['success' => true];
    }
    public function updatePassword(string $currentPassword, string $newPassword): array
    {
        $user = Auth::user();
        if (!$user) {
            return ['success' => false, 'message' => 'User not found', 'code' => 404];
        }
        if (!Hash::check($currentPassword, $user->password)) {
            return ['success' => false, 'message' => 'Invalid current password', 'code' => 401];
        }
        $user->password = Hash::make($newPassword);
        $user->save();
        return ['success' => true];
    }
    public function getProfile(?int $userId = null): array
    {
        $user = User::with('profile')->find($userId ?? Auth::id());
        if (!$user) {
            return ['success' => false, 'message' => 'User not found', 'code' => 404];
        }
        return [
            'success' => true,
            'data' => $user,
        ];
    }
    
    public function logout(): array
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return [
                'success' => true,
                'message' => 'Successfully logged out.'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to logout. Please try again.',
                'code' => 500
            ];
        }
    }
}