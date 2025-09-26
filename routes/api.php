<?php

use App\Http\Controllers\Api\Admin\AvailableRewardController;
use App\Http\Controllers\Api\Admin\ChallengeManagementController;
use App\Http\Controllers\Api\Admin\ChallengeTypeController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\PartnerBusinessController;
use App\Http\Controllers\Api\Admin\SubscriptionControler;
use App\Http\Controllers\Api\Admin\TransationController;
use App\Http\Controllers\Api\Admin\UserManagementController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\Partner\RedemptionController;
use App\Http\Controllers\Api\User\HabitController;
use App\Http\Controllers\Api\User\RewardController as UserRewardController;
use App\Http\Controllers\Api\User\SayNoController;
use App\Http\Controllers\Api\Partner\RewardController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\User\GroupController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\StaticPageController;
use App\Http\Controllers\Api\User\AdvanceFeatureController;
use App\Models\Subscription;
use App\Services\User\AdvanceFeatureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
// social login (google)
Route::post('/social-login', [AuthController::class, 'socialLogin']);
// static page show
Route::get('pages/{slug}', [StaticPageController::class, 'show']);


Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/get-profile', [AuthController::class, 'getProfile']);
    Route::post('/edit-profile', [SettingsController::class, 'editProfile']);
    Route::post('/update-password', [AuthController::class, 'updatePassword']);
    // static page update
    Route::post('pages/{slug}', [StaticPageController::class, 'update']);
    // notification
    Route::get('/get-notifications', [NotificationController::class, 'getNotifications']);
    Route::patch('/read', [NotificationController::class, 'read']);
    Route::patch('/read-all', [NotificationController::class, 'readAll']);
    Route::get('/notification-status', [NotificationController::class, 'status']);
    // get all challeng from admin
    Route::get('/get-challenge-type-lists', [GroupController::class, 'getChallengeTypeLists']);

    Route::middleware('admin')->prefix('admin')->group(function () {

        // dashboard
        Route::get('/dashboard-info', [DashboardController::class, 'dashboardInfo']);
        Route::get('/user-chart', [DashboardController::class, 'userChart']);
        Route::get('/group-chart', [DashboardController::class, 'groupChart']);
        Route::get('/top-challenge-chart', [DashboardController::class, 'topChallengeChart']);
        Route::get('/revenue-chart', [DashboardController::class, 'revenueChart']);

        // challenge type
        Route::post('/add-type', [ChallengeTypeController::class, 'addType']);
        Route::get('/get-types', [ChallengeTypeController::class, 'getTypes']);
        Route::get('/view-type/{id?}', [ChallengeTypeController::class, 'viewType']);
        Route::patch('/edit-type/{id?}', [ChallengeTypeController::class, 'editType']);
        Route::delete('/delete-type/{id?}', [ChallengeTypeController::class, 'deleteType']);

        // active challenge
        Route::get('/get-active-challenges', [ChallengeManagementController::class, 'getActiveChallenges']);
        Route::get('/view-active-challenge/{id?}', [ChallengeManagementController::class, 'viewActiveChallenge']);
        Route::get('/get-completed-challenges', [ChallengeManagementController::class, 'getCompletedChallenges']);
        Route::get('/view-completed-challenge/{id?}', [ChallengeManagementController::class, 'viewCompletedChallenge']);

        // user management
        Route::get('/get-users', [UserManagementController::class, 'getUsers']);
        Route::get('/view-user', [UserManagementController::class, 'viewUser']);
        Route::patch('/block-unblock-user', [UserManagementController::class, 'blockUnblockUser']);
        Route::get('/basic-info', [AdvanceFeatureController::class, 'basicInfo']);

        // partner business
        Route::get('/get-partners', [PartnerBusinessController::class, 'getPartners']);

        // available reward
        Route::get('/get-rewards', [AvailableRewardController::class, 'getRewards']);
        Route::get('/view-reward/{id?}', [AvailableRewardController::class, 'viewReward']);
        Route::patch('/approved-reward', [AvailableRewardController::class, 'approvedReward']);
        Route::patch('/canceled-reward', [AvailableRewardController::class, 'canceledReward']);

        // subscriptions
        Route::get('/get-subscriptions', [SubscriptionControler::class, 'getSubscriptions']);
        Route::post('/edit-premium-price/{id?}', [SubscriptionControler::class, 'editPremiumPrice']);

        // transation
        Route::get('/get-transations', [TransationController::class, 'getTransations']);
    });

    Route::middleware('partner')->prefix('partner')->group(function () {
        // rewards
        Route::get('/check-profile-completion', [RewardController::class, 'checkProfileCompletion']);
        Route::post('/add-reward', [RewardController::class, 'addReward']);
        Route::patch('/enable-disable-reward/{id?}', [RewardController::class, 'enableDisableReward']);
        Route::get('/get-rewards', [RewardController::class, 'getRewards']);

        // redemptions
        Route::get('/get-redeem-history', [RedemptionController::class, 'getRedeemHistory']);
        Route::get('/get-redemption-details/{id?}', [RedemptionController::class, 'getRedemptionDetails']);
        Route::patch('/mark-as-redeemed/{id?}', [RedemptionController::class, 'markAsRedeemed']);
    });

    Route::middleware('user')->prefix('user')->group(function () {
        // habits
        Route::post('/add-new-habit', [HabitController::class, 'addNewHabit']);
        Route::get('/get-habits', [HabitController::class, 'getHabits']);
        Route::get('/view-habit/{id?}', [HabitController::class, 'viewHabit']);
        Route::delete('/delete-habit/{id?}', [HabitController::class, 'deleteHabit']);
        Route::patch('/archived-habit', [HabitController::class, 'archivedHabit']);
        Route::patch('/done-habit', [HabitController::class, 'doneHabit']);

        // say no
        Route::post('/add-entry', [SayNoController::class, 'addEntry']);
        Route::get('/get-entries', [SayNoController::class, 'getEntries']);
        Route::get('/view-entry/{id?}', [SayNoController::class, 'viewEntry']);
        Route::delete('/delete-entry/{id?}', [SayNoController::class, 'deleteEntry']);

        // rewards
        Route::get('/get-available-rewards', [UserRewardController::class, 'getAvailableRewards']);
        Route::get('/view-reward/{id?}', [UserRewardController::class, 'viewReward']);
        Route::patch('/redeem-reward', [UserRewardController::class, 'redeem']);
        Route::get('/get-redeem-history', [UserRewardController::class, 'getRedeemHistory']);
        Route::get('/get-redemption-details/{id?}', [UserRewardController::class, 'getRedemptionDetails']);
        Route::patch('/mark-as-completed/{id?}', [UserRewardController::class, 'markAsCompleted']);

        // groups
        Route::post('/create-group', [GroupController::class, 'createGroup']);
        Route::get('/get-groups', [GroupController::class, 'getGroups']);
        Route::get('/get-active-groups', [GroupController::class, 'getActiveGroups']);
        Route::get('/view-group/{id?}', [GroupController::class, 'viewGroup']);
        Route::post('/join-group', [GroupController::class, 'joinGroup']);
        Route::post('/log-progress', [GroupController::class, 'logProgress']);
        Route::get('/get-today-logs', [GroupController::class, 'getTodayLogs']);
        Route::patch('/task-completed', [GroupController::class, 'taskCompleted']);
        Route::get('/get-daily-summaries', [GroupController::class, 'getDailySummaries']);
        Route::get('/get-overall-progress', [GroupController::class, 'getOverallProgress']);
        Route::get('/get-my-completed-groups', [GroupController::class, 'getMyCompletedGroups']);
        Route::post('/send-celebration', [GroupController::class, 'sendCelebration']);
        Route::get('/check-group-member', [GroupController::class, 'checkGroupMember']);
        Route::get('/group-array', [GroupController::class, 'groupArray']);
        Route::get('/view-celebration-member', [GroupController::class, 'viewCelebrationMember']);
        Route::get('/get-users', [GroupController::class, 'getUsers']);
        Route::get('/my-group-lists', [GroupController::class, 'myGroupLists']);

        // payment
        Route::post('/payment-intent', [PaymentController::class, 'paymentIntent']);
        Route::post('/payment-success', [PaymentController::class, 'paymentSuccess']);

        //advance feature
        Route::get('/basic-info', [AdvanceFeatureController::class, 'basicInfo']);
        Route::get('/get-subscriptions', [AdvanceFeatureController::class, 'getSubscriptions']);
        Route::get('/premium-user-check', [AdvanceFeatureController::class, 'premiumUserCheck']);
        Route::get('/habit-calendar', [AdvanceFeatureController::class, 'habitCalendar']);
        Route::get('/mode-track-line-graph', [AdvanceFeatureController::class, 'modeTrackLineGraph']);
        Route::get('/say-no-bar-chart', [AdvanceFeatureController::class, 'sayOnBarChart']);
    });
});
