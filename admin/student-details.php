<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

$student_id = isset($_GET['id']) ? $_GET['id'] : null;

if(!$student_id) {
    header('Location: students-management.php');
    exit();
}

// Get student details
$stmt = $conn->prepare("
    SELECT s.*, f.faculty_name, f.faculty_code
    FROM students s
    LEFT JOIN faculties f ON s.faculty_id = f.faculty_id
    WHERE s.student_id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if(!$student) {
    header('Location: students-management.php?error=Student not found');
    exit();
}

// Get student statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE student_id = ?");
$stmt->execute([$student_id]);
$total_bookings = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE student_id = ? AND status = 'completed'");
$stmt->execute([$student_id]);
$completed_bookings = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE student_id = ? AND status = 'pending'");
$stmt->execute([$student_id]);
$pending_bookings = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE student_id = ? AND status = 'cancelled'");
$stmt->execute([$student_id]);
$cancelled_bookings = $stmt->fetch()['total'];

// Get recent bookings
$stmt = $conn->prepare("
    SELECT b.*, s.service_name, sc.category_name, staff.first_name as staff_first, staff.last_name as staff_last
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN service_categories sc ON s.category_id = sc.category_id
    JOIN staff ON b.staff_id = staff.staff_id
    WHERE b.student_id = ?
    ORDER BY b.booking_date DESC, b.start_time DESC
    LIMIT 10
");
$stmt->execute([$student_id]);
$recent_bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <!-- Back Button -->
                <div style="margin-bottom: 20px;">
                    <a href="students-management.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Students
                    </a>
                </div>

                <!-- Student Profile Header -->
                <div class="hero-section">
                    <div class="hero-content">
                        <div style="display: flex; align-items: center; gap: 30px;">
                            <div style="width: 100px; height: 100px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 48px;">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div style="flex: 1;">
                                <h1 style="margin-bottom: 10px;"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h1>
                                <p style="font-size: 18px; opacity: 0.9; margin-bottom: 15px;">
                                    <?php echo htmlspecialchars($student['student_id']); ?> • Year <?php echo $student['year_of_study']; ?> • <?php echo ucfirst($student['student_type']); ?>
                                </p>
                                <span class="badge badge-<?php echo $student['status']; ?>" style="font-size: 14px; padding: 8px 20px;">
                                    <?php echo ucfirst($student['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_bookings; ?></h3>
                            <p>Total Bookings</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $completed_bookings; ?></h3>
                            <p>Completed</p>
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
                        <div class="stat-icon red">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $cancelled_bookings; ?></h3>
                            <p>Cancelled</p>
                        </div>
                    </div>
                </div>

                <!-- Student Information -->
                <div class="section">
                    <h3><i class="fas fa-info-circle"></i> Student Information</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; margin-top: 20px;">
                        <div class="info-item">
                            <label><i class="fas fa-id-card"></i> Student ID</label>
                            <p><?php echo htmlspecialchars($student['student_id']); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-envelope"></i> Email</label>
                            <p><?php echo htmlspecialchars($student['email']); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-phone"></i> Phone</label>
                            <p><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-university"></i> Faculty</label>
                            <p><?php echo htmlspecialchars($student['faculty_name'] ?? 'Not assigned'); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-book"></i> Year of Study</label>
                            <p>Year <?php echo $student['year_of_study']; ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-graduation-cap"></i> Student Type</label>
                            <p><?php echo ucfirst($student['student_type']); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-calendar"></i> Registered</label>
                            <p><?php echo date('d F Y', strtotime($student['created_at'])); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-clock"></i> Last Login</label>
                            <p><?php echo $student['last_login'] ? date('d M Y H:i', strtotime($student['last_login'])) : 'Never'; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Recent Bookings -->
                <div class="section">
                    <h3><i class="fas fa-history"></i> Booking History</h3>
                    <?php if(count($recent_bookings) > 0): ?>
                        <div class="students-table-container">
                            <table class="students-table">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Service</th>
                                        <th>Staff</th>
                                        <th>Date & Time</th>
                                        <th>Location</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_bookings as $booking): ?>
                                    <tr>
                                        <td><strong style="font-family: monospace; color: var(--blue);"><?php echo htmlspecialchars($booking['booking_reference']); ?></strong></td>
                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                                <span style="font-weight: 600;"><?php echo htmlspecialchars($booking['service_name']); ?></span>
                                                <span class="badge badge-blue" style="width: fit-content;"><?php echo htmlspecialchars($booking['category_name']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($booking['staff_first'] . ' ' . $booking['staff_last']); ?></td>
                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                                <span style="font-weight: 600;"><?php echo date('d M Y', strtotime($booking['booking_date'])); ?></span>
                                                <span style="font-size: 12px; color: #6b7280;">
                                                    <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($booking['start_time'])); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($booking['location']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $booking['status']; ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No Bookings Yet</h3>
                            <p>This student has not made any bookings</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/dashboard.js"></script>
</body>
</html>
