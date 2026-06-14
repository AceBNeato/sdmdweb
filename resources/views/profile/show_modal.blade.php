@php
    $user = $user ?? (auth('technician')->user() ?? auth('staff')->user() ?? auth()->user());
@endphp

<div class="container-fluid profile-modal">
    <div class="row g-3">
        <div class="col-md-4">
            <div class="text-center profile-avatar-wrapper">
                <img src="{{ $user->profile_photo_path ? asset('storage/' . $user->profile_photo_path) : asset('images/SDMDlogo.png') }}"
                     alt="Profile Picture"
                     class="profile-avatar profile-avatar-lg img-fluid"
                     onerror="this.onerror=null; this.src='{{ asset('images/SDMDlogo.png') }}'">
                <h5 class="mt-3 mb-1">{{ $user->first_name . ' ' . $user->last_name }}</h5>
                <div class="text-muted small">{{ $user->position ?? 'User' }}</div>
                <div class="mt-3">
                    <button type="button"
                            class="btn btn-primary open-edit-profile-modal"
                            data-url="{{ url((auth('staff')->check() ? 'staff' : (auth('technician')->check() ? 'technician' : 'admin')) . '/profile/edit?modal=1') }}">
                        <i class='bx bx-edit-alt me-1'></i>Edit Profile
                    </button>
                </div>
            </div>
        </div>
        <div class="col-md-8 px-md-4 px-2 py-2">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="profile-info-label">Email</div>
                    <div class="profile-info-value"><i class='bx bx-envelope'></i> {{ $user->email }}</div>
                </div>
                <div class="col-md-6">
                    <div class="profile-info-label">Phone</div>
                    <div class="profile-info-value"><i class='bx bx-phone'></i> {{ $user->phone ?? 'N/A' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="profile-info-label">Address</div>
                    <div class="profile-info-value"><i class='bx bx-map-pin'></i> {{ $user->address ?? 'N/A' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="profile-info-label">Employee ID</div>
                    <div class="profile-info-value"><i class='bx bx-id-card'></i> {{ $user->employee_id ?? 'N/A' }}</div>
                </div>
                @if(method_exists($user, 'office') && $user->office)
                <div class="col-md-6">
                    <div class="profile-info-label">Office</div>
                    <div class="profile-info-value"><i class='bx bx-building-house'></i> {{ optional($user->office)->name }}</div>
                </div>
                @elseif(method_exists($user, 'staff') && $user->staff && $user->staff->office)
                <div class="col-md-6">
                    <div class="profile-info-label">Office</div>
                    <div class="profile-info-value"><i class='bx bx-building-house'></i> {{ optional($user->staff->office)->name }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
