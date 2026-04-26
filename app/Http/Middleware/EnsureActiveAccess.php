<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Subscription-only gate.
 *
 * The app uses a subscription-only model (client decision): every
 * authenticated user must either be on their 7-day trial OR have an
 * active paid subscription. This middleware enforces that on every
 * data API route — only the auth + subscription endpoints are exempt
 * (so the user can register, log in, check subscription status, and
 * complete a purchase even when their access is currently locked).
 *
 * Returns HTTP 402 Payment Required when access is denied so the
 * mobile client can distinguish "you need to pay" (402) from "you
 * are not allowed to do this" (403). The mobile router uses the
 * `subscription_required` flag to redirect to the paywall.
 *
 * Admins always pass — see User::hasActiveAccess().
 */
class EnsureActiveAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            // No user — auth middleware should have caught this already,
            // but defend against being applied to a public route by
            // mistake.
            return response()->json([
                'message' => 'Authentication required.',
            ], 401);
        }

        if ($user->hasActiveAccess()) {
            return $next($request);
        }

        return response()->json([
            'message'               => 'Your free trial has ended. Subscribe to continue using the app.',
            'subscription_required' => true,
            'is_on_trial'           => false,
            'trial_ended_at'        => $user->trial_ends_at?->toIso8601String(),
        ], 402);
    }
}
