<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Get the current user's access status.
     *
     * Returns the single source of truth the mobile app uses to decide
     * whether to show the app or the paywall. Subscription-only model:
     * the user can use the app if they're either on trial OR paid.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscription;

        return response()->json([
            // The one boolean the mobile router cares about — true means
            // "let them in", false means "show paywall".
            'has_active_access'    => $user->hasActiveAccess(),

            // Trial state (so mobile can show the countdown banner)
            'is_on_trial'          => $user->isOnTrial(),
            'trial_started_at'     => $user->trial_started_at?->toIso8601String(),
            'trial_ends_at'        => $user->trial_ends_at?->toIso8601String(),
            'trial_days_remaining' => $user->trialDaysRemaining(),

            // Paid subscription state
            'is_premium'           => $user->isPremium(),
            'subscription'         => $subscription ? [
                'status'               => $subscription->status,
                'product_id'           => $subscription->product_id,
                'store'                => $subscription->store,
                'auto_renew'           => $subscription->auto_renew,
                'current_period_start' => $subscription->current_period_start?->toIso8601String(),
                'current_period_end'   => $subscription->current_period_end?->toIso8601String(),
            ] : null,
        ]);
    }

    /**
     * Verify a purchase receipt from mobile app.
     *
     * After a purchase is made via RevenueCat SDK on mobile, the app
     * calls this to confirm the backend has received the webhook. If
     * the webhook hasn't arrived yet (race condition between IAP
     * completion + RevenueCat → our webhook), we optimistically create
     * a pending subscription so the user isn't bounced back to the
     * paywall while we wait for RevenueCat to catch up.
     */
    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id'    => ['required', 'string'],
            'store'         => ['required', 'string', 'in:app_store,play_store'],
            'transaction_id' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $subscription = $user->subscription;

        // If webhook already processed, just confirm
        if ($subscription && $subscription->isActive()) {
            return response()->json([
                'verified'           => true,
                'has_active_access'  => true,
                'is_premium'         => true,
                'message'            => 'Subscription already active.',
            ]);
        }

        // Create a pending subscription record if webhook hasn't arrived yet
        $user->subscription()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'store'                => $data['store'],
                'product_id'           => $data['product_id'],
                'store_transaction_id' => $data['transaction_id'],
                'status'               => 'active',
                'current_period_start' => now(),
                'auto_renew'           => true,
            ],
        );

        $user->update(['is_premium' => true]);

        return response()->json([
            'verified'           => true,
            'has_active_access'  => true,
            'is_premium'         => true,
            'message'            => 'Subscription activated.',
        ]);
    }
}
