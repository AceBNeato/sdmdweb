@extends('layouts.app')


@php
    $typeLabels = [
        'all' => 'System Logs',
        'accounts' => 'Accounts Logs',
        'equipment' => 'Equipment Logs',
        'login' => 'User Login Logs',
    ];

    $typeLabel = $typeLabels[$type] ?? 'System Logs';
    $currentRoute = match ($type) {
        'accounts' => route('admin.system-logs.accounts'),
        'equipment' => route('admin.system-logs.equipment'),
        'login' => route('admin.system-logs.user-logins'),
        default => route('admin.system-logs.index'),
    };

    $visibleActivities = collect($activities->items());
    $uniqueUsersCount = $visibleActivities->pluck('user_id')->filter()->unique()->count();
    $latestActivity = $visibleActivities->first();

    $appliedFilters = collect([
        request('search') ? 'Search: "' . request('search') . '"' : null,
        request('user_id') && request('user_id') !== 'all' ? 'User: ' . optional($users->firstWhere('id', request('user_id')))->name : null,
        request('action') && request('action') !== 'all' ? 'Action: ' . request('action') : null,
        request('date_from') ? 'From: ' . request('date_from') : null,
        request('date_to') ? 'To: ' . request('date_to') : null,
    ])->filter();
@endphp

@section('page_title', $typeLabel)
@section('page_description', 'Monitor and track all user activities and system events')

@section('breadcrumbs')
    <a href="{{ route('accounts.index') }}">Accounts</a>
    <span class="separator">/</span>
    <span class="current">{{ $typeLabel }}</span>
@endsection

@push('styles')
  <link href="{{ asset('css/logs.css') }}" rel="stylesheet">
@endpush

@section('title', $typeLabel . ' - SDMD Admin')

@section('content')
<div class="content logs-dashboard">

@if(!auth()->user()->hasPermissionTo('system.logs.view'))
    @php abort(403) @endphp
@else

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <!-- Filters and Export Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ $currentRoute }}" class="row g-3">
                <div class="col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Search actions, descriptions, or users...">
                </div>
                <div class="col-md-2">
                    <label for="user_id" class="form-label">User</label>
                    <select class="form-select" id="user_id" name="user_id">
                        <option value="all">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name ?? $user->first_name . ' ' . $user->last_name }} ({{ $user->email }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="action" class="form-label">Action</label>
                    <select class="form-select" id="action" name="action">
                        <option value="all">All Actions</option>
                        @foreach($actions as $actionItem)
                            <option value="{{ $actionItem }}" {{ request('action') == $actionItem ? 'selected' : '' }}>
                                {{ $actionItem }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-2">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class='bx bx-search'></i>
                    </button>
                </div>
            </form>

            <div class="d-flex justify-content-between align-items-center mt-3">
                <div>
                    @if(request()->hasAny(['search', 'user_id', 'action', 'date_from', 'date_to']))
                        <a href="{{ $currentRoute }}" class="btn btn-outline-secondary btn-sm">
                            <i class='bx bx-x'></i> Clear Filters
                        </a>
                    @endif
                </div>
                <div>
                    <a href="{{ route('admin.system-logs.export', request()->query()) }}" class="btn btn-success btn-sm">
                        <i class='bx bx-download'></i> Export CSV
                    </a>
                    @if(auth()->user()->hasPermissionTo('system.logs.manage'))
                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#clearLogsModal">
                            <i class='bx bx-trash'></i> Clear Old Logs
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Clear Logs Modal -->
    @if(auth()->user()->hasPermissionTo('system.logs.manage'))
    <div class="modal fade" id="clearLogsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Clear Old System Logs</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ route('admin.system-logs.clear') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="days" class="form-label">Delete logs older than (days)</label>
                            <input type="number" class="form-control" id="days" name="days" value="90" min="1" max="365" required>
                            <div class="form-text">This action cannot be undone. Recommended: 90 days or older.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Clear Logs</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <div class="card logs-header-card mb-4">
        <div class="card-body">
            <div class="logs-metrics">
                <div class="logs-metric-card">
                    <div class="metric-label">Total Entries</div>
                    <div class="metric-value">{{ number_format($activities->total()) }}</div>
                    <small class="text-muted">Across all matching records</small>
                </div>
                <div class="logs-metric-card">
                    <div class="metric-label">Visible on Page</div>
                    <div class="metric-value">{{ number_format($activities->count()) }}</div>
                    <small class="text-muted">Page {{ $activities->currentPage() }} of {{ $activities->lastPage() }}</small>
                </div>
                <div class="logs-metric-card">
                    <div class="metric-label">Unique Users (Page)</div>
                    <div class="metric-value">{{ number_format($uniqueUsersCount) }}</div>
                    <small class="text-muted">Captured within current view</small>
                </div>
                <div class="logs-metric-card">
                    <div class="metric-label">Latest Entry</div>
                    <div class="metric-value">
                        {{ $latestActivity ? $latestActivity->created_at->timezone(config('app.timezone'))->format('M d') : '—' }}
                    </div>
                    <small class="text-muted">{{ $latestActivity ? $latestActivity->created_at->timezone(config('app.timezone'))->format('h:i A') : 'No records yet' }}</small>
                </div>
            </div>

            @if($appliedFilters->isNotEmpty())
                <div class="mt-3">
                    <div class="text-muted mb-2">Active filters</div>
                    <div class="filter-chips">
                        @foreach($appliedFilters as $filter)
                            <span class="filter-chip"><i class='bx bx-slider-alt me-1'></i>{{ $filter }}</span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="logs-nav-tabs mb-4">
        <a class="nav-link {{ $type === 'all' ? 'active' : '' }}" href="{{ route('admin.system-logs.index') }}">
            <i class='bx bx-list-ul me-1'></i> All Logs
        </a>
        <a class="nav-link {{ $type === 'accounts' ? 'active' : '' }}" href="{{ route('admin.system-logs.accounts') }}" title="User management: add, edit, update, delete accounts, roles, permissions, profiles">
            <i class='bx bx-user me-1'></i> Accounts
        </a>
        <a class="nav-link {{ $type === 'equipment' ? 'active' : '' }}" href="{{ route('admin.system-logs.equipment') }}" title="Equipment management: add, edit, update, delete equipment, maintenance, repairs, history sheets, QR scans">
            <i class='bx bx-cog me-1'></i> Equipment
        </a>
        <a class="nav-link {{ $type === 'login' ? 'active' : '' }}" href="{{ route('admin.system-logs.user-logins') }}" title="Authentication: login, logout, session lock/unlock">
            <i class='bx bx-log-in me-1'></i> User Logins
        </a>
    </div>

    @if($type !== 'all')
        <div class="card logs-category-info mb-4" data-type="{{ $type }}">
            <div class="card-body">
                <div class="category-activities">
                    <strong>Includes:</strong>
                    <div class="activity-tags mt-2">
                        @if($type === 'accounts')
                            <span class="activity-tag">User Creation</span>
                            <span class="activity-tag">Profile Updates</span>
                            <span class="activity-tag">Role Assignments</span>
                            <span class="activity-tag">Permission Changes</span>
                            <span class="activity-tag">Account Deletions</span>
                            <span class="activity-tag">Staff Management</span>
                            <span class="activity-tag">Technician Management</span>
                        @elseif($type === 'equipment')
                            <span class="activity-tag">Equipment Addition</span>
                            <span class="activity-tag">Equipment Updates</span>
                            <span class="activity-tag">Equipment Deletion</span>
                            <span class="activity-tag">Maintenance Logs</span>
                            <span class="activity-tag">Repair Records</span>
                            <span class="activity-tag">History Sheet Creation</span>
                            <span class="activity-tag">QR Code Scans</span>
                        @elseif($type === 'login')
                            <span class="activity-tag">User Login</span>
                            <span class="activity-tag">User Logout</span>
                            <span class="activity-tag">Session Lock</span>
                            <span class="activity-tag">Session Unlock</span>
                            <span class="activity-tag">Authentication Events</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif


    <div class="card logs-table-card">
        @if($activities->count() > 0)
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th scope="col">User</th>
                            <th scope="col">Action</th>
                            <th scope="col">Description</th>
                            <th scope="col">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activities as $activity)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="logs-user-info">
                                            <div class="fw-medium">{{ $activity->user->name ?? 'Unknown User' }}</div>
                                            <div class="text-muted small">{{ $activity->user->email ?? 'Email not available' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge">{{ $activity->action }}</span>
                                </td>
                                <td>
                                    <div class="text-body">{{ $activity->description ?? 'No description available' }}</div>
                                </td>
                                <td class="text-end">
                                    <div class="timestamp">{{ $activity->created_at->timezone(config('app.timezone'))->format('M d, Y') }}</div>
                                    <small>{{ $activity->created_at->timezone(config('app.timezone'))->format('h:i A') }}</small>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="showing">
                <div class="text-muted">Showing {{ $activities->firstItem() }} to {{ $activities->lastItem() }} of {{ $activities->total() }} entries</div>
                {{ $activities->links() }}
            </div>
        @else
            <div class="logs-empty">
                <i class='bx bx-history'></i>
                <h3 class="fw-semibold">No {{ $typeLabel }} Yet</h3>
                <p class="mb-0">We're not seeing any {{ strtolower($typeLabel) }} right now. As soon as activity happens, it will appear here automatically.</p>
            </div>
        @endif
    </div>
</div>
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const autoRefreshCheckbox = document.getElementById('auto-refresh');
    if (autoRefreshCheckbox) {
        autoRefreshCheckbox.addEventListener('change', function() {
            if (this.checked) {
                setInterval(() => window.location.reload(), 30000);
            }
        });
    }
});
</script>
@endpush
@endsection
