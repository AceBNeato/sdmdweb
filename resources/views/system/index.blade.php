@extends('layouts.app')

@section('title', 'System Management - Admin')
@section('page_title', 'System Management')
@section('page_description', 'Manage categories and equipment types')

@section('content')
<div class="content">
    <div class="row">
        <!-- System Overview Cards -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-icon">
                        <i class="bx bx-category text-primary"></i>
                    </div>
                    <div class="stats-content">
                        <h3>{{ \App\Models\Category::count() }}</h3>
                        <p class="text-muted mb-0">Total Categories</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-icon">
                        <i class="bx bx-cog text-success"></i>
                    </div>
                    <div class="stats-content">
                        <h3>{{ \App\Models\EquipmentType::count() }}</h3>
                        <p class="text-muted mb-0">Equipment Types</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-icon">
                        <i class="bx bx-package text-warning"></i>
                    </div>
                    <div class="stats-content">
                        <h3>{{ \App\Models\Equipment::count() }}</h3>
                        <p class="text-muted mb-0">Total Equipment</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card stats-card">
                <div class="card-body">
                    <div class="stats-icon">
                        <i class="bx bx-check-circle text-info"></i>
                    </div>
                    <div class="stats-content">
                        <h3>{{ \App\Models\Equipment::where('status', 'available')->count() }}</h3>
                        <p class="text-muted mb-0">Available Equipment</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6 col-lg-3">
                            <a href="{{ route('admin.system.categories.index') }}" class="btn btn-outline-primary btn-lg w-100">
                                <i class="bx bx-category me-2"></i>
                                Manage Categories
                            </a>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <a href="{{ route('admin.system.equipment-types.index') }}" class="btn btn-outline-success btn-lg w-100">
                                <i class="bx bx-cog me-2"></i>
                                Manage Equipment Types
                            </a>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <a href="{{ route('admin.equipment.index') }}" class="btn btn-outline-warning btn-lg w-100">
                                <i class="bx bx-package me-2"></i>
                                View All Equipment
                            </a>
                        </div>
                        <div class="col-md-6 col-lg-3">
                            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-info btn-lg w-100">
                                <i class="bx bx-bar-chart me-2"></i>
                                View Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Recent System Activity</h5>
                </div>
                <div class="card-body">
                    @php
                        $recentCategories = \App\Models\Category::latest()->take(3)->get();
                        $recentEquipmentTypes = \App\Models\EquipmentType::latest()->take(3)->get();
                    @endphp

                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="bx bx-category me-2"></i>
                                Recent Categories
                            </h6>
                            @if($recentCategories->count() > 0)
                                @foreach($recentCategories as $category)
                                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                        <div>
                                            <strong>{{ $category->name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $category->equipment_count }} equipment items</small>
                                        </div>
                                        <span class="badge {{ $category->is_active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $category->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-muted mb-0">No categories found</p>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <h6 class="text-success mb-3">
                                <i class="bx bx-cog me-2"></i>
                                Recent Equipment Types
                            </h6>
                            @if($recentEquipmentTypes->count() > 0)
                                @foreach($recentEquipmentTypes as $type)
                                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                        <div>
                                            <strong>{{ $type->name }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $type->equipment_count }} equipment items</small>
                                        </div>
                                        <span class="badge {{ $type->is_active ? 'bg-success' : 'bg-secondary' }}">
                                            {{ $type->is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                    </div>
                                @endforeach
                            @else
                                <p class="text-muted mb-0">No equipment types found</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
