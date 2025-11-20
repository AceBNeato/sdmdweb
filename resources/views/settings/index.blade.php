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

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="settings-container">
        <!-- Settings Tabs -->
        <div class="settings-tabs">
            <div class="settings-tab active" data-tab="general">
                <i class="fas fa-cog mr-2"></i>General Settings
            </div>
            <div class="settings-tab" data-tab="system">
                <i class="fas fa-database mr-2"></i>System Management
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
                <div class="summary-card">
                    <div class="summary-value">{{ count($backupSettings['days'] ?? []) }}</div>
                    <div class="summary-label">Backup Days</div>
                </div>
                <div class="summary-card">
                    <div class="summary-value">{{ $backupSettings['enabled'] ? 'On' : 'Off' }}</div>
                    <div class="summary-label">Automation</div>
                </div>
                <div class="summary-card">
                    <div class="summary-value">{{ count($backups ?? []) }}</div>
                    <div class="summary-label">Backups</div>
                </div>
            </div>

            <!-- Settings Grid -->
            <div class="settings-grid">

                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                        <span class="font-semibold">{{ session('success') }}</span>
                    </div>
                @endif

                <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-6">
                    @csrf


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
            </div>

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

        <!-- System Management Tab -->
        <div id="system-tab" class="tab-content">
            <div class="row">
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
                            <a href="{{ route('admin.settings.system.equipment-types.index') }}" class="btn btn-success">
                                Manage Equipment Types
                            </a>
                        </div>
                    </div>
                </div>
            </div>

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
                            <div class="h3 text-info">{{ \App\Models\EquipmentType::count() }}</div>
                            <p class="text-muted mb-0">Total Equipment Types</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-backup-modal" class="modal-backdrop hidden">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Delete Backup</h3>
            <button type="button" class="modal-close" id="delete-cancel-btn">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <p class="text-gray-700 mb-6">
                Are you sure you want to delete <span id="delete-backup-filename" class="font-semibold"></span>?
            </p>
            <p class="text-sm text-gray-500">This action is permanent. The selected backup file will be removed.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" id="delete-cancel-btn">
                Cancel
            </button>
            <button type="button" class="btn btn-danger" id="delete-confirm-btn">
                Delete
            </button>
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
        const deleteModal = document.getElementById('delete-backup-modal');
        const deleteFilenameSpan = document.getElementById('delete-backup-filename');
        const deleteCancelBtn = document.getElementById('delete-cancel-btn');
        const deleteConfirmBtn = document.getElementById('delete-confirm-btn');
        let pendingDeleteFilename = null;

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

        function showModal(modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function hideModal(modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function refreshBackupTable() {
            fetch('{{ route('admin.backup.index') }}', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newTableBody = doc.getElementById('backup-table-body');
                    const newCount = doc.getElementById('backup-count');
                    const newOptions = doc.querySelectorAll('#restore-backup-select option');

                    if (newTableBody && newCount) {
                        document.getElementById('backup-table-body').innerHTML = newTableBody.innerHTML;
                        document.getElementById('backup-count').textContent = newCount.textContent;
                    }

                    if (newOptions) {
                        restoreSelect.innerHTML = '<option value="" disabled selected>Select a backup</option>';
                        newOptions.forEach((option, index) => {
                            if (index === 0 && option.value === '') return;
                            restoreSelect.appendChild(option.cloneNode(true));
                        });
                    }

                    attachDeleteHandlers();
                })
                .catch(() => showToast('error', 'Failed to refresh backup list.'));
        }

        function attachDeleteHandlers() {
            document.querySelectorAll('[data-delete]').forEach(btn => {
                btn.addEventListener('click', () => {
                    pendingDeleteFilename = btn.getAttribute('data-delete');
                    deleteFilenameSpan.textContent = pendingDeleteFilename;
                    showModal(deleteModal);
                });
            });
        }

        attachDeleteHandlers();

        if (manualBackupBtn) {
            manualBackupBtn.addEventListener('click', () => {
                setButtonLoading(manualBackupBtn, true, '<i class="fas fa-cloud-download-alt mr-2"></i> Create Backup', '<i class="fas fa-spinner fa-spin mr-2"></i> Creating...');

                fetch('{{ route('admin.backup.create') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('success', data.message);
                            refreshBackupTable();
                        } else {
                            showToast('error', data.message || 'Backup failed.');
                        }
                    })
                    .catch(() => showToast('error', 'An error occurred while creating the backup.'))
                    .finally(() => setButtonLoading(manualBackupBtn, false, '<i class="fas fa-cloud-download-alt mr-2"></i> Create Backup', '<i class="fas fa-spinner fa-spin mr-2"></i> Creating...'));
            });
        }

        if (restoreBtn) {
            restoreBtn.addEventListener('click', () => {
                const filename = restoreSelect.value;
                if (!filename) {
                    showToast('error', 'Please choose a backup file to restore.');
                    return;
                }

                if (!confirm('Restoring will overwrite all data. Continue?')) {
                    return;
                }

                setButtonLoading(restoreBtn, true, '<i class="fas fa-history mr-2"></i> Restore Selected Backup', '<i class="fas fa-spinner fa-spin mr-2"></i> Restoring...');

                fetch(`{{ route('admin.backup.restore') }}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ filename })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showToast('success', data.message);
                        } else {
                            showToast('error', data.message || 'Restore failed.');
                        }
                    })
                    .catch(() => showToast('error', 'An error occurred while restoring the database.'))
                    .finally(() => setButtonLoading(restoreBtn, false, '<i class="fas fa-history mr-2"></i> Restore Selected Backup', '<i class="fas fa-spinner fa-spin mr-2"></i> Restoring...'));
            });
        }

        deleteCancelBtn?.addEventListener('click', () => {
            hideModal(deleteModal);
            pendingDeleteFilename = null;
        });

        // Also add listener to modal close button
        document.querySelector('#delete-backup-modal .modal-close')?.addEventListener('click', () => {
            hideModal(deleteModal);
            pendingDeleteFilename = null;
        });

        deleteConfirmBtn?.addEventListener('click', () => {
            if (!pendingDeleteFilename) {
                return;
            }

            setButtonLoading(deleteConfirmBtn, true, 'Delete', '<i class="fas fa-spinner fa-spin mr-2"></i> Deleting...');

            fetch(`{{ url('/admin/backup/delete') }}/${pendingDeleteFilename}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('success', data.message || 'Backup deleted.');
                        refreshBackupTable();
                    } else {
                        showToast('error', data.error || 'Failed to delete backup.');
                    }
                })
                .catch(() => showToast('error', 'An error occurred while deleting the backup.'))
                .finally(() => {
                    setButtonLoading(deleteConfirmBtn, false, 'Delete', '<i class="fas fa-spinner fa-spin mr-2"></i> Deleting...');
                    hideModal(deleteModal);
                    pendingDeleteFilename = null;
                });
        });

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
