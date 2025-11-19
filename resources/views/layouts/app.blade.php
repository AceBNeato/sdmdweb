<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Cache Control Headers to prevent back button access -->
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>@yield('title', auth('technician')->check() || auth('staff')->check() || auth()->check() ? ((auth('technician')->user() ?? auth('staff')->user() ?? auth()->user())->is_admin ? 'SDMD Admin' : ((auth('technician')->user() ?? auth('staff')->user() ?? auth()->user())->hasRole('technician') ? 'SDMD Technician' : ((auth('technician')->user() ?? auth('staff')->user() ?? auth()->user())->hasRole('staff') ? 'SDMD Staff' : 'SDMD'))) : 'SDMD Login')</title>
    <link rel="icon" href="{{ asset('images/SDMDlogo.png') }}" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:whttp://127.0.0.1:8000/accountsght@100..900&family=Montserrat:wght@100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/session-lock.css') }}" rel="stylesheet">
    <link href="{{ asset('css/profile-modal.css') }}" rel="stylesheet">
    <link href="{{ asset('css/animations.css') }}" rel="stylesheet">
    <link href="{{ asset('css/toast.css') }}" rel="stylesheet">
    <link href="{{ asset('css/profile-dropdown.css') }}" rel="stylesheet">
    @stack('styles')
</head>

<body>
    <div class="app" id="appRoot">
        @php
            // Determine the correct prefix primarily from the current route or path to avoid cross-guard collisions
            $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
            $path = request()->path();

            $prefix = 'admin';
            $currentUser = auth()->user();

            if (($routeName && str_starts_with($routeName, 'admin.')) || str_starts_with($path, 'admin')) {
                $prefix = 'admin';
                $currentUser = auth()->user();
            } elseif (($routeName && str_starts_with($routeName, 'technician.')) || str_starts_with($path, 'technician')) {
                $prefix = 'technician';
                $currentUser = auth('technician')->user();
            } elseif (($routeName && str_starts_with($routeName, 'staff.')) || str_starts_with($path, 'staff')) {
                $prefix = 'staff';
                $currentUser = auth('staff')->user();
            } elseif (auth('technician')->check()) {
                $prefix = 'technician';
                $currentUser = auth('technician')->user();
            } elseif (auth('staff')->check()) {
                $prefix = 'staff';
                $currentUser = auth('staff')->user();
            } else {
                $prefix = 'admin';
                $currentUser = auth()->user();
            }

            // Guard-aware profile URL
            $profileUrl = match ($prefix) {
                'technician' => route('technician.profile'),
                'staff' => route('staff.profile'),
                default => route('admin.profile'),
            };

            // Modal endpoint for profile for all guards
            $profileModalUrl = match ($prefix) {
                'technician' => url('/technician?modal=1'),
                'staff' => url('/staff/profile?modal=1'),
                default => url('/admin/profile?modal=1'),
            };
        @endphp
        <aside class="sidebar">
            <a class="brand" href="javascript:window.location.reload();">
                <img src="{{ asset('images/SDMDlogo.png') }}" alt="SDMD logo">
                <div class="tt">
                    <p class="user-info">{{  (auth('technician')->user() ?? auth('staff')->user() ?? auth()->user())?->roles?->first()?->name ?? 'No Role' }}</p></div>
            </a>
        <hr>

            <nav class="menu">
                <!-- QR Scanner - available to roles with permission -->
                @if($currentUser && $currentUser->hasPermissionTo('qr.scan') && in_array($prefix, ['admin', 'technician', 'staff']))
                <a href="{{ route($prefix . '.qr-scanner') }}" class="{{ Route::currentRouteName() === $prefix . '.qr-scanner' ? 'active' : '' }}">
                    <i class='bx bx-qr-scan'></i>QR Scan
                </a>
                @endif

                <!-- Accounts - only for admin -->
                @if($currentUser && $currentUser->hasPermissionTo('users.view'))
                @if($prefix === 'admin')
                <a href="{{ route('admin.accounts.index') }}" class="{{ Route::currentRouteName() && str_starts_with(Route::currentRouteName(), 'admin.accounts') ? 'active' : '' }}">
                    <i class='bx bx-user'></i>Accounts
                </a>
                @endif
                @endif

                <!-- Offices - only for admin -->
                @if($currentUser && $currentUser->hasPermissionTo('settings.manage'))
                @if($prefix === 'admin')
                <a href="{{ route('admin.offices.index') }}" class="{{ Route::currentRouteName() && str_starts_with(Route::currentRouteName(), 'admin.offices') ? 'active' : '' }}">
                    <i class='bx bx-building'></i>Office
                </a>
                @endif
                @endif

                <!-- Equipment - available to all user types -->
                @if($currentUser && $currentUser->hasPermissionTo('equipment.view'))
                <a href="{{ route($prefix . '.equipment.index') }}" class="{{ Route::currentRouteName() && str_starts_with(Route::currentRouteName(), 'admin.equipment') || request()->routeIs('technician.equipment.*') || request()->routeIs('staff.equipment.*') ? 'active' : '' }}">
                    <i class='bx bx-cube'></i>Equipments
                </a>
                @endif

                <!-- Reports - available to all user types -->
                @if($currentUser && $currentUser->hasPermissionTo('reports.view') && Route::has($prefix . '.reports.index'))
                <a href="{{ route($prefix . '.reports.index') }}" class="{{ Route::currentRouteName() && str_starts_with(Route::currentRouteName(), 'admin.reports') || request()->routeIs('technician.reports.*') || request()->routeIs('staff.reports.*') ? 'active' : '' }}">
                    <i class='bx bx-bar-chart-alt-2'></i>Reports
                </a>
                @endif

                <!-- System Logs - only for admin -->
                @if($currentUser && $currentUser->hasPermissionTo('system.logs.view'))
                @if($prefix === 'admin')
                <a href="{{ route('admin.system-logs.index') }}" class="{{ Route::currentRouteName() && str_starts_with(Route::currentRouteName(), 'admin.system-logs') ? 'active' : '' }}">
                    <i class='bx bx-file-find'></i>System Logs
                </a>
                @endif
                @endif

                <!-- Settings - only for admin -->
                @if($currentUser && $currentUser->hasPermissionTo('settings.manage'))
                @if($prefix === 'admin')
                <a href="{{ route('admin.settings.index') }}" class="{{ Route::currentRouteName() && str_starts_with(Route::currentRouteName(), 'admin.settings') ? 'active' : '' }}">
                    <i class='bx bx-cog'></i>Settings
                </a>
                @endif
                @endif
            </nav>

            <!-- Profile Dropdown at bottom -->
            <div class="profile-dropdown">
                <button class="profile-btn" id="profileDropdownBtn">
                    <i class='bx bx-user-circle'></i>
                    <span>{{ (auth('technician')->user() ?? auth('staff')->user() ?? auth()->user())->first_name . ' ' . (auth('technician')->user() ?? auth('staff')->user() ?? auth()->user())->last_name }}</span>
                    <i class='bx bx-chevron-up dropdown-arrow'></i>
                </button>
                <div class="profile-menu" id="profileDropdownMenu">
                    <a href="#" class="profile-menu-item open-profile-modal" data-url="{{ $profileModalUrl }}">
                        <i class='bx bx-user'></i> My Profile
                    </a>
                    <div class="profile-menu-divider"></div>
                    <a href="#" onclick="event.preventDefault(); if(!this.hasAttribute('disabled')){ this.setAttribute('disabled','disabled'); this.style.pointerEvents='none'; document.getElementById('logout-form').submit(); }" class="profile-menu-item logout-item">
                        <i class='bx bx-log-out'></i> Logout
                    </a>
                </div>
            </div>
            <form id="logout-form" action="{{ $prefix === 'admin' ? url('/admin/logout') : ($prefix === 'technician' ? url('/technician/logout') : url('/staff/logout')) }}" method="POST" style="display: none;">
                @csrf
            </form>
        </aside>

        <div class="main">
            <div class="topbar">
                <div class="container">
                    <div class="row-between">
                        
                        <div class="text-right">
                            <h1 class="fade-in">@yield('page_title')</h1>
                            <p class="text-muted mt-2 fade-in">@yield('page_description')</p>
                            <button class="hamburger" id="menuToggle" aria-label="Toggle menu"><i class='bx bx-menu'></i></button>
                        </div>

                        <div class="actions">
                            @yield('header_actions')
                        </div>
                        <div>
                            <nav class="breadcrumbs" aria-label="breadcrumb">
                                @yield('breadcrumbs')
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            <div class="container mt-16">
                <div class="card">
                    @yield('content')
                </div>
            </div>
            <footer class="footer">
                Copyright 2025. All Rights Reserved.
            </footer>
        </div>
        <div class="backdrop" id="backdrop"></div>
    </div>

    <!-- Global Profile Modals -->
    <div class="modal fade" id="viewProfileModal" tabindex="-1" aria-labelledby="viewProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewProfileModalLabel">My Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="viewProfileContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editProfileModalLabel">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="editProfileContent">
                    <div class="text-center">
                        <div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notifications Container -->
    <div id="toast-container" class="toast-container"></div>

    <!-- Modals Stack -->
    @stack('modals')

    <!-- jQuery and Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    @stack('scripts')

    @include('layouts.session-lock-modal')

    @php
        $isTechnician = auth('technician')->check();
        $isStaff = auth('staff')->check();
        $isAdmin = auth()->check();
        $isAuthenticated = $isTechnician || $isStaff || $isAdmin;
        $sessionUnlockUrl = $isTechnician
            ? route('technician.unlock.session')
            : ($isStaff ? route('staff.unlock.session') : route('unlock.session'));

        $sessionData = $isAuthenticated ? [
            'lockoutTimeoutMinutes' => \App\Models\Setting::getSessionLockoutMinutes(),
            'timeoutTimeoutMinutes' => \App\Models\Setting::getSessionTimeoutMinutes(),
            'unlockUrl' => $sessionUnlockUrl,
        ] : null;
    @endphp

    <!-- Back Button Prevention and Authentication Script -->
    <script>
        // Set global authentication variables for external scripts
        window.isAuthenticated = {!! json_encode($isAuthenticated) !!};
        window.loginUrl = {!! json_encode(url('/login')) !!};
    </script>
    <script src="{{ asset('js/auth-prevention.js') }}"></script>

    <!-- Toast Notification System -->
    <script>
        // Set global session messages for toast system
        window.sessionMessages = {!! json_encode([
            'success' => session('success'),
            'error' => session('error'),
            'warning' => session('warning'),
            'info' => session('info')
        ]) !!};
    </script>
    <script src="{{ asset('js/toast-system.js') }}"></script>
    <script src="{{ asset('js/ui-functionality.js') }}"></script>

    @if($isAuthenticated)
        <script>
            // Pass session data to JavaScript
            window.sessionData = {!! json_encode($sessionData) !!};
        </script>
        <script src="{{ asset('js/session-lock.js') }}"></script>
    @endif

    <script src="{{ asset('js/profile-modals.js') }}"></script>
</body>
</html>
