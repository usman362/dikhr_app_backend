<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Handles RevenueCat webhook events to keep local subscription
 * state in sync. RevenueCat is the single source of truth.
 *
 * Webhook URL: POST /api/v1/webhooks/revenuecat
 * Authorization header must match REVENUECAT_WEBHOOK_SECRET.
 *
 * Events handled:
 *  - INITIAL_PURCHASE
 *  - RENEWAL
 *  - CANCELLATION
 *  - EXPIRATION
 *  - BILLING_ISSUE
 *  - PRODUCT_CHANGE
 *  - SUBSCRIBER_ALIAS (not used)
 */
class RevenueCatWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        // Verify webhook secret
        $secret = config('services.revenuecat.webhook_secret');
        if ($secret && $request->header('Authorization') !== "Bearer {$secret}") {
            Log::warning('RevenueCat webhook: invalid authorization header');
            abort(401);
        }

        $payload = $request->all();
        $event = $payload['event'] ?? [];
        $type = $event['type'] ?? 'UNKNOWN';

        Log::info("RevenueCat webhook received: {$type}", ['event_id' => $event['id'] ?? null]);

        // Extract subscriber info
        $appUserId = $event['app_user_id'] ?? null;
        $productId = $event['product_id'] ?? null;
        $store = $event['store'] ?? 'unknown';
        $transactionId = $event['transaction_id'] ?? null;
        $expirationAt = $event['expiration_at_ms'] ?? null;
        $purchasedAt = $event['purchased_at_ms'] ?? null;

        if (! $appUserId) {
            return response()->json(['status' => 'ignored', 'reason' => 'no app_user_id']);
        }

        // Find user by app_user_id (we use user ID as RevenueCat customer ID)
        $user = User::query()->find($appUserId);
        if (! $user) {
            // Try finding by rc_customer_id in subscriptions table
            $sub = Subscription::query()->where('rc_customer_id', $appUserId)->first();
            $user = $sub?->user;
        }

        if (! $user) {
            Log::warning("RevenueCat webhook: user not found for app_user_id={$appUserId}");
            return response()->json(['status' => 'ignored', 'reason' => 'user not found']);
        }

        // Normalize store name
        $storeName = match (strtolower($store)) {
            'app_store', 'mac_app_store' => 'app_store',
            'play_store' => 'play_store',
            default => $store,
        };

        // Process event
        match ($type) {
            'INITIAL_PURCHASE' => $this->handlePurchase($user, $appUserId, $storeName, $productId, $transactionId, $purchasedAt, $expirationAt),
            'RENEWAL' => $this->handleRenewal($user, $storeName, $productId, $transactionId, $purchasedAt, $expirationAt),
            'CANCELLATION' => $this->handleCancellation($user),
            'EXPIRATION' => $this->handleExpiration($user),
            'BILLING_ISSUE' => $this->handleBillingIssue($user),
            'PRODUCT_CHANGE' => $this->handleProductChange($user, $productId, $expirationAt),
            default => Log::info("RevenueCat: unhandled event type: {$type}"),
        };

        return response()->json(['status' => 'ok']);
    }

    private function handlePurchase(
        User $user, string $rcCustomerId, string $store,
        ?string $productId, ?string $transactionId,
        ?int $purchasedAtMs, ?int $expirationAtMs
    ): void {
        Subscription::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'rc_customer_id'       => $rcCustomerId,
                'store'                => $store,
                'product_id'           => $productId ?? 'unknown',
                'store_transaction_id' => $transactionId,
                'status'               => 'active',
                'current_period_start' => $purchasedAtMs ? date('Y-m-d H:i:s', $purchasedAtMs / 1000) : now(),
                'current_period_end'   => $expirationAtMs ? date('Y-m-d H:i:s', $expirationAtMs / 1000) : null,
                'auto_renew'           => true,
            ],
        );

        $user->update(['is_premium' => true]);
        Log::info("User #{$user->id} subscribed with {$productId}");
    }

    private function handleRenewal(
        User $user, string $store, ?string $productId,
        ?string $transactionId, ?int $purchasedAtMs, ?int $expirationAtMs
    ): void {
        $sub = $user->subscription;
        if ($sub) {
            $sub->update([
                'status'               => 'active',
                'store_transaction_id' => $transactionId ?? $sub->store_transaction_id,
                'current_period_start' => $purchasedAtMs ? date('Y-m-d H:i:s', $purchasedAtMs / 1000) : now(),
                'current_period_end'   => $expirationAtMs ? date('Y-m-d H:i:s', $expirationAtMs / 1000) : null,
                'auto_renew'           => true,
            ]);
        }

        $user->update(['is_premium' => true]);
        Log::info("User #{$user->id} subscription renewed");
    }

    private function handleCancellation(User $user): void
    {
        $sub = $user->subscription;
        if ($sub) {
            $sub->update([
                'auto_renew' => false,
                'status'     => 'cancelled',
            ]);
        }

        // User keeps premium until period ends — don't revoke immediately
        Log::info("User #{$user->id} subscription cancelled (still active until period end)");
    }

    private function handleExpiration(User $user): void
    {
        $sub = $user->subscription;
        if ($sub) {
            $sub->update(['status' => 'expired', 'auto_renew' => false]);
        }

        $user->update(['is_premium' => false]);
        Log::info("User #{$user->id} subscription expired");
    }

    private function handleBillingIssue(User $user): void
    {
        $sub = $user->subscription;
        if ($sub) {
            $sub->update(['status' => 'grace_period']);
        }

        // Keep premium during grace period
        Log::info("User #{$user->id} billing issue — grace period");
    }

    private function handleProductChange(User $user, ?string $newProductId, ?int $expirationAtMs): void
    {
        $sub = $user->subscription;
        if ($sub) {
            $sub->update([
                'product_id'         => $newProductId ?? $sub->product_id,
                'current_period_end' => $expirationAtMs ? date('Y-m-d H:i:s', $expirationAtMs / 1000) : $sub->current_period_end,
                'status'             => 'active',
            ]);
        }

        Log::info("User #{$user->id} changed product to {$newProductId}");
    }
}
