<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['staff_id'])) {
    header('Location: ../staff-login.php');
    exit();
}

$db = new Database();
$conn = $db->connect();
$staff_id = $_SESSION['staff_id'];

// Get today's appointments
$today = date('Y-m-d');
$stmt = $conn->prepare("
    SELECT COUNT(*) as count FROM bookings 
    WHERE staff_id = ? AND booking_date = ? AND status IN ('pending', 'confirmed')
");
$stmt->execute([$staff_id, $today]);
$today_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get pending appointments
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM bookings WHERE staff_id = ? AND status = 'pending'");
$stmt->execute([$staff_id]);
$pending_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get total appointments this month
$stmt = $conn->prepare("
    SELECT COUNT(*) as count FROM bookings 
    WHERE staff_id = ? AND MONTH(booking_date) = MONTH(CURDATE()) AND YEAR(booking_date) = YEAR(CURDATE())
");
$stmt->execute([$staff_id]);
$month_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get upcoming appointments
$stmt = $conn->prepare("
    SELECT b.*, s.service_name, st.first_name, st.last_name, st.student_id
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN students st ON b.student_id = st.student_id
    WHERE b.staff_id = ? AND b.booking_date >= CURDATE() AND b.status IN ('pending', 'confirmed')
    ORDER BY b.booking_date, b.start_time
    LIMIT 5
");
$stmt->execute([$staff_id]);
$upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - WSU Booking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <div class="welcome-banner">
                    <div class="welcome-content">
                        <h1>Welcome back, <?php echo htmlspecialchars(explode(' ', $_SESSION['staff_name'])[0]); ?>!</h1>
                        <p>Here's your appointment overview for today</p>
                    </div>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $today_count; ?></h3>
                            <p>Today's Appointments</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $pending_count; ?></h3>
                            <p>Pending Requests</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $month_count; ?></h3>
                            <p>This Month</p>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Upcoming Appointments</h2>
                        <a href="appointments.php" class="btn btn-primary">View All</a>
                    </div>

                    <?php if (count($upcoming) > 0): ?>
                        <div class="bookings-list">
                            <?php foreach ($upcoming as $booking): ?>
                                <div class="booking-card">
                                    <div class="booking-icon">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <div class="booking-details">
                                        <h4><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></h4>
                                        <p><i class="fas fa-id-card"></i> <?php echo htmlspecialchars($booking['student_id']); ?></p>
                                        <p><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($booking['service_name']); ?></p>
                                        <p><i class="fas fa-calendar"></i> <?php echo date('D, M j, Y', strtotime($booking['booking_date'])); ?></p>
                                        <p><i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($booking['start_time'])); ?></p>
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
            </div>
        </div>
    </div>

    <script src="js/dashboard.js"></script>
</body>
</html>
