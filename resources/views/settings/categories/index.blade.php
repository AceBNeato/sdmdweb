@extends('layouts.app')

@section('title', 'Categories - System Management')
@section('page_title', 'Category Management')
@section('page_description', 'Manage equipment categories')

@section('content')
<div class="content">
    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="{{ route('admin.settings.index') }}" class="btn btn-outline-secondary btn-sm">
            <i class='bx bx-arrow-back me-1'></i> Back to Settings
        </a>
        @if(auth()->user()->hasPermissionTo('settings.manage'))
        <a href="{{ route('admin.settings.categories.create') }}" class="btn btn-primary btn-sm">
            <i class='bx bx-plus me-1'></i> Add Category
        </a>
        @endif
    </div>

    <!-- Search and Filter Card -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('admin.settings.categories.index') }}" method="GET" class="filter-form">
                <div class="filter-group">
                    <label for="search">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class='bx bx-search'></i></span>
                        <input type="text" id="search" name="search" class="form-control"
                               placeholder="Search categories..." value="{{ request('search') }}">
                    </div>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-filter-alt me-1'></i> Apply Filters
                    </button>
                    <a href="{{ route('admin.settings.categories.index') }}" class="btn btn-outline-secondary">
                        <i class='bx bx-reset me-1'></i> Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Categories Table -->
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col">Equipment Count</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($categories as $category)
                    <tr>
                        <td>
                            <div class="fw-medium">{{ $category->name }}</div>
                        </td>
                        <td>
                            <span class="badge bg-info">{{ $category->equipment_count }}</span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                @if(auth()->user()->hasPermissionTo('settings.manage'))
                                <a href="{{ route('admin.settings.categories.edit', $category) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class='bx bx-edit'></i>
                                </a>
                                @if(!$category->hasEquipment())
                                <form action="{{ route('admin.settings.categories.destroy', $category) }}" method="POST" class="d-inline delete-form">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="btn btn-sm btn-outline-secondary delete-btn">
                                        <i class='bx bx-trash'></i>
                                    </button>
                                </form>
                                @endif
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center py-5">
                            <div class="empty-equipment">
                                <i class='bx bx-category-x'></i>
                                <h5>No Categories Found</h5>
                                <p>{{ request()->hasAny(['search']) ? 'Try adjusting your search criteria.' : 'Get started by adding your first category.' }}</p>
                                @if(auth()->user()->hasPermissionTo('settings.manage') && !request()->hasAny(['search']))
                                <a href="{{ route('admin.settings.categories.create') }}" class="btn btn-primary">
                                    <i class='bx bx-plus me-1'></i> Add Category
                                </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($categories->hasPages())
    <div class="pagination-section">
        <div class="pagination-info">
            Showing {{ $categories->firstItem() }} to {{ $categories->lastItem() }} of {{ $categories->total() }} results
        </div>
        {{ $categories->appends(request()->query())->links('pagination.admin') }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
// Delete confirmation
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('.delete-form');
            const categoryName = form.closest('tr').querySelector('.fw-medium').textContent;

            if (confirm(`Are you sure you want to delete "${categoryName}"? This action cannot be undone.`)) {
                form.submit();
            }
        });
    });
});
</script>
@endpush
