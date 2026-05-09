<?php
session_start();
if(!isset($_SESSION['staff_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get staff stats
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE staff_id = ?");
$stmt->execute([$_SESSION['staff_id']]);
$total_bookings = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as pending FROM bookings WHERE staff_id = ? AND status = 'pending'");
$stmt->execute([$_SESSION['staff_id']]);
$pending_bookings = $stmt->fetch()['pending'];

$stmt = $conn->prepare("SELECT COUNT(*) as confirmed FROM bookings WHERE staff_id = ? AND status = 'confirmed'");
$stmt->execute([$_SESSION['staff_id']]);
$confirmed_bookings = $stmt->fetch()['confirmed'];

$stmt = $conn->prepare("SELECT COUNT(*) as completed FROM bookings WHERE staff_id = ? AND status = 'completed'");
$stmt->execute([$_SESSION['staff_id']]);
$completed_bookings = $stmt->fetch()['completed'];

// Get upcoming bookings
$stmt = $conn->prepare("
    SELECT b.*, s.service_name, st.first_name, st.last_name, st.student_id
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN students st ON b.student_id = st.student_id
    WHERE b.staff_id = ? AND b.booking_date >= CURDATE() AND b.status IN ('pending', 'confirmed')
    ORDER BY b.booking_date, b.start_time
    LIMIT 5
");
$stmt->execute([$_SESSION['staff_id']]);
$upcoming_bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - WSU Booking</title>
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
                        <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>! 👋</h1>
                        <p>Manage your appointments, schedules, and student bookings efficiently</p>
                        <div class="hero-stats">
                            <div class="hero-stat">
                                <i class="fas fa-calendar-check"></i>
                                <span><?php echo $total_bookings; ?> Total Bookings</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-clock"></i>
                                <span><?php echo $pending_bookings; ?> Pending Approval</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-check-circle"></i>
                                <span><?php echo $confirmed_bookings; ?> Confirmed Today</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_bookings; ?></h3>
                            <p>Total Bookings</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $pending_bookings; ?></h3>
                            <p>Pending</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $confirmed_bookings; ?></h3>
                            <p>Confirmed</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $completed_bookings; ?></h3>
                            <p>Completed</p>
                        </div>
                    </div>
                </div>
                
                <!-- Upcoming Bookings -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title"><i class="fas fa-calendar-alt"></i> Upcoming Appointments</h2>
                    </div>
                    
                    <?php if(count($upcoming_bookings) > 0): ?>
                        <div class="bookings-list">
                            <?php foreach($upcoming_bookings as $booking): ?>
                            <div class="booking-card">
                                <div class="booking-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="booking-details">
                                    <h4><?php echo htmlspecialchars($booking['service_name']); ?></h4>
                                    <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?> (<?php echo htmlspecialchars($booking['student_id']); ?>)</p>
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
                            <h3>No Upcoming Appointments</h3>
                            <p>You don't have any scheduled appointments</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3>Quick Actions</h3>
                    <div class="actions-grid">
                        <a href="appointments.php" class="action-card">
                            <i class="fas fa-calendar"></i>
                            <span>View Appointments</span>
                        </a>
                        <a href="schedule.php" class="action-card">
                            <i class="fas fa-clock"></i>
                            <span>Manage Schedule</span>
                        </a>
                        <a href="students.php" class="action-card">
                            <i class="fas fa-users"></i>
                            <span>Students</span>
                        </a>
                        <a href="profile.php" class="action-card">
                            <i class="fas fa-user-edit"></i>
                            <span>Edit Profile</span>
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
