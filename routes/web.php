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

// ── Legal pages (public, required by Apple + Google review) ────────
//
// The mobile app's PaywallScreen links to these URLs — they MUST be
// reachable without auth. Apple/Google reviewers also visit them
// directly to verify the subscription terms before approving the app.
// Effective date is the launch date — bump it whenever the legal text
// changes so users can see when it was last revised.
Route::view('/privacy-policy', 'legal.privacy', [
    'effectiveDate' => 'April 27, 2026',
])->name('legal.privacy');

Route::view('/terms-of-service', 'legal.terms', [
    'effectiveDate' => 'April 27, 2026',
])->name('legal.terms');

// Catch any other accidental browser hit and bounce them to admin
// login instead of leaking a generic 404 that could fingerprint the
// framework. The legal routes above are matched first.
Route::fallback(fn () => redirect('/admin/login'));
