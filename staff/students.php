<?php
session_start();
if(!isset($_SESSION['staff_id'])) {
    header('Location: ../staff-login.php');
    exit();
}

$role = $_SESSION['role'] ?? 'staff';

// Allow counselors, advisors, tutors, and PALs
$is_counselor = in_array($role, ['counsellor', 'academic_advisor', 'career_counsellor', 'financial_advisor']);
$is_tutor = in_array($role, ['tutor', 'pal']);

if(!$is_counselor && !$is_tutor) {
    header('Location: index.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

$staff_id = $_SESSION['staff_id'];

// Get search filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Different queries based on role
if($is_tutor) {
    // For tutors/PALs: Get ALL students enrolled in modules they're assigned to
    $query = "
        SELECT DISTINCT
            st.student_id, st.first_name, st.last_name, st.email, st.phone, st.reading_score,
            GROUP_CONCAT(DISTINCT m.subject_code ORDER BY m.subject_code SEPARATOR ', ') as modules,
            GROUP_CONCAT(DISTINCT m.subject_name ORDER BY m.subject_name SEPARATOR ', ') as module_names
        FROM students st
        INNER JOIN student_modules sm ON st.student_id = sm.student_id
        INNER JOIN modules m ON sm.module_id = m.module_id
        INNER JOIN at_risk_modules arm ON m.module_id = arm.module_id
        INNER JOIN tutor_assignments ta ON arm.risk_id = ta.risk_module_id
        WHERE ta.tutor_id = ? AND ta.status = 'active' AND sm.status = 'active'
    ";
    $params = [$staff_id];
} else {
    // For counselors/advisors: Get students who have appointments
    $query = "
        SELECT DISTINCT
            st.student_id, st.first_name, st.last_name, st.email, st.phone, st.reading_score,
            COUNT(DISTINCT b.booking_id) as total_appointments,
            COUNT(DISTINCT CASE WHEN b.status = 'completed' THEN b.booking_id END) as completed_appointments,
            COUNT(DISTINCT CASE WHEN b.status = 'confirmed' THEN b.booking_id END) as upcoming_appointments,
            MAX(b.booking_date) as last_appointment_date,
            GROUP_CONCAT(DISTINCT s.service_name ORDER BY s.service_name SEPARATOR ', ') as services
        FROM students st
        INNER JOIN bookings b ON st.student_id = b.student_id
        INNER JOIN services s ON b.service_id = s.service_id
        WHERE b.staff_id = ?
    ";
    $params = [$staff_id];
}

if($search != '') {
    $query .= " AND (st.first_name LIKE ? OR st.last_name LIKE ? OR st.student_id LIKE ? OR st.email LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$query .= " GROUP BY st.student_id ORDER BY st.last_name, st.first_name";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

// Get statistics based on role
if($is_tutor) {
    $stats = $conn->prepare("
        SELECT 
            COUNT(DISTINCT st.student_id) as total_students
        FROM students st
        INNER JOIN student_modules sm ON st.student_id = sm.student_id
        INNER JOIN modules m ON sm.module_id = m.module_id
        INNER JOIN at_risk_modules arm ON m.module_id = arm.module_id
        INNER JOIN tutor_assignments ta ON arm.risk_id = ta.risk_module_id
        WHERE ta.tutor_id = ? AND ta.status = 'active' AND sm.status = 'active'
    ");
    $stats->execute([$staff_id]);
    $statistics = $stats->fetch();
    $statistics['total_sessions'] = 0;
    $statistics['total_attended'] = 0;
    $attendance_rate = 0;
} else {
    $stats = $conn->prepare("
        SELECT 
            COUNT(DISTINCT st.student_id) as total_students,
            COUNT(DISTINCT b.booking_id) as total_appointments,
            COUNT(DISTINCT CASE WHEN b.status = 'completed' THEN b.booking_id END) as completed_appointments
        FROM students st
        INNER JOIN bookings b ON st.student_id = b.student_id
        WHERE b.staff_id = ?
    ");
    $stats->execute([$staff_id]);
    $statistics = $stats->fetch();

    $completion_rate = $statistics['total_appointments'] > 0 ? 
        ($statistics['completed_appointments'] / $statistics['total_appointments']) * 100 : 0;
}
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
                <!-- Hero Section -->
                <div class="hero-section">
                    <div class="hero-content">
                        <h1><?php echo $is_tutor ? 'My Students' : 'Student Management'; ?></h1>
                        <p><?php echo $is_tutor ? 'View students enrolled in your assigned modules' : 'View, manage, and track all your students in one place'; ?></p>
                        <div class="hero-stats">
                            <div class="hero-stat">
                                <i class="fas fa-user-graduate"></i>
                                <span><?php echo $statistics['total_students']; ?> Students</span>
                            </div>
                            <?php if(!$is_tutor): ?>
                            <div class="hero-stat">
                                <i class="fas fa-clipboard-check"></i>
                                <span><?php echo $statistics['total_appointments']; ?> Appointments</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-check-circle"></i>
                                <span><?php echo $statistics['completed_appointments']; ?> Completed</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-percentage"></i>
                                <span><?php echo number_format($completion_rate, 1); ?>% Rate</span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="page-header">
                    <h1><i class="fas fa-users"></i> Students</h1>
                    <p><?php echo $is_tutor ? 'Students enrolled in your modules' : 'View students with appointments'; ?></p>
                </div>

                <!-- Search -->
                <div class="section">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <form method="GET" style="display: flex; gap: 15px; flex: 1;">
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
                        
                        <!-- View Toggle -->
                        <div style="display: flex; gap: 10px; margin-left: 20px;">
                            <button onclick="toggleView('card')" id="cardViewBtn" class="btn btn-secondary">
                                <i class="fas fa-th-large"></i> Cards
                            </button>
                            <button onclick="toggleView('table')" id="tableViewBtn" class="btn btn-secondary">
                                <i class="fas fa-table"></i> Table
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Card View -->
                <div class="section" id="cardView">
                    <?php if(count($students) > 0): ?>
                        <?php foreach($students as $student): 
                            // For tutors/PALs: no session tracking, just enrollment
                            if($is_tutor) {
                                $total_count = 0;
                                $completed_count = 0;
                                $upcoming_count = 0;
                                $completion_rate_student = 0;
                            } else {
                                // For counselors: track appointments
                                $completion_rate_student = $student['total_appointments'] > 0 ? 
                                    ($student['completed_appointments'] / $student['total_appointments']) * 100 : 0;
                                $total_count = $student['total_appointments'];
                                $completed_count = $student['completed_appointments'];
                                $upcoming_count = $student['upcoming_appointments'];
                            }
                            
                            // Use reading_score from database
                            $success_score = 0;
                            if(isset($student['reading_score']) && $student['reading_score'] !== null && $student['reading_score'] > 0) {
                                $success_score = round($student['reading_score']);
                            } elseif(!$is_tutor && $total_count > 0) {
                                $engagement_score = min(100, ($total_count / 10) * 100);
                                
                                $success_score = max(0, min(100, 
                                    ($completion_rate_student * 0.6) + 
                                    ($engagement_score * 0.4)
                                ));
                                $success_score = round($success_score);
                            }
                            
                            // Determine score color and label
                            if($success_score >= 80) {
                                $score_color = '#10b981'; // Green
                                $score_label = 'Excellent';
                            } elseif($success_score >= 60) {
                                $score_color = '#2563eb'; // Blue
                                $score_label = 'Good';
                            } elseif($success_score >= 40) {
                                $score_color = '#f59e0b'; // Orange
                                $score_label = 'Fair';
                            } else {
                                $score_color = '#ef4444'; // Red
                                $score_label = 'Needs Support';
                            }
                            
                            // Only calculate completion class for counselors/advisors
                            if(!$is_tutor) {
                                $completion_class = 'attendance-excellent';
                                $completion_label = 'Excellent';
                                if($completion_rate_student < 90) {
                                    $completion_class = 'attendance-good';
                                    $completion_label = 'Good';
                                }
                                if($completion_rate_student < 70) {
                                    $completion_class = 'attendance-fair';
                                    $completion_label = 'Fair';
                                }
                                if($completion_rate_student < 50) {
                                    $completion_class = 'attendance-poor';
                                    $completion_label = 'Poor';
                                }
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
                                        <?php if($student['phone']): ?>
                                        <div class="detail-item">
                                            <i class="fas fa-phone"></i>
                                            <span><?php echo htmlspecialchars($student['phone']); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if(!$is_tutor): ?>
                                        <?php 
                                        $last_date = $student['last_appointment_date'] ?? null;
                                        if($last_date): 
                                        ?>
                                        <div class="detail-item">
                                            <i class="fas fa-calendar"></i>
                                            <span>Last: <?php echo date('M j, Y', strtotime($last_date)); ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if($student['modules'] ?? $student['services']): ?>
                                    <div class="modules-list">
                                        <?php 
                                        $items = explode(', ', $is_tutor ? ($student['modules'] ?? '') : ($student['services'] ?? ''));
                                        foreach($items as $item): 
                                            if($item):
                                        ?>
                                            <span class="module-tag"><?php echo htmlspecialchars($item); ?></span>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="student-stats">
                                        <?php if(!$is_tutor): ?>
                                        <div class="stat-box">
                                            <div class="stat-box-value"><?php echo $total_count; ?></div>
                                            <div class="stat-box-label">Total Appointments</div>
                                        </div>
                                        <div class="stat-box">
                                            <div class="stat-box-value"><?php echo $completed_count; ?></div>
                                            <div class="stat-box-label">Completed</div>
                                        </div>
                                        <div class="stat-box">
                                            <div class="stat-box-value"><?php echo $upcoming_count; ?></div>
                                            <div class="stat-box-label">Upcoming</div>
                                        </div>
                                        <div class="stat-box">
                                            <div class="stat-box-value"><?php echo number_format($completion_rate_student, 0); ?>%</div>
                                            <div class="stat-box-label">Completion Rate</div>
                                        </div>
                                        <?php endif; ?>
                                        <div class="stat-box">
                                            <div class="stat-box-value" style="color: <?php echo $score_color; ?>">
                                                <?php echo $success_score; ?>
                                            </div>
                                            <div class="stat-box-label">Readiness Score</div>
                                        </div>
                                        <?php if(!$is_tutor): ?>
                                        <div class="stat-box">
                                            <span class="attendance-badge <?php echo $completion_class; ?>">
                                                <?php echo $completion_label; ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <?php if($completion_rate_student < 70): ?>
                                        <div style="padding: 10px 15px; background: #fee2e2; border-radius: 8px; margin-top: 10px; border-left: 4px solid #dc2626;">
                                            <p style="margin: 0; font-size: 13px; color: #991b1b;">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                <strong>Low Completion Alert:</strong> This student may need additional support or follow-up.
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div style="display: flex; flex-direction: column; gap: 10px;">
                                    <a href="student-profile.php?id=<?php echo $student['student_id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> View Profile
                                    </a>
                                    <?php if(!$is_tutor): ?>
                                        <a href="student-appointments.php?student_id=<?php echo $student['student_id']; ?>" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-calendar"></i> View Appointments
                                        </a>
                                    <?php endif; ?>
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
                                    <?php echo $is_tutor ? 'No students enrolled in your assigned modules yet.' : 'No students have appointments with you yet.'; ?>
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

                <!-- Table View -->
                <div class="section" id="tableView" style="display: none;">
                    <?php if(count($students) > 0): ?>
                        <div style="background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                            <table style="width: 100%; border-collapse: collapse;">
                                <thead style="background: #f9fafb; border-bottom: 2px solid #e5e7eb;">
                                    <tr>
                                        <th style="padding: 15px; text-align: left; font-size: 13px; font-weight: 700; color: var(--dark); text-transform: uppercase;">Student</th>
                                        <th style="padding: 15px; text-align: left; font-size: 13px; font-weight: 700; color: var(--dark); text-transform: uppercase;"><?php echo $is_tutor ? 'Modules' : 'Services'; ?></th>
                                        <?php if(!$is_tutor): ?>
                                        <th style="padding: 15px; text-align: center; font-size: 13px; font-weight: 700; color: var(--dark); text-transform: uppercase;">Total</th>
                                        <th style="padding: 15px; text-align: center; font-size: 13px; font-weight: 700; color: var(--dark); text-transform: uppercase;">Completed</th>
                                        <th style="padding: 15px; text-align: center; font-size: 13px; font-weight: 700; color: var(--dark); text-transform: uppercase;">Upcoming</th>
                                        <th style="padding: 15px; text-align: center; font-size: 13px; font-weight: 700; color: var(--dark); text-transform: uppercase;">Rate</th>
                                        <?php endif; ?>
                                        <th style="padding: 15px; text-align: center; font-size: 13px; font-weight: 700; color: var(--dark); text-transform: uppercase;">Readiness</th>
                                        <th style="padding: 15px; text-align: center; font-size: 13px; font-weight: 700; color: var(--dark); text-transform: uppercase;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($students as $student): 
                                        // Calculate rates
                                        if($is_tutor) {
                                            $total_count = 0;
                                            $completed_count = 0;
                                            $upcoming_count = 0;
                                            $completion_rate_student = 0;
                                        } else {
                                            $completion_rate_student = $student['total_appointments'] > 0 ? 
                                                ($student['completed_appointments'] / $student['total_appointments']) * 100 : 0;
                                            $total_count = $student['total_appointments'];
                                            $completed_count = $student['completed_appointments'];
                                            $upcoming_count = $student['upcoming_appointments'];
                                        }
                                        
                                        $success_score = 0;
                                        if(isset($student['reading_score']) && $student['reading_score'] !== null && $student['reading_score'] > 0) {
                                            $success_score = round($student['reading_score']);
                                        }
                                        
                                        $score_color = $success_score >= 80 ? '#10b981' : ($success_score >= 60 ? '#2563eb' : ($success_score >= 40 ? '#f59e0b' : '#ef4444'));
                                    ?>
                                    <tr style="border-bottom: 1px solid #f3f4f6; transition: background 0.2s;" onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='white'">
                                        <td style="padding: 15px;">
                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <div style="width: 40px; height: 40px; border-radius: 50%; background: var(--blue); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">
                                                    <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600; color: var(--dark);"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></div>
                                                    <div style="font-size: 12px; color: #9ca3af;"><?php echo htmlspecialchars($student['student_id']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="padding: 15px;">
                                            <?php 
                                            $items = $is_tutor ? ($student['modules'] ?? 'N/A') : ($student['services'] ?? 'N/A');
                                            echo htmlspecialchars($items);
                                            ?>
                                        </td>
                                        <?php if(!$is_tutor): ?>
                                        <td style="padding: 15px; text-align: center; font-weight: 600;"><?php echo $total_count; ?></td>
                                        <td style="padding: 15px; text-align: center; font-weight: 600; color: var(--green);"><?php echo $completed_count; ?></td>
                                        <td style="padding: 15px; text-align: center; font-weight: 600; color: #f59e0b;"><?php echo $upcoming_count; ?></td>
                                        <td style="padding: 15px; text-align: center; font-weight: 600;"><?php echo number_format($completion_rate_student, 0); ?>%</td>
                                        <?php endif; ?>
                                        <td style="padding: 15px; text-align: center;">
                                            <span style="display: inline-block; padding: 4px 12px; border-radius: 12px; font-weight: 700; font-size: 14px; color: <?php echo $score_color; ?>; background: <?php echo $score_color; ?>20;">
                                                <?php echo $success_score; ?>
                                            </span>
                                        </td>
                                        <td style="padding: 15px; text-align: center;">
                                            <div style="display: flex; gap: 8px; justify-content: center;">
                                                <a href="student-profile.php?id=<?php echo $student['student_id']; ?>" class="btn btn-primary btn-sm" style="padding: 6px 12px; font-size: 12px;">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if(!$is_tutor): ?>
                                                    <a href="student-appointments.php?student_id=<?php echo $student['student_id']; ?>" class="btn btn-secondary btn-sm" style="padding: 6px 12px; font-size: 12px;">
                                                        <i class="fas fa-calendar"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-user-graduate"></i>
                            <h3>No Students Found</h3>
                            <p>
                                <?php if($search): ?>
                                    No students match your search criteria.
                                <?php else: ?>
                                    <?php echo $is_tutor ? 'No students enrolled in your assigned modules yet.' : 'No students have appointments with you yet.'; ?>
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
    <script>
        // View toggle functionality
        function toggleView(view) {
            const cardView = document.getElementById('cardView');
            const tableView = document.getElementById('tableView');
            const cardBtn = document.getElementById('cardViewBtn');
            const tableBtn = document.getElementById('tableViewBtn');
            
            if(view === 'card') {
                cardView.style.display = 'block';
                tableView.style.display = 'none';
                cardBtn.classList.add('btn-primary');
                cardBtn.classList.remove('btn-secondary');
                tableBtn.classList.remove('btn-primary');
                tableBtn.classList.add('btn-secondary');
                localStorage.setItem('studentsView', 'card');
            } else {
                cardView.style.display = 'none';
                tableView.style.display = 'block';
                tableBtn.classList.add('btn-primary');
                tableBtn.classList.remove('btn-secondary');
                cardBtn.classList.remove('btn-primary');
                cardBtn.classList.add('btn-secondary');
                localStorage.setItem('studentsView', 'table');
            }
        }
        
        // Load saved view preference
        document.addEventListener('DOMContentLoaded', function() {
            const savedView = localStorage.getItem('studentsView') || 'card';
            toggleView(savedView);
        });
    </script>
</body>
</html>
