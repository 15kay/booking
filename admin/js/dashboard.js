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

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            if (isMobile()) {
                sidebar.classList.toggle('open');
                document.body.classList.toggle('sidebar-open');
            } else {
                sidebar.classList.toggle('closed');
                if (mainContent) mainContent.classList.toggle('sidebar-closed');
                if (headerLogo) {
                    headerLogo.style.display = sidebar.classList.contains('closed') ? 'flex' : 'none';
                }
            }
        });
    }

    document.addEventListener('click', function (e) {
        if (userDropdown)  userDropdown.classList.remove('active');
        if (notifDropdown) notifDropdown.classList.remove('active');

        if (isMobile() && sidebar && sidebar.classList.contains('open')) {
            if (!sidebar.contains(e.target) && e.target !== menuToggle && !menuToggle.contains(e.target)) {
                sidebar.classList.remove('open');
                document.body.classList.remove('sidebar-open');
            }
        }
    });

    if (userMenuBtn && userDropdown) {
        userMenuBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
            if (notifDropdown) notifDropdown.classList.remove('active');
        });
    }

    if (notifBtn && notifDropdown) {
        notifBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            notifDropdown.classList.toggle('active');
            if (userDropdown) userDropdown.classList.remove('active');
        });
    }

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

    window.confirmLogout = function () {
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = '../auth/logout.php';
        }
    };

});
