<?php
session_start();
if(!isset($_SESSION['staff_id']) || $_SESSION['role'] != 'coordinator') {
    header('Location: ../staff-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get filter and search
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get all sessions
$query = "
    SELECT ts.*, 
           ta.tutor_type,
           m.subject_code, m.subject_name, m.campus, m.faculty,
           arm.academic_year, arm.semester,
           s.first_name, s.last_name, s.staff_number, s.role as tutor_role,
           COUNT(DISTINCT sr.registration_id) as registered_students
    FROM tutor_sessions ts
    JOIN tutor_assignments ta ON ts.assignment_id = ta.assignment_id
    JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
    JOIN modules m ON arm.module_id = m.module_id
    JOIN staff s ON ta.tutor_id = s.staff_id
    LEFT JOIN session_registrations sr ON ts.session_id = sr.session_id
    WHERE 1=1
";
$params = [];

// Filter by coordinator's campus
if(isset($_SESSION['assigned_campus']) && $_SESSION['assigned_campus']) {
    $query .= " AND arm.campus = ?";
    $params[] = $_SESSION['assigned_campus'];
}

if($status_filter != 'all') {
    $query .= " AND ts.status = ?";
    $params[] = $status_filter;
}

if($search != '') {
    $query .= " AND (ts.topic LIKE ? OR m.subject_code LIKE ? OR m.subject_name LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$query .= " GROUP BY ts.session_id ORDER BY ts.session_date DESC, ts.start_time DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$sessions = $stmt->fetchAll();

// Get statistics
$stats_query = "
    SELECT 
        COUNT(DISTINCT ts.session_id) as total,
        SUM(CASE WHEN ts.status = 'scheduled' THEN 1 ELSE 0 END) as scheduled,
        SUM(CASE WHEN ts.status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN ts.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
    FROM tutor_sessions ts
    JOIN tutor_assignments ta ON ts.assignment_id = ta.assignment_id
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
    <title>Sessions - WSU Booking</title>
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
        
        .sessions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
        }
        
        .session-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s;
        }
        
        .session-card:hover {
            border-color: var(--blue);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .session-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .session-header h3 {
            margin: 0;
            font-size: 16px;
            color: var(--dark);
        }
        
        .session-body {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .session-info {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #4b5563;
        }
        
        .session-info i {
            width: 18px;
            color: var(--blue);
        }
        
        .session-footer {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .attendance-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .attendance-low {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .attendance-medium {
            background: #fef3c7;
            color: #f59e0b;
        }
        
        .attendance-good {
            background: #d1fae5;
            color: #10b981;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .status-scheduled {
            background: #fef3c7;
            color: #f59e0b;
        }
        
        .status-completed {
            background: #d1fae5;
            color: #10b981;
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
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <div class="page-header">
                    <h1><i class="fas fa-calendar-alt"></i> Sessions</h1>
                    <p>Monitor all tutoring and PAL sessions</p>
                </div>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total']; ?></h3>
                            <p>Total Sessions</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['scheduled']; ?></h3>
                            <p>Scheduled</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['completed']; ?></h3>
                            <p>Completed</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['cancelled']; ?></h3>
                            <p>Cancelled</p>
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
                        <a href="?status=scheduled<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="filter-tab <?php echo $status_filter == 'scheduled' ? 'active' : ''; ?>">
                            <i class="fas fa-clock"></i> Scheduled (<?php echo $stats['scheduled']; ?>)
                        </a>
                        <a href="?status=completed<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="filter-tab <?php echo $status_filter == 'completed' ? 'active' : ''; ?>">
                            <i class="fas fa-check-circle"></i> Completed (<?php echo $stats['completed']; ?>)
                        </a>
                        <a href="?status=cancelled<?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                           class="filter-tab <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>">
                            <i class="fas fa-times-circle"></i> Cancelled (<?php echo $stats['cancelled']; ?>)
                        </a>
                    </div>
                    
                    <form method="GET" class="search-bar">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                        <input type="text" name="search" class="search-input" 
                               placeholder="Search by topic, module, or tutor name..." 
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

                <!-- Sessions Grid -->
                <div class="section">
                    <h2 class="section-title">
                        <i class="fas fa-calendar-day"></i> Session Schedule
                        <span style="font-weight: normal; font-size: 14px; color: #6b7280;">
                            (<?php echo count($sessions); ?> results)
                        </span>
                    </h2>
                    
                    <?php if(count($sessions) > 0): ?>
                        <div class="sessions-grid">
                            <?php foreach($sessions as $session): 
                                // Calculate attendance percentage
                                $attendance_pct = $session['max_capacity'] > 0 ? ($session['registered_students'] / $session['max_capacity']) * 100 : 0;
                                $attendance_class = 'attendance-low';
                                if($attendance_pct >= 70) $attendance_class = 'attendance-good';
                                elseif($attendance_pct >= 40) $attendance_class = 'attendance-medium';
                            ?>
                            <div class="session-card">
                                <div class="session-header">
                                    <div>
                                        <h3><?php echo htmlspecialchars($session['topic']); ?></h3>
                                        <p style="margin: 5px 0 0 0; font-size: 12px; color: #6b7280;">
                                            <?php echo htmlspecialchars($session['subject_code']); ?> - <?php echo htmlspecialchars($session['subject_name']); ?>
                                        </p>
                                    </div>
                                    <span class="status-badge status-<?php echo $session['status']; ?>">
                                        <?php echo ucfirst($session['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="session-body">
                                    <div class="session-info">
                                        <i class="fas fa-user-tie"></i>
                                        <span>
                                            <?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?>
                                            <span class="role-badge role-<?php echo $session['tutor_role']; ?>">
                                                <?php echo strtoupper($session['tutor_role']); ?>
                                            </span>
                                        </span>
                                    </div>
                                    
                                    <div class="session-info">
                                        <i class="fas fa-calendar"></i>
                                        <span><?php echo date('l, d M Y', strtotime($session['session_date'])); ?></span>
                                    </div>
                                    
                                    <div class="session-info">
                                        <i class="fas fa-clock"></i>
                                        <span>
                                            <?php echo date('H:i', strtotime($session['start_time'])); ?> - 
                                            <?php echo date('H:i', strtotime($session['end_time'])); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="session-info">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($session['location']); ?></span>
                                    </div>
                                    
                                    <?php if($session['description']): ?>
                                    <div class="session-info">
                                        <i class="fas fa-info-circle"></i>
                                        <span style="font-size: 13px; color: #6b7280;">
                                            <?php echo htmlspecialchars($session['description']); ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="session-footer">
                                    <div class="attendance-badge <?php echo $attendance_class; ?>">
                                        <i class="fas fa-users"></i>
                                        <span><?php echo $session['registered_students']; ?>/<?php echo $session['max_capacity']; ?> students</span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No Sessions Found</h3>
                            <p>
                                <?php if($search): ?>
                                    No sessions match your search criteria.
                                <?php else: ?>
                                    No tutoring sessions have been scheduled yet.
                                <?php endif; ?>
                            </p>
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
