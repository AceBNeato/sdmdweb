<div id="equipment-table-container">
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
                    <tr data-serial="{{ $item->serial_number ?? '' }}" 
                        data-model="{{ $item->model_number ?? '' }}" 
                        data-type="{{ $item->equipmentType ? $item->equipmentType->name : '' }}" 
                        data-office="{{ $item->office ? $item->office->name : '' }}" 
                        data-status="{{ $item->status ?? '' }}">
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
                                @if($currentUser && $currentUser->hasPermissionTo('equipment.view'))
                                <button type="button" class="btn btn-sm btn-primary view-equipment-btn" 
                                        data-equipment-id="{{ $item->id }}" 
                                        data-url="{{ route($prefix . '.equipment.show', $item) }}"
                                        title="view">
                                    <i class='bx bx-show-alt'></i>
                                </button>
                                @endif
                                @if($currentUser && $currentUser->hasPermissionTo('equipment.edit') && Route::has($prefix . '.equipment.edit'))
                                <button type="button" class="btn btn-sm btn-primary edit-equipment-btn" 
                                        data-equipment-id="{{ $item->id }}" 
                                        data-url="{{ route($prefix . '.equipment.edit', $item) }}"
                                        title="edit">
                                    <i class='bx bx-edit'></i>
                                </button>
                                @endif
                                @if($currentUser && $currentUser->hasPermissionTo('history.create') && Route::has($prefix . '.equipment.history.create'))
                                <button type="button" class="btn btn-sm btn-primary history-equipment-btn" 
                                        data-equipment-id="{{ $item->id }}" 
                                        data-url="{{ route($prefix . '.equipment.history.create', $item) }}"
                                        title="Add History">
                                    <i class='bx bx-file'></i>
                                </button>
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
    <div class="pagination-section" hx-boost="true" hx-target="#equipment-table-container">
        <div class="pagination-info">
            Showing {{ $equipment->firstItem() }} to {{ $equipment->lastItem() }} of {{ $equipment->total() }} results
        </div>
        {{ $equipment->appends(request()->query())->links('pagination.admin') }}
    </div>
    @endif
</div>
