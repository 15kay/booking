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
$risk_filter = isset($_GET['risk']) ? $_GET['risk'] : 'all';
$faculty_filter = isset($_GET['faculty']) ? $_GET['faculty'] : 'all';
$year_filter = isset($_GET['year']) ? $_GET['year'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query - Filter by coordinator's assigned campus only
$query = "SELECT * FROM modules WHERE status = 'active'";
$params = [];

// Filter by coordinator's campus
if(isset($_SESSION['assigned_campus']) && $_SESSION['assigned_campus']) {
    $query .= " AND campus = ?";
    $params[] = $_SESSION['assigned_campus'];
}

if($risk_filter != 'all') {
    $query .= " AND risk_category = ?";
    $params[] = $risk_filter;
}

if($faculty_filter != 'all') {
    $query .= " AND faculty = ?";
    $params[] = $faculty_filter;
}

if($year_filter != 'all') {
    $query .= " AND academic_year = ?";
    $params[] = $year_filter;
}

if($search != '') {
    $query .= " AND (subject_code LIKE ? OR subject_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$query .= " ORDER BY subject_pass_rate ASC, subject_code";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$modules = $stmt->fetchAll();

// Get statistics - Filter by coordinator's campus only
$stats_query = "
    SELECT 
        COUNT(*) as total_modules,
        SUM(CASE WHEN risk_category = 'High Risk' THEN 1 ELSE 0 END) as high_risk,
        SUM(CASE WHEN risk_category = 'Moderate Risk' THEN 1 ELSE 0 END) as moderate_risk,
        SUM(CASE WHEN risk_category = 'Low Risk' THEN 1 ELSE 0 END) as low_risk,
        SUM(CASE WHEN risk_category = 'Very Low Risk' THEN 1 ELSE 0 END) as very_low_risk,
        AVG(subject_pass_rate) as avg_pass_rate,
        SUM(headcount) as total_students
    FROM modules WHERE status = 'active'
";
$stats_params = [];

if(isset($_SESSION['assigned_campus']) && $_SESSION['assigned_campus']) {
    $stats_query .= " AND campus = ?";
    $stats_params[] = $_SESSION['assigned_campus'];
}

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->execute($stats_params);
$stats = $stats_stmt->fetch();

// Get faculties for filter (from coordinator's campus)
$faculties_query = "
    SELECT DISTINCT faculty FROM modules 
    WHERE faculty IS NOT NULL AND faculty != '' AND status = 'active'
";
$faculties_params = [];

if(isset($_SESSION['assigned_campus']) && $_SESSION['assigned_campus']) {
    $faculties_query .= " AND campus = ?";
    $faculties_params[] = $_SESSION['assigned_campus'];
}

$faculties_query .= " ORDER BY faculty";
$faculties_stmt = $conn->prepare($faculties_query);
$faculties_stmt->execute($faculties_params);
$faculties = $faculties_stmt->fetchAll(PDO::FETCH_COLUMN);

// Get years for filter (from coordinator's campus)
$years_query = "
    SELECT DISTINCT academic_year FROM modules 
    WHERE academic_year IS NOT NULL AND status = 'active'
";
$years_params = [];

if(isset($_SESSION['assigned_campus']) && $_SESSION['assigned_campus']) {
    $years_query .= " AND campus = ?";
    $years_params[] = $_SESSION['assigned_campus'];
}

$years_query .= " ORDER BY academic_year DESC";
$years_stmt = $conn->prepare($years_query);
$years_stmt->execute($years_params);
$years = $years_stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Modules - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .search-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--blue);
        }
        
        .filter-select {
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            min-width: 150px;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }
        
        table th,
        table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }
        
        table th {
            background: #f9fafb;
            font-weight: 600;
            color: var(--dark);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        table tr:hover {
            background: #f9fafb;
        }
        
        .risk-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            white-space: nowrap;
        }
        
        .risk-high {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .risk-moderate {
            background: #fed7aa;
            color: #ea580c;
        }
        
        .risk-low {
            background: #fef3c7;
            color: #f59e0b;
        }
        
        .risk-very-low {
            background: #d1fae5;
            color: #10b981;
        }
        
        .btn-flag {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-flag.flagged {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .btn-flag.not-flagged {
            background: #e5e7eb;
            color: var(--dark);
        }
        
        .btn-flag:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .btn-assign {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
            text-decoration: none;
            background: var(--blue);
            color: white;
        }
        
        .btn-assign:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }
        
        .btn-flag-first {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
            text-decoration: none;
            background: #f3f4f6;
            color: #6b7280;
            border: 2px dashed #d1d5db;
        }
        
        .btn-flag-first:hover {
            background: #e5e7eb;
            border-color: #9ca3af;
            transform: translateY(-2px);
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
                    <h1><i class="fas fa-search"></i> Browse Modules</h1>
                    <p>View all modules and assign tutors/PALs based on risk category</p>
                    <?php if(isset($_SESSION['assigned_campus']) && $_SESSION['assigned_campus']): ?>
                        <div style="margin-top: 15px; padding: 12px 20px; background: #eff6ff; border-radius: 8px; border-left: 4px solid var(--blue); display: inline-block;">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Your Campus:</strong>
                            <span style="color: var(--blue); font-weight: 600;"><?php echo htmlspecialchars($_SESSION['assigned_campus']); ?> Campus</span>
                            <span style="color: #6b7280; font-size: 13px; margin-left: 10px;">(All faculties in your campus)</span>
                        </div>
                    <?php else: ?>
                        <div style="margin-top: 15px; padding: 12px 20px; background: #fee2e2; border-radius: 8px; border-left: 4px solid #dc2626; display: inline-block;">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong>Warning:</strong> No campus assigned to your account. 
                            <span style="color: #6b7280; font-size: 13px; margin-left: 10px;">
                                Please log out and log back in, or contact the administrator.
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total_modules']; ?></h3>
                            <p>Total Modules</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['high_risk']; ?></h3>
                            <p>High Risk (<40%)</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['moderate_risk']; ?></h3>
                            <p>Moderate Risk (40-59%)</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($stats['avg_pass_rate'] * 100, 1); ?>%</h3>
                            <p>Average Pass Rate</p>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="section">
                    <div class="filter-tabs">
                        <a href="?risk=all&faculty=<?php echo $faculty_filter; ?>&year=<?php echo $year_filter; ?>&search=<?php echo urlencode($search); ?>" 
                           class="filter-tab <?php echo $risk_filter == 'all' ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> All (<?php echo $stats['total_modules']; ?>)
                        </a>
                        <a href="?risk=High Risk&faculty=<?php echo $faculty_filter; ?>&year=<?php echo $year_filter; ?>&search=<?php echo urlencode($search); ?>" 
                           class="filter-tab <?php echo $risk_filter == 'High Risk' ? 'active' : ''; ?>">
                            <i class="fas fa-exclamation-triangle"></i> High Risk (<?php echo $stats['high_risk']; ?>)
                        </a>
                        <a href="?risk=Moderate Risk&faculty=<?php echo $faculty_filter; ?>&year=<?php echo $year_filter; ?>&search=<?php echo urlencode($search); ?>" 
                           class="filter-tab <?php echo $risk_filter == 'Moderate Risk' ? 'active' : ''; ?>">
                            <i class="fas fa-exclamation-circle"></i> Moderate Risk (<?php echo $stats['moderate_risk']; ?>)
                        </a>
                        <a href="?risk=Low Risk&faculty=<?php echo $faculty_filter; ?>&year=<?php echo $year_filter; ?>&search=<?php echo urlencode($search); ?>" 
                           class="filter-tab <?php echo $risk_filter == 'Low Risk' ? 'active' : ''; ?>">
                            <i class="fas fa-info-circle"></i> Low Risk (<?php echo $stats['low_risk']; ?>)
                        </a>
                    </div>

                    <form method="GET" class="search-bar">
                        <input type="hidden" name="risk" value="<?php echo htmlspecialchars($risk_filter); ?>">
                        <input type="text" name="search" class="search-input" 
                               placeholder="Search by subject code or name..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        
                        <select name="faculty" class="filter-select">
                            <option value="all">All Faculties</option>
                            <?php foreach($faculties as $faculty): ?>
                                <option value="<?php echo htmlspecialchars($faculty); ?>" 
                                        <?php echo $faculty_filter == $faculty ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($faculty); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select name="year" class="filter-select">
                            <option value="all">All Years</option>
                            <?php foreach($years as $year): ?>
                                <option value="<?php echo htmlspecialchars($year); ?>" 
                                        <?php echo $year_filter == $year ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($year); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </form>
                </div>

                <!-- Modules Table -->
                <div class="section">
                    <h2 class="section-title">
                        <i class="fas fa-table"></i> Modules 
                        <span style="font-weight: normal; font-size: 14px; color: #6b7280;">
                            (<?php echo count($modules); ?> results)
                        </span>
                    </h2>
                    
                    <?php if(count($modules) > 0): ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Subject Code</th>
                                        <th>Subject Name</th>
                                        <th>Faculty</th>
                                        <th>Year</th>
                                        <th>Pass Rate</th>
                                        <th>Students</th>
                                        <th>Risk Category</th>
                                        <th>Recommended</th>
                                        <th>Assigned</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($modules as $module): 
                                        // Calculate recommended tutors based on risk category and student count
                                        $recommended_tutors = 0;
                                        if($module['risk_category'] == 'High Risk') {
                                            $recommended_tutors = ceil($module['headcount'] / 15); // 1 tutor per 15 students
                                        } elseif($module['risk_category'] == 'Moderate Risk') {
                                            $recommended_tutors = ceil($module['headcount'] / 20); // 1 tutor per 20 students
                                        } elseif($module['risk_category'] == 'Low Risk') {
                                            $recommended_tutors = ceil($module['headcount'] / 30); // 1 tutor per 30 students
                                        } else {
                                            $recommended_tutors = 1; // At least 1 for very low risk
                                        }
                                        
                                        // Get assigned tutors count
                                        $tutor_stmt = $conn->prepare("
                                            SELECT COUNT(DISTINCT ta.tutor_id) as tutor_count
                                            FROM tutor_assignments ta
                                            JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
                                            WHERE arm.module_id = ? AND arm.academic_year = ? AND ta.status = 'active' AND arm.status = 'active'
                                        ");
                                        $tutor_stmt->execute([$module['module_id'], $module['academic_year']]);
                                        $assigned_count = $tutor_stmt->fetch()['tutor_count'];
                                        
                                        // Check if module is flagged as at-risk
                                        $risk_stmt = $conn->prepare("
                                            SELECT risk_id FROM at_risk_modules 
                                            WHERE module_id = ? AND academic_year = ? AND status = 'active'
                                        ");
                                        $risk_stmt->execute([$module['module_id'], $module['academic_year']]);
                                        $risk_data = $risk_stmt->fetch();
                                        $is_at_risk = $risk_data ? true : false;
                                        $risk_id = $risk_data ? $risk_data['risk_id'] : null;
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($module['subject_code']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($module['subject_name']); ?></td>
                                        <td style="font-size: 12px;"><?php echo htmlspecialchars($module['faculty']); ?></td>
                                        <td><?php echo htmlspecialchars($module['academic_year']); ?></td>
                                        <td>
                                            <strong style="color: <?php echo $module['subject_pass_rate'] < 0.4 ? '#dc2626' : ($module['subject_pass_rate'] < 0.6 ? '#ea580c' : '#10b981'); ?>">
                                                <?php echo number_format($module['subject_pass_rate'] * 100, 1); ?>%
                                            </strong>
                                        </td>
                                        <td><?php echo $module['subjects_passed']; ?>/<?php echo $module['headcount']; ?></td>
                                        <td>
                                            <span class="risk-badge risk-<?php echo strtolower(str_replace(' ', '-', $module['risk_category'])); ?>">
                                                <?php echo $module['risk_category']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span style="color: var(--blue); font-weight: 600; font-size: 14px;">
                                                <i class="fas fa-user-friends"></i> <?php echo $recommended_tutors; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($assigned_count > 0): ?>
                                                <span style="color: <?php echo $assigned_count >= $recommended_tutors ? 'var(--green)' : '#f59e0b'; ?>; font-weight: 600;">
                                                    <i class="fas fa-user-check"></i> <?php echo $assigned_count; ?>
                                                    <?php if($assigned_count < $recommended_tutors): ?>
                                                        <span style="font-size: 11px; color: #f59e0b;">
                                                            (Need <?php echo $recommended_tutors - $assigned_count; ?> more)
                                                        </span>
                                                    <?php endif; ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #dc2626; font-weight: 600;">
                                                    <i class="fas fa-user-times"></i> None
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="assign-tutor.php?module_id=<?php echo $module['module_id']; ?>&year=<?php echo urlencode($module['academic_year']); ?>" 
                                               class="btn-assign">
                                                <i class="fas fa-user-plus"></i> Assign Tutor
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h3>No Modules Found</h3>
                            <p>No modules match your search criteria</p>
                            <a href="browse-modules.php" class="btn btn-secondary">
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
