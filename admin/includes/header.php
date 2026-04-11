<?php
$current_page = basename($_SERVER['PHP_SELF']);
$page_titles  = [
    'index.php'               => 'Dashboard',
    'users.php'               => 'User Management',
    'staff-management.php'    => 'Staff Management',
    'students-management.php' => 'Student Management',
    'services.php'            => 'Services',
    'bookings.php'            => 'Bookings',
    'reports.php'             => 'Reports',
    'settings.php'            => 'Settings',
    'booking-details.php'     => 'Booking Details',
    'service-details.php'     => 'Service Details',
    'staff-details.php'       => 'Staff Details',
    'student-details.php'     => 'Student Details',
    'add-staff.php'           => 'Add Staff',
    'edit-service.php'        => 'Edit Service',
    'edit-staff.php'          => 'Edit Staff',
    'profile.php'             => 'My Profile',
];
$page_icons = [
    'index.php'               => 'fa-home',
    'users.php'               => 'fa-users',
    'staff-management.php'    => 'fa-user-tie',
    'students-management.php' => 'fa-user-graduate',
    'services.php'            => 'fa-concierge-bell',
    'bookings.php'            => 'fa-calendar-alt',
    'reports.php'             => 'fa-chart-bar',
    'settings.php'            => 'fa-cog',
    'booking-details.php'     => 'fa-info-circle',
    'service-details.php'     => 'fa-info-circle',
    'staff-details.php'       => 'fa-user-tie',
    'student-details.php'     => 'fa-user-graduate',
    'add-staff.php'           => 'fa-user-plus',
    'edit-service.php'        => 'fa-edit',
    'edit-staff.php'          => 'fa-edit',
    'profile.php'             => 'fa-user-shield',
];
$page_title  = $page_titles[$current_page] ?? 'Dashboard';
$page_icon   = $page_icons[$current_page]  ?? 'fa-home';

$admin_name  = $_SESSION['admin_name'] ?? 'Administrator';
$admin_role  = $_SESSION['admin_role'] ?? 'super_admin';
$role_label  = $admin_role === 'super_admin' ? 'Super Admin' : ucfirst(str_replace('_', ' ', $admin_role));
$initials    = strtoupper(substr($admin_name, 0, 1) . (strpos($admin_name, ' ') !== false ? substr($admin_name, strpos($admin_name, ' ') + 1, 1) : ''));
?>
<header class="header">
    <div class="header-left">
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="header-logo" id="headerLogo" style="display:none;">
            <img src="../wsu-new-logo.gif" alt="WSU">
        </div>
        <div class="page-info">
            <div class="page-title-wrap">
                <i class="fas <?php echo $page_icon; ?>"></i>
                <h1 class="page-title"><?php echo $page_title; ?></h1>
            </div>
            <p class="page-breadcrumb">
                <span>WSU Admin</span>
                <i class="fas fa-chevron-right"></i>
                <span><?php echo $page_title; ?></span>
            </p>
        </div>
    </div>

    <div class="header-right">

        <!-- Notification Bell -->
        <button class="header-icon-btn" id="notificationBtn">
            <i class="fas fa-bell"></i>
            <span class="notif-badge">5</span>
        </button>

        <!-- User Avatar -->
        <div class="header-user" id="userMenuBtn">
            <div class="header-avatar"><?php echo htmlspecialchars($initials); ?></div>
            <div class="header-user-info">
                <span class="header-user-name"><?php echo htmlspecialchars($admin_name); ?></span>
                <span class="header-user-id"><?php echo htmlspecialchars($role_label); ?></span>
            </div>
            <i class="fas fa-chevron-down header-chevron"></i>
        </div>

        <!-- User Dropdown -->
        <div class="header-dropdown" id="userDropdown">
            <div class="dropdown-profile">
                <div class="dropdown-avatar"><?php echo htmlspecialchars($initials); ?></div>
                <div>
                    <p class="dropdown-name"><?php echo htmlspecialchars($admin_name); ?></p>
                    <p class="dropdown-id"><?php echo htmlspecialchars($role_label); ?></p>
                </div>
            </div>
            <div class="dropdown-divider"></div>
            <a href="profile.php" class="dropdown-item"><i class="fas fa-user-shield"></i> My Profile</a>
            <a href="settings.php" class="dropdown-item"><i class="fas fa-cog"></i> Settings</a>
            <div class="dropdown-divider"></div>
            <a href="#" onclick="confirmLogout(); return false;" class="dropdown-item dropdown-logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <!-- Notifications Dropdown -->
        <div class="header-dropdown notif-dropdown" id="notificationsDropdown">
            <div class="dropdown-notif-header">
                <h4>Notifications</h4>
                <span class="mark-read-btn">Mark all read</span>
            </div>
            <div class="dropdown-notif-list">
                <a href="#" class="notif-item unread">
                    <div class="notif-dot"></div>
                    <div class="notif-icon green"><i class="fas fa-user-plus"></i></div>
                    <div class="notif-text">
                        <p>New staff member registered</p>
                        <span>1 hour ago</span>
                    </div>
                </a>
                <a href="#" class="notif-item unread">
                    <div class="notif-dot"></div>
                    <div class="notif-icon gold"><i class="fas fa-exclamation-triangle"></i></div>
                    <div class="notif-text">
                        <p>System maintenance required</p>
                        <span>3 hours ago</span>
                    </div>
                </a>
                <a href="#" class="notif-item">
                    <div class="notif-dot" style="opacity:0"></div>
                    <div class="notif-icon maroon"><i class="fas fa-chart-line"></i></div>
                    <div class="notif-text">
                        <p>Weekly report generated</p>
                        <span>1 day ago</span>
                    </div>
                </a>
            </div>
            <a href="#" class="dropdown-view-all">View All Notifications <i class="fas fa-arrow-right"></i></a>
        </div>

    </div>
</header>
