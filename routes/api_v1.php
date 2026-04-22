<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CampaignController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\DhikrContributionController;
use App\Http\Controllers\Api\GlobalStatsController;
use App\Http\Controllers\Api\GroupController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\RevenueCatWebhookController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\UserStatsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| V1 API Routes
|--------------------------------------------------------------------------
*/

// ── Public (guest) — rate-limited for security ─────────────────────
Route::middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);
});

// Password Reset (public — OTP rate limited)
Route::middleware('throttle:otp')->group(function () {
    Route::post('/password/send-otp', [PasswordResetController::class, 'sendOtp']);
    Route::post('/password/reset',    [PasswordResetController::class, 'resetPassword']);
});

// RevenueCat Webhook (public — secured by webhook secret)
Route::post('/webhooks/revenuecat', [RevenueCatWebhookController::class, 'handle']);

// ── Authenticated ───────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Groups
    Route::get('/groups',              [GroupController::class, 'index']);
    Route::post('/groups',             [GroupController::class, 'store']);
    Route::get('/groups/search',       [GroupController::class, 'search']);
    Route::post('/groups/join-by-code', [GroupController::class, 'joinByCode']);
    Route::get('/groups/{group}',      [GroupController::class, 'show']);
    Route::put('/groups/{group}',      [GroupController::class, 'update']);
    Route::delete('/groups/{group}',   [GroupController::class, 'destroy']);
    Route::post('/groups/{group}/join', [GroupController::class, 'join']);
    Route::post('/groups/{group}/leave', [GroupController::class, 'leave']);
    Route::post('/groups/{group}/regenerate-code', [GroupController::class, 'regenerateInviteCode']);

    // Campaigns
    Route::get('/campaigns',              [CampaignController::class, 'index']);
    Route::post('/campaigns',             [CampaignController::class, 'store']);
    Route::get('/campaigns/{campaign}',   [CampaignController::class, 'show']);
    Route::put('/campaigns/{campaign}',   [CampaignController::class, 'update']);
    Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy']);
    Route::get('/campaigns/{campaign}/leaderboard', [CampaignController::class, 'leaderboard']);

    // Dhikr Contributions (rate-limited)
    Route::middleware('throttle:contributions')->group(function () {
        Route::post('/dhikr-contributions', [DhikrContributionController::class, 'store']);
    });

    // Global Stats (real-time dashboard data)
    Route::get('/global-stats', [GlobalStatsController::class, 'index']);

    // User Stats & History
    Route::get('/user/stats',         [UserStatsController::class, 'stats']);
    Route::get('/user/contributions', [UserStatsController::class, 'contributions']);

    // Device Tokens (Push Notifications)
    Route::post('/device-tokens',   [DeviceTokenController::class, 'store']);
    Route::delete('/device-tokens', [DeviceTokenController::class, 'destroy']);

    // Subscription Management
    Route::get('/subscription/status',  [SubscriptionController::class, 'status']);
    Route::post('/subscription/verify', [SubscriptionController::class, 'verify']);

    // ── Premium-only features ─────────────────────────────────────
    Route::middleware('premium')->group(function () {
        // Premium: Create unlimited groups (free users limited to 3)
        // Premium: Advanced analytics
        // Premium: Ad-free experience (checked client-side)
        // These gates are also enforced in controllers for flexibility
    });
});
