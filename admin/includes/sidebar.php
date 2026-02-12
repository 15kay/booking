<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <img src="../logo.png" alt="WSU" class="sidebar-logo">
        <h3>WSU Booking</h3>
        <p class="sidebar-subtitle">Admin Portal</p>
    </div>
    
    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" data-tooltip="Dashboard">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="users.php" class="nav-item <?php echo $current_page == 'users.php' ? 'active' : ''; ?>" data-tooltip="Users">
            <i class="fas fa-users"></i>
            <span>Users</span>
        </a>
        <a href="staff-management.php" class="nav-item <?php echo $current_page == 'staff-management.php' ? 'active' : ''; ?>" data-tooltip="Staff">
            <i class="fas fa-user-tie"></i>
            <span>Staff</span>
        </a>
        <a href="students-management.php" class="nav-item <?php echo $current_page == 'students-management.php' ? 'active' : ''; ?>" data-tooltip="Students">
            <i class="fas fa-user-graduate"></i>
            <span>Students</span>
        </a>
        <a href="services.php" class="nav-item <?php echo $current_page == 'services.php' ? 'active' : ''; ?>" data-tooltip="Services">
            <i class="fas fa-concierge-bell"></i>
            <span>Services</span>
        </a>
        <a href="bookings.php" class="nav-item <?php echo $current_page == 'bookings.php' ? 'active' : ''; ?>" data-tooltip="Bookings">
            <i class="fas fa-calendar-alt"></i>
            <span>Bookings</span>
        </a>
        <a href="reports.php" class="nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>" data-tooltip="Reports">
            <i class="fas fa-chart-bar"></i>
            <span>Reports</span>
        </a>
        <a href="settings.php" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>" data-tooltip="Settings">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <a href="#" onclick="confirmLogout(); return false;" class="nav-item logout" data-tooltip="Logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>
