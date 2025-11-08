<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HoneypotProtection
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
        // Honeypot field check
        if ($request->has('website') && !empty($request->input('website'))) {
            Log::warning('Honeypot Protection: Bot detected via honeypot field', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'honeypot_value' => $request->input('website')
            ]);

            return response()->json(['error' => 'Request blocked.'], 403);
        }

        // Check for suspicious timing (too fast submissions)
        $sessionKey = 'request_timing_' . session()->getId();
        $lastRequestTime = Cache::get($sessionKey);

        if ($lastRequestTime) {
            $timeDiff = microtime(true) - $lastRequestTime;
            if ($timeDiff < 0.1) { // Less than 100ms between requests
                Log::warning('Honeypot Protection: Suspicious timing detected', [
                    'ip' => $request->ip(),
                    'time_diff' => $timeDiff,
                    'url' => $request->fullUrl()
                ]);

                return response()->json(['error' => 'Too fast. Please slow down.'], 429);
            }
        }

        Cache::put($sessionKey, microtime(true), 300); // 5 minutes

        return $next($request);
    }
}
