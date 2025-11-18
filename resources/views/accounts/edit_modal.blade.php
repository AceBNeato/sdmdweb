@php
    $user = $user ?? (auth('technician')->user() ?? auth('staff')->user() ?? auth()->user());
@endphp

<div class="card form-card mb-0">
    <div class="card-header form-header border-0 pb-0">
        <div class="d-flex justify-content-between align-items-start gap-3">
            <div>
                <h5 class="mb-1">Edit User Information</h5>
                <p class="text-sm text-gray-600 mb-0">Update user details, password, and roles</p>
            </div>
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal" aria-label="Close">
                <i class='bx bx-x'></i> Close
            </button>
        </div>
    </div>

    <form action="{{ route('admin.accounts.update', $user) }}" method="POST" class="needs-validation" novalidate>
        @csrf
        @method('PUT')

        <div class="card-body form-body pt-3">
            <!-- Basic Information Section -->
            <div class="form-section">
                <h6 class="form-section-title">
                    <i class='bx bx-user'></i>
                    Basic Information
                </h6>

                <div class="form-row">
                    <div class="form-group required">
                        <label for="first_name">First Name</label>
                        <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                               id="first_name" name="first_name" value="{{ old('first_name', $user->first_name) }}" required
                               placeholder="Enter first name">
                        @error('first_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="form-group required">
                        <label for="last_name">Last Name</label>
                        <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                               id="last_name" name="last_name" value="{{ old('last_name', $user->last_name) }}" required
                               placeholder="Enter last name">
                        @error('last_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group required">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror"
                               id="email" name="email" value="{{ old('email', $user->email) }}" required
                               placeholder="Enter email address">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group required">
                        <label for="position">Position</label>
                        <input type="text" class="form-control @error('position') is-invalid @enderror"
                               id="position" name="position" value="{{ old('position', $user->position) }}" required
                               placeholder="Job title or position">
                        @error('position')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text">Enter the user's job title or position</small>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" class="form-control @error('phone') is-invalid @enderror"
                               id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
                               pattern="[0-9]*" placeholder="09123456789">
                        @error('phone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text">Mobile number (digits only, optional)</small>
                    </div>
                </div>
            </div>

            <!-- Account Security Section -->
            <div class="form-section">
                <h6 class="form-section-title">
                    <i class='bx bx-lock'></i>
                    Account Security
                </h6>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                               id="password" name="password"
                               placeholder="Enter new password (leave blank to keep current)">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text">Leave blank to keep current password</small>

                        <!-- Password Strength Indicator -->
                        <div class="password-strength" id="password-strength" style="display: none;">
                            <div class="strength-meter">
                                <div class="strength-bar" id="strength-bar"></div>
                            </div>
                            <div class="strength-text" id="strength-text">Password strength: </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password_confirmation">Confirm New Password</label>
                        <input type="password" class="form-control"
                               id="password_confirmation" name="password_confirmation"
                               placeholder="Confirm new password">
                    </div>
                </div>
            </div>

            <!-- Organization Section -->
            <div class="form-section">
                <h6 class="form-section-title">
                    <i class='bx bx-building'></i>
                    Organization
                </h6>

                <div class="form-row">
                    <div class="form-group required">
                        <label for="office_id">Office</label>
                        <select class="form-select @error('office_id') is-invalid @enderror"
                                id="office_id" name="office_id" required>
                            <option value="">Select Office</option>
                            @foreach(\App\Models\Campus::where('is_active', true)->orderBy('name')->get() as $campus)
                                <optgroup label="{{ $campus->name }} ({{ $campus->code }})">
                                    @foreach($campus->offices->where('is_active', true) as $office)
                                        <option value="{{ $office->id }}" {{ (old('office_id', $user->office_id) == $office->id) ? 'selected' : '' }}>
                                            {{ $office->name }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('office_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Direct Permissions Section -->
            <div class="form-section">
                <h6 class="form-section-title">
                    <i class='bx bx-key'></i>
                    Direct Permissions
                </h6>

                <div class="mb-4">
                    <div class="permissions-grid">
                        @foreach($allPermissions as $permission)
                            <div class="permission-card">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           name="direct_permissions[]"
                                           value="{{ $permission->id }}"
                                           id="permission_{{ $permission->id }}"
                                           {{ in_array($permission->id, $userPermissions ?? []) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="permission_{{ $permission->id }}">
                                        {{ $permission->display_name ?? $permission->name }}
                                    </label>
                                </div>
                                @if($permission->description)
                                    <small class="form-text">{{ $permission->description }}</small>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <small class="form-text text-muted">Grant specific permissions directly to this user. These work in addition to role permissions.</small>
                </div>
            </div>

            <!-- Roles Section (Only visible to superadmin) -->
            @if(auth()->user()->is_super_admin)
            <div class="form-section">
                <h6 class="form-section-title">
                    <i class='bx bx-shield'></i>
                    Roles & Permissions
                </h6>

                <div class="alert alert-info mt-3">
                    <i class='bx bx-info-circle'></i>
                    <small>Change the user's role. Each role comes with specific permissions and access levels.</small>
                </div>

                <div class="roles-grid">
                    @foreach($roles as $role)
                        <div class="role-card" onclick="selectRole({{ $role->id }})">
                            <div class="form-check">
                                <input class="form-check-input" type="radio"
                                       name="roles" value="{{ $role->id }}"
                                       id="role_{{ $role->id }}"
                                       {{ in_array($role->id, $userRoles) ? 'checked' : '' }}>
                                <label class="form-check-label" for="role_{{ $role->id }}">
                                    {{ ucfirst($role->display_name ?? $role->name) }}
                                </label>
                            </div>
                            <small class="text-muted d-block mt-1">
                                @if($role->name === 'admin')
                                    Full system access and management
                                @elseif($role->name === 'staff')
                                    Standard staff permissions
                                @elseif($role->name === 'technician')
                                    Equipment and maintenance access
                                @else
                                    {{ $role->name }} permissions
                                @endif
                            </small>
                        </div>
                    @endforeach
                </div>

                @error('roles')
                    <div class="text-danger mt-2" style="font-size: 0.875rem;">{{ $message }}</div>
                @enderror

            </div>
            @endif

        </div>

        <div class="form-actions d-flex justify-content-end gap-2 border-top p-3">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                <i class='bx bx-x'></i> Cancel
            </button>
            <button type="submit" class="btn btn-primary">
                <i class='bx bx-save'></i> Update User
            </button>
        </div>
    </form>
</div>
