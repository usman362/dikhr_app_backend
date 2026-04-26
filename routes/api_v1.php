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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| V1 API Routes
|--------------------------------------------------------------------------
*/

// ── Health check (public, unauthenticated) ─────────────────────────
//
// Used by load balancers, uptime monitors, and the mobile app's
// "is the server reachable?" probe. Pings the database so a bad
// connection / migration state still surfaces as unhealthy.
Route::get('/health', function () {
    try {
        DB::connection()->getPdo();
        $db = 'ok';
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'degraded',
            'db'     => 'down',
            'time'   => now()->toIso8601String(),
        ], 503);
    }
    return response()->json([
        'status'  => 'ok',
        'db'      => $db,
        'version' => 'v1',
        'time'    => now()->toIso8601String(),
    ]);
});

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

// ── Authenticated (no subscription required) ───────────────────────
//
// These routes work even when the user's trial has ended and they
// have no active subscription. The mobile app needs them to:
//   - log out from the locked screen
//   - check who they are (so the paywall can greet them by name)
//   - check subscription status (so the router knows where to send them)
//   - complete a purchase (verify endpoint receives the receipt)
//
// Everything else is gated behind `active_access` below.
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Subscription Management — must be reachable from paywall
    Route::get('/subscription/status',  [SubscriptionController::class, 'status']);
    Route::post('/subscription/verify', [SubscriptionController::class, 'verify']);
});

// ── Authenticated + Active Subscription/Trial Required ─────────────
//
// Subscription-only model: every data route requires either an active
// 7-day trial OR a paid subscription. `active_access` middleware
// returns HTTP 402 with `subscription_required: true` when the user
// has neither, and the mobile router redirects to the paywall.
Route::middleware(['auth:sanctum', 'active_access'])->group(function () {

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
});
