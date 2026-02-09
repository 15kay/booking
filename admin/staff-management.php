<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../admin-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get all staff
$stmt = $conn->query("
    SELECT s.*, d.department_name, COUNT(DISTINCT b.booking_id) as total_bookings
    FROM staff s
    LEFT JOIN departments d ON s.department_id = d.department_id
    LEFT JOIN bookings b ON s.staff_id = b.staff_id
    GROUP BY s.staff_id
    ORDER BY s.created_at DESC
");
$staff_members = $stmt->fetchAll();

// Get statistics
$stmt = $conn->query("SELECT COUNT(*) as total FROM staff WHERE status = 'active'");
$active_staff = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM staff WHERE status = 'inactive'");
$inactive_staff = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <!-- Hero Section -->
                <div class="hero-section">
                    <div class="hero-content">
                        <h1>Staff Management</h1>
                        <p>Manage staff members, roles, and permissions</p>
                        <div class="hero-stats">
                            <div class="hero-stat">
                                <i class="fas fa-user-tie"></i>
                                <span><?php echo count($staff_members); ?> Total Staff</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-user-check"></i>
                                <span><?php echo $active_staff; ?> Active</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-calendar-check"></i>
                                <span><?php echo array_sum(array_column($staff_members, 'total_bookings')); ?> Total Bookings</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count($staff_members); ?></h3>
                            <p>Total Staff</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $active_staff; ?></h3>
                            <p>Active</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-user-times"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $inactive_staff; ?></h3>
                            <p>Inactive</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo array_sum(array_column($staff_members, 'total_bookings')); ?></h3>
                            <p>Total Bookings</p>
                        </div>
                    </div>
                </div>

                <!-- Staff Grid -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title"><i class="fas fa-users"></i> Staff Members</h2>
                        <a href="add-staff.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Staff
                        </a>
                    </div>
                    
                    <div class="bookings-grid">
                        <?php foreach($staff_members as $staff): ?>
                        <div class="booking-item">
                            <div class="booking-item-header">
                                <div>
                                    <h3><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></h3>
                                    <span class="category-badge"><?php echo htmlspecialchars($staff['staff_number']); ?></span>
                                </div>
                                <span class="badge badge-<?php echo $staff['status']; ?>">
                                    <?php echo ucfirst($staff['status']); ?>
                                </span>
                            </div>
                            
                            <div class="booking-item-body">
                                <div class="booking-info-row">
                                    <i class="fas fa-briefcase"></i>
                                    <span><?php echo ucfirst(str_replace('_', ' ', $staff['role'])); ?></span>
                                </div>
                                
                                <div class="booking-info-row">
                                    <i class="fas fa-building"></i>
                                    <span><?php echo htmlspecialchars($staff['department_name'] ?? 'No department'); ?></span>
                                </div>
                                
                                <div class="booking-info-row">
                                    <i class="fas fa-envelope"></i>
                                    <span><?php echo htmlspecialchars($staff['email']); ?></span>
                                </div>
                                
                                <?php if(!empty($staff['phone'])): ?>
                                <div class="booking-info-row">
                                    <i class="fas fa-phone"></i>
                                    <span><?php echo htmlspecialchars($staff['phone']); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="booking-info-row">
                                    <i class="fas fa-calendar-check"></i>
                                    <span><?php echo $staff['total_bookings']; ?> Total Bookings</span>
                                </div>
                            </div>
                            
                            <div class="booking-item-footer">
                                <a href="staff-details.php?id=<?php echo $staff['staff_id']; ?>" class="btn-action btn-secondary">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                                <button class="btn-action btn-primary" onclick="showMessageModal('info', 'Coming Soon', 'Edit functionality coming soon!')">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../assets/includes/modals.php'; ?>
    <link rel="stylesheet" href="../assets/css/modals.css">
    <script src="../assets/js/modals.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>
