<?php
$page_titles = [
    'index.php' => 'Dashboard',
    'appointments.php' => 'Appointments',
    'schedule.php' => 'My Schedule',
    'students.php' => 'Students',
    'profile.php' => 'Profile'
];
$current_page = basename($_SERVER['PHP_SELF']);
$page_title = $page_titles[$current_page] ?? 'Staff Portal';
?>
<header class="header">
    <div class="header-left">
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="header-logo" id="headerLogo" style="display: none;">
            <img src="../logo.png" alt="WSU" style="height: 40px;">
        </div>
        <h1 class="page-title"><?php echo $page_title; ?></h1>
    </div>
    
    <div class="header-right">
        <button class="notification-btn" id="notificationBtn">
            <i class="fas fa-bell"></i>
            <span class="badge">3</span>
        </button>
        
        <div class="dropdown">
            <div id="notificationsDropdown" class="dropdown-menu">
                <div class="dropdown-header">
                    <h4>Notifications</h4>
                </div>
                <div class="dropdown-body">
                    <p style="text-align: center; color: #6b7280; padding: 20px;">No new notifications</p>
                </div>
            </div>
        </div>
        
        <div class="user-menu">
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['staff_name'] ?? 'Staff Member'); ?></span>
                <span class="user-id"><?php echo htmlspecialchars($_SESSION['staff_number'] ?? ''); ?></span>
            </div>
            <button class="user-avatar" id="userMenuBtn">
                <i class="fas fa-user-circle"></i>
            </button>
        </div>
        
        <div class="dropdown">
            <div id="userDropdown" class="dropdown-menu">
                <a href="profile.php" class="dropdown-item">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a href="../auth/logout.php" class="dropdown-item">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>
</header>
