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
$type_filter = isset($_GET['type']) ? $_GET['type'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$view = isset($_GET['view']) ? $_GET['view'] : 'card'; // card or table

// Get all tutors and PALs with their workload
$query = "
    SELECT s.*,
           COUNT(DISTINCT ta.assignment_id) as total_assignments,
           COUNT(DISTINCT CASE WHEN ta.status = 'active' THEN ta.assignment_id END) as active_assignments,
           COUNT(DISTINCT ts.session_id) as total_sessions
    FROM staff s
    LEFT JOIN tutor_assignments ta ON s.staff_id = ta.tutor_id
    LEFT JOIN tutor_sessions ts ON ta.assignment_id = ts.assignment_id
    WHERE s.role IN ('tutor', 'pal') AND s.status = 'active'
";

$params = [];

if($type_filter == 'tutor') {
    $query .= " AND s.role = 'tutor'";
} elseif($type_filter == 'pal') {
    $query .= " AND s.role = 'pal'";
}

if($search != '') {
    $query .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.staff_number LIKE ? OR s.specialization LIKE ?)";
    $search_param = "%$search%";
    $params = [$search_param, $search_param, $search_param, $search_param];
}

$query .= " GROUP BY s.staff_id ORDER BY s.last_name, s.first_name";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$tutors = $stmt->fetchAll();

// Debug: Check if query is working
// echo "<!-- DEBUG: Query executed. Found " . count($tutors) . " tutors -->";
// echo "<!-- DEBUG: Type filter: " . $type_filter . " -->";
// echo "<!-- DEBUG: Search: " . $search . " -->";

// Get statistics
$stats = $conn->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN role = 'tutor' THEN 1 ELSE 0 END) as tutors,
        SUM(CASE WHEN role = 'pal' THEN 1 ELSE 0 END) as pals
    FROM staff 
    WHERE role IN ('tutor', 'pal') AND status = 'active'
")->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tutors & PALs - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .search-bar {
            display: flex;
            gap: 15px;
            flex: 1;
        }
        
        .view-toggle {
            display: flex;
            gap: 5px;
            background: #f3f4f6;
            padding: 5px;
            border-radius: 8px;
        }
        
        .view-btn {
            padding: 10px 15px;
            border: none;
            background: transparent;
            color: #6b7280;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .view-btn:hover {
            background: #e5e7eb;
            color: var(--dark);
        }
        
        .view-btn.active {
            background: var(--blue);
            color: white;
        }
        
        .tutors-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .tutors-table th,
        .tutors-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .tutors-table th {
            background: #f9fafb;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }
        
        .tutors-table td {
            font-size: 14px;
            color: #4b5563;
        }
        
        .tutors-table tr:hover {
            background: #f9fafb;
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
        
        .tutor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .tutor-card {
            background: var(--white);
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .tutor-card:hover {
            border-color: var(--blue);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .tutor-header {
            padding: 20px;
            background: linear-gradient(135deg, #f9fafb 0%, #e5e7eb 100%);
            border-bottom: 1px solid #e5e7eb;
        }
        
        .tutor-header-top {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        
        .tutor-avatar-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .tutor-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--blue);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: 600;
        }
        
        .tutor-card[data-role="pal"] .tutor-avatar {
            background: var(--green);
        }
        
        .tutor-header h3 {
            font-size: 18px;
            color: var(--dark);
            margin: 0 0 5px 0;
        }
        
        .tutor-header p {
            font-size: 13px;
            color: #6b7280;
            margin: 0;
        }
        
        .tutor-body {
            padding: 20px;
        }
        
        .tutor-info {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .info-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            color: #6b7280;
        }
        
        .info-row i {
            width: 20px;
            color: var(--blue);
        }
        
        .tutor-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: var(--blue);
        }
        
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            margin-top: 2px;
        }
        
        .workload-indicator {
            padding: 10px 15px;
            border-radius: 8px;
            text-align: center;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .workload-available {
            background: #d1fae5;
            color: #10b981;
        }
        
        .workload-good {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .workload-moderate {
            background: #fef3c7;
            color: #f59e0b;
        }
        
        .workload-high {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .tutor-actions {
            display: flex;
            gap: 10px;
        }
        
        .btn-view {
            flex: 1;
            padding: 10px;
            border: 2px solid var(--blue);
            background: white;
            color: var(--blue);
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-view:hover {
            background: var(--blue);
            color: white;
        }
        
        .type-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .type-tutor {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .type-pal {
            background: #d1fae5;
            color: #10b981;
        }
        
        .rating {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #f59e0b;
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
                    <h1><i class="fas fa-user-tie"></i> Tutors & PALs</h1>
                    <p>View and manage all tutors and peer assisted learning facilitators</p>
                </div>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['total']; ?></h3>
                            <p>Total Tutors & PALs</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['tutors']; ?></h3>
                            <p>Tutors</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-user-friends"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $stats['pals']; ?></h3>
                            <p>PALs</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo array_sum(array_column($tutors, 'active_assignments')); ?></h3>
                            <p>Active Assignments</p>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="section">
                    <div class="filter-tabs">
                        <a href="?type=all<?php echo $search ? '&search=' . urlencode($search) : ''; ?>&view=<?php echo $view; ?>" 
                           class="filter-tab <?php echo $type_filter == 'all' ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> All (<?php echo $stats['total']; ?>)
                        </a>
                        <a href="?type=tutor<?php echo $search ? '&search=' . urlencode($search) : ''; ?>&view=<?php echo $view; ?>" 
                           class="filter-tab <?php echo $type_filter == 'tutor' ? 'active' : ''; ?>">
                            <i class="fas fa-chalkboard-teacher"></i> Tutors (<?php echo $stats['tutors']; ?>)
                        </a>
                        <a href="?type=pal<?php echo $search ? '&search=' . urlencode($search) : ''; ?>&view=<?php echo $view; ?>" 
                           class="filter-tab <?php echo $type_filter == 'pal' ? 'active' : ''; ?>">
                            <i class="fas fa-user-friends"></i> PALs (<?php echo $stats['pals']; ?>)
                        </a>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 15px;">
                        <form method="GET" class="search-bar" style="margin-bottom: 0;">
                            <input type="hidden" name="type" value="<?php echo htmlspecialchars($type_filter); ?>">
                            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                            <input type="text" name="search" class="search-input" 
                                   placeholder="Search by name, staff number, or specialization..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <?php if($search): ?>
                                <a href="?type=<?php echo $type_filter; ?>&view=<?php echo $view; ?>" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            <?php endif; ?>
                        </form>
                        
                        <div class="view-toggle">
                            <a href="?type=<?php echo $type_filter; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>&view=card" 
                               class="view-btn <?php echo $view == 'card' ? 'active' : ''; ?>" title="Card View">
                                <i class="fas fa-th-large"></i>
                            </a>
                            <a href="?type=<?php echo $type_filter; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>&view=table" 
                               class="view-btn <?php echo $view == 'table' ? 'active' : ''; ?>" title="Table View">
                                <i class="fas fa-table"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Tutors Grid -->
                <div class="section">
                    <h2 class="section-title">
                        <i class="fas fa-users"></i> 
                        <?php echo $type_filter == 'tutor' ? 'Tutors' : ($type_filter == 'pal' ? 'PALs' : 'All Tutors & PALs'); ?>
                        <span style="font-weight: normal; font-size: 14px; color: #6b7280;">
                            (<?php echo count($tutors); ?> results)
                        </span>
                    </h2>
                    
                    <?php if(count($tutors) > 0): ?>
                        <?php if($view == 'card'): ?>
                            <!-- Card View -->
                            <div class="tutor-grid">
                            <?php foreach($tutors as $tutor): 
                                // Determine workload status
                                $workload_class = 'workload-available';
                                $workload_text = 'Available';
                                if($tutor['active_assignments'] > 0) {
                                    if($tutor['active_assignments'] <= 2) {
                                        $workload_class = 'workload-good';
                                        $workload_text = 'Good Availability';
                                    } elseif($tutor['active_assignments'] <= 4) {
                                        $workload_class = 'workload-moderate';
                                        $workload_text = 'Moderate Workload';
                                    } else {
                                        $workload_class = 'workload-high';
                                        $workload_text = 'High Workload';
                                    }
                                }
                            ?>
                            <div class="tutor-card" data-role="<?php echo $tutor['role']; ?>">
                                <div class="tutor-header">
                                    <div class="tutor-header-top">
                                        <div class="tutor-avatar-section">
                                            <div class="tutor-avatar">
                                                <?php echo strtoupper(substr($tutor['first_name'], 0, 1) . substr($tutor['last_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <h3><?php echo htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']); ?></h3>
                                                <p><?php echo htmlspecialchars($tutor['staff_number']); ?></p>
                                            </div>
                                        </div>
                                        <span class="type-badge type-<?php echo $tutor['role']; ?>">
                                            <?php echo strtoupper($tutor['role']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="tutor-body">
                                    <div class="workload-indicator <?php echo $workload_class; ?>">
                                        <i class="fas fa-circle"></i> <?php echo $workload_text; ?>
                                    </div>
                                    
                                    <div class="tutor-info">
                                        <div class="info-row">
                                            <i class="fas fa-id-badge"></i>
                                            <span><strong>Student #:</strong> <?php echo htmlspecialchars($tutor['student_number'] ?? 'N/A'); ?></span>
                                        </div>
                                        <div class="info-row">
                                            <i class="fas fa-chart-line"></i>
                                            <span><strong>GPA:</strong> 
                                                <strong style="color: <?php echo ($tutor['gpa'] ?? 0) >= 3.7 ? 'var(--green)' : (($tutor['gpa'] ?? 0) >= 3.5 ? 'var(--blue)' : '#f59e0b'); ?>">
                                                    <?php echo number_format($tutor['gpa'] ?? 0, 2); ?>/4.00
                                                </strong>
                                            </span>
                                        </div>
                                        <div class="info-row">
                                            <i class="fas fa-graduation-cap"></i>
                                            <span><?php echo htmlspecialchars($tutor['qualification']); ?></span>
                                        </div>
                                        <div class="info-row">
                                            <i class="fas fa-user-graduate"></i>
                                            <span><?php echo htmlspecialchars($tutor['academic_year_level'] ?? 'N/A'); ?></span>
                                        </div>
                                        <div class="info-row">
                                            <i class="fas fa-star"></i>
                                            <span><?php echo htmlspecialchars($tutor['specialization']); ?></span>
                                        </div>
                                        <div class="info-row">
                                            <i class="fas fa-envelope"></i>
                                            <span><?php echo htmlspecialchars($tutor['email']); ?></span>
                                        </div>
                                        <div class="info-row">
                                            <i class="fas fa-phone"></i>
                                            <span><?php echo htmlspecialchars($tutor['phone']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="tutor-stats">
                                        <div class="stat-item">
                                            <div class="stat-value"><?php echo $tutor['active_assignments']; ?></div>
                                            <div class="stat-label">Active Assignments</div>
                                        </div>
                                        <div class="stat-item">
                                            <div class="stat-value"><?php echo $tutor['total_sessions']; ?></div>
                                            <div class="stat-label">Total Sessions</div>
                                        </div>
                                    </div>
                                    
                                    <div class="tutor-actions">
                                        <a href="tutor-details.php?id=<?php echo $tutor['staff_id']; ?>" class="btn-view">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <!-- Table View -->
                            <div style="overflow-x: auto;">
                                <table class="tutors-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Role</th>
                                            <th>Student #</th>
                                            <th>GPA</th>
                                            <th>Level</th>
                                            <th>Qualification</th>
                                            <th>Specialization</th>
                                            <th>Workload</th>
                                            <th>Assignments</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($tutors as $tutor): 
                                            // Determine workload status
                                            $workload_class = 'workload-available';
                                            $workload_text = 'Available';
                                            if($tutor['active_assignments'] > 0) {
                                                if($tutor['active_assignments'] <= 2) {
                                                    $workload_class = 'workload-good';
                                                    $workload_text = 'Good';
                                                } elseif($tutor['active_assignments'] <= 4) {
                                                    $workload_class = 'workload-moderate';
                                                    $workload_text = 'Moderate';
                                                } else {
                                                    $workload_class = 'workload-high';
                                                    $workload_text = 'High';
                                                }
                                            }
                                        ?>
                                        <tr>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <div class="tutor-avatar" style="width: 35px; height: 35px; font-size: 14px;">
                                                        <?php echo strtoupper(substr($tutor['first_name'], 0, 1) . substr($tutor['last_name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']); ?></strong><br>
                                                        <span style="font-size: 12px; color: #6b7280;"><?php echo htmlspecialchars($tutor['staff_number']); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="type-badge type-<?php echo $tutor['role']; ?>">
                                                    <?php echo strtoupper($tutor['role']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($tutor['student_number'] ?? 'N/A'); ?></td>
                                            <td>
                                                <strong style="color: <?php echo ($tutor['gpa'] ?? 0) >= 3.7 ? 'var(--green)' : (($tutor['gpa'] ?? 0) >= 3.5 ? 'var(--blue)' : '#f59e0b'); ?>">
                                                    <?php echo number_format($tutor['gpa'] ?? 0, 2); ?>
                                                </strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($tutor['academic_year_level'] ?? 'N/A'); ?></td>
                                            <td style="font-size: 12px;"><?php echo htmlspecialchars($tutor['qualification']); ?></td>
                                            <td style="font-size: 12px;"><?php echo htmlspecialchars($tutor['specialization']); ?></td>
                                            <td>
                                                <span class="workload-indicator <?php echo $workload_class; ?>" style="padding: 4px 10px; font-size: 11px;">
                                                    <?php echo $workload_text; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong style="font-size: 16px; color: var(--blue);"><?php echo $tutor['active_assignments']; ?></strong>
                                                <span style="font-size: 11px; color: #6b7280;">/<?php echo $tutor['total_sessions']; ?></span>
                                            </td>
                                            <td>
                                                <a href="tutor-details.php?id=<?php echo $tutor['staff_id']; ?>" class="btn-view" style="padding: 6px 12px; font-size: 12px;">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-search"></i>
                            <h3>No Tutors/PALs Found</h3>
                            <p>
                                <?php if($search): ?>
                                    No tutors or PALs match your search criteria.
                                <?php else: ?>
                                    No tutors or PALs are currently available.
                                <?php endif; ?>
                            </p>
                            <?php if($search): ?>
                                <a href="?type=<?php echo $type_filter; ?>" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> Clear Search
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
</body>
</html>
