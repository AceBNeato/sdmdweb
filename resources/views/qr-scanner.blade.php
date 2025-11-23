@extends('layouts.app')

@php
$prefix = auth()->user()->is_admin ? 'admin' : (auth()->user()->role?->name === 'technician' ? 'technician' : 'staff');
$scanRoute = route($prefix . '.equipment.scan.process');
$viewUrl = '/' . $prefix . '/equipment';
$historyUrl = '/' . $prefix . '/equipment';
$isStaff = $prefix === 'staff';
@endphp

@section('title', 'QR Scanner')

@section('page_title', 'QR Scanner')
@section('page_description', 'Scan QR codes to access equipment details')


@push('styles')
<link rel="stylesheet" href="{{ asset('css/qr-scanner.css') }}">
<link rel="stylesheet" href="{{ asset('css/equipment.css') }}">
@endpush

@section('content')
    <div class="qr-container">
        <div class="qr-section">
            <div id="my-qr-reader">
            </div>
        </div>
    </div>

    <script>
        // Set up global QR scanner routes for external script
        window.qrScannerRoutes = {!! json_encode([
            'scan' => $scanRoute,
            'view' => $viewUrl,
            'history' => $historyUrl,
            'isStaff' => $isStaff,
        ]) !!};
        
        // Debug: Log the routes
        console.log('QR Scanner Routes:', window.qrScannerRoutes);
        console.log('Current user prefix:', '{!! $prefix !!}');
    </script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script src="{{ asset('js/qr-scanner.js') }}"></script>
@endsection