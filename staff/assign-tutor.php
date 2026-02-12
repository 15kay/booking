<?php
session_start();
if(!isset($_SESSION['staff_id']) || $_SESSION['role'] != 'coordinator') {
    header('Location: ../staff-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

$module_id = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;
$academic_year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Get module details
$stmt = $conn->prepare("
    SELECT * FROM modules WHERE module_id = ?
");
$stmt->execute([$module_id]);
$module = $stmt->fetch();

if(!$module) {
    header('Location: browse-modules.php?error=Module not found');
    exit();
}

// Check if module is already flagged as at-risk, if not create entry
$risk_stmt = $conn->prepare("
    SELECT risk_id FROM at_risk_modules 
    WHERE module_id = ? AND academic_year = ? AND status = 'active'
");
$risk_stmt->execute([$module_id, $academic_year]);
$risk_data = $risk_stmt->fetch();

if(!$risk_data) {
    // Auto-flag module as at-risk
    $insert_stmt = $conn->prepare("
        INSERT INTO at_risk_modules 
        (module_id, academic_year, semester, campus, faculty, identified_date, 
         at_risk_students, reason, status)
        VALUES (?, ?, 1, ?, ?, NOW(), ?, 'Low pass rate - requires tutor support', 'active')
    ");
    $insert_stmt->execute([
        $module_id, 
        $academic_year, 
        $module['campus'],
        $module['faculty'],
        $module['headcount']
    ]);
    $risk_id = $conn->lastInsertId();
} else {
    $risk_id = $risk_data['risk_id'];
}

// Calculate recommended tutors and max students per session
$recommended_tutors = 0;
$max_students_per_session = 20; // default

if($module['risk_category'] == 'High Risk') {
    $recommended_tutors = ceil($module['headcount'] / 15);
    $max_students_per_session = 15;
} elseif($module['risk_category'] == 'Moderate Risk') {
    $recommended_tutors = ceil($module['headcount'] / 20);
    $max_students_per_session = 20;
} elseif($module['risk_category'] == 'Low Risk') {
    $recommended_tutors = ceil($module['headcount'] / 30);
    $max_students_per_session = 30;
} else {
    $recommended_tutors = 1;
    $max_students_per_session = 40;
}

// Get currently assigned tutors count
$assigned_stmt = $conn->prepare("
    SELECT COUNT(*) as count FROM tutor_assignments 
    WHERE risk_module_id = ? AND status = 'active'
");
$assigned_stmt->execute([$risk_id]);
$assigned_count = $assigned_stmt->fetch()['count'];

// Get available tutors/PALs - prioritize those from same faculty
// Note: In real system, you'd match by subject area, qualifications, etc.
$tutors_stmt = $conn->prepare("
    SELECT 
        s.staff_id, s.staff_number, s.student_number, s.first_name, s.last_name, 
        s.email, s.phone, s.role, s.specialization, s.qualification, s.gpa, s.academic_year_level,
        COUNT(ta.assignment_id) as current_assignments,
        CASE WHEN s.specialization LIKE ? THEN 1 ELSE 0 END as subject_match
    FROM staff s
    LEFT JOIN tutor_assignments ta ON s.staff_id = ta.tutor_id AND ta.status = 'active'
    WHERE s.role IN ('tutor', 'pal') AND s.status = 'active'
    GROUP BY s.staff_id
    ORDER BY subject_match DESC, s.role ASC, current_assignments ASC, s.last_name ASC
");
$subject_search = '%' . $module['subject_area'] . '%';
$tutors_stmt->execute([$subject_search]);
$tutors = $tutors_stmt->fetchAll();

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tutor_ids = isset($_POST['tutor_ids']) ? explode(',', $_POST['tutor_ids']) : [];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $max_students = intval($_POST['max_students']);
    $session_frequency = trim($_POST['session_frequency']);
    $location = trim($_POST['location']);
    $notes = trim($_POST['notes']);
    
    try {
        $conn->beginTransaction();
        
        $stmt = $conn->prepare("
            INSERT INTO tutor_assignments 
            (risk_module_id, tutor_id, tutor_type, assigned_by, assignment_date, 
             start_date, end_date, max_students, session_frequency, location, notes, status)
            VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, 'active')
        ");
        
        $assigned_count = 0;
        foreach($tutor_ids as $tutor_id) {
            $tutor_id = intval($tutor_id);
            if($tutor_id > 0) {
                // Get tutor type
                $type_stmt = $conn->prepare("SELECT role FROM staff WHERE staff_id = ?");
                $type_stmt->execute([$tutor_id]);
                $tutor_type = $type_stmt->fetch()['role'];
                
                $stmt->execute([
                    $risk_id, $tutor_id, $tutor_type, $_SESSION['staff_id'],
                    $start_date, $end_date, $max_students, $session_frequency, $location, $notes
                ]);
                $assigned_count++;
            }
        }
        
        $conn->commit();
        
        header('Location: browse-modules.php?success=' . $assigned_count . ' tutor(s) assigned successfully');
        exit();
        
    } catch(PDOException $e) {
        $conn->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Tutor - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .form-container {
            max-width: 1100px;
            margin: 0 auto;
        }
        
        .module-info-card {
            background: linear-gradient(135deg, #000000 0%, #1d4ed8 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .module-info-card h2 {
            margin: 0 0 15px 0;
            font-size: 24px;
        }
        
        .module-stats {
            display: flex;
            gap: 30px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .module-stat {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .module-stat i {
            font-size: 20px;
            opacity: 0.9;
        }
        
        .recommendation-box {
            background: #eff6ff;
            border-left: 4px solid var(--blue);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .recommendation-box h3 {
            margin: 0 0 10px 0;
            color: var(--blue);
            font-size: 18px;
        }
        
        .tutor-list {
            margin-bottom: 30px;
        }
        
        .tutor-item {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 15px;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .tutor-item.recommended {
            border-color: var(--green);
            background: #f0fdf4;
        }
        
        .tutor-item.selected {
            border-color: var(--blue);
            background: #eff6ff;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }
        
        .tutor-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .tutor-header:hover {
            background: rgba(0, 0, 0, 0.02);
        }
        
        .tutor-main-info {
            display: flex;
            align-items: center;
            gap: 20px;
            flex: 1;
        }
        
        .tutor-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--blue);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 600;
            flex-shrink: 0;
        }
        
        .tutor-item.recommended .tutor-avatar {
            background: var(--green);
        }
        
        .tutor-basic-info {
            flex: 1;
        }
        
        .tutor-basic-info h4 {
            margin: 0 0 5px 0;
            font-size: 18px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .tutor-basic-info p {
            margin: 0 0 8px 0;
            font-size: 13px;
            color: #6b7280;
        }
        
        .tutor-quick-info {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 8px;
        }
        
        .quick-info-item {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #4b5563;
        }
        
        .quick-info-item i {
            color: var(--blue);
        }
        
        .tutor-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .expand-btn {
            background: none;
            border: none;
            color: var(--blue);
            cursor: pointer;
            padding: 8px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s;
        }
        
        .expand-btn:hover {
            color: #1e40af;
        }
        
        .select-btn {
            padding: 10px 20px;
            border: 2px solid var(--blue);
            background: white;
            color: var(--blue);
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .select-btn:hover {
            background: var(--blue);
            color: white;
        }
        
        .tutor-item.selected .select-btn {
            background: var(--blue);
            color: white;
        }
        
        .select-checkbox {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--blue);
        }
        
        .selection-counter {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--blue);
            color: white;
            padding: 15px 25px;
            border-radius: 50px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            font-weight: 600;
            z-index: 1000;
            display: none;
        }
        
        .selection-counter.show {
            display: block;
        }
        
        .tutor-details {
            padding: 0 20px 20px 20px;
            display: none;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }
        
        .tutor-details.expanded {
            display: block;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .detail-section {
            background: white;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        
        .detail-section h5 {
            margin: 0 0 12px 0;
            font-size: 14px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .detail-section h5 i {
            color: var(--blue);
        }
        
        .detail-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 10px;
            font-size: 13px;
            color: #4b5563;
        }
        
        .detail-item:last-child {
            margin-bottom: 0;
        }
        
        .detail-item i {
            color: var(--blue);
            width: 16px;
            margin-top: 2px;
        }
        
        .detail-item strong {
            color: var(--dark);
        }
        
        .form-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark);
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group label i {
            margin-right: 8px;
            color: var(--blue);
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
            font-family: inherit;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #dc2626;
            border-left: 4px solid #dc2626;
        }
        
        .role-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .role-tutor {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .role-pal {
            background: #fef3c7;
            color: #92400e;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <div class="form-container">
                    <div style="margin-bottom: 20px;">
                        <a href="browse-modules.php" class="btn-link">
                            <i class="fas fa-arrow-left"></i> Back to Browse Modules
                        </a>
                    </div>

                    <div class="page-header">
                        <h1><i class="fas fa-user-plus"></i> Assign Tutor/PAL</h1>
                        <p>Select qualified tutors or PALs to support students in this module</p>
                    </div>

                    <?php if(isset($error)): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <div class="module-info-card">
                        <h2><?php echo htmlspecialchars($module['subject_code']); ?> - <?php echo htmlspecialchars($module['subject_name']); ?></h2>
                        <div class="module-stats">
                            <div class="module-stat">
                                <i class="fas fa-building"></i>
                                <span><?php echo htmlspecialchars($module['faculty']); ?></span>
                            </div>
                            <div class="module-stat">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo $module['academic_year']; ?></span>
                            </div>
                            <div class="module-stat">
                                <i class="fas fa-users"></i>
                                <span><?php echo $module['headcount']; ?> Students</span>
                            </div>
                            <div class="module-stat">
                                <i class="fas fa-percentage"></i>
                                <span><?php echo number_format($module['subject_pass_rate'] * 100, 1); ?>% Pass Rate</span>
                            </div>
                            <div class="module-stat">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span><?php echo $module['risk_category']; ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="recommendation-box">
                        <h3><i class="fas fa-lightbulb"></i> Tutor Assignment Recommendation</h3>
                        <p style="margin: 0; font-size: 15px; color: #374151;">
                            Based on the <strong><?php echo $module['risk_category']; ?></strong> status and <strong><?php echo $module['headcount']; ?> students</strong>, 
                            we recommend assigning <strong><?php echo $recommended_tutors; ?> tutor<?php echo $recommended_tutors > 1 ? 's' : ''; ?>/PAL<?php echo $recommended_tutors > 1 ? 's' : ''; ?></strong> 
                            with a maximum of <strong><?php echo $max_students_per_session; ?> students per session</strong>.
                            <?php if($assigned_count > 0): ?>
                                <br><span style="color: <?php echo $assigned_count >= $recommended_tutors ? 'var(--green)' : '#f59e0b'; ?>; font-weight: 600; margin-top: 8px; display: inline-block;">
                                    <i class="fas fa-info-circle"></i> Currently assigned: <?php echo $assigned_count; ?> tutor<?php echo $assigned_count > 1 ? 's' : ''; ?>
                                    <?php if($assigned_count < $recommended_tutors): ?>
                                        (<?php echo $recommended_tutors - $assigned_count; ?> more recommended)
                                    <?php else: ?>
                                        <i class="fas fa-check-circle"></i> Target reached!
                                    <?php endif; ?>
                                </span>
                            <?php else: ?>
                                <br><span style="color: #6b7280; font-weight: 500; margin-top: 8px; display: inline-block;">
                                    <i class="fas fa-info-circle"></i> No tutors assigned yet. Select one below to get started.
                                </span>
                            <?php endif; ?>
                        </p>
                    </div>

                    <form method="POST" action="" id="assignForm">
                        <input type="hidden" name="tutor_ids" id="selectedTutorIds" required>
                        <input type="hidden" name="tutor_count" id="selectedTutorCount" value="0">
                        
                        <div class="section">
                            <h2 class="section-title">
                                <i class="fas fa-user-friends"></i> Select Tutors/PALs 
                                <span style="font-weight: normal; font-size: 14px; color: #6b7280;">
                                    (Select up to <?php echo $recommended_tutors; ?> tutors)
                                </span>
                            </h2>
                            <p style="color: #6b7280; margin-bottom: 20px;">
                                Tutors marked as "RECOMMENDED" have specializations matching this module's subject area. 
                                Check the boxes to select multiple tutors (maximum: <?php echo $recommended_tutors; ?>).
                            </p>
                            
                            <div class="tutor-list">
                                <?php 
                                $tutor_count = 0;
                                foreach($tutors as $tutor): 
                                    $tutor_count++;
                                    if($tutor_count > 10) break; // Show only first 10 tutors
                                ?>
                                    <div class="tutor-item <?php echo $tutor['subject_match'] ? 'recommended' : ''; ?>" 
                                         id="tutor-<?php echo $tutor['staff_id']; ?>">
                                        <div class="tutor-header" onclick="toggleDetails(<?php echo $tutor['staff_id']; ?>)">
                                            <div class="tutor-main-info">
                                                <div class="tutor-avatar">
                                                    <?php echo strtoupper(substr($tutor['first_name'], 0, 1) . substr($tutor['last_name'], 0, 1)); ?>
                                                </div>
                                                <div class="tutor-basic-info">
                                                    <h4>
                                                        <?php echo htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']); ?>
                                                        <?php if($tutor['subject_match']): ?>
                                                            <span class="role-badge" style="background: var(--green); color: white;">RECOMMENDED</span>
                                                        <?php endif; ?>
                                                        <span class="role-badge role-<?php echo $tutor['role']; ?>">
                                                            <?php echo strtoupper($tutor['role']); ?>
                                                        </span>
                                                    </h4>
                                                    <p>
                                                        <?php echo htmlspecialchars($tutor['student_number'] ?? $tutor['staff_number']); ?> • 
                                                        <?php echo htmlspecialchars($tutor['email']); ?>
                                                        <?php if(isset($tutor['gpa']) && $tutor['gpa'] > 0): ?>
                                                            • <strong style="color: <?php echo $tutor['gpa'] >= 3.7 ? 'var(--green)' : ($tutor['gpa'] >= 3.5 ? 'var(--blue)' : '#f59e0b'); ?>">
                                                                GPA: <?php echo number_format($tutor['gpa'], 2); ?>
                                                            </strong>
                                                        <?php endif; ?>
                                                    </p>
                                                    <div class="tutor-quick-info">
                                                        <div class="quick-info-item">
                                                            <i class="fas fa-graduation-cap"></i>
                                                            <span><?php echo htmlspecialchars($tutor['qualification']); ?></span>
                                                        </div>
                                                        <?php if(isset($tutor['academic_year_level']) && $tutor['academic_year_level']): ?>
                                                            <div class="quick-info-item">
                                                                <i class="fas fa-user-graduate"></i>
                                                                <span><?php echo htmlspecialchars($tutor['academic_year_level']); ?></span>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div class="quick-info-item">
                                                            <i class="fas fa-chalkboard-teacher"></i>
                                                            <span><?php echo $tutor['current_assignments']; ?> active assignment<?php echo $tutor['current_assignments'] != 1 ? 's' : ''; ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="tutor-actions">
                                                <button type="button" class="expand-btn" onclick="event.stopPropagation(); toggleDetails(<?php echo $tutor['staff_id']; ?>)">
                                                    <span class="expand-text">View Details</span>
                                                    <i class="fas fa-chevron-down"></i>
                                                </button>
                                                <input type="checkbox" class="select-checkbox" 
                                                       id="checkbox-<?php echo $tutor['staff_id']; ?>"
                                                       value="<?php echo $tutor['staff_id']; ?>"
                                                       onchange="toggleTutorSelection(<?php echo $tutor['staff_id']; ?>, '<?php echo $tutor['role']; ?>')">
                                            </div>
                                        </div>
                                        
                                        <div class="tutor-details" id="details-<?php echo $tutor['staff_id']; ?>">
                                            <div class="details-grid">
                                                <div class="detail-section">
                                                    <h5><i class="fas fa-user"></i> Personal Information</h5>
                                                    <div class="detail-item">
                                                        <i class="fas fa-id-badge"></i>
                                                        <span><strong>Student Number:</strong> <?php echo htmlspecialchars($tutor['student_number'] ?? 'N/A'); ?></span>
                                                    </div>
                                                    <?php if(isset($tutor['gpa']) && $tutor['gpa'] > 0): ?>
                                                        <div class="detail-item">
                                                            <i class="fas fa-chart-line"></i>
                                                            <span>
                                                                <strong>GPA:</strong> 
                                                                <strong style="color: <?php echo $tutor['gpa'] >= 3.7 ? 'var(--green)' : ($tutor['gpa'] >= 3.5 ? 'var(--blue)' : '#f59e0b'); ?>">
                                                                    <?php echo number_format($tutor['gpa'], 2); ?>/4.00
                                                                </strong>
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if(isset($tutor['academic_year_level']) && $tutor['academic_year_level']): ?>
                                                        <div class="detail-item">
                                                            <i class="fas fa-user-graduate"></i>
                                                            <span><strong>Year Level:</strong> <?php echo htmlspecialchars($tutor['academic_year_level']); ?></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="detail-item">
                                                        <i class="fas fa-envelope"></i>
                                                        <span><strong>Email:</strong> <?php echo htmlspecialchars($tutor['email']); ?></span>
                                                    </div>
                                                    <div class="detail-item">
                                                        <i class="fas fa-phone"></i>
                                                        <span><strong>Phone:</strong> <?php echo htmlspecialchars($tutor['phone']); ?></span>
                                                    </div>
                                                </div>
                                                
                                                <div class="detail-section">
                                                    <h5><i class="fas fa-graduation-cap"></i> Qualifications</h5>
                                                    <div class="detail-item">
                                                        <i class="fas fa-certificate"></i>
                                                        <span><strong>Qualification:</strong> <?php echo htmlspecialchars($tutor['qualification']); ?></span>
                                                    </div>
                                                    <div class="detail-item">
                                                        <i class="fas fa-star"></i>
                                                        <span><strong>Specialization:</strong> <?php echo htmlspecialchars($tutor['specialization']); ?></span>
                                                    </div>
                                                    <div class="detail-item">
                                                        <i class="fas fa-tag"></i>
                                                        <span><strong>Role:</strong> <?php echo ucfirst($tutor['role']); ?></span>
                                                    </div>
                                                </div>
                                                
                                                <div class="detail-section">
                                                    <h5><i class="fas fa-chart-line"></i> Current Workload</h5>
                                                    <div class="detail-item">
                                                        <i class="fas fa-clipboard-list"></i>
                                                        <span><strong>Active Assignments:</strong> <?php echo $tutor['current_assignments']; ?></span>
                                                    </div>
                                                    <div class="detail-item">
                                                        <i class="fas fa-info-circle"></i>
                                                        <span>
                                                            <?php if($tutor['current_assignments'] == 0): ?>
                                                                <strong style="color: var(--green);">Available - No current assignments</strong>
                                                            <?php elseif($tutor['current_assignments'] <= 2): ?>
                                                                <strong style="color: var(--blue);">Good availability</strong>
                                                            <?php elseif($tutor['current_assignments'] <= 4): ?>
                                                                <strong style="color: #f59e0b;">Moderate workload</strong>
                                                            <?php else: ?>
                                                                <strong style="color: #dc2626;">High workload</strong>
                                                            <?php endif; ?>
                                                        </span>
                                                    </div>
                                                    <?php if($tutor['subject_match']): ?>
                                                        <div class="detail-item">
                                                            <i class="fas fa-check-circle" style="color: var(--green);"></i>
                                                            <span><strong style="color: var(--green);">Specialization matches this module</strong></span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <?php if(count($tutors) == 0): ?>
                                    <div class="empty-state">
                                        <i class="fas fa-user-times"></i>
                                        <h3>No Tutors Available</h3>
                                        <p>There are currently no active tutors or PALs in the system.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-section">
                            <h2 class="section-title"><i class="fas fa-cog"></i> Assignment Details</h2>
                            
                            <div class="form-grid">
                                <div class="form-group">
                                    <label><i class="fas fa-users"></i> Maximum Students per Session</label>
                                    <input type="number" name="max_students" min="1" value="<?php echo $max_students_per_session; ?>" required>
                                    <small style="color: #6b7280; font-size: 12px; margin-top: 5px; display: block;">
                                        Recommended: <?php echo $max_students_per_session; ?> students per session based on <?php echo $module['risk_category']; ?>
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-clock"></i> Session Frequency</label>
                                    <select name="session_frequency" required>
                                        <option value="">Select Frequency</option>
                                        <option value="Once weekly">Once weekly</option>
                                        <option value="Twice weekly" selected>Twice weekly</option>
                                        <option value="Three times weekly">Three times weekly</option>
                                        <option value="Daily">Daily</option>
                                        <option value="As needed">As needed</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-calendar-day"></i> Start Date</label>
                                    <input type="date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>

                                <div class="form-group">
                                    <label><i class="fas fa-calendar-check"></i> End Date</label>
                                    <input type="date" name="end_date" value="<?php echo date('Y-m-d', strtotime('+3 months')); ?>" required>
                                </div>

                                <div class="form-group full-width">
                                    <label><i class="fas fa-map-marker-alt"></i> Location</label>
                                    <input type="text" name="location" placeholder="e.g., Computer Lab A, Room 101, Library Study Room" required>
                                </div>

                                <div class="form-group full-width">
                                    <label><i class="fas fa-sticky-note"></i> Notes/Instructions (Optional)</label>
                                    <textarea name="notes" placeholder="Any special instructions, focus areas, or requirements for the tutor..."></textarea>
                                </div>
                            </div>

                            <div style="display: flex; gap: 15px; margin-top: 30px;">
                                <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                                    <i class="fas fa-check"></i> Assign <span id="assignCount">0</span> Tutor(s)
                                </button>
                                <a href="browse-modules.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="selection-counter" id="selectionCounter">
        <i class="fas fa-check-circle"></i> <span id="counterText">0 of <?php echo $recommended_tutors; ?> tutors selected</span>
    </div>
    
    <?php include '../assets/includes/modals.php'; ?>
    <link rel="stylesheet" href="../assets/css/modals.css">
    <script src="../assets/js/modals.js"></script>
    <script src="js/dashboard.js"></script>
    <script>
        const maxTutors = <?php echo $recommended_tutors; ?>;
        let selectedTutors = [];
        
        function toggleDetails(tutorId) {
            const details = document.getElementById('details-' + tutorId);
            const expandBtn = document.querySelector('#tutor-' + tutorId + ' .expand-btn');
            const expandText = expandBtn.querySelector('.expand-text');
            const expandIcon = expandBtn.querySelector('i');
            
            if(details.classList.contains('expanded')) {
                details.classList.remove('expanded');
                expandText.textContent = 'View Details';
                expandIcon.classList.remove('fa-chevron-up');
                expandIcon.classList.add('fa-chevron-down');
            } else {
                details.classList.add('expanded');
                expandText.textContent = 'Hide Details';
                expandIcon.classList.remove('fa-chevron-down');
                expandIcon.classList.add('fa-chevron-up');
            }
        }
        
        function toggleTutorSelection(tutorId, tutorType) {
            const checkbox = document.getElementById('checkbox-' + tutorId);
            const tutorItem = document.getElementById('tutor-' + tutorId);
            
            if(checkbox.checked) {
                // Check if we've reached the maximum
                if(selectedTutors.length >= maxTutors) {
                    checkbox.checked = false;
                    showModal('Maximum Reached', 'You can only select up to ' + maxTutors + ' tutor(s) for this module based on the recommendation.', 'warning');
                    return;
                }
                
                // Add to selection
                selectedTutors.push(tutorId);
                tutorItem.classList.add('selected');
            } else {
                // Remove from selection
                selectedTutors = selectedTutors.filter(id => id !== tutorId);
                tutorItem.classList.remove('selected');
            }
            
            updateSelectionUI();
        }
        
        function updateSelectionUI() {
            const count = selectedTutors.length;
            const counter = document.getElementById('selectionCounter');
            const counterText = document.getElementById('counterText');
            const submitBtn = document.getElementById('submitBtn');
            const assignCount = document.getElementById('assignCount');
            
            // Update counter
            counterText.textContent = count + ' of ' + maxTutors + ' tutor(s) selected';
            
            if(count > 0) {
                counter.classList.add('show');
                submitBtn.disabled = false;
                assignCount.textContent = count;
            } else {
                counter.classList.remove('show');
                submitBtn.disabled = true;
                assignCount.textContent = '0';
            }
            
            // Update hidden field
            document.getElementById('selectedTutorIds').value = selectedTutors.join(',');
            document.getElementById('selectedTutorCount').value = count;
        }
    </script>
</body>
</html>
