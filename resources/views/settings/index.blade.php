@extends('layouts.app')

@section('page_title', 'Settings')
@section('page_description', 'Manage system configuration, backups, and master data')

@push('styles')
<link href="{{ asset('css/settings.css') }}" rel="stylesheet">

<style>
.settings-tabs {
    display: flex;
    border-bottom: 1px solid #e5e7eb;
    margin-bottom: 2rem;
}

.settings-tab {
    padding: 0.75rem 1.5rem;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    font-weight: 500;
    color: #6b7280;
    transition: all 0.2s;
}

.settings-tab.active {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
}

.settings-tab:hover {
    color: #374151;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="settings-container">
        <!-- Settings Tabs -->
        <div class="settings-tabs">
            <div class="settings-tab active" data-tab="general">
                <i class="fas fa-cog mr-2"></i>Session Settings
            </div>
            <div class="settings-tab" data-tab="system">
                <i class="fas fa-database mr-2"></i>Equipment Settings
            </div>
            <div class="settings-tab" data-tab="backup">
                <i class="fas fa-hdd mr-2"></i>Backup and Restore
            </div>
        </div>

        <!-- General Settings Tab -->
        <div id="general-tab" class="tab-content active">
            <!-- Summary Cards -->
            <div class="settings-summary-cards">
                <div class="summary-card">
                    <div class="summary-value">{{ $settings['session_lockout_minutes'] }}</div>
                    <div class="summary-label">Session Lockout (min)</div>
                </div>
            </div>

            <!-- Settings Grid -->
            <div class="settings-grid">

                <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-6">
                    @csrf
                    <input type="hidden" name="section" value="session">

                <!-- Session Lockout Section -->
                <div class="settings-section">
                    <div class="settings-section-header">
                        <i class="fas fa-lock settings-section-icon"></i>
                        <h3>Session Lockout</h3>
                    </div>
                    <div class="settings-section-content">
                        <p class="text-gray-600 mb-4">
                            Configure when the screen locks due to inactivity. Users must enter their password to unlock and continue working.
                        </p>

                        <div class="settings-form">
                            <div class="form-group">
                                <label for="session_lockout_minutes">Lockout Duration (minutes)</label>
                                <input
                                    type="number"
                                    id="session_lockout_minutes"
                                    name="session_lockout_minutes"
                                    value="{{ $settings['session_lockout_minutes'] }}"
                                    min="1"
                                    max="60"
                                    class="form-input"
                                    required
                                >
                                <p class="text-xs text-gray-500 mt-1">Range: 1-60 minutes</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="settings-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>
                        Save Settings
                    </button>
                </div>
                </form>
            </div>
        </div>

        <!-- Backup & Restore Tab -->
        <div id="backup-tab" class="tab-content">
            <!-- Settings Grid -->
            <div class="settings-grid">

                <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-6 backup-settings-form">
                    @csrf
                    <input type="hidden" name="section" value="backup">

                <!-- Database Backup Section -->
                <div class="settings-section">
                    <div class="settings-section-header">
                        <i class="fas fa-database settings-section-icon"></i>
                        <h3>Database Backups</h3>
                    </div>
                    <div class="settings-section-content">
                        <p class="text-gray-600 mb-4">
                            Configure automatic database backups. Backups will run at the selected time on the chosen days.
                        </p>

                        <!-- Backup Toggle -->
                        <div class="mb-6">
                            <label class="flex items-center space-x-3">
                                <input type="checkbox" name="backup_auto_enabled" value="1" class="h-5 w-5 text-primary" {{ ($backupSettings['enabled'] ?? false) ? 'checked' : '' }}>
                                <span class="text-sm font-medium">Enable automatic backups</span>
                            </label>
                        </div>

                        <!-- Backup Time -->
                        <div class="settings-form mb-6">
                            <div class="form-group">
                                <label for="backup_auto_time">Backup Time</label>
                                <input
                                    type="time"
                                    id="backup_auto_time"
                                    name="backup_auto_time"
                                    value="{{ $backupSettings['time'] ?? '02:00' }}"
                                    class="form-input"
                                >
                                <p class="text-xs text-gray-500 mt-1">Time is based on the server timezone.</p>
                            </div>
                        </div>

                        <!-- Backup Days -->
                        <div class="form-group mb-6">
                            <label>Backup Days</label>
                            <div class="form-checkbox-group">
                                @php
                                    $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                                @endphp
                                @foreach($weekdays as $day)
                                    <label class="checkbox-item {{ in_array($day, $backupSettings['days'] ?? []) ? 'active' : '' }}">
                                        <input
                                            type="checkbox"
                                            name="backup_auto_days[]"
                                            value="{{ $day }}"
                                            {{ in_array($day, $backupSettings['days'] ?? []) ? 'checked' : '' }}
                                            
                                        >
                                        <span>{{ ucfirst($day) }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="text-xs text-gray-500 mt-2">Select one or more days when automated backups should run.</p>
                        </div>

                        <!-- Automation Status -->
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <h4 class="text-sm font-semibold text-gray-800 mb-2">Automation Status</h4>
                            <ul class="text-sm text-gray-700 space-y-1">
                                <li>
                                    <span class="font-medium">Next run time:</span>
                                    @if(($backupSettings['enabled'] ?? false) && !empty($backupSettings['days']))
                                        <span id="backup-next-run" data-days="{{ implode(',', $backupSettings['days'] ?? []) }}" data-time="{{ $backupSettings['time'] ?? '02:00' }}"></span>
                                    @else
                                        <span>Automation disabled</span>
                                    @endif
                                </li>
                                <li>
                                    <span class="font-medium">Last run:</span>
                                    {{ $backupSettings['last_run_at'] ? \Carbon\Carbon::parse($backupSettings['last_run_at'])->format('M d, Y h:i A') : 'Never' }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="settings-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>
                        Save Settings
                    </button>
                </div>
                </form>

                <!-- Backup Management -->
                <div class="backup-management">
                    <div class="settings-section">
                        <div class="settings-section-header">
                            <i class="fas fa-database settings-section-icon"></i>
                            <h3>Database Backup & Restore</h3>
                        </div>
                        <div class="settings-section-content">
                            <div class="backup-toolbar">
                                <button type="button" class="btn btn-primary" id="backup-now-btn">
                                    <i class="fas fa-cloud-download-alt mr-2"></i>
                                    Create Backup
                                </button>

                                <select id="restore-backup-select" class="backup-select">
                                    <option value="" disabled selected>Select a backup</option>
                                    @foreach(($backups ?? []) as $backup)
                                        <option value="{{ $backup['filename'] }}">
                                            {{ $backup['filename'] }}
                                        </option>
                                    @endforeach
                                </select>

                                <button type="button" id="restore-btn" class="btn btn-danger">
                                    <i class="fas fa-history mr-2"></i>
                                    Restore Selected Backup
                                </button>
                            </div>

                            <div class="backup-table">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                    <h3 style="font-size: 1.125rem; font-weight: 600; color: var(--text);">Backup History</h3>
                                    <div style="font-size: 0.875rem; color: var(--text-muted);" id="backup-count">{{ count($backups ?? []) }} backups stored</div>
                                </div>
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Filename</th>
                                            <th>Size</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="backup-table-body">
                                        @forelse(($backups ?? []) as $backup)
                                            <tr data-filename="{{ $backup['filename'] }}">
                                                <td>{{ $backup['filename'] }}</td>
                                                <td>{{ $backup['size_human'] }}</td>
                                                <td>{{ \Carbon\Carbon::parse($backup['created_at'])->format('M d, Y h:i A') }}</td>
                                                <td class="backup-actions">
                                                    <a href="{{ route('admin.backup.download', $backup['filename']) }}" class="action-link">
                                                        <i class="fas fa-download mr-1"></i> Download
                                                    </a>
                                                    <button type="button" class="action-link danger" data-delete="{{ $backup['filename'] }}">
                                                        <i class="fas fa-trash mr-1"></i> Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" style="text-align: center; padding: 3rem;">
                                                    <i class="fas fa-database text-3xl mb-2 text-gray-400"></i>
                                                    <div>No backups found. Create your first backup above.</div>
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Management Tab -->
        <div id="system-tab" class="tab-content">

            <!-- System Statistics -->
            <div class="row">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="h3 text-primary">{{ \App\Models\Category::count() }}</div>
                            <p class="text-muted mb-0">Total Categories</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="h3 text-success">{{ \App\Models\EquipmentType::count() }}</div>
                            <p class="text-muted mb-0">Equipment Types</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="h3 text-warning">{{ \App\Models\Equipment::count() }}</div>
                            <p class="text-muted mb-0">Total Equipment</p>
                        </div>
                    </div>
                </div>
                        <div class="col-md-3">
                    <div class="card">
                        <div class="card-body text-center">
                            <div class="h3 text-info">{{ \App\Models\Campus::count() }}</div>
                            <p class="text-muted mb-0">Total Campuses</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="category-row">
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-tags fa-3x text-primary mb-3"></i>
                            <h5>Categories</h5>
                            <p class="text-muted">Manage equipment categories</p>
                            <a href="{{ route('admin.settings.system.categories.index') }}" class="btn btn-primary">
                                Manage Categories
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <i class="fas fa-cogs fa-3x text-success mb-3"></i>
                            <h5>Equipment Types</h5>
                            <p class="text-muted">Manage equipment types and ordering</p>
                            <a href="{{ route('admin.settings.system.equipment-types.index') }}" class="btn btn-primary">
                                Manage Equipment Types
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        
    </div>
</div>

<!-- Toast Notifications Container -->
<div class="toast-container" id="settings-toast-container"></div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const csrfToken = '{{ csrf_token() }}';
        const manualBackupBtn = document.getElementById('backup-now-btn');
        const restoreBtn = document.getElementById('restore-btn');
        const restoreSelect = document.getElementById('restore-backup-select');
        const backupDownloadUrlTemplate = '{{ route('admin.backup.download', ['filename' => 'FILENAME_PLACEHOLDER']) }}';

        function setButtonLoading(button, loading, idleText, loadingText) {
            if (!button) return;
            button.disabled = loading;
            button.innerHTML = loading ? loadingText : idleText;
        }

        function showToast(type, message) {
            const toastContainer = document.getElementById('settings-toast-container');
            const existing = toastContainer.querySelector('.toast');
            if (existing) existing.remove();

            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                <div>
                    <p class="font-semibold">${type === 'success' ? 'Success' : 'Error'}</p>
                    <p class="text-sm">${message}</p>
                </div>
            `;

            toastContainer.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        }

        function refreshBackupTable() {
            fetch('{{ route('admin.backup.list') }}', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    const backups = data.backups || [];
                    const tableBody = document.getElementById('backup-table-body');
                    const countEl = document.getElementById('backup-count');

                    if (tableBody && countEl) {
                        tableBody.innerHTML = '';
                        countEl.textContent = `${backups.length} backups stored`;

                        if (backups.length === 0) {
                            tableBody.innerHTML = `
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 3rem;">
                                        <i class="fas fa-database text-3xl mb-2 text-gray-400"></i>
                                        <div>No backups found. Create your first backup above.</div>
                                    </td>
                                </tr>
                            `;
                        } else {
                            backups.forEach(backup => {
                                const createdAtRaw = backup.created_at || '';
                                let createdAtDisplay = createdAtRaw;
                                const parsedDate = createdAtRaw ? new Date(createdAtRaw.replace(' ', 'T')) : null;
                                if (parsedDate && !isNaN(parsedDate.getTime())) {
                                    createdAtDisplay = parsedDate.toLocaleString();
                                }

                                const row = document.createElement('tr');
                                row.setAttribute('data-filename', backup.filename);
                                row.innerHTML = `
                                    <td>${backup.filename}</td>
                                    <td>${backup.size_human}</td>
                                    <td>${createdAtDisplay}</td>
                                    <td class="backup-actions">
                                        <a href="${backupDownloadUrlTemplate.replace('FILENAME_PLACEHOLDER', encodeURIComponent(backup.filename))}" class="action-link">
                                            <i class="fas fa-download mr-1"></i> Download
                                        </a>
                                        <button type="button" class="action-link danger" data-delete="${backup.filename}">
                                            <i class="fas fa-trash mr-1"></i> Delete
                                        </button>
                                    </td>
                                `;
                                tableBody.appendChild(row);
                            });
                        }
                    }

                    if (restoreSelect) {
                        restoreSelect.innerHTML = '<option value="" disabled selected>Select a backup</option>';
                        backups.forEach(backup => {
                            const option = document.createElement('option');
                            option.value = backup.filename;
                            option.textContent = backup.filename;
                            restoreSelect.appendChild(option);
                        });
                    }

                    attachDeleteHandlers();
                })
                .catch(() => showToast('error', 'Failed to refresh backup list.'));
        }

        function attachDeleteHandlers() {
            document.querySelectorAll('[data-delete]').forEach(btn => {
                btn.addEventListener('click', () => {
                    const filename = btn.getAttribute('data-delete');

                    Swal.fire({
                        title: 'Delete Backup?',
                        text: `This will permanently remove ${filename}.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc2626',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Yes, delete it',
                        cancelButtonText: 'Cancel',
                    }).then(result => {
                        if (!result.isConfirmed) {
                            return;
                        }

                        Swal.fire({
                            title: 'Deleting backup...',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });

                        fetch(`{{ url('/admin/backup/delete') }}/${filename}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                            .then(response => response.json())
                            .then(data => {
                                Swal.close();

                                if (data.success) {
                                    showToast('success', data.message || 'Backup deleted.');
                                    refreshBackupTable();
                                } else {
                                    showToast('error', data.error || 'Failed to delete backup.');
                                }
                            })
                            .catch(() => {
                                Swal.close();
                                showToast('error', 'An error occurred while deleting the backup.');
                            });
                    });
                });
            });
        }

        attachDeleteHandlers();

        if (manualBackupBtn) {
            manualBackupBtn.addEventListener('click', () => {
                // SweetAlert confirmation for backup creation
                Swal.fire({
                    title: 'Create Database Backup?',
                    html: `
                        <div class="text-left">
                            <p class="mb-3">This will create a complete backup of the database including:</p>
                            <ul class="text-sm text-gray-600 mb-3">
                                <li>Equipment records and history</li>
                                <li>User accounts and permissions</li>
                                <li>System settings and configurations</li>
                                <li>All related data</li>
                            </ul>
                            <p class="text-sm text-blue-600">
                                <i class="fas fa-info-circle"></i> The process may take a few minutes depending on data size.
                            </p>
                        </div>
                    `,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: '<i class="fas fa-download mr-2"></i>Create Backup',
                    cancelButtonText: 'Cancel',
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return fetch('{{ route('admin.backup.create') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'X-SweetAlert': 'true'
                            }
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .catch(error => {
                            Swal.showValidationMessage(`Request failed: ${error.message}`);
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (result.value.success) {
                            Swal.fire({
                                title: 'Backup Created Successfully!',
                                html: `
                                    <div class="text-left">
                                        <p class="mb-2"><i class="fas fa-check-circle text-green-500 mr-2"></i>Database backup completed successfully.</p>
                                        <div class="bg-gray-50 p-3 rounded text-sm">
                                            <strong>Backup Details:</strong><br>
                                            File: ${result.value.filename || 'N/A'}<br>
                                            Size: ${result.value.size_human || 'N/A'}<br>
                                            Created: ${new Date().toLocaleString()}
                                        </div>
                                    </div>
                                `,
                                icon: 'success',
                                confirmButtonColor: '#10b981',
                                confirmButtonText: '<i class="fas fa-check mr-2"></i>Great!'
                            });
                            refreshBackupTable();
                        } else {
                            Swal.fire({
                                title: 'Backup Failed!',
                                html: `
                                    <div class="text-left">
                                        <p class="mb-2"><i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>Failed to create database backup.</p>
                                        <p class="text-sm text-gray-600">${result.value.message || 'An unknown error occurred.'}</p>
                                    </div>
                                `,
                                icon: 'error',
                                confirmButtonColor: '#ef4444',
                                confirmButtonText: '<i class="fas fa-times mr-2"></i>Understood'
                            });
                        }
                    }
                });
            });
        }

        if (restoreBtn) {
            restoreBtn.addEventListener('click', () => {
                const filename = restoreSelect.value;
                if (!filename) {
                    Swal.fire({
                        title: 'No Backup Selected',
                        html: `
                            <div class="text-left">
                                <p class="mb-3"><i class="fas fa-exclamation-circle text-yellow-500 mr-2"></i>Please select a backup file from the dropdown list before proceeding.</p>
                                <p class="text-sm text-gray-600">Choose a backup from the "Select a backup" dropdown above.</p>
                            </div>
                        `,
                        icon: 'warning',
                        confirmButtonColor: '#f59e0b',
                        confirmButtonText: '<i class="fas fa-check mr-2"></i>I Understand'
                    });
                    return;
                }

                // SweetAlert confirmation for restore
                Swal.fire({
                    title: '⚠️ DANGER: Restore Database?',
                    html: `
                        <div class="text-left">
                            <div class="bg-red-50 border border-red-200 p-3 rounded mb-3">
                                <p class="text-red-800 font-semibold mb-2">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>WARNING: This action is irreversible!
                                </p>
                                <p class="text-red-700 text-sm">
                                    Restoring will <strong>PERMANENTLY OVERWRITE</strong> all current data with the backup from:
                                </p>
                                <p class="bg-white p-2 rounded text-sm font-mono mt-2">${filename}</p>
                            </div>
                            
                            <p class="mb-3"><strong>This will overwrite:</strong></p>
                            <ul class="text-sm text-gray-600 mb-3">
                                <li>✓ All equipment records and maintenance history</li>
                                <li>✓ User accounts, roles, and permissions</li>
                                <li>✓ System settings and configurations</li>
                                <li>✓ All current data in the database</li>
                            </ul>
                            
                            <div class="bg-yellow-50 border border-yellow-200 p-3 rounded">
                                <p class="text-yellow-800 text-sm">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    <strong>Recommendation:</strong> Create a backup of the current data before restoring.
                                </p>
                            </div>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: '<i class="fas fa-history mr-2"></i>Yes, Restore Anyway',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true,
                    showLoaderOnConfirm: true,
                    preConfirm: () => {
                        return fetch(`{{ route('admin.backup.restore') }}`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-SweetAlert': 'true'
                            },
                            body: JSON.stringify({ filename })
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .catch(error => {
                            Swal.showValidationMessage(`Request failed: ${error.message}`);
                        });
                    },
                    allowOutsideClick: () => !Swal.isLoading()
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (result.value.success) {
                            Swal.fire({
                                title: 'Database Restored Successfully!',
                                html: `
                                    <div class="text-left">
                                        <p class="mb-3"><i class="fas fa-check-circle text-green-500 mr-2"></i>Database has been restored from backup.</p>
                                        <div class="bg-green-50 border border-green-200 p-3 rounded mb-3">
                                            <p class="text-green-800 text-sm">
                                                <strong>Restoration Details:</strong><br>
                                                Source: ${filename}<br>
                                                Completed: ${new Date().toLocaleString()}
                                            </p>
                                        </div>
                                        <div class="bg-blue-50 border border-blue-200 p-3 rounded">
                                            <p class="text-blue-800 text-sm">
                                                <i class="fas fa-info-circle mr-2"></i>
                                                <strong>Next Steps:</strong><br>
                                                • Refresh the page to see restored data<br>
                                                • Verify all data is correct<br>
                                                • Create a new backup if needed
                                            </p>
                                        </div>
                                    </div>
                                `,
                                icon: 'success',
                                confirmButtonColor: '#10b981',
                                confirmButtonText: '<i class="fas fa-check mr-2"></i>Got it!',
                                allowOutsideClick: false
                            }).then(() => {
                                // Optionally refresh the page after success
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            });
                        } else {
                            Swal.fire({
                                title: 'Restore Failed!',
                                html: `
                                    <div class="text-left">
                                        <p class="mb-2"><i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>Failed to restore database from backup.</p>
                                        <div class="bg-red-50 border border-red-200 p-3 rounded">
                                            <p class="text-red-800 text-sm">
                                                <strong>Error Details:</strong><br>
                                                ${result.value.message || 'An unknown error occurred during restoration.'}
                                            </p>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-3">
                                            <i class="fas fa-lightbulb mr-1"></i>
                                            Try selecting a different backup file or contact support if the issue persists.
                                        </p>
                                    </div>
                                `,
                                icon: 'error',
                                confirmButtonColor: '#ef4444',
                                confirmButtonText: '<i class="fas fa-times mr-2"></i>Understood'
                            });
                        }
                    }
                });
            });
        }

        // Calculate next run display
        const nextRunEl = document.getElementById('backup-next-run');
        if (nextRunEl) {
            const days = nextRunEl.dataset.days?.split(',').filter(Boolean) || [];
            const time = nextRunEl.dataset.time || '02:00';

            if (days.length > 0) {
                const now = new Date();
                const [hour, minute] = time.split(':').map(Number);
                const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                let nextDate = new Date(now);

                for (let i = 0; i < 7; i++) {
                    nextDate = new Date(now);
                    nextDate.setDate(now.getDate() + i);
                    nextDate.setHours(hour, minute, 0, 0);
                    const weekday = dayNames[nextDate.getDay()];

                    if (days.includes(weekday) && nextDate > now) {
                        nextRunEl.textContent = nextDate.toLocaleString();
                        return;
                    }
                }

                // fallback if nothing found (shouldn't happen)
                nextRunEl.textContent = 'Pending';
            }
        }

        // Handle backup settings form submission
        const backupSettingsForm = document.querySelector('.backup-settings-form');
        if (backupSettingsForm) {
            backupSettingsForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Saving...';
                
                fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Settings Saved!',
                            html: `
                                <div class="text-left">
                                    <p class="mb-3"><i class="fas fa-check-circle text-green-500 mr-2"></i>Backup settings have been updated successfully.</p>
                                    <div class="bg-gray-50 p-3 rounded text-sm">
                                        <strong>Changes Applied:</strong><br>
                                        ${formData.get('backup_auto_enabled') === '1' ? '✓ Automatic backups enabled' : '✓ Automatic backups disabled'}<br>
                                        ${formData.get('backup_auto_time') ? `✓ Backup time set to ${formData.get('backup_auto_time')}` : ''}<br>
                                        ${formData.getAll('backup_auto_days[]').length > 0 ? `✓ Backup days: ${formData.getAll('backup_auto_days[]').map(d => d.charAt(0).toUpperCase() + d.slice(1)).join(', ')}` : ''}
                                    </div>
                                </div>
                            `,
                            icon: 'success',
                            confirmButtonColor: '#10b981',
                            confirmButtonText: '<i class="fas fa-check mr-2"></i>Great!'
                        }).then(() => {
                            // Optionally refresh the page to show updated status
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            html: `
                                <div class="text-left">
                                    <p class="mb-2"><i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>Failed to save backup settings.</p>
                                    <p class="text-sm text-gray-600">${data.message || 'An unknown error occurred.'}</p>
                                </div>
                            `,
                            icon: 'error',
                            confirmButtonColor: '#ef4444',
                            confirmButtonText: '<i class="fas fa-times mr-2"></i>Understood'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Network Error!',
                        html: `
                            <div class="text-left">
                                <p class="mb-2"><i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>A network error occurred while saving settings.</p>
                                <p class="text-sm text-gray-600">Please check your connection and try again.</p>
                            </div>
                        `,
                        icon: 'error',
                        confirmButtonColor: '#ef4444',
                        confirmButtonText: '<i class="fas fa-times mr-2"></i>Understood'
                    });
                })
                .finally(() => {
                    // Restore button state
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
            });
        }
    });
</script>

<script>
    // Tab switching functionality
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.settings-tab');
        const tabContents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const targetTab = this.getAttribute('data-tab');

                // Remove active class from all tabs
                tabs.forEach(t => t.classList.remove('active'));
                tabContents.forEach(tc => tc.classList.remove('active'));

                // Add active class to clicked tab and corresponding content
                this.classList.add('active');
                document.getElementById(targetTab + '-tab').classList.add('active');
            });
        });
    });
</script>
@endsection
