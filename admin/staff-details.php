<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../admin-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

$staff_id = isset($_GET['id']) ? $_GET['id'] : null;

if(!$staff_id) {
    header('Location: staff-management.php');
    exit();
}

// Get staff details
$stmt = $conn->prepare("
    SELECT s.*, d.department_name, f.faculty_name
    FROM staff s
    LEFT JOIN departments d ON s.department_id = d.department_id
    LEFT JOIN faculties f ON d.faculty_id = f.faculty_id
    WHERE s.staff_id = ?
");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch();

if(!$staff) {
    header('Location: staff-management.php?error=Staff not found');
    exit();
}

// Get staff statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE staff_id = ?");
$stmt->execute([$staff_id]);
$total_bookings = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE staff_id = ? AND status = 'completed'");
$stmt->execute([$staff_id]);
$completed_bookings = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE staff_id = ? AND status = 'pending'");
$stmt->execute([$staff_id]);
$pending_bookings = $stmt->fetch()['total'];

// Get staff schedules
$stmt = $conn->prepare("
    SELECT ss.*, s.service_name
    FROM staff_schedules ss
    JOIN services s ON ss.service_id = s.service_id
    WHERE ss.staff_id = ? AND ss.status = 'active'
    ORDER BY ss.day_of_week, ss.start_time
");
$stmt->execute([$staff_id]);
$schedules = $stmt->fetchAll();

// Get recent bookings
$stmt = $conn->prepare("
    SELECT b.*, s.service_name, st.first_name, st.last_name, st.student_id
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN students st ON b.student_id = st.student_id
    WHERE b.staff_id = ?
    ORDER BY b.booking_date DESC, b.start_time DESC
    LIMIT 10
");
$stmt->execute([$staff_id]);
$recent_bookings = $stmt->fetchAll();

$days = ['', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Details - WSU Booking</title>
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
                    <a href="staff-management.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Staff Management
                    </a>
                </div>

                <!-- Success Message -->
                <?php if(isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Staff Profile Header -->
                <div class="hero-section">
                    <div class="hero-content">
                        <div style="display: flex; align-items: center; gap: 30px;">
                            <div style="width: 100px; height: 100px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 48px;">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div style="flex: 1;">
                                <h1 style="margin-bottom: 10px;"><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></h1>
                                <p style="font-size: 18px; opacity: 0.9; margin-bottom: 15px;">
                                    <?php echo htmlspecialchars($staff['staff_number']); ?> • <?php echo ucfirst(str_replace('_', ' ', $staff['role'])); ?>
                                </p>
                                <span class="badge badge-<?php echo $staff['status']; ?>" style="font-size: 14px; padding: 8px 20px;">
                                    <?php echo ucfirst($staff['status']); ?>
                                </span>
                            </div>
                            <div>
                                <a href="edit-staff.php?id=<?php echo $staff['staff_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-edit"></i> Edit Staff
                                </a>
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
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count($schedules); ?></h3>
                            <p>Active Schedules</p>
                        </div>
                    </div>
                </div>

                <!-- Staff Information -->
                <div class="section">
                    <h3><i class="fas fa-info-circle"></i> Staff Information</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; margin-top: 20px;">
                        <div class="info-item">
                            <label><i class="fas fa-id-badge"></i> Staff Number</label>
                            <p><?php echo htmlspecialchars($staff['staff_number']); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-envelope"></i> Email</label>
                            <p><?php echo htmlspecialchars($staff['email']); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-phone"></i> Phone</label>
                            <p><?php echo htmlspecialchars($staff['phone'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-briefcase"></i> Role</label>
                            <p><?php echo ucfirst(str_replace('_', ' ', $staff['role'])); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-building"></i> Department</label>
                            <p><?php echo htmlspecialchars($staff['department_name'] ?? 'Not assigned'); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-university"></i> Faculty</label>
                            <p><?php echo htmlspecialchars($staff['faculty_name'] ?? 'Not assigned'); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-graduation-cap"></i> Qualification</label>
                            <p><?php echo htmlspecialchars($staff['qualification'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-star"></i> Specialization</label>
                            <p><?php echo htmlspecialchars($staff['specialization'] ?? 'N/A'); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-calendar"></i> Joined</label>
                            <p><?php echo date('d F Y', strtotime($staff['created_at'])); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-clock"></i> Last Login</label>
                            <p><?php echo $staff['last_login'] ? date('d M Y H:i', strtotime($staff['last_login'])) : 'Never'; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Schedules -->
                <div class="section">
                    <h3><i class="fas fa-calendar-alt"></i> Active Schedules</h3>
                    <?php if(count($schedules) > 0): ?>
                        <div class="students-table-container">
                            <table class="students-table">
                                <thead>
                                    <tr>
                                        <th>Day</th>
                                        <th>Service</th>
                                        <th>Time</th>
                                        <th>Duration</th>
                                        <th>Location</th>
                                        <th>Effective From</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($schedules as $schedule): ?>
                                    <tr>
                                        <td><strong><?php echo $days[$schedule['day_of_week']]; ?></strong></td>
                                        <td><?php echo htmlspecialchars($schedule['service_name']); ?></td>
                                        <td><?php echo date('H:i', strtotime($schedule['start_time'])); ?> - <?php echo date('H:i', strtotime($schedule['end_time'])); ?></td>
                                        <td><?php echo $schedule['slot_duration']; ?> min</td>
                                        <td><?php echo htmlspecialchars($schedule['location']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($schedule['effective_from'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No Active Schedules</h3>
                            <p>This staff member has no active schedules</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Bookings -->
                <div class="section">
                    <h3><i class="fas fa-history"></i> Recent Bookings</h3>
                    <?php if(count($recent_bookings) > 0): ?>
                        <div class="students-table-container">
                            <table class="students-table">
                                <thead>
                                    <tr>
                                        <th>Reference</th>
                                        <th>Student</th>
                                        <th>Service</th>
                                        <th>Date & Time</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_bookings as $booking): ?>
                                    <tr>
                                        <td><strong style="font-family: monospace; color: var(--blue);"><?php echo htmlspecialchars($booking['booking_reference']); ?></strong></td>
                                        <td>
                                            <div class="student-info">
                                                <div class="student-avatar">
                                                    <?php echo strtoupper(substr($booking['first_name'], 0, 1) . substr($booking['last_name'], 0, 1)); ?>
                                                </div>
                                                <div class="student-details">
                                                    <h4><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></h4>
                                                    <p><?php echo htmlspecialchars($booking['student_id']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($booking['service_name']); ?></td>
                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                                <span style="font-weight: 600;"><?php echo date('d M Y', strtotime($booking['booking_date'])); ?></span>
                                                <span style="font-size: 12px; color: #6b7280;">
                                                    <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($booking['start_time'])); ?>
                                                </span>
                                            </div>
                                        </td>
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
                            <p>This staff member has no bookings</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/dashboard.js"></script>
</body>
</html>
