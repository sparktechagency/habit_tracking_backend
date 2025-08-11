<?php

use App\Http\Controllers\Api\Admin\ChallengeTypeController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Partner\RedemptionController;
use App\Http\Controllers\Api\User\HabitController;
use App\Http\Controllers\Api\User\RewardController as UserRewardController;
use App\Http\Controllers\Api\User\SayNoController;
use App\Http\Controllers\Api\Partner\RewardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/social-login', [AuthController::class, 'socialLogin']);


Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/get-profile', [AuthController::class, 'getProfile']);
    Route::post('/update-password', [AuthController::class, 'updatePassword']);

    Route::middleware('admin')->prefix('admin')->group(function () {
        // challenge type
        Route::post('/add-type',[ChallengeTypeController::class,'addType']);
        Route::get('/get-types',[ChallengeTypeController::class,'getTypes']);
        Route::get('/view-type/{id?}',[ChallengeTypeController::class,'viewType']);
        Route::patch('/edit-type/{id?}',[ChallengeTypeController::class,'editType']);
        Route::delete('/delete-type/{id?}',[ChallengeTypeController::class,'deleteType']);
    });

    Route::middleware('partner')->prefix('partner')->group(function () {
       // rewards
       Route::get('/check-profile-completion',[RewardController::class,'checkProfileCompletion']);
       Route::post('/add-reward',[RewardController::class,'addReward']);
       Route::patch('/enable-disable-reward/{id?}',[RewardController::class,'enableDisableReward']);
       Route::get('/get-rewards',[RewardController::class,'getRewards']);

       // redemptions
       Route::get('/get-redeem-history',[RedemptionController::class,'getRedeemHistory']);
       Route::get('/get-redemption-details/{id?}',[RedemptionController::class,'getRedemptionDetails']);
       Route::patch('/mark-as-redeemed/{id?}',[RedemptionController::class,'markAsRedeemed']);
    });

    Route::middleware('user')->prefix('user')->group(function () {
       // habits
       Route::post('/add-new-habit',[HabitController::class,'addNewHabit']);
       Route::get('/get-habits',[HabitController::class,'getHabits']);
       Route::get('/view-habit/{id?}',[HabitController::class,'viewHabit']);
       Route::delete('/delete-habit/{id?}',[HabitController::class,'deleteHabit']);
       Route::patch('/archived-habit',[HabitController::class,'archivedHabit']);

       // say no
       Route::post('/add-entry',[SayNoController::class,'addEntry']);
       Route::get('/get-entries',[SayNoController::class,'getEntries']);
       Route::get('/view-entry/{id?}',[SayNoController::class,'viewEntry']);
       Route::delete('/delete-entry/{id?}',[SayNoController::class,'deleteEntry']);

       // rewards
       Route::get('/get-available-rewards',[UserRewardController::class,'getAvailableRewards']);
       Route::patch('/redeem-reward',[UserRewardController::class,'redeem']);
       Route::get('/get-redeem-history',[UserRewardController::class,'getRedeemHistory']);
       Route::get('/get-redemption-details/{id?}',[UserRewardController::class,'getRedemptionDetails']);
       Route::patch('/mark-as-completed/{id?}',[UserRewardController::class,'markAsCompleted']);
    });
});
