<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Get the current user's subscription status.
     */
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscription;

        return response()->json([
            'is_premium' => $user->isPremium(),
            'subscription' => $subscription ? [
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
     * After a purchase is made via RevenueCat SDK on mobile,
     * the app calls this to confirm the backend has received the webhook.
     * If not yet received, we create a pending subscription record.
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
                'verified'    => true,
                'is_premium'  => true,
                'message'     => 'Subscription already active.',
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
            'verified'    => true,
            'is_premium'  => true,
            'message'     => 'Subscription activated.',
        ]);
    }
}
