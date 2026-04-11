document.addEventListener('DOMContentLoaded', function () {

    var menuToggle    = document.getElementById('menuToggle');
    var sidebar       = document.querySelector('.sidebar');
    var mainContent   = document.querySelector('.main-content');
    var headerLogo    = document.getElementById('headerLogo');
    var userMenuBtn   = document.getElementById('userMenuBtn');
    var userDropdown  = document.getElementById('userDropdown');
    var notifBtn      = document.getElementById('notificationBtn');
    var notifDropdown = document.getElementById('notificationsDropdown');

    function isMobile() {
        return window.innerWidth <= 768;
    }

    // ── Hamburger ──────────────────────────────────────────
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function (e) {
            e.stopPropagation();

            if (isMobile()) {
                // Mobile: slide in from left using 'open' class
                sidebar.classList.toggle('open');
                document.body.classList.toggle('sidebar-open');
            } else {
                // Desktop: collapse to icon-only using 'closed' class
                sidebar.classList.toggle('closed');
                if (mainContent) mainContent.classList.toggle('sidebar-closed');
                if (headerLogo) {
                    headerLogo.style.display = sidebar.classList.contains('closed') ? 'flex' : 'none';
                }
            }
        });
    }

    // ── Close mobile sidebar on overlay click ──────────────
    document.addEventListener('click', function (e) {
        // Close dropdowns
        if (userDropdown)  userDropdown.classList.remove('active');
        if (notifDropdown) notifDropdown.classList.remove('active');

        // Close mobile sidebar when clicking outside
        if (isMobile() && sidebar && sidebar.classList.contains('open')) {
            if (!sidebar.contains(e.target) && e.target !== menuToggle && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('open');
                document.body.classList.remove('sidebar-open');
            }
        }
    });

    // ── User Dropdown ──────────────────────────────────────
    if (userMenuBtn && userDropdown) {
        userMenuBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
            if (notifDropdown) notifDropdown.classList.remove('active');
        });
    }

    // ── Notification Dropdown ──────────────────────────────
    if (notifBtn && notifDropdown) {
        notifBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            notifDropdown.classList.toggle('active');
            if (userDropdown) userDropdown.classList.remove('active');
        });
    }

    // ── Reset on resize ────────────────────────────────────
    window.addEventListener('resize', function () {
        if (!isMobile()) {
            sidebar.classList.remove('open');
            document.body.classList.remove('sidebar-open');
        } else {
            sidebar.classList.remove('closed');
            if (mainContent) mainContent.classList.remove('sidebar-closed');
            if (headerLogo) headerLogo.style.display = 'none';
        }
    });

});
