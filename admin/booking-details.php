<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../admin-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

$booking_id = isset($_GET['id']) ? $_GET['id'] : null;

if(!$booking_id) {
    header('Location: bookings.php');
    exit();
}

// Get booking details
$stmt = $conn->prepare("
    SELECT b.*, s.service_name, s.service_code, sc.category_name,
           st.first_name as student_first, st.last_name as student_last, st.student_id, st.email as student_email, st.phone as student_phone,
           staff.first_name as staff_first, staff.last_name as staff_last, staff.staff_number, staff.email as staff_email
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN service_categories sc ON s.category_id = sc.category_id
    JOIN students st ON b.student_id = st.student_id
    JOIN staff ON b.staff_id = staff.staff_id
    WHERE b.booking_id = ?
");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

if(!$booking) {
    header('Location: bookings.php?error=Booking not found');
    exit();
}

// Get booking history
$stmt = $conn->prepare("
    SELECT * FROM booking_history
    WHERE booking_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$booking_id]);
$history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - WSU Booking</title>
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
                    <a href="bookings.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Bookings
                    </a>
                </div>

                <!-- Booking Header -->
                <div class="hero-section">
                    <div class="hero-content">
                        <div style="display: flex; align-items: center; gap: 30px;">
                            <div style="width: 100px; height: 100px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 48px;">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div style="flex: 1;">
                                <h1 style="margin-bottom: 10px;"><?php echo htmlspecialchars($booking['service_name']); ?></h1>
                                <p style="font-size: 18px; opacity: 0.9; margin-bottom: 15px;">
                                    <?php echo htmlspecialchars($booking['booking_reference']); ?> • <?php echo htmlspecialchars($booking['category_name']); ?>
                                </p>
                                <span class="badge badge-<?php echo $booking['status']; ?>" style="font-size: 14px; padding: 8px 20px;">
                                    <?php echo ucfirst($booking['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Booking Information -->
                <div class="section">
                    <h3><i class="fas fa-info-circle"></i> Booking Information</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; margin-top: 20px;">
                        <div class="info-item">
                            <label><i class="fas fa-hashtag"></i> Booking Reference</label>
                            <p style="font-family: monospace; color: var(--blue); font-weight: 600;"><?php echo htmlspecialchars($booking['booking_reference']); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-calendar"></i> Booking Date</label>
                            <p><?php echo date('l, d F Y', strtotime($booking['booking_date'])); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-clock"></i> Time</label>
                            <p><?php echo date('H:i', strtotime($booking['start_time'])); ?> - <?php echo date('H:i', strtotime($booking['end_time'])); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-map-marker-alt"></i> Location</label>
                            <p><?php echo htmlspecialchars($booking['location']); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-concierge-bell"></i> Service</label>
                            <p><?php echo htmlspecialchars($booking['service_name']); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-th-large"></i> Category</label>
                            <p><?php echo htmlspecialchars($booking['category_name']); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-calendar-plus"></i> Created</label>
                            <p><?php echo date('d M Y H:i', strtotime($booking['created_at'])); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-sync"></i> Last Updated</label>
                            <p><?php echo date('d M Y H:i', strtotime($booking['updated_at'])); ?></p>
                        </div>
                    </div>
                    
                    <?php if(!empty($booking['notes'])): ?>
                    <div style="margin-top: 25px;">
                        <label style="font-size: 13px; font-weight: 600; color: #6b7280; display: block; margin-bottom: 8px;">
                            <i class="fas fa-sticky-note"></i> Notes
                        </label>
                        <p style="font-size: 15px; color: var(--dark); line-height: 1.6; background: #f9fafb; padding: 15px; border-radius: 8px;">
                            <?php echo htmlspecialchars($booking['notes']); ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if($booking['status'] == 'cancelled' && !empty($booking['cancellation_reason'])): ?>
                    <div style="margin-top: 25px;">
                        <label style="font-size: 13px; font-weight: 600; color: #ef4444; display: block; margin-bottom: 8px;">
                            <i class="fas fa-times-circle"></i> Cancellation Reason
                        </label>
                        <p style="font-size: 15px; color: var(--dark); line-height: 1.6; background: #fee2e2; padding: 15px; border-radius: 8px; border-left: 4px solid #ef4444;">
                            <?php echo htmlspecialchars($booking['cancellation_reason']); ?>
                            <?php if($booking['cancelled_by']): ?>
                                <br><small style="color: #6b7280; margin-top: 8px; display: block;">Cancelled by: <?php echo ucfirst($booking['cancelled_by']); ?> on <?php echo date('d M Y H:i', strtotime($booking['cancelled_at'])); ?></small>
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Student Information -->
                <div class="section">
                    <h3><i class="fas fa-user-graduate"></i> Student Information</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; margin-top: 20px;">
                        <div class="info-item">
                            <label><i class="fas fa-user"></i> Name</label>
                            <p><?php echo htmlspecialchars($booking['student_first'] . ' ' . $booking['student_last']); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-id-card"></i> Student ID</label>
                            <p><?php echo htmlspecialchars($booking['student_id']); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-envelope"></i> Email</label>
                            <p><?php echo htmlspecialchars($booking['student_email']); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-phone"></i> Phone</label>
                            <p><?php echo htmlspecialchars($booking['student_phone'] ?? 'N/A'); ?></p>
                        </div>
                    </div>
                    <div style="margin-top: 20px;">
                        <a href="student-details.php?id=<?php echo urlencode($booking['student_id']); ?>" class="btn btn-secondary">
                            <i class="fas fa-eye"></i> View Student Profile
                        </a>
                    </div>
                </div>

                <!-- Staff Information -->
                <div class="section">
                    <h3><i class="fas fa-user-tie"></i> Staff Information</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; margin-top: 20px;">
                        <div class="info-item">
                            <label><i class="fas fa-user"></i> Name</label>
                            <p><?php echo htmlspecialchars($booking['staff_first'] . ' ' . $booking['staff_last']); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-id-badge"></i> Staff Number</label>
                            <p><?php echo htmlspecialchars($booking['staff_number']); ?></p>
                        </div>
                        <div class="info-item">
                            <label><i class="fas fa-envelope"></i> Email</label>
                            <p><?php echo htmlspecialchars($booking['staff_email']); ?></p>
                        </div>
                    </div>
                    <div style="margin-top: 20px;">
                        <a href="staff-details.php?id=<?php echo $booking['staff_id']; ?>" class="btn btn-secondary">
                            <i class="fas fa-eye"></i> View Staff Profile
                        </a>
                    </div>
                </div>

                <!-- Booking History -->
                <?php if(count($history) > 0): ?>
                <div class="section">
                    <h3><i class="fas fa-history"></i> Booking History</h3>
                    <div style="margin-top: 20px;">
                        <?php foreach($history as $entry): ?>
                        <div style="display: flex; gap: 20px; padding: 15px; background: #f9fafb; border-radius: 8px; margin-bottom: 12px; border-left: 4px solid var(--blue);">
                            <div style="flex-shrink: 0;">
                                <div style="width: 40px; height: 40px; background: var(--blue); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-<?php 
                                        echo $entry['action'] == 'created' ? 'plus' : 
                                            ($entry['action'] == 'confirmed' ? 'check' : 
                                            ($entry['action'] == 'cancelled' ? 'times' : 
                                            ($entry['action'] == 'completed' ? 'check-circle' : 'sync'))); 
                                    ?>"></i>
                                </div>
                            </div>
                            <div style="flex: 1;">
                                <h4 style="margin-bottom: 5px; font-size: 15px;"><?php echo ucfirst($entry['action']); ?></h4>
                                <?php if($entry['old_status'] && $entry['new_status']): ?>
                                    <p style="font-size: 13px; color: #6b7280; margin-bottom: 5px;">
                                        Status changed from <strong><?php echo $entry['old_status']; ?></strong> to <strong><?php echo $entry['new_status']; ?></strong>
                                    </p>
                                <?php endif; ?>
                                <?php if($entry['notes']): ?>
                                    <p style="font-size: 13px; color: #6b7280; margin-bottom: 5px;"><?php echo htmlspecialchars($entry['notes']); ?></p>
                                <?php endif; ?>
                                <p style="font-size: 12px; color: #9ca3af;">
                                    <?php echo date('d M Y H:i', strtotime($entry['created_at'])); ?>
                                    <?php if($entry['changed_by']): ?>
                                        • by <?php echo htmlspecialchars($entry['changed_by']); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="js/dashboard.js"></script>
</body>
</html>
