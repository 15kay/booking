<?php
session_start();
if(!isset($_SESSION['staff_id']) || !in_array($_SESSION['role'], ['tutor', 'pal'])) {
    header('Location: ../staff-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

$tutor_id = $_SESSION['staff_id'];

// Get filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Get all sessions
$query = "
    SELECT 
        ts.*,
        m.subject_code, m.subject_name, m.campus,
        ta.assignment_id,
        COUNT(sr.registration_id) as registered_students,
        COUNT(CASE WHEN sr.attended = TRUE THEN 1 END) as attended_students
    FROM tutor_sessions ts
    INNER JOIN tutor_assignments ta ON ts.assignment_id = ta.assignment_id
    INNER JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
    INNER JOIN modules m ON arm.module_id = m.module_id
    LEFT JOIN session_registrations sr ON ts.session_id = sr.session_id
    WHERE ta.tutor_id = ?
";

$params = [$tutor_id];

if($status_filter != 'all') {
    $query .= " AND ts.status = ?";
    $params[] = $status_filter;
}

$query .= " GROUP BY ts.session_id ORDER BY ts.session_date DESC, ts.start_time DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$sessions = $stmt->fetchAll();

// Get statistics
$stats = $conn->prepare("
    SELECT 
        COUNT(DISTINCT ts.session_id) as total,
        COUNT(DISTINCT CASE WHEN ts.status = 'scheduled' THEN ts.session_id END) as scheduled,
        COUNT(DISTINCT CASE WHEN ts.status = 'completed' THEN ts.session_id END) as completed,
        COUNT(DISTINCT CASE WHEN ts.status = 'cancelled' THEN ts.session_id END) as cancelled
    FROM tutor_sessions ts
    INNER JOIN tutor_assignments ta ON ts.assignment_id = ta.assignment_id
    WHERE ta.tutor_id = ?
");
$stats->execute([$tutor_id]);
$statistics = $stats->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Sessions - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .session-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #e5e7eb;
            transition: all 0.3s;
            display: flex;
            gap: 20px;
        }
        
        .session-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .session-card.scheduled {
            border-left-color: var(--blue);
        }
        
        .session-card.completed {
            border-left-color: var(--green);
        }
        
        .session-card.cancelled {
            border-left-color: #dc2626;
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
        
        .session-time {
            font-size: 12px;
            color: var(--blue);
            font-weight: 600;
            margin-top: 5px;
        }
        
        .session-content {
            flex: 1;
        }
        
        .session-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        
        .session-header h3 {
            margin: 0 0 5px 0;
            font-size: 18px;
            color: var(--dark);
        }
        
        .session-header p {
            margin: 0;
            font-size: 13px;
            color: #6b7280;
        }
        
        .session-info {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        
        .info-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #4b5563;
        }
        
        .info-badge i {
            color: var(--blue);
        }
        
        .session-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .attendance-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            background: #f3f4f6;
            border-radius: 8px;
            font-size: 13px;
        }
        
        .attendance-bar {
            width: 100px;
            height: 6px;
            background: #e5e7eb;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .attendance-fill {
            height: 100%;
            background: var(--green);
            transition: width 0.3s;
        }
        
        .attendance-fill.low {
            background: #dc2626;
        }
        
        .attendance-fill.medium {
            background: #f59e0b;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <div class="page-header">
                    <h1><i class="fas fa-calendar-alt"></i> My Sessions</h1>
                    <p>View and manage your tutoring sessions</p>
                </div>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $statistics['total']; ?></h3>
                            <p>Total Sessions</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $statistics['scheduled']; ?></h3>
                            <p>Scheduled</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $statistics['completed']; ?></h3>
                            <p>Completed</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $statistics['cancelled']; ?></h3>
                            <p>Cancelled</p>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="section">
                    <div class="filter-tabs">
                        <a href="?status=all" class="filter-tab <?php echo $status_filter == 'all' ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> All (<?php echo $statistics['total']; ?>)
                        </a>
                        <a href="?status=scheduled" class="filter-tab <?php echo $status_filter == 'scheduled' ? 'active' : ''; ?>">
                            <i class="fas fa-clock"></i> Scheduled (<?php echo $statistics['scheduled']; ?>)
                        </a>
                        <a href="?status=completed" class="filter-tab <?php echo $status_filter == 'completed' ? 'active' : ''; ?>">
                            <i class="fas fa-check-circle"></i> Completed (<?php echo $statistics['completed']; ?>)
                        </a>
                        <a href="?status=cancelled" class="filter-tab <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>">
                            <i class="fas fa-times-circle"></i> Cancelled (<?php echo $statistics['cancelled']; ?>)
                        </a>
                    </div>
                </div>

                <!-- Sessions List -->
                <div class="section">
                    <?php if(count($sessions) > 0): ?>
                        <?php foreach($sessions as $session): 
                            $date = new DateTime($session['session_date']);
                            $attendance_rate = $session['registered_students'] > 0 ? 
                                ($session['attended_students'] / $session['registered_students']) * 100 : 0;
                            $attendance_class = $attendance_rate >= 70 ? '' : ($attendance_rate >= 40 ? 'medium' : 'low');
                        ?>
                            <div class="session-card <?php echo $session['status']; ?>">
                                <div class="session-date">
                                    <div class="session-day"><?php echo $date->format('d'); ?></div>
                                    <div class="session-month"><?php echo $date->format('M'); ?></div>
                                    <div class="session-time">
                                        <?php echo date('g:i A', strtotime($session['start_time'])); ?>
                                    </div>
                                </div>
                                
                                <div class="session-content">
                                    <div class="session-header">
                                        <div>
                                            <h3><?php echo htmlspecialchars($session['topic']); ?></h3>
                                            <p>
                                                <strong><?php echo htmlspecialchars($session['subject_code']); ?></strong> - 
                                                <?php echo htmlspecialchars($session['subject_name']); ?>
                                            </p>
                                        </div>
                                        <span class="badge badge-<?php echo $session['status']; ?>">
                                            <?php echo ucfirst($session['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="session-info">
                                        <div class="info-badge">
                                            <i class="fas fa-clock"></i>
                                            <span>
                                                <?php echo date('g:i A', strtotime($session['start_time'])); ?> - 
                                                <?php echo date('g:i A', strtotime($session['end_time'])); ?>
                                            </span>
                                        </div>
                                        <div class="info-badge">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo htmlspecialchars($session['location']); ?></span>
                                        </div>
                                        <div class="info-badge">
                                            <i class="fas fa-building"></i>
                                            <span><?php echo htmlspecialchars($session['campus']); ?> Campus</span>
                                        </div>
                                        <div class="info-badge">
                                            <i class="fas fa-users"></i>
                                            <span><?php echo $session['registered_students']; ?> registered</span>
                                        </div>
                                    </div>
                                    
                                    <?php if($session['status'] == 'completed' && $session['registered_students'] > 0): ?>
                                        <div class="attendance-indicator">
                                            <span>Attendance:</span>
                                            <div class="attendance-bar">
                                                <div class="attendance-fill <?php echo $attendance_class; ?>" 
                                                     style="width: <?php echo $attendance_rate; ?>%"></div>
                                            </div>
                                            <strong><?php echo $session['attended_students']; ?>/<?php echo $session['registered_students']; ?></strong>
                                            <span>(<?php echo number_format($attendance_rate, 0); ?>%)</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if($session['description']): ?>
                                        <p style="margin: 10px 0 0 0; color: #6b7280; font-size: 14px;">
                                            <?php echo nl2br(htmlspecialchars($session['description'])); ?>
                                        </p>
                                    <?php endif; ?>
                                    
                                    <div class="session-actions">
                                        <a href="session-details.php?id=<?php echo $session['session_id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                        
                                        <?php if($session['status'] == 'scheduled'): ?>
                                            <a href="mark-attendance.php?session_id=<?php echo $session['session_id']; ?>" class="btn btn-success btn-sm">
                                                <i class="fas fa-clipboard-check"></i> Mark Attendance
                                            </a>
                                            <button onclick="cancelSession(<?php echo $session['session_id']; ?>)" class="btn btn-danger btn-sm">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if($session['status'] == 'completed'): ?>
                                            <a href="mark-attendance.php?session_id=<?php echo $session['session_id']; ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-clipboard-check"></i> View Attendance
                                            </a>
                                            <a href="session-report.php?id=<?php echo $session['session_id']; ?>" class="btn btn-secondary btn-sm">
                                                <i class="fas fa-file-alt"></i> View Report
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No Sessions Found</h3>
                            <p>
                                <?php if($status_filter != 'all'): ?>
                                    No <?php echo $status_filter; ?> sessions found.
                                <?php else: ?>
                                    You don't have any sessions yet. Create sessions from your assignments.
                                <?php endif; ?>
                            </p>
                            <?php if($status_filter != 'all'): ?>
                                <a href="?status=all" class="btn btn-secondary">
                                    <i class="fas fa-list"></i> View All Sessions
                                </a>
                            <?php else: ?>
                                <a href="my-assignments.php" class="btn btn-primary">
                                    <i class="fas fa-clipboard-list"></i> View My Assignments
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../assets/includes/modals.php'; ?>
    <link rel="stylesheet" href="../assets/css/modals.css">
    <script src="../assets/js/modals.js"></script>
    <script src="js/dashboard.js"></script>
    <script>
        function cancelSession(sessionId) {
            showModal(
                'Cancel Session',
                'Are you sure you want to cancel this session? Students will be notified.',
                'warning',
                function() {
                    window.location.href = 'cancel-session.php?id=' + sessionId;
                }
            );
        }
    </script>
</body>
</html>
