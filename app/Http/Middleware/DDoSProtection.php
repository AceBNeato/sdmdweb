<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;

class DDoSProtection
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $ip = $request->ip();
        $route = $request->route() ? $request->route()->getName() : 'unknown';
        $userAgent = $request->userAgent();
        $method = $request->method();

        // Skip aggressive protection for login routes
        if (in_array($route, ['login', 'login.submit', 'technician.login', 'staff.login'])) {
            // Only basic rate limiting for login routes
            $loginKey = 'login_attempts_' . $ip;
            if (RateLimiter::tooManyAttempts($loginKey, 10)) { // 10 login attempts per minute
                return response()->json([
                    'error' => 'Too many login attempts. Please try again later.',
                    'retry_after' => RateLimiter::availableIn($loginKey)
                ], 429);
            }
            RateLimiter::hit($loginKey, 60);
            return $next($request);
        }

        // Rate limiting per IP (requests per minute) - reduced from 100 to 200
        $ipKey = 'ddos_ip_' . $ip;
        if (RateLimiter::tooManyAttempts($ipKey, 200)) {
            Log::warning('DDOS Protection: Rate limit exceeded for IP', [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'route' => $route,
                'method' => $method
            ]);

            return response()->json([
                'error' => 'Too many requests. Please try again later.',
                'retry_after' => RateLimiter::availableIn($ipKey)
            ], 429)->header('Retry-After', RateLimiter::availableIn($ipKey));
        }
        RateLimiter::hit($ipKey, 60);

        // Rate limiting per route - increased from 30 to 50
        $routeKey = 'ddos_route_' . $route . '_' . $ip;
        if (RateLimiter::tooManyAttempts($routeKey, 50)) {
            Log::warning('DDOS Protection: Route rate limit exceeded', [
                'ip' => $ip,
                'route' => $route,
                'user_agent' => $userAgent,
                'method' => $method
            ]);

            return response()->json([
                'error' => 'Too many requests to this endpoint.',
                'retry_after' => RateLimiter::availableIn($routeKey)
            ], 429)->header('Retry-After', RateLimiter::availableIn($routeKey));
        }
        RateLimiter::hit($routeKey, 60);

        // Skip suspicious request detection for development
        // if ($this->isSuspiciousRequest($request)) {
        //     Log::warning('DDOS Protection: Suspicious request detected', [
        //         'ip' => $ip,
        //         'user_agent' => $userAgent,
        //         'route' => $route,
        //         'method' => $method,
        //         'headers' => $request->headers->all()
        //     ]);

        //     return response()->json(['error' => 'Request blocked.'], 403);
        // }

        // Bot detection - exclude common development tools
        if ($this->isLikelyBot($request) && !$this->isDevelopmentTool($request)) {
            Log::info('DDOS Protection: Bot detected', [
                'ip' => $ip,
                'user_agent' => $userAgent,
                'route' => $route
            ]);

            // Still allow but log for monitoring
            $request->merge(['bot_detected' => true]);
        }

        return $next($request);
    }

    /**
     * Check if request appears suspicious
     */
    private function isSuspiciousRequest(Request $request): bool
    {
        // Check for unusual headers
        $suspiciousHeaders = [
            'X-Forwarded-For' => 'multiple', // Multiple forwarded IPs
            'User-Agent' => ['bot', 'crawler', 'spider'],
            'Accept' => '', // Empty accept header
        ];

        // Check for rapid repeated requests to the same endpoint
        $fingerprint = md5($request->fullUrl() . $request->ip() . serialize($request->all()));

        $fingerprintKey = 'request_fingerprint_' . $fingerprint;
        if (RateLimiter::tooManyAttempts($fingerprintKey, 5)) {
            return true;
        }
        RateLimiter::hit($fingerprintKey, 30); // 30 second window

        return false;
    }

    /**
     * Check if request is likely from a bot
     */
    private function isLikelyBot(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent() ?? '');

        // Common bot indicators (excluding development tools)
        $botIndicators = [
            'bot',
            'crawler',
            'spider',
            'scraper',
            'googlebot',
            'bingbot',
            'yahoo',
            'baidu',
            'yandex',
            'facebookexternalhit',
            'twitterbot',
            'linkedinbot',
            'whatsapp',
            'telegrambot',
        ];

        foreach ($botIndicators as $indicator) {
            if (str_contains($userAgent, $indicator)) {
                return true;
            }
        }

        // Check for missing or suspicious headers (but be less strict)
        if ($request->userAgent() === null || $request->userAgent() === '') {
            return true;
        }

        // Check for empty or suspicious accept headers
        $accept = $request->header('Accept', '');
        if ($accept === '' || $accept === '*/*') {
            // Allow this for API requests and development tools
            if (!$request->expectsJson() && !$this->isDevelopmentTool($request)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if request is from a development tool
     */
    private function isDevelopmentTool(Request $request): bool
    {
        $userAgent = strtolower($request->userAgent() ?? '');

        // Common development tools that should be allowed
        $devTools = [
            'postman',
            'insomnia',
            'curl',
            'wget',
            'python-requests',
            'httpie',
            'paw/',
            'advanced rest client',
            'restclient',
            'soapui',
            'fiddler',
            'charles',
            'burp',
            'wireshark',
            'mitmproxy',
        ];

        foreach ($devTools as $tool) {
            if (str_contains($userAgent, $tool)) {
                return true;
            }
        }

        return false;
    }
}
