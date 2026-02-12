<?php
session_start();
if(!isset($_SESSION['staff_id']) || !in_array($_SESSION['role'], ['tutor', 'pal'])) {
    header('Location: ../staff-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;
$tutor_id = $_SESSION['staff_id'];

// Get session details with authorization check
$stmt = $conn->prepare("
    SELECT 
        ts.*,
        ta.tutor_type,
        m.subject_code, m.subject_name, m.faculty, m.campus,
        arm.at_risk_students,
        COUNT(sr.registration_id) as registered_count
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
    header('Location: my-sessions.php?error=Session not found or unauthorized');
    exit();
}

// Get all students enrolled in this module (from student_modules table)
// These students are automatically "registered" for attendance purposes
try {
    // First check if student_modules table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'student_modules'");
    $table_exists = $table_check->rowCount() > 0;
    
    if($table_exists) {
        // Use student_modules table (preferred method)
        $students_stmt = $conn->prepare("
            SELECT 
                s.student_id,
                s.first_name, 
                s.last_name, 
                s.email, 
                s.year_of_study,
                sr.registration_id,
                sr.attended,
                sr.attendance_marked_at,
                sr.status as attendance_status,
                COALESCE(sr.status, 'not_marked') as status
            FROM student_modules sm
            INNER JOIN students s ON sm.student_id = s.student_id
            INNER JOIN at_risk_modules arm ON sm.module_id = arm.module_id
            INNER JOIN tutor_assignments ta ON arm.risk_id = ta.risk_module_id
            INNER JOIN tutor_sessions ts ON ta.assignment_id = ts.assignment_id
            LEFT JOIN session_registrations sr ON (
                sr.session_id = ts.session_id 
                AND sr.student_id = s.student_id
            )
            WHERE ts.session_id = ?
            AND sm.status = 'active'
            ORDER BY s.last_name, s.first_name
        ");
        $students_stmt->execute([$session_id]);
        $students = $students_stmt->fetchAll();
    } else {
        // Fallback: Get students who manually registered for this session
        $students_stmt = $conn->prepare("
            SELECT 
                s.student_id,
                s.first_name, 
                s.last_name, 
                s.email, 
                s.year_of_study,
                sr.registration_id,
                sr.attended,
                sr.attendance_marked_at,
                sr.status as attendance_status,
                COALESCE(sr.status, 'not_marked') as status
            FROM session_registrations sr
            INNER JOIN students s ON sr.student_id = s.student_id
            WHERE sr.session_id = ?
            ORDER BY s.last_name, s.first_name
        ");
        $students_stmt->execute([$session_id]);
        $students = $students_stmt->fetchAll();
        
        // Set a flag to show warning
        $using_fallback = true;
    }
} catch(PDOException $e) {
    // If there's an error, try the fallback method
    $students_stmt = $conn->prepare("
        SELECT 
            s.student_id,
            s.first_name, 
            s.last_name, 
            s.email, 
            s.year_of_study,
            sr.registration_id,
            sr.attended,
            sr.attendance_marked_at,
            sr.status as attendance_status,
            COALESCE(sr.status, 'not_marked') as status
        FROM session_registrations sr
        INNER JOIN students s ON sr.student_id = s.student_id
        WHERE sr.session_id = ?
        ORDER BY s.last_name, s.first_name
    ");
    $students_stmt->execute([$session_id]);
    $students = $students_stmt->fetchAll();
    $using_fallback = true;
}

// Handle AJAX attendance update
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    $student_id = $_POST['student_id'];
    $attended = $_POST['attended'] === 'true' ? 1 : 0;
    $status = $attended ? 'attended' : 'absent';
    
    try {
        // Check if registration record exists
        $check = $conn->prepare("
            SELECT registration_id 
            FROM session_registrations 
            WHERE session_id = ? AND student_id = ?
        ");
        $check->execute([$session_id, $student_id]);
        $existing = $check->fetch();
        
        if($existing) {
            // Update existing record
            $update = $conn->prepare("
                UPDATE session_registrations 
                SET attended = ?, 
                    status = ?,
                    attendance_marked_at = NOW()
                WHERE registration_id = ?
            ");
            $update->execute([$attended, $status, $existing['registration_id']]);
        } else {
            // Create new registration record
            $insert = $conn->prepare("
                INSERT INTO session_registrations 
                (session_id, student_id, registration_date, attended, status, attendance_marked_at)
                VALUES (?, ?, NOW(), ?, ?, NOW())
            ");
            $insert->execute([$session_id, $student_id, $attended, $status]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Attendance updated successfully']);
        exit();
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error updating attendance: ' . $e->getMessage()]);
        exit();
    }
}

// Handle bulk save
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_all'])) {
    try {
        $conn->beginTransaction();
        
        foreach($_POST['attendance'] as $student_id => $attended) {
            $attended_bool = $attended === '1' ? 1 : 0;
            $status = $attended_bool ? 'attended' : 'absent';
            
            // Check if registration record exists
            $check = $conn->prepare("
                SELECT registration_id 
                FROM session_registrations 
                WHERE session_id = ? AND student_id = ?
            ");
            $check->execute([$session_id, $student_id]);
            $existing = $check->fetch();
            
            if($existing) {
                // Update existing record
                $update = $conn->prepare("
                    UPDATE session_registrations 
                    SET attended = ?, 
                        status = ?,
                        attendance_marked_at = NOW()
                    WHERE registration_id = ?
                ");
                $update->execute([$attended_bool, $status, $existing['registration_id']]);
            } else {
                // Create new registration record (auto-register student)
                $insert = $conn->prepare("
                    INSERT INTO session_registrations 
                    (session_id, student_id, registration_date, attended, status, attendance_marked_at)
                    VALUES (?, ?, NOW(), ?, ?, NOW())
                ");
                $insert->execute([$session_id, $student_id, $attended_bool, $status]);
            }
        }
        
        // Update session status to completed if not already
        if($session['status'] == 'scheduled') {
            $update_session = $conn->prepare("
                UPDATE tutor_sessions 
                SET status = 'completed' 
                WHERE session_id = ?
            ");
            $update_session->execute([$session_id]);
        }
        
        $conn->commit();
        
        header('Location: mark-attendance.php?session_id=' . $session_id . '&success=Attendance saved successfully');
        exit();
        
    } catch(PDOException $e) {
        $conn->rollBack();
        header('Location: mark-attendance.php?session_id=' . $session_id . '&error=Error saving attendance: ' . $e->getMessage());
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mark Attendance - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .attendance-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .session-banner {
            background: linear-gradient(135deg, #000000 0%, #1d4ed8 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .session-banner h2 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        
        .session-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .session-info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            opacity: 0.95;
        }
        
        .session-info-item i {
            font-size: 18px;
        }
        
        .attendance-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            border: 2px solid #e5e7eb;
        }
        
        .attendance-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .attendance-stats {
            display: flex;
            gap: 20px;
        }
        
        .stat-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .stat-badge.total {
            background: #eff6ff;
            color: var(--blue);
        }
        
        .stat-badge.present {
            background: #d1fae5;
            color: #10b981;
        }
        
        .stat-badge.absent {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .students-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .students-table thead {
            background: #f9fafb;
        }
        
        .students-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            border-bottom: 2px solid #e5e7eb;
        }
        
        .students-table td {
            padding: 15px;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .students-table tbody tr:hover {
            background: #f9fafb;
        }
        
        .student-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--blue);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
        }
        
        .student-details h4 {
            margin: 0 0 3px 0;
            font-size: 15px;
            color: var(--dark);
        }
        
        .student-details p {
            margin: 0;
            font-size: 13px;
            color: #6b7280;
        }
        
        .attendance-toggle {
            display: flex;
            gap: 10px;
        }
        
        .toggle-btn {
            padding: 8px 20px;
            border: 2px solid #e5e7eb;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .toggle-btn:hover {
            border-color: var(--blue);
        }
        
        .toggle-btn.present {
            border-color: #10b981;
            background: #d1fae5;
            color: #10b981;
        }
        
        .toggle-btn.absent {
            border-color: #dc2626;
            background: #fee2e2;
            color: #dc2626;
        }
        
        .toggle-btn input[type="radio"] {
            display: none;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .status-badge.attended {
            background: #d1fae5;
            color: #10b981;
        }
        
        .status-badge.absent {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .status-badge.registered {
            background: #e5e7eb;
            color: #6b7280;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }
        
        .empty-state i {
            font-size: 64px;
            color: #d1d5db;
            margin-bottom: 20px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <div class="attendance-container">
                    <div style="margin-bottom: 20px;">
                        <a href="session-details.php?id=<?php echo $session_id; ?>" class="btn-link">
                            <i class="fas fa-arrow-left"></i> Back to Session Details
                        </a>
                    </div>

                    <?php if(isset($_GET['success'])): ?>
                        <div style="margin-bottom: 20px; padding: 15px 20px; background: #d1fae5; border-radius: 8px; border-left: 4px solid #10b981; color: #065f46;">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($_GET['error'])): ?>
                        <div style="margin-bottom: 20px; padding: 15px 20px; background: #fee2e2; border-radius: 8px; border-left: 4px solid #dc2626; color: #991b1b;">
                            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if(isset($using_fallback) && $using_fallback): ?>
                        <div style="margin-bottom: 20px; padding: 15px 20px; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b; color: #92400e;">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong>Notice:</strong> The student enrollment system (student_modules table) is not set up yet. 
                            Only students who manually registered for this session will appear below.
                            <br><br>
                            <strong>To enable automatic enrollment:</strong> Run the SQL file: 
                            <code style="background: white; padding: 2px 6px; border-radius: 4px;">database/student_modules_schema.sql</code>
                            <br>
                            <small>This will automatically show all students enrolled in the module.</small>
                        </div>
                    <?php endif; ?>

                    <!-- Session Banner -->
                    <div class="session-banner">
                        <h2><?php echo htmlspecialchars($session['topic']); ?></h2>
                        <div class="session-info-grid">
                            <div class="session-info-item">
                                <i class="fas fa-book"></i>
                                <span><?php echo htmlspecialchars($session['subject_code']); ?> - <?php echo htmlspecialchars($session['subject_name']); ?></span>
                            </div>
                            <div class="session-info-item">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo date('l, F j, Y', strtotime($session['session_date'])); ?></span>
                            </div>
                            <div class="session-info-item">
                                <i class="fas fa-clock"></i>
                                <span><?php echo date('H:i', strtotime($session['start_time'])); ?> - <?php echo date('H:i', strtotime($session['end_time'])); ?></span>
                            </div>
                            <div class="session-info-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($session['location']); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Card -->
                    <div class="attendance-card">
                        <div class="attendance-header">
                            <h3 style="margin: 0; font-size: 20px; color: var(--dark);">
                                <i class="fas fa-clipboard-check"></i> Mark Attendance
                            </h3>
                            <div class="attendance-stats">
                                <span class="stat-badge total">
                                    <i class="fas fa-users"></i> <?php echo count($students); ?> Registered
                                </span>
                                <span class="stat-badge present" id="presentCount">
                                    <i class="fas fa-check"></i> <span id="presentNum"><?php echo count(array_filter($students, fn($s) => $s['attended'])); ?></span> Present
                                </span>
                                <span class="stat-badge absent" id="absentCount">
                                    <i class="fas fa-times"></i> <span id="absentNum"><?php echo count(array_filter($students, fn($s) => !$s['attended'] && $s['status'] == 'absent')); ?></span> Absent
                                </span>
                            </div>
                        </div>

                        <?php if(count($students) > 0): ?>
                            <form method="POST" id="attendanceForm">
                                <input type="hidden" name="save_all" value="1">
                                
                                <table class="students-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th>Student</th>
                                            <th style="width: 150px;">Year</th>
                                            <th style="width: 250px; text-align: center;">Attendance</th>
                                            <th style="width: 120px; text-align: center;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($students as $index => $student): ?>
                                        <tr data-student-id="<?php echo htmlspecialchars($student['student_id']); ?>">
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <div class="student-info">
                                                    <div class="student-avatar">
                                                        <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                                    </div>
                                                    <div class="student-details">
                                                        <h4><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h4>
                                                        <p><?php echo htmlspecialchars($student['student_id']); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>Year <?php echo $student['year_of_study']; ?></td>
                                            <td>
                                                <div class="attendance-toggle">
                                                    <label class="toggle-btn <?php echo $student['attended'] ? 'present' : ''; ?>" data-value="1">
                                                        <input type="radio" 
                                                               name="attendance[<?php echo htmlspecialchars($student['student_id']); ?>]" 
                                                               value="1" 
                                                               <?php echo $student['attended'] ? 'checked' : ''; ?>
                                                               onchange="updateAttendance('<?php echo htmlspecialchars($student['student_id']); ?>', true)">
                                                        <i class="fas fa-check"></i> Present
                                                    </label>
                                                    <label class="toggle-btn <?php echo !$student['attended'] && $student['status'] == 'absent' ? 'absent' : ''; ?>" data-value="0">
                                                        <input type="radio" 
                                                               name="attendance[<?php echo htmlspecialchars($student['student_id']); ?>]" 
                                                               value="0" 
                                                               <?php echo !$student['attended'] && $student['status'] == 'absent' ? 'checked' : ''; ?>
                                                               onchange="updateAttendance('<?php echo htmlspecialchars($student['student_id']); ?>', false)">
                                                        <i class="fas fa-times"></i> Absent
                                                    </label>
                                                </div>
                                            </td>
                                            <td style="text-align: center;">
                                                <span class="status-badge <?php echo $student['status']; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $student['status'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>

                                <div class="action-buttons">
                                    <a href="session-details.php?id=<?php echo $session_id; ?>" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save"></i> Save Attendance
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-user-slash"></i>
                                <h3>No Students Registered</h3>
                                <p>No students have registered for this session yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../assets/includes/modals.php'; ?>
    <link rel="stylesheet" href="../assets/css/modals.css">
    <script src="../assets/js/modals.js"></script>
    <script src="js/dashboard.js"></script>
    <script>
        // Update attendance via AJAX
        function updateAttendance(studentId, attended) {
            const row = document.querySelector(`tr[data-student-id="${studentId}"]`);
            const labels = row.querySelectorAll('.toggle-btn');
            
            // Update button styles
            labels.forEach(label => {
                const value = label.dataset.value;
                label.classList.remove('present', 'absent');
                
                if((value === '1' && attended) || (value === '0' && !attended)) {
                    label.classList.add(attended ? 'present' : 'absent');
                }
            });
            
            // Update status badge
            const statusBadge = row.querySelector('.status-badge');
            statusBadge.className = 'status-badge ' + (attended ? 'attended' : 'absent');
            statusBadge.textContent = attended ? 'Attended' : 'Absent';
            
            // Update counts
            updateCounts();
            
            // Optional: Auto-save via AJAX
            /*
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('student_id', studentId);
            formData.append('attended', attended);
            
            fetch('mark-attendance.php?session_id=<?php echo $session_id; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(!data.success) {
                    showMessageModal('Error', data.message, 'error');
                }
            });
            */
        }
        
        // Update attendance counts
        function updateCounts() {
            const rows = document.querySelectorAll('tbody tr');
            let present = 0;
            let absent = 0;
            
            rows.forEach(row => {
                const statusBadge = row.querySelector('.status-badge');
                if(statusBadge.classList.contains('attended')) {
                    present++;
                } else if(statusBadge.classList.contains('absent')) {
                    absent++;
                }
            });
            
            document.getElementById('presentNum').textContent = present;
            document.getElementById('absentNum').textContent = absent;
        }
        
        // Form submission confirmation
        document.getElementById('attendanceForm').addEventListener('submit', function(e) {
            const unmarked = document.querySelectorAll('.status-badge.not_marked').length;
            
            if(unmarked > 0) {
                e.preventDefault();
                showMessageModal(
                    'Unmarked Students',
                    `There are ${unmarked} student(s) with unmarked attendance. Please mark all students as Present or Absent before saving.`,
                    'warning'
                );
            }
        });
    </script>
</body>
</html>
