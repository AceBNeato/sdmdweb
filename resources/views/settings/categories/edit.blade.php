@extends('layouts.app')

@section('title', 'Edit Category - System Management')
@section('page_title', 'Edit Category')
@section('page_description', 'Update category information')

@section('content')
<div class="content">
    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="{{ route('admin.settings.categories.index') }}" class="btn btn-outline-secondary btn-sm">
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
                    <form action="{{ route('admin.settings.categories.update', $category) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name <span class="text-danger">*required</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name', $category->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class='bx bx-save me-1'></i> Update Category
                            </button>
                            <a href="{{ route('admin.settings.categories.index') }}" class="btn btn-outline-secondary">
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
