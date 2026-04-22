<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sanitize incoming request data to prevent XSS attacks.
 *
 * Strips HTML tags from string inputs. Applied globally to API routes.
 */
class SanitizeInput
{
    /**
     * Keys to skip sanitization (e.g., password fields).
     */
    private array $except = [
        'password',
        'password_confirmation',
        'token',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();
        $request->merge($this->sanitize($input));

        return $next($request);
    }

    private function sanitize(array $data): array
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $this->except, true)) {
                continue;
            }

            if (is_string($value)) {
                $data[$key] = strip_tags($value);
            } elseif (is_array($value)) {
                $data[$key] = $this->sanitize($value);
            }
        }

        return $data;
    }
}
