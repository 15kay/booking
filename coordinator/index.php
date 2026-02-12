<?php
session_start();
if(!isset($_SESSION['staff_id']) || $_SESSION['role'] != 'coordinator') {
    header('Location: ../staff-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get statistics - Filter by coordinator's campus
$stats_query = "SELECT COUNT(*) as total FROM at_risk_modules WHERE status = 'active'";
$stats_params = [];

if(isset($_SESSION['assigned_campus']) && $_SESSION['assigned_campus']) {
    $stats_query .= " AND campus = ?";
    $stats_params[] = $_SESSION['assigned_campus'];
}

$stmt = $conn->prepare($stats_query);
$stmt->execute($stats_params);
$total_at_risk = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM tutor_assignments WHERE status = 'active'");
$total_assignments = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM staff WHERE role IN ('tutor', 'pal') AND status = 'active'");
$total_tutors = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM tutor_sessions WHERE session_date >= CURDATE() AND status = 'scheduled'");
$upcoming_sessions = $stmt->fetch()['total'];

// Get recent at-risk modules - Filter by coordinator's campus
$recent_query = "
    SELECT arm.*, m.subject_code, m.subject_name, m.academic_year,
           COUNT(ta.assignment_id) as tutor_count
    FROM at_risk_modules arm
    JOIN modules m ON arm.module_id = m.module_id
    LEFT JOIN tutor_assignments ta ON arm.risk_id = ta.risk_module_id AND ta.status = 'active'
    WHERE arm.status = 'active'
";
$recent_params = [];

if(isset($_SESSION['assigned_campus']) && $_SESSION['assigned_campus']) {
    $recent_query .= " AND arm.campus = ?";
    $recent_params[] = $_SESSION['assigned_campus'];
}

$recent_query .= " GROUP BY arm.risk_id ORDER BY arm.identified_date DESC LIMIT 5";

$stmt = $conn->prepare($recent_query);
$stmt->execute($recent_params);
$recent_at_risk = $stmt->fetchAll();

// Get recent assignments
$stmt = $conn->query("
    SELECT ta.*, m.subject_code, m.subject_name,
           s.first_name, s.last_name, s.staff_number
    FROM tutor_assignments ta
    JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
    JOIN modules m ON arm.module_id = m.module_id
    JOIN staff s ON ta.tutor_id = s.staff_id
    WHERE ta.status = 'active'
    ORDER BY ta.assignment_date DESC
    LIMIT 5
");
$recent_assignments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coordinator Dashboard - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <!-- Campus Assignment Notice -->
                <?php if(!isset($_SESSION['assigned_campus']) || !$_SESSION['assigned_campus']): ?>
                    <div style="margin-bottom: 20px; padding: 15px 20px; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
                        <i class="fas fa-exclamation-circle" style="color: #f59e0b;"></i> 
                        <strong>Action Required:</strong> 
                        No campus assigned to your account. Please log out and log back in to refresh your session.
                        <a href="../auth/logout.php" style="margin-left: 10px; color: #f59e0b; text-decoration: underline;">Log out now</a>
                    </div>
                <?php endif; ?>
                
                <!-- Hero Section -->
                <div class="hero-section">
                    <div class="hero-content">
                        <h1>Coordinator Dashboard</h1>
                        <p>Manage at-risk modules and assign tutors/PALs to support student success</p>
                        <div class="hero-stats">
                            <div class="hero-stat">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span><?php echo $total_at_risk; ?> At-Risk Modules</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-user-tie"></i>
                                <span><?php echo $total_tutors; ?> Tutors/PALs</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <span><?php echo $total_assignments; ?> Active Assignments</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-calendar-check"></i>
                                <span><?php echo $upcoming_sessions; ?> Upcoming Sessions</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_at_risk; ?></h3>
                            <p>At-Risk Modules</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_tutors; ?></h3>
                            <p>Available Tutors/PALs</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_assignments; ?></h3>
                            <p>Active Assignments</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $upcoming_sessions; ?></h3>
                            <p>Upcoming Sessions</p>
                        </div>
                    </div>
                </div>

                <!-- At-Risk Modules -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title"><i class="fas fa-exclamation-triangle"></i> At-Risk Modules</h2>
                        <a href="add-at-risk-module.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add At-Risk Module
                        </a>
                    </div>
                    
                    <?php if(count($recent_at_risk) > 0): ?>
                        <div class="modules-grid">
                            <?php foreach($recent_at_risk as $module): 
                                // Determine risk level based on pass rate or reason
                                $risk_level = 'medium';
                                if(isset($module['risk_category'])) {
                                    if(strpos(strtolower($module['risk_category']), 'high') !== false) {
                                        $risk_level = 'high';
                                    } elseif(strpos(strtolower($module['risk_category']), 'moderate') !== false) {
                                        $risk_level = 'medium';
                                    } else {
                                        $risk_level = 'low';
                                    }
                                }
                                
                                $risk_colors = [
                                    'low' => ['bg' => '#d1fae5', 'color' => '#10b981'],
                                    'medium' => ['bg' => '#fef3c7', 'color' => '#f59e0b'],
                                    'high' => ['bg' => '#fee2e2', 'color' => '#dc2626']
                                ];
                                $colors = $risk_colors[$risk_level];
                            ?>
                            <div class="module-card">
                                <div class="module-header">
                                    <div>
                                        <h3><?php echo htmlspecialchars($module['subject_code']); ?></h3>
                                        <p><?php echo htmlspecialchars($module['subject_name']); ?></p>
                                    </div>
                                    <span class="risk-badge" style="background: <?php echo $colors['bg']; ?>; color: <?php echo $colors['color']; ?>;">
                                        <?php echo ucfirst($risk_level); ?> Risk
                                    </span>
                                </div>
                                <div class="module-stats">
                                    <div class="stat-item">
                                        <i class="fas fa-users"></i>
                                        <span><?php echo $module['at_risk_students']; ?> At-Risk Students</span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                        <span><?php echo $module['tutor_count']; ?> Tutor(s) Assigned</span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-calendar"></i>
                                        <span><?php echo date('M j, Y', strtotime($module['identified_date'])); ?></span>
                                    </div>
                                </div>
                                <div class="module-actions">
                                    <a href="assign-tutor.php?id=<?php echo $module['risk_id']; ?>" class="btn-action btn-primary">
                                        <i class="fas fa-user-plus"></i> Assign Tutor
                                    </a>
                                    <a href="module-details.php?id=<?php echo $module['risk_id']; ?>" class="btn-action btn-secondary">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="text-align: center; margin-top: 20px;">
                            <a href="at-risk-modules.php" class="btn-link">View All At-Risk Modules <i class="fas fa-arrow-right"></i></a>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-check-circle"></i>
                            <h3>No At-Risk Modules</h3>
                            <p>All modules are performing well. Add modules that need intervention.</p>
                            <a href="add-at-risk-module.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add At-Risk Module
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Assignments -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title"><i class="fas fa-clipboard-list"></i> Recent Tutor Assignments</h2>
                        <a href="tutor-assignments.php" class="btn-link">View All <i class="fas fa-arrow-right"></i></a>
                    </div>
                    
                    <?php if(count($recent_assignments) > 0): ?>
                        <div class="assignments-list">
                            <?php foreach($recent_assignments as $assignment): ?>
                            <div class="assignment-card">
                                <div class="assignment-icon">
                                    <i class="fas fa-<?php echo $assignment['tutor_type'] == 'tutor' ? 'chalkboard-teacher' : 'users'; ?>"></i>
                                </div>
                                <div class="assignment-details">
                                    <h4><?php echo htmlspecialchars($assignment['subject_code']); ?> - <?php echo htmlspecialchars($assignment['subject_name']); ?></h4>
                                    <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?> (<?php echo htmlspecialchars($assignment['staff_number']); ?>)</p>
                                    <p><i class="fas fa-tag"></i> <?php echo ucfirst($assignment['tutor_type']); ?> | <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($assignment['start_date'])); ?></p>
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
                            <i class="fas fa-clipboard"></i>
                            <h3>No Assignments Yet</h3>
                            <p>Start assigning tutors and PALs to at-risk modules</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="section">
                    <h2 class="section-title"><i class="fas fa-bolt"></i> Quick Actions</h2>
                    <div class="quick-actions">
                        <a href="add-at-risk-module.php" class="action-card">
                            <i class="fas fa-plus-circle"></i>
                            <h3>Add At-Risk Module</h3>
                            <p>Identify a new module that needs intervention</p>
                        </a>
                        <a href="tutors.php" class="action-card">
                            <i class="fas fa-users"></i>
                            <h3>Manage Tutors/PALs</h3>
                            <p>View and manage all tutors and PALs</p>
                        </a>
                        <a href="sessions.php" class="action-card">
                            <i class="fas fa-calendar-alt"></i>
                            <h3>View Sessions</h3>
                            <p>See all scheduled tutoring sessions</p>
                        </a>
                        <a href="reports.php" class="action-card">
                            <i class="fas fa-chart-line"></i>
                            <h3>Performance Reports</h3>
                            <p>View analytics and performance metrics</p>
                        </a>
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
