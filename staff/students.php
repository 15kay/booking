<?php
session_start();
if(!isset($_SESSION['staff_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get students who have booked with this staff member
$stmt = $conn->prepare("
    SELECT DISTINCT st.*, f.faculty_name,
           COUNT(DISTINCT b.booking_id) as total_bookings,
           MAX(b.booking_date) as last_booking_date,
           COUNT(DISTINCT CASE WHEN b.status = 'completed' THEN b.booking_id END) as completed_sessions,
           COUNT(DISTINCT CASE WHEN b.status = 'cancelled' THEN b.booking_id END) as cancelled_sessions,
           COUNT(DISTINCT CASE WHEN b.status = 'no_show' THEN b.booking_id END) as no_show_sessions
    FROM students st
    LEFT JOIN faculties f ON st.faculty_id = f.faculty_id
    INNER JOIN bookings b ON st.student_id = b.student_id
    WHERE b.staff_id = ?
    GROUP BY st.student_id
    ORDER BY last_booking_date DESC
");
$stmt->execute([$_SESSION['staff_id']]);
$students = $stmt->fetchAll();

// Get total count
$total_students = count($students);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - WSU Booking</title>
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
                        <h1>Student Management</h1>
                        <p>View and manage students who have booked appointments with you</p>
                        <div class="hero-stats">
                            <div class="hero-stat">
                                <i class="fas fa-users"></i>
                                <span><?php echo $total_students; ?> Total Students</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-calendar-check"></i>
                                <span>Booking History</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-user-graduate"></i>
                                <span>Student Profiles</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Students Grid -->
                <?php if(count($students) > 0): ?>
                    <div class="bookings-grid">
                        <?php foreach($students as $student): 
                            // Calculate success score
                            $success_score = 0;
                            if($student['total_bookings'] > 0) {
                                $completion_rate = ($student['completed_sessions'] / $student['total_bookings']) * 100;
                                $engagement_score = min(100, ($student['total_bookings'] / 10) * 100);
                                $no_show_penalty = ($student['no_show_sessions'] * 10);
                                $cancelled_penalty = ($student['cancelled_sessions'] * 5);
                                
                                $success_score = max(0, min(100, 
                                    ($completion_rate * 0.5) + 
                                    ($engagement_score * 0.3) + 
                                    (20) - 
                                    $no_show_penalty - 
                                    $cancelled_penalty
                                ));
                            }
                            $success_score = round($success_score);
                            
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
                        <div class="booking-item">
                            <div class="booking-item-header">
                                <div>
                                    <h3><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h3>
                                    <span class="category-badge"><?php echo htmlspecialchars($student['student_id']); ?></span>
                                </div>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="text-align: center;">
                                        <div style="position: relative; width: 50px; height: 50px;">
                                            <svg style="transform: rotate(-90deg);" width="50" height="50">
                                                <circle cx="25" cy="25" r="20" fill="none" stroke="#e5e7eb" stroke-width="5"/>
                                                <circle cx="25" cy="25" r="20" fill="none" stroke="<?php echo $score_color; ?>" stroke-width="5" 
                                                        stroke-dasharray="<?php echo (2 * 3.14159 * 20); ?>" 
                                                        stroke-dashoffset="<?php echo (2 * 3.14159 * 20) * (1 - $success_score / 100); ?>"
                                                        stroke-linecap="round"/>
                                            </svg>
                                            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: 700; font-size: 12px; color: <?php echo $score_color; ?>;">
                                                <?php echo $success_score; ?>
                                            </div>
                                        </div>
                                        <span style="font-size: 9px; font-weight: 600; padding: 2px 6px; border-radius: 8px; background: <?php echo $score_bg; ?>; color: <?php echo $score_color; ?>; display: block; margin-top: 4px;">
                                            <?php echo $score_label; ?>
                                        </span>
                                    </div>
                                    <span class="badge badge-<?php echo $student['status']; ?>">
                                        <?php echo ucfirst($student['status']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="booking-item-body">
                                <div class="booking-info-row">
                                    <i class="fas fa-envelope"></i>
                                    <span><?php echo htmlspecialchars($student['email']); ?></span>
                                </div>
                                
                                <?php if(!empty($student['phone'])): ?>
                                <div class="booking-info-row">
                                    <i class="fas fa-phone"></i>
                                    <span><?php echo htmlspecialchars($student['phone']); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if(!empty($student['faculty_name'])): ?>
                                <div class="booking-info-row">
                                    <i class="fas fa-university"></i>
                                    <span><?php echo htmlspecialchars($student['faculty_name']); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <div class="booking-info-row">
                                    <i class="fas fa-graduation-cap"></i>
                                    <span>Year <?php echo $student['year_of_study']; ?> - <?php echo ucfirst(str_replace('_', ' ', $student['student_type'])); ?></span>
                                </div>
                                
                                <div class="booking-info-row">
                                    <i class="fas fa-calendar-check"></i>
                                    <span><?php echo $student['completed_sessions']; ?> / <?php echo $student['total_bookings']; ?> Sessions Completed</span>
                                </div>
                                
                                <div class="booking-info-row">
                                    <i class="fas fa-clock"></i>
                                    <span>Last booking: <?php echo date('d M Y', strtotime($student['last_booking_date'])); ?></span>
                                </div>
                            </div>
                            
                            <div class="booking-item-footer">
                                <a href="student-profile.php?id=<?php echo $student['student_id']; ?>" class="btn-action btn-secondary">
                                    <i class="fas fa-eye"></i> View Profile
                                </a>
                                <a href="appointments.php?student=<?php echo $student['student_id']; ?>" class="btn-action btn-primary">
                                    <i class="fas fa-calendar"></i> View Bookings
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="section">
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <h3>No Students Yet</h3>
                            <p>Students who book appointments with you will appear here</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="js/dashboard.js"></script>
</body>
</html>
