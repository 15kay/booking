<?php
session_start();
if(!isset($_SESSION['staff_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

if(!isset($_GET['id'])) {
    header('Location: all-students.php?error=Invalid request');
    exit();
}

$student_id = $_GET['id'];

$db = new Database();
$conn = $db->connect();

if(!$conn) {
    header('Location: ../index.php?error=' . urlencode('Database connection failed. Please try again later.'));
    exit();
}

// Get student details
$stmt = $conn->prepare("
    SELECT s.*, s.faculty_id as faculty_name, NULL as faculty_code
    FROM students s
    WHERE s.student_id = ?
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if(!$student) {
    header('Location: all-students.php?error=Student not found');
    exit();
}

// Get booking statistics
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled,
        COUNT(CASE WHEN status = 'no_show' THEN 1 END) as no_shows
    FROM bookings 
    WHERE student_id = ?
");
$stmt->execute([$student_id]);
$booking_stats = $stmt->fetch();

// Get recent bookings
$stmt = $conn->prepare("
    SELECT b.*, s.service_name, sc.category_name, st.first_name, st.last_name
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN service_categories sc ON s.category_id = sc.category_id
    JOIN staff st ON b.staff_id = st.staff_id
    WHERE b.student_id = ?
    ORDER BY b.booking_date DESC, b.start_time DESC
    OFFSET 0 ROWS FETCH NEXT 10 ROWS ONLY
");
$stmt->execute([$student_id]);
$recent_bookings = $stmt->fetchAll();

// Get service usage
$stmt = $conn->prepare("
    SELECT sc.category_name, COUNT(*) as usage_count
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN service_categories sc ON s.category_id = sc.category_id
    WHERE b.student_id = ? AND b.status = 'completed'
    GROUP BY sc.category_id, sc.category_name
    ORDER BY usage_count DESC
");
$stmt->execute([$student_id]);
$service_usage = $stmt->fetchAll();

// Calculate engagement score and readiness score
$engagement_score = 0;
$success_score = 0;

if(isset($student['reading_score']) && $student['reading_score'] !== null && $student['reading_score'] > 0) {
    $success_score = round($student['reading_score']);
} elseif($booking_stats['total_bookings'] > 0) {
    $completion_rate = ($booking_stats['completed'] / $booking_stats['total_bookings']) * 100;
    $engagement_score = min(100, $completion_rate);
    
    // Calculate readiness score from bookings
    $engagement_level = min(100, ($booking_stats['total_bookings'] / 10) * 100);
    $no_show_penalty = ($booking_stats['no_shows'] * 10);
    $cancelled_penalty = ($booking_stats['cancelled'] * 5);
    
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
    $score_label = 'Needs Support';
    $score_icon = 'fa-exclamation-triangle';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/modals.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .engagement-score {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, #000000 0%, #1d4ed8 100%);
            border-radius: 12px;
            color: var(--white);
            margin-bottom: 30px;
        }
        
        .score-circle {
            width: 120px;
            height: 120px;
            margin: 0 auto 15px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: 700;
            border: 4px solid rgba(255, 255, 255, 0.3);
        }
        
        .service-usage-chart {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .usage-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .usage-label {
            min-width: 150px;
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
        }
        
        .usage-bar-container {
            flex: 1;
            height: 30px;
            background: #f3f4f6;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }
        
        .usage-bar {
            height: 100%;
            background: linear-gradient(90deg, #1d4ed8 0%, #2563eb 100%);
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 10px;
            color: var(--white);
            font-size: 12px;
            font-weight: 700;
            transition: width 0.5s;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e5e7eb;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 25px;
        }
        
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -35px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--blue);
            border: 3px solid var(--white);
            box-shadow: 0 0 0 2px var(--blue);
        }
        
        .timeline-item.completed::before {
            background: var(--success);
            box-shadow: 0 0 0 2px var(--success);
        }
        
        .timeline-item.cancelled::before {
            background: var(--danger);
            box-shadow: 0 0 0 2px var(--danger);
        }
        
        .timeline-content {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            border-left: 3px solid var(--blue);
        }
        
        .timeline-content h4 {
            font-size: 15px;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .timeline-content p {
            font-size: 13px;
            color: #6b7280;
            margin: 4px 0;
        }
        
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .quick-action-card {
            padding: 20px;
            background: var(--white);
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .quick-action-card:hover {
            border-color: var(--blue);
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .quick-action-card i {
            font-size: 32px;
            color: var(--blue);
            margin-bottom: 10px;
        }
        
        .quick-action-card span {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--dark);
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background-color: var(--white);
            margin: 5% auto;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            animation: slideDown 0.3s;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            padding: 20px 25px;
            background: linear-gradient(135deg, #000000 0%, #1d4ed8 100%);
            color: var(--white);
            border-radius: 12px 12px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-header .close {
            color: var(--white);
            font-size: 32px;
            font-weight: 300;
            cursor: pointer;
            transition: all 0.3s;
            line-height: 1;
        }
        
        .modal-header .close:hover {
            transform: rotate(90deg);
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }
        
        .form-group label i {
            color: var(--blue);
            margin-right: 5px;
        }
        
        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-group input[type="checkbox"] {
            margin-right: 8px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 2px solid #f3f4f6;
        }
        
        @media print {
            .sidebar, .header, .hero-section, .quick-actions-grid, .btn, .back-link {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0 !important;
                padding: 20px !important;
            }
            
            .profile-section, .section {
                page-break-inside: avoid;
            }
        }
    </style>
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
                        <h1>Student Academic Profile</h1>
                        <p>Comprehensive view of student progress, engagement, and service utilization</p>
                        <div class="hero-stats">
                            <div class="hero-stat">
                                <i class="fas fa-id-card"></i>
                                <span><?php echo htmlspecialchars($student['student_id']); ?></span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-graduation-cap"></i>
                                <span>Year <?php echo $student['year_of_study']; ?></span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-calendar-check"></i>
                                <span><?php echo $booking_stats['total_bookings']; ?> Total Sessions</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Back Button -->
                <div class="detail-header">
                    <a href="all-students.php" class="back-link">
                        <i class="fas fa-arrow-left"></i> Back to All Students
                    </a>
                </div>

                <!-- Student Profile Header -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h2>
                        <p><?php echo htmlspecialchars($student['email']); ?></p>
                        <span class="status-badge <?php echo $student['status']; ?>">
                            <?php echo ucfirst($student['status']); ?>
                        </span>
                    </div>
                </div>

                <!-- Engagement Score -->
                <div class="engagement-score">
                    <div class="score-circle">
                        <?php echo round($engagement_score); ?>%
                    </div>
                    <h3>Engagement Score</h3>
                    <p>Based on booking completion rate and service utilization</p>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card" style="background: linear-gradient(135deg, <?php echo $score_bg; ?> 0%, <?php echo $score_bg; ?> 100%); border: 2px solid <?php echo $score_color; ?>;">
                        <div class="stat-icon" style="background: <?php echo $score_color; ?>; color: white;">
                            <i class="fas <?php echo $score_icon; ?>"></i>
                        </div>
                        <div class="stat-info">
                            <h3 style="color: <?php echo $score_color; ?>; font-size: 42px;"><?php echo $success_score; ?></h3>
                            <p style="color: <?php echo $score_color; ?>; font-weight: 700;">Readiness Score - <?php echo $score_label; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $booking_stats['total_bookings']; ?></h3>
                            <p>Total Bookings</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $booking_stats['completed']; ?></h3>
                            <p>Completed</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $booking_stats['cancelled']; ?></h3>
                            <p>Cancelled</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="fas fa-user-times"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $booking_stats['no_shows']; ?></h3>
                            <p>No-Shows</p>
                        </div>
                    </div>
                </div>

                <!-- Profile Grid -->
                <div class="profile-grid">
                    <!-- Personal Information -->
                    <div class="profile-section">
                        <h3><i class="fas fa-user"></i> Personal Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Student ID</label>
                                <p><?php echo htmlspecialchars($student['student_id']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Full Name</label>
                                <p><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Email</label>
                                <p><?php echo htmlspecialchars($student['email']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Phone</label>
                                <p><?php echo htmlspecialchars($student['phone'] ?? 'Not provided'); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Status</label>
                                <p><?php echo ucfirst($student['status']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Registered</label>
                                <p><?php echo date('d M Y', strtotime($student['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Academic Information -->
                    <div class="profile-section">
                        <h3><i class="fas fa-graduation-cap"></i> Academic Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Faculty</label>
                                <p><?php echo htmlspecialchars($student['faculty_name'] ?? 'Not assigned'); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Faculty Code</label>
                                <p><?php echo htmlspecialchars($student['faculty_code'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Year of Study</label>
                                <p>Year <?php echo $student['year_of_study']; ?></p>
                            </div>
                            <div class="info-item">
                                <label>Student Type</label>
                                <p><?php echo ucfirst(str_replace('_', ' ', $student['student_type'])); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Last Login</label>
                                <p><?php echo $student['last_login'] ? date('d M Y, H:i', strtotime($student['last_login'])) : 'Never'; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Service Usage -->
                <?php if(count($service_usage) > 0): ?>
                <div class="section">
                    <h3><i class="fas fa-chart-bar"></i> Service Utilization</h3>
                    <div class="service-usage-chart">
                        <?php 
                        $max_usage = max(array_column($service_usage, 'usage_count'));
                        foreach($service_usage as $usage): 
                        ?>
                        <div class="usage-item">
                            <div class="usage-label"><?php echo htmlspecialchars($usage['category_name']); ?></div>
                            <div class="usage-bar-container">
                                <div class="usage-bar" style="width: <?php echo ($usage['usage_count'] / $max_usage) * 100; ?>%">
                                    <?php echo $usage['usage_count']; ?> sessions
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recent Activity Timeline -->
                <?php if(count($recent_bookings) > 0): ?>
                <div class="section">
                    <h3><i class="fas fa-history"></i> Recent Activity</h3>
                    <div class="timeline">
                        <?php foreach($recent_bookings as $booking): ?>
                        <div class="timeline-item <?php echo $booking['status']; ?>">
                            <div class="timeline-content">
                                <h4><?php echo htmlspecialchars($booking['service_name']); ?></h4>
                                <p><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($booking['booking_date'])); ?> at <?php echo date('H:i', strtotime($booking['start_time'])); ?></p>
                                <p><i class="fas fa-user"></i> With <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></p>
                                <p><i class="fas fa-tag"></i> <?php echo htmlspecialchars($booking['category_name']); ?></p>
                                <span class="badge badge-<?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Quick Actions -->
                <div class="section">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    <div class="quick-actions-grid">
                        <a href="mailto:<?php echo $student['email']; ?>" class="quick-action-card">
                            <i class="fas fa-envelope"></i>
                            <span>Send Email</span>
                        </a>
                        <a href="appointments.php?student=<?php echo $student['student_id']; ?>" class="quick-action-card">
                            <i class="fas fa-calendar"></i>
                            <span>View Bookings</span>
                        </a>
                        <a href="#" class="quick-action-card" onclick="openReportModal(); return false;">
                            <i class="fas fa-file-alt"></i>
                            <span>Generate Report</span>
                        </a>
                        <a href="#" class="quick-action-card" onclick="openNoteModal(); return false;">
                            <i class="fas fa-comment"></i>
                            <span>Add Note</span>
                        </a>
                        <?php if(!empty($student['phone'])): ?>
                        <a href="tel:<?php echo $student['phone']; ?>" class="quick-action-card">
                            <i class="fas fa-phone"></i>
                            <span>Call Student</span>
                        </a>
                        <?php endif; ?>
                        <a href="#" class="quick-action-card" onclick="printProfile(); return false;">
                            <i class="fas fa-print"></i>
                            <span>Print Profile</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Generate Report Modal -->
    <div id="reportModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-file-alt"></i> Generate Student Report</h3>
                <span class="close" onclick="closeReportModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="reportForm" onsubmit="generateReport(event)">
                    <div class="form-group">
                        <label for="report_type"><i class="fas fa-list"></i> Report Type</label>
                        <select id="report_type" name="report_type" required>
                            <option value="">Select report type...</option>
                            <option value="full">Full Academic Profile</option>
                            <option value="attendance">Attendance Summary</option>
                            <option value="engagement">Engagement Analysis</option>
                            <option value="services">Service Utilization</option>
                            <option value="progress">Progress Report</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_range"><i class="fas fa-calendar"></i> Date Range</label>
                        <select id="date_range" name="date_range" required>
                            <option value="all">All Time</option>
                            <option value="this_month">This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="this_semester">This Semester</option>
                            <option value="this_year">This Year</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    
                    <div id="customDateRange" style="display: none;">
                        <div class="form-group">
                            <label for="start_date">Start Date</label>
                            <input type="date" id="start_date" name="start_date">
                        </div>
                        <div class="form-group">
                            <label for="end_date">End Date</label>
                            <input type="date" id="end_date" name="end_date">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="format"><i class="fas fa-file-download"></i> Export Format</label>
                        <select id="format" name="format" required>
                            <option value="pdf">PDF Document</option>
                            <option value="excel">Excel Spreadsheet</option>
                            <option value="csv">CSV File</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="include_charts" checked>
                            Include charts and visualizations
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="include_notes" checked>
                            Include advisor notes
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-download"></i> Generate Report
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeReportModal()">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Add Note Modal -->
    <div id="noteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-comment"></i> Add Student Note</h3>
                <span class="close" onclick="closeNoteModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="noteForm" onsubmit="saveNote(event)">
                    <div class="form-group">
                        <label for="note_type"><i class="fas fa-tag"></i> Note Type</label>
                        <select id="note_type" name="note_type" required>
                            <option value="">Select note type...</option>
                            <option value="general">General Note</option>
                            <option value="academic">Academic Concern</option>
                            <option value="behavioral">Behavioral Observation</option>
                            <option value="achievement">Achievement/Success</option>
                            <option value="follow_up">Follow-up Required</option>
                            <option value="intervention">Intervention Plan</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="note_priority"><i class="fas fa-exclamation-circle"></i> Priority</label>
                        <select id="note_priority" name="note_priority" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="note_content"><i class="fas fa-edit"></i> Note Content</label>
                        <textarea id="note_content" name="note_content" rows="6" required placeholder="Enter your note about the student..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="confidential" id="confidential">
                            Mark as confidential (visible only to advisors)
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="send_notification" id="send_notification">
                            Send notification to student
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Note
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="closeNoteModal()">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="js/dashboard.js"></script>
    <script>
        // Report Modal Functions
        function openReportModal() {
            document.getElementById('reportModal').style.display = 'block';
        }
        
        function closeReportModal() {
            document.getElementById('reportModal').style.display = 'none';
            document.getElementById('reportForm').reset();
            document.getElementById('customDateRange').style.display = 'none';
        }
        
        function generateReport(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const reportType = formData.get('report_type');
            const dateRange = formData.get('date_range');
            const format = formData.get('format');
            
            // Show loading message
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
            submitBtn.disabled = true;
            
            // Simulate report generation
            setTimeout(() => {
                showMessageModal(
                    'Report Generated',
                    `Type: ${reportType}\nDate Range: ${dateRange}\nFormat: ${format}\n\nThe report has been generated and will be downloaded shortly.`,
                    'success'
                );
                
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                closeReportModal();
                
                // In a real implementation, you would make an AJAX call here
                // to generate and download the actual report
            }, 2000);
        }
        
        // Note Modal Functions
        function openNoteModal() {
            document.getElementById('noteModal').style.display = 'block';
        }
        
        function closeNoteModal() {
            document.getElementById('noteModal').style.display = 'none';
            document.getElementById('noteForm').reset();
        }
        
        function saveNote(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const noteType = formData.get('note_type');
            const priority = formData.get('note_priority');
            const content = formData.get('note_content');
            
            // Show loading message
            const submitBtn = event.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            submitBtn.disabled = true;
            
            // Simulate note saving
            setTimeout(() => {
                showMessageModal(
                    'Note Saved',
                    `Type: ${noteType}\nPriority: ${priority}\n\nYour note has been added to the student's profile.`,
                    'success'
                );
                
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                closeNoteModal();
                
                // In a real implementation, you would make an AJAX call here
                // to save the note to the database
            }, 1500);
        }
        
        // Print Profile Function
        function printProfile() {
            window.print();
        }
        
        // Date range toggle
        document.getElementById('date_range').addEventListener('change', function() {
            const customRange = document.getElementById('customDateRange');
            if(this.value === 'custom') {
                customRange.style.display = 'block';
            } else {
                customRange.style.display = 'none';
            }
        });
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const reportModal = document.getElementById('reportModal');
            const noteModal = document.getElementById('noteModal');
            
            if(event.target == reportModal) {
                closeReportModal();
            }
            if(event.target == noteModal) {
                closeNoteModal();
            }
        }
    </script>
    
    <?php include '../assets/includes/modals.php'; ?>
    <script src="../assets/js/modals.js"></script>
</body>
</html>

