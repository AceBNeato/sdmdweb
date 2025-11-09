@extends('layouts.staff')

@section('page_title', 'My Profile')

@section('content')

<!-- Add custom CSS for the profile page -->
<style>
    /* Modal Backdrop */
    .modal-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1040;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    /* Show backdrop when modal is open */
    #editProfileModal:target ~ #modalBackdrop,
    #editProfileModal.show ~ #modalBackdrop {
        display: block;
        opacity: 1;
    }

    /* Modal styles */
    .modal {
        z-index: 1050;
    }

    /* Hide modal by default */
    .modal:not(.show) {
        display: none;
    }

    /* Show modal when targeted */
    #editProfileModal:target {
        display: block;
    }

    /* Modal close button */
    .modal .btn-close {
        cursor: pointer;
    }

    /* Your existing CSS styles remain the same */
    .profile-card {
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        padding: 15px;
        overflow: hidden;
    }

    .profile-body {
        padding: 0;
    }

    .profile-picture-container {
        position: relative;
        display: inline-block;
    }

    .profile-picture {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #f0f0f0;
    }

    .edit-profile-btn {
        position: absolute;
        bottom: 5px;
        right: 5px;
        background: var(--primary-color, #007bff);
        color: white;
        border: none;
        border-radius: 50%;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }

    .edit-profile-btn:hover {
        background: var(--primary-dark, #0056b3);
        transform: scale(1.1);
    }

    .badge {
        background: #e9ecef;
        color: #ffffff;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
    }

    .compact-info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 12px;
    }

    .info-item-compact {
        margin-bottom: 8px;
    }

    .info-item-compact .label {
        font-size: 0.8rem;
        color: #6c757d;
        margin-bottom: 2px;
        font-weight: 500;
    }

    .info-item-compact .value {
        font-size: 0.9rem;
        color: #343a40;
        font-weight: 500;
    }

    .section-title {
        font-size: 1rem;
        color: #495057;
        margin-bottom: 12px;
        padding-bottom: 6px;
        border-bottom: 1px solid #e9ecef;
        font-weight: 600;
    }

    .modal.fade .modal-dialog {
        transition: transform 0.3s ease-out, opacity 0.3s ease-out;
        transform: translateY(-50px) scale(0.95);
        opacity: 0;
    }

    .modal.show .modal-dialog {
        transform: translateY(0) scale(1);
        opacity: 1;
    }

    .modal-dialog.modal-centered-custom {
        margin: 1.75rem auto;
        display: flex;
        align-items: center;
        min-height: calc(100% - 3.5rem);
    }

    .toast {
        background: #28a745;
        color: white;
    }

    @media (max-width: 768px) {
        .profile-picture {
            width: 100px;
            height: 100px;
        }

        .modal-dialog.modal-centered-custom {
            margin: 0.5rem auto;
            min-height: calc(100% - 1rem);
        }
    }

    @media (max-width: 576px) {
        .profile-picture {
            width: 80px;
            height: 80px;
        }

        .modal-dialog.modal-centered-custom {
            margin: 0.25rem auto;
            min-height: calc(100% - 0.5rem);
        }
    }

    @keyframes popup-center {
        0% {
            opacity: 0;
            transform: translateY(-50px) scale(0.9);
        }
        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .modal.show .modal-dialog {
        animation: popup-center 0.3s ease-out;
    }

    .activity-list .activity-item {
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }

    .activity-list .activity-item:last-child {
        border-bottom: none;
    }

    .activity-title {
        font-weight: 600;
        color: #343a40;
        margin-bottom: 2px;
    }

    .activity-desc {
        color: #6c757d;
        margin: 0;
    }

    .activity-time {
        color: #adb5bd;
        white-space: nowrap;
    }
</style>

<!-- Profile Card - Compact Version -->
<div class="profile-card">
    <div class="profile-body">
        <div class="row align-items-start">
            <!-- Profile Picture Column -->
            <div class="col-md-3 text-center mb-3 mb-md-0">
                <div class="profile-picture-container">
                    <img src="{{ $user->profile_photo ? asset('storage/' . $user->profile_photo) : asset('images/SDMDlogo.png') }}"
                         alt="Profile Picture"
                         class="profile-picture img-fluid"
                         id="currentProfileImage"
                         onerror="this.onerror=null; this.src='{{ asset('images/SDMDlogo.png') }}'">
                    <button type="button" class="edit-profile-btn" onclick="window.location.href='{{ route('staff.profile.edit') }}'">
                        <i class='bx bx-edit-alt'></i>
                    </button>
                </div>
                <div class="mt-2">
                    <h5 class="mb-1">{{ $user->name }}</h5>
                    <p class="text-muted small mb-1">{{ $user->position ?? 'Staff' }}</p>
                    <div class="info-section">
                        <h6 class="section-title"></h6>
                        <div class="info-item-compact">
                            <div class="label"></div>
                            <div class="value">
                                @if($user && $user->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Information Column -->
            <div class="col-md-9">
                <div class="compact-info-grid">
                    <!-- Personal Information -->
                    <div class="info-section">
                        <h6 class="section-title">Personal Information</h6>
                        <div class="info-item-compact">
                            <div class="label">Email</div>
                            <div class="value">{{ $user->email }}</div>
                        </div>
                        <div class="info-item-compact">
                            <div class="label">Phone</div>
                            <div class="value">{{ $user->phone ?? 'N/A' }}</div>
                        </div>
                        <div class="info-item-compact">
                            <div class="label">Address</div>
                            <div class="value">{{ $user->address ?? 'N/A' }}</div>
                        </div>
                    </div>

                    <!-- Employment Details -->
                    @if($user)
                    <div class="info-section">
                        <h6 class="section-title">Employment Details</h6>
                        <div class="info-item-compact">
                            <div class="label">Employee ID</div>
                            <div class="value">{{ $user->employee_id ?? 'N/A' }}</div>
                        </div>
                        @if($user->office)
                        <div class="info-item-compact">
                            <div class="label">Office</div>
                            <div class="value">{{ $user->office->name }}</div>
                        </div>
                        @endif
                    </div>


                    @endif
                </div>

                <!-- Skills Section (if available) -->
                @if($user && $user->skills)
                <div class="mt-3">
                    <h6 class="section-title">Skills</h6>
                    <div class="value small">
                        {!! nl2br(e($user->skills)) !!}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity - Compact Version -->
<div class="profile-card mt-3">
    <h6 class="section-title mb-3">Recent Activity</h6>
    <div class="activity-list">
    @forelse($recentActivities as $activity)
        <div class="activity-item d-flex justify-content-between align-items-center py-2 border-bottom">
            <div class="activity-content">
                <div class="activity-title small fw-bold">{{ $activity->action }}</div>
                <div class="activity-desc small text-muted">{{ $activity->description ?? 'No description available' }}</div>
            </div>
            <div class="activity-time small text-muted">{{ $activity->created_at->diffForHumans() }}</div>
        </div>
    @empty
        <div class="activity-item d-flex justify-content-between align-items-center py-2">
            <div class="activity-content">
                <div class="activity-title small fw-bold">No Recent Activity</div>
                <div class="activity-desc small text-muted">No activities recorded yet.</div>
            </div>
            <div class="activity-time small text-muted">N/A</div>
        </div>
    @endforelse
</div>
    </div>
</div>

@endsection
