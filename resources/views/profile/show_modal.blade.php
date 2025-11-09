@php
    $user = $user ?? (auth('technician')->user() ?? auth('staff')->user() ?? auth()->user());
@endphp

<div class="container-fluid">
    <div class="row g-3">
        <div class="col-md-4">
            <div class="text-center">
                <img src="{{ $user->profile_photo ? asset('storage/' . $user->profile_photo) : asset('images/SDMDlogo.png') }}"
                     alt="Profile Picture"
                     class="rounded-circle img-fluid"
                     style="width: 140px; height: 140px; object-fit: cover; border: 3px solid #f0f0f0;"
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

            @isset($recentActivities)
            <hr class="my-3">
            <h6 class="mb-2">Recent Activity</h6>
            <div class="list-group list-group-flush">
                @forelse($recentActivities as $activity)
                    <div class="list-group-item px-0 d-flex justify-content-between">
                        <div>
                            <div class="fw-semibold small">{{ $activity->action }}</div>
                            <div class="text-muted small">{{ $activity->description ?? '—' }}</div>
                        </div>
                        <div class="text-muted small">{{ $activity->created_at->timezone(config('app.timezone'))->diffForHumans() }}</div>
                    </div>
                @empty
                    <div class="text-muted small">No activity yet.</div>
                @endforelse
            </div>
            @endisset
        </div>
    </div>
</div>
