<?php
$current_page = basename($_SERVER['PHP_SELF']);
$role = $_SESSION['role'] ?? 'staff';
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <img src="../logo.png" alt="WSU" class="sidebar-logo">
        <h3>WSU Booking</h3>
        <p class="sidebar-subtitle"><?php echo ucfirst($role); ?> Portal</p>
    </div>
    
    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        
        <?php if($role == 'coordinator'): ?>
            <!-- Coordinator Menu -->
            <a href="browse-modules.php" class="nav-item <?php echo $current_page == 'browse-modules.php' ? 'active' : ''; ?>">
                <i class="fas fa-book"></i>
                <span>Browse Modules</span>
            </a>
            <a href="tutors.php" class="nav-item <?php echo $current_page == 'tutors.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-tie"></i>
                <span>Tutors & PALs</span>
            </a>
            <a href="tutor-assignments.php" class="nav-item <?php echo $current_page == 'tutor-assignments.php' ? 'active' : ''; ?>">
                <i class="fas fa-clipboard-list"></i>
                <span>Assignments</span>
            </a>
            <a href="sessions.php" class="nav-item <?php echo $current_page == 'sessions.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-alt"></i>
                <span>Sessions</span>
            </a>
            <a href="reports.php" class="nav-item <?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                <i class="fas fa-chart-line"></i>
                <span>Reports</span>
            </a>
            
        <?php elseif($role == 'tutor' || $role == 'pal'): ?>
            <!-- Tutor/PAL Menu -->
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
            
        <?php else: ?>
            <!-- Regular Staff Menu -->
            <a href="appointments.php" class="nav-item <?php echo $current_page == 'appointments.php' ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i>
                <span>Appointments</span>
            </a>
            <a href="schedule.php" class="nav-item <?php echo $current_page == 'schedule.php' ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i>
                <span>Schedule</span>
            </a>
            <a href="all-students.php" class="nav-item <?php echo $current_page == 'all-students.php' ? 'active' : ''; ?>">
                <i class="fas fa-user-graduate"></i>
                <span>All Students</span>
            </a>
            <a href="students.php" class="nav-item <?php echo $current_page == 'students.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>My Students</span>
            </a>
        <?php endif; ?>
        
        <a href="notifications.php" class="nav-item <?php echo $current_page == 'notifications.php' ? 'active' : ''; ?>">
            <i class="fas fa-bell"></i>
            <span>Notifications</span>
        </a>
        <a href="profile.php" class="nav-item <?php echo $current_page == 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>Profile</span>
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
