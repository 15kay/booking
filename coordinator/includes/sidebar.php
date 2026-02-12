<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <img src="../logo.png" alt="WSU" class="sidebar-logo">
        <h3>WSU Booking</h3>
        <p class="sidebar-subtitle">Coordinator Portal</p>
    </div>
    
    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="browse-modules.php" class="nav-item <?php echo $current_page == 'browse-modules.php' ? 'active' : ''; ?>">
            <i class="fas fa-search"></i>
            <span>Browse Modules</span>
        </a>
        <a href="tutor-assignments.php" class="nav-item <?php echo $current_page == 'tutor-assignments.php' ? 'active' : ''; ?>">
            <i class="fas fa-tasks"></i>
            <span>Assignments</span>
        </a>
        <a href="tutors.php" class="nav-item <?php echo $current_page == 'tutors.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-tie"></i>
            <span>Tutors & PALs</span>
        </a>
        <a href="sessions.php" class="nav-item <?php echo $current_page == 'sessions.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i>
            <span>Sessions</span>
        </a>
        <a href="reports.php" class="nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i>
            <span>Reports</span>
        </a>
        <a href="settings.php" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <a href="#" onclick="confirmLogout(); return false;" class="nav-item logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>
