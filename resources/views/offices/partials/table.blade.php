<div id="offices-table-container">
    <div class="table-responsive">
        <table class="table table-hover table-sm">
            <thead>
                <tr>
                    <th scope="col">Name</th>
                    <th scope="col" class="d-none d-lg-table-cell">Campus</th>
                    <th scope="col">Contact</th>
                    <th scope="col" class="d-none d-md-table-cell">Email</th>
                    <th scope="col" class="d-none d-xl-table-cell">Location</th>
                    <th scope="col">Status</th>
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
                            <div class="text-truncate" style="max-width: 150px;" title="{{ $office->location ?? 'N/A' }}">
                                {{ $office->location ?? 'N/A' }}
                            </div>
                        </td>
                        <td>
                            <span class="badge status-{{ $office->is_active ? 'active' : 'inactive' }} fs-6 px-2 py-1">
                                {{ $office->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm" role="group">
                                @if($currentUser && $currentUser->hasPermissionTo('offices.view')) 
                                <button type="button" 
                                        class="btn btn-sm btn-primary office-view-btn"
                                        data-office-id="{{ $office->id }}"
                                        data-url="{{ route($prefix . '.offices.show', $office) }}"
                                        title="view">
                                    <i class='bx bx-show-alt'></i>
                                </button>
                                @endif
                                @if($currentUser && $currentUser->hasPermissionTo('offices.edit'))
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary office-edit-btn"
                                        data-office-id="{{ $office->id }}"
                                        data-url="{{ route($prefix . '.offices.edit', $office) }}"
                                        title="edit">
                                    <i class='bx bx-edit'></i>
                                </button>
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
    <div class="pagination-section" hx-boost="true" hx-target="#offices-table-container">
        <div class="pagination-info">
            Showing {{ $offices->firstItem() }} to {{ $offices->lastItem() }} of {{ $offices->total() }} results
        </div>
        {{ $offices->appends(request()->query())->links('pagination.admin') }}
    </div>
    @endif
</div>
