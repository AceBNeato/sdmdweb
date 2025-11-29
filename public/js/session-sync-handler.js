// Handle session sync notifications from login
document.addEventListener('DOMContentLoaded', function() {
    // Check if there's a session sync notification (set by Blade template)
    const sessionSyncElement = document.getElementById('session-sync-data');
    const sessionSync = sessionSyncElement ? JSON.parse(sessionSyncElement.textContent) : null;
    
    if (sessionSync && sessionSync.type === 'login' && window.sessionSync) {
        // Notify other tabs about the login
        window.sessionSync.notifyLogin(sessionSync.user, sessionSync.redirectUrl);
    }
});
