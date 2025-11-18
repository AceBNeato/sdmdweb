// UI Functionality (Profile Dropdown, Hamburger Menu)
document.addEventListener('DOMContentLoaded', function() {
    // Profile dropdown functionality
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
