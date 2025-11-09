@extends('layouts.app')

@php
    $campuses = $campuses ?? \App\Models\Campus::where('is_active', true)->get();
@endphp

@section('title', $office->exists ? 'Edit Office' : 'Add New Office')

@section('page_title', 'Offices Management')
@section('page_description', 'Manage all offices')


@push('styles')
    <link rel="stylesheet" href="{{ asset('css/office.css') }}">
@endpush

@section('content')
<div class="content">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

@if(!auth()->user()->hasPermissionTo('settings.manage'))
    @php abort(403) @endphp
@else

    <!-- Form Card -->
    <div class="office-form-card">
        <div class="form-header">
            <h4 class="mb-1">
                <i class='bx bx-building'></i>
                {{ $office->exists ? 'Edit Office' : 'Add New Office' }}
            </h4>
            <p class="text-muted">Fill in the details below to {{ $office->exists ? 'update' : 'create' }} the office</p>
        </div>

        <form method="POST" action="{{ $office->exists ? route('admin.offices.update', $office) : route('admin.offices.store') }}" class="needs-validation" novalidate>
            @csrf
            @if($office->exists)
                @method('PUT')
            @endif

            <div class="form-body">
                <div class="form-grid">
                    <!-- Office Name -->
                    <div class="form-group">
                        <label for="name" class="form-label">
                            Office Name <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class='bx bx-building'></i></span>
                            <input type="text"
                                   class="form-control @error('name') is-invalid @enderror"
                                   id="name"
                                   name="name"
                                   value="{{ old('name', $office->name) }}"
                                   required
                                   minlength="3"
                                   maxlength="255"
                                   placeholder="Enter office name">
                        </div>
                        @error('name')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Campus Selection -->
                    <div class="form-group">
                        <label for="campus_id" class="form-label">
                            Campus <span class="text-danger">*</span>
                        </label>
                        <select class="form-select @error('campus_id') is-invalid @enderror"
                                id="campus_id"
                                name="campus_id"
                                required>
                            <option value="" disabled {{ old('campus_id', $office->campus_id) ? '' : 'selected' }}>
                                Select Campus
                            </option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus->id }}"
                                        {{ old('campus_id', $office->campus_id) == $campus->id ? 'selected' : '' }}>
                                    {{ $campus->name }} ({{ $campus->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('campus_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Address -->
                    <div class="form-group">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control @error('address') is-invalid @enderror"
                                  id="address"
                                  name="address"
                                  rows="3"
                                  maxlength="500"
                                  placeholder="Enter office address">{{ old('address', $office->address) }}</textarea>
                        <div class="form-text text-muted">Maximum 500 characters</div>
                        @error('address')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Contact Number -->
                    <div class="form-group">
                        <label for="contact_number" class="form-label">Contact Number</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class='bx bx-phone'></i></span>
                            <input type="text"
                                   class="form-control @error('contact_number') is-invalid @enderror"
                                   id="contact_number"
                                   name="contact_number"
                                   value="{{ old('contact_number', $office->contact_number) }}"
                                   maxlength="20"
                                   placeholder="e.g., +63 123 456 7890">
                        </div>
                        <div class="form-text text-muted">Format: +63 123 456 7890</div>
                        @error('contact_number')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label for="email" class="form-label">Email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class='bx bx-envelope'></i></span>
                            <input type="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   id="email"
                                   name="email"
                                   value="{{ old('email', $office->email) }}"
                                   maxlength="255"
                                   placeholder="office@example.com">
                        </div>
                        @error('email')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Active Status Toggle -->
                    <div class="form-group">
                        <div class="form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input @error('is_active') is-invalid @enderror"
                                   type="checkbox"
                                   role="switch"
                                   id="is_active"
                                   name="is_active"
                                   value="1"
                                   {{ (old('is_active', $office->is_active ?? true) == 1 || old('is_active', $office->is_active ?? true) === '1') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                        <small class="form-text text-muted">Toggle to set the office as active or inactive</small>
                        @error('is_active')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="{{ route('admin.offices.index') }}" class="btn btn-outline-secondary">
                        <i class='bx bx-x me-1'></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-save me-1'></i> {{ $office->exists ? 'Update' : 'Create' }} Office
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
