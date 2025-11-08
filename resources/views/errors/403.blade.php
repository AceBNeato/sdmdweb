<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="card shadow" style="max-width: 500px; width: 100%;">
            <div class="card-body text-center">
                <i class="bx bx-lock-alt" style="font-size: 3rem; color: #dc3545;"></i>
                <h4 class="mt-3">Access Denied</h4>
                <p>You do not have permission to view this page.</p>
                <div class="mt-4">
                    @php
                        $user = auth()->user();
                        $prefix = '';
                        if ($user->hasRole('technician')) {
                            $prefix = 'technician';
                        } elseif ($user->hasRole('staff')) {
                            $prefix = 'staff';
                        } elseif ($user->is_admin) {
                            $prefix = 'admin';
                        }
                        $hasEquipment = $user && $user->hasPermissionTo('equipment.view');
                        $hasReports = $user && $user->hasPermissionTo('reports.view');
                        $hasOffices = $user && $user->hasPermissionTo('settings.manage') && $prefix === 'admin';
                        $hasSystemLogs = $user && $user->hasPermissionTo('system.logs.view') && $prefix === 'admin';
                        $hasAccounts = $user && $user->hasPermissionTo('users.view');
                    @endphp
                    @if($hasEquipment)
                        <a href="{{ $prefix === 'admin' ? url('/admin/equipment') : route($prefix . '.equipment.index') }}" class="btn btn-primary me-2">Go to Equipment</a>
                    @endif
                    @if($hasReports)
                        <a href="{{ $prefix === 'admin' ? url('/admin/reports') : ($prefix === 'technician' ? route('technician.reports.index') : url('/admin/reports')) }}" class="btn btn-secondary me-2">Go to Reports</a>
                    @endif
                    @if($hasOffices)
                        <a href="{{ url('/admin/offices') }}" class="btn btn-info me-2">Go to Offices</a>
                    @endif
                    @if($hasSystemLogs)
                        <a href="{{ url('/admin/system-logs') }}" class="btn btn-warning me-2">Go to System Logs</a>
                    @endif
                    @if($hasAccounts)
                        <a href="{{ $prefix === 'admin' ? url('/admin/accounts') : url('/accounts') }}" class="btn btn-success me-2">Go to Accounts</a>
                    @endif
                    @if(!$hasEquipment && !$hasReports && !$hasOffices && !$hasSystemLogs && !$hasAccounts)
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                        <button type="button" onclick="document.getElementById('logout-form').submit();" class="btn btn-danger">Logout</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</body>
</html>
