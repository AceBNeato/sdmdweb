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
        request('type') && request('type') !== 'all' ? 'Action: ' . request('type') : null,
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

    <!-- Filters and Export Section -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" type="{{ $currentRoute }}" class="row g-3">
                <div class="col-12 col-sm-6 col-md-3">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" class="form-control" id="search" name="search" value="{{ request('search') }}" placeholder="Search types, descriptions, or users...">
                </div>
                <div class="col-12 col-sm-6 col-md-2">
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
                <div class="col-12 col-sm-6 col-md-2">
                    <label for="type" class="form-label">Action</label>
                    <select class="form-select" id="type" name="type">
                        <option value="all">All Actions</option>
                        @foreach($actions as $actionItem)
                            <option value="{{ $actionItem }}" {{ request('type') == $actionItem ? 'selected' : '' }}>
                                {{ $actionItem }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label for="date_from" class="form-label">From Date</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label for="date_to" class="form-label">To Date</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
                </div>
                <div class="col-12 col-sm-6 col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class='bx bx-search'></i>
                    </button>
                </div>
            </form>

            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center mt-3 gap-2">
                <div class="w-100 w-sm-auto">
                    @if(request()->hasAny(['search', 'user_id', 'type', 'date_from', 'date_to']))
                        <a href="{{ $currentRoute }}" class="btn btn-outline-secondary btn-sm w-100 w-sm-auto">
                            <i class='bx bx-x me-1'></i> Clear Filters
                        </a>
                    @endif
                </div>
                <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-sm-auto">
                    <a href="{{ route('admin.system-logs.export', request()->query()) }}" class="btn-export-csv">
                        <i class='bx bx-download me-1'></i> Export CSV
                    </a>
                    @if(auth()->user()->hasPermissionTo('system.logs.manage'))
                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#clearLogsModal">
                            <i class='bx bx-trash me-1'></i> Clear Old Logs
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
                <form method="POST" type="{{ route('admin.system-logs.clear') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="days" class="form-label">Delete logs older than (days)</label>
                            <input type="number" class="form-control" id="days" name="days" value="90" min="1" max="365" required>
                            <div class="form-text">This type cannot be undone. Recommended: 90 days or older.</div>
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
                <table id="logs-table" class="table align-middle">
                    <thead>
                        <tr>
                            <th scope="col">User</th>
                            <th scope="col">Action</th>
                            <th scope="col">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($activities as $activity)
                            <tr class="logs-row" data-activity-id="{{ $activity->id }}" data-user-name="{{ $activity->user->name ?? 'Unknown User' }}" data-user-email="{{ $activity->user->email ?? 'Email not available' }}" data-type="{{ $activity->type }}" data-description="{{ $activity->description ?? 'No description available' }}" data-timestamp="{{ $activity->created_at->timezone(config('app.timezone'))->format('M d, Y h:i A') }}">
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="logs-user-info">
                                            <div class="fw-medium">{{ $activity->user->name ?? 'Unknown User' }}</div>
                                            <div class="text-muted small d-none d-md-block">{{ $activity->user->email ?? 'Email not available' }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge">{{ $activity->type }}</span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary view-log-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#logModal"
                                            data-user-name="{{ $activity->user->name ?? 'Unknown User' }}"
                                            data-user-email="{{ $activity->user->email ?? 'Email not available' }}"
                                            data-type="{{ $activity->type }}"
                                            data-description="{{ $activity->description ?? 'No description available' }}"
                                            data-timestamp="{{ $activity->created_at->timezone(config('app.timezone'))->format('M d, Y h:i A') }}">
                                        <i class='bx bx-eye me-1'></i>View
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($activities->hasPages())
                <div class="pagination-section">
                    <div class="pagination-info">
                        Showing {{ $activities->firstItem() }} to {{ $activities->lastItem() }} of {{ $activities->total() }} results
                    </div>
                    {{ $activities->appends(request()->query())->links('pagination.admin') }}
                </div>
            @endif

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

@endsection

@push('modals')
<div class="modal fade" id="logModal" tabindex="-1" aria-labelledby="logModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content modal-content-modern">
            <div class="modal-header modal-header-modern">
                <div class="d-flex align-items-center gap-3">
                    <div class="modal-icon">
                        <i class='bx bx-file-find'></i>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0" id="logModalLabel">Log Details</h5>
                        <small class="text-muted">Activity information</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-modern" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body modal-body-modern">
                <div class="log-details-grid">
                    <div class="detail-card">
                        <div class="detail-icon">
                            <i class='bx bx-user'></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">User</div>
                            <div class="detail-value" id="modal-user-name">-</div>
                        </div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-icon">
                            <i class='bx bx-envelope'></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Email</div>
                            <div class="detail-value" id="modal-user-email">-</div>
                        </div>
                    </div>
                    
                    <div class="detail-card detail-card-full">
                        <div class="detail-icon">
                            <i class='bx bx-text'></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Description</div>
                            <div class="detail-value" id="modal-description">-</div>
                        </div>
                    </div>
                    
                    <div class="detail-card">
                        <div class="detail-icon">
                            <i class='bx bx-time'></i>
                        </div>
                        <div class="detail-content">
                            <div class="detail-label">Timestamp</div>
                            <div class="detail-value" id="modal-timestamp">-</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer modal-footer-modern">
                <button type="button" class="btn btn-secondary btn-modern" data-bs-dismiss="modal">
                    <i class='bx bx-x me-2'></i>Close
                </button>
            </div>
        </div>
    </div>
</div>
@endpush

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

    // Handle View button clicks for modal
    document.addEventListener('click', function(event) {
        const viewBtn = event.target.closest('.view-log-btn');
        if (viewBtn) {
            event.preventDefault();

            // Get data from data attributes
            const userName = viewBtn.dataset.userName || 'Unknown User';
            const userEmail = viewBtn.dataset.userEmail || 'Email not available';
            const description = viewBtn.dataset.description || 'No description available';
            const timestamp = viewBtn.dataset.timestamp || '';

            // Populate modal
            document.getElementById('modal-user-name').textContent = userName;
            document.getElementById('modal-user-email').textContent = userEmail;
            document.getElementById('modal-description').textContent = description;
            document.getElementById('modal-timestamp').textContent = timestamp;

            // Ensure clean modal state
            const modalElement = document.getElementById('logModal');
            const existingModal = bootstrap.Modal.getInstance(modalElement);
            if (existingModal) {
                existingModal.dispose();
            }

            // Create new modal instance
            const modal = new bootstrap.Modal(modalElement, {
                backdrop: true,
                keyboard: true,
                focus: true
            });

            // Add event listener to clean up when modal is hidden
            modalElement.addEventListener('hidden.bs.modal', function () {
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
            });

            modal.show();
        }
    });
});
</script>
@endpush
