<?php
$current_page = basename($_SERVER['PHP_SELF']);
$admin_name   = $_SESSION['admin_name'] ?? 'Administrator';
$admin_role   = $_SESSION['admin_role'] ?? 'super_admin';
$role_label   = $admin_role === 'super_admin' ? 'Super Admin' : ucfirst(str_replace('_', ' ', $admin_role));
$initials     = strtoupper(substr($admin_name, 0, 1) . (strpos($admin_name, ' ') !== false ? substr($admin_name, strpos($admin_name, ' ') + 1, 1) : ''));
?>
<aside class="sidebar">

    <!-- Brand -->
    <div class="sidebar-brand">
        <img src="../wsu-new-logo.gif" alt="WSU" class="sidebar-logo">
        <div class="sidebar-brand-text">
            <span class="brand-name">WSU Booking</span>
            <span class="brand-sub">Admin Portal</span>
        </div>
    </div>

    <!-- Admin Card -->
    <div class="sidebar-student">
        <div class="student-avatar"><?php echo htmlspecialchars($initials); ?></div>
        <div class="student-info">
            <span class="student-name"><?php echo htmlspecialchars($admin_name); ?></span>
            <span class="student-id"><?php echo htmlspecialchars($role_label); ?></span>
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
        <a href="users.php" class="nav-item <?php echo $current_page == 'users.php' ? 'active' : ''; ?>">
            <div class="nav-icon"><i class="fas fa-users"></i></div>
            <span>Users</span>
        </a>
        <a href="staff-management.php" class="nav-item <?php echo $current_page == 'staff-management.php' ? 'active' : ''; ?>">
            <div class="nav-icon"><i class="fas fa-user-tie"></i></div>
            <span>Staff</span>
        </a>
        <a href="students-management.php" class="nav-item <?php echo $current_page == 'students-management.php' ? 'active' : ''; ?>">
            <div class="nav-icon"><i class="fas fa-user-graduate"></i></div>
            <span>Students</span>
        </a>
        <a href="services.php" class="nav-item <?php echo $current_page == 'services.php' ? 'active' : ''; ?>">
            <div class="nav-icon"><i class="fas fa-concierge-bell"></i></div>
            <span>Services</span>
        </a>
        <a href="bookings.php" class="nav-item <?php echo $current_page == 'bookings.php' ? 'active' : ''; ?>">
            <div class="nav-icon"><i class="fas fa-calendar-alt"></i></div>
            <span>Bookings</span>
        </a>

        <p class="nav-label">System</p>

        <a href="reports.php" class="nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
            <div class="nav-icon"><i class="fas fa-chart-bar"></i></div>
            <span>Reports</span>
        </a>
        <a href="settings.php" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
            <div class="nav-icon"><i class="fas fa-cog"></i></div>
            <span>Settings</span>
        </a>

        <p class="nav-label">Account</p>

        <a href="profile.php" class="nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
            <div class="nav-icon"><i class="fas fa-user-shield"></i></div>
            <span>My Profile</span>
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
