<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../admin-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get statistics
$stmt = $conn->query("SELECT COUNT(*) as total FROM students WHERE status = 'active'");
$total_students = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM staff WHERE status = 'active'");
$total_staff = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM bookings");
$total_bookings = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM bookings WHERE status = 'pending'");
$pending_bookings = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM services WHERE status = 'active'");
$total_services = $stmt->fetch()['total'];

// Get recent bookings
$stmt = $conn->prepare("
    SELECT b.*, s.service_name, st.first_name as student_first, st.last_name as student_last,
           staff.first_name as staff_first, staff.last_name as staff_last
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN students st ON b.student_id = st.student_id
    JOIN staff ON b.staff_id = staff.staff_id
    ORDER BY b.created_at DESC
    LIMIT 10
");
$stmt->execute();
$recent_bookings = $stmt->fetchAll();

// Get booking statistics by status
$stmt = $conn->query("
    SELECT status, COUNT(*) as count
    FROM bookings
    GROUP BY status
");
$booking_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/modals.css">
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
                        <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_name']); ?>! 👋</h1>
                        <p>System overview and management dashboard</p>
                        <div class="hero-stats">
                            <div class="hero-stat">
                                <i class="fas fa-users"></i>
                                <span><?php echo $total_students; ?> Students</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-user-tie"></i>
                                <span><?php echo $total_staff; ?> Staff Members</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-calendar-check"></i>
                                <span><?php echo $total_bookings; ?> Total Bookings</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_students; ?></h3>
                            <p>Active Students</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_staff; ?></h3>
                            <p>Active Staff</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $pending_bookings; ?></h3>
                            <p>Pending Bookings</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="fas fa-concierge-bell"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_services; ?></h3>
                            <p>Active Services</p>
                        </div>
                    </div>
                </div>

                <!-- Booking Status Overview -->
                <div class="section">
                    <h3><i class="fas fa-chart-pie"></i> Booking Status Overview</h3>
                    <div class="stats-grid">
                        <?php
                        $status_config = [
                            'pending' => ['icon' => 'clock', 'color' => 'orange', 'label' => 'Pending'],
                            'confirmed' => ['icon' => 'check-circle', 'color' => 'green', 'label' => 'Confirmed'],
                            'completed' => ['icon' => 'calendar-check', 'color' => 'blue', 'label' => 'Completed'],
                            'cancelled' => ['icon' => 'times-circle', 'color' => 'red', 'label' => 'Cancelled']
                        ];
                        
                        foreach($status_config as $status => $config):
                            $count = isset($booking_stats[$status]) ? $booking_stats[$status] : 0;
                        ?>
                        <div class="stat-card">
                            <div class="stat-icon <?php echo $config['color']; ?>">
                                <i class="fas fa-<?php echo $config['icon']; ?>"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $count; ?></h3>
                                <p><?php echo $config['label']; ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Recent Bookings -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title"><i class="fas fa-calendar-alt"></i> Recent Bookings</h2>
                        <a href="bookings.php" class="btn-link">View All <i class="fas fa-arrow-right"></i></a>
                    </div>
                    
                    <?php if(count($recent_bookings) > 0): ?>
                        <div class="bookings-list">
                            <?php foreach($recent_bookings as $booking): ?>
                            <div class="booking-card">
                                <div class="booking-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="booking-details">
                                    <h4><?php echo htmlspecialchars($booking['service_name']); ?></h4>
                                    <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($booking['student_first'] . ' ' . $booking['student_last']); ?></p>
                                    <p><i class="fas fa-user-tie"></i> Staff: <?php echo htmlspecialchars($booking['staff_first'] . ' ' . $booking['staff_last']); ?></p>
                                    <p><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($booking['booking_date'])); ?> at <?php echo date('H:i', strtotime($booking['start_time'])); ?></p>
                                </div>
                                <div class="booking-status">
                                    <span class="badge badge-<?php echo $booking['status']; ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No Recent Bookings</h3>
                            <p>No bookings have been made yet</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3>Quick Actions</h3>
                    <div class="actions-grid">
                        <a href="users.php" class="action-card">
                            <i class="fas fa-user-plus"></i>
                            <span>Add User</span>
                        </a>
                        <a href="services.php" class="action-card">
                            <i class="fas fa-plus-circle"></i>
                            <span>Add Service</span>
                        </a>
                        <a href="reports.php" class="action-card">
                            <i class="fas fa-file-download"></i>
                            <span>Generate Report</span>
                        </a>
                        <a href="settings.php" class="action-card">
                            <i class="fas fa-cog"></i>
                            <span>System Settings</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../assets/includes/modals.php'; ?>
    <script src="js/dashboard.js"></script>
    <script src="../assets/js/modals.js"></script>
</body>
</html>
