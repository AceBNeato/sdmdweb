@php
    $user = $user ?? (auth('technician')->user() ?? auth('staff')->user() ?? auth()->user());
@endphp

<div class="card user-info-card mb-0">
    <div class="card-header border-0 pb-0">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
                <h4 class="mb-1">User Information</h4>
                <p class="text-sm text-gray-600 mb-0">View user details, roles, and permissions</p>
            </div>
            @if(auth()->user()->hasPermissionTo('users.edit'))
                <button type="button"
                        class="btn btn-outline-secondary btn-sm edit-user-btn"
                        data-user-id="{{ $user->id }}"
                        data-url="{{ route('admin.accounts.edit', ['user' => $user, 'modal' => 1]) }}"
                        title="Edit">
                    <i class='bx bx-edit'></i> Edit
                </button>
            @endif
        </div>
    </div>

    <div class="card-body pt-3">
        <div class="roles-section mb-4">
            <h6 class="info-label">
                <i class='bx bx-shield'></i>
                Assigned Roles
            </h6>
            @if($user->role)
                <div class="info-value d-flex flex-wrap gap-2">
                    <span class="role-badge">
                        <i class='bx bx-shield-check'></i> {{ $user->role->display_name ?? $user->role->name }}
                    </span>
                </div>
            @else
                <div class="info-value text-muted">No roles assigned</div>
            @endif
        </div>

        <div class="info-grid">
            <!-- Basic Information Section -->
            <div class="info-section">
                <h6 class="info-label">
                    <i class='bx bx-user'></i>
                    Basic Information
                </h6>

                <div class="info-meta">Full Name</div>
                <div class="info-value">{{ $user->first_name . ' ' . $user->last_name }}</div>

                <div class="info-meta">Employee ID</div>
                <div class="info-value">{{ $user->employee_id ?? 'N/A' }}</div>
            </div>

            <!-- Contact Information Section -->
            <div class="info-section">
                <h6 class="info-label">
                    <i class='bx bx-envelope'></i>
                    Contact Information
                </h6>

                <div class="info-meta">Email Address</div>
                <div class="info-value">{{ $user->email }}</div>

                @if($user->phone)
                    <div class="info-meta">Phone Number</div>
                    <div class="info-value mt-2">{{ $user->phone }}</div>
                @endif
            </div>

            <!-- Staff Information Section -->
            <div class="info-section">
                <h6 class="info-label">
                    <i class='bx bx-briefcase'></i>
                    Staff Information
                </h6>
                <div class="info-meta">Position</div>
                <div class="info-value">{{ $user->position ?? 'Not specified' }}</div>
            </div>

            <!-- Campus Assignment Section -->
            <div class="info-section">
                <h6 class="info-label">
                    <i class='bx bx-map'></i>
                    Campus Assignment
                </h6>
                @if($user->campus)
                    <div class="info-meta">Campus</div>
                    <div class="info-value">{{ $user->campus->name }} Campus</div>
                @else
                    <div class="info-meta">No campus assignment</div>
                    <div class="info-value text-muted">Not assigned</div>
                @endif
            </div>

            <!-- Office Assignment Section -->
            <div class="info-section">
                <h6 class="info-label">
                    <i class='bx bx-building'></i>
                    Office Assignment
                </h6>
                @if($user->office)
                    <div class="info-meta">{{ $user->office->address ?? 'No address specified' }}</div>
                    <div class="info-value">{{ $user->office->name }}</div>
                @else
                    <div class="info-meta">No office assignment</div>
                    <div class="info-value text-muted">Not assigned</div>
                @endif
            </div>

            <!-- Account Status Section -->
            <div class="info-section">
                <h6 class="info-label">
                    <i class='bx bx-shield-check'></i>
                    Account Status
                </h6>

                <div class="info-meta">Current Status</div>
                <div class="info-value">
                    @if($user->is_active)
                        <span class="status-badge badge-success">
                            <i class='bx bx-check-circle'></i> Active
                        </span>
                    @else
                        <span class="status-badge badge-danger">
                            <i class='bx bx-x-circle'></i> Inactive
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
