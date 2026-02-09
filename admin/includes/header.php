<?php
$current_page = basename($_SERVER['PHP_SELF']);
$page_titles = [
    'index.php' => 'Dashboard',
    'users.php' => 'User Management',
    'staff-management.php' => 'Staff Management',
    'students-management.php' => 'Student Management',
    'services.php' => 'Services',
    'bookings.php' => 'Bookings',
    'reports.php' => 'Reports',
    'settings.php' => 'Settings'
];
$page_title = isset($page_titles[$current_page]) ? $page_titles[$current_page] : 'Dashboard';
?>
<header class="header">
    <div class="header-left">
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="header-logo" id="headerLogo" style="display: none;">
            <img src="../logo.png" alt="WSU" style="height: 40px;">
        </div>
        <h1 class="page-title"><?php echo $page_title; ?></h1>
    </div>
    
    <div class="header-right">
        <button class="notification-btn" id="notificationBtn">
            <i class="fas fa-bell"></i>
            <span class="badge">5</span>
        </button>
        
        <div class="user-menu" id="userMenuBtn">
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></span>
                <span class="user-id"><?php echo htmlspecialchars($_SESSION['admin_role']); ?></span>
            </div>
            <div class="user-avatar">
                <i class="fas fa-user-shield"></i>
            </div>
        </div>
        
        <!-- User Dropdown Menu -->
        <div class="user-dropdown" id="userDropdown">
            <a href="profile.php" class="dropdown-item">
                <i class="fas fa-user"></i> My Profile
            </a>
            <a href="settings.php" class="dropdown-item">
                <i class="fas fa-cog"></i> Settings
            </a>
            <div class="dropdown-divider"></div>
            <a href="#" onclick="confirmLogout(); return false;" class="dropdown-item logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
        
        <!-- Notifications Dropdown -->
        <div class="notifications-dropdown" id="notificationsDropdown">
            <div class="notifications-header">
                <h4>Notifications</h4>
                <span class="mark-read">Mark all as read</span>
            </div>
            <div class="notifications-list">
                <a href="#" class="notification-item unread">
                    <i class="fas fa-user-plus"></i>
                    <div>
                        <p>New staff member registered</p>
                        <span>1 hour ago</span>
                    </div>
                </a>
                <a href="#" class="notification-item unread">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <p>System maintenance required</p>
                        <span>3 hours ago</span>
                    </div>
                </a>
                <a href="#" class="notification-item">
                    <i class="fas fa-chart-line"></i>
                    <div>
                        <p>Weekly report generated</p>
                        <span>1 day ago</span>
                    </div>
                </a>
            </div>
            <a href="notifications.php" class="view-all">View All Notifications</a>
        </div>
    </div>
</header>
