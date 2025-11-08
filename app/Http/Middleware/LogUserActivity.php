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
            $action = $request->method() . ' ' . $request->path();

            // Log to Laravel's log file
            Log::info('User Activity', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'action' => $action,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Log to database activities table
            try {
                Activity::create([
                    'user_id' => $user->id,
                    'action' => $action,
                    'description' => $this->getActionDescription($request),
                ]);
            } catch (\Exception $e) {
                // Log the error but don't break the request
                Log::error('Failed to log user activity to database: ' . $e->getMessage());
            }
        }

        return $next($request);
    }

    /**
     * Get a human-readable description of the action.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    private function getActionDescription(Request $request): string
    {
        $method = $request->method();
        $path = $request->path();

        // Extract meaningful descriptions based on common patterns
        if (str_contains($path, '/equipment/')) {
            if ($method === 'GET' && str_contains($path, '/create')) {
                return 'Viewed equipment creation form';
            } elseif ($method === 'POST' && !str_contains($path, '/scan')) {
                return 'Created new equipment';
            } elseif ($method === 'GET' && !str_contains($path, '/create') && !str_contains($path, '/edit') && !str_contains($path, '/qrcode')) {
                return 'Viewed equipment details';
            } elseif ($method === 'PUT') {
                return 'Updated equipment information';
            } elseif ($method === 'DELETE') {
                return 'Deleted equipment';
            } elseif (str_contains($path, '/scan')) {
                return 'Scanned equipment QR code';
            }
        }

        if (str_contains($path, '/reports/')) {
            return 'Accessed reports section';
        }

        if (str_contains($path, '/profile')) {
            if ($method === 'PUT' || $method === 'POST') {
                return 'Updated profile information';
            } else {
                return 'Viewed profile';
            }
        }

        if (str_contains($path, '/admin/accounts')) {
            return 'Accessed user accounts management';
        }

        if (str_contains($path, '/admin/equipment')) {
            return 'Accessed equipment management';
        }

        if (str_contains($path, '/admin/offices')) {
            return 'Accessed offices management';
        }

        if (str_contains($path, '/admin/system-logs')) {
            return 'Accessed system logs';
        }

        // Default descriptions based on HTTP method
        switch ($method) {
            case 'GET':
                return 'Viewed page: ' . $path;
            case 'POST':
                return 'Created or submitted data on: ' . $path;
            case 'PUT':
                return 'Updated data on: ' . $path;
            case 'DELETE':
                return 'Deleted data on: ' . $path;
            default:
                return 'Performed action on: ' . $path;
        }
    }
}
