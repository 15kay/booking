<?php
session_start();
if(!isset($_SESSION['staff_id']) || !in_array($_SESSION['role'], ['tutor', 'pal'])) {
    header('Location: ../staff-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

$session_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$tutor_id = $_SESSION['staff_id'];

// Get session details
$stmt = $conn->prepare("
    SELECT 
        ts.*,
        m.subject_code, m.subject_name, m.faculty, m.campus,
        ta.assignment_id, ta.tutor_type,
        COUNT(sr.registration_id) as total_registered,
        COUNT(CASE WHEN sr.attended = TRUE THEN 1 END) as total_attended
    FROM tutor_sessions ts
    INNER JOIN tutor_assignments ta ON ts.assignment_id = ta.assignment_id
    INNER JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
    INNER JOIN modules m ON arm.module_id = m.module_id
    LEFT JOIN session_registrations sr ON ts.session_id = sr.session_id
    WHERE ts.session_id = ? AND ta.tutor_id = ?
    GROUP BY ts.session_id
");
$stmt->execute([$session_id, $tutor_id]);
$session = $stmt->fetch();

if(!$session) {
    header('Location: my-sessions.php?error=Session not found');
    exit();
}

// Get registered students
$students_stmt = $conn->prepare("
    SELECT 
        sr.*,
        st.first_name, st.last_name, st.email, st.phone
    FROM session_registrations sr
    INNER JOIN students st ON sr.student_id = st.student_id
    WHERE sr.session_id = ?
    ORDER BY sr.registration_date ASC
");
$students_stmt->execute([$session_id]);
$students = $students_stmt->fetchAll();

$attendance_rate = $session['total_registered'] > 0 ? 
    ($session['total_attended'] / $session['total_registered']) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Details - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .details-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .session-header {
            background: linear-gradient(135deg, #000000 0%, #1d4ed8 100%);
            color: white;
            padding: 40px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .session-header h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
        }
        
        .session-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
        }
        
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            border: 2px solid #e5e7eb;
        }
        
        .info-card h2 {
            margin: 0 0 20px 0;
            font-size: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .info-card h2 i {
            color: var(--blue);
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .info-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .info-value {
            font-size: 16px;
            color: var(--dark);
            font-weight: 500;
        }
        
        .student-list-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: all 0.3s;
        }
        
        .student-list-item:hover {
            border-color: var(--blue);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .student-list-item.attended {
            background: #f0fdf4;
            border-color: var(--green);
        }
        
        .student-list-item.absent {
            background: #fef2f2;
            border-color: #dc2626;
        }
        
        .student-avatar-small {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--blue);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
            flex-shrink: 0;
        }
        
        .student-list-item.attended .student-avatar-small {
            background: var(--green);
        }
        
        .student-list-item.absent .student-avatar-small {
            background: #dc2626;
        }
        
        .student-info-inline {
            flex: 1;
        }
        
        .student-info-inline h4 {
            margin: 0 0 5px 0;
            font-size: 16px;
            color: var(--dark);
        }
        
        .student-info-inline p {
            margin: 0;
            font-size: 13px;
            color: #6b7280;
        }
        
        .attendance-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .attendance-status.attended {
            background: #d1fae5;
            color: #065f46;
        }
        
        .attendance-status.absent {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .attendance-status.registered {
            background: #dbeafe;
            color: #1e40af;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <div class="details-container">
                    <div style="margin-bottom: 20px;">
                        <a href="my-sessions.php" class="btn-link">
                            <i class="fas fa-arrow-left"></i> Back to My Sessions
                        </a>
                    </div>

                    <!-- Session Header -->
                    <div class="session-header">
                        <h1><?php echo htmlspecialchars($session['topic']); ?></h1>
                        <p>
                            <?php echo htmlspecialchars($session['subject_code']); ?> - <?php echo htmlspecialchars($session['subject_name']); ?> • 
                            <?php echo date('l, F j, Y', strtotime($session['session_date'])); ?> • 
                            <?php echo date('g:i A', strtotime($session['start_time'])); ?> - <?php echo date('g:i A', strtotime($session['end_time'])); ?>
                        </p>
                        <div style="margin-top: 15px;">
                            <span class="badge badge-<?php echo $session['status']; ?>">
                                <?php echo ucfirst($session['status']); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon blue">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $session['total_registered']; ?></h3>
                                <p>Students Registered</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon green">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $session['total_attended']; ?></h3>
                                <p>Students Attended</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon orange">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo number_format($attendance_rate, 1); ?>%</h3>
                                <p>Attendance Rate</p>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon blue">
                                <i class="fas fa-chair"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $session['max_capacity']; ?></h3>
                                <p>Max Capacity</p>
                            </div>
                        </div>
                    </div>

                    <!-- Session Information -->
                    <div class="info-card">
                        <h2><i class="fas fa-info-circle"></i> Session Information</h2>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Date</span>
                                <span class="info-value"><?php echo date('l, F j, Y', strtotime($session['session_date'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Time</span>
                                <span class="info-value">
                                    <?php echo date('g:i A', strtotime($session['start_time'])); ?> - 
                                    <?php echo date('g:i A', strtotime($session['end_time'])); ?>
                                </span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Location</span>
                                <span class="info-value"><?php echo htmlspecialchars($session['location']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Session Type</span>
                                <span class="info-value"><?php echo ucfirst(str_replace('_', ' ', $session['session_type'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Module</span>
                                <span class="info-value"><?php echo htmlspecialchars($session['subject_code']); ?> - <?php echo htmlspecialchars($session['subject_name']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Campus</span>
                                <span class="info-value"><?php echo htmlspecialchars($session['campus']); ?></span>
                            </div>
                        </div>
                        
                        <?php if($session['description']): ?>
                            <div style="margin-top: 20px; padding: 15px; background: #eff6ff; border-radius: 8px; border-left: 4px solid var(--blue);">
                                <strong style="color: var(--blue);"><i class="fas fa-align-left"></i> Description:</strong>
                                <p style="margin: 5px 0 0 0; color: #4b5563; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($session['description'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Registered Students -->
                    <div class="info-card">
                        <h2><i class="fas fa-user-graduate"></i> Registered Students (<?php echo count($students); ?>)</h2>
                        
                        <?php if(count($students) > 0): ?>
                            <?php foreach($students as $student): ?>
                                <div class="student-list-item <?php echo $student['attended'] ? 'attended' : ($session['status'] == 'completed' ? 'absent' : ''); ?>">
                                    <div class="student-avatar-small">
                                        <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                    </div>
                                    
                                    <div class="student-info-inline">
                                        <h4><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h4>
                                        <p>
                                            <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($student['student_id']); ?> • 
                                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($student['email']); ?>
                                            <?php if($student['phone']): ?>
                                                • <i class="fas fa-phone"></i> <?php echo htmlspecialchars($student['phone']); ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    
                                    <?php if($session['status'] == 'completed'): ?>
                                        <span class="attendance-status <?php echo $student['attended'] ? 'attended' : 'absent'; ?>">
                                            <?php if($student['attended']): ?>
                                                <i class="fas fa-check-circle"></i> Attended
                                            <?php else: ?>
                                                <i class="fas fa-times-circle"></i> Absent
                                            <?php endif; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="attendance-status registered">
                                            <i class="fas fa-clock"></i> Registered
                                        </span>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-user-times"></i>
                                <h3>No Students Registered</h3>
                                <p>No students have registered for this session yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Actions -->
                    <?php if($session['status'] == 'scheduled'): ?>
                        <div style="display: flex; gap: 15px;">
                            <a href="mark-attendance.php?session_id=<?php echo $session_id; ?>" class="btn btn-success">
                                <i class="fas fa-clipboard-check"></i> Mark Attendance
                            </a>
                            <button onclick="cancelSession()" class="btn btn-danger">
                                <i class="fas fa-times"></i> Cancel Session
                            </button>
                            <a href="my-sessions.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Sessions
                            </a>
                        </div>
                    <?php else: ?>
                        <div style="display: flex; gap: 15px;">
                            <a href="mark-attendance.php?session_id=<?php echo $session_id; ?>" class="btn btn-primary">
                                <i class="fas fa-clipboard-check"></i> View Attendance
                            </a>
                            <a href="my-sessions.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Sessions
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
    <script>
        function cancelSession() {
            showModal(
                'Cancel Session',
                'Are you sure you want to cancel this session? All registered students will be notified.',
                'warning',
                function() {
                    window.location.href = 'cancel-session.php?id=<?php echo $session_id; ?>';
                }
            );
        }
    </script>
</body>
</html>
