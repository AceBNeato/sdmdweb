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


    <div class="card mb-4">
        <div class="card-body">
            <form action="#" method="GET" class="filter-form">
                <div class="filter-group">
                    <label for="historySearch">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class='bx bx-search'></i></span>
                        <input type="text" id="historySearch" name="search" class="form-control"
                               placeholder="Search history records..."
                               value="{{ request('search') }}">
                    </div>
                </div>

                <div class="filter-group">
                    <label for="actionFilter">Action</label>
                    <select id="actionFilter" name="action" class="form-select">
                        <option value="">All Actions</option>
                        @foreach($equipment->history->unique('action_taken')->pluck('action_taken') as $action)
                            <option value="{{ $action }}">{{ $action }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <label for="technicianFilter">Technician</label>
                    <select id="technicianFilter" name="technician" class="form-select">
                        <option value="">All Technicians</option>
                        @foreach($equipment->history->unique('responsible_person')->pluck('responsible_person') as $tech)
                            <option value="{{ $tech }}">{{ $tech }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class='bx bx-filter-alt me-1'></i> Apply Filters
                </button>
                <a href="#" class="btn btn-outline-secondary">
                    <i class='bx bx-reset me-1'></i> Reset
                </a>
            </form>
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
                                @if(auth()->user()->hasPermissionTo('history.edit'))
                                    <a href="#" class="btn btn-sm btn-outline-secondary edit-history-btn ms-2"
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
