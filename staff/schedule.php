<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION['staff_id'])) {
    header('Location: ../staff-login.php');
    exit();
}

$role = $_SESSION['role'] ?? 'staff';
$staff_id = $_SESSION['staff_id'];

// Determine if this is a tutor/PAL
$is_tutor = in_array($role, ['tutor', 'pal']);

// Only tutors and PALs can access this tutoring schedule page
if(!$is_tutor) {
    header('Location: index.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// FOR TUTORS/PALS: Continue with tutoring session schedule below
$tutor_id = $staff_id;

// Get selected assignment (if any)
$selected_assignment_id = isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : 0;

// Get all active assignments for this tutor
$assignments_query = $conn->prepare("
    SELECT 
        ta.assignment_id,
        ta.tutor_type,
        m.subject_code, 
        m.subject_name, 
        m.faculty, 
        m.campus,
        arm.at_risk_students
    FROM tutor_assignments ta
    INNER JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
    INNER JOIN modules m ON arm.module_id = m.module_id
    WHERE ta.tutor_id = ? AND ta.status = 'active'
    ORDER BY m.subject_code
");
$assignments_query->execute([$tutor_id]);
$all_assignments = $assignments_query->fetchAll();

// If no assignment selected but user has assignments, select the first one
if($selected_assignment_id == 0 && count($all_assignments) > 0) {
    $selected_assignment_id = $all_assignments[0]['assignment_id'];
}

// Get selected assignment details
$selected_assignment = null;
if($selected_assignment_id > 0) {
    foreach($all_assignments as $assignment) {
        if($assignment['assignment_id'] == $selected_assignment_id) {
            $selected_assignment = $assignment;
            break;
        }
    }
}

// Get current month and year
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// South African Public Holidays
function getSAHolidays($year) {
    $holidays = [
        "$year-01-01" => "New Year's Day",
        "$year-03-21" => "Human Rights Day",
        "$year-04-27" => "Freedom Day",
        "$year-05-01" => "Workers' Day",
        "$year-06-16" => "Youth Day",
        "$year-08-09" => "National Women's Day",
        "$year-09-24" => "Heritage Day",
        "$year-12-16" => "Day of Reconciliation",
        "$year-12-25" => "Christmas Day",
        "$year-12-26" => "Day of Goodwill"
    ];
    
    // Calculate Easter-based holidays (Good Friday and Family Day)
    $easter = easter_date($year);
    $good_friday = date('Y-m-d', strtotime('-2 days', $easter));
    $family_day = date('Y-m-d', strtotime('+1 day', $easter));
    
    $holidays[$good_friday] = "Good Friday";
    $holidays[$family_day] = "Family Day";
    
    // If holiday falls on Sunday, Monday is observed
    foreach($holidays as $date => $name) {
        if(date('w', strtotime($date)) == 0) { // Sunday
            $observed = date('Y-m-d', strtotime('+1 day', strtotime($date)));
            if(!isset($holidays[$observed])) {
                $holidays[$observed] = $name . " (Observed)";
            }
        }
    }
    
    return $holidays;
}

$sa_holidays = getSAHolidays($year);

// Calculate previous and next month
$prev_month = $month - 1;
$prev_year = $year;
if($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// Get first and last day of month
$first_day = date('Y-m-01', strtotime("$year-$month-01"));
$last_day = date('Y-m-t', strtotime("$year-$month-01"));

// Get all sessions for the selected assignment
if($selected_assignment_id > 0) {
    $sessions = $conn->prepare("
        SELECT 
            ts.*,
            m.subject_code, m.subject_name,
            COUNT(sr.registration_id) as registered_students
        FROM tutor_sessions ts
        INNER JOIN tutor_assignments ta ON ts.assignment_id = ta.assignment_id
        INNER JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
        INNER JOIN modules m ON arm.module_id = m.module_id
        LEFT JOIN session_registrations sr ON ts.session_id = sr.session_id
        WHERE ta.assignment_id = ? AND ts.session_date BETWEEN ? AND ?
        GROUP BY ts.session_id
        ORDER BY ts.session_date, ts.start_time
    ");
    $sessions->execute([$selected_assignment_id, $first_day, $last_day]);
    $all_sessions = $sessions->fetchAll();
} else {
    $all_sessions = [];
}

// Check if user has any active assignments
$has_assignments = count($all_assignments) > 0;

// Handle AJAX session creation
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax']) && $selected_assignment_id > 0) {
    header('Content-Type: application/json');
    
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
    
    // Check for duplicate sessions
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
            $selected_assignment_id, $session_date, $start_time, $end_time, 
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

// Organize sessions by date
$sessions_by_date = [];
foreach($all_sessions as $session) {
    $date = $session['session_date'];
    if(!isset($sessions_by_date[$date])) {
        $sessions_by_date[$date] = [];
    }
    $sessions_by_date[$date][] = $session;
}

// Get calendar data
$first_day_of_month = date('N', strtotime($first_day)); // 1 (Monday) to 7 (Sunday)
$days_in_month = date('t', strtotime($first_day));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 12px;
        }
        
        .calendar-nav {
            display: flex;
            gap: 10px;
        }
        
        .calendar-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--dark);
        }
        
        .calendar-grid {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .weekday {
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
            background: white;
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
        
        .calendar-day.holiday {
            background: #fef3c7;
        }
        
        .calendar-day.holiday .day-number {
            color: #f59e0b;
        }
        
        .holiday-name {
            font-size: 10px;
            color: #f59e0b;
            font-weight: 600;
            margin-top: 2px;
            line-height: 1.2;
        }
        
        .day-number {
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 5px;
        }
        
        .calendar-day.past .day-number {
            color: #9ca3af;
        }
        
        .calendar-day.today .day-number {
            background: var(--blue);
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
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
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .session-dot:hover {
            transform: translateX(2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .session-dot.scheduled {
            background: var(--blue);
        }
        
        .session-dot.completed {
            background: var(--green);
        }
        
        .session-dot.cancelled {
            background: #dc2626;
        }
        
        .legend {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            padding: 15px;
            background: white;
            border-radius: 12px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .legend-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
        
        .upcoming-sessions {
            margin-top: 30px;
        }
        
        .session-list-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid var(--blue);
        }
        
        .session-list-item.completed {
            border-left-color: var(--green);
        }
        
        .session-list-item.cancelled {
            border-left-color: #dc2626;
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
                    <h1><i class="fas fa-calendar"></i> Schedule</h1>
                    <p>View your tutoring session calendar</p>
                </div>

                <!-- Info Message - Only show if user has assignments -->
                <?php if($has_assignments): ?>
                    <!-- Assignment Selector -->
                    <div style="margin-bottom: 20px; padding: 20px; background: white; border-radius: 12px; border: 2px solid #e5e7eb;">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px;">
                            <div style="flex: 1;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--dark);">
                                    <i class="fas fa-book"></i> Select Module Assignment:
                                </label>
                                <select onchange="window.location.href='?assignment_id=' + this.value" 
                                        style="width: 100%; padding: 12px 15px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px;">
                                    <?php foreach($all_assignments as $assignment): ?>
                                        <option value="<?php echo $assignment['assignment_id']; ?>" 
                                                <?php echo $assignment['assignment_id'] == $selected_assignment_id ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($assignment['subject_code']); ?> - 
                                            <?php echo htmlspecialchars($assignment['subject_name']); ?> 
                                            (<?php echo ucfirst($assignment['tutor_type']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if($selected_assignment): ?>
                                <button onclick="showScheduleModal()" class="btn btn-success" style="white-space: nowrap;">
                                    <i class="fas fa-info-circle"></i> How to Schedule
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php if($selected_assignment): ?>
                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 14px;">
                                <i class="fas fa-info-circle"></i> 
                                <strong><?php echo $selected_assignment['at_risk_students']; ?></strong> at-risk students • 
                                <strong><?php echo $selected_assignment['campus']; ?></strong> Campus • 
                                Click any weekday on the calendar to schedule a session
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div style="margin-bottom: 20px; padding: 15px 20px; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
                        <i class="fas fa-info-circle" style="color: #f59e0b;"></i> 
                        <strong>No Assignments Yet:</strong> 
                        You don't have any module assignments yet. Coordinators will assign you to modules that need tutoring support.
                        Once assigned, you can create sessions from <a href="my-assignments.php" style="color: #f59e0b; text-decoration: underline; font-weight: 600;">My Assignments</a>.
                    </div>
                <?php endif; ?>

                <!-- Calendar Header -->
                <div class="calendar-header">
                    <div class="calendar-title" id="currentMonth"></div>
                    <div class="calendar-nav">
                        <button onclick="previousMonth()" class="btn btn-secondary">
                            <i class="fas fa-chevron-left"></i> Previous
                        </button>
                        <button onclick="goToToday()" class="btn btn-primary">
                            <i class="fas fa-calendar-day"></i> Today
                        </button>
                        <button onclick="nextMonth()" class="btn btn-secondary">
                            Next <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Calendar Grid -->
                <div class="calendar-grid">
                    <div class="calendar-weekdays">
                        <div class="weekday">Mon</div>
                        <div class="weekday">Tue</div>
                        <div class="weekday">Wed</div>
                        <div class="weekday">Thu</div>
                        <div class="weekday">Fri</div>
                    </div>
                    <div class="calendar-days" id="calendarDays"></div>
                </div>

                <!-- Legend -->
                <div class="legend">
                    <div class="legend-item">
                        <div class="legend-dot" style="background: #1e40af;"></div>
                        <span>Scheduled</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-dot" style="background: #065f46;"></div>
                        <span>Completed</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-dot" style="background: #991b1b;"></div>
                        <span>Cancelled</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-dot" style="background: #f59e0b;"></div>
                        <span>Public Holiday</span>
                    </div>
                </div>

                <!-- Upcoming Sessions List -->
                <?php
                $upcoming = $conn->prepare("
                    SELECT 
                        ts.*,
                        m.subject_code, m.subject_name,
                        COUNT(sr.registration_id) as registered_students
                    FROM tutor_sessions ts
                    INNER JOIN tutor_assignments ta ON ts.assignment_id = ta.assignment_id
                    INNER JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
                    INNER JOIN modules m ON arm.module_id = m.module_id
                    LEFT JOIN session_registrations sr ON ts.session_id = sr.session_id
                    WHERE ta.tutor_id = ? AND ts.session_date >= CURDATE() AND ts.status = 'scheduled'
                    GROUP BY ts.session_id
                    ORDER BY ts.session_date, ts.start_time
                    LIMIT 5
                ");
                $upcoming->execute([$tutor_id]);
                $upcoming_sessions = $upcoming->fetchAll();
                ?>
                
                <?php if(count($upcoming_sessions) > 0): ?>
                    <div class="upcoming-sessions">
                        <h2 class="section-title"><i class="fas fa-clock"></i> Upcoming Sessions</h2>
                        <?php foreach($upcoming_sessions as $session): ?>
                            <div class="session-list-item <?php echo $session['status']; ?>">
                                <div>
                                    <strong><?php echo htmlspecialchars($session['topic']); ?></strong>
                                    <br>
                                    <small style="color: #6b7280;">
                                        <?php echo htmlspecialchars($session['subject_code']); ?> • 
                                        <?php echo date('M j, Y', strtotime($session['session_date'])); ?> • 
                                        <?php echo date('g:i A', strtotime($session['start_time'])); ?> - 
                                        <?php echo date('g:i A', strtotime($session['end_time'])); ?> • 
                                        <?php echo htmlspecialchars($session['location']); ?> • 
                                        <?php echo $session['registered_students']; ?> registered
                                    </small>
                                </div>
                                <a href="session-details.php?id=<?php echo $session['session_id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include '../assets/includes/modals.php'; ?>
    
    <!-- Session Creation Modal (only show if user has assignment selected) -->
    <?php if($selected_assignment): ?>
    <div class="session-modal" id="sessionModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 10000; align-items: center; justify-content: center; padding: 20px;">
        <div class="modal-content" style="background: white; border-radius: 12px; max-width: 700px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);">
            <div class="modal-header" style="padding: 25px 30px; border-bottom: 2px solid #e5e7eb; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin: 0; font-size: 22px; color: var(--dark);"><i class="fas fa-calendar-plus"></i> Schedule New Session</h3>
                <button class="modal-close" onclick="closeSessionModal()" style="background: none; border: none; font-size: 24px; color: #6b7280; cursor: pointer; padding: 0; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border-radius: 6px;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="sessionForm">
                <div class="modal-body" style="padding: 30px;">
                    <div style="background: #eff6ff; border-left: 4px solid var(--blue); padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; color: #4b5563;">
                        <i class="fas fa-info-circle"></i>
                        Students enrolled in <strong><?php echo htmlspecialchars($selected_assignment['subject_code']); ?></strong> will be able to see and attend this session.
                    </div>

                    <input type="hidden" name="session_date" id="sessionDate">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: var(--dark); font-weight: 600; font-size: 14px;"><i class="fas fa-clock" style="margin-right: 8px; color: var(--blue); width: 20px;"></i> Start Time</label>
                            <input type="time" name="start_time" id="startTime" required style="width: 100%; padding: 12px 15px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px;">
                        </div>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: var(--dark); font-weight: 600; font-size: 14px;"><i class="fas fa-clock" style="margin-right: 8px; color: var(--blue); width: 20px;"></i> End Time</label>
                            <input type="time" name="end_time" id="endTime" required style="width: 100%; padding: 12px 15px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px;">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: var(--dark); font-weight: 600; font-size: 14px;"><i class="fas fa-map-marker-alt" style="margin-right: 8px; color: var(--blue); width: 20px;"></i> Location</label>
                            <input type="text" name="location" value="<?php echo htmlspecialchars($selected_assignment['campus']); ?> Campus" required style="width: 100%; padding: 12px 15px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px;">
                        </div>
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 8px; color: var(--dark); font-weight: 600; font-size: 14px;"><i class="fas fa-users" style="margin-right: 8px; color: var(--blue); width: 20px;"></i> Max Capacity</label>
                            <input type="number" name="max_capacity" min="1" value="20" required style="width: 100%; padding: 12px 15px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px;">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; color: var(--dark); font-weight: 600; font-size: 14px;"><i class="fas fa-tag" style="margin-right: 8px; color: var(--blue); width: 20px;"></i> Session Type</label>
                        <select name="session_type" required style="width: 100%; padding: 12px 15px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px;">
                            <option value="group">Group Session</option>
                            <option value="workshop">Workshop</option>
                            <option value="review">Review Session</option>
                            <option value="practice">Practice Session</option>
                            <option value="exam_prep">Exam Preparation</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; color: var(--dark); font-weight: 600; font-size: 14px;"><i class="fas fa-book" style="margin-right: 8px; color: var(--blue); width: 20px;"></i> Session Topic</label>
                        <input type="text" name="topic" placeholder="e.g., Introduction to Variables and Data Types" maxlength="200" required style="width: 100%; padding: 12px 15px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px;">
                        <small style="display: block; margin-top: 5px; font-size: 12px; color: #6b7280;">Brief title for this session</small>
                    </div>

                    <div class="form-group" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; color: var(--dark); font-weight: 600; font-size: 14px;"><i class="fas fa-align-left" style="margin-right: 8px; color: var(--blue); width: 20px;"></i> Description (Optional)</label>
                        <textarea name="description" placeholder="Describe what will be covered, learning objectives, what students should bring..." rows="3" style="width: 100%; padding: 12px 15px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 15px; resize: vertical; min-height: 80px; font-family: inherit;"></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 20px 30px; border-top: 2px solid #e5e7eb; display: flex; gap: 10px; justify-content: flex-end;">
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
    <?php endif; ?>
    
    <link rel="stylesheet" href="../assets/css/modals.css">
    <script src="../assets/js/modals.js"></script>
    <script src="js/dashboard.js"></script>

    <script>
        // Sessions data from PHP
        const sessions = <?php echo json_encode($all_sessions); ?>;
        const assignmentId = <?php echo $selected_assignment_id; ?>;
        const hasAssignments = <?php echo $has_assignments ? 'true' : 'false'; ?>;
        
        let currentDate = new Date();
        let selectedDate = null;
        
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
            
            // Add sessions
            if(daySessions && daySessions.length > 0) {
                daySessions.forEach(session => {
                    const sessionDot = document.createElement('div');
                    sessionDot.className = `session-dot ${session.status}`;
                    
                    const time = session.start_time.substring(0, 5);
                    const code = session.subject_code || '';
                    sessionDot.textContent = `${time} ${code}`;
                    sessionDot.title = `${session.topic} - ${time}`;
                    
                    sessionDot.onclick = (e) => {
                        e.stopPropagation();
                        window.location.href = `session-details.php?id=${session.session_id}`;
                    };
                    
                    dayDiv.appendChild(sessionDot);
                });
            }
            
            // Click handler for non-past days (only if user has assignments)
            if(!isPast && !isOtherMonth && dateStr && hasAssignments) {
                dayDiv.onclick = () => {
                    selectedDate = dateStr;
                    openSessionModal(dateStr);
                };
            }
            
            return dayDiv;
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
            if(!hasAssignments) {
                showMessageModal('No Assignments', 'You need to have module assignments before you can schedule sessions.', 'info');
                return;
            }
            
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
            if(!modal) {
                showMessageModal('Error', 'Session creation is not available. Please select a module assignment first.', 'error');
                return;
            }
            
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            
            // Set the date field
            const dateField = document.getElementById('sessionDate');
            if(dateField) {
                dateField.value = date;
            }
            
            // Update modal title to show selected date
            const dateObj = new Date(date);
            const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                              'July', 'August', 'September', 'October', 'November', 'December'];
            const formattedDate = `${dayNames[dateObj.getDay()]}, ${monthNames[dateObj.getMonth()]} ${dateObj.getDate()}, ${dateObj.getFullYear()}`;
            
            const modalTitle = document.querySelector('#sessionModal .modal-header h3');
            if(modalTitle) {
                modalTitle.innerHTML = `<i class="fas fa-calendar-plus"></i> Schedule Session - ${formattedDate}`;
            }
        }

        function showScheduleModal() {
            if(!hasAssignments) {
                showMessageModal('No Assignments', 'You need to have module assignments before you can schedule sessions.', 'info');
                return;
            }
            showMessageModal(
                'How to Schedule a Session',
                'Click on any future weekday in the calendar below to schedule a session for that date. Weekends and past dates are not available.',
                'info'
            );
        }

        function closeSessionModal() {
            const modal = document.getElementById('sessionModal');
            if(!modal) return;
            
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
            
            const form = document.getElementById('sessionForm');
            if(form) {
                form.reset();
            }
            
            // Reset modal title
            const modalTitle = document.querySelector('#sessionModal .modal-header h3');
            if(modalTitle) {
                modalTitle.innerHTML = '<i class="fas fa-calendar-plus"></i> Schedule New Session';
            }
        }

        // Form submission (only attach if form exists)
        const sessionForm = document.getElementById('sessionForm');
        if(sessionForm) {
            sessionForm.addEventListener('submit', async function(e) {
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
                
                // Disable submit button
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
                
                try {
                    const response = await fetch('schedule.php?assignment_id=' + assignmentId, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if(result.success) {
                        closeSessionModal();
                        showMessageModal('Success!', 'Session created successfully! Refreshing calendar...', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
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
        }

        // Close modal on outside click (only if modal exists)
        const sessionModal = document.getElementById('sessionModal');
        if(sessionModal) {
            sessionModal.addEventListener('click', function(e) {
                if(e.target === this) {
                    closeSessionModal();
                }
            });
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', initCalendar);
    </script>
</body>
</html>
