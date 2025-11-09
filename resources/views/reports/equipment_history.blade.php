@extends('layouts.app')

@section('page_title', 'Equipment History Report')
@section('page_description', 'Detailed equipment service history')

@section('content')
@if(!auth()->user()->hasPermissionTo('reports.view'))
    @php abort(403) @endphp
@else
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h4 class="card-title mb-0">
                        <i class='bx bx-history me-2'></i>
                        Equipment History Report
                    </h4>
                    <div class="card-header-actions">
                        <a href="{{ route('admin.reports.equipment.export', ['equipment' => $equipment->id, 'format' => 'pdf']) }}"
                           class="btn btn-danger btn-sm">
                            <i class='bx bx-download me-1'></i> Download PDF
                        </a>
                        <a href="{{ route('admin.reports.equipment.export', ['equipment' => $equipment->id, 'format' => 'csv']) }}"
                           class="btn btn-success btn-sm">
                            <i class='bx bx-export me-1'></i> Export CSV
                        </a>
                        <button onclick="window.print()" class="btn btn-primary btn-sm">
                            <i class='bx bx-printer me-1'></i> Print
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Equipment Information -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h5 class="text-primary">Equipment Details</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Model Number:</strong></td>
                                    <td>{{ $equipment->model_number }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Serial Number:</strong></td>
                                    <td>{{ $equipment->serial_number }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Equipment Type:</strong></td>
                                    <td>{{ $equipment->equipment_type }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td>
                                        <span class="badge bg-{{ $equipment->status === 'available' ? 'success' : ($equipment->status === 'maintenance' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($equipment->status) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Location:</strong></td>
                                    <td>{{ $equipment->location }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Office:</strong></td>
                                    <td>{{ $equipment->office ? $equipment->office->name : 'N/A' }}</td>
                                </tr>
                                @if($equipment->purchase_date)
                                <tr>
                                    <td><strong>Purchase Date:</strong></td>
                                    <td>{{ $equipment->purchase_date->format('M d, Y') }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                        <div class="col-md-4 text-center">
                            @if($equipment->qr_code_image_path)
                                <img src="{{ asset('storage/' . $equipment->qr_code_image_path) }}"
                                     alt="QR Code" class="img-fluid" style="max-width: 150px;">
                                <p class="mt-2 text-muted small">QR Code</p>
                            @endif
                        </div>
                    </div>

                    <!-- Maintenance History -->
                    @if($equipment->maintenanceLogs->count() > 0)
                    <div class="row">
                        <div class="col-12">
                            <h5 class="text-primary">Maintenance History</h5>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date & Time</th>
                                            <th>Action</th>
                                            <th>Details</th>
                                            <th>User</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($equipment->maintenanceLogs as $log)
                                        <tr>
                                            <td>{{ $log->created_at->format('M d, Y H:i') }}</td>
                                            <td>{{ ucfirst(str_replace('_', ' ', $log->action)) }}</td>
                                            <td>{{ $log->details }}</td>
                                            <td>{{ $log->user ? $log->user->name : 'System' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class='bx bx-info-circle me-2'></i>
                                No maintenance history available for this equipment.
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Report Footer -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <hr>
                            <p class="text-muted mb-0">
                                <small>
                                    Report generated on {{ now()->format('F j, Y \a\t g:i A') }} by {{ Auth::user()->name }}
                                </small>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('styles')
<style>
    @media print {
        .card-header-actions {
            display: none !important;
        }
        .btn {
            display: none !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
    }
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .card-header-actions {
        display: flex;
        gap: 0.5rem;
    }
</style>
@endpush
