@extends('layouts.app')

@php
    $prefix = auth()->user()->is_admin ? 'admin' : (auth()->user()->role?->name === 'technician' ? 'technician' : 'staff');
@endphp

@section('page_title', 'Equipment History')
@section('page_description', 'View detailed equipment service history')

@push('styles')
    <link href="{{ asset('css/reports.css') }}" rel="stylesheet">
@endpush

@section('title', 'Equipment History - ' . ($equipment->model_number ?? 'SDMD'))

@section('content')
@if(!auth()->user()->hasPermissionTo('reports.generate'))
    @php abort(403) @endphp
@else

<div class="history-dashboard">
    <!-- Header Actions -->
    <div class="action-buttons">
        <div class="dashboard-actions">
            <a href="{{ route($prefix . '.reports.index') }}" class="btn btn-outline-secondary">
                <i class='bx bx-arrow-back'></i> Back to Reports
            </a>
        </div>

        <div class="dashboard-controls">
            <div class="export-controls">
                <a href="{{ route($prefix . '.reports.equipment.history.export', $equipment) }}" class="btn btn-primary" target="_blank">
                    <i class='bx bx-download'></i> Export Report
                </a>
            </div>
        </div>
    </div>


    <!-- History Content -->
    <div class="history-content">
        <!-- Filters and Search -->
        <div class="content-controls">
            <div class="search-section">
                <div class="search-input-wrapper">
                    <i class='bx bx-search'></i>
                    <input type="text" id="historySearch" placeholder="Search history records..." class="search-input">
                </div>
            </div>

            <div class="filter-section">
                <select id="actionFilter" class="filter-select">
                    <option value="">All Actions</option>
                    @foreach($equipment->history->unique('action_taken')->pluck('action_taken') as $action)
                        <option value="{{ $action }}">{{ $action }}</option>
                    @endforeach
                </select>

                <select id="technicianFilter" class="filter-select">
                    <option value="">All Technicians</option>
                    @foreach($equipment->history->unique('responsible_person')->pluck('responsible_person') as $tech)
                        <option value="{{ $tech }}">{{ $tech }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- History Records -->
        <div class="history-records" id="historyRecords">
            @if($equipment->history->count() > 0)
                @foreach($equipment->history->sortByDesc('created_at') as $index => $history)
                    <div class="history-record" data-action="{{ $history->action_taken }}" data-technician="{{ $history->responsible_person }}">
                        <div class="record-header">
                            <div class="record-primary">
                                <div class="record-action">
                                    <span class="action-badge">{{ $history->action_taken }}</span>
                                </div>
                                <div class="record-meta">
                                    <div class="record-date">
                                        <i class='bx bx-calendar'></i>
                                        {{ $history->created_at->format('M j, Y') }}
                                    </div>
                                    <div class="record-time">
                                        <i class='bx bx-time-five'></i>
                                        {{ $history->created_at->format('h:i A') }}
                                    </div>
                                </div>
                            </div>

                            @if($history->jo_number)
                            <div class="record-secondary">
                                <span class="jo-number">JO #{{ $history->jo_number }}</span>
                                @if(auth()->user()->is_admin || (auth()->guard('technician')->check() && $history->user_id == auth('technician')->user()->user_id))
                                    <a href="#" class="btn btn-sm btn-outline-primary edit-history-btn ms-2"
                                       data-history-id="{{ $history->id }}"
                                       data-url="{{ route($prefix . '.equipment.history.edit', [$equipment, $history]) }}"
                                       title="Edit History Entry">
                                        <i class='bx bx-edit'></i>
                                    </a>
                                @endif
                            </div>
                            @endif
                        </div>

                        @if($history->remarks)
                        <div class="record-content">
                            <div class="record-description">
                                {{ $history->remarks }}
                            </div>
                        </div>
                        @endif

                        <div class="record-footer">
                            <div class="record-user">
                                <i class='bx bx-user-circle'></i>
                                <span>{{ $history->responsible_person }}</span>
                                @if($history->user)
                                    <span class="user-role">({{ $history->user->role?->display_name ?? $history->user->role?->name ?? 'Staff' }})</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class='bx bx-time-five'></i>
                    </div>
                    <h3>No History Records</h3>
                    <p>This equipment doesn't have any service history records yet.</p>
                    <div class="empty-actions">
                        <a href="{{ route($prefix . '.equipment.show', $equipment) }}" class="btn btn-primary">
                            <i class='bx bx-plus'></i> Add First Record
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Search functionality
    const searchInput = document.getElementById('historySearch');
    const actionFilter = document.getElementById('actionFilter');
    const technicianFilter = document.getElementById('technicianFilter');
    const records = document.querySelectorAll('.history-record');

    function filterRecords() {
        const searchTerm = searchInput.value.toLowerCase();
        const actionValue = actionFilter.value.toLowerCase();
        const technicianValue = technicianFilter.value.toLowerCase();

        records.forEach(record => {
            const action = record.dataset.action.toLowerCase();
            const technician = record.dataset.technician.toLowerCase();
            const text = record.textContent.toLowerCase();

            const matchesSearch = searchTerm === '' || text.includes(searchTerm);
            const matchesAction = actionValue === '' || action.includes(actionValue);
            const matchesTechnician = technicianValue === '' || technician.includes(technicianValue);

            if (matchesSearch && matchesAction && matchesTechnician) {
                record.style.display = 'block';
            } else {
                record.style.display = 'none';
            }
        });
    }

    searchInput.addEventListener('input', filterRecords);
    actionFilter.addEventListener('change', filterRecords);
    technicianFilter.addEventListener('change', filterRecords);

    // Handle edit history button clicks
    document.querySelectorAll('.edit-history-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('data-url');

            // Create or get the edit modal
            let modal = document.getElementById('editHistoryModal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'editHistoryModal';
                modal.className = 'modal fade';
                modal.setAttribute('tabindex', '-1');
                modal.innerHTML = `
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit History Entry</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body" id="editHistoryContent">
                                <div class="text-center">
                                    <div class="spinner-border" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            }

            // Show loading state
            const content = document.getElementById('editHistoryContent');
            content.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            `;

            // Load content via AJAX
            fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
                }
            })
            .then(response => response.text())
            .then(html => {
                content.innerHTML = html;
            })
            .catch(error => {
                content.innerHTML = '<div class="alert alert-danger">Failed to load edit form. Please try again.</div>';
                console.error('Error loading edit form:', error);
            });

            // Show modal
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        });
    });
});
</script>
@endpush

@endif
@endsection
