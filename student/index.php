<?php
session_start();
if(!isset($_SESSION['student_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get student stats
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE student_id = ?");
$stmt->execute([$_SESSION['student_id']]);
$total_bookings = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as pending FROM bookings WHERE student_id = ? AND status = 'pending'");
$stmt->execute([$_SESSION['student_id']]);
$pending_bookings = $stmt->fetch()['pending'];

$stmt = $conn->prepare("SELECT COUNT(*) as confirmed FROM bookings WHERE student_id = ? AND status = 'confirmed'");
$stmt->execute([$_SESSION['student_id']]);
$confirmed_bookings = $stmt->fetch()['confirmed'];

$stmt = $conn->prepare("SELECT COUNT(*) as completed FROM bookings WHERE student_id = ? AND status = 'completed'");
$stmt->execute([$_SESSION['student_id']]);
$completed_bookings = $stmt->fetch()['completed'];

// Get booking statistics for success score
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_sessions,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_sessions,
        COUNT(CASE WHEN status = 'no_show' THEN 1 END) as no_show_sessions
    FROM bookings 
    WHERE student_id = ?
");
$stmt->execute([$_SESSION['student_id']]);
$booking_stats = $stmt->fetch();

// Calculate success score
$success_score = 0;
if($booking_stats['total_bookings'] > 0) {
    $completion_rate = ($booking_stats['completed_sessions'] / $booking_stats['total_bookings']) * 100;
    $engagement_level = min(100, ($booking_stats['total_bookings'] / 10) * 100);
    $no_show_penalty = ($booking_stats['no_show_sessions'] * 10);
    $cancelled_penalty = ($booking_stats['cancelled_sessions'] * 5);
    
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
    $score_icon = 'fa-star';
} elseif($success_score >= 60) {
    $score_color = '#2563eb';
    $score_bg = '#dbeafe';
    $score_label = 'Good';
    $score_icon = 'fa-thumbs-up';
} elseif($success_score >= 40) {
    $score_color = '#f59e0b';
    $score_bg = '#fef3c7';
    $score_label = 'Fair';
    $score_icon = 'fa-hand-paper';
} else {
    $score_color = '#ef4444';
    $score_bg = '#fee2e2';
    $score_label = 'Needs Outreach';
    $score_icon = 'fa-exclamation-triangle';
}

// Get upcoming bookings
$stmt = $conn->prepare("
    SELECT b.*, s.service_name, st.first_name, st.last_name 
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN staff st ON b.staff_id = st.staff_id
    WHERE b.student_id = ? AND b.booking_date >= CURDATE() AND b.status IN ('pending', 'confirmed')
    ORDER BY b.booking_date, b.start_time
    LIMIT 5
");
$stmt->execute([$_SESSION['student_id']]);
$upcoming_bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - WSU Booking</title>
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
                <!-- Welcome Section -->
                <div class="welcome-banner">
                    <div class="welcome-content">
                        <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>! 👋</h1>
                        <p>Manage your appointments and explore available services</p>
                    </div>
                    <a href="book-service.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Book New Service
                    </a>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <!-- Success Score Card -->
                    <div class="stat-card readiness-card" style="background: linear-gradient(135deg, <?php echo $score_bg; ?> 0%, <?php echo $score_bg; ?> 100%); border: 2px solid <?php echo $score_color; ?>;">
                        <div class="readiness-card-content">
                            <div class="score-circle-small">
                                <svg style="transform: rotate(-90deg);" width="100" height="100" viewBox="0 0 100 100">
                                    <circle cx="50" cy="50" r="42" fill="none" stroke="rgba(0,0,0,0.1)" stroke-width="8"/>
                                    <circle cx="50" cy="50" r="42" fill="none" stroke="<?php echo $score_color; ?>" stroke-width="8" 
                                            stroke-dasharray="<?php echo (2 * 3.14159 * 42); ?>" 
                                            stroke-dashoffset="<?php echo (2 * 3.14159 * 42) * (1 - $success_score / 100); ?>"
                                            stroke-linecap="round"/>
                                </svg>
                                <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                                    <div class="score-number" style="font-weight: 700; font-size: 32px; color: <?php echo $score_color; ?>; line-height: 1;">
                                        <?php echo $success_score; ?>
                                    </div>
                                    <div class="score-label" style="font-size: 11px; color: <?php echo $score_color; ?>; font-weight: 600;">
                                        SCORE
                                    </div>
                                </div>
                            </div>
                            <div class="score-info">
                                <h3 style="color: <?php echo $score_color; ?>; font-size: 24px; margin-bottom: 8px; display: flex; align-items: center; gap: 10px;">
                                    <i class="fas <?php echo $score_icon; ?>"></i>
                                    Your Readiness Score
                                </h3>
                                <p style="color: <?php echo $score_color; ?>; font-weight: 600; font-size: 18px; margin-bottom: 12px;">
                                    Engagement Level: <?php echo $score_label; ?>
                                </p>
                                <div class="score-metrics">
                                    <span><i class="fas fa-calendar-check"></i> <?php echo $booking_stats['completed_sessions']; ?> sessions attended</span>
                                    <span><i class="fas fa-calendar"></i> <?php echo $booking_stats['total_bookings']; ?> total bookings</span>
                                    <?php if($booking_stats['no_show_sessions'] > 0): ?>
                                        <span style="color: #ef4444;"><i class="fas fa-user-times"></i> <?php echo $booking_stats['no_show_sessions']; ?> missed</span>
                                    <?php endif; ?>
                                </div>
                                <p class="info-text" style="font-size: 12px; color: #6b7280; margin-top: 10px; font-style: italic;">
                                    <i class="fas fa-info-circle"></i> Track your engagement with support services before coursework begins
                                </p>
                            </div>
                        </div>
                    </div>
                    
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
                        <a href="my-bookings.php" class="btn-link">View All <i class="fas fa-arrow-right"></i></a>
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
                                    <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></p>
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
                            <a href="book-service.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Book Your First Service
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3>Quick Actions</h3>
                    <div class="actions-grid">
                        <a href="book-service.php" class="action-card">
                            <i class="fas fa-calendar-plus"></i>
                            <span>Book Service</span>
                        </a>
                        <a href="my-bookings.php" class="action-card">
                            <i class="fas fa-list"></i>
                            <span>My Bookings</span>
                        </a>
                        <!-- <a href="history.php" class="action-card">
                            <i class="fas fa-history"></i>
                            <span>History</span>
                        </a> -->
                        <a href="profile.php" class="action-card">
                            <i class="fas fa-user-edit"></i>
                            <span>Edit Profile</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Success Modal -->
    <?php if(isset($_GET['success'])): ?>
    <div class="modal-overlay" id="successModal">
        <div class="modal">
            <div class="modal-icon">
                <i class="fas fa-check"></i>
            </div>
            <h2>Booking Successful!</h2>
            <p><?php echo htmlspecialchars($_GET['success']); ?></p>
            <?php if(preg_match('/Reference: (BK\d+)/', $_GET['success'], $matches)): ?>
            <div class="booking-ref"><?php echo $matches[1]; ?></div>
            <?php endif; ?>
            <div class="modal-actions">
                <button class="modal-btn modal-btn-secondary" onclick="window.location.href='my-bookings.php'">
                    <i class="fas fa-list"></i> View Bookings
                </button>
                <button class="modal-btn modal-btn-primary" onclick="closeModal()">
                    <i class="fas fa-home"></i> Go to Dashboard
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php include '../assets/includes/modals.php'; ?>
    <script src="js/dashboard.js"></script>
    <script src="../assets/js/modals.js"></script>
    <script>
        // Success modal
        function closeModal() {
            const modal = document.getElementById('successModal');
            if(modal) {
                modal.style.animation = 'fadeOut 0.3s';
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 300);
            }
        }
        
        // Auto close modal after 10 seconds
        const successModal = document.getElementById('successModal');
        if(successModal) {
            setTimeout(closeModal, 10000);
        }
    </script>
</body>
</html>
