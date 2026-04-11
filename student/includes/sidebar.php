<?php
$current_page = basename($_SERVER['PHP_SELF']);
$student_name = $_SESSION['student_name'] ?? ($_SESSION['first_name'] . ' ' . $_SESSION['last_name']);
$student_id   = $_SESSION['student_id'] ?? '';
$initials     = strtoupper(substr(explode(' ', trim($student_name))[0], 0, 1) . substr(explode(' ', trim($student_name))[1] ?? '', 0, 1));
?>
<aside class="sidebar">

    <!-- Brand -->
    <div class="sidebar-brand">
        <img src="../wsu-new-logo.gif" alt="WSU" class="sidebar-logo">
        <div class="sidebar-brand-text">
            <span class="brand-name">WSU Booking</span>
            <span class="brand-sub">Student Portal</span>
        </div>
    </div>

    <!-- Student Card -->
    <div class="sidebar-student">
        <div class="student-avatar"><?php echo htmlspecialchars($initials); ?></div>
        <div class="student-info">
            <span class="student-name"><?php echo htmlspecialchars($student_name); ?></span>
            <span class="student-id"><?php echo htmlspecialchars($student_id); ?></span>
        </div>
        <span class="student-status">Active</span>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <p class="nav-label">Main Menu</p>

        <a href="index.php" class="nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" data-tooltip="Dashboard">
            <div class="nav-icon"><i class="fas fa-home"></i></div>
            <span>Dashboard</span>
        </a>

        <a href="hub.php" class="nav-item <?php echo $current_page == 'hub.php' ? 'active' : ''; ?>" data-tooltip="WSU Hub">
            <div class="nav-icon"><i class="fas fa-th-large"></i></div>
            <span>WSU Hub</span>
        </a>

        <a href="book-service.php" class="nav-item <?php echo $current_page == 'book-service.php' ? 'active' : ''; ?>" data-tooltip="Book Service">
            <div class="nav-icon"><i class="fas fa-calendar-plus"></i></div>
            <span>Book Service</span>
        </a>

        <a href="my-bookings.php" class="nav-item <?php echo $current_page == 'my-bookings.php' ? 'active' : ''; ?>" data-tooltip="My Bookings">
            <div class="nav-icon"><i class="fas fa-calendar-check"></i></div>
            <span>My Bookings</span>
        </a>

        <a href="notifications.php" class="nav-item <?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>" data-tooltip="Notifications">
            <div class="nav-icon"><i class="fas fa-bell"></i></div>
            <span>Notifications</span>
        </a>

        <p class="nav-label">Account</p>

        <a href="profile.php" class="nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>" data-tooltip="Profile">
            <div class="nav-icon"><i class="fas fa-user"></i></div>
            <span>Profile</span>
        </a>

        <a href="settings.php" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>" data-tooltip="Settings">
            <div class="nav-icon"><i class="fas fa-cog"></i></div>
            <span>Settings</span>
        </a>
    </nav>

    <!-- Footer -->
    <div class="sidebar-footer">
        <a href="../auth/logout.php" class="nav-item logout" data-tooltip="Logout">
            <div class="nav-icon"><i class="fas fa-sign-out-alt"></i></div>
            <span>Logout</span>
        </a>
    </div>

</aside>
