<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

$student_id = $_SESSION['student_id'];

// Get student info
$stmt = $conn->prepare("SELECT s.*, f.faculty_name FROM students s LEFT JOIN faculties f ON s.faculty_id = f.faculty_id WHERE s.student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Get booking statistics for readiness score
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_sessions,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_sessions,
        COUNT(CASE WHEN status = 'no_show' THEN 1 END) as no_show_sessions
    FROM bookings 
    WHERE student_id = ?
");
$stmt->execute([$student_id]);
$booking_stats = $stmt->fetch();

// Calculate readiness score - prioritize reading_score from database
$success_score = 0;
if(isset($student['reading_score']) && $student['reading_score'] !== null && $student['reading_score'] > 0) {
    $success_score = round($student['reading_score']);
} elseif($booking_stats['total_bookings'] > 0) {
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
    $success_score = round($success_score);
}

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

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - WSU Booking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Profile updated successfully!
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Readiness Score Banner -->
                <div class="readiness-banner" style="background: linear-gradient(135deg, <?php echo $score_bg; ?> 0%, <?php echo $score_bg; ?> 100%); border-color: <?php echo $score_color; ?>;">
                    <div class="readiness-content">
                        <div class="score-circle">
                            <svg style="transform: rotate(-90deg);" width="120" height="120" viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="52" fill="none" stroke="rgba(0,0,0,0.1)" stroke-width="10"/>
                                <circle cx="60" cy="60" r="52" fill="none" stroke="<?php echo $score_color; ?>" stroke-width="10" 
                                        stroke-dasharray="<?php echo (2 * 3.14159 * 52); ?>" 
                                        stroke-dashoffset="<?php echo (2 * 3.14159 * 52) * (1 - $success_score / 100); ?>"
                                        stroke-linecap="round"/>
                            </svg>
                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                                <div class="score-number" style="font-weight: 700; font-size: 36px; color: <?php echo $score_color; ?>; line-height: 1;">
                                    <?php echo $success_score; ?>
                                </div>
                                <div class="score-label" style="font-size: 12px; color: <?php echo $score_color; ?>; font-weight: 600; margin-top: 4px;">
                                    SCORE
                                </div>
                            </div>
                        </div>
                        <div class="score-details">
                            <h2 style="color: <?php echo $score_color; ?>; font-size: 28px; margin-bottom: 10px; display: flex; align-items: center; gap: 12px;">
                                <i class="fas <?php echo $score_icon; ?>"></i>
                                Your Readiness Score
                            </h2>
                            <p style="color: <?php echo $score_color; ?>; font-weight: 600; font-size: 20px; margin-bottom: 15px;">
                                Engagement Level: <?php echo $score_label; ?>
                            </p>
                            <div class="score-stats">
                                <span><i class="fas fa-calendar-check"></i> <strong><?php echo $booking_stats['completed_sessions']; ?></strong> sessions attended</span>
                                <span><i class="fas fa-calendar"></i> <strong><?php echo $booking_stats['total_bookings']; ?></strong> total bookings</span>
                                <?php if($booking_stats['cancelled_sessions'] > 0): ?>
                                    <span style="color: #f59e0b;"><i class="fas fa-calendar-times"></i> <strong><?php echo $booking_stats['cancelled_sessions']; ?></strong> cancelled</span>
                                <?php endif; ?>
                                <?php if($booking_stats['no_show_sessions'] > 0): ?>
                                    <span style="color: #ef4444;"><i class="fas fa-user-times"></i> <strong><?php echo $booking_stats['no_show_sessions']; ?></strong> missed</span>
                                <?php endif; ?>
                            </div>
                            <p class="info-text" style="font-size: 13px; color: #6b7280; font-style: italic; line-height: 1.5;">
                                <i class="fas fa-info-circle"></i> Your readiness score tracks your engagement with support services before coursework begins. 
                                <?php if($success_score < 60): ?>
                                    <strong style="color: <?php echo $score_color; ?>;">Consider booking more sessions to improve your readiness!</strong>
                                <?php else: ?>
                                    Keep up the great work!
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="profile-container">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div class="profile-info">
                            <h2><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h2>
                            <p><?php echo htmlspecialchars($student['student_id']); ?></p>
                            <span class="status-badge <?php echo $student['status']; ?>">
                                <?php echo ucfirst($student['status']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="profile-grid">
                        <div class="profile-section">
                            <h3><i class="fas fa-user"></i> Personal Information</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>First Name</label>
                                    <p><?php echo htmlspecialchars($student['first_name']); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Last Name</label>
                                    <p><?php echo htmlspecialchars($student['last_name']); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Email</label>
                                    <p><?php echo htmlspecialchars($student['email']); ?></p>
                                </div>
                            </div>
                            
                            <form action="update-profile.php" method="POST" class="profile-form" style="margin-top: 20px;">
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>" pattern="[0-9]{10}">
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Phone
                                </button>
                            </form>
                        </div>

                        <div class="profile-section">
                            <h3><i class="fas fa-graduation-cap"></i> Academic Information</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Student ID</label>
                                    <p><?php echo htmlspecialchars($student['student_id']); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Faculty</label>
                                    <p><?php echo htmlspecialchars($student['faculty_name'] ?? 'Not assigned'); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Year of Study</label>
                                    <p>Year <?php echo $student['year_of_study']; ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Student Type</label>
                                    <p><?php echo ucfirst(str_replace('_', ' ', $student['student_type'])); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="profile-section">
                            <h3><i class="fas fa-lock"></i> Change Password</h3>
                            <form action="change-password.php" method="POST" class="profile-form">
                                <div class="form-group">
                                    <label>Current Password</label>
                                    <input type="password" name="current_password" required>
                                </div>
                                <div class="form-group">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" required minlength="6">
                                </div>
                                <div class="form-group">
                                    <label>Confirm New Password</label>
                                    <input type="password" name="confirm_password" required minlength="6">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key"></i> Update Password
                                </button>
                            </form>
                        </div>

                        <div class="profile-section">
                            <h3><i class="fas fa-info-circle"></i> Account Details</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Account Status</label>
                                    <p><span class="status-badge <?php echo $student['status']; ?>"><?php echo ucfirst($student['status']); ?></span></p>
                                </div>
                                <div class="info-item">
                                    <label>Member Since</label>
                                    <p><?php echo date('F Y', strtotime($student['created_at'])); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Last Login</label>
                                    <p><?php echo $student['last_login'] ? date('d M Y, H:i', strtotime($student['last_login'])) : 'Never'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/dashboard.js"></script>
</body>
</html>
