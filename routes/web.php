<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| The backend has no public website — it's an API-only server with an
| admin panel mounted at /admin. Hitting the bare host should send the
| visitor straight to the admin login (no marketing page, no framework
| branding).
|
| Mobile clients never hit web routes (they only use /api/v1/*), so
| anything here is for browser visits — currently just redirecting to
| the admin panel.
|
*/

// Bare-host visit → admin login. No public website exists.
Route::get('/', fn () => redirect('/admin/login'));

// Catch any other accidental browser hit and bounce them too instead
// of leaking a generic "404 Not Found" page that could fingerprint
// the framework.
Route::fallback(fn () => redirect('/admin/login'));
