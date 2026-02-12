<?php
$page_titles = [
    'index.php' => 'Dashboard',
    'my-assignments.php' => 'My Assignments',
    'my-sessions.php' => 'My Sessions',
    'schedule.php' => 'Schedule',
    'students.php' => 'Students',
    'profile.php' => 'Profile',
    'settings.php' => 'Settings'
];

$current_page = basename($_SERVER['PHP_SELF']);
$page_title = isset($page_titles[$current_page]) ? $page_titles[$current_page] : 'Dashboard';
?>
<header class="header">
    <button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <div class="header-title">
        <h1><?php echo $page_title; ?></h1>
    </div>
    
    <div class="header-actions">
        <div class="user-menu">
            <div class="user-avatar">
                <?php echo strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1)); ?>
            </div>
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></span>
                <span class="user-role"><?php echo ucfirst($_SESSION['role']); ?></span>
            </div>
        </div>
    </div>
</header>
