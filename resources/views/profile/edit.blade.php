@extends('layouts.staff')

@section('page_title', 'Edit Profile')

@push('styles')
<style>
    .profile-picture-wrapper {
        position: relative;
        width: 120px;
        height: 120px;
        margin: 0 auto 1.5rem;
    }
    
    .profile-picture-container {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        overflow: hidden;
        border: 4px solid #f8f9fa;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease;
    }
    
    .profile-picture-preview {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .profile-picture-container:hover .profile-picture-preview {
        transform: scale(1.05);
    }
    
    .camera-icon {
        position: absolute;
        bottom: 8px;
        right: 8px;
        background: white;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        border: 3px solid #fff;
        color: #0d6efd;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .camera-icon:hover {
        transform: scale(1.1);
        background: #f8f9fa;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
    }
</style>
@endpush



@section('content')

<!-- Page Header -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box d-flex align-items-center justify-content-between">
                <h4 class="mb-0"></h4>
                <div class="page-title-right">
                    <a href="{{ route('staff.profile') }}" class="btn btn-outline-secondary">
                        <i class='bx bx-arrow-back me-1'></i> Back to Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Form -->
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('staff.profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- Profile Picture Section -->
                        <div class="text-center mb-4">
                            <div class="position-relative d-inline-block">
                                <div class="rounded-circle overflow-hidden" style="width: 120px; height: 120px; border: 3px solid #f0f0f0;">
                                    <img src="{{ $user->profile_photo ? asset('storage/' . $user->profile_photo) : asset('images/SDMDlogo.png') }}"
                                         alt="Profile Picture"
                                         class="w-100 h-100 object-fit-cover"
                                         id="profileImagePreview"
                                         onerror="this.onerror=null; this.src='{{ asset('images/SDMDlogo.png') }}'">
                                </div>
                                <div class="position-absolute bottom-0 end-0 bg-white rounded-circle p-2 border border-2 border-white shadow-sm"
                                     style="cursor: pointer;"
                                     onclick="document.getElementById('profileImageInput').click()">
                                    <i class='bx bxs-camera text-primary' style="font-size: 1rem;"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <button type="button" class="btn btn-sm btn-primary px-3" onclick="document.getElementById('profileImageInput').click()">
                                    <i class='bx bx-upload me-1'></i>Upload New
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="resetImage()" {{ !$user->profile_photo ? 'disabled' : '' }}>
                                    <i class='bx bx-reset me-1'></i>Reset
                                </button>
                            </div>
                            <input type="file" id="profileImageInput" name="profile_photo" class="d-none" accept="image/*">
                        </div>

                        <hr>

                        <!-- Basic Information -->
                        <h5 class="text-primary mb-3">
                            <i class='bx bx-user me-2'></i>Personal Information
                        </h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Full Name *</label>
                                <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email Address *</label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone Number</label>
                                <input type="tel" name="phone" class="form-control" value="{{ old('phone', $user->phone) }}" pattern="[0-9+\-\s()]*" minlength="10" maxlength="20">
                                <small class="form-text text-muted">Phone number must be 10-20 characters long and contain only numbers, spaces, dashes, plus signs, and parentheses.</small>
                            </div>
                            <div class="col-md-6">
                                <label for="address" class="form-label fw-bold">Address</label>
                                <input type="text" id="address" name="address" class="form-control" value="{{ old('address', $user->address) }}" minlength="10">
                                <small class="form-text text-muted">Address must be at least 10 characters long.</small>
                            </div>
                        </div>

                        @if($user)
                        <!-- Staff Information -->
                        <h5 class="text-primary mb-3">
                            <i class='bx bx-briefcase me-2'></i>Staff Information
                        </h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Specialization</label>
                                <input type="text" name="specialization" class="form-control" value="{{ old('specialization', $user->specialization) }}">
                            </div>
                            <div class="col-md-6">
                                <label for="employee_id" class="form-label">Employee ID</label>
                                <input type="text" id="employee_id" name="employee_id" class="form-control" value="{{ old('employee_id', $user->employee_id) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">
                                    Skills 
                                    <small class="text-muted">(One per line)</small>
                                </label>
                                <textarea 
                                    name="skills" 
                                    class="form-control" 
                                    rows="5"
                                    placeholder="Enter each skill on a new line, for example:
- Network Troubleshooting
- Hardware Repair
- Software Installation"
                                >{{ old('skills', $user->skills) }}</textarea>
                                <div class="form-text">Each skill should be on a new line. They will be displayed as tags on your profile.</div>
                            </div>
                        </div>
                        @endif

                        <!-- Password Change -->
                        <h5 class="text-primary mb-3">
                            <i class='bx bx-lock me-2'></i>Change Password
                        </h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Current Password</label>
                                <input type="password" name="current_password" class="form-control">
                                <small class="text-muted">Leave blank if you don't want to change password</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-control" minlength="8">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" name="new_password_confirmation" class="form-control" minlength="8">
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('staff.profile') }}" class="btn btn-secondary">
                                <i class='bx bx-arrow-back me-1'></i>Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class='bx bx-save me-1'></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .profile-picture-preview {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border: 4px solid #f8f9fa;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .card {
        border: none;
        border-radius: 10px;
    }

    .form-label {
        font-weight: 600;
        color: #495057;
    }

    @media (max-width: 768px) {
        .profile-picture-preview {
            width: 100px;
            height: 100px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // Image preview functionality with visual feedback
    document.getElementById('profileImageInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Validate file type
        if (!file.type.match('image.*')) {
            alert('Please select a valid image file (JPEG, PNG, etc.).');
            this.value = '';
            return;
        }

        // Validate file size (max 2MB)
        if (file.size > 2 * 1024 * 1024) {
            alert('Image size should be less than 2MB.');
            this.value = '';
            return;
        }

        // Show loading state
        const preview = document.getElementById('profileImagePreview');
        preview.style.opacity = '0.7';
        
        // Create a new image to handle the preview
        const reader = new FileReader();
        reader.onload = function(e) {
            // Create a temporary image to check dimensions
            const img = new Image();
            img.onload = function() {
                // Set the preview source
                preview.src = e.target.result;
                // Add fade in effect
                preview.style.transition = 'opacity 0.3s ease';
                preview.style.opacity = '1';
                
                // Enable reset button
                const resetButton = document.querySelector('button[onclick="resetImage()"]');
                if (resetButton) {
                    resetButton.removeAttribute('disabled');
                }
            };
            img.src = e.target.result;
        };
        reader.onerror = function() {
            alert('Error reading the image file. Please try again.');
            this.value = '';
            preview.style.opacity = '1';
        };
        reader.readAsDataURL(file);
    });

    // Reset image to default
    function resetImage() {
        const preview = document.getElementById('profileImagePreview');
        const resetButton = document.querySelector('button[onclick="resetImage()"]');
        
        // Add fade out effect
        preview.style.transition = 'opacity 0.3s ease';
        preview.style.opacity = '0.7';
        
        // Reset the image after fade out
        setTimeout(() => {
            preview.src = '{{ $user->profile_photo ? asset('storage/' . $user->profile_photo) : asset('images/SDMDlogo.png') }}';
            document.getElementById('profileImageInput').value = '';
            
            // Fade back in
            setTimeout(() => {
                preview.style.opacity = '1';
                // Disable reset button if no original image exists
                if (!'{{ $user->profile_photo }}') {
                    resetButton.setAttribute('disabled', 'disabled');
                }
            }, 50);
        }, 300);
    }
    
    // Add hover effect for the upload area
    const uploadArea = document.querySelector('.profile-picture-container');
    if (uploadArea) {
        uploadArea.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.02)';
            this.style.transition = 'transform 0.3s ease';
        });
        
        uploadArea.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
        
        // Make the whole area clickable
        uploadArea.style.cursor = 'pointer';
        uploadArea.addEventListener('click', function() {
            document.getElementById('profileImageInput').click();
        });
    }

    // Form validation
    (function() {
        'use strict';

        var form = document.querySelector('form');

        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }

            form.classList.add('was-validated');
        }, false);

        // Password confirmation validation
        const newPassword = document.querySelector('input[name="new_password"]');
        const confirmPassword = document.querySelector('input[name="new_password_confirmation"]');

        function validatePassword() {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity("Passwords don't match");
            } else {
                confirmPassword.setCustomValidity('');
            }
        }

        if (newPassword && confirmPassword) {
            newPassword.addEventListener('change', validatePassword);
            confirmPassword.addEventListener('keyup', validatePassword);
        }
    })();
</script>
@endpush
