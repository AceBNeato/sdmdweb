@extends('layouts.app')

@section('page_title', 'Reports')
@section('page_description', 'View and manage equipment service history records')

@push('styles')
    <link href="{{ asset('css/reports.css') }}" rel="stylesheet">
@endpush

@section('title', 'Equipment History - SDMD')

@section('content')
<div class="content">

@php
$prefix = auth()->user()->is_admin ? 'admin' : (auth()->user()->hasRole('technician') ? 'technician' : 'staff');
@endphp

@if(!auth()->user()->hasPermissionTo('reports.view'))
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

    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route($prefix . '.reports.index') }}" method="GET" class="filter-form">
                <div class="filter-group">
                    <label for="search">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class='bx bx-search'></i></span>
                        <input type="text" id="search" name="search" class="form-control"
                               placeholder="Search history records..."
                               value="{{ request('search') }}">
                    </div>
                </div>

                <!-- Date Range Filter -->
                <div class="filter-group">
                    <label for="date_from">History Date</label>
                    <input type="date" id="date_from" name="date_from"
                           class="form-control"
                           value="{{ request('date_from') }}">
                </div>

                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-filter-alt me-1'></i> Apply Filters
                    </button>
                    <a href="{{ route($prefix . '.reports.index') }}" class="btn btn-outline-secondary">
                        <i class='bx bx-reset me-1'></i> Reset
                    </a>
            </form>
        </div>
    </div>
    @if($equipmentHistory->count() > 0)
        <!-- Equipment History Table -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th scope="col">Equipment</th>
                        <th scope="col">Office</th>
                        <th scope="col">Last Updated</th>
                        <th scope="col">Entries</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($equipmentHistory->groupBy('equipment_id') as $equipmentId => $historyItems)
                        @php
                            $equipment = $historyItems->first()->equipment;
                            $latestEntry = $historyItems->sortByDesc('created_at')->first();
                        @endphp
                        <tr>
                            <td>
                                <div class="fw-bold text-primary">{{ $equipment->model_number }}</div>
                                <small class="text-muted">{{ $equipment->serial_number }}</small>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" title="{{ $equipment->office->name ?? 'N/A' }}">
                                    {{ $equipment->office->name ?? 'N/A' }}
                                </div>
                            </td>
                            <td>
                                <div class="text-nowrap">{{ $latestEntry->created_at->format('M d, Y') }}</div>
                                <small class="text-muted">{{ $latestEntry->created_at->format('H:i') }}</small>
                            </td>
                            <td>
                                <span class="badge bg-primary">{{ $historyItems->count() }} entries</span>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route($prefix . '.reports.history', $equipment->id) }}"
                                       class="btn btn-sm btn-primary"
                                       title="View History">
                                        <i class='bx bx-history'></i> View History
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $equipmentHistory->links() }}
        </div>
    @else
        <div class="empty-state">
            <i class='bx bx-history'></i>
            <h3>No History Records Found</h3>
            <p>There are no equipment history records available at the moment. Start by adding a new history entry.</p>
            <a href="{{ route($prefix . '.equipment.index') }}" class="btn btn-primary">
                 View Equipment List
            </a>
        </div>
    @endif
</div>
@endif

@push('scripts')
<script>
    // Filter functionality is now handled server-side via the form
</script>
@endpush
@endsection
