@extends('layouts.app')

@section('title', 'View User Information')
@section('breadcrumbs')
    <a href="{{ route('accounts.index') }}">Accounts</a>
    <span class="separator">/</span>
    <a href="{{ route('accounts.show', $user) }}" class="current">{{ $user->name }}</a>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('css/accounts/accounts-show.css') }}">
@endpush

@section('content')
<div class="content">
    <div class="action-buttons">
        <a href="{{ route('accounts.index') }}" class="btn btn-outline-secondary">
            <i class='bx bx-arrow-back me-1'></i> Back to Accounts
        </a>

        @if(auth()->user()->hasPermissionTo('users.edit'))
        <a href="{{ route('accounts.edit', $user) }}"
           class="btn btn-sm btn-outline-secondary"
           title="edit">
            <i class='bx bx-edit'></i>Edit Account
        </a>
        @endif
    </div>

    <div class="user-info-card">

    
        </div>
        <div class="card-header">
            <h4 class="mb-0">User Information</h4>
            <p class="text-sm text-gray-600 mb-0">View user details, roles, and permissions</p>
        </div>

        
            <!-- Roles Section -->
            <div class="roles-section">
                <h6 class="info-label">
                    <i class='bx bx-shield'></i>
                    Assigned Roles
                </h6>
                @php
                    $activeRoles = $user->roles->filter(function($role) {
                        $expiresAt = $role->pivot->expires_at;
                        return is_null($expiresAt) || $expiresAt > now();
                    });
                @endphp
                @if($activeRoles->count() > 0)
                    <div class="info-value">
                        @foreach($activeRoles as $role)
                            <span class="role-badge">
                                <i class='bx bx-shield-check'></i> {{ $role->display_name ?? $role->name }}
                            </span>
                        @endforeach
                    </div>
                @else
                    <div class="info-value text-muted">No roles assigned</div>
                @endif
            </div>

            <!-- Temporary Admin Countdown -->
            @php
                $adminRole = $activeRoles->where('name', 'admin')->first();
                $hasTempAdmin = $adminRole && $adminRole->pivot && $adminRole->pivot->expires_at;
                $expiresAt = $hasTempAdmin ? $adminRole->pivot->expires_at : null;
                $timeRemaining = $expiresAt ? now()->diffInSeconds($expiresAt, false) : 0;
                $isExpiringSoon = $timeRemaining > 0 && $timeRemaining <= 3600; // Less than 1 hour
                $isExpired = $timeRemaining <= 0;
            @endphp

            @if($hasTempAdmin)
            <div class="temp-admin-countdown {{ $isExpired ? 'expired' : ($isExpiringSoon ? 'expiring-soon' : 'active') }}">
                <div class="countdown-header">
                    <i class='bx bx-time-five'></i>
                    <span class="countdown-title">
                        @if($isExpired)
                            Temporary Admin Access Expired
                        @else
                            Temporary Admin Access
                        @endif
                    </span>
                </div>
                <div class="countdown-body">
                    @if($isExpired)
                        <div class="expired-message">
                            <i class='bx bx-x-circle'></i>
                            Admin privileges have expired
                        </div>
                    @else
                        <div class="countdown-timer" id="temp-admin-countdown" data-expires-at="{{ $expiresAt->toISOString() }}">
                            <div class="time-units">
                                <div class="time-unit">
                                    <span class="time-value" id="days">--</span>
                                    <span class="time-label">Days</span>
                                </div>
                                <div class="time-unit">
                                    <span class="time-value" id="hours">--</span>
                                    <span class="time-label">Hours</span>
                                </div>
                                <div class="time-unit">
                                    <span class="time-value" id="minutes">--</span>
                                    <span class="time-label">Minutes</span>
                                </div>
                                <div class="time-unit">
                                    <span class="time-value" id="seconds">--</span>
                                    <span class="time-label">Seconds</span>
                                </div>
                            </div>
                        </div>
                        <div class="countdown-footer">
                            Expires on: <strong>{{ $expiresAt->format('M j, Y g:i A') }}</strong>
                        </div>
                    @endif
                </div>
            </div>
            @endif

        <div class="card-body">
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
@endsection

@push('scripts')
<script>
// Temporary Admin Countdown Timer
function initializeCountdown() {
    const countdownElement = document.getElementById('temp-admin-countdown');
    if (!countdownElement) return;

    const expiresAt = new Date(countdownElement.dataset.expiresAt);
    const daysElement = document.getElementById('days');
    const hoursElement = document.getElementById('hours');
    const minutesElement = document.getElementById('minutes');
    const secondsElement = document.getElementById('seconds');

    function updateCountdown() {
        const now = new Date();
        const timeRemaining = expiresAt - now;

        if (timeRemaining <= 0) {
            // Expired
            daysElement.textContent = '00';
            hoursElement.textContent = '00';
            minutesElement.textContent = '00';
            secondsElement.textContent = '00';

            // Update styling to show expired state
            const countdownContainer = countdownElement.closest('.temp-admin-countdown');
            countdownContainer.classList.remove('active', 'expiring-soon');
            countdownContainer.classList.add('expired');

            // Update title
            const titleElement = countdownContainer.querySelector('.countdown-title');
            titleElement.textContent = 'Temporary Admin Access Expired';

            // Update message
            const bodyElement = countdownContainer.querySelector('.countdown-body');
            bodyElement.innerHTML = `
                <div class="expired-message">
                    <i class='bx bx-x-circle'></i>
                    Admin privileges have expired
                </div>
            `;

            return;
        }

        // Calculate time units
        const days = Math.floor(timeRemaining / (1000 * 60 * 60 * 24));
        const hours = Math.floor((timeRemaining % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((timeRemaining % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((timeRemaining % (1000 * 60)) / 1000);

        // Update display
        daysElement.textContent = days.toString().padStart(2, '0');
        hoursElement.textContent = hours.toString().padStart(2, '0');
        minutesElement.textContent = minutes.toString().padStart(2, '0');
        secondsElement.textContent = seconds.toString().padStart(2, '0');

        // Update styling based on time remaining
        const countdownContainer = countdownElement.closest('.temp-admin-countdown');
        if (timeRemaining <= 3600000) { // Less than 1 hour
            countdownContainer.classList.remove('active');
            countdownContainer.classList.add('expiring-soon');
        } else {
            countdownContainer.classList.remove('expiring-soon');
            countdownContainer.classList.add('active');
        }
    }

    // Initial update
    updateCountdown();

    // Update every second
    setInterval(updateCountdown, 1000);
}

// Initialize countdown when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeCountdown();
});
</script>
@endpush
