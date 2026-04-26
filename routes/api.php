<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| All API routes are versioned under /api/v1/. The mobile app pins to
| this prefix, so any new endpoints belong in routes/api_v1.php.
|
| Older builds that hit unversioned URLs (no /v1 prefix) are no longer
| supported — the duplicate alias group has been removed because the
| live mobile app already targets /api/v1.
|
*/

Route::prefix('v1')->group(base_path('routes/api_v1.php'));
