<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Public surface for emhai.dk. Three categories live here:
|
|   1. Marketing homepage  (`/`)      — visible to anyone landing on the
|      bare host. Replaces the old admin-login redirect so App Store /
|      Play Store reviewers (and real users) get a real product page,
|      not a backend login form.
|   2. Legal pages         (`/privacy-policy`, `/terms-of-service`) —
|      linked from the in-app paywall + required by Apple/Google review.
|   3. Catch-all fallback                — anything else bounces to the
|      admin login, the only other public surface this host serves.
|
| Mobile clients NEVER hit these routes; they only use `/api/v1/*`, the
| admin panel lives at `/admin/*`, and the RevenueCat webhook hits
| `/api/v1/webhooks/revenuecat`. All of those remain untouched.
|
*/

// Marketing homepage.
Route::view('/', 'home')->name('home');

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
