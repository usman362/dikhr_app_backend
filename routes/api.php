<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All API routes are versioned under /api/v1/
| Legacy unversioned routes redirect to v1 for backward compatibility.
|
*/

Route::prefix('v1')->group(base_path('routes/api_v1.php'));

// ── Backward-compatible aliases (no version prefix) ─────────────────
// These mirror every v1 route so existing mobile builds keep working.
// They can be removed once all clients are updated to use /api/v1/.
Route::group([], base_path('routes/api_v1.php'));
