<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate premium-only features behind subscription check.
 *
 * Usage: Route::middleware(['auth:sanctum', 'premium'])->group(...)
 */
class EnsurePremium
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isPremium()) {
            return response()->json([
                'message' => 'This feature requires a premium subscription.',
                'upgrade_required' => true,
            ], 403);
        }

        return $next($request);
    }
}
