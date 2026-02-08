// Hamburger Menu Toggle
const menuToggle = document.getElementById('menuToggle');
const sidebar = document.querySelector('.sidebar');
const headerLogo = document.getElementById('headerLogo');

if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', function() {
        sidebar.classList.toggle('closed');
        if (headerLogo) {
            headerLogo.style.display = sidebar.classList.contains('closed') ? 'flex' : 'none';
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
