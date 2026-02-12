<?php
session_start();
if(!isset($_SESSION['staff_id']) || $_SESSION['role'] != 'coordinator') {
    header('Location: ../staff-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get coordinator's campus
$coord_stmt = $conn->prepare("SELECT assigned_campus FROM staff WHERE staff_id = ?");
$coord_stmt->execute([$_SESSION['staff_id']]);
$coordinator = $coord_stmt->fetch();
$campus = $coordinator['assigned_campus'];

// Get date range filter
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-t');

// Overview Statistics
$stats = $conn->prepare("
    SELECT 
        COUNT(DISTINCT m.module_id) as total_modules,
        COUNT(DISTINCT CASE WHEN m.risk_category = 'High Risk' THEN m.module_id END) as high_risk_modules,
        COUNT(DISTINCT ta.assignment_id) as total_assignments,
        COUNT(DISTINCT ta.tutor_id) as active_tutors,
        COUNT(DISTINCT ts.session_id) as total_sessions,
        COUNT(DISTINCT CASE WHEN ts.status = 'completed' THEN ts.session_id END) as completed_sessions,
        COUNT(DISTINCT sr.registration_id) as total_registrations
    FROM modules m
    LEFT JOIN at_risk_modules arm ON m.module_id = arm.module_id AND arm.status = 'active'
    LEFT JOIN tutor_assignments ta ON arm.risk_id = ta.risk_module_id AND ta.status = 'active'
    LEFT JOIN tutor_sessions ts ON ta.assignment_id = ts.assignment_id
    LEFT JOIN session_registrations sr ON ts.session_id = sr.session_id
    WHERE m.campus = ? AND m.academic_year = ?
");
$stats->execute([$campus, date('Y')]);
$overview = $stats->fetch();

// Tutor Performance
$tutor_performance = $conn->prepare("
    SELECT 
        s.staff_id, s.first_name, s.last_name, s.student_number, s.role, s.gpa,
        COUNT(DISTINCT ta.assignment_id) as assignments,
        COUNT(DISTINCT ts.session_id) as sessions_conducted,
        COUNT(DISTINCT CASE WHEN ts.status = 'completed' THEN ts.session_id END) as completed_sessions,
        COUNT(DISTINCT sr.registration_id) as total_students_reached,
        AVG(CASE WHEN ts.status = 'completed' THEN 
            (SELECT COUNT(*) FROM session_registrations WHERE session_id = ts.session_id AND attended = TRUE)
        END) as avg_attendance
    FROM staff s
    INNER JOIN tutor_assignments ta ON s.staff_id = ta.tutor_id
    INNER JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
    LEFT JOIN tutor_sessions ts ON ta.assignment_id = ts.assignment_id
    LEFT JOIN session_registrations sr ON ts.session_id = sr.session_id
    WHERE s.role IN ('tutor', 'pal') AND arm.campus = ? AND ta.status = 'active'
    GROUP BY s.staff_id
    ORDER BY sessions_conducted DESC, avg_attendance DESC
    LIMIT 10
");
$tutor_performance->execute([$campus]);
$top_tutors = $tutor_performance->fetchAll();

// Module Risk Distribution
$risk_dist = $conn->prepare("
    SELECT 
        risk_category,
        COUNT(*) as count,
        SUM(headcount) as total_students
    FROM modules
    WHERE campus = ? AND academic_year = ?
    GROUP BY risk_category
    ORDER BY FIELD(risk_category, 'High Risk', 'Moderate Risk', 'Low Risk', 'Very Low Risk')
");
$risk_dist->execute([$campus, date('Y')]);
$risk_distribution = $risk_dist->fetchAll();

// Faculty Performance
$faculty_perf = $conn->prepare("
    SELECT 
        m.faculty,
        COUNT(DISTINCT m.module_id) as modules,
        AVG(m.subject_pass_rate) as avg_pass_rate,
        COUNT(DISTINCT ta.assignment_id) as tutors_assigned,
        COUNT(DISTINCT ts.session_id) as sessions_held
    FROM modules m
    LEFT JOIN at_risk_modules arm ON m.module_id = arm.module_id AND arm.status = 'active'
    LEFT JOIN tutor_assignments ta ON arm.risk_id = ta.risk_module_id AND ta.status = 'active'
    LEFT JOIN tutor_sessions ts ON ta.assignment_id = ts.assignment_id
    WHERE m.campus = ? AND m.academic_year = ?
    GROUP BY m.faculty
    ORDER BY avg_pass_rate ASC
");
$faculty_perf->execute([$campus, date('Y')]);
$faculty_performance = $faculty_perf->fetchAll();

// Recent Sessions
$recent_sessions = $conn->prepare("
    SELECT 
        ts.*, 
        s.first_name, s.last_name, s.role,
        m.subject_code, m.subject_name,
        COUNT(sr.registration_id) as registered_students,
        COUNT(CASE WHEN sr.attended = TRUE THEN 1 END) as attended_students
    FROM tutor_sessions ts
    INNER JOIN tutor_assignments ta ON ts.assignment_id = ta.assignment_id
    INNER JOIN staff s ON ta.tutor_id = s.staff_id
    INNER JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
    INNER JOIN modules m ON arm.module_id = m.module_id
    LEFT JOIN session_registrations sr ON ts.session_id = sr.session_id
    WHERE m.campus = ? AND ts.session_date BETWEEN ? AND ?
    GROUP BY ts.session_id
    ORDER BY ts.session_date DESC, ts.start_time DESC
    LIMIT 15
");
$recent_sessions->execute([$campus, $date_from, $date_to]);
$sessions = $recent_sessions->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .report-filters {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        
        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }
        
        .filter-group input {
            width: 100%;
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
        }
        
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .report-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
        }
        
        .report-card h3 {
            margin: 0 0 20px 0;
            font-size: 16px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .report-card h3 i {
            color: var(--blue);
        }
        
        .chart-placeholder {
            height: 200px;
            background: #f9fafb;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            font-size: 14px;
        }
        
        .performance-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .performance-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .performance-item:last-child {
            border-bottom: none;
        }
        
        .performance-name {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }
        
        .performance-avatar {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background: var(--blue);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
        }
        
        .performance-info {
            flex: 1;
        }
        
        .performance-info strong {
            display: block;
            font-size: 14px;
            color: var(--dark);
        }
        
        .performance-info span {
            font-size: 12px;
            color: #6b7280;
        }
        
        .performance-value {
            font-size: 18px;
            font-weight: 700;
            color: var(--blue);
        }
        
        .risk-bar {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .risk-label {
            min-width: 120px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .risk-progress {
            flex: 1;
            height: 30px;
            background: #f3f4f6;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
        }
        
        .risk-fill {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: 600;
            transition: width 0.3s;
        }
        
        .risk-high .risk-fill {
            background: #dc2626;
        }
        
        .risk-moderate .risk-fill {
            background: #f59e0b;
        }
        
        .risk-low .risk-fill {
            background: #3b82f6;
        }
        
        .risk-very-low .risk-fill {
            background: #10b981;
        }
        
        .faculty-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .faculty-table th,
        .faculty-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            font-size: 13px;
        }
        
        .faculty-table th {
            background: #f9fafb;
            font-weight: 600;
            color: var(--dark);
        }
        
        .faculty-table tr:hover {
            background: #f9fafb;
        }
        
        .session-timeline {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .session-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            border-left: 3px solid #e5e7eb;
            margin-bottom: 15px;
            background: #f9fafb;
            border-radius: 0 8px 8px 0;
        }
        
        .session-item.completed {
            border-left-color: var(--green);
        }
        
        .session-item.scheduled {
            border-left-color: var(--blue);
        }
        
        .session-item.cancelled {
            border-left-color: #dc2626;
        }
        
        .session-date {
            min-width: 80px;
            text-align: center;
        }
        
        .session-day {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
        }
        
        .session-month {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
        }
        
        .session-details {
            flex: 1;
        }
        
        .session-details h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
            color: var(--dark);
        }
        
        .session-details p {
            margin: 0;
            font-size: 12px;
            color: #6b7280;
        }
        
        .export-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
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
                    <h1><i class="fas fa-chart-line"></i> Reports & Analytics</h1>
                    <p>Performance insights and statistics for <?php echo htmlspecialchars($campus); ?> Campus</p>
                </div>

                <!-- Date Range Filter -->
                <form method="GET" class="report-filters">
                    <div class="filter-group">
                        <label><i class="fas fa-calendar"></i> From Date</label>
                        <input type="date" name="date_from" value="<?php echo $date_from; ?>">
                    </div>
                    <div class="filter-group">
                        <label><i class="fas fa-calendar"></i> To Date</label>
                        <input type="date" name="date_to" value="<?php echo $date_to; ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filter
                    </button>
                </form>

                <!-- Overview Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $overview['total_modules']; ?></h3>
                            <p>Total Modules</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $overview['high_risk_modules']; ?></h3>
                            <p>High Risk Modules</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $overview['active_tutors']; ?></h3>
                            <p>Active Tutors/PALs</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $overview['total_sessions']; ?></h3>
                            <p>Total Sessions</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $overview['completed_sessions']; ?></h3>
                            <p>Completed Sessions</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $overview['total_registrations']; ?></h3>
                            <p>Student Registrations</p>
                        </div>
                    </div>
                </div>

                <!-- Report Grid -->
                <div class="report-grid">
                    <!-- Top Performing Tutors -->
                    <div class="report-card">
                        <h3><i class="fas fa-trophy"></i> Top Performing Tutors/PALs</h3>
                        <?php if(count($top_tutors) > 0): ?>
                            <ul class="performance-list">
                                <?php foreach($top_tutors as $tutor): ?>
                                    <li class="performance-item">
                                        <div class="performance-name">
                                            <div class="performance-avatar">
                                                <?php echo strtoupper(substr($tutor['first_name'], 0, 1) . substr($tutor['last_name'], 0, 1)); ?>
                                            </div>
                                            <div class="performance-info">
                                                <strong><?php echo htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']); ?></strong>
                                                <span><?php echo ucfirst($tutor['role']); ?> • GPA: <?php echo number_format($tutor['gpa'] ?? 0, 2); ?></span>
                                            </div>
                                        </div>
                                        <div class="performance-value"><?php echo $tutor['sessions_conducted']; ?></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p style="color: #6b7280; text-align: center; padding: 20px;">No tutor data available</p>
                        <?php endif; ?>
                    </div>

                    <!-- Module Risk Distribution -->
                    <div class="report-card">
                        <h3><i class="fas fa-chart-pie"></i> Module Risk Distribution</h3>
                        <?php if(count($risk_distribution) > 0): 
                            $total_modules = array_sum(array_column($risk_distribution, 'count'));
                        ?>
                            <?php foreach($risk_distribution as $risk): 
                                $percentage = $total_modules > 0 ? ($risk['count'] / $total_modules) * 100 : 0;
                                $risk_class = '';
                                if($risk['risk_category'] == 'High Risk') $risk_class = 'risk-high';
                                elseif($risk['risk_category'] == 'Moderate Risk') $risk_class = 'risk-moderate';
                                elseif($risk['risk_category'] == 'Low Risk') $risk_class = 'risk-low';
                                else $risk_class = 'risk-very-low';
                            ?>
                                <div class="risk-bar <?php echo $risk_class; ?>">
                                    <div class="risk-label"><?php echo $risk['risk_category']; ?></div>
                                    <div class="risk-progress">
                                        <div class="risk-fill" style="width: <?php echo $percentage; ?>%">
                                            <?php echo $risk['count']; ?> (<?php echo number_format($percentage, 1); ?>%)
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="color: #6b7280; text-align: center; padding: 20px;">No module data available</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Faculty Performance -->
                <div class="section">
                    <h2 class="section-title"><i class="fas fa-building"></i> Faculty Performance</h2>
                    <div class="report-card">
                        <?php if(count($faculty_performance) > 0): ?>
                            <table class="faculty-table">
                                <thead>
                                    <tr>
                                        <th>Faculty</th>
                                        <th>Modules</th>
                                        <th>Avg Pass Rate</th>
                                        <th>Tutors Assigned</th>
                                        <th>Sessions Held</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($faculty_performance as $faculty): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($faculty['faculty']); ?></strong></td>
                                            <td><?php echo $faculty['modules']; ?></td>
                                            <td>
                                                <strong style="color: <?php echo $faculty['avg_pass_rate'] < 0.4 ? '#dc2626' : ($faculty['avg_pass_rate'] < 0.6 ? '#f59e0b' : 'var(--green)'); ?>">
                                                    <?php echo number_format($faculty['avg_pass_rate'] * 100, 1); ?>%
                                                </strong>
                                            </td>
                                            <td><?php echo $faculty['tutors_assigned']; ?></td>
                                            <td><?php echo $faculty['sessions_held']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="color: #6b7280; text-align: center; padding: 20px;">No faculty data available</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Sessions -->
                <div class="section">
                    <h2 class="section-title"><i class="fas fa-history"></i> Recent Sessions</h2>
                    <div class="report-card">
                        <?php if(count($sessions) > 0): ?>
                            <div class="session-timeline">
                                <?php foreach($sessions as $session): 
                                    $date = new DateTime($session['session_date']);
                                    $attendance_rate = $session['registered_students'] > 0 ? 
                                        ($session['attended_students'] / $session['registered_students']) * 100 : 0;
                                ?>
                                    <div class="session-item <?php echo strtolower($session['status']); ?>">
                                        <div class="session-date">
                                            <div class="session-day"><?php echo $date->format('d'); ?></div>
                                            <div class="session-month"><?php echo $date->format('M'); ?></div>
                                        </div>
                                        <div class="session-details">
                                            <h4><?php echo htmlspecialchars($session['topic']); ?></h4>
                                            <p>
                                                <strong><?php echo htmlspecialchars($session['subject_code']); ?></strong> • 
                                                <?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?> 
                                                (<?php echo ucfirst($session['role']); ?>) • 
                                                <?php echo date('g:i A', strtotime($session['start_time'])); ?> - 
                                                <?php echo date('g:i A', strtotime($session['end_time'])); ?>
                                            </p>
                                            <p>
                                                <span class="badge badge-<?php echo $session['status']; ?>">
                                                    <?php echo ucfirst($session['status']); ?>
                                                </span>
                                                <?php if($session['registered_students'] > 0): ?>
                                                    • Attendance: <?php echo $session['attended_students']; ?>/<?php echo $session['registered_students']; ?> 
                                                    (<?php echo number_format($attendance_rate, 0); ?>%)
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-times"></i>
                                <h3>No Sessions Found</h3>
                                <p>No sessions found for the selected date range.</p>
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
