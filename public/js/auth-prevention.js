// Back Button Prevention and Authentication Script
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

    // Check authentication status (this will be handled by PHP in the layout)
    window.checkAuthentication = function() {
        if (window.isAuthenticated === false) {
            // Force redirect to login and prevent any back navigation
            window.history.replaceState(null, null, window.loginUrl);
            window.location.replace(window.loginUrl);
            return;
        } else {
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
                        window.location.replace(window.loginUrl);
                    }
                }).catch(function() {
                    window.location.replace(window.loginUrl);
                });
            }, 5000);
        }
    };

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
        if (window.isAuthenticated === false) {
            window.location.replace(window.loginUrl);
        }
    }

    // Initialize authentication check
    if (typeof window.isAuthenticated !== 'undefined' && typeof window.loginUrl !== 'undefined') {
        window.checkAuthentication();
    }
})();
