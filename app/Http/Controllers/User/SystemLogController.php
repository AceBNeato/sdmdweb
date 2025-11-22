<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controller as BaseController;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemLogController extends BaseController
{
    /**
     * Display the system logs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        return $this->getLogsByType($request, 'all');
    }

    /**
     * Display accounts logs (user management activities).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function accountsLogs(Request $request)
    {
        return $this->getLogsByType($request, 'accounts');
    }

    /**
     * Display equipment logs (equipment-related activities).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function equipmentLogs(Request $request)
    {
        return $this->getLogsByType($request, 'equipment');
    }

    /**
     * Display user login logs.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function userLoginLogs(Request $request)
    {
        return $this->getLogsByType($request, 'login');
    }


    /**
     * Get logs filtered by type.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $type
     * @return \Illuminate\View\View
     */
    private function getLogsByType(Request $request, string $type)
    {
        // Build query with filters - shows logs from ALL users by default
        $query = Activity::with('user')
            ->orderBy('created_at', 'desc');

        // Apply type-specific filters
        if ($type !== 'all') {
            $this->applyTypeFilter($query, $type);
        }

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('type', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('first_name', 'like', "%{$search}%")
                               ->orWhere('last_name', 'like', "%{$search}%")
                               ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Apply user filter
        if ($request->filled('user_id') && $request->user_id !== 'all') {
            $query->where('user_id', $request->user_id);
        }

        // Apply type filter
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        // Apply date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Get unique types for filter dropdown
        $actions = Activity::select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        // Get users for filter dropdown
        $users = \App\Models\User::select('id', 'first_name', 'last_name', 'email')
            ->orderBy('first_name')
            ->get();

        // Paginate results
        $activities = $query->paginate(25)->appends($request->query());

        return view('system-logs.index', compact('activities', 'actions', 'users', 'type'));
    }

    /**
     * Apply type-specific filters to the query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $type
     * @return void
     */
    private function applyTypeFilter($query, string $type)
    {
        switch ($type) {
            case 'accounts':
                $accountsKeywords = [
                    'admin/accounts',
                    'accounts',
                    'users',
                    'rbac',
                    'staff',
                    'technician',
                    'profile',
                    'role',
                    'permission',
                    'user-store',
                    'user-update',
                    'user-delete',
                ];

                $query->where(function ($q) use ($accountsKeywords) {
                    foreach ($accountsKeywords as $keyword) {
                        $q->orWhere('type', 'like', "%{$keyword}%")
                          ->orWhere('description', 'like', "%{$keyword}%");
                    }
                });
                break;

            case 'equipment':
                $equipmentKeywords = [
                    'equipment',
                    'maintenance',
                    'repair',
                    'history',
                    'ict_history_sheet',
                    'qr',
                    'scan',
                    'equipment-history',
                    'equipment.store',
                    'equipment.update',
                    'equipment.destroy',
                    'technician/equipment',
                    '/history',
                    'history.store',
                    'history/create',
                    '/status',
                    'status.update',
                    'qrcode',
                    'download-qrcode',
                    'print-qrcode',
                ];

                $query->where(function ($q) use ($equipmentKeywords) {
                    foreach ($equipmentKeywords as $keyword) {
                        $q->orWhere('type', 'like', "%{$keyword}%")
                          ->orWhere('description', 'like', "%{$keyword}%");
                    }
                });
                break;

            case 'login':
                $authKeywords = [
                    'login',
                    'logout',
                    'unlock-session',
                    'lock-screen',
                    'session',
                ];

                $query->where(function ($q) use ($authKeywords) {
                    foreach ($authKeywords as $keyword) {
                        $q->orWhere('type', 'like', "%{$keyword}%")
                          ->orWhere('description', 'like', "%{$keyword}%");
                    }
                });
                break;
        }
    }

    /**
     * Clear old system logs (older than specified days).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function clear(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:365'
        ]);

        $deleted = Activity::where('created_at', '<', now()->subDays($request->days))->delete();

        return redirect()->back()
            ->with('success', "Successfully deleted {$deleted} log entries older than {$request->days} days.");
    }

    /**
     * Export system logs to CSV.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        $filename = 'system-logs-' . now()->format('Y-m-d-H-i-s') . '.csv';

        return response()->stream(function () use ($request) {
            $handle = fopen('php://output', 'w');

            // Write CSV headers
            fputcsv($handle, [
                'ID',
                'User Name',
                'User Email',
                'Action',
                'Description',
                'Created At'
            ]);

            // Build query (same as index method) - exports logs from ALL users
            $query = Activity::with('user')
                ->orderBy('created_at', 'desc');

            // Apply type-specific filters (same as getLogsByType)
            $type = $request->get('type', 'all');
            if ($type !== 'all') {
                $this->applyTypeFilter($query, $type);
            }

            // Apply same filters as index
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('type', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhereHas('user', function ($userQuery) use ($search) {
                          $userQuery->where('name', 'like', "%{$search}%")
                                   ->orWhere('email', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('user_id') && $request->user_id !== 'all') {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('type') && $request->type !== 'all') {
                $query->where('type', $request->type);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Get all matching records
            $activities = $query->get();

            // Write data rows
            foreach ($activities as $activity) {
                fputcsv($handle, [
                    $activity->id,
                    $activity->user->name ?? 'N/A',
                    $activity->user->email ?? 'N/A',
                    $activity->type,
                    $activity->description ?? '',
                    $activity->created_at->timezone(config('app.timezone'))->format('Y-m-d H:i:s')
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
