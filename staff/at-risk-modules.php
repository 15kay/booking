<?php
session_start();
if(!isset($_SESSION['staff_id']) || $_SESSION['role'] != 'coordinator') {
    header('Location: ../staff-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get filter parameters
$risk_level = isset($_GET['risk']) ? $_GET['risk'] : 'all';
$semester = isset($_GET['semester']) ? $_GET['semester'] : 'all';
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Build query - Filter by coordinator's campus
$query = "
    SELECT arm.*, m.module_code, m.module_name, m.year_level, m.credits,
           COUNT(DISTINCT ta.assignment_id) as tutor_count,
           COUNT(DISTINCT ts.session_id) as session_count,
           s.first_name, s.last_name
    FROM at_risk_modules arm
    JOIN modules m ON arm.module_id = m.module_id
    LEFT JOIN tutor_assignments ta ON arm.risk_id = ta.risk_module_id AND ta.status = 'active'
    LEFT JOIN tutor_sessions ts ON ta.assignment_id = ts.assignment_id AND ts.status = 'scheduled'
    LEFT JOIN staff s ON arm.identified_by = s.staff_id
    WHERE 1=1
";

$params = [];

// Filter by coordinator's campus
if(isset($_SESSION['assigned_campus']) && $_SESSION['assigned_campus']) {
    $query .= " AND arm.campus = ?";
    $params[] = $_SESSION['assigned_campus'];
}

if($risk_level != 'all') {
    $query .= " AND arm.risk_level = ?";
    $params[] = $risk_level;
}

if($semester != 'all') {
    $query .= " AND arm.semester = ?";
    $params[] = $semester;
}

if($year != 'all') {
    $query .= " AND arm.academic_year = ?";
    $params[] = $year;
}

$query .= " GROUP BY arm.risk_id ORDER BY 
    FIELD(arm.risk_level, 'critical', 'high', 'medium', 'low'),
    arm.identified_date DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$modules = $stmt->fetchAll();

// Get statistics - Filter by coordinator's campus
$stats_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN risk_level = 'critical' THEN 1 ELSE 0 END) as critical,
        SUM(CASE WHEN risk_level = 'high' THEN 1 ELSE 0 END) as high,
        SUM(CASE WHEN risk_level = 'medium' THEN 1 ELSE 0 END) as medium,
        SUM(CASE WHEN risk_level = 'low' THEN 1 ELSE 0 END) as low,
        SUM(at_risk_students) as total_at_risk_students,
        AVG(failure_rate) as avg_failure_rate
    FROM at_risk_modules
    WHERE status = 'active'
";
$stats_params = [];

if(isset($_SESSION['assigned_campus']) && $_SESSION['assigned_campus']) {
    $stats_query .= " AND campus = ?";
    $stats_params[] = $_SESSION['assigned_campus'];
}

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute($stats_params);
$stats = $stats_stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>At-Risk Modules - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <!-- Page Header -->
                <div class="page-header">
                    <h1><i class="fas fa-exclamation-triangle"></i> At-Risk Modules</h1>
                    <p>Manage and monitor modules that need intervention and support</p>
                </div>

                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total']; ?></h3>
                            <p>Total At-Risk Modules</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_at_risk_students']; ?></h3>
                            <p>Students At Risk</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['avg_failure_rate'], 1); ?>%</h3>
                            <p>Average Failure Rate</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['critical'] + $stats['high']; ?></h3>
                            <p>Critical & High Priority</p>
                        </div>
                    </div>
                </div>

                <!-- Filters and Actions -->
                <div class="section">
                    <div class="section-header">
                        <div class="filter-tabs">
                            <a href="?risk=all&semester=<?php echo $semester; ?>&year=<?php echo $year; ?>" 
                               class="filter-tab <?php echo $risk_level == 'all' ? 'active' : ''; ?>">
                                <i class="fas fa-list"></i> All (<?php echo $stats['total']; ?>)
                            </a>
                            <a href="?risk=critical&semester=<?php echo $semester; ?>&year=<?php echo $year; ?>" 
                               class="filter-tab <?php echo $risk_level == 'critical' ? 'active' : ''; ?>">
                                <i class="fas fa-exclamation-triangle"></i> Critical (<?php echo $stats['critical']; ?>)
                            </a>
                            <a href="?risk=high&semester=<?php echo $semester; ?>&year=<?php echo $year; ?>" 
                               class="filter-tab <?php echo $risk_level == 'high' ? 'active' : ''; ?>">
                                <i class="fas fa-exclamation-circle"></i> High (<?php echo $stats['high']; ?>)
                            </a>
                            <a href="?risk=medium&semester=<?php echo $semester; ?>&year=<?php echo $year; ?>" 
                               class="filter-tab <?php echo $risk_level == 'medium' ? 'active' : ''; ?>">
                                <i class="fas fa-info-circle"></i> Medium (<?php echo $stats['medium']; ?>)
                            </a>
                            <a href="?risk=low&semester=<?php echo $semester; ?>&year=<?php echo $year; ?>" 
                               class="filter-tab <?php echo $risk_level == 'low' ? 'active' : ''; ?>">
                                <i class="fas fa-check-circle"></i> Low (<?php echo $stats['low']; ?>)
                            </a>
                        </div>
                        <a href="add-at-risk-module.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add At-Risk Module
                        </a>
                    </div>

                    <!-- Additional Filters -->
                    <div style="display: flex; gap: 15px; margin-top: 20px; flex-wrap: wrap;">
                        <div>
                            <label style="font-size: 14px; color: #6b7280; margin-bottom: 5px; display: block;">Semester</label>
                            <select onchange="window.location.href='?risk=<?php echo $risk_level; ?>&semester=' + this.value + '&year=<?php echo $year; ?>'" 
                                    style="padding: 8px 12px; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 14px;">
                                <option value="all" <?php echo $semester == 'all' ? 'selected' : ''; ?>>All Semesters</option>
                                <option value="1" <?php echo $semester == '1' ? 'selected' : ''; ?>>Semester 1</option>
                                <option value="2" <?php echo $semester == '2' ? 'selected' : ''; ?>>Semester 2</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size: 14px; color: #6b7280; margin-bottom: 5px; display: block;">Academic Year</label>
                            <select onchange="window.location.href='?risk=<?php echo $risk_level; ?>&semester=<?php echo $semester; ?>&year=' + this.value" 
                                    style="padding: 8px 12px; border: 2px solid #e5e7eb; border-radius: 6px; font-size: 14px;">
                                <option value="all" <?php echo $year == 'all' ? 'selected' : ''; ?>>All Years</option>
                                <option value="2024" <?php echo $year == '2024' ? 'selected' : ''; ?>>2024</option>
                                <option value="2025" <?php echo $year == '2025' ? 'selected' : ''; ?>>2025</option>
                                <option value="2026" <?php echo $year == '2026' ? 'selected' : ''; ?>>2026</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Modules Grid -->
                <div class="section">
                    <?php if(count($modules) > 0): ?>
                        <div class="modules-grid">
                            <?php foreach($modules as $module): 
                                $risk_colors = [
                                    'low' => ['bg' => '#d1fae5', 'color' => '#10b981'],
                                    'medium' => ['bg' => '#fef3c7', 'color' => '#f59e0b'],
                                    'high' => ['bg' => '#fed7aa', 'color' => '#ea580c'],
                                    'critical' => ['bg' => '#fee2e2', 'color' => '#dc2626']
                                ];
                                $colors = $risk_colors[$module['risk_level']];
                            ?>
                            <div class="module-card">
                                <div class="module-header">
                                    <div>
                                        <h3><?php echo htmlspecialchars($module['module_code']); ?></h3>
                                        <p><?php echo htmlspecialchars($module['module_name']); ?></p>
                                        <p style="font-size: 12px; color: #9ca3af; margin-top: 5px;">
                                            <i class="fas fa-calendar"></i> <?php echo $module['academic_year']; ?> - Semester <?php echo $module['semester']; ?>
                                        </p>
                                    </div>
                                    <span class="risk-badge" style="background: <?php echo $colors['bg']; ?>; color: <?php echo $colors['color']; ?>;">
                                        <?php echo ucfirst($module['risk_level']); ?> Risk
                                    </span>
                                </div>
                                
                                <div class="module-stats">
                                    <div class="stat-item">
                                        <i class="fas fa-percentage"></i>
                                        <span><strong><?php echo number_format($module['failure_rate'], 1); ?>%</strong> Failure Rate</span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-users"></i>
                                        <span><strong><?php echo $module['at_risk_students']; ?></strong> of <strong><?php echo $module['total_students']; ?></strong> Students At Risk</span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-chalkboard-teacher"></i>
                                        <span><strong><?php echo $module['tutor_count']; ?></strong> Tutor(s) Assigned</span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-calendar-check"></i>
                                        <span><strong><?php echo $module['session_count']; ?></strong> Upcoming Session(s)</span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-user"></i>
                                        <span>Identified by: <?php echo htmlspecialchars($module['first_name'] . ' ' . $module['last_name']); ?></span>
                                    </div>
                                    <div class="stat-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo date('d M Y', strtotime($module['identified_date'])); ?></span>
                                    </div>
                                </div>

                                <div style="padding: 15px 20px; background: #f9fafb; border-top: 1px solid #e5e7eb;">
                                    <p style="font-size: 13px; color: #6b7280; margin-bottom: 8px;">
                                        <strong>Reason:</strong> <?php echo htmlspecialchars($module['reason']); ?>
                                    </p>
                                    <p style="font-size: 13px; color: #6b7280;">
                                        <strong>Intervention:</strong> <?php echo htmlspecialchars($module['intervention_needed']); ?>
                                    </p>
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
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-check-circle"></i>
                            <h3>No At-Risk Modules Found</h3>
                            <p>No modules match your current filter criteria.</p>
                            <a href="at-risk-modules.php" class="btn btn-secondary">
                                <i class="fas fa-redo"></i> Clear Filters
                            </a>
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
