<?php

namespace App\Services;

use App\Mail\VerifyOTPMail;
use App\Models\Profile;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
            'role' => $data['role'] == 'user' ? 'USER' : 'PARTNER',
            'full_name' => $data['full_name'],
            'email' => $data['email'],
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

    public function login(array $credentials): ?string
    {
        if (!$token = Auth::attempt($credentials)) {
            return null;
        }

        return $token;
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
}
