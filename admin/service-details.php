<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../admin-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

$service_id = isset($_GET['id']) ? $_GET['id'] : null;

if(!$service_id) {
    header('Location: services.php');
    exit();
}

// Get service details
$stmt = $conn->prepare("
    SELECT s.*, sc.category_name, sc.icon
    FROM services s
    JOIN service_categories sc ON s.category_id = sc.category_id
    WHERE s.service_id = ?
");
$stmt->execute([$service_id]);
$service = $stmt->fetch();

if(!$service) {
    header('Location: services.php?error=Service not found');
    exit();
}

// Get service statistics
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE service_id = ?");
$stmt->execute([$service_id]);
$total_bookings = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE service_id = ? AND status = 'completed'");
$stmt->execute([$service_id]);
$completed_bookings = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM staff_schedules WHERE service_id = ? AND status = 'active'");
$stmt->execute([$service_id]);
$active_schedules = $stmt->fetch()['total'];

// Get staff offering this service
$stmt = $conn->prepare("
    SELECT DISTINCT staff.*, COUNT(ss.schedule_id) as schedule_count
    FROM staff
    JOIN staff_schedules ss ON staff.staff_id = ss.staff_id
    WHERE ss.service_id = ? AND ss.status = 'active'
    GROUP BY staff.staff_id
");
$stmt->execute([$service_id]);
$staff_members = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Details - WSU Booking</title>
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
                    <a href="services.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Services
                    </a>
                </div>

                <!-- Success Message -->
                <?php if(isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Service Header -->
                <div class="hero-section">
                    <div class="hero-content">
                        <div style="display: flex; align-items: center; gap: 30px;">
                            <div style="width: 100px; height: 100px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 48px;">
                                <i class="fas <?php echo htmlspecialchars($service['icon']); ?>"></i>
                            </div>
                            <div style="flex: 1;">
                                <h1 style="margin-bottom: 10px;"><?php echo htmlspecialchars($service['service_name']); ?></h1>
                                <p style="font-size: 18px; opacity: 0.9; margin-bottom: 15px;">
                                    <?php echo htmlspecialchars($service['service_code']); ?> • <?php echo htmlspecialchars($service['category_name']); ?>
                                </p>
                                <span class="badge badge-<?php echo $service['status']; ?>" style="font-size: 14px; padding: 8px 20px;">
                                    <?php echo ucfirst($service['status']); ?>
                                </span>
                            </div>
                            <div>
                                <a href="edit-service.php?id=<?php echo $service['service_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-edit"></i> Edit Service
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
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count($staff_members); ?></h3>
                            <p>Staff Members</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $active_schedules; ?></h3>
                            <p>Active Schedules</p>
                        </div>
                    </div>
                </div>

                <!-- Service Information -->
                <div class="section">
                    <h3><i class="fas fa-info-circle"></i> Service Information</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; margin-top: 20px;">
                        <div class="info-item">
                            <label><i class="fas fa-hashtag"></i> Service Code</label>
                            <p><?php echo htmlspecialchars($service['service_code']); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-th-large"></i> Category</label>
                            <p><?php echo htmlspecialchars($service['category_name']); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-clock"></i> Duration</label>
                            <p><?php echo $service['duration_minutes']; ?> minutes</p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-hourglass-half"></i> Buffer Time</label>
                            <p><?php echo $service['buffer_time_minutes']; ?> minutes</p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-calendar-plus"></i> Max Advance Booking</label>
                            <p><?php echo $service['max_advance_booking_days']; ?> days</p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-times-circle"></i> Cancellation Notice</label>
                            <p><?php echo $service['cancellation_hours']; ?> hours</p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-check-square"></i> Requires Approval</label>
                            <p><?php echo $service['requires_approval'] ? 'Yes' : 'No'; ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-calendar"></i> Created</label>
                            <p><?php echo date('d F Y', strtotime($service['created_at'])); ?></p>
                        </div>
                    </div>
                    
                    <div style="margin-top: 25px;">
                        <label style="font-size: 13px; font-weight: 600; color: #6b7280; display: block; margin-bottom: 8px;">
                            <i class="fas fa-align-left"></i> Description
                        </label>
                        <p style="font-size: 15px; color: var(--dark); line-height: 1.6;">
                            <?php echo htmlspecialchars($service['description']); ?>
                        </p>
                    </div>
                </div>

                <!-- Staff Offering This Service -->
                <div class="section">
                    <h3><i class="fas fa-users"></i> Staff Offering This Service</h3>
                    <?php if(count($staff_members) > 0): ?>
                        <div class="students-table-container">
                            <table class="students-table">
                                <thead>
                                    <tr>
                                        <th>Staff Member</th>
                                        <th>Role</th>
                                        <th>Email</th>
                                        <th>Active Schedules</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($staff_members as $staff): ?>
                                    <tr>
                                        <td>
                                            <div class="student-info">
                                                <div class="student-avatar" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                                                    <?php echo strtoupper(substr($staff['first_name'], 0, 1) . substr($staff['last_name'], 0, 1)); ?>
                                                </div>
                                                <div class="student-details">
                                                    <h4><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></h4>
                                                    <p><?php echo htmlspecialchars($staff['staff_number']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo ucfirst(str_replace('_', ' ', $staff['role'])); ?></td>
                                        <td><?php echo htmlspecialchars($staff['email']); ?></td>
                                        <td><strong style="color: var(--blue);"><?php echo $staff['schedule_count']; ?></strong></td>
                                        <td>
                                            <span class="badge badge-<?php echo $staff['status']; ?>">
                                                <?php echo ucfirst($staff['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-user-times"></i>
                            <h3>No Staff Assigned</h3>
                            <p>No staff members are currently offering this service</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/dashboard.js"></script>
</body>
</html>
