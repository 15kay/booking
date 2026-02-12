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
        m.subject_code, m.subject_name, m.faculty, m.campus, m.risk_category,
        ta.assignment_id, ta.tutor_type,
        CONCAT(s.first_name, ' ', s.last_name) as tutor_name,
        COUNT(sr.registration_id) as total_registered,
        COUNT(CASE WHEN sr.attended = TRUE THEN 1 END) as total_attended,
        COUNT(CASE WHEN sr.attended = FALSE THEN 1 END) as total_absent
    FROM tutor_sessions ts
    INNER JOIN tutor_assignments ta ON ts.assignment_id = ta.assignment_id
    INNER JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
    INNER JOIN modules m ON arm.module_id = m.module_id
    INNER JOIN staff s ON ta.tutor_id = s.staff_id
    LEFT JOIN session_registrations sr ON ts.session_id = sr.session_id
    WHERE ts.session_id = ? AND ta.tutor_id = ? AND ts.status = 'completed'
    GROUP BY ts.session_id
");
$stmt->execute([$session_id, $tutor_id]);
$session = $stmt->fetch();

if(!$session) {
    header('Location: my-sessions.php?error=Session not found or not completed');
    exit();
}

// Get registered students with attendance
$students_stmt = $conn->prepare("
    SELECT 
        sr.*,
        st.first_name, st.last_name, st.email, st.phone
    FROM session_registrations sr
    INNER JOIN students st ON sr.student_id = st.student_id
    WHERE sr.session_id = ?
    ORDER BY sr.attended DESC, st.last_name ASC
");
$students_stmt->execute([$session_id]);
$students = $students_stmt->fetchAll();

$attendance_rate = $session['total_registered'] > 0 ? 
    ($session['total_attended'] / $session['total_registered']) * 100 : 0;

$duration_minutes = (strtotime($session['end_time']) - strtotime($session['start_time'])) / 60;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Report - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .report-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .report-header {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            border: 2px solid #e5e7eb;
            text-align: center;
        }
        
        .report-header h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
            color: var(--dark);
        }
        
        .report-header p {
            margin: 0;
            color: #6b7280;
            font-size: 16px;
        }
        
        .report-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 2px solid #e5e7eb;
        }
        
        .report-section h2 {
            margin: 0 0 20px 0;
            font-size: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .report-section h2 i {
            color: var(--blue);
        }
        
        .report-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .report-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .report-item:last-child {
            border-bottom: none;
        }
        
        .report-label {
            font-weight: 600;
            color: #6b7280;
            font-size: 14px;
        }
        
        .report-value {
            font-weight: 500;
            color: var(--dark);
            font-size: 14px;
        }
        
        .attendance-summary {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .summary-box {
            text-align: center;
            padding: 20px;
            border-radius: 8px;
            border: 2px solid #e5e7eb;
        }
        
        .summary-box.attended {
            background: #f0fdf4;
            border-color: var(--green);
        }
        
        .summary-box.absent {
            background: #fef2f2;
            border-color: #dc2626;
        }
        
        .summary-box.rate {
            background: #eff6ff;
            border-color: var(--blue);
        }
        
        .summary-number {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .summary-box.attended .summary-number {
            color: var(--green);
        }
        
        .summary-box.absent .summary-number {
            color: #dc2626;
        }
        
        .summary-box.rate .summary-number {
            color: var(--blue);
        }
        
        .summary-label {
            font-size: 14px;
            color: #6b7280;
            font-weight: 600;
        }
        
        .student-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .student-table th {
            background: #f9fafb;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .student-table td {
            padding: 12px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
            color: #4b5563;
        }
        
        .student-table tr:hover {
            background: #f9fafb;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
        }
        
        .status-badge.attended {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-badge.absent {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .print-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: var(--blue);
            color: white;
            padding: 15px 25px;
            border-radius: 50px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            border: none;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s;
        }
        
        .print-button:hover {
            background: #1e40af;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.3);
        }
        
        @media print {
            .sidebar, .header, .print-button, .btn-link {
                display: none !important;
            }
            
            .main-content {
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .report-container {
                max-width: 100% !important;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <div class="report-container">
                    <div style="margin-bottom: 20px;">
                        <a href="session-details.php?id=<?php echo $session_id; ?>" class="btn-link">
                            <i class="fas fa-arrow-left"></i> Back to Session Details
                        </a>
                    </div>

                    <!-- Report Header -->
                    <div class="report-header">
                        <h1>Session Report</h1>
                        <p><?php echo htmlspecialchars($session['subject_code']); ?> - <?php echo htmlspecialchars($session['subject_name']); ?></p>
                        <p style="margin-top: 10px; font-size: 14px;">
                            <?php echo date('l, F j, Y', strtotime($session['session_date'])); ?> • 
                            <?php echo date('g:i A', strtotime($session['start_time'])); ?> - <?php echo date('g:i A', strtotime($session['end_time'])); ?>
                        </p>
                    </div>

                    <!-- Attendance Summary -->
                    <div class="report-section">
                        <h2><i class="fas fa-chart-pie"></i> Attendance Summary</h2>
                        <div class="attendance-summary">
                            <div class="summary-box attended">
                                <div class="summary-number"><?php echo $session['total_attended']; ?></div>
                                <div class="summary-label">Students Attended</div>
                            </div>
                            <div class="summary-box absent">
                                <div class="summary-number"><?php echo $session['total_absent']; ?></div>
                                <div class="summary-label">Students Absent</div>
                            </div>
                            <div class="summary-box rate">
                                <div class="summary-number"><?php echo number_format($attendance_rate, 0); ?>%</div>
                                <div class="summary-label">Attendance Rate</div>
                            </div>
                        </div>
                    </div>

                    <!-- Session Details -->
                    <div class="report-section">
                        <h2><i class="fas fa-info-circle"></i> Session Details</h2>
                        <div class="report-grid">
                            <div>
                                <div class="report-item">
                                    <span class="report-label">Session Topic:</span>
                                    <span class="report-value"><?php echo htmlspecialchars($session['topic']); ?></span>
                                </div>
                                <div class="report-item">
                                    <span class="report-label">Module:</span>
                                    <span class="report-value"><?php echo htmlspecialchars($session['subject_code']); ?></span>
                                </div>
                                <div class="report-item">
                                    <span class="report-label">Date:</span>
                                    <span class="report-value"><?php echo date('F j, Y', strtotime($session['session_date'])); ?></span>
                                </div>
                                <div class="report-item">
                                    <span class="report-label">Time:</span>
                                    <span class="report-value">
                                        <?php echo date('g:i A', strtotime($session['start_time'])); ?> - 
                                        <?php echo date('g:i A', strtotime($session['end_time'])); ?>
                                    </span>
                                </div>
                                <div class="report-item">
                                    <span class="report-label">Duration:</span>
                                    <span class="report-value"><?php echo $duration_minutes; ?> minutes</span>
                                </div>
                            </div>
                            <div>
                                <div class="report-item">
                                    <span class="report-label">Location:</span>
                                    <span class="report-value"><?php echo htmlspecialchars($session['location']); ?></span>
                                </div>
                                <div class="report-item">
                                    <span class="report-label">Campus:</span>
                                    <span class="report-value"><?php echo htmlspecialchars($session['campus']); ?></span>
                                </div>
                                <div class="report-item">
                                    <span class="report-label">Session Type:</span>
                                    <span class="report-value"><?php echo ucfirst(str_replace('_', ' ', $session['session_type'])); ?></span>
                                </div>
                                <div class="report-item">
                                    <span class="report-label">Facilitator:</span>
                                    <span class="report-value"><?php echo htmlspecialchars($session['tutor_name']); ?> (<?php echo strtoupper($session['tutor_type']); ?>)</span>
                                </div>
                                <div class="report-item">
                                    <span class="report-label">Max Capacity:</span>
                                    <span class="report-value"><?php echo $session['max_capacity']; ?> students</span>
                                </div>
                            </div>
                        </div>
                        
                        <?php if($session['description']): ?>
                            <div style="margin-top: 20px; padding: 15px; background: #f9fafb; border-radius: 8px;">
                                <strong style="color: var(--dark);">Session Description:</strong>
                                <p style="margin: 5px 0 0 0; color: #4b5563; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($session['description'])); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Student Attendance List -->
                    <div class="report-section">
                        <h2><i class="fas fa-users"></i> Student Attendance List</h2>
                        <table class="student-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Registration Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $counter = 1;
                                foreach($students as $student): 
                                ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td><?php echo date('M j, Y g:i A', strtotime($student['registration_date'])); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $student['attended'] ? 'attended' : 'absent'; ?>">
                                                <?php if($student['attended']): ?>
                                                    <i class="fas fa-check-circle"></i> Attended
                                                <?php else: ?>
                                                    <i class="fas fa-times-circle"></i> Absent
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Performance Notes -->
                    <div class="report-section">
                        <h2><i class="fas fa-clipboard-check"></i> Performance Analysis</h2>
                        <div style="padding: 15px; background: #f9fafb; border-radius: 8px; margin-bottom: 15px;">
                            <strong style="color: var(--dark);">Attendance Performance:</strong>
                            <p style="margin: 5px 0 0 0; color: #4b5563; line-height: 1.6;">
                                <?php if($attendance_rate >= 90): ?>
                                    <span style="color: var(--green);"><i class="fas fa-check-circle"></i> Excellent attendance rate!</span> 
                                    The session had outstanding student participation with <?php echo $session['total_attended']; ?> out of <?php echo $session['total_registered']; ?> students attending.
                                <?php elseif($attendance_rate >= 70): ?>
                                    <span style="color: var(--blue);"><i class="fas fa-thumbs-up"></i> Good attendance rate.</span> 
                                    The session had good student participation with <?php echo $session['total_attended']; ?> out of <?php echo $session['total_registered']; ?> students attending.
                                <?php elseif($attendance_rate >= 50): ?>
                                    <span style="color: #f59e0b;"><i class="fas fa-exclamation-triangle"></i> Fair attendance rate.</span> 
                                    Consider following up with the <?php echo $session['total_absent']; ?> absent students to understand barriers to attendance.
                                <?php else: ?>
                                    <span style="color: #dc2626;"><i class="fas fa-times-circle"></i> Low attendance rate.</span> 
                                    Immediate follow-up recommended with absent students. Consider reviewing session timing and communication strategies.
                                <?php endif; ?>
                            </p>
                        </div>
                        
                        <div style="padding: 15px; background: #eff6ff; border-radius: 8px;">
                            <strong style="color: var(--blue);">Recommendations:</strong>
                            <ul style="margin: 10px 0 0 20px; color: #4b5563; line-height: 1.8;">
                                <?php if($session['total_absent'] > 0): ?>
                                    <li>Follow up with absent students to identify barriers to attendance</li>
                                    <li>Share session materials with absent students to help them catch up</li>
                                <?php endif; ?>
                                <?php if($attendance_rate >= 80): ?>
                                    <li>Continue with current session format and timing</li>
                                    <li>Consider increasing session capacity if demand is high</li>
                                <?php else: ?>
                                    <li>Review session timing to ensure it doesn't conflict with other commitments</li>
                                    <li>Send reminder notifications 24 hours before sessions</li>
                                <?php endif; ?>
                                <li>Collect feedback from attending students to improve future sessions</li>
                            </ul>
                        </div>
                    </div>

                    <!-- Report Footer -->
                    <div style="text-align: center; padding: 20px; color: #6b7280; font-size: 13px;">
                        <p>Report generated on <?php echo date('F j, Y \a\t g:i A'); ?></p>
                        <p>Walter Sisulu University - Tutor & PAL Support System</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <button onclick="window.print()" class="print-button">
        <i class="fas fa-print"></i> Print Report
    </button>
    
    <?php include '../assets/includes/modals.php'; ?>
    <link rel="stylesheet" href="../assets/css/modals.css">
    <script src="../assets/js/modals.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>
