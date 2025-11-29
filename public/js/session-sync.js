// Cross-tab session synchronization
class SessionSync {
    constructor() {
        this.channel = new BroadcastChannel('auth_session');
        this.currentPath = window.location.pathname;
        this.isLoggedIn = false;
        this.currentUser = null;
        
        this.init();
    }
    
    init() {
        // Listen for auth events from other tabs
        this.channel.addEventListener('message', (event) => {
            const { type, user, redirectUrl } = event.data;
            
            switch(type) {
                case 'login':
                    this.handleLoginFromOtherTab(user, redirectUrl);
                    break;
                case 'logout':
                    this.handleLogoutFromOtherTab();
                    break;
            }
        });
        
        // Check current auth status on page load
        this.checkAuthStatus();
        
        // Set up periodic checking
        setInterval(() => this.checkAuthStatus(), 5000);
    }
    
    checkAuthStatus() {
        fetch('/session/check-status')
            .then(response => response.json())
            .then(data => {
                if (data.authenticated && data.user) {
                    if (!this.isLoggedIn || this.currentUser?.id !== data.user.id) {
                        this.isLoggedIn = true;
                        this.currentUser = data.user;
                        
                        // If we're on a login page and user is authenticated, redirect
                        if (this.isLoginPage()) {
                            this.redirectToDashboard(data.user);
                        }
                    }
                } else {
                    if (this.isLoggedIn) {
                        this.isLoggedIn = false;
                        this.currentUser = null;
                    }
                }
            })
            .catch(error => console.log('Session check failed:', error));
    }
    
    isLoginPage() {
        return this.currentPath.includes('/login');
    }
    
    handleLoginFromOtherTab(user, redirectUrl) {
        this.isLoggedIn = true;
        this.currentUser = user;
        
        // If this tab is on a login page, redirect to dashboard
        if (this.isLoginPage()) {
            window.location.href = redirectUrl;
        }
    }
    
    handleLogoutFromOtherTab() {
        this.isLoggedIn = false;
        this.currentUser = null;
        
        // If this tab is not on login page, redirect to login
        if (!this.isLoginPage()) {
            window.location.href = '/login';
        }
    }
    
    redirectToDashboard(user) {
        let redirectUrl = '/login';
        
        if (user.is_admin) {
            redirectUrl = '/admin/qr-scanner';
        } else if (user.is_staff) {
            redirectUrl = '/staff/equipment';
        } else if (user.is_technician) {
            redirectUrl = '/technician/qr-scanner';
        }
        
        window.location.href = redirectUrl;
    }
    
    // Call this when user logs in
    notifyLogin(user, redirectUrl) {
        this.channel.postMessage({
            type: 'login',
            user: user,
            redirectUrl: redirectUrl
        });
    }
    
    // Call this when user logs out
    notifyLogout() {
        this.channel.postMessage({
            type: 'logout'
        });
    }
}

// Initialize session sync
window.sessionSync = new SessionSync();
