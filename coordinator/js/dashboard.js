// Hamburger Menu Toggle
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.querySelector('.sidebar');
const mainContent = document.querySelector('.main-content');
const headerLogo = document.getElementById('headerLogo');

if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', function(e) {
        e.stopPropagation();
        
        // Check if we're on mobile or desktop
        const isMobile = window.innerWidth <= 768;
        
        if (isMobile) {
            // Mobile behavior: overlay sidebar
            sidebar.classList.toggle('active');
            document.body.classList.toggle('sidebar-open');
            
            if (headerLogo) {
                headerLogo.style.display = sidebar.classList.contains('active') ? 'none' : 'flex';
            }
        } else {
            // Desktop behavior: collapse sidebar
            sidebar.classList.toggle('collapsed');
            if (mainContent) {
                mainContent.classList.toggle('sidebar-collapsed');
            }
        }
    });
    
    // Close sidebar when clicking on overlay (mobile only)
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768) {
            if (sidebar.classList.contains('active') && 
                !sidebar.contains(e.target) && 
                !menuToggle.contains(e.target)) {
                sidebar.classList.remove('active');
                document.body.classList.remove('sidebar-open');
                if (headerLogo) {
                    headerLogo.style.display = 'flex';
                }
            }
        }
    });
    
    // Prevent clicks inside sidebar from closing it
    sidebar.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        const isMobile = window.innerWidth <= 768;
        
        if (!isMobile) {
            // Remove mobile classes when switching to desktop
            sidebar.classList.remove('active');
            document.body.classList.remove('sidebar-open');
            if (headerLogo) {
                headerLogo.style.display = 'flex';
            }
        } else {
            // Remove desktop classes when switching to mobile
            sidebar.classList.remove('collapsed');
            if (mainContent) {
                mainContent.classList.remove('sidebar-collapsed');
            }
        }
    });
}

// User Menu Dropdown
const userMenuBtn = document.getElementById('userMenuBtn');
const userDropdown = document.getElementById('userDropdown');

if (userMenuBtn && userDropdown) {
    userMenuBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        userDropdown.classList.toggle('active');
        if (notificationsDropdown) {
            notificationsDropdown.classList.remove('active');
        }
    });
}

// Notification Dropdown
const notificationBtn = document.getElementById('notificationBtn');
const notificationsDropdown = document.getElementById('notificationsDropdown');

if (notificationBtn && notificationsDropdown) {
    notificationBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        notificationsDropdown.classList.toggle('active');
        if (userDropdown) {
            userDropdown.classList.remove('active');
        }
    });
}

// Close dropdowns when clicking outside
document.addEventListener('click', function() {
    if (userDropdown) userDropdown.classList.remove('active');
    if (notificationsDropdown) notificationsDropdown.classList.remove('active');
});


// Logout Confirmation
function confirmLogout() {
    if (typeof showConfirmModal === 'function') {
        showConfirmModal(
            'Confirm Logout',
            'Are you sure you want to logout?',
            function() {
                window.location.href = '../auth/logout.php';
            }
        );
    } else {
        // Fallback if modal system is not loaded
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = '../auth/logout.php';
        }
    }
}
