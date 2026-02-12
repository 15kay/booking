<?php
session_start();
if(!isset($_SESSION['staff_id']) || !in_array($_SESSION['role'], ['tutor', 'pal'])) {
    header('Location: ../staff-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tutor_id = $_SESSION['staff_id'];

// Get assignment details
$stmt = $conn->prepare("
    SELECT 
        ta.*,
        m.subject_code, m.subject_name, m.faculty, m.campus, m.headcount, 
        m.risk_category, m.subject_pass_rate, m.academic_year, m.semester,
        arm.at_risk_students, arm.reason,
        s.first_name as coordinator_first, s.last_name as coordinator_last,
        s.email as coordinator_email, s.phone as coordinator_phone
    FROM tutor_assignments ta
    INNER JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
    INNER JOIN modules m ON arm.module_id = m.module_id
    LEFT JOIN staff s ON ta.assigned_by = s.staff_id
    WHERE ta.assignment_id = ? AND ta.tutor_id = ?
");
$stmt->execute([$assignment_id, $tutor_id]);
$assignment = $stmt->fetch();

if(!$assignment) {
    header('Location: my-assignments.php?error=Assignment not found');
    exit();
}

// Get sessions for this assignment
$sessions_stmt = $conn->prepare("
    SELECT 
        ts.*,
        COUNT(sr.registration_id) as registered_students,
        COUNT(CASE WHEN sr.attended = TRUE THEN 1 END) as attended_students
    FROM tutor_sessions ts
    LEFT JOIN session_registrations sr ON ts.session_id = sr.session_id
    WHERE ts.assignment_id = ?
    GROUP BY ts.session_id
    ORDER BY ts.session_date DESC, ts.start_time DESC
");
$sessions_stmt->execute([$assignment_id]);
$sessions = $sessions_stmt->fetchAll();

// Get statistics
$stats = $conn->prepare("
    SELECT 
        COUNT(DISTINCT ts.session_id) as total_sessions,
        COUNT(DISTINCT CASE WHEN ts.status = 'completed' THEN ts.session_id END) as completed_sessions,
        COUNT(DISTINCT CASE WHEN ts.status = 'scheduled' THEN ts.session_id END) as scheduled_sessions,
        COUNT(DISTINCT sr.registration_id) as total_registrations,
        COUNT(DISTINCT CASE WHEN sr.attended = TRUE THEN sr.registration_id END) as total_attended
    FROM tutor_sessions ts
    LEFT JOIN session_registrations sr ON ts.session_id = sr.session_id
    WHERE ts.assignment_id = ?
");
$stats->execute([$assignment_id]);
$statistics = $stats->fetch();

$attendance_rate = $statistics['total_registrations'] > 0 ? 
    ($statistics['total_attended'] / $statistics['total_registrations']) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment Details - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .details-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .module-header {
            background: linear-gradient(135deg, #000000 0%, #1d4ed8 100%);
            color: white;
            padding: 40px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .module-header h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
        }
        
        .module-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
        }
        
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            border: 2px solid #e5e7eb;
        }
        
        .info-card h2 {
            margin: 0 0 20px 0;
            font-size: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .info-card h2 i {
            color: var(--blue);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .info-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .info-value {
            font-size: 16px;
            color: var(--dark);
            font-weight: 500;
        }
        
        .risk-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .risk-high {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .risk-moderate {
            background: #fef3c7;
            color: #f59e0b;
        }
        
        .risk-low {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .risk-very-low {
            background: #d1fae5;
            color: #10b981;
        }
        
        .session-item {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .session-item.completed {
            border-left: 4px solid var(--green);
        }
        
        .session-item.scheduled {
            border-left: 4px solid var(--blue);
        }
        
        .session-item.cancelled {
            border-left: 4px solid #dc2626;
            opacity: 0.7;
        }
        
        .session-date {
            min-width: 80px;
            text-align: center;
            padding: 10px;
            background: #f9fafb;
            border-radius: 8px;
        }
        
        .session-day {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark);
            line-height: 1;
        }
        
        .session-month {
            font-size: 14px;
            color: #6b7280;
            text-transform: uppercase;
            margin-top: 5px;
        }
        
        .session-content {
            flex: 1;
        }
        
        .session-content h3 {
            margin: 0 0 8px 0;
            font-size: 18px;
            color: var(--dark);
        }
        
        .session-info {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 13px;
            color: #6b7280;
        }
        
        .session-info span {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .session-info i {
            color: var(--blue);
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <div class="details-container">
                    <div style="margin-bottom: 20px;">
                        <a href="my-assignments.php" class="btn-link">
                            <i class="fas fa-arrow-left"></i> Back to My Assignments
                        </a>
                    </div>

                    <!-- Module Header -->
                    <div class="module-header">
                        <h1><?php echo htmlspecialchars($assignment['subject_code']); ?> - <?php echo htmlspecialchars($assignment['subject_name']); ?></h1>
                        <p><?php echo htmlspecialchars($assignment['faculty']); ?> • <?php echo htmlspecialchars($assignment['campus']); ?> Campus</p>
                        <div style="margin-top: 15px;">
                            <span class="risk-badge risk-<?php echo strtolower(str_replace(' ', '-', $assignment['risk_category'])); ?>">
                                <?php echo $assignment['risk_category']; ?>
                            </span>
                            <span class="badge badge-<?php echo $assignment['status']; ?>" style="margin-left: 10px;">
                                <?php echo ucfirst($assignment['status']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon blue">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $statistics['total_sessions']; ?></h3>
                                <p>Total Sessions</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon green">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $statistics['completed_sessions']; ?></h3>
                                <p>Completed</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon orange">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $statistics['total_registrations']; ?></h3>
                                <p>Total Registrations</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon blue">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo number_format($attendance_rate, 1); ?>%</h3>
                                <p>Attendance Rate</p>
                            </div>
                        </div>
                    </div>

                    <!-- Module Information -->
                    <div class="info-card">
                        <h2><i class="fas fa-book"></i> Module Information</h2>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Subject Code</span>
                                <span class="info-value"><?php echo htmlspecialchars($assignment['subject_code']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Faculty</span>
                                <span class="info-value"><?php echo htmlspecialchars($assignment['faculty']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Campus</span>
                                <span class="info-value"><?php echo htmlspecialchars($assignment['campus']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Academic Year</span>
                                <span class="info-value"><?php echo $assignment['academic_year']; ?> - Semester <?php echo $assignment['semester']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Total Students</span>
                                <span class="info-value"><?php echo $assignment['headcount']; ?> students</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">At-Risk Students</span>
                                <span class="info-value"><?php echo $assignment['at_risk_students']; ?> students</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Pass Rate</span>
                                <span class="info-value"><?php echo number_format($assignment['subject_pass_rate'] * 100, 1); ?>%</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Risk Category</span>
                                <span class="info-value">
                                    <span class="risk-badge risk-<?php echo strtolower(str_replace(' ', '-', $assignment['risk_category'])); ?>">
                                        <?php echo $assignment['risk_category']; ?>
                                    </span>
                                </span>
                            </div>
                        </div>
                        
                        <?php if($assignment['reason']): ?>
                            <div style="margin-top: 20px; padding: 15px; background: #eff6ff; border-radius: 8px; border-left: 4px solid var(--blue);">
                                <strong style="color: var(--blue);"><i class="fas fa-info-circle"></i> Risk Reason:</strong>
                                <p style="margin: 5px 0 0 0; color: #4b5563;"><?php echo htmlspecialchars($assignment['reason']); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Assignment Details -->
                    <div class="info-card">
                        <h2><i class="fas fa-clipboard-list"></i> Assignment Details</h2>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Assignment Date</span>
                                <span class="info-value"><?php echo date('F j, Y', strtotime($assignment['assignment_date'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Start Date</span>
                                <span class="info-value"><?php echo date('F j, Y', strtotime($assignment['start_date'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">End Date</span>
                                <span class="info-value"><?php echo date('F j, Y', strtotime($assignment['end_date'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Session Frequency</span>
                                <span class="info-value"><?php echo htmlspecialchars($assignment['session_frequency']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Max Students per Session</span>
                                <span class="info-value"><?php echo $assignment['max_students']; ?> students</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Location</span>
                                <span class="info-value"><?php echo htmlspecialchars($assignment['location']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Assigned By</span>
                                <span class="info-value">
                                    <?php echo htmlspecialchars($assignment['coordinator_first'] . ' ' . $assignment['coordinator_last']); ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Status</span>
                                <span class="info-value">
                                    <span class="badge badge-<?php echo $assignment['status']; ?>">
                                        <?php echo ucfirst($assignment['status']); ?>
                                    </span>
                                </span>
                            </div>
                        </div>
                        
                        <?php if($assignment['notes']): ?>
                            <div style="margin-top: 20px; padding: 15px; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
                                <strong style="color: #92400e;"><i class="fas fa-sticky-note"></i> Notes:</strong>
                                <p style="margin: 5px 0 0 0; color: #4b5563;"><?php echo nl2br(htmlspecialchars($assignment['notes'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Sessions -->
                    <div class="info-card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2 style="margin: 0; border: none; padding: 0;"><i class="fas fa-calendar-alt"></i> Sessions</h2>
                            <?php if($assignment['status'] == 'active'): ?>
                                <a href="create-session.php?assignment_id=<?php echo $assignment['assignment_id']; ?>" class="btn btn-success">
                                    <i class="fas fa-plus"></i> Create Session
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div style="padding: 15px; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b; margin-bottom: 20px;">
                            <p style="margin: 0; font-size: 14px; color: #4b5563;">
                                <i class="fas fa-info-circle" style="color: #f59e0b;"></i>
                                <strong>Note:</strong> Students enrolled in this module can view and register for your sessions. 
                                They are not automatically registered - students must sign up themselves.
                            </p>
                        </div>
                        
                        <?php if(count($sessions) > 0): ?>
                            <?php foreach($sessions as $session): 
                                $date = new DateTime($session['session_date']);
                            ?>
                                <div class="session-item <?php echo $session['status']; ?>">
                                    <div class="session-date">
                                        <div class="session-day"><?php echo $date->format('d'); ?></div>
                                        <div class="session-month"><?php echo $date->format('M'); ?></div>
                                    </div>
                                    
                                    <div class="session-content">
                                        <h3><?php echo htmlspecialchars($session['topic']); ?></h3>
                                        <div class="session-info">
                                            <span>
                                                <i class="fas fa-clock"></i>
                                                <?php echo date('g:i A', strtotime($session['start_time'])); ?> - 
                                                <?php echo date('g:i A', strtotime($session['end_time'])); ?>
                                            </span>
                                            <span>
                                                <i class="fas fa-map-marker-alt"></i>
                                                <?php echo htmlspecialchars($session['location']); ?>
                                            </span>
                                            <span>
                                                <i class="fas fa-users"></i>
                                                <?php echo $session['registered_students']; ?> registered
                                            </span>
                                            <?php if($session['status'] == 'completed'): ?>
                                                <span>
                                                    <i class="fas fa-check-circle"></i>
                                                    <?php echo $session['attended_students']; ?> attended
                                                </span>
                                            <?php endif; ?>
                                            <span class="badge badge-<?php echo $session['status']; ?>">
                                                <?php echo ucfirst($session['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <a href="session-details.php?id=<?php echo $session['session_id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-times"></i>
                                <h3>No Sessions Yet</h3>
                                <p>Create your first session to start helping students.</p>
                                <?php if($assignment['status'] == 'active'): ?>
                                    <a href="create-session.php?assignment_id=<?php echo $assignment['assignment_id']; ?>" class="btn btn-success" style="margin-top: 15px;">
                                        <i class="fas fa-plus"></i> Create Session
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../assets/includes/modals.php'; ?>
    <link rel="stylesheet" href="../assets/css/modals.css">
    <script src="../assets/js/modals.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>
