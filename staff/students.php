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

// Get search filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get all students who have registered for this tutor's sessions
$query = "
    SELECT DISTINCT
        st.student_id, st.first_name, st.last_name, st.email, st.phone,
        COUNT(DISTINCT sr.registration_id) as total_registrations,
        COUNT(DISTINCT CASE WHEN sr.attended = TRUE THEN sr.registration_id END) as attended_sessions,
        COUNT(DISTINCT ts.session_id) as total_sessions_available,
        GROUP_CONCAT(DISTINCT m.subject_code ORDER BY m.subject_code SEPARATOR ', ') as modules
    FROM students st
    INNER JOIN session_registrations sr ON st.student_id = sr.student_id
    INNER JOIN tutor_sessions ts ON sr.session_id = ts.session_id
    INNER JOIN tutor_assignments ta ON ts.assignment_id = ta.assignment_id
    INNER JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
    INNER JOIN modules m ON arm.module_id = m.module_id
    WHERE ta.tutor_id = ?
";

$params = [$tutor_id];

if($search != '') {
    $query .= " AND (st.first_name LIKE ? OR st.last_name LIKE ? OR st.student_id LIKE ? OR st.email LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$query .= " GROUP BY st.student_id ORDER BY st.last_name, st.first_name";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Get statistics
$stats = $conn->prepare("
    SELECT 
        COUNT(DISTINCT st.student_id) as total_students,
        COUNT(DISTINCT sr.registration_id) as total_registrations,
        COUNT(DISTINCT CASE WHEN sr.attended = TRUE THEN sr.registration_id END) as total_attended
    FROM students st
    INNER JOIN session_registrations sr ON st.student_id = sr.student_id
    INNER JOIN tutor_sessions ts ON sr.session_id = ts.session_id
    INNER JOIN tutor_assignments ta ON ts.assignment_id = ta.assignment_id
    WHERE ta.tutor_id = ?
");
$stats->execute([$tutor_id]);
$statistics = $stats->fetch();

$avg_attendance = $statistics['total_registrations'] > 0 ? 
    ($statistics['total_attended'] / $statistics['total_registrations']) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .student-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border: 2px solid #e5e7eb;
            transition: all 0.3s;
            display: flex;
            gap: 20px;
            align-items: center;
        }
        
        .student-card:hover {
            border-color: var(--blue);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        .student-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--blue);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            flex-shrink: 0;
        }
        
        .student-info {
            flex: 1;
        }
        
        .student-name {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
            margin: 0 0 5px 0;
        }
        
        .student-details {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 8px;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #6b7280;
        }
        
        .detail-item i {
            color: var(--blue);
        }
        
        .student-stats {
            display: flex;
            gap: 20px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
            margin-top: 10px;
        }
        
        .stat-box {
            text-align: center;
        }
        
        .stat-box-value {
            font-size: 20px;
            font-weight: 700;
            color: var(--blue);
        }
        
        .stat-box-label {
            font-size: 11px;
            color: #6b7280;
            margin-top: 2px;
        }
        
        .attendance-badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
        }
        
        .attendance-excellent {
            background: #d1fae5;
            color: #10b981;
        }
        
        .attendance-good {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .attendance-fair {
            background: #fef3c7;
            color: #f59e0b;
        }
        
        .attendance-poor {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .modules-list {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 8px;
        }
        
        .module-tag {
            padding: 4px 10px;
            background: #eff6ff;
            color: var(--blue);
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
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
                    <h1><i class="fas fa-users"></i> Students</h1>
                    <p>View students registered for your sessions</p>
                </div>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $statistics['total_students']; ?></h3>
                            <p>Total Students</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $statistics['total_registrations']; ?></h3>
                            <p>Total Registrations</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $statistics['total_attended']; ?></h3>
                            <p>Sessions Attended</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo number_format($avg_attendance, 1); ?>%</h3>
                            <p>Avg Attendance Rate</p>
                        </div>
                    </div>
                </div>

                <!-- Search -->
                <div class="section">
                    <form method="GET" style="display: flex; gap: 15px; margin-bottom: 20px;">
                        <input type="text" name="search" class="search-input" 
                               placeholder="Search by name, student ID, or email..." 
                               value="<?php echo htmlspecialchars($search); ?>"
                               style="flex: 1; padding: 12px 15px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if($search): ?>
                            <a href="students.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- Students List -->
                <div class="section">
                    <?php if(count($students) > 0): ?>
                        <?php foreach($students as $student): 
                            $attendance_rate = $student['total_registrations'] > 0 ? 
                                ($student['attended_sessions'] / $student['total_registrations']) * 100 : 0;
                            
                            $attendance_class = 'attendance-excellent';
                            $attendance_label = 'Excellent';
                            if($attendance_rate < 90) {
                                $attendance_class = 'attendance-good';
                                $attendance_label = 'Good';
                            }
                            if($attendance_rate < 70) {
                                $attendance_class = 'attendance-fair';
                                $attendance_label = 'Fair';
                            }
                            if($attendance_rate < 50) {
                                $attendance_class = 'attendance-poor';
                                $attendance_label = 'Poor';
                            }
                        ?>
                            <div class="student-card">
                                <div class="student-avatar">
                                    <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                </div>
                                
                                <div class="student-info">
                                    <h3 class="student-name">
                                        <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                    </h3>
                                    
                                    <div class="student-details">
                                        <div class="detail-item">
                                            <i class="fas fa-id-card"></i>
                                            <span><?php echo htmlspecialchars($student['student_id']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-envelope"></i>
                                            <span><?php echo htmlspecialchars($student['email']); ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <i class="fas fa-phone"></i>
                                            <span><?php echo htmlspecialchars($student['phone']); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="modules-list">
                                        <?php 
                                        $modules = explode(', ', $student['modules']);
                                        foreach($modules as $module): 
                                        ?>
                                            <span class="module-tag"><?php echo htmlspecialchars($module); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="student-stats">
                                        <div class="stat-box">
                                            <div class="stat-box-value"><?php echo $student['total_registrations']; ?></div>
                                            <div class="stat-box-label">Sessions Registered</div>
                                        </div>
                                        <div class="stat-box">
                                            <div class="stat-box-value"><?php echo $student['attended_sessions']; ?></div>
                                            <div class="stat-box-label">Sessions Attended</div>
                                        </div>
                                        <div class="stat-box">
                                            <div class="stat-box-value"><?php echo number_format($attendance_rate, 0); ?>%</div>
                                            <div class="stat-box-label">Attendance Rate</div>
                                        </div>
                                        <div class="stat-box">
                                            <span class="attendance-badge <?php echo $attendance_class; ?>">
                                                <?php echo $attendance_label; ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <?php if($attendance_rate < 70): ?>
                                        <div style="padding: 10px 15px; background: #fee2e2; border-radius: 8px; margin-top: 10px; border-left: 4px solid #dc2626;">
                                            <p style="margin: 0; font-size: 13px; color: #991b1b;">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                <strong>Low Attendance Alert:</strong> This student may need additional support or follow-up.
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div style="display: flex; flex-direction: column; gap: 10px;">
                                    <a href="student-profile.php?id=<?php echo $student['student_id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> View Profile
                                    </a>
                                    <a href="student-sessions.php?id=<?php echo $student['student_id']; ?>" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-calendar"></i> Sessions
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-user-graduate"></i>
                            <h3>No Students Found</h3>
                            <p>
                                <?php if($search): ?>
                                    No students match your search criteria.
                                <?php else: ?>
                                    No students have registered for your sessions yet.
                                <?php endif; ?>
                            </p>
                            <?php if($search): ?>
                                <a href="students.php" class="btn btn-secondary">
                                    <i class="fas fa-list"></i> View All Students
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
