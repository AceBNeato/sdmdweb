@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-4xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <div class="flex items-center justify-center mb-4 text-green-600 text-3xl">
                <i class="fas fa-database mr-3"></i>
                <h1 class="text-2xl font-bold text-gray-900">Database Backup &amp; Restore</h1>
            </div>

            <p class="text-gray-600 mb-4">
                Backup and restore controls have moved to the <strong>System Settings</strong> page.
            </p>

            <p class="text-gray-500 mb-6">
                Navigate to <em>Settings &raquo; System Settings</em> to configure automated backups, create manual backups, restore from recent files, and manage downloads or deletions.
            </p>

            <a href="{{ route('admin.settings.index') }}" class="btn btn-primary inline-flex items-center">
                <i class="fas fa-cogs mr-2"></i>
                Open System Settings
            </a>
        </div>
    </div>
</div>
@endsection
