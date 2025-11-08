@extends('layouts.app')

@push('styles')
  <link href="{{ asset('css/equipment.css') }}" rel="stylesheet">
@endpush

@push('scripts')
    <script src="{{ asset('js/equipment.js') }}"></script>
    <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
@endpush
@section('title', 'Equipment - SDMD')


@section('page_title', 'Equipment Management')
@section('page_description', 'Manage equipments and their information')



@section('content')
<div class="content">

@php
$prefix = auth()->user()->is_admin ? 'admin' : (auth()->user()->hasRole('technician') ? 'technician' : 'staff');
@endphp

@if(!auth()->user()->hasPermissionTo('equipment.view'))
    @php abort(403) @endphp
@else


    <div class="card mb-4">
        <div class="action-buttons">
            @if(auth()->user()->hasPermissionTo('equipment.create'))
            <a href="{{ route($prefix . '.equipment.create') }}" class="btn btn-primary">
                <i class='bx bx-plus me-1'></i> Add New Equipment
            </a>
            @endif
            @if(auth()->user()->hasPermissionTo('equipment.view'))
            <a href="{{ route($prefix . '.equipment.scan') }}" class="btn btn-outline-secondary">
                <i class='bx bx-qr-scan me-1'></i> Scan QR Code
            </a>
            @endif
            </div>
        <div class="card-body">
            <form action="{{ route($prefix . '.equipment.index') }}" method="GET" class="filter-form">
                <!-- Search Field -->
                <div class="filter-group">
                    <label for="search">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class='bx bx-search'></i></span>
                        <input type="text" id="search" name="search" class="form-control"
                               placeholder="Model, serial, or description..."
                               value="{{ request('search') }}">
                    </div>
                </div>

                <!-- Status Filter -->
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" class="form-select">
                        <option value="all" {{ request('status') == 'all' || !request('status') ? 'selected' : '' }}>All Statuses</option>
                        <option value="serviceable" {{ request('status') == 'serviceable' ? 'selected' : '' }}>Serviceable</option>
                        <option value="for_repair" {{ request('status') == 'for_repair' ? 'selected' : '' }}>For Repair</option>
                        <option value="defective" {{ request('status') == 'defective' ? 'selected' : '' }}>Defective</option>
                    </select>
                </div>

                <!-- Equipment Type Filter -->
                <div class="filter-group">
                    <label for="equipment_type">Equipment Type</label>
                    <select id="equipment_type" name="equipment_type" class="form-select">
                        <option value="all" {{ request('equipment_type') == 'all' || !request('equipment_type') ? 'selected' : '' }}>All Types</option>
                        @foreach($equipmentTypes as $key => $label)
                            <option value="{{ $key }}" {{ request('equipment_type') == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Office Filter -->
                <div class="filter-group">
                    <label for="office_id">Office</label>
                    <select name="office_id" id="office_id" class="form-select">
                        <option value="all" {{ request('office_id') == 'all' || !request('office_id') ? 'selected' : '' }}>All Offices</option>
                        @foreach($campuses as $campus)
                            <optgroup label="{{ $campus->name }} ({{ $campus->code }})">
                                @foreach($campus->offices->where('is_active', true) as $office)
                                    <option value="{{ $office->id }}" {{ request('office_id') == $office->id ? 'selected' : '' }}>
                                        {{ $office->name }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <!-- Category Filter -->
                <div class="filter-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id" class="form-select">
                        <option value="all" {{ request('category_id') == 'all' || !request('category_id') ? 'selected' : '' }}>All Categories</option>
                        @foreach($categories as $id => $name)
                            <option value="{{ $id }}" {{ request('category_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>


                <!-- Action Buttons -->
                <div class="filter-group filter-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-filter-alt me-1'></i> Apply Filters
                    </button>
                    <a href="{{ route($prefix . '.equipment.index') }}" class="btn btn-outline-secondary">
                        <i class='bx bx-reset me-1'></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>


    <!-- Equipment Table -->
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th scope="col">Serial</th>
                    <th scope="col">Model</th>
                    <th scope="col">Type</th>
                    <th scope="col">Office</th>
                    <th scope="col">Status</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($equipment as $item)
                    <tr>
                        <td>
                            <div class="fw-bold text-primary">{{ $item->serial_number ?? 'N/A' }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold">{{ $item->model_number ?? 'N/A' }}</div>
                        </td>
                        <td>
                            <div class="text-truncate" style="max-width: 120px;" title="{{ $item->equipmentType ? $item->equipmentType->name : 'N/A' }}">
                                {{ $item->equipmentType ? $item->equipmentType->name : 'N/A' }}
                            </div>
                        </td>
                        <td>
                            <div class="text-truncate" title="{{ $item->office ? $item->office->name : 'N/A' }}">
                                {{ $item->office ? $item->office->name : 'N/A' }}
                            </div>
                        </td>
                        <td>
                            <span class="badge status-{{ str_replace(' ', '_', strtolower($item->status ?? 'available')) }} fs-6 px-2 py-1">
                                {{ ucfirst(str_replace('_', ' ', $item->status ?? 'available')) }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                @if(auth()->user()->hasPermissionTo('equipment.view'))
                                <a href="{{ route($prefix . '.equipment.show', $item) }}" 
                                   class="btn btn-sm btn-primary" 
                                   title="view">
                                    <i class='bx bx-show-alt'></i>
                                </a>
                                @endif
                                @if(auth()->user()->hasPermissionTo('equipment.edit'))
                                <a href="{{ route($prefix . '.equipment.edit', $item) }}" 
                                   class="btn btn-sm btn-outline-secondary" 
                                   title="edit">
                                    <i class='bx bx-edit'></i>
                                </a>
                                @endif
                                @if(auth()->user()->hasPermissionTo('reports.generate'))
                                <a href="{{ route($prefix . '.reports.equipment.history.view', $item) }}" 
                                   class="btn btn-outline-secondary" 
                                   title="History Sheet">
                                    <i class='bx bx-file'></i>
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="empty-equipment">
                                <i class='bx bx-cube'></i>
                                <h5>No Equipment Found</h5>
                                <p>Contact your administrator to add equipment to the inventory.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($equipment->hasPages())
    <div class="pagination-section">
        <div class="pagination-info">
            Showing {{ $equipment->firstItem() }} to {{ $equipment->lastItem() }} of {{ $equipment->total() }} results
        </div>
        {{ $equipment->appends(request()->query())->links('pagination.admin') }}
    </div>
    @endif

    @endif

@endsection
