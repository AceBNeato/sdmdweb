<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (!app()->environment('local')) {
            $headers = [
                'X-Frame-Options' => 'SAMEORIGIN',
                'X-Content-Type-Options' => 'nosniff',
                'X-XSS-Protection' => '1; mode=block',
                'Referrer-Policy' => 'strict-origin-when-cross-origin',
                'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: https:; font-src 'self' https: data:;",
                'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
                'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
                'X-Permitted-Cross-Domain-Policies' => 'none',
            ];

            foreach ($headers as $key => $value) {
                $response->headers->set($key, $value);
            }
        }

        return $response;
    }
}
