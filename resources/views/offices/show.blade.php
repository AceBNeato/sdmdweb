@extends('layouts.admin')

@section('title', 'Office Details')

@section('breadcrumbs')
    <a href="{{ route('admin.accounts') }}">Accounts</a>
    <span class="separator">/</span>
    <a href="{{ route('admin.offices.index') }}">Offices</a>
    <span class="separator">/</span>
    <span class="current">Office Details</span>
@endsection

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/office/office-common.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pages/office/office-show.css') }}">
@endpush

@section('content')
<div class="content">
    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="{{ route('admin.offices.index') }}" class="btn btn-outline-secondary">
            <i class='bx bx-arrow-back me-1'></i> Back to Offices
        </a>
    </div>

    <!-- Office Info Card -->
    <div class="office-info-card">
        <div class="card-body">
            <!-- Office Information Section -->
            <div class="info-section">
                <div class="info-label">Office Information</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-item-label">Name:</div>
                        <div class="info-item-value">{{ $office->name }}</div>
                    </div>
                    @if($office->campus)
                    <div class="info-item">
                        <div class="info-item-label">Campus:</div>
                        <div class="info-item-value">{{ $office->campus->name }} ({{ $office->campus->code }})</div>
                    </div>
                    @endif
                    <div class="info-item">
                        <div class="info-item-label">Status:</div>
                        <div class="info-item-value">
                            <span class="status-badge {{ $office->is_active ? 'badge-success' : 'badge-danger' }}">
                                {{ $office->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Information Section -->
            <div class="info-section">
                <div class="info-label">Contact Information</div>
                <div class="info-grid">
                    @if($office->address)
                    <div class="info-item">
                        <div class="info-item-label">Address:</div>
                        <div class="info-item-value">{{ $office->address }}</div>
                    </div>
                    @endif
                    @if($office->contact_number)
                    <div class="info-item">
                        <div class="info-item-label">Contact Number:</div>
                        <div class="info-item-value">{{ $office->contact_number }}</div>
                    </div>
                    @endif
                    @if($office->email)
                    <div class="info-item">
                        <div class="info-item-label">Email:</div>
                        <div class="info-item-value">
                            <a href="mailto:{{ $office->email }}" class="text-decoration-none">
                                {{ $office->email }}
                            </a>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Footer Information -->
            <div class="info-section">
                <div class="info-label">Record Information</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-item-label">Created:</div>
                        <div class="info-item-value">{{ $office->created_at->format('M d, Y h:i A') }}</div>
                    </div>
                    @if($office->created_at != $office->updated_at)
                    <div class="info-item">
                        <div class="info-item-label">Last Updated:</div>
                        <div class="info-item-value">{{ $office->updated_at->format('M d, Y h:i A') }}</div>
                    </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
