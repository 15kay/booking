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
$student_id = isset($_GET['student_id']) ? trim($_GET['student_id']) : '';

if(empty($student_id)) {
    header('Location: students.php');
    exit();
}

// Get student information
$student_query = $conn->prepare("
    SELECT * FROM students WHERE student_id = ?
");
$student_query->execute([$student_id]);
$student = $student_query->fetch();

if(!$student) {
    header('Location: students.php?error=Student not found');
    exit();
}

// Get all sessions this student registered for (from this tutor)
$sessions_query = $conn->prepare("
    SELECT 
        ts.*,
        m.subject_code, m.subject_name,
        ta.tutor_type,
        sr.registration_id, sr.attended, sr.registered_at,
        COUNT(DISTINCT sr2.registration_id) as total_registered
    FROM tutor_sessions ts
    INNER JOIN tutor_assignments ta ON ts.assignment_id = ta.assignment_id
    INNER JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
    INNER JOIN modules m ON arm.module_id = m.module_id
    LEFT JOIN session_registrations sr ON ts.session_id = sr.session_id AND sr.student_id = ?
    LEFT JOIN session_registrations sr2 ON ts.session_id = sr2.session_id
    WHERE ta.tutor_id = ? AND sr.registration_id IS NOT NULL
    GROUP BY ts.session_id
    ORDER BY ts.session_date DESC, ts.start_time DESC
");
$sessions_query->execute([$student_id, $tutor_id]);
$sessions = $sessions_query->fetchAll();

// Calculate statistics
$total_sessions = count($sessions);
$attended_sessions = 0;
$missed_sessions = 0;
$upcoming_sessions = 0;

foreach($sessions as $session) {
    if($session['status'] == 'completed') {
        if($session['attended']) {
            $attended_sessions++;
        } else {
            $missed_sessions++;
        }
    } elseif($session['status'] == 'scheduled' && $session['session_date'] >= date('Y-m-d')) {
        $upcoming_sessions++;
    }
}

$attendance_rate = $total_sessions > 0 ? ($attended_sessions / $total_sessions) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Sessions - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .student-header {
            background: linear-gradient(135deg, var(--blue) 0%, #1e40af 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .student-avatar-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: white;
            color: var(--blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 700;
        }
        
        .student-header-info h2 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        
        .student-header-info p {
            margin: 0;
            opacity: 0.9;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid var(--blue);
        }
        
        .stat-card h3 {
            font-size: 32px;
            margin: 0 0 5px 0;
            color: var(--blue);
        }
        
        .stat-card p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }
        
        .session-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            border-left: 4px solid var(--blue);
            transition: all 0.3s;
        }
        
        .session-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .session-card.attended {
            border-left-color: var(--green);
        }
        
        .session-card.missed {
            border-left-color: #dc2626;
        }
        
        .session-card.upcoming {
            border-left-color: #f59e0b;
        }
        
        .session-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .session-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
            margin: 0 0 5px 0;
        }
        
        .session-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 14px;
            color: #6b7280;
        }
        
        .session-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .session-meta-item i {
            color: var(--blue);
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .badge-attended {
            background: #d1fae5;
            color: #10b981;
        }
        
        .badge-missed {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .badge-upcoming {
            background: #fef3c7;
            color: #f59e0b;
        }
        
        .badge-scheduled {
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
                <div style="margin-bottom: 20px;">
                    <a href="students.php" class="btn-link">
                        <i class="fas fa-arrow-left"></i> Back to Students
                    </a>
                </div>

                <!-- Student Header -->
                <div class="student-header">
                    <div class="student-avatar-large">
                        <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                    </div>
                    <div class="student-header-info">
                        <h2><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h2>
                        <p>
                            <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($student['student_id']); ?> • 
                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($student['email']); ?>
                            <?php if($student['phone']): ?>
                                • <i class="fas fa-phone"></i> <?php echo htmlspecialchars($student['phone']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3><?php echo $total_sessions; ?></h3>
                        <p>Total Sessions</p>
                    </div>
                    <div class="stat-card" style="border-left-color: var(--green);">
                        <h3><?php echo $attended_sessions; ?></h3>
                        <p>Attended</p>
                    </div>
                    <div class="stat-card" style="border-left-color: #dc2626;">
                        <h3><?php echo $missed_sessions; ?></h3>
                        <p>Missed</p>
                    </div>
                    <div class="stat-card" style="border-left-color: #f59e0b;">
                        <h3><?php echo $upcoming_sessions; ?></h3>
                        <p>Upcoming</p>
                    </div>
                    <div class="stat-card" style="border-left-color: #8b5cf6;">
                        <h3><?php echo number_format($attendance_rate, 0); ?>%</h3>
                        <p>Attendance Rate</p>
                    </div>
                </div>

                <!-- Sessions List -->
                <div class="section-card">
                    <div class="section-header">
                        <h2><i class="fas fa-calendar-alt"></i> Session History</h2>
                    </div>

                    <?php if(count($sessions) > 0): ?>
                        <?php foreach($sessions as $session): 
                            $session_class = 'session-card';
                            $badge_class = 'badge-scheduled';
                            $badge_text = 'Scheduled';
                            
                            if($session['status'] == 'completed') {
                                if($session['attended']) {
                                    $session_class .= ' attended';
                                    $badge_class = 'badge-attended';
                                    $badge_text = 'Attended';
                                } else {
                                    $session_class .= ' missed';
                                    $badge_class = 'badge-missed';
                                    $badge_text = 'Missed';
                                }
                            } elseif($session['status'] == 'scheduled' && $session['session_date'] >= date('Y-m-d')) {
                                $session_class .= ' upcoming';
                                $badge_class = 'badge-upcoming';
                                $badge_text = 'Upcoming';
                            }
                        ?>
                            <div class="<?php echo $session_class; ?>">
                                <div class="session-header">
                                    <div>
                                        <h3 class="session-title"><?php echo htmlspecialchars($session['topic']); ?></h3>
                                        <div class="session-meta">
                                            <div class="session-meta-item">
                                                <i class="fas fa-book"></i>
                                                <span><?php echo htmlspecialchars($session['subject_code']); ?> - <?php echo htmlspecialchars($session['subject_name']); ?></span>
                                            </div>
                                            <div class="session-meta-item">
                                                <i class="fas fa-calendar"></i>
                                                <span><?php echo date('M j, Y', strtotime($session['session_date'])); ?></span>
                                            </div>
                                            <div class="session-meta-item">
                                                <i class="fas fa-clock"></i>
                                                <span><?php echo date('g:i A', strtotime($session['start_time'])); ?> - <?php echo date('g:i A', strtotime($session['end_time'])); ?></span>
                                            </div>
                                            <div class="session-meta-item">
                                                <i class="fas fa-map-marker-alt"></i>
                                                <span><?php echo htmlspecialchars($session['location']); ?></span>
                                            </div>
                                            <div class="session-meta-item">
                                                <i class="fas fa-users"></i>
                                                <span><?php echo $session['total_registered']; ?> students</span>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                                </div>
                                
                                <?php if($session['description']): ?>
                                    <p style="margin: 10px 0 0 0; color: #6b7280; font-size: 14px;">
                                        <?php echo htmlspecialchars($session['description']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div style="margin-top: 15px;">
                                    <a href="session-details.php?id=<?php echo $session['session_id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p>No sessions found for this student</p>
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
