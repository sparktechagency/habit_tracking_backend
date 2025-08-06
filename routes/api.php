<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\User\HabitController;
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
        
    });

    Route::middleware('partner')->prefix('patner')->group(function () {
       
    });

    Route::middleware('user')->prefix('user')->group(function () {
       // habits
       Route::post('/add-new-habit',[HabitController::class,'addNewHabit']);
       Route::get('/get-habits',[HabitController::class,'getHabits']);
       Route::get('/view-habit/{id?}',[HabitController::class,'viewHabit']);
       Route::delete('/delete-habit/{id?}',[HabitController::class,'deleteHabit']);
       Route::patch('/archived-habit',[HabitController::class,'archivedHabit']);
    });

});
