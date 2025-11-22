@extends('layouts.app')

@section('title', 'Office Details')

@section('page_title', 'Office Details')
@section('page_description', 'View office details')

@push('styles')
    <link href="{{ asset('css/office.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="content">

@if(!auth()->user()->hasPermissionTo('settings.manage'))
    @php abort(403) @endphp
@else

    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="{{ route('admin.offices.index') }}" class="btn btn-outline-secondary">
            <i class='bx bx-arrow-back me-1'></i> Back to Offices
        </a>
    </div>

    <!-- Office Header -->
    <div class="office-header">
        <div>
            <h1 class="office-title">
                <i class='bx bx-building-house'></i>
                {{ $office->name }}
            </h1>
            <div class="office-meta">
                @if($office->campus)
                <div class="office-meta-item">
                    <i class='bx bx-school'></i>
                    <span>{{ $office->campus->name }} ({{ $office->campus->code }})</span>
                </div>
                @endif
                <div class="office-meta-item">
                    <i class='bx bx-time-five'></i>
                    <span>Created {{ $office->created_at->format('M d, Y') }}</span>
                </div>
            </div>
        </div>

        <div class="status-indicator {{ $office->is_active ? 'status-active' : 'status-inactive' }}">
            <i class='bx bx-{{ $office->is_active ? "check-circle" : "x-circle" }}'></i>
            {{ $office->is_active ? 'Active' : 'Inactive' }}
        </div>
    </div>

    <div class="row">
        <!-- Office Information -->
        <div class="col-md-6 mb-4">
            <div class="detail-card">
                <h5>
                    <i class='bx bx-info-circle'></i>
                    Office Information
                </h5>
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class='bx bx-tag'></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Office Name</div>
                        <div class="detail-value">{{ $office->name }}</div>
                    </div>
                </div>

                @if($office->campus)
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class='bx bx-school'></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Campus</div>
                        <div class="detail-value">{{ $office->campus->name }} <small class="text-muted">({{ $office->campus->code }})</small></div>
                    </div>
                </div>
                @endif

                <div class="detail-item">
                    <div class="detail-icon">
                        <i class='bx bx-{{ $office->is_active ? "check-circle" : "x-circle" }}'></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Status</div>
                        <div class="detail-value">
                            <span class="status-indicator {{ $office->is_active ? 'status-active' : 'status-inactive' }}">
                                {{ $office->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Information -->
        <div class="col-md-6 mb-4">
            <div class="detail-card">
                <h5>
                    <i class='bx bx-phone'></i>
                    Contact Information
                </h5>

                @if($office->address)
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class='bx bx-map-pin'></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Address</div>
                        <div class="detail-value">{{ $office->address }}</div>
                    </div>
                </div>
                @endif

                @if($office->contact_number)
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class='bx bx-phone'></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Contact Number</div>
                        <div class="detail-value">
                            <a href="tel:{{ $office->contact_number }}" class="text-decoration-none">
                                {{ $office->contact_number }}
                            </a>
                        </div>
                    </div>
                </div>
                @endif

                @if($office->email)
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class='bx bx-envelope'></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Email Address</div>
                        <div class="detail-value">
                            <a href="mailto:{{ $office->email }}" class="text-decoration-none">
                                {{ $office->email }}
                            </a>
                        </div>
                    </div>
                </div>
                @endif

                @if(!$office->address && !$office->contact_number && !$office->email)
                <div class="text-center py-4">
                    <i class='bx bx-info-circle' style="font-size: 2rem; color: #cbd5e0;"></i>
                    <p class="text-muted mt-2 mb-0">No contact information available</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Record Information -->
        <div class="col-md-6 mb-4">
            <div class="detail-card">
                <h5>
                    <i class='bx bx-time-five'></i>
                    Record Information
                </h5>

                <div class="detail-item">
                    <div class="detail-icon">
                        <i class='bx bx-calendar-plus'></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Created</div>
                        <div class="detail-value">
                            {{ $office->created_at->format('F j, Y') }}
                            <small class="text-muted d-block">{{ $office->created_at->format('h:i A') }}</small>
                        </div>
                    </div>
                </div>

                @if($office->created_at != $office->updated_at)
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class='bx bx-edit'></i>
                    </div>
                    <div class="detail-content">
                        <div class="detail-label">Last Updated</div>
                        <div class="detail-value">
                            {{ $office->updated_at->format('F j, Y') }}
                            <small class="text-muted d-block">{{ $office->updated_at->format('h:i A') }}</small>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

</div>
@endif
@endsection
