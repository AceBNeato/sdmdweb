@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex items-center mb-6">
                <div class="text-green-600 text-2xl mr-3">
                    <i class="fas fa-database"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Database Backup & Restore</h1>
            </div>

            <!-- Messages Container -->
            <div id="messages-container" class="mb-6"></div>

            <!-- Backup Section -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Create Backup</h2>
                <div class="bg-blue-50 rounded-lg p-6">
                    <p class="text-gray-600 mb-4">
                        Create a backup of the current database. This will generate a SQL file that can be downloaded and used to restore the database if needed.
                    </p>

                    <button type="button" class="btn btn-primary" id="backup-btn" onclick="createBackup()">
                        <i class="fas fa-download mr-2"></i>
                        Create Database Backup
                    </button>
                </div>
            </div>

            <!-- Restore Section -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Restore Database</h2>
                <div class="bg-yellow-50 rounded-lg p-6">
                    <div class="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded mb-4">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle mr-2"></i>
                            <strong>Warning:</strong> Restoring a database will overwrite all current data. Make sure you have a recent backup before proceeding.
                        </div>
                    </div>

                    <p class="text-gray-600 mb-4">
                        Upload a SQL backup file to restore the database. Only .sql files are accepted.
                    </p>

                    <div class="mb-4">
                        <label for="backup_file" class="block text-sm font-medium text-gray-700 mb-2">
                            Backup File
                        </label>
                        <input
                            type="file"
                            id="backup_file"
                            name="backup_file"
                            accept=".sql"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        >
                        <p class="text-xs text-gray-500 mt-1">Maximum file size: 50MB</p>
                    </div>

                    <button type="button" class="btn btn-danger" id="restore-btn" onclick="restoreDatabase()">
                        <i class="fas fa-upload mr-2"></i>
                        Restore Database
                    </button>
                </div>
            </div>

            <!-- Backup History -->
            <div>
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Backup History</h2>
                <div class="bg-gray-50 rounded-lg p-6">
                    @if(count($backups) > 0)
                        <div class="overflow-x-auto">
                            <table class="min-w-full table-auto" id="backup-table">
                                <thead>
                                    <tr class="bg-gray-200">
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Filename</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200" id="backup-table-body">
                                    @foreach($backups as $backup)
                                        <tr data-filename="{{ $backup['filename'] }}">
                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                                {{ $backup['filename'] }}
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">
                                                {{ $backup['size_human'] }}
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-500">
                                                {{ $backup['created_at'] }}
                                            </td>
                                            <td class="px-4 py-2 whitespace-nowrap text-sm font-medium">
                                                <a href="{{ route('admin.backup.download', $backup['filename']) }}"
                                                   class="text-blue-600 hover:text-blue-900 mr-3 download-link">
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                                <button onclick="deleteBackup('{{ $backup['filename'] }}')"
                                                        class="text-red-600 hover:text-red-900 delete-btn">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-8">
                            <i class="fas fa-database text-4xl mb-4"></i><br>
                            No backups found. Create your first backup above.
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Backup Modal -->
<div id="delete-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <i class="fas fa-exclamation-triangle text-red-600"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Delete Backup</h3>
            <p class="text-sm text-gray-500 mb-4">
                Are you sure you want to delete this backup file? This action cannot be undone.
            </p>
            <div class="flex justify-center space-x-4">
                <button id="cancel-delete" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">
                    Cancel
                </button>
                <button id="confirm-delete" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                    Delete
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// CSRF token for AJAX requests
const csrfToken = '{{ csrf_token() }}';

// Utility functions
function showMessage(type, message) {
    const container = document.getElementById('messages-container');
    const alertClass = type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700';
    const iconClass = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';

    container.innerHTML = `
        <div class="${alertClass} border px-4 py-3 rounded">
            <div class="flex items-center">
                <i class="${iconClass} mr-2"></i>
                <span class="font-semibold">${message}</span>
            </div>
        </div>
    `;

    // Auto-hide after 5 seconds
    setTimeout(() => {
        container.innerHTML = '';
    }, 5000);
}

function setButtonLoading(button, loading, text) {
    button.disabled = loading;
    if (loading) {
        button.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i> ${text}`;
    } else {
        button.innerHTML = text;
    }
}

function refreshBackupTable() {
    fetch(window.location.href, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'
        }
    })
    .then(response => response.text())
    .then(html => {
        const parser = new DOMParser();
        const newDoc = parser.parseFromString(html, 'text/html');
        const newTableBody = newDoc.getElementById('backup-table-body');

        if (newTableBody) {
            document.getElementById('backup-table-body').innerHTML = newTableBody.innerHTML;
        }
    })
    .catch(error => {
        console.error('Failed to refresh backup table:', error);
    });
}

// Create backup function
function createBackup() {
    const button = document.getElementById('backup-btn');
    setButtonLoading(button, true, 'Creating Backup...');

    fetch('{{ route("admin.backup.create") }}', {
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
            showMessage('success', data.message);
            refreshBackupTable();
        } else {
            showMessage('error', data.message);
        }
    })
    .catch(error => {
        showMessage('error', 'An error occurred while creating the backup.');
        console.error('Backup error:', error);
    })
    .finally(() => {
        setButtonLoading(button, false, '<i class="fas fa-download mr-2"></i> Create Database Backup');
    });
}

// Restore database function
function restoreDatabase() {
    const fileInput = document.getElementById('backup_file');
    const button = document.getElementById('restore-btn');

    if (!fileInput.files[0]) {
        showMessage('error', 'Please select a backup file to restore.');
        return;
    }

    if (!confirm('Are you sure you want to restore the database? This will overwrite all current data.')) {
        return;
    }

    setButtonLoading(button, true, 'Restoring Database...');

    const formData = new FormData();
    formData.append('backup_file', fileInput.files[0]);
    formData.append('_token', csrfToken);

    fetch('{{ route("admin.backup.restore") }}', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showMessage('success', data.message);
            fileInput.value = ''; // Clear file input
            refreshBackupTable();
        } else {
            showMessage('error', data.message);
        }
    })
    .catch(error => {
        showMessage('error', 'An error occurred while restoring the database.');
        console.error('Restore error:', error);
    })
    .finally(() => {
        setButtonLoading(button, false, '<i class="fas fa-upload mr-2"></i> Restore Database');
    });
}

// Delete backup functions
let deleteFilename = '';

function deleteBackup(filename) {
    deleteFilename = filename;
    document.getElementById('delete-modal').classList.remove('hidden');
}

document.getElementById('cancel-delete').addEventListener('click', function() {
    document.getElementById('delete-modal').classList.add('hidden');
    deleteFilename = '';
});

document.getElementById('confirm-delete').addEventListener('click', function() {
    if (deleteFilename) {
        deleteBackupFile(deleteFilename);
    }
});

function deleteBackupFile(filename) {
    const button = document.getElementById('confirm-delete');
    const originalText = button.innerHTML;
    setButtonLoading(button, true, 'Deleting...');

    fetch(`{{ url('/admin/backup/delete') }}/${filename}`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('delete-modal').classList.add('hidden');

        if (data.success) {
            showMessage('success', 'Backup deleted successfully');
            refreshBackupTable();
        } else {
            showMessage('error', data.error || 'Failed to delete backup');
        }
    })
    .catch(error => {
        document.getElementById('delete-modal').classList.add('hidden');
        showMessage('error', 'An error occurred while deleting the backup.');
        console.error('Delete error:', error);
    })
    .finally(() => {
        setButtonLoading(button, false, originalText);
        deleteFilename = '';
    });
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Add loading states to download links
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('download-link') || e.target.closest('.download-link')) {
            const link = e.target.classList.contains('download-link') ? e.target : e.target.closest('.download-link');
            const originalText = link.innerHTML;
            link.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Downloading...';
            link.style.pointerEvents = 'none';

            // Reset after a short delay (download should start)
            setTimeout(() => {
                link.innerHTML = originalText;
                link.style.pointerEvents = 'auto';
            }, 2000);
        }
    });
});
</script>
@endsection
