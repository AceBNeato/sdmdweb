@extends('layouts.app')

@section('title', 'Offices Management')

@section('page_title', 'Offices Management')
@section('page_description', 'Manage all office locations and contact information')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/reports.css') }}">
@endpush

@section('content')
<div class="content">
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

@if(!auth()->user()->hasPermissionTo('settings.manage'))
    @php abort(403) @endphp
@else

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="action-buttons">
            @if(auth()->user()->hasPermissionTo('settings.manage'))
            <a href="{{ route('admin.offices.create') }}" class="btn btn-primary">
                <i class='bx bx-plus me-1'></i> Add New Office
            </a>
            @endif
        </div>
    </div>

    <!-- Search and Filter Card -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.offices.index') }}" method="GET" class="filter-form">
                <div class="filter-group">
                    <label for="search">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class='bx bx-search'></i></span>
                        <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="filter-group">
                    <label for="campus_id">Campus</label>
                    <select name="campus_id" class="form-select">
                        <option value="">All Campuses</option>
                        @foreach($campuses as $campus)
                            <option value="{{ $campus->id }}" {{ request('campus_id') == $campus->id ? 'selected' : '' }}>
                                {{ $campus->name }} ({{ $campus->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="filter-group">
                    <div class="btn-group">
                        <button type="submit" class="btn btn-primary">
                            <i class='bx bx-filter-alt me-1'></i> Apply Filters
                        </button>
                        <a href="{{ route('admin.offices.index') }}" class="btn btn-outline-secondary">
                            <i class='bx bx-reset me-1'></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Results Summary -->
    @if(request()->hasAny(['search', 'campus_id']))
        <div class="results-summary">
            <div class="alert alert-info">
                <i class='bx bx-info-circle me-2'></i>
                Found {{ $offices->total() }} office(s) matching your criteria.
                @if(request()->has('search'))
                    <div class="filter-info"><strong>Search:</strong> {{ request('search') }}</div>
                @endif
                @if(request()->has('campus_id') && request('campus_id') !== '')
                    <div class="filter-info"><strong>Campus:</strong> {{ $campuses->firstWhere('id', request('campus_id'))?->name ?? 'Unknown Campus' }} ({{ $campuses->firstWhere('id', request('campus_id'))?->code ?? '' }})</div>
                @endif
            </div>
        </div>
    @endif

    <!-- Offices Table -->
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col" class="d-none d-lg-table-cell">Campus</th>
                    <th scope="col">Contact</th>
                    <th scope="col" class="d-none d-md-table-cell">Email</th>
                    <th scope="col" class="d-none d-xl-table-cell">Address</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($offices as $office)
                    <tr>
                        <td>
                            <div class="fw-bold text-primary">{{ $office->name }}</div>
                            <div class="small text-muted d-lg-none">{{ $office->campus?->name ?? 'No campus' }}</div>
                        </td>
                        <td class="d-none d-lg-table-cell">
                            <div class="text-truncate" style="max-width: 120px;" title="{{ $office->campus?->name ?? 'N/A' }}">
                                {{ $office->campus?->name ?? 'N/A' }}
                            </div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $office->contact_number ?? 'N/A' }}</div>
                        </td>
                        <td class="d-none d-md-table-cell">
                            <div class="text-truncate" style="max-width: 150px;" title="{{ $office->email ?? 'N/A' }}">
                                @if($office->email)
                                    <a href="mailto:{{ $office->email }}" class="text-decoration-none">{{ $office->email }}</a>
                                @else
                                    N/A
                                @endif
                            </div>
                        </td>
                        <td class="d-none d-xl-table-cell">
                            <div class="text-truncate" style="max-width: 150px;" title="{{ $office->address ?? 'N/A' }}">
                                {{ $office->address ?? 'N/A' }}
                            </div>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                @if(auth()->user()->hasPermissionTo('settings.manage')) 
                                <a href="{{ route('admin.offices.show', $office) }}"
                                   class="btn btn-sm btn-primary"
                                   title="view">
                                    <i class='bx bx-show-alt'></i>
                                </a>
                                @endif
                                @if(auth()->user()->hasPermissionTo('settings.manage'))
                                <a href="{{ route('admin.offices.edit', $office) }}"
                                   class="btn btn-sm btn-outline-secondary"
                                   title="edit">
                                    <i class='bx bx-edit'></i>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="empty-equipment">
                                <i class='bx bx-building-house'></i>
                                <h5>No Offices Found</h5>
                                <p>Get started by adding your first office location.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($offices->hasPages())
    <div class="pagination-section">
        <div class="pagination-info">
            Showing {{ $offices->firstItem() }} to {{ $offices->lastItem() }} of {{ $offices->total() }} results
        </div>
        {{ $offices->appends(request()->query())->links('pagination.admin') }}
    </div>
    @endif
    @endif
</div>
@endsection
