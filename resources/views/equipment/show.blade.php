@extends('layouts.app')

@section('title', 'View Equipment')

@php
    $prefix = auth()->user()->is_admin ? 'admin' : (auth()->user()->role?->name === 'technician' ? 'technician' : 'staff');
@endphp

@section('breadcrumbs')
    <a href="{{ route($prefix . '.equipment.index') }}">Equipment</a>
    <span class="separator">/</span>
    <a href="{{ route($prefix . '.equipment.show', $equipment) }}" class="current">{{ $equipment->model_number }}</a>
@endsection
@section('page_title', 'Equipment Details')

@push('styles')
    <link href="{{ asset('css/equipment.css') }}" rel="stylesheet">
@endpush


@section('content')
<div class="equipment-header">
    <div class="row align-items-center">
        <div class="col-md-3" style="background: white; border-radius: 12px;">
            <div class="equipment-image">

                @if($equipment->qr_code_image_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($equipment->qr_code_image_path))
                    <img src="{{ asset('storage/' . $equipment->qr_code_image_path) }}" alt="QR Code for {{ $equipment->model_number }}" class="img-fluid">
                @elseif($equipment->qr_code)
                    <img src="{{ route($prefix . '.equipment.qrcode', $equipment) }}" alt="QR Code for {{ $equipment->model_number }}" class="img-fluid" onerror="console.log('QR Code image failed to load'); this.style.display='none';">
                @else
                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                        <i class='bx bx-qr-scan text-4xl opacity-50'></i>
                        <small class="d-block mt-1">No QR Code</small>
                    </div>
                @endif

            </div>
        </div>
        <div class="col-md-9">
            <h1 class="equipment-title">{{ $equipment->model_number }}</h1>
            <div class="equipment-subtitle">{{ $equipment->equipmentType->name ?? 'Unknown Type' }} • {{ $equipment->serial_number }}</div>



            <div class="action-buttons">
                @can('equipment.edit')
                <a href="{{ route($prefix . '.equipment.edit', $equipment) }}" class="btn btn-primary" title="Edit Equipment">
                    <i class='bx bx-edit-alt'></i> EDIT
                </a>
                @endcan

                @can('reports.view')
                <a href="{{ route($prefix . '.reports.history', $equipment->id) }}" class="btn btn-primary" title="View History">
                    <i class='bx bx-history'></i> HISTORY
                </a>
                @endcan

                @can('equipment.delete')
                <button type="button" class="btn btn-primary ajax-delete" 
                        data-url="{{ route($prefix . '.equipment.destroy', $equipment) }}" 
                        title="Delete Equipment">
                    <i class='bx bx-trash-alt'></i> DELETE
                </button>
                @endcan

                <a href="{{ route($prefix . '.equipment.download-qrcode', $equipment) }}"
                    class="btn btn-primary">
                    <i class='bx bx-download me-1'></i> Download QR Code
                </a>

                <a href="{{ route($prefix . '.equipment.print-qrcode', $equipment) }}"
                        class="btn btn-primary">
                    <i class='bx bx-printer me-1'></i> Print QR Code
                </a>

                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#printQrcodesModal">
                    <i class='bx bx-printer me-1'></i> Print Multiple QR
                </button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="detail-card">
            <h5><i class='bx bx-cog me-2'></i>Technical Details</h5>

            <div class="detail-item">
                <div class="detail-icon">
                    <i class='bx bx-barcode'></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label">Serial Number</div>
                    <div class="detail-value">{{ $equipment->serial_number }}</div>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-icon">
                    <i class='bx bx-chip'></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label">Model Number</div>
                    <div class="detail-value">{{ $equipment->model_number }}</div>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-icon">
                    <i class='bx bx-category'></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label">Equipment Type</div>
                    <div class="detail-value">{{ $equipment->equipmentType->name ?? 'Unknown Type' }}</div>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-icon">
                    <i class='bx bx-info-circle'></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label">Status</div>
                    <div class="detail-value">
                        <span class="badge status-{{ str_replace('_', '-', $equipment->status ?? 'unknown') }}">
                            {{ ucfirst(str_replace('_', ' ', $equipment->status ?? 'unknown')) }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-icon">
                    <i class='bx bx-calendar'></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label">Acquisition Date</div>
                    <div class="detail-value">
                        {{ $equipment->purchase_date ? $equipment->purchase_date->format('M d, Y') : 'N/A' }}
                    </div>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-icon">
                    <i class='bx bx-dollar'></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label">Cost of Purchase</div>
                    <div class="detail-value">
                        {{ $equipment->cost_of_purchase ? '₱' . number_format($equipment->cost_of_purchase, 2) : 'N/A' }}
                    </div>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-icon">
                    <i class='bx bx-check-circle'></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label">Condition</div>
                    <div class="detail-value">
                        <span class="badge status-{{ str_replace('_', '-', $equipment->condition ?? 'unknown') }}">
                            {{ ucfirst(str_replace('_', ' ', $equipment->condition ?? 'unknown')) }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="detail-item">
                <div class="detail-icon">
                    <i class='bx bx-note'></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label">Description</div>
                    <div class="detail-value">{{ $equipment->description }}</div>
                </div>
            </div>

        

            <div class="detail-item">
                <div class="detail-icon">
                    <i class='bx bx-wrench'></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label">Last Maintenance</div>
                    <div class="detail-value">
                        @if($equipment->last_maintenance)
                            {{ $equipment->last_maintenance->format('M d, Y') }}
                        @else
                            No maintenance record
                        @endif
                    </div>
                </div>
            </div>


            
            <div class="detail-item">
                <div class="detail-icon">
                    <i class='bx bx-map-pin'></i>
                </div>
                <div class="detail-content">
                    <div class="detail-label">Office</div>
                    <div class="detail-value">{{ $equipment->office ? $equipment->office->name . ' (' . ($equipment->office->campus->name ?? 'Unknown Campus') . ')' : 'Not assigned' }}</div>
                </div>
            </div>
        </div>
    </div>



        <div class="detail-card">
            <h5><i class='bx bx-history me-2'></i>Activity Log</h5>

            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <p class="equipment-added"><strong>Equipment Created</strong></p>
                        <p class="equipment-added">
                            {{ \Carbon\Carbon::parse($equipment->created_at)->format('M d, Y \a\t h:i A') }} by System
                        </p>
                    </div>
                </div>

                @if($equipment->updated_at->gt($equipment->created_at))
                <div class="timeline-item">
                    <div class="timeline-marker"></div>
                    <div class="timeline-content">
                        <p class="equipment-added"><strong>Last Updated</strong></p>
                        <p class="equipment-added">
                            {{ \Carbon\Carbon::parse($equipment->updated_at)->format('M d, Y \a\t h:i A') }}
                        </p>
                    </div>
                </div>
                @endif

            
            </div>
        </div>
    </div>
</div>

<!-- Print QR Codes Modal -->
<div class="modal fade" id="printQrcodesModal" tabindex="-1" aria-labelledby="printQrcodesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            @include('equipment.print-qrcodes_modal', [
                'equipment' => $equipmentCollection ?? collect([$equipment]),
                'campuses' => $campuses,
                'routePrefix' => $prefix,
                'selectedOfficeId' => $selectedOfficeId ?? 'all',
                'printPdfRoute' => route($prefix . '.equipment.print-qrcodes-pdf')
            ])
        </div>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/equipment-show.js') }}"></script>
@endpush

@endsection
