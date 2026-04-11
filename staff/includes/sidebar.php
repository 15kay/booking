<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role         = $_SESSION['role'] ?? 'staff';
$staff_name   = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
$staff_number = $_SESSION['staff_number'] ?? '';
$initials     = strtoupper(substr($_SESSION['first_name'] ?? 'S', 0, 1) . substr($_SESSION['last_name'] ?? 'T', 0, 1));
$role_label   = ucfirst($role) . ' Portal';
?>
<aside class="sidebar">

    <!-- Brand -->
    <div class="sidebar-brand">
        <img src="../wsu-new-logo.gif" alt="WSU" class="sidebar-logo">
        <div class="sidebar-brand-text">
            <span class="brand-name">WSU Booking</span>
            <span class="brand-sub"><?php echo $role_label; ?></span>
        </div>
    </div>

    <!-- Staff Card -->
    <div class="sidebar-student">
        <div class="student-avatar"><?php echo htmlspecialchars($initials); ?></div>
        <div class="student-info">
            <span class="student-name"><?php echo htmlspecialchars(trim($staff_name)); ?></span>
            <span class="student-id"><?php echo htmlspecialchars($staff_number); ?></span>
        </div>
        <span class="student-status">Active</span>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <p class="nav-label">Main Menu</p>

        <a href="index.php" class="nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
            <div class="nav-icon"><i class="fas fa-home"></i></div>
            <span>Dashboard</span>
        </a>

        <?php if($role == 'coordinator'): ?>

            <a href="browse-modules.php" class="nav-item <?php echo $current_page == 'browse-modules.php' ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fas fa-book"></i></div>
                <span>Browse Modules</span>
            </a>
            <a href="tutors.php" class="nav-item <?php echo $current_page == 'tutors.php' ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fas fa-user-tie"></i></div>
                <span>Tutors & PALs</span>
            </a>
            <a href="tutor-assignments.php" class="nav-item <?php echo $current_page == 'tutor-assignments.php' ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fas fa-clipboard-list"></i></div>
                <span>Assignments</span>
            </a>
            <a href="sessions.php" class="nav-item <?php echo $current_page == 'sessions.php' ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fas fa-calendar-alt"></i></div>
                <span>Sessions</span>
            </a>
            <a href="reports.php" class="nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fas fa-chart-line"></i></div>
                <span>Reports</span>
            </a>

        <?php elseif($role == 'tutor' || $role == 'pal'): ?>

            <a href="my-assignments.php" class="nav-item <?php echo $current_page == 'my-assignments.php' ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fas fa-clipboard-list"></i></div>
                <span>My Assignments</span>
            </a>
            <a href="my-sessions.php" class="nav-item <?php echo $current_page == 'my-sessions.php' ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fas fa-calendar-alt"></i></div>
                <span>My Sessions</span>
            </a>
            <a href="schedule.php" class="nav-item <?php echo $current_page == 'schedule.php' ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fas fa-clock"></i></div>
                <span>Schedule</span>
            </a>
            <a href="students.php" class="nav-item <?php echo $current_page == 'students.php' ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fas fa-users"></i></div>
                <span>Students</span>
            </a>

        <?php else: ?>

            <a href="appointments.php" class="nav-item <?php echo $current_page == 'appointments.php' ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fas fa-calendar-check"></i></div>
                <span>Appointments</span>
            </a>
            <a href="staff-schedule.php" class="nav-item <?php echo $current_page == 'staff-schedule.php' ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fas fa-clock"></i></div>
                <span>Schedule</span>
            </a>
            <a href="all-students.php" class="nav-item <?php echo $current_page == 'all-students.php' ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fas fa-user-graduate"></i></div>
                <span>All Students</span>
            </a>
            <a href="students.php" class="nav-item <?php echo $current_page == 'students.php' ? 'active' : ''; ?>">
                <div class="nav-icon"><i class="fas fa-users"></i></div>
                <span>My Students</span>
            </a>

        <?php endif; ?>

        <p class="nav-label">Account</p>

        <a href="notifications.php" class="nav-item <?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>">
            <div class="nav-icon"><i class="fas fa-bell"></i></div>
            <span>Notifications</span>
        </a>
        <a href="profile.php" class="nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
            <div class="nav-icon"><i class="fas fa-user"></i></div>
            <span>Profile</span>
        </a>
        <a href="settings.php" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
            <div class="nav-icon"><i class="fas fa-cog"></i></div>
            <span>Settings</span>
        </a>
    </nav>

    <!-- Footer -->
    <div class="sidebar-footer">
        <a href="#" onclick="confirmLogout(); return false;" class="nav-item logout">
            <div class="nav-icon"><i class="fas fa-sign-out-alt"></i></div>
            <span>Logout</span>
        </a>
    </div>

</aside>
