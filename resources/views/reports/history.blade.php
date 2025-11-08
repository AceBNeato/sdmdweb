@extends('layouts.admin')

@push('styles')
    <link href="{{ asset('css/admin/reports.css') }}" rel="stylesheet">
    <style>
        .history-container {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        .history-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .history-actions {
            display: flex;
            gap: 0.5rem;
        }
        .history-timeline {
            position: relative;
            padding-left: 2rem;
        }
        .timeline-item {
            position: relative;
            padding-bottom: 2rem;
            padding-left: 2rem;
            border-left: 2px solid #e0e0e0;
        }
        .timeline-item:last-child {
            border-left-color: transparent;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #4a90e2;
            left: -7px;
            top: 0;
        }
        .timeline-date {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .timeline-content {
            background: #f8f9fa;
            padding: 1.25rem;
            border-radius: 6px;
            margin-bottom: 1.5rem;
            border-left: 3px solid #4a90e2;
        }
        .timeline-title {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2c3e50;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .jo-number {
            background: #e3f2fd;
            color: #1976d2;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .timeline-description {
            color: #555;
            font-size: 0.95rem;
            margin-bottom: 0.75rem;
            line-height: 1.5;
        }
        .timeline-user {
            font-size: 0.85rem;
            color: #4a90e2;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
            font-weight: 500;
        }
        .back-button {
            margin-bottom: 1.5rem;
        }
        .no-history {
            text-align: center;
            padding: 3rem 0;
            color: #666;
        }
        .no-history i {
            font-size: 3rem;
            color: #ddd;
            margin-bottom: 1rem;
            display: block;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 20px;
                font-size: 12px;
            }
            .history-container {
                box-shadow: none;
                padding: 0;
                margin: 0;
            }
        }
    </style>
@endpush

@section('title', 'Equipment History - ' . ($equipment->model_number ?? 'SDMD'))

@section('breadcrumbs')
    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">Reports</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.equipment.index') }}">Equipment</a></li>
    <li class="breadcrumb-item active">History</li>
@endsection

@section('page_title', 'Equipment History')

@section('content')
<div class="history-container">
    <div class="action-buttons">
        <a href="{{ route('admin.equipment.show', $equipment) }}" class="btn btn-outline-secondary no-print">
            <i class='bx bx-arrow-back'></i> Back
        </a>

        <a href="{{ route('admin.reports.equipment.history.export', $equipment) }}" class="btn btn-primary no-print" target="_blank">
            <i class='bx bx-printer'></i> Print
        </a>
    </div>

    <div class="history-header">
        <div>
            <h2 class="mb-2">{{ $equipment->model_number }}</h2>
            <p class="mb-0">
                <strong>Serial Number:</strong> {{ $equipment->serial_number }}
                @if($equipment->property_number)
                    | <strong>Property #:</strong> {{ $equipment->property_number }}
                @endif
            </p>
            @if($equipment->office)
                <p class="mb-0"><strong>Location:</strong> {{ $equipment->office->name }} - {{ $equipment->office->campus->name ?? '' }}</p>
            @endif
        </div>
    </div>

    @if($equipment->history->count() > 0)
        <div class="history-timeline">
            @foreach($equipment->history as $history)
                <div class="timeline-item">
                    <div class="timeline-date">
                        {{ $history->created_at->format('F j, Y - h:i A') }}
                    </div>
                    <div class="timeline-content">
                        <div class="timeline-title">
                            <span>{{ $history->action_taken }}</span>
                            @if($history->jo_number)
                                <span class="jo-number">JO #{{ $history->jo_number }}</span>
                            @endif
                        </div>

                        @if($history->remarks)
                            <div class="timeline-description">
                                {{ $history->remarks }}
                            </div>
                        @endif

                        <div class="timeline-user">
                            <i class='bx bx-user'></i>
                            {{ $history->responsible_person }}
                            @if($history->user)
                                ({{ $history->user->name }})
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="no-history">
            <i class='bx bx-time'></i>
            <h4>No History Available</h4>
            <p>This equipment doesn't have any history records yet.</p>
        </div>
    @endif
</div>
@endsection
