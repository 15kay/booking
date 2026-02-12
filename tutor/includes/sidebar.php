<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>WSU Booking</h2>
        <span class="role-badge"><?php echo ucfirst($_SESSION['role']); ?></span>
    </div>
    
    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="my-assignments.php" class="nav-item <?php echo $current_page == 'my-assignments.php' ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-list"></i>
            <span>My Assignments</span>
        </a>
        <a href="my-sessions.php" class="nav-item <?php echo $current_page == 'my-sessions.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i>
            <span>My Sessions</span>
        </a>
        <a href="schedule.php" class="nav-item <?php echo $current_page == 'schedule.php' ? 'active' : ''; ?>">
            <i class="fas fa-clock"></i>
            <span>Schedule</span>
        </a>
        <a href="students.php" class="nav-item <?php echo $current_page == 'students.php' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Students</span>
        </a>
        <a href="profile.php" class="nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
        <a href="settings.php" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        <a href="../auth/logout.php" class="nav-item">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </nav>
</div>
