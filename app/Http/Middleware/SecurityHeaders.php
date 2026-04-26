<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends a standard set of security + framework-anonymizing headers on
 * every response.
 *
 * Why these headers:
 *   - X-Powered-By stripped: hides PHP version (vulnerability scanners
 *     fingerprint apps by reading this).
 *   - Server header overwritten: same reason — default value leaks
 *     "Apache/2.4.x (Ubuntu)" or similar.
 *   - X-Frame-Options DENY: prevents clickjacking via iframe.
 *   - X-Content-Type-Options nosniff: forces declared MIME types,
 *     blocks MIME-sniffing attacks.
 *   - Referrer-Policy strict-origin: doesn't leak full URLs to other
 *     sites on outbound clicks.
 *   - Permissions-Policy: explicitly turns off device features the
 *     admin panel doesn't use (camera, mic, geolocation, etc.) so a
 *     compromised JS dependency can't request them.
 *   - HSTS in production: tells browsers "always use HTTPS for this
 *     domain" for one year, including subdomains. Only emitted over
 *     HTTPS — sending it over HTTP has no effect and can confuse
 *     local-dev browsers.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Strip framework / language fingerprints. PHP exposes
        // X-Powered-By when `expose_php = On` (the default on dev
        // installs, occasionally on shared hosting). Symfony's
        // header bag remove only clears headers that Laravel set —
        // PHP's SAPI re-adds X-Powered-By at output time, so we
        // also call header_remove() at the PHP level to nuke it
        // before the response is sent. Together this guarantees
        // the header is gone in every environment.
        $response->headers->remove('X-Powered-By');
        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }
        // Anonymize the Server header. Most production stacks (Apache /
        // Nginx) write it themselves; this hides it at the framework
        // layer too. The web server config should override this with
        // `ServerTokens Prod` / `server_tokens off`.
        $response->headers->set('Server', 'web');

        // Standard security headers — safe defaults for an API + admin
        // panel server. Adjust if Filament ever needs to be embedded.
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), interest-cohort=(), payment=(), usb=()'
        );

        // HSTS only over HTTPS — telling a browser to "always use HTTPS"
        // before the user has visited the site over HTTPS would brick
        // local dev (which is plain HTTP).
        if ($request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
