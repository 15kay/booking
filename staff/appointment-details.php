<?php
session_start();
if(!isset($_SESSION['staff_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

if(!isset($_GET['id'])) {
    header('Location: appointments.php?error=Invalid request');
    exit();
}

$booking_id = $_GET['id'];

$db = new Database();
$conn = $db->connect();

// Get appointment details
$stmt = $conn->prepare("
    SELECT b.*, s.service_name, s.service_code, s.description as service_description,
           st.first_name, st.last_name, st.student_id, st.email, st.phone, st.year_of_study, st.student_type,
           sc.category_name,
           f.faculty_name
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN students st ON b.student_id = st.student_id
    JOIN service_categories sc ON s.category_id = sc.category_id
    LEFT JOIN faculties f ON st.faculty_id = f.faculty_id
    WHERE b.booking_id = ? AND b.staff_id = ?
");
$stmt->execute([$booking_id, $_SESSION['staff_id']]);
$appointment = $stmt->fetch();

if(!$appointment) {
    header('Location: appointments.php?error=Appointment not found');
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

// Get student's booking statistics for success score
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled,
        COUNT(CASE WHEN status = 'no_show' THEN 1 END) as no_shows
    FROM bookings 
    WHERE student_id = ?
");
$stmt->execute([$appointment['student_id']]);
$student_stats = $stmt->fetch();

// Calculate success score
$success_score = 0;
if($student_stats['total_bookings'] > 0) {
    $completion_rate = ($student_stats['completed'] / $student_stats['total_bookings']) * 100;
    $engagement_level = min(100, ($student_stats['total_bookings'] / 10) * 100);
    $no_show_penalty = ($student_stats['no_shows'] * 10);
    $cancelled_penalty = ($student_stats['cancelled'] * 5);
    
    $success_score = max(0, min(100, 
        ($completion_rate * 0.5) + 
        ($engagement_level * 0.3) + 
        (20) - 
        $no_show_penalty - 
        $cancelled_penalty
    ));
}
$success_score = round($success_score);

// Determine score color and label
if($success_score >= 80) {
    $score_color = '#10b981';
    $score_bg = '#d1fae5';
    $score_label = 'Excellent';
} elseif($success_score >= 60) {
    $score_color = '#2563eb';
    $score_bg = '#dbeafe';
    $score_label = 'Good';
} elseif($success_score >= 40) {
    $score_color = '#f59e0b';
    $score_bg = '#fef3c7';
    $score_label = 'Fair';
} else {
    $score_color = '#ef4444';
    $score_bg = '#fee2e2';
    $score_label = 'Needs Support';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Details - WSU Booking</title>
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
                        <h1>Appointment Details</h1>
                        <p>Complete information about this appointment and student</p>
                        <div class="hero-stats">
                            <div class="hero-stat">
                                <i class="fas fa-hashtag"></i>
                                <span><?php echo htmlspecialchars($appointment['booking_reference']); ?></span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo date('d M Y', strtotime($appointment['booking_date'])); ?></span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-clock"></i>
                                <span><?php echo date('H:i', strtotime($appointment['start_time'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Back Button -->
                <div class="detail-header">
                    <a href="appointments.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Back to Appointments
                    </a>
                </div>

                <!-- Appointment Header -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($appointment['service_name']); ?></h2>
                        <p><?php echo htmlspecialchars($appointment['booking_reference']); ?></p>
                        <span class="status-badge <?php echo $appointment['status']; ?>">
                            <?php echo ucfirst($appointment['status']); ?>
                        </span>
                    </div>
                </div>

                <!-- Details Grid -->
                <div class="profile-grid">
                    <!-- Appointment Information -->
                    <div class="profile-section">
                        <h3><i class="fas fa-info-circle"></i> Appointment Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Service</label>
                                <p><?php echo htmlspecialchars($appointment['service_name']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Category</label>
                                <p><?php echo htmlspecialchars($appointment['category_name']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Service Code</label>
                                <p><?php echo htmlspecialchars($appointment['service_code']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Date</label>
                                <p><?php echo date('l, d F Y', strtotime($appointment['booking_date'])); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Time</label>
                                <p><?php echo date('H:i', strtotime($appointment['start_time'])); ?> - <?php echo date('H:i', strtotime($appointment['end_time'])); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Location</label>
                                <p><?php echo htmlspecialchars($appointment['location']); ?></p>
                            </div>
                            <?php if(!empty($appointment['notes'])): ?>
                            <div class="info-item">
                                <label>Notes</label>
                                <p><?php echo htmlspecialchars($appointment['notes']); ?></p>
                            </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <label>Booked On</label>
                                <p><?php echo date('d M Y, H:i', strtotime($appointment['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Student Information -->
                    <div class="profile-section">
                        <h3><i class="fas fa-user"></i> Student Information</h3>
                        
                        <!-- Success Score Display -->
                        <div style="background: <?php echo $score_bg; ?>; border: 2px solid <?php echo $score_color; ?>; border-radius: 12px; padding: 20px; margin-bottom: 20px; display: flex; align-items: center; gap: 20px;">
                            <div style="position: relative; width: 80px; height: 80px; flex-shrink: 0;">
                                <svg style="transform: rotate(-90deg);" width="80" height="80">
                                    <circle cx="40" cy="40" r="35" fill="none" stroke="rgba(0,0,0,0.1)" stroke-width="6"/>
                                    <circle cx="40" cy="40" r="35" fill="none" stroke="<?php echo $score_color; ?>" stroke-width="6" 
                                            stroke-dasharray="<?php echo (2 * 3.14159 * 35); ?>" 
                                            stroke-dashoffset="<?php echo (2 * 3.14159 * 35) * (1 - $success_score / 100); ?>"
                                            stroke-linecap="round"/>
                                </svg>
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: 700; font-size: 24px; color: <?php echo $score_color; ?>;">
                                    <?php echo $success_score; ?>
                                </div>
                            </div>
                            <div style="flex: 1;">
                                <h4 style="color: <?php echo $score_color; ?>; font-size: 20px; margin-bottom: 8px;">Student Success Score</h4>
                                <p style="color: <?php echo $score_color; ?>; font-weight: 600; font-size: 16px; margin-bottom: 8px;">
                                    Performance: <?php echo $score_label; ?>
                                </p>
                                <div style="display: flex; gap: 15px; font-size: 13px; color: #6b7280;">
                                    <span><i class="fas fa-calendar-check"></i> <?php echo $student_stats['completed']; ?> completed</span>
                                    <span><i class="fas fa-calendar"></i> <?php echo $student_stats['total_bookings']; ?> total</span>
                                    <?php if($student_stats['no_shows'] > 0): ?>
                                        <span style="color: #ef4444;"><i class="fas fa-user-times"></i> <?php echo $student_stats['no_shows']; ?> no-shows</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <a href="student-profile.php?id=<?php echo $appointment['student_id']; ?>" class="btn btn-secondary" style="flex-shrink: 0;">
                                <i class="fas fa-user"></i> View Full Profile
                            </a>
                        </div>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Student Name</label>
                                <p><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Student ID</label>
                                <p><?php echo htmlspecialchars($appointment['student_id']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Email</label>
                                <p><?php echo htmlspecialchars($appointment['email']); ?></p>
                            </div>
                            <?php if(!empty($appointment['phone'])): ?>
                            <div class="info-item">
                                <label>Phone</label>
                                <p><?php echo htmlspecialchars($appointment['phone']); ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if(!empty($appointment['faculty_name'])): ?>
                            <div class="info-item">
                                <label>Faculty</label>
                                <p><?php echo htmlspecialchars($appointment['faculty_name']); ?></p>
                            </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <label>Year of Study</label>
                                <p>Year <?php echo $appointment['year_of_study']; ?></p>
                            </div>
                            <div class="info-item">
                                <label>Student Type</label>
                                <p><?php echo ucfirst(str_replace('_', ' ', $appointment['student_type'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <?php if($appointment['status'] == 'pending' || $appointment['status'] == 'confirmed'): ?>
                <div class="section">
                    <h3>Actions</h3>
                    <div class="form-actions">
                        <?php if($appointment['status'] == 'pending'): ?>
                            <button class="btn btn-primary" onclick="updateStatus(<?php echo $appointment['booking_id']; ?>, 'confirmed')">
                                <i class="fas fa-check"></i> Confirm Appointment
                            </button>
                            <button class="btn btn-danger" onclick="updateStatus(<?php echo $appointment['booking_id']; ?>, 'cancelled')">
                                <i class="fas fa-times"></i> Decline Appointment
                            </button>
                        <?php elseif($appointment['status'] == 'confirmed'): ?>
                            <button class="btn btn-primary" onclick="updateStatus(<?php echo $appointment['booking_id']; ?>, 'completed')">
                                <i class="fas fa-check-circle"></i> Mark as Completed
                            </button>
                            <button class="btn btn-secondary" onclick="updateStatus(<?php echo $appointment['booking_id']; ?>, 'no_show')">
                                <i class="fas fa-user-times"></i> Mark as No-Show
                            </button>
                            <button class="btn btn-danger" onclick="updateStatus(<?php echo $appointment['booking_id']; ?>, 'cancelled')">
                                <i class="fas fa-times"></i> Cancel Appointment
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Booking History -->
                <?php if(count($history) > 0): ?>
                <div class="section">
                    <h3><i class="fas fa-history"></i> Booking History</h3>
                    <div class="notifications-list">
                        <?php foreach($history as $record): ?>
                        <div class="notification-item">
                            <div class="notification-icon blue">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="notification-content">
                                <h4><?php echo ucfirst($record['action']); ?></h4>
                                <p>
                                    <?php if($record['old_status']): ?>
                                        Status changed from <strong><?php echo $record['old_status']; ?></strong> to <strong><?php echo $record['new_status']; ?></strong>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($record['notes']); ?>
                                    <?php endif; ?>
                                </p>
                                <span class="notification-time">
                                    <i class="fas fa-clock"></i>
                                    <?php echo date('d M Y, H:i', strtotime($record['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include '../assets/includes/modals.php'; ?>
    <link rel="stylesheet" href="../assets/css/modals.css">
    <script src="../assets/js/modals.js"></script>
    <script src="js/dashboard.js"></script>
    <script>
        function updateStatus(bookingId, newStatus) {
            const messages = {
                'confirmed': 'Are you sure you want to confirm this appointment?',
                'cancelled': 'Are you sure you want to cancel this appointment?',
                'completed': 'Mark this appointment as completed?',
                'no_show': 'Mark this student as no-show?'
            };
            
            const titles = {
                'confirmed': 'Confirm Appointment',
                'cancelled': 'Cancel Appointment',
                'completed': 'Complete Appointment',
                'no_show': 'Mark No-Show'
            };
            
            showConfirmModal(
                titles[newStatus],
                messages[newStatus],
                function() {
                    window.location.href = 'update-appointment.php?id=' + bookingId + '&status=' + newStatus;
                }
            );
        }
    </script>
</body>
</html>
