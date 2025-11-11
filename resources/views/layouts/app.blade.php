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
    @stack('styles')
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
            pointer-events: none;
        }

        .toast {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            margin-bottom: 10px;
            min-width: 300px;
            max-width: 400px;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            pointer-events: auto;
            border-left: 4px solid;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .toast.show {
            opacity: 1;
            transform: translateX(0);
        }

        .toast-success {
            border-left-color: #28a745;
        }

        .toast-error {
            border-left-color: #dc3545;
        }

        .toast-warning {
            border-left-color: #ffc107;
        }

        .toast-content {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            gap: 10px;
            flex: 1;
        }

        .toast-content i {
            font-size: 20px;
            flex-shrink: 0;
        }

        .toast-success .toast-content i {
            color: #28a745;
        }

        .toast-error .toast-content i {
            color: #dc3545;
        }

        .toast-warning .toast-content i {
            color: #ffc107;
        }

        .toast-content span {
            flex: 1;
            font-size: 14px;
            line-height: 1.4;
        }

        .toast-close {
            background: none;
            border: none;
            padding: 12px 16px;
            cursor: pointer;
            color: #6c757d;
            font-size: 16px;
            transition: color 0.2s;
            flex-shrink: 0;
        }

        .toast-close:hover {
            color: #495057;
        }

        /* Profile Dropdown */
        .profile-dropdown {
            z-index: 100;
            position: absolute;
            display: flex;
            flex-wrap: wrap-reverse;
            bottom: 0px;
            left: 0;
            right: 0;
            padding: 20px 15px 20px 15px;
        }

        .profile-btn {
            width: 100%;
            max-width: 15rem;
            background: #2c3e50;
            border: none;
            color: white;
            padding: 12px 16px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .profile-btn:hover {
            background: #34495e;
        }

        .profile-btn i {
            font-size: 20px;
            flex-shrink: 0;
        }

        .profile-btn span {
            flex: 1;
            text-align: left;
            font-weight: 100;
        }

        .dropdown-arrow {
            transition: transform 0.3s ease;
            font-size: 16px;
        }

        .profile-btn.active .dropdown-arrow {
            transform: rotate(180deg);
        }

        .profile-menu {
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            margin-top: 8px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            position: relative;
            z-index: 1000;
            overflow: hidden;
        }

        .profile-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .profile-menu-item {
            width: 15rem;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            color: #333;
            text-decoration: none;
            transition: background 0.2s ease;
            font-size: 14px;
        }

        .profile-menu-item:hover {
            background: #f8f9fa;
            color: #007bff;
        }

        .profile-menu-item i {
            font-size: 16px;
            width: 16px;
            flex-shrink: 0;
        }

        .logout-item:hover {
            background: #ffe6e6;
            color: #dc3545;
        }

        .profile-menu-divider {
            height: 1px;
            background: #e9ecef;
            margin: 4px 0;
        }
    </style>
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
                <!-- QR Scanner - available to technicians and admins with permission -->
                @if($currentUser && $currentUser->hasPermissionTo('qr.scan'))
                @if($prefix === 'admin' || $prefix === 'technician')
                <a href="{{ route($prefix . '.qr-scanner') }}" class="{{ Route::currentRouteName() && str_starts_with(Route::currentRouteName(), 'admin.qr-scanner') ? 'active' : '' }}">
                    <i class='bx bx-qr-scan'></i>QR Scan
                </a>
                @endif
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

    <!-- Session Lock Modal - Outside app container to cover everything -->
    <div id="session-lock-modal" class="session-lock-overlay" style="display: none;">
        <div class="session-lock-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Session Locked</h5>
                </div>
                <div class="modal-body">
                    <p>Your session has been locked due to inactivity. Please enter your password to continue.</p>
                    <form id="unlock-form">
                        <div class="mb-3">
                            <label for="unlock-password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="unlock-password" required>
                        </div>
                        <div id="unlock-error" class="alert alert-danger d-none"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <a href="#" class="btn btn-secondary" onclick="event.preventDefault(); if(!this.hasAttribute('disabled')){ this.setAttribute('disabled','disabled'); this.style.pointerEvents='none'; document.getElementById('logout-form').submit(); }"><i class='bx bx-log-out'></i> Logout </a>
                        <button type="button" class="btn btn-primary" id="unlock-btn">Unlock</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Aggressive back button prevention for SDMD
        (function() {
            'use strict';

            // Clear all caches immediately
            if ('caches' in window) {
                caches.keys().then(function(names) {
                    for (let name of names) {
                        caches.delete(name);
                    }
                });
            }

            @if(!auth('technician')->check() && !auth('staff')->check() && !auth()->check())
                // Force redirect to login and prevent any back navigation
                window.history.replaceState(null, null, '{{ url('/login') }}');
                window.location.replace('{{ url('/login') }}');
                return;
            @else
                // For authenticated users - prevent back button completely
                window.history.replaceState({page: 'authenticated'}, document.title, window.location.href);
                window.history.pushState({page: 'authenticated'}, document.title, window.location.href);

                // Override back button behavior
                window.addEventListener('popstate', function(event) {
                    // Immediately redirect to current page to prevent back navigation
                    window.history.replaceState({page: 'authenticated'}, document.title, window.location.href);
                    window.history.pushState({page: 'authenticated'}, document.title, window.location.href);
                });

                // Check authentication every 5 seconds
                setInterval(function() {
                    fetch(window.location.href, {
                        method: 'HEAD',
                        headers: { 'X-Requested-With': 'XMLHttpRequest' },
                        cache: 'no-cache'
                    }).then(function(response) {
                        if (response.status === 401 || response.status === 419) {
                            window.location.replace('{{ url('/login') }}');
                        }
                    }).catch(function() {
                        window.location.replace('{{ url('/login') }}');
                    });
                }, 5000);
            @endif
        })();

        // Additional aggressive cache prevention
        window.addEventListener('beforeunload', function() {
            // Clear any cached data
            if ('caches' in window) {
                caches.keys().then(function(names) {
                    for (let name of names) caches.delete(name);
                });
            }
        });

        // Prevent page caching on load
        if (window.performance && window.performance.navigation.type === window.performance.navigation.TYPE_BACK_FORWARD) {
            @if(!auth('technician')->check() && !auth('staff')->check() && !auth()->check())
                window.location.replace('{{ url('/login') }}');
            @endif
        }

        // Toast notification system
        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toast-container');
            if (!toastContainer) return;

            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <div class="toast-content">
                    <i class="bx bx-${type === 'success' ? 'check-circle' : 'error-circle'}"></i>
                    <span>${message}</span>
                </div>
                <button class="toast-close" onclick="this.parentElement.remove()">
                    <i class="bx bx-x"></i>
                </button>
            `;

            toastContainer.appendChild(toast);

            // Auto remove after 5 seconds
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 5000);

            // Animate in
            setTimeout(() => {
                toast.classList.add('show');
            }, 10);
        }

        // Show toasts from session messages
        document.addEventListener('DOMContentLoaded', function() {
            @if(session('success'))
                showToast('{{ session('success') }}', 'success');
            @endif
            @if(session('error'))
                showToast('{{ session('error') }}', 'error');
            @endif
            @if(session('warning'))
                showToast('{{ session('warning') }}', 'warning');
            @endif
        });

        // Profile dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            const profileBtn = document.getElementById('profileDropdownBtn');
            const profileMenu = document.getElementById('profileDropdownMenu');

            if (profileBtn && profileMenu) {
                profileBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const isActive = profileBtn.classList.contains('active');
                    
                    if (isActive) {
                        profileBtn.classList.remove('active');
                        profileMenu.classList.remove('show');
                    } else {
                        profileBtn.classList.add('active');
                        profileMenu.classList.add('show');
                    }
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!profileBtn.contains(e.target) && !profileMenu.contains(e.target)) {
                        profileBtn.classList.remove('active');
                        profileMenu.classList.remove('show');
                    }
                });

                // Close dropdown when pressing Escape
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        profileBtn.classList.remove('active');
                        profileMenu.classList.remove('show');
                    }
                });
            }

            // Hamburger menu toggle functionality
            const menuToggle = document.getElementById('menuToggle');
            const app = document.getElementById('appRoot');
            const backdrop = document.querySelector('.backdrop');

            if (menuToggle && app && backdrop) {
                menuToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    app.classList.toggle('menu-open');
                });

                // Close menu when clicking backdrop
                backdrop.addEventListener('click', function() {
                    app.classList.remove('menu-open');
                });

                // Close menu when pressing Escape
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        app.classList.remove('menu-open');
                    }
                });
            }
        });
    </script>

    @if(auth('technician')->check() || auth('staff')->check() || auth()->check())
    <script>
        // Pass session data to JavaScript
        window.sessionData = {
            lockoutTimeoutMinutes: {{ \App\Models\Setting::getSessionLockoutMinutes() }},
            timeoutTimeoutMinutes: {{ \App\Models\Setting::getSessionTimeoutMinutes() }},
            unlockUrl: '@if(auth()->guard("technician")->check()){{ route("technician.unlock.session") }}@elseif(auth()->guard("staff")->check()){{ route("staff.unlock.session") }}@else{{ route("unlock.session") }}@endif'
        };
    </script>
    <script src="{{ asset('js/session-lock.js') }}"></script>
    @endif

    <script>
        // Profile modals: open and submit via AJAX
        (function($){
            $(document).on('click', '.open-profile-modal', function(e){
                e.preventDefault();
                var url = $(this).data('url');
                if (!url) return;
                $('#viewProfileContent').html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
                $.ajax({
                    url: url,
                    type: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function(html){
                        $('#viewProfileContent').html(html);
                        var modal = new bootstrap.Modal(document.getElementById('viewProfileModal'));
                        modal.show();
                    },
                    error: function(xhr){
                        $('#viewProfileContent').html('<div class="alert alert-danger">Failed to load profile. Error: '+xhr.status+'</div>');
                        var modal = new bootstrap.Modal(document.getElementById('viewProfileModal'));
                        modal.show();
                    }
                });
            });

            $(document).on('click', '.open-edit-profile-modal', function(e){
                e.preventDefault();
                var url = $(this).data('url');
                if (!url) return;
                $('#editProfileContent').html('<div class="text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');
                // Hide view modal if open
                var viewEl = document.getElementById('viewProfileModal');
                if (viewEl) {
                    var viewInstance = bootstrap.Modal.getInstance(viewEl);
                    if (viewInstance) viewInstance.hide();
                }
                $.ajax({
                    url: url,
                    type: 'GET',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function(html){
                        $('#editProfileContent').html(html);
                        var modal = new bootstrap.Modal(document.getElementById('editProfileModal'));
                        modal.show();
                    },
                    error: function(xhr){
                        $('#editProfileContent').html('<div class="alert alert-danger">Failed to load edit form. Error: '+xhr.status+'</div>');
                        var modal = new bootstrap.Modal(document.getElementById('editProfileModal'));
                        modal.show();
                    }
                });
            });

            $(document).on('submit', '#editProfileModal form', function(e){
                e.preventDefault();
                var form = this;
                var formData = new FormData(form);
                var method = $(form).attr('method') || 'POST';
                var action = form.action;
                $.ajax({
                    url: action,
                    type: method,
                    data: formData,
                    processData: false,
                    contentType: false,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    success: function(resp){
                        var editEl = document.getElementById('editProfileModal');
                        var editInstance = bootstrap.Modal.getInstance(editEl);
                        if (editInstance) editInstance.hide();
                        if (resp && resp.redirect) {
                            window.location.href = resp.redirect;
                        } else {
                            window.location.reload();
                        }
                    },
                    error: function(xhr){
                        if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                            var errors = xhr.responseJSON.errors;
                            var html = '<div class="alert alert-danger"><ul class="mb-0">';
                            for (var k in errors) { html += '<li>' + errors[k][0] + '</li>'; }
                            html += '</ul></div>';
                            $('#editProfileContent').prepend(html);
                        } else {
                            $('#editProfileContent').prepend('<div class="alert alert-danger">Failed to save profile. Please try again.</div>');
                        }
                    }
                });
            });
        })(jQuery);
    </script>
</body>
</html>
