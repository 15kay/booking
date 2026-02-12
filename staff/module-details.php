<?php
session_start();
if(!isset($_SESSION['staff_id']) || $_SESSION['role'] != 'coordinator') {
    header('Location: ../staff-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

$risk_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get module details
$stmt = $conn->prepare("
    SELECT arm.*, m.module_code, m.module_name, m.year_level, m.credits, m.description,
           s.first_name, s.last_name, s.staff_number
    FROM at_risk_modules arm
    JOIN modules m ON arm.module_id = m.module_id
    LEFT JOIN staff s ON arm.identified_by = s.staff_id
    WHERE arm.risk_id = ?
");
$stmt->execute([$risk_id]);
$module = $stmt->fetch();

if(!$module) {
    header('Location: at-risk-modules.php');
    exit();
}

// Get tutor assignments
$assignments_stmt = $conn->prepare("
    SELECT ta.*, s.first_name, s.last_name, s.staff_number, s.email, s.phone,
           COUNT(DISTINCT ts.session_id) as total_sessions,
           COUNT(DISTINCT CASE WHEN ts.status = 'completed' THEN ts.session_id END) as completed_sessions
    FROM tutor_assignments ta
    JOIN staff s ON ta.tutor_id = s.staff_id
    LEFT JOIN tutor_sessions ts ON ta.assignment_id = ts.assignment_id
    WHERE ta.risk_module_id = ?
    GROUP BY ta.assignment_id
    ORDER BY ta.assignment_date DESC
");
$assignments_stmt->execute([$risk_id]);
$assignments = $assignments_stmt->fetchAll();

// Get upcoming sessions
$sessions_stmt = $conn->prepare("
    SELECT ts.*, ta.tutor_type, s.first_name, s.last_name,
           COUNT(sr.registration_id) as registered_students
    FROM tutor_sessions ts
    JOIN tutor_assignments ta ON ts.assignment_id = ta.assignment_id
    JOIN staff s ON ta.tutor_id = s.staff_id
    LEFT JOIN session_registrations sr ON ts.session_id = sr.session_id
    WHERE ta.risk_module_id = ? AND ts.session_date >= CURDATE()
    GROUP BY ts.session_id
    ORDER BY ts.session_date, ts.start_time
    LIMIT 10
");
$sessions_stmt->execute([$risk_id]);
$sessions = $sessions_stmt->fetchAll();

$risk_colors = [
    'low' => ['bg' => '#d1fae5', 'color' => '#10b981'],
    'medium' => ['bg' => '#fef3c7', 'color' => '#f59e0b'],
    'high' => ['bg' => '#fed7aa', 'color' => '#ea580c'],
    'critical' => ['bg' => '#fee2e2', 'color' => '#dc2626']
];
$colors = $risk_colors[$module['risk_level']];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($module['module_code']); ?> - Module Details</title>
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
                    <a href="at-risk-modules.php" class="btn-link">
                        <i class="fas fa-arrow-left"></i> Back to At-Risk Modules
                    </a>
                </div>

                <!-- Module Header -->
                <div class="hero-section">
                    <div class="hero-content">
                        <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 20px;">
                            <div>
                                <h1><?php echo htmlspecialchars($module['module_code']); ?> - <?php echo htmlspecialchars($module['module_name']); ?></h1>
                                <p style="margin-top: 10px;">
                                    Year Level <?php echo $module['year_level']; ?> | <?php echo $module['credits']; ?> Credits | 
                                    <?php echo $module['academic_year']; ?> - Semester <?php echo $module['semester']; ?>
                                </p>
                            </div>
                            <span class="risk-badge" style="background: <?php echo $colors['bg']; ?>; color: <?php echo $colors['color']; ?>; font-size: 16px; padding: 10px 20px;">
                                <?php echo ucfirst($module['risk_level']); ?> Risk
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($module['failure_rate'], 1); ?>%</h3>
                            <p>Failure Rate</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $module['at_risk_students']; ?>/<?php echo $module['total_students']; ?></h3>
                            <p>Students At Risk</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count($assignments); ?></h3>
                            <p>Tutors Assigned</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count($sessions); ?></h3>
                            <p>Upcoming Sessions</p>
                        </div>
                    </div>
                </div>

                <!-- Module Information -->
                <div class="section">
                    <h2 class="section-title"><i class="fas fa-info-circle"></i> Module Information</h2>
                    
                    <div style="display: grid; gap: 20px;">
                        <div>
                            <h4 style="color: var(--dark); margin-bottom: 8px;">Description</h4>
                            <p style="color: #6b7280; line-height: 1.6;"><?php echo htmlspecialchars($module['description']); ?></p>
                        </div>
                        
                        <div>
                            <h4 style="color: var(--dark); margin-bottom: 8px;">Reason for At-Risk Status</h4>
                            <p style="color: #6b7280; line-height: 1.6;"><?php echo htmlspecialchars($module['reason']); ?></p>
                        </div>
                        
                        <div>
                            <h4 style="color: var(--dark); margin-bottom: 8px;">Intervention Needed</h4>
                            <p style="color: #6b7280; line-height: 1.6;"><?php echo htmlspecialchars($module['intervention_needed']); ?></p>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; padding: 15px; background: #f9fafb; border-radius: 8px;">
                            <div>
                                <p style="font-size: 13px; color: #6b7280; margin-bottom: 5px;">Identified By</p>
                                <p style="font-weight: 600; color: var(--dark);">
                                    <?php echo htmlspecialchars($module['first_name'] . ' ' . $module['last_name']); ?>
                                    <br><small style="color: #9ca3af;"><?php echo htmlspecialchars($module['staff_number']); ?></small>
                                </p>
                            </div>
                            <div>
                                <p style="font-size: 13px; color: #6b7280; margin-bottom: 5px;">Identified Date</p>
                                <p style="font-weight: 600; color: var(--dark);">
                                    <?php echo date('d F Y', strtotime($module['identified_date'])); ?>
                                </p>
                            </div>
                            <div>
                                <p style="font-size: 13px; color: #6b7280; margin-bottom: 5px;">Status</p>
                                <p>
                                    <span class="badge badge-<?php echo $module['status']; ?>" style="padding: 6px 12px;">
                                        <?php echo ucfirst($module['status']); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tutor Assignments -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title"><i class="fas fa-user-tie"></i> Tutor Assignments</h2>
                        <a href="assign-tutor.php?id=<?php echo $risk_id; ?>" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Assign New Tutor
                        </a>
                    </div>
                    
                    <?php if(count($assignments) > 0): ?>
                        <div class="assignments-list">
                            <?php foreach($assignments as $assignment): ?>
                            <div class="assignment-card">
                                <div class="assignment-icon">
                                    <i class="fas fa-<?php echo $assignment['tutor_type'] == 'tutor' ? 'chalkboard-teacher' : 'users'; ?>"></i>
                                </div>
                                <div class="assignment-details">
                                    <h4><?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?></h4>
                                    <p><i class="fas fa-id-card"></i> <?php echo htmlspecialchars($assignment['staff_number']); ?></p>
                                    <p><i class="fas fa-tag"></i> <?php echo ucfirst($assignment['tutor_type']); ?></p>
                                    <p><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($assignment['start_date'])); ?> - <?php echo date('d M Y', strtotime($assignment['end_date'])); ?></p>
                                    <p><i class="fas fa-users"></i> Max <?php echo $assignment['max_students']; ?> students | <?php echo $assignment['session_frequency']; ?></p>
                                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($assignment['location']); ?></p>
                                    <p><i class="fas fa-chart-line"></i> <?php echo $assignment['completed_sessions']; ?>/<?php echo $assignment['total_sessions']; ?> sessions completed</p>
                                </div>
                                <div class="assignment-status">
                                    <span class="badge badge-<?php echo $assignment['status']; ?>">
                                        <?php echo ucfirst($assignment['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-user-plus"></i>
                            <h3>No Tutors Assigned</h3>
                            <p>Assign tutors or PALs to provide support for this module</p>
                            <a href="assign-tutor.php?id=<?php echo $risk_id; ?>" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Assign Tutor
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Upcoming Sessions -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title"><i class="fas fa-calendar-alt"></i> Upcoming Sessions</h2>
                        <a href="sessions.php?module=<?php echo $risk_id; ?>" class="btn-link">
                            View All <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    
                    <?php if(count($sessions) > 0): ?>
                        <div class="bookings-list">
                            <?php foreach($sessions as $session): ?>
                            <div class="booking-card">
                                <div class="booking-icon">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                                <div class="booking-details">
                                    <h4><?php echo htmlspecialchars($session['topic']); ?></h4>
                                    <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?> (<?php echo ucfirst($session['tutor_type']); ?>)</p>
                                    <p><i class="fas fa-calendar"></i> <?php echo date('l, d M Y', strtotime($session['session_date'])); ?></p>
                                    <p><i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($session['start_time'])); ?> - <?php echo date('H:i', strtotime($session['end_time'])); ?></p>
                                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($session['location']); ?></p>
                                    <p><i class="fas fa-users"></i> <?php echo $session['registered_students']; ?>/<?php echo $session['max_capacity']; ?> students registered</p>
                                </div>
                                <div class="booking-status">
                                    <span class="badge badge-<?php echo $session['status']; ?>">
                                        <?php echo ucfirst($session['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No Upcoming Sessions</h3>
                            <p>No tutoring sessions scheduled for this module</p>
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
</body>
</html>
