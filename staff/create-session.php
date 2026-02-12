<?php
session_start();
if(!isset($_SESSION['staff_id']) || !in_array($_SESSION['role'], ['tutor', 'pal'])) {
    header('Location: ../staff-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

$assignment_id = isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : 0;
$tutor_id = $_SESSION['staff_id'];

// Get assignment details
$stmt = $conn->prepare("
    SELECT 
        ta.*,
        m.subject_code, m.subject_name, m.faculty, m.campus,
        arm.at_risk_students
    FROM tutor_assignments ta
    INNER JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
    INNER JOIN modules m ON arm.module_id = m.module_id
    WHERE ta.assignment_id = ? AND ta.tutor_id = ? AND ta.status = 'active'
");
$stmt->execute([$assignment_id, $tutor_id]);
$assignment = $stmt->fetch();

if(!$assignment) {
    header('Location: my-assignments.php?error=Assignment not found or inactive');
    exit();
}

// Get existing sessions for this assignment
$sessions_stmt = $conn->prepare("
    SELECT 
        ts.*,
        COUNT(sr.registration_id) as registered_count
    FROM tutor_sessions ts
    LEFT JOIN session_registrations sr ON ts.session_id = sr.session_id
    WHERE ts.assignment_id = ?
    GROUP BY ts.session_id
");
$sessions_stmt->execute([$assignment_id]);
$existing_sessions = $sessions_stmt->fetchAll();

// Get class timetable for this module to check conflicts
try {
    $timetable_stmt = $conn->prepare("
        SELECT 
            ct.*,
            CASE ct.day_of_week
                WHEN 1 THEN 'Monday'
                WHEN 2 THEN 'Tuesday'
                WHEN 3 THEN 'Wednesday'
                WHEN 4 THEN 'Thursday'
                WHEN 5 THEN 'Friday'
            END as day_name
        FROM class_timetable ct
        INNER JOIN at_risk_modules arm ON ct.module_id = arm.module_id
        WHERE arm.risk_id = ?
        AND ct.effective_from <= ? 
        AND ct.effective_to >= ?
        ORDER BY ct.day_of_week, ct.start_time
    ");
    $timetable_stmt->execute([
        $assignment['risk_module_id'], 
        $assignment['end_date'],
        $assignment['start_date']
    ]);
    $class_schedule = $timetable_stmt->fetchAll();
} catch(PDOException $e) {
    // If table doesn't exist yet, use empty array
    $class_schedule = [];
}

// Handle AJAX form submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    // Handle delete request
    if(isset($_POST['action']) && $_POST['action'] == 'delete') {
        $session_id = intval($_POST['session_id']);
        
        try {
            // Check if session belongs to this tutor
            $check = $conn->prepare("
                SELECT ts.session_id 
                FROM tutor_sessions ts
                INNER JOIN tutor_assignments ta ON ts.assignment_id = ta.assignment_id
                WHERE ts.session_id = ? AND ta.tutor_id = ?
            ");
            $check->execute([$session_id, $tutor_id]);
            
            if(!$check->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Session not found or unauthorized.']);
                exit();
            }
            
            // Delete session
            $delete = $conn->prepare("DELETE FROM tutor_sessions WHERE session_id = ?");
            $delete->execute([$session_id]);
            
            echo json_encode(['success' => true, 'message' => 'Session deleted successfully!']);
            exit();
            
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error deleting session: ' . $e->getMessage()]);
            exit();
        }
    }
    
    // Handle edit request
    if(isset($_POST['action']) && $_POST['action'] == 'edit') {
        $session_id = intval($_POST['session_id']);
        $session_date = $_POST['session_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $location = trim($_POST['location']);
        $topic = trim($_POST['topic']);
        $description = trim($_POST['description']);
        $max_capacity = intval($_POST['max_capacity']);
        $session_type = $_POST['session_type'];
        
        // Validate end time is after start time
        if($end_time <= $start_time) {
            echo json_encode(['success' => false, 'message' => 'End time must be after start time.']);
            exit();
        }
        
        try {
            // Check if session belongs to this tutor
            $check = $conn->prepare("
                SELECT ts.session_id, ts.session_date
                FROM tutor_sessions ts
                INNER JOIN tutor_assignments ta ON ts.assignment_id = ta.assignment_id
                WHERE ts.session_id = ? AND ta.tutor_id = ?
            ");
            $check->execute([$session_id, $tutor_id]);
            
            $existing_session = $check->fetch();
            if(!$existing_session) {
                echo json_encode(['success' => false, 'message' => 'Session not found or unauthorized.']);
                exit();
            }
            
            // Check for duplicate sessions (excluding current session)
            $duplicate_check = $conn->prepare("
                SELECT ts.topic, ts.start_time, ts.end_time
                FROM tutor_sessions ts
                INNER JOIN tutor_assignments ta ON ts.assignment_id = ta.assignment_id
                WHERE ta.tutor_id = ?
                AND ts.session_date = ?
                AND ts.session_id != ?
                AND ts.status != 'cancelled'
                AND (
                    (ts.start_time < ? AND ts.end_time > ?) OR
                    (ts.start_time < ? AND ts.end_time > ?) OR
                    (ts.start_time >= ? AND ts.end_time <= ?)
                )
            ");
            
            $duplicate_check->execute([
                $tutor_id,
                $existing_session['session_date'], // Use original date since we can't change it
                $session_id,
                $end_time, $start_time,
                $end_time, $start_time,
                $start_time, $end_time
            ]);
            
            $duplicates = $duplicate_check->fetchAll();
            
            if(count($duplicates) > 0) {
                $duplicate = $duplicates[0];
                $message = "You already have another session scheduled on this date from " . 
                           date('H:i', strtotime($duplicate['start_time'])) . " to " . 
                           date('H:i', strtotime($duplicate['end_time'])) . 
                           " (" . htmlspecialchars($duplicate['topic']) . "). Please choose a different time.";
                echo json_encode(['success' => false, 'message' => $message]);
                exit();
            }
            
            // Update session
            $update = $conn->prepare("
                UPDATE tutor_sessions 
                SET start_time = ?, end_time = ?, location = ?, topic = ?, 
                    description = ?, max_capacity = ?, session_type = ?
                WHERE session_id = ?
            ");
            
            $update->execute([
                $start_time, $end_time, $location, $topic, 
                $description, $max_capacity, $session_type, $session_id
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Session updated successfully!']);
            exit();
            
        } catch(PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Error updating session: ' . $e->getMessage()]);
            exit();
        }
    }
    
    // Handle create request (existing code)
    $session_date = $_POST['session_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $location = trim($_POST['location']);
    $topic = trim($_POST['topic']);
    $description = trim($_POST['description']);
    $max_capacity = intval($_POST['max_capacity']);
    $session_type = $_POST['session_type'];
    
    // Validate end time is after start time
    if($end_time <= $start_time) {
        echo json_encode(['success' => false, 'message' => 'End time must be after start time.']);
        exit();
    }
    
    // Check for class schedule conflicts (only if table exists)
    $day_of_week = date('w', strtotime($session_date)); // 0=Sunday, 6=Saturday
    
    try {
        $conflict_check = $conn->prepare("
            SELECT ct.*, m.subject_code, m.subject_name
            FROM class_timetable ct
            INNER JOIN at_risk_modules arm ON ct.module_id = arm.module_id
            INNER JOIN modules m ON ct.module_id = m.module_id
            WHERE arm.risk_id = ?
            AND ct.day_of_week = ?
            AND ct.effective_from <= ?
            AND ct.effective_to >= ?
            AND (
                (ct.start_time < ? AND ct.end_time > ?) OR
                (ct.start_time < ? AND ct.end_time > ?) OR
                (ct.start_time >= ? AND ct.end_time <= ?)
            )
        ");
        
        $conflict_check->execute([
            $assignment['risk_module_id'],
            $day_of_week,
            $session_date,
            $session_date,
            $end_time, $start_time,
            $end_time, $start_time,
            $start_time, $end_time
        ]);
        
        $conflicts = $conflict_check->fetchAll();
        
        if(count($conflicts) > 0) {
            $conflict = $conflicts[0];
            $message = "Cannot schedule session during class time! Students have {$conflict['subject_code']} {$conflict['class_type']} from " . 
                       date('H:i', strtotime($conflict['start_time'])) . " to " . 
                       date('H:i', strtotime($conflict['end_time'])) . " at {$conflict['location']}.";
            echo json_encode(['success' => false, 'message' => $message]);
            exit();
        }
    } catch(PDOException $e) {
        // If table doesn't exist, skip conflict check
    }
    
    // Check for duplicate sessions (same tutor, same date, overlapping time)
    $duplicate_check = $conn->prepare("
        SELECT ts.topic, ts.start_time, ts.end_time
        FROM tutor_sessions ts
        INNER JOIN tutor_assignments ta ON ts.assignment_id = ta.assignment_id
        WHERE ta.tutor_id = ?
        AND ts.session_date = ?
        AND ts.status != 'cancelled'
        AND (
            (ts.start_time < ? AND ts.end_time > ?) OR
            (ts.start_time < ? AND ts.end_time > ?) OR
            (ts.start_time >= ? AND ts.end_time <= ?)
        )
    ");
    
    $duplicate_check->execute([
        $tutor_id,
        $session_date,
        $end_time, $start_time,
        $end_time, $start_time,
        $start_time, $end_time
    ]);
    
    $duplicates = $duplicate_check->fetchAll();
    
    if(count($duplicates) > 0) {
        $duplicate = $duplicates[0];
        $message = "You already have a session scheduled on this date from " . 
                   date('H:i', strtotime($duplicate['start_time'])) . " to " . 
                   date('H:i', strtotime($duplicate['end_time'])) . 
                   " (" . htmlspecialchars($duplicate['topic']) . "). Please choose a different time.";
        echo json_encode(['success' => false, 'message' => $message]);
        exit();
    }
    
    try {
        $insert = $conn->prepare("
            INSERT INTO tutor_sessions 
            (assignment_id, session_date, start_time, end_time, location, topic, 
             description, max_capacity, session_type, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled', NOW())
        ");
        
        $insert->execute([
            $assignment_id, $session_date, $start_time, $end_time, 
            $location, $topic, $description, $max_capacity, $session_type
        ]);
        
        $session_id = $conn->lastInsertId();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Session created successfully!',
            'session_id' => $session_id
        ]);
        exit();
        
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error creating session: ' . $e->getMessage()]);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Sessions - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .calendar-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .assignment-banner {
            background: linear-gradient(135deg, #000000 0%, #1d4ed8 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .assignment-banner h2 {
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        
        .assignment-banner p {
            margin: 0;
            opacity: 0.9;
        }
        
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 20px;
            background: white;
            border-radius: 12px;
            border: 2px solid #e5e7eb;
        }
        
        .calendar-nav {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .calendar-nav button {
            background: var(--blue);
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
        }
        
        .calendar-nav button:hover {
            background: #1e40af;
            transform: translateY(-2px);
        }
        
        .calendar-nav h3 {
            margin: 0;
            font-size: 20px;
            color: var(--dark);
            min-width: 200px;
            text-align: center;
        }
        
        .calendar-grid {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 2px solid #e5e7eb;
        }
        
        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .calendar-weekday {
            text-align: center;
            font-weight: 700;
            color: var(--dark);
            padding: 10px;
            font-size: 14px;
        }
        
        .calendar-days {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
        }
        
        .calendar-day {
            min-height: 120px;
            padding: 10px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        
        .calendar-day:hover {
            border-color: var(--blue);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .calendar-day.other-month {
            background: #f9fafb;
            opacity: 0.5;
        }
        
        .calendar-day.today {
            background: #eff6ff;
            border-color: var(--blue);
        }
        
        .calendar-day.past {
            background: #f9fafb;
            cursor: not-allowed;
        }
        
        .calendar-day.past:hover {
            border-color: #e5e7eb;
            box-shadow: none;
        }
        
        .day-number {
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .calendar-day.past .day-number {
            color: #9ca3af;
        }
        
        .session-dot {
            width: 100%;
            padding: 4px 6px;
            background: var(--blue);
            color: white;
            border-radius: 4px;
            font-size: 11px;
            margin-bottom: 3px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 4px;
            position: relative;
            min-height: 24px;
        }
        
        .session-dot:hover {
            transform: translateX(2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .session-dot.completed {
            background: var(--green);
        }
        
        .session-dot.cancelled {
            background: #dc2626;
        }
        
        .session-text {
            flex: 1;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            line-height: 1.2;
        }
        
        .session-actions {
            display: flex;
            gap: 3px;
            align-items: center;
            flex-shrink: 0;
        }
        
        .session-action-btn {
            background: rgba(255, 255, 255, 0.25);
            border: none;
            color: white;
            width: 18px;
            height: 18px;
            border-radius: 3px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9px;
            transition: all 0.2s;
            padding: 0;
            flex-shrink: 0;
        }
        
        .session-action-btn:hover {
            background: rgba(255, 255, 255, 0.4);
            transform: scale(1.15);
        }
        
        .session-action-btn.delete:hover {
            background: #dc2626;
        }
        
        .session-action-btn.edit:hover {
            background: #059669;
        }
        
        .class-time-indicator {
            width: 100%;
            padding: 4px 6px;
            background: #fef3c7;
            color: #92400e;
            border-radius: 4px;
            font-size: 10px;
            margin-bottom: 3px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            border: 1px solid #fbbf24;
        }
        
        .legend {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            padding: 15px;
            background: #f9fafb;
            border-radius: 8px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: #4b5563;
        }
        
        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 3px;
        }
        
        /* Modal Styles */
        .session-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 10000;
            overflow-y: auto;
        }
        
        .session-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        /* Message modal should appear on top of session modals */
        .modal-overlay {
            z-index: 10100 !important;
        }
        
        #messageModal {
            z-index: 10100 !important;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 700px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        
        .modal-header {
            padding: 25px 30px;
            border-bottom: 2px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            font-size: 22px;
            color: var(--dark);
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #6b7280;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .modal-close:hover {
            background: #f3f4f6;
            color: var(--dark);
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
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
            width: 20px;
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
        
        .form-group small {
            display: block;
            margin-top: 5px;
            font-size: 12px;
            color: #6b7280;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .modal-footer {
            padding: 20px 30px;
            border-top: 2px solid #e5e7eb;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .info-box {
            background: #eff6ff;
            border-left: 4px solid var(--blue);
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #4b5563;
        }
        
        /* Session View Modal */
        .session-info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .session-info-row:last-child {
            border-bottom: none;
        }
        
        .session-info-label {
            font-weight: 600;
            color: #6b7280;
            font-size: 13px;
        }
        
        .session-info-value {
            font-weight: 500;
            color: var(--dark);
            font-size: 13px;
            text-align: right;
        }
        
        .session-description-box {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            font-size: 13px;
            color: #4b5563;
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <div class="calendar-container">
                    <div style="margin-bottom: 20px;">
                        <a href="assignment-details.php?id=<?php echo $assignment_id; ?>" class="btn-link">
                            <i class="fas fa-arrow-left"></i> Back to Assignment Details
                        </a>
                    </div>

                    <!-- Assignment Banner -->
                    <div class="assignment-banner">
                        <div>
                            <h2><?php echo htmlspecialchars($assignment['subject_code']); ?> - <?php echo htmlspecialchars($assignment['subject_name']); ?></h2>
                            <p>
                                <?php echo htmlspecialchars($assignment['faculty']); ?> • 
                                <?php echo htmlspecialchars($assignment['campus']); ?> Campus • 
                                <?php echo $assignment['at_risk_students']; ?> at-risk students
                            </p>
                        </div>
                        <button onclick="showScheduleInfo()" class="btn btn-success" style="background: white; color: var(--blue); font-size: 16px; padding: 12px 24px;">
                            <i class="fas fa-plus-circle"></i> Schedule New Session
                        </button>
                    </div>

                    <!-- Calendar Header -->
                    <div class="calendar-header">
                        <div class="calendar-nav">
                            <button onclick="previousMonth()">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <h3 id="currentMonth"></h3>
                            <button onclick="nextMonth()">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                        <button onclick="goToToday()" class="btn btn-secondary">
                            <i class="fas fa-calendar-day"></i> Today
                        </button>
                    </div>

                    <!-- Calendar Grid -->
                    <div class="calendar-grid">
                        <div class="calendar-weekdays">
                            <div class="calendar-weekday">Mon</div>
                            <div class="calendar-weekday">Tue</div>
                            <div class="calendar-weekday">Wed</div>
                            <div class="calendar-weekday">Thu</div>
                            <div class="calendar-weekday">Fri</div>
                        </div>
                        <div class="calendar-days" id="calendarDays"></div>
                    </div>

                    <!-- Legend -->
                    <div class="legend">
                        <div class="legend-item">
                            <div class="legend-dot" style="background: var(--blue);"></div>
                            <span>Scheduled Session</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-dot" style="background: var(--green);"></div>
                            <span>Completed Session</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-dot" style="background: #dc2626;"></div>
                            <span>Cancelled Session</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-dot" style="background: #fef3c7; border: 1px solid #fbbf24;"></div>
                            <span>Class Time (Unavailable)</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-dot" style="background: #eff6ff; border: 2px solid var(--blue);"></div>
                            <span>Today</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Session Modal -->
    <div class="session-modal" id="sessionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-calendar-plus"></i> Schedule New Session</h3>
                <button class="modal-close" onclick="closeSessionModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="sessionForm">
                <div class="modal-body">
                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        Students will be able to view and register for this session. They are NOT automatically registered.
                    </div>

                    <div class="info-box" style="background: #fef3c7; border-left-color: #f59e0b;">
                        <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i>
                        <strong>Avoid Class Times:</strong> Do not schedule sessions during regular class hours when students are attending lectures or practicals.
                        <div id="classTimes" style="margin-top: 10px; font-size: 12px;"></div>
                    </div>

                    <input type="hidden" name="session_date" id="sessionDate">

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> Start Time</label>
                            <input type="time" name="start_time" id="startTime" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> End Time</label>
                            <input type="time" name="end_time" id="endTime" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Location</label>
                            <input type="text" name="location" 
                                   value="<?php echo htmlspecialchars($assignment['location']); ?>" 
                                   required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-users"></i> Max Capacity</label>
                            <input type="number" name="max_capacity" 
                                   min="1" 
                                   max="<?php echo $assignment['max_students']; ?>"
                                   value="<?php echo $assignment['max_students']; ?>" 
                                   required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Session Type</label>
                        <select name="session_type" required>
                            <option value="group">Group Session</option>
                            <option value="workshop">Workshop</option>
                            <option value="review">Review Session</option>
                            <option value="practice">Practice Session</option>
                            <option value="exam_prep">Exam Preparation</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-book"></i> Session Topic</label>
                        <input type="text" name="topic" 
                               placeholder="e.g., Introduction to Variables and Data Types" 
                               maxlength="200"
                               required>
                        <small>Brief title for this session</small>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Description (Optional)</label>
                        <textarea name="description" 
                                  placeholder="Describe what will be covered, learning objectives, what students should bring..."
                                  rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeSessionModal()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Create Session
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Session View Modal -->
    <div class="session-modal" id="sessionViewModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="viewModalTitle"><i class="fas fa-calendar-check"></i> Session Details</h3>
                <button class="modal-close" onclick="closeSessionViewModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="sessionViewBody">
                <!-- Session details will be loaded here -->
            </div>
            <div class="modal-footer" id="sessionViewFooter">
                <button type="button" onclick="closeSessionViewModal()" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Close
                </button>
                <a href="#" id="markAttendanceBtn" class="btn btn-success" style="display: none;">
                    <i class="fas fa-clipboard-check"></i> Mark Attendance
                </a>
                <button type="button" id="editSessionBtn" onclick="editSession()" class="btn btn-primary" style="display: none;">
                    <i class="fas fa-edit"></i> Edit Session
                </button>
                <button type="button" id="deleteSessionBtn" onclick="deleteSession()" class="btn btn-danger" style="display: none;">
                    <i class="fas fa-trash"></i> Delete Session
                </button>
                <a href="#" id="viewSessionDetailsBtn" class="btn btn-primary">
                    <i class="fas fa-eye"></i> View Full Details
                </a>
            </div>
        </div>
    </div>

    <!-- Edit Session Modal -->
    <div class="session-modal" id="editSessionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Session</h3>
                <button class="modal-close" onclick="closeEditSessionModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="editSessionForm">
                <div class="modal-body">
                    <input type="hidden" name="session_id" id="editSessionId">
                    <input type="hidden" name="session_date" id="editSessionDate">

                    <div class="info-box" style="background: #fef3c7; border-left-color: #f59e0b;">
                        <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i>
                        <strong>Note:</strong> Editing this session will notify all registered students of the changes.
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> Start Time</label>
                            <input type="time" name="start_time" id="editStartTime" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-clock"></i> End Time</label>
                            <input type="time" name="end_time" id="editEndTime" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-map-marker-alt"></i> Location</label>
                            <input type="text" name="location" id="editLocation" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-users"></i> Max Capacity</label>
                            <input type="number" name="max_capacity" id="editMaxCapacity" min="1" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Session Type</label>
                        <select name="session_type" id="editSessionType" required>
                            <option value="group">Group Session</option>
                            <option value="workshop">Workshop</option>
                            <option value="review">Review Session</option>
                            <option value="practice">Practice Session</option>
                            <option value="exam_prep">Exam Preparation</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-book"></i> Session Topic</label>
                        <input type="text" name="topic" id="editTopic" maxlength="200" required>
                    </div>

                    <div class="form-group">
                        <label><i class="fas fa-align-left"></i> Description (Optional)</label>
                        <textarea name="description" id="editDescription" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeEditSessionModal()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php include '../assets/includes/modals.php'; ?>
    <link rel="stylesheet" href="../assets/css/modals.css">
    <script src="../assets/js/modals.js"></script>
    <script src="js/dashboard.js"></script>
    <script>
        // Alias for showModal to match the modals.js function
        function showModal(title, message, type = 'info', callback = null) {
            showMessageModal(title, message, type);
            if(callback) {
                // Execute callback after a short delay when modal is closed
                setTimeout(() => {
                    document.getElementById('messageModal').addEventListener('click', function handler(e) {
                        if(e.target.classList.contains('btn-primary') || e.target.closest('.btn-primary')) {
                            callback();
                            this.removeEventListener('click', handler);
                        }
                    });
                }, 100);
            }
        }
        
        // Sessions data from PHP
        const sessions = <?php echo json_encode($existing_sessions); ?>;
        const classSchedule = <?php echo json_encode($class_schedule); ?>;
        const assignmentId = <?php echo $assignment_id; ?>;
        const assignmentEndDate = '<?php echo $assignment['end_date']; ?>';
        
        let currentDate = new Date();
        let selectedDate = null;
        
        console.log('Class Schedule:', classSchedule);
        
        // Initialize calendar
        function initCalendar() {
            renderCalendar();
        }
        
        // Render calendar
        function renderCalendar() {
            const year = currentDate.getFullYear();
            const month = currentDate.getMonth();
            
            // Update month display
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                              'July', 'August', 'September', 'October', 'November', 'December'];
            document.getElementById('currentMonth').textContent = `${monthNames[month]} ${year}`;
            
            // Get first day of month and number of days
            const firstDay = new Date(year, month, 1).getDay(); // 0=Sunday, 1=Monday, etc.
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            
            const calendarDays = document.getElementById('calendarDays');
            calendarDays.innerHTML = '';
            
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            // Calculate offset for Monday start (skip Sunday)
            let offset = firstDay === 0 ? 6 : firstDay - 1; // If Sunday, offset is 6, else offset is day-1
            
            // Add empty cells for days before month starts (only weekdays)
            for(let i = 0; i < offset; i++) {
                const emptyDiv = document.createElement('div');
                emptyDiv.className = 'calendar-day other-month';
                calendarDays.appendChild(emptyDiv);
            }
            
            // Current month days (only weekdays)
            for(let day = 1; day <= daysInMonth; day++) {
                const date = new Date(year, month, day);
                const dayOfWeek = date.getDay();
                
                // Skip weekends (0=Sunday, 6=Saturday)
                if(dayOfWeek === 0 || dayOfWeek === 6) {
                    continue;
                }
                
                date.setHours(0, 0, 0, 0);
                const isPast = date < today;
                const isToday = date.getTime() === today.getTime();
                
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                const daySessions = sessions.filter(s => s.session_date === dateStr);
                
                const dayDiv = createDayElement(day, false, daySessions, isPast, isToday, dateStr);
                calendarDays.appendChild(dayDiv);
            }
        }
        
        // Create day element
        function createDayElement(day, isOtherMonth, daySessions, isPast = false, isToday = false, dateStr = null) {
            const dayDiv = document.createElement('div');
            dayDiv.className = 'calendar-day';
            
            if(isOtherMonth) {
                dayDiv.classList.add('other-month');
            }
            if(isPast && !isOtherMonth) {
                dayDiv.classList.add('past');
            }
            if(isToday) {
                dayDiv.classList.add('today');
            }
            
            const dayNumber = document.createElement('div');
            dayNumber.className = 'day-number';
            dayNumber.textContent = day;
            dayDiv.appendChild(dayNumber);
            
            // Add class times for this day
            if(dateStr) {
                const dayOfWeek = new Date(dateStr).getDay();
                const dayClasses = classSchedule.filter(c => c.day_of_week == dayOfWeek);
                
                dayClasses.forEach(classTime => {
                    const classIndicator = document.createElement('div');
                    classIndicator.className = 'class-time-indicator';
                    classIndicator.textContent = `${classTime.start_time.substring(0, 5)} ${classTime.class_type}`;
                    classIndicator.title = `${classTime.class_type} - ${classTime.start_time.substring(0, 5)} to ${classTime.end_time.substring(0, 5)} at ${classTime.location}`;
                    dayDiv.appendChild(classIndicator);
                });
            }
            
            // Add sessions
            if(daySessions && daySessions.length > 0) {
                daySessions.forEach(session => {
                    const sessionDot = document.createElement('div');
                    sessionDot.className = `session-dot ${session.status}`;
                    
                    // Session text
                    const sessionText = document.createElement('span');
                    sessionText.className = 'session-text';
                    sessionText.textContent = `${session.start_time.substring(0, 5)} ${session.topic}`;
                    sessionDot.appendChild(sessionText);
                    
                    // Action buttons (only for scheduled sessions)
                    if(session.status === 'scheduled') {
                        const actionsDiv = document.createElement('div');
                        actionsDiv.className = 'session-actions';
                        
                        // Edit button
                        const editBtn = document.createElement('button');
                        editBtn.className = 'session-action-btn edit';
                        editBtn.innerHTML = '<i class="fas fa-edit"></i>';
                        editBtn.title = 'Edit session';
                        editBtn.onclick = (e) => {
                            e.stopPropagation();
                            viewSession(session.session_id);
                            // Auto-click edit after modal opens
                            setTimeout(() => {
                                document.getElementById('editSessionBtn').click();
                            }, 100);
                        };
                        
                        // Delete button
                        const deleteBtn = document.createElement('button');
                        deleteBtn.className = 'session-action-btn delete';
                        deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
                        deleteBtn.title = 'Delete session';
                        deleteBtn.onclick = (e) => {
                            e.stopPropagation();
                            currentSessionId = session.session_id;
                            deleteSession();
                        };
                        
                        actionsDiv.appendChild(editBtn);
                        actionsDiv.appendChild(deleteBtn);
                        sessionDot.appendChild(actionsDiv);
                    }
                    
                    sessionDot.title = `Click to view: ${session.topic} - ${session.start_time.substring(0, 5)}`;
                    sessionDot.onclick = (e) => {
                        // Only trigger if not clicking on action buttons
                        if(!e.target.closest('.session-action-btn')) {
                            e.stopPropagation();
                            viewSession(session.session_id);
                        }
                    };
                    
                    dayDiv.appendChild(sessionDot);
                });
            }
            
            // Click handler for non-past days
            if(!isPast && !isOtherMonth && dateStr) {
                dayDiv.onclick = () => {
                    selectedDate = dateStr;
                    openSessionModal(dateStr);
                };
            }
            
            return dayDiv;
        }
        
        // Show info about how to schedule
        function showScheduleInfo() {
            showMessageModal(
                'How to Schedule a Session',
                'Click on any future weekday in the calendar below to schedule a session for that date. Weekends and past dates are not available.',
                'info'
            );
        }
        
        // View session details in modal
        let currentSessionId = null;
        
        function viewSession(sessionId) {
            const session = sessions.find(s => s.session_id == sessionId);
            if(!session) return;
            
            currentSessionId = sessionId;
            
            const modal = document.getElementById('sessionViewModal');
            const body = document.getElementById('sessionViewBody');
            
            // Format date
            const date = new Date(session.session_date);
            const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                              'July', 'August', 'September', 'October', 'November', 'December'];
            const formattedDate = `${dayNames[date.getDay()]}, ${monthNames[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`;
            
            // Update title
            document.getElementById('viewModalTitle').innerHTML = `<i class="fas fa-calendar-check"></i> ${session.topic}`;
            
            // Status badge
            const statusColors = {
                'scheduled': 'var(--blue)',
                'completed': 'var(--green)',
                'cancelled': '#dc2626'
            };
            const statusColor = statusColors[session.status] || 'var(--blue)';
            
            // Build content
            let html = `
                <div style="margin-bottom: 20px;">
                    <span class="badge" style="background: ${statusColor}; color: white; padding: 6px 12px; border-radius: 12px; font-size: 12px; font-weight: 700; text-transform: uppercase;">
                        ${session.status}
                    </span>
                </div>
                
                <div class="session-info-row">
                    <span class="session-info-label"><i class="fas fa-calendar"></i> Date</span>
                    <span class="session-info-value">${formattedDate}</span>
                </div>
                
                <div class="session-info-row">
                    <span class="session-info-label"><i class="fas fa-clock"></i> Time</span>
                    <span class="session-info-value">${session.start_time.substring(0, 5)} - ${session.end_time.substring(0, 5)}</span>
                </div>
                
                <div class="session-info-row">
                    <span class="session-info-label"><i class="fas fa-map-marker-alt"></i> Location</span>
                    <span class="session-info-value">${session.location}</span>
                </div>
                
                <div class="session-info-row">
                    <span class="session-info-label"><i class="fas fa-tag"></i> Type</span>
                    <span class="session-info-value">${session.session_type.replace('_', ' ')}</span>
                </div>
                
                <div class="session-info-row">
                    <span class="session-info-label"><i class="fas fa-users"></i> Capacity</span>
                    <span class="session-info-value">${session.registered_count || 0} / ${session.max_capacity}</span>
                </div>
            `;
            
            if(session.description) {
                html += `
                    <div class="session-description-box">
                        <strong style="color: var(--dark); display: block; margin-bottom: 8px;">
                            <i class="fas fa-align-left"></i> Description:
                        </strong>
                        ${session.description.replace(/\n/g, '<br>')}
                    </div>
                `;
            }
            
            body.innerHTML = html;
            
            // Update button link
            document.getElementById('viewSessionDetailsBtn').href = `session-details.php?id=${sessionId}`;
            document.getElementById('markAttendanceBtn').href = `mark-attendance.php?session_id=${sessionId}`;
            
            // Show/hide edit and delete buttons based on status
            const editBtn = document.getElementById('editSessionBtn');
            const deleteBtn = document.getElementById('deleteSessionBtn');
            const markAttendanceBtn = document.getElementById('markAttendanceBtn');
            
            if(session.status === 'scheduled') {
                editBtn.style.display = 'inline-flex';
                deleteBtn.style.display = 'inline-flex';
                markAttendanceBtn.style.display = 'inline-flex';
            } else {
                editBtn.style.display = 'none';
                deleteBtn.style.display = 'none';
                // Show mark attendance for completed sessions too (to view attendance)
                if(session.status === 'completed') {
                    markAttendanceBtn.style.display = 'inline-flex';
                    markAttendanceBtn.innerHTML = '<i class="fas fa-clipboard-check"></i> View Attendance';
                } else {
                    markAttendanceBtn.style.display = 'none';
                }
            }
            
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeSessionViewModal() {
            const modal = document.getElementById('sessionViewModal');
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
            currentSessionId = null;
        }
        
        // Edit session
        function editSession() {
            const session = sessions.find(s => s.session_id == currentSessionId);
            if(!session) return;
            
            // Close view modal
            closeSessionViewModal();
            
            // Open edit modal with session data
            document.getElementById('editSessionId').value = session.session_id;
            document.getElementById('editSessionDate').value = session.session_date;
            document.getElementById('editStartTime').value = session.start_time.substring(0, 5);
            document.getElementById('editEndTime').value = session.end_time.substring(0, 5);
            document.getElementById('editLocation').value = session.location;
            document.getElementById('editMaxCapacity').value = session.max_capacity;
            document.getElementById('editSessionType').value = session.session_type;
            document.getElementById('editTopic').value = session.topic;
            document.getElementById('editDescription').value = session.description || '';
            
            document.getElementById('editSessionModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeEditSessionModal() {
            document.getElementById('editSessionModal').classList.remove('active');
            document.body.style.overflow = 'auto';
            document.getElementById('editSessionForm').reset();
        }
        
        // Delete session
        function deleteSession() {
            const session = sessions.find(s => s.session_id == currentSessionId);
            if(!session) return;
            
            const registeredCount = session.registered_count || 0;
            let message = 'Are you sure you want to delete this session? This action cannot be undone.';
            
            if(registeredCount > 0) {
                message = `<p style="margin-bottom: 15px;">This session has <strong>${registeredCount} registered student(s)</strong>.</p>
                          <p style="margin-bottom: 15px;">Deleting it will:</p>
                          <ul style="text-align: left; margin: 0 auto; max-width: 400px; line-height: 1.8;">
                              <li>Remove the session from the calendar</li>
                              <li>Notify all registered students</li>
                              <li>Cancel all registrations</li>
                          </ul>
                          <p style="margin-top: 15px; color: #dc2626; font-weight: 600;">This action cannot be undone. Are you sure?</p>`;
            }
            
            // Create custom confirmation modal (don't close session view modal yet)
            const modal = document.getElementById('messageModal');
            document.getElementById('messageTitle').innerHTML = '<i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i> Confirm Delete Session';
            document.getElementById('messageContent').innerHTML = `
                <div style="text-align: center;">
                    ${message}
                    <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
                        <button onclick="closeMessageModal()" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button onclick="confirmDeleteSession()" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Yes, Delete Session
                        </button>
                    </div>
                </div>
            `;
            modal.classList.add('active');
            
            // Prevent body scroll
            document.body.style.overflow = 'hidden';
        }
        
        async function confirmDeleteSession() {
            closeMessageModal();
            
            // Show loading modal
            const modal = document.getElementById('messageModal');
            document.getElementById('messageTitle').innerHTML = '<i class="fas fa-spinner fa-spin" style="color: var(--blue);"></i> Deleting Session...';
            document.getElementById('messageContent').innerHTML = '<p>Please wait while we delete the session...</p>';
            modal.classList.add('active');
            
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'delete');
            formData.append('session_id', currentSessionId);
            
            try {
                const response = await fetch('create-session.php?assignment_id=' + assignmentId, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if(result.success) {
                    // Close session view modal first
                    closeSessionViewModal();
                    
                    // Show success modal
                    document.getElementById('messageTitle').innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i> Session Deleted Successfully!';
                    document.getElementById('messageContent').innerHTML = `
                        <p style="margin-bottom: 15px;">The session has been deleted and removed from the calendar.</p>
                        <p style="color: #6b7280; font-size: 14px;">Registered students have been notified of the cancellation.</p>
                        <div style="margin-top: 20px;">
                            <button onclick="window.location.reload()" class="btn btn-success">
                                <i class="fas fa-sync"></i> Refresh Calendar
                            </button>
                        </div>
                    `;
                    
                    // Auto-reload after 2 seconds
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    document.getElementById('messageTitle').innerHTML = '<i class="fas fa-exclamation-circle" style="color: #dc2626;"></i> Error';
                    document.getElementById('messageContent').innerHTML = `
                        <p>${result.message}</p>
                        <div style="margin-top: 20px;">
                            <button onclick="closeMessageModal()" class="btn btn-primary">
                                <i class="fas fa-times"></i> Close
                            </button>
                        </div>
                    `;
                }
            } catch(error) {
                document.getElementById('messageTitle').innerHTML = '<i class="fas fa-exclamation-circle" style="color: #dc2626;"></i> Error';
                document.getElementById('messageContent').innerHTML = `
                    <p>Failed to delete session. Please try again.</p>
                    <div style="margin-top: 20px;">
                        <button onclick="closeMessageModal()" class="btn btn-primary">
                            <i class="fas fa-times"></i> Close
                        </button>
                    </div>
                `;
            }
        }
        
        // Navigation functions
        function previousMonth() {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
        }
        
        function nextMonth() {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
        }
        
        function goToToday() {
            currentDate = new Date();
            renderCalendar();
        }
        
        // Modal functions
        function openSessionModal(date = null) {
            if(!date) {
                showMessageModal('Select a Date', 'Please click on a date in the calendar to schedule a session.', 'info');
                return;
            }
            
            // Check if date is in the past
            const selectedDate = new Date(date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            if(selectedDate < today) {
                showMessageModal('Invalid Date', 'Cannot schedule sessions in the past. Please select a future date.', 'error');
                return;
            }
            
            // Check if it's a weekend
            const dayOfWeek = selectedDate.getDay();
            if(dayOfWeek === 0 || dayOfWeek === 6) {
                showMessageModal('Weekend Not Allowed', 'Cannot schedule sessions on weekends. Please select a weekday.', 'error');
                return;
            }
            
            const modal = document.getElementById('sessionModal');
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Set the hidden date field
            document.getElementById('sessionDate').value = date;
            
            // Update modal title to show selected date
            const dateObj = new Date(date);
            const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                              'July', 'August', 'September', 'October', 'November', 'December'];
            const formattedDate = `${dayNames[dateObj.getDay()]}, ${monthNames[dateObj.getMonth()]} ${dateObj.getDate()}, ${dateObj.getFullYear()}`;
            
            document.querySelector('.modal-header h3').innerHTML = `<i class="fas fa-calendar-plus"></i> Schedule Session - ${formattedDate}`;
            
            updateClassTimesDisplay(date);
            
            // Suggest times
            document.getElementById('startTime').value = '14:00';
            document.getElementById('endTime').value = '16:00';
        }
        
        // Update class times display when date changes
        function updateClassTimesDisplay(dateStr) {
            const date = new Date(dateStr);
            const dayOfWeek = date.getDay();
            const dayClasses = classSchedule.filter(c => c.day_of_week == dayOfWeek);
            
            const classTimesDiv = document.getElementById('classTimes');
            
            if(dayClasses.length > 0) {
                const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                let html = `<strong>${dayNames[dayOfWeek]} Class Schedule:</strong><br>`;
                dayClasses.forEach(classTime => {
                    html += `• ${classTime.start_time.substring(0, 5)} - ${classTime.end_time.substring(0, 5)}: ${classTime.class_type} at ${classTime.location}<br>`;
                });
                classTimesDiv.innerHTML = html;
            } else {
                classTimesDiv.innerHTML = '<strong style="color: #10b981;">✓ No classes scheduled on this day - Perfect time for sessions!</strong>';
            }
        }
        
        // Listen for date changes (removed since date field is now hidden)
        // document.getElementById('sessionDate').addEventListener('change', function() {
        //     updateClassTimesDisplay(this.value);
        // });
        
        function closeSessionModal() {
            const modal = document.getElementById('sessionModal');
            modal.classList.remove('active');
            document.body.style.overflow = 'auto';
            document.getElementById('sessionForm').reset();
            
            // Reset modal title
            document.querySelector('.modal-header h3').innerHTML = '<i class="fas fa-calendar-plus"></i> Schedule New Session';
        }
        
        // Form submission
        document.getElementById('sessionForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('ajax', '1');
            
            // Validate times
            const startTime = formData.get('start_time');
            const endTime = formData.get('end_time');
            
            if(endTime <= startTime) {
                showMessageModal('Invalid Time', 'End time must be after start time.', 'error');
                return;
            }
            
            // Check duration
            const start = new Date('2000-01-01 ' + startTime);
            const end = new Date('2000-01-01 ' + endTime);
            const diffMinutes = (end - start) / (1000 * 60);
            
            if(diffMinutes < 30) {
                showMessageModal('Session Too Short', 'Sessions should be at least 30 minutes long.', 'warning');
                return;
            }
            
            if(diffMinutes > 240) {
                showMessageModal('Session Too Long', 'Sessions longer than 4 hours may be too exhausting. Consider breaking it into multiple sessions.', 'warning');
                return;
            }
            
            // Disable submit button
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
            
            try {
                const response = await fetch('create-session.php?assignment_id=' + assignmentId, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if(result.success) {
                    closeSessionModal();
                    
                    // Show success modal with options
                    const successModal = document.getElementById('messageModal');
                    document.getElementById('messageTitle').innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i> Session Created Successfully!';
                    document.getElementById('messageContent').innerHTML = `
                        <p style="margin-bottom: 15px;">Your session has been created and is now visible to students.</p>
                        <div style="display: flex; gap: 10px; justify-content: center;">
                            <button onclick="window.location.href='session-details.php?id=${result.session_id}'" class="btn btn-primary">
                                <i class="fas fa-eye"></i> View Session
                            </button>
                            <button onclick="window.location.reload()" class="btn btn-success">
                                <i class="fas fa-calendar-plus"></i> Schedule Another
                            </button>
                        </div>
                    `;
                    successModal.classList.add('active');
                } else {
                    showMessageModal('Error', result.message, 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            } catch(error) {
                showMessageModal('Error', 'Failed to create session. Please try again.', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
        
        // Edit form submission
        document.getElementById('editSessionForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('ajax', '1');
            formData.append('action', 'edit');
            
            // Validate times
            const startTime = formData.get('start_time');
            const endTime = formData.get('end_time');
            
            if(endTime <= startTime) {
                showMessageModal('Invalid Time', 'End time must be after start time.', 'error');
                return;
            }
            
            // Disable submit button
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            try {
                const response = await fetch('create-session.php?assignment_id=' + assignmentId, {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if(result.success) {
                    closeEditSessionModal();
                    
                    // Show success modal
                    const modal = document.getElementById('messageModal');
                    document.getElementById('messageTitle').innerHTML = '<i class="fas fa-check-circle" style="color: #10b981;"></i> Session Updated Successfully!';
                    document.getElementById('messageContent').innerHTML = `
                        <p style="margin-bottom: 15px;">Your changes have been saved and the session has been updated.</p>
                        <p style="color: #6b7280; font-size: 14px;">Registered students have been notified of the changes.</p>
                        <div style="margin-top: 20px;">
                            <button onclick="window.location.reload()" class="btn btn-success">
                                <i class="fas fa-sync"></i> Refresh Calendar
                            </button>
                        </div>
                    `;
                    modal.classList.add('active');
                    
                    // Auto-reload after 2 seconds
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showMessageModal('Error', result.message, 'error');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            } catch(error) {
                showMessageModal('Error', 'Failed to update session. Please try again.', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
        
        // Close modal on outside click
        document.getElementById('sessionModal').addEventListener('click', function(e) {
            if(e.target === this) {
                closeSessionModal();
            }
        });
        
        document.getElementById('sessionViewModal').addEventListener('click', function(e) {
            if(e.target === this) {
                closeSessionViewModal();
            }
        });
        
        document.getElementById('editSessionModal').addEventListener('click', function(e) {
            if(e.target === this) {
                closeEditSessionModal();
            }
        });
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', initCalendar);
    </script>
</body>
</html>
