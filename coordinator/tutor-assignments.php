<?php
session_start();
if(!isset($_SESSION['staff_id']) || $_SESSION['role'] != 'coordinator') {
    header('Location: ../staff-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'active';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$query = "
    SELECT ta.*, 
           m.subject_code, m.subject_name, m.faculty, m.campus,
           arm.academic_year, arm.semester,
           s.first_name, s.last_name, s.staff_number, s.email, s.phone, s.role as tutor_role, s.gpa,
           COUNT(DISTINCT ts.session_id) as total_sessions
    FROM tutor_assignments ta
    JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
    JOIN modules m ON arm.module_id = m.module_id
    JOIN staff s ON ta.tutor_id = s.staff_id
    LEFT JOIN tutor_sessions ts ON ta.assignment_id = ts.assignment_id
    WHERE 1=1
";
$params = [];

// Filter by coordinator's campus
if(isset($_SESSION['assigned_campus']) && $_SESSION['assigned_campus']) {
    $query .= " AND arm.campus = ?";
    $params[] = $_SESSION['assigned_campus'];
}

if($status_filter != 'all') {
    $query .= " AND ta.status = ?";
    $params[] = $status_filter;
}

if($search != '') {
    $query .= " AND (m.subject_code LIKE ? OR m.subject_name LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$query .= " GROUP BY ta.assignment_id ORDER BY ta.assignment_date DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$assignments = $stmt->fetchAll();

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN ta.status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN ta.status = 'completed' THEN 1 ELSE 0 END) as completed,
        COUNT(DISTINCT ta.tutor_id) as unique_tutors
    FROM tutor_assignments ta
    JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
    WHERE 1=1
";
$stats_params = [];

if(isset($_SESSION['assigned_campus']) && $_SESSION['assigned_campus']) {
    $stats_query .= " AND arm.campus = ?";
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
    <title>Assignments - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .search-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .search-input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--blue);
        }
        
        .assignments-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .assignments-table th,
        .assignments-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .assignments-table th {
            background: #f9fafb;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }
        
        .assignments-table td {
            font-size: 14px;
            color: #4b5563;
        }
        
        .assignments-table tr:hover {
            background: #f9fafb;
        }
        
        .tutor-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .tutor-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--blue);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
            flex-shrink: 0;
        }
        
        .tutor-avatar.pal {
            background: var(--green);
        }
        
        .tutor-details h4 {
            margin: 0 0 3px 0;
            font-size: 14px;
            color: var(--dark);
        }
        
        .tutor-details p {
            margin: 0;
            font-size: 12px;
            color: #6b7280;
        }
        
        .module-info h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
            color: var(--dark);
        }
        
        .module-info p {
            margin: 0;
            font-size: 12px;
            color: #6b7280;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d1fae5;
            color: #10b981;
        }
        
        .status-completed {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .status-cancelled {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .role-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .role-tutor {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .role-pal {
            background: #d1fae5;
            color: #10b981;
        }
        
        .gpa-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .gpa-excellent {
            background: #d1fae5;
            color: #10b981;
        }
        
        .gpa-good {
            background: #dbeafe;
            color: #1d4ed8;
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
                    <h1><i class="fas fa-tasks"></i> Assignments</h1>
                    <p>Monitor all tutor and PAL assignments across modules</p>
                </div>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total']; ?></h3>
                            <p>Total Assignments</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['active']; ?></h3>
                            <p>Active Assignments</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['unique_tutors']; ?></h3>
                            <p>Tutors/PALs Assigned</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-flag-checkered"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['completed']; ?></h3>
                            <p>Completed</p>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="section">
                    <div class="filter-tabs">
                        <a href="?status=all<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="filter-tab <?php echo $status_filter == 'all' ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> All (<?php echo $stats['total']; ?>)
                        </a>
                        <a href="?status=active<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="filter-tab <?php echo $status_filter == 'active' ? 'active' : ''; ?>">
                            <i class="fas fa-check-circle"></i> Active (<?php echo $stats['active']; ?>)
                        </a>
                        <a href="?status=completed<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="filter-tab <?php echo $status_filter == 'completed' ? 'active' : ''; ?>">
                            <i class="fas fa-flag-checkered"></i> Completed (<?php echo $stats['completed']; ?>)
                        </a>
                    </div>
                    
                    <form method="GET" class="search-bar">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                        <input type="text" name="search" class="search-input" 
                               placeholder="Search by module, tutor name..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if($search): ?>
                            <a href="?status=<?php echo $status_filter; ?>" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Assignments Table -->
                <div class="section">
                    <h2 class="section-title">
                        <i class="fas fa-list"></i> Assignment List
                        <span style="font-weight: normal; font-size: 14px; color: #6b7280;">
                            (<?php echo count($assignments); ?> results)
                        </span>
                    </h2>
                    
                    <?php if(count($assignments) > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="assignments-table">
                                <thead>
                                    <tr>
                                        <th>Tutor/PAL</th>
                                        <th>Module</th>
                                        <th>Period</th>
                                        <th>Schedule</th>
                                        <th>Sessions</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($assignments as $assignment): ?>
                                    <tr>
                                        <td>
                                            <div class="tutor-info">
                                                <div class="tutor-avatar <?php echo $assignment['tutor_role']; ?>">
                                                    <?php echo strtoupper(substr($assignment['first_name'], 0, 1) . substr($assignment['last_name'], 0, 1)); ?>
                                                </div>
                                                <div class="tutor-details">
                                                    <h4><?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?></h4>
                                                    <p>
                                                        <span class="role-badge role-<?php echo $assignment['tutor_role']; ?>">
                                                            <?php echo strtoupper($assignment['tutor_role']); ?>
                                                        </span>
                                                        <?php if($assignment['gpa']): ?>
                                                            <span class="gpa-badge <?php echo $assignment['gpa'] >= 3.7 ? 'gpa-excellent' : 'gpa-good'; ?>">
                                                                GPA: <?php echo number_format($assignment['gpa'], 2); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="module-info">
                                                <h4><?php echo htmlspecialchars($assignment['subject_code']); ?></h4>
                                                <p><?php echo htmlspecialchars($assignment['subject_name']); ?></p>
                                                <p style="margin-top: 3px; font-size: 11px;">
                                                    <i class="fas fa-building"></i> <?php echo htmlspecialchars($assignment['faculty']); ?>
                                                </p>
                                            </div>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($assignment['academic_year']); ?></strong><br>
                                            <span style="font-size: 12px; color: #6b7280;">Semester <?php echo $assignment['semester']; ?></span>
                                        </td>
                                        <td>
                                            <div style="font-size: 13px;">
                                                <div style="margin-bottom: 5px;">
                                                    <i class="fas fa-calendar"></i> 
                                                    <?php echo date('M d', strtotime($assignment['start_date'])); ?> - 
                                                    <?php echo date('M d, Y', strtotime($assignment['end_date'])); ?>
                                                </div>
                                                <div style="margin-bottom: 5px;">
                                                    <i class="fas fa-clock"></i> <?php echo htmlspecialchars($assignment['session_frequency']); ?>
                                                </div>
                                                <div>
                                                    <i class="fas fa-users"></i> Max <?php echo $assignment['max_students']; ?> students
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <strong style="font-size: 16px; color: var(--blue);"><?php echo $assignment['total_sessions']; ?></strong>
                                            <div style="font-size: 11px; color: #6b7280;">sessions</div>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $assignment['status']; ?>">
                                                <?php echo ucfirst($assignment['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard"></i>
                            <h3>No Assignments Found</h3>
                            <p>
                                <?php if($search): ?>
                                    No assignments match your search criteria.
                                <?php else: ?>
                                    No tutor assignments have been made yet.
                                <?php endif; ?>
                            </p>
                            <a href="browse-modules.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Assign Tutors to Modules
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
