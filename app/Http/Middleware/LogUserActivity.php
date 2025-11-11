<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Activity;
use Symfony\Component\HttpFoundation\Response;

class LogUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Only log GET requests for page navigation, exclude AJAX and API calls
            if ($request->method() === 'GET' && !$request->ajax() && !$this->isApiRequest($request)) {
                $action = 'Page View: ' . $request->path();

                // Log to Laravel's log file
                Log::info('User Page View', [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'action' => $action,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'url' => $request->fullUrl(),
                ]);

                // Log to database activities table
                try {
                    Activity::create([
                        'user_id' => $user->id,
                        'action' => $action,
                        'description' => $this->getPageViewDescription($request),
                    ]);
                } catch (\Exception $e) {
                    // Log the error but don't break the request
                    Log::error('Failed to log user page view to database: ' . $e->getMessage());
                }
            }
        }

        return $next($request);
    }

    /**
     * Get a human-readable description of the page view.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    private function getPageViewDescription(Request $request): string
    {
        $path = $request->path();

        // Extract meaningful descriptions based on common page patterns
        if (str_contains($path, '/equipment/')) {
            if (str_contains($path, '/create')) {
                return 'Viewed equipment creation form';
            } elseif (str_contains($path, '/edit')) {
                return 'Viewed equipment edit form';
            } elseif (!str_contains($path, '/qrcode') && !str_contains($path, '/download')) {
                return 'Viewed equipment details';
            }
        }

        if (str_contains($path, '/profile')) {
            if (str_contains($path, '/edit')) {
                return 'Viewed profile edit form';
            } else {
                return 'Viewed profile page';
            }
        }

        if (str_contains($path, '/reports/')) {
            return 'Viewed reports section';
        }

        if (str_contains($path, '/admin/accounts')) {
            return 'Viewed user accounts management';
        }

        if (str_contains($path, '/admin/equipment')) {
            return 'Viewed equipment management';
        }

        if (str_contains($path, '/admin/system-logs')) {
            return 'Viewed system logs';
        }

        // Default description for page views
        return 'Viewed page: ' . $path;
    }

    /**
     * Check if the request is an API request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    private function isApiRequest(Request $request): bool
    {
        $path = $request->path();

        // Check for common API patterns
        return str_contains($path, '/api/') ||
               str_contains($path, '/ajax/') ||
               $request->expectsJson() ||
               str_contains($request->header('Accept', ''), 'application/json');
    }
}
