<?php
$current_page = basename($_SERVER['PHP_SELF']);
$page_titles = [
    'index.php'        => 'Dashboard',
    'hub.php'          => 'WSU Hub',
    'book-service.php' => 'Book Service',
    'my-bookings.php'  => 'My Bookings',
    'notifications.php'=> 'Notifications',
    'profile.php'      => 'Profile',
    'settings.php'     => 'Settings',
    'booking-details.php' => 'Booking Details',
];
$page_icons = [
    'index.php'        => 'fa-home',
    'hub.php'          => 'fa-th-large',
    'book-service.php' => 'fa-calendar-plus',
    'my-bookings.php'  => 'fa-calendar-check',
    'notifications.php'=> 'fa-bell',
    'profile.php'      => 'fa-user',
    'settings.php'     => 'fa-cog',
    'booking-details.php' => 'fa-info-circle',
];
$page_title = $page_titles[$current_page] ?? 'Dashboard';
$page_icon  = $page_icons[$current_page] ?? 'fa-home';

$student_name = $_SESSION['student_name'] ?? ($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
$student_id   = $_SESSION['student_id'] ?? '';
$initials     = strtoupper(substr(explode(' ', trim($student_name))[0], 0, 1) . substr(explode(' ', trim($student_name))[1] ?? '', 0, 1));
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
                <span>WSU Booking</span>
                <i class="fas fa-chevron-right"></i>
                <span><?php echo $page_title; ?></span>
            </p>
        </div>
    </div>

    <div class="header-right">

        <!-- Notification Bell -->
        <button class="header-icon-btn" id="notificationBtn">
            <i class="fas fa-bell"></i>
            <span class="notif-badge">3</span>
        </button>

        <!-- User Avatar -->
        <div class="header-user" id="userMenuBtn">
            <div class="header-avatar"><?php echo htmlspecialchars($initials); ?></div>
            <div class="header-user-info">
                <span class="header-user-name"><?php echo htmlspecialchars($student_name); ?></span>
                <span class="header-user-id"><?php echo htmlspecialchars($student_id); ?></span>
            </div>
            <i class="fas fa-chevron-down header-chevron"></i>
        </div>

        <!-- User Dropdown -->
        <div class="header-dropdown" id="userDropdown">
            <div class="dropdown-profile">
                <div class="dropdown-avatar"><?php echo htmlspecialchars($initials); ?></div>
                <div>
                    <p class="dropdown-name"><?php echo htmlspecialchars($student_name); ?></p>
                    <p class="dropdown-id"><?php echo htmlspecialchars($student_id); ?></p>
                </div>
            </div>
            <div class="dropdown-divider"></div>
            <a href="profile.php" class="dropdown-item"><i class="fas fa-user"></i> My Profile</a>
            <a href="settings.php" class="dropdown-item"><i class="fas fa-cog"></i> Settings</a>
            <div class="dropdown-divider"></div>
            <a href="../auth/logout.php" class="dropdown-item dropdown-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
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
                    <div class="notif-icon green"><i class="fas fa-check-circle"></i></div>
                    <div class="notif-text">
                        <p>Your booking has been confirmed</p>
                        <span>2 hours ago</span>
                    </div>
                </a>
                <a href="#" class="notif-item unread">
                    <div class="notif-dot"></div>
                    <div class="notif-icon gold"><i class="fas fa-clock"></i></div>
                    <div class="notif-text">
                        <p>Reminder: Appointment tomorrow at 10:00 AM</p>
                        <span>5 hours ago</span>
                    </div>
                </a>
                <a href="#" class="notif-item">
                    <div class="notif-dot" style="opacity:0"></div>
                    <div class="notif-icon maroon"><i class="fas fa-info-circle"></i></div>
                    <div class="notif-text">
                        <p>New services available for booking</p>
                        <span>1 day ago</span>
                    </div>
                </a>
            </div>
            <a href="notifications.php" class="dropdown-view-all">View All Notifications <i class="fas fa-arrow-right"></i></a>
        </div>

    </div>
</header>
