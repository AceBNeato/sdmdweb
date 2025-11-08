@extends('layouts.app')

@section('page_title', 'Add History Entry')
@section('page_description', 'Record maintenance and service history for equipment')

@push('styles')
    <link href="{{ asset('css/admin/accounts.css') }}" rel="stylesheet">
@endpush

@section('title', 'Add History - ' . $equipment->model_number . ' - SDMD Admin')

@section('content')
<div class="content">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-0">Add History Entry</h4>
                    <p class="text-muted mt-1">Record maintenance or service activity for {{ $equipment->model_number }} (SN: {{ $equipment->serial_number }})</p>
                </div>
                <a href="{{ route('admin.equipment.show', $equipment) }}" class="btn btn-outline-secondary">
                    <i class='bx bx-arrow-back me-1'></i> Back to Equipment
                </a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.equipment.history.store', $equipment) }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('date') is-invalid @enderror"
                                   id="date" name="date" value="{{ old('date', now()->format('Y-m-d')) }}" required>
                            @error('date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="jo_number" class="form-label">Job Order Number</label>
                            <input type="text" class="form-control @error('jo_number') is-invalid @enderror"
                                   id="jo_number" name="jo_number"
                                   value="{{ old('jo_number') }}"
                                   placeholder="e.g., JO-2023-001">
                            @error('jo_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="action_taken" class="form-label">Action Taken <span class="text-danger">*</span></label>
                    <textarea class="form-control @error('action_taken') is-invalid @enderror"
                              id="action_taken" name="action_taken" rows="4"
                              placeholder="Describe the maintenance or service action performed..." required>{{ old('action_taken') }}</textarea>
                    @error('action_taken')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="remarks" class="form-label">Remarks</label>
                    <textarea class="form-control @error('remarks') is-invalid @enderror"
                              id="remarks" name="remarks" rows="3"
                              placeholder="Additional notes or observations...">{{ old('remarks') }}</textarea>
                    @error('remarks')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="responsible_person" class="form-label">Responsible Person <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('responsible_person') is-invalid @enderror"
                           id="responsible_person" name="responsible_person"
                           value="{{ old('responsible_person', auth()->user()->name ?? '') }}"
                           placeholder="Name of person who performed the work" required>
                    @error('responsible_person')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex gap-3">
                    <button type="submit" class="btn btn-primary">
                        <i class='bx bx-save me-1'></i> Save History Entry
                    </button>
                    <a href="{{ route('admin.equipment.show', $equipment) }}" class="btn btn-outline-secondary">
                        <i class='bx bx-x me-1'></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.equipment-info-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
}

.equipment-info-card h5 {
    color: #495057;
    font-weight: 600;
}

.info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid #e9ecef;
}

.info-item:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.info-label {
    font-weight: 500;
    color: #6c757d;
}

.info-value {
    font-weight: 500;
    color: #495057;
}
</style>
@endsection
