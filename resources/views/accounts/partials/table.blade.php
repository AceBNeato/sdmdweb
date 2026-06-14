

<!-- Accounts Table -->
<div class="table-responsive">
    <table class="table table-hover">
        <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col">Email</th>
                <th scope="col">Role</th>
                <th scope="col">Campus</th>
                <th scope="col">Office</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $user)
                <tr data-name="{{ $user->first_name . ' ' . $user->last_name }}" 
                    data-email="{{ $user->email }}" 
                    data-role="{{ $user->role?->display_name ?? '' }}" 
                    data-campus="{{ $user->campus?->name ?? '' }}" 
                    data-office="{{ $user->office?->name ?? '' }}">
                    <td>
                        <div class="fw-bold text-primary">{{ $user->first_name . ' ' . $user->last_name }}</div>
                    </td>
                    <td>
                        <div class="fw-semibold">{{ $user->email }}</div>
                    </td>
                    <td>
                        <div class="text-truncate" style="max-width: 150px;" title="{{ $user->role?->display_name ?? 'No role' }}">
                            {{ $user->role?->display_name ?? 'No role' }}
                        </div>
                    </td>
                    <td>
                        <div class="text-truncate" style="max-width: 120px;" title="{{ $user->campus?->name ?? 'N/A' }}">
                            {{ $user->campus?->name ?? 'N/A' }}
                        </div>
                    </td>
                    <td>
                        <div class="text-truncate" style="max-width: 120px;" title="{{ $user->office?->name ?? 'N/A' }}">
                            {{ $user->office?->name ?? 'N/A' }}
                        </div>
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            @if(auth()->user()->hasPermissionTo('users.view'))
                            <button type="button" class="btn btn-sm btn-primary view-user-btn"
                                    data-user-id="{{ $user->id }}"
                                    data-url="{{ route('admin.accounts.show', $user) }}"
                                    title="view">
                                <i class='bx bx-show-alt'></i>
                            </button>
                            @endif
                            @if(auth()->user()->hasPermissionTo('users.edit'))
                            <button type="button"
                                    class="btn btn-sm btn-primary edit-user-btn"
                                    data-url="{{ route('admin.accounts.edit', $user) }}"
                                    title="Edit">
                                <i class='bx bx-edit'></i>
                            </button>
                            
                            <button type="button"
                                    class="btn btn-sm toggle-status-btn {{ $user->is_active ? 'active' : 'inactive' }}"
                                    data-user-id="{{ $user->id }}"
                                    data-url="{{ route('admin.accounts.toggle-status', $user) }}"
                                    title="{{ $user->is_active ? 'Deactivate Account' : 'Activate Account' }}">
                                <i class='bx {{ $user->is_active ? 'bx-user-x' : 'bx-user-check' }}'></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <div class="empty-equipment">
                            <i class='bx bx-user-x'></i>
                            <h5>No Users Found</h5>
                            <p>Get started by adding a new user</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($users->hasPages())
<div class="pagination-section" hx-boost="true" hx-target="#accounts-table-container">
    <div class="pagination-info">
        Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} results
    </div>
    {{ $users->appends(request()->query())->links('pagination.admin') }}
</div>
@endif
