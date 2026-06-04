<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Laravel side of emhai.dk
|--------------------------------------------------------------------------
|
| The operator's own EMH AI company website lives at the document root
| as static files (`public/index.html`, `public/about.html`, etc.). The
| Apache rewrite rules in `public/.htaccess` only hand a request to
| Laravel when the URL matches one of the prefixes the app actually owns:
|
|   • /api/v1/*                       — mobile app + webhooks
|   • /admin/*                        — Filament admin panel
|   • /privacy-policy                 — public legal page
|   • /terms-of-service               — public legal page
|
| Mobile clients hit `/api/v1/*` exclusively — they never reach this
| file. Admin login is mounted by Filament at `/admin/login`.
|
*/

// ── Operator's static-site passthrough ──────────────────────────────
//
// On Apache, the rewrite rules in `public/.htaccess` serve the
// operator's HTML files (`index.html`, `about.html`, etc.) without
// Laravel ever booting. But the PHP built-in dev server (and any
// shared host that ignores .htaccess) sends the request straight to
// Laravel, so we need an in-app fallback that does the same lookup:
// take the slug, try `public/<slug>.html`, serve it if found, 404
// otherwise.
//
// The constraint excludes paths that genuinely belong to Laravel /
// Filament so they keep working:
//
//   /api/...                  → API routes
//   /admin (and /admin/...)   → Filament admin panel
//   /privacy-policy           → legal page (registered below)
//   /terms-of-service         → legal page (registered below)
//   /storage, /livewire,      → framework / first-party packages
//    /filament, /sanctum,
//    /_ignition, /up
//
// Anything else is treated as a static page. The negative lookahead
// inside the constraint stops Laravel from matching THIS route on
// excluded slugs so the next matching route (e.g. Filament's) gets a
// turn instead of us 404-ing prematurely.
Route::get('/', function () {
    $file = public_path('index.html');
    return file_exists($file)
        ? response()->file($file)
        : abort(404);
});

Route::get('/{slug}', function (string $slug) {
    $file = public_path($slug . '.html');
    return file_exists($file)
        ? response()->file($file)
        : abort(404);
})->where(
    'slug',
    '(?!(?:api|admin|storage|livewire|filament|sanctum|_ignition|privacy-policy|terms-of-service|up)$)[a-z0-9_-]+'
);

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
