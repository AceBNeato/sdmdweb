@extends('layouts.app')

@section('title', 'Edit Category - System Management')
@section('page_title', 'Edit Category')
@section('page_description', 'Update category information')

@section('content')
<div class="content">
    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="{{ route('admin.settings.system.categories.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class='bx bx-arrow-back me-1'></i> Back to Categories
        </a>
    </div>

    <!-- Edit Category Form -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Category Information</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.system.categories.update', $category) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name', $category->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Category Statistics -->
                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6 class="card-title">Category Statistics</h6>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <div class="h4 mb-0">{{ $category->equipment_count }}</div>
                                        <small class="text-muted">Total Equipment</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="h4 mb-0">{{ $category->getAvailableEquipmentCountAttribute() }}</div>
                                        <small class="text-muted">Available</small>
                                    </div>
                                    <div class="col-4">
                                        <div class="h4 mb-0">{{ $category->equipment_count - $category->getAvailableEquipmentCountAttribute() }}</div>
                                        <small class="text-muted">In Use</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class='bx bx-save me-1'></i> Update Category
                            </button>
                            <a href="{{ route('admin.settings.system.categories.index') }}" class="btn btn-outline-secondary">
                                <i class='bx bx-x me-1'></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    input[type="color"] {
        height: 38px;
        padding: 0;
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
    }
</style>
@endpush
