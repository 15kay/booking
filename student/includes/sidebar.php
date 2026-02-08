<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <img src="../logo.png" alt="WSU" class="sidebar-logo">
        <h3>WSU Booking</h3>
        <p class="sidebar-subtitle">Student Portal</p>
    </div>
    
    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item <?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Dashboard</span>
        </a>
        <a href="book-service.php" class="nav-item <?php echo $current_page == 'book-service.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-plus"></i>
            <span>Book Service</span>
        </a>
        <a href="my-bookings.php" class="nav-item <?php echo $current_page == 'my-bookings.php' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-check"></i>
            <span>My Bookings</span>
        </a>
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
        <a href="../auth/logout.php" class="nav-item logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>
