@php
    $user = $user ?? (auth('technician')->user() ?? auth('staff')->user() ?? auth()->user());
@endphp

<div class="container-fluid profile-modal">
    <div class="row g-3">
        <div class="col-md-4">
            <div class="text-center profile-avatar-wrapper">
                <img src="{{ $user->profile_photo ? asset('storage/' . $user->profile_photo) : asset('images/SDMDlogo.png') }}"
                     alt="Profile Picture"
                     class="profile-avatar profile-avatar-lg img-fluid"
                     onerror="this.onerror=null; this.src='{{ asset('images/SDMDlogo.png') }}'">
                <h5 class="mt-3 mb-1">{{ $user->name }}</h5>
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
        <div class="col-md-8">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="small text-muted">Email</div>
                    <div class="fw-semibold">{{ $user->email }}</div>
                </div>
                <div class="col-md-6">
                    <div class="small text-muted">Phone</div>
                    <div class="fw-semibold">{{ $user->phone ?? 'N/A' }}</div>
                </div>
                <div class="col-md-12">
                    <div class="small text-muted">Address</div>
                    <div class="fw-semibold">{{ $user->address ?? 'N/A' }}</div>
                </div>
                <div class="col-md-6">
                    <div class="small text-muted">Employee ID</div>
                    <div class="fw-semibold">{{ $user->employee_id ?? 'N/A' }}</div>
                </div>
                @if(method_exists($user, 'office') && $user->office)
                <div class="col-md-6">
                    <div class="small text-muted">Office</div>
                    <div class="fw-semibold">{{ optional($user->office)->name }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
