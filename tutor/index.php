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
$role = $_SESSION['role'];

// Get tutor statistics
$stats = $conn->prepare("
    SELECT 
        COUNT(DISTINCT ta.assignment_id) as total_assignments,
        COUNT(DISTINCT CASE WHEN ta.status = 'active' THEN ta.assignment_id END) as active_assignments,
        COUNT(DISTINCT ts.session_id) as total_sessions,
        COUNT(DISTINCT CASE WHEN ts.status = 'completed' THEN ts.session_id END) as completed_sessions,
        COUNT(DISTINCT CASE WHEN ts.status = 'scheduled' THEN ts.session_id END) as upcoming_sessions,
        COUNT(DISTINCT sr.registration_id) as total_students
    FROM tutor_assignments ta
    LEFT JOIN tutor_sessions ts ON ta.assignment_id = ts.assignment_id
    LEFT JOIN session_registrations sr ON ts.session_id = sr.session_id
    WHERE ta.tutor_id = ?
");
$stats->execute([$tutor_id]);
$statistics = $stats->fetch();

// Get active assignments
$assignments = $conn->prepare("
    SELECT 
        ta.*,
        m.subject_code, m.subject_name, m.faculty, m.campus,
        arm.at_risk_students
    FROM tutor_assignments ta
    INNER JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
    INNER JOIN modules m ON arm.module_id = m.module_id
    WHERE ta.tutor_id = ? AND ta.status = 'active'
    ORDER BY ta.assignment_date DESC
");
$assignments->execute([$tutor_id]);
$active_assignments = $assignments->fetchAll();

// Get upcoming sessions
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
    WHERE ta.tutor_id = ? AND ts.status = 'scheduled' AND ts.session_date >= CURDATE()
    GROUP BY ts.session_id
    ORDER BY ts.session_date ASC, ts.start_time ASC
    LIMIT 5
");
$upcoming->execute([$tutor_id]);
$upcoming_sessions = $upcoming->fetchAll();

// Get tutor profile
$profile = $conn->prepare("SELECT * FROM staff WHERE staff_id = ?");
$profile->execute([$tutor_id]);
$tutor = $profile->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($role); ?> Dashboard - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <div class="page-header">
                    <h1><i class="fas fa-home"></i> Dashboard</h1>
                    <p>Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</p>
                </div>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $statistics['active_assignments']; ?></h3>
                            <p>Active Assignments</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-chalkboard-teacher"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $statistics['total_sessions']; ?></h3>
                            <p>Total Sessions</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $statistics['upcoming_sessions']; ?></h3>
                            <p>Upcoming Sessions</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $statistics['total_students']; ?></h3>
                            <p>Students Reached</p>
                        </div>
                    </div>
                </div>

                <!-- Active Assignments -->
                <div class="section">
                    <h2 class="section-title"><i class="fas fa-book"></i> My Assignments</h2>
                    <?php if(count($active_assignments) > 0): ?>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Module</th>
                                        <th>Faculty</th>
                                        <th>Campus</th>
                                        <th>Students</th>
                                        <th>Period</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($active_assignments as $assignment): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($assignment['subject_code']); ?></strong><br>
                                                <small><?php echo htmlspecialchars($assignment['subject_name']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($assignment['faculty']); ?></td>
                                            <td><?php echo htmlspecialchars($assignment['campus']); ?></td>
                                            <td><?php echo $assignment['at_risk_students']; ?> students</td>
                                            <td>
                                                <?php echo date('M j', strtotime($assignment['start_date'])); ?> - 
                                                <?php echo date('M j, Y', strtotime($assignment['end_date'])); ?>
                                            </td>
                                            <td>
                                                <a href="assignment-details.php?id=<?php echo $assignment['assignment_id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard"></i>
                            <h3>No Active Assignments</h3>
                            <p>You don't have any active assignments yet.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Upcoming Sessions -->
                <div class="section">
                    <h2 class="section-title"><i class="fas fa-calendar-alt"></i> Upcoming Sessions</h2>
                    <?php if(count($upcoming_sessions) > 0): ?>
                        <div class="table-container">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Module</th>
                                        <th>Topic</th>
                                        <th>Location</th>
                                        <th>Registered</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($upcoming_sessions as $session): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo date('M j, Y', strtotime($session['session_date'])); ?></strong><br>
                                                <small><?php echo date('g:i A', strtotime($session['start_time'])); ?> - <?php echo date('g:i A', strtotime($session['end_time'])); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($session['subject_code']); ?></td>
                                            <td><?php echo htmlspecialchars($session['topic']); ?></td>
                                            <td><?php echo htmlspecialchars($session['location']); ?></td>
                                            <td><?php echo $session['registered_students']; ?> students</td>
                                            <td>
                                                <a href="session-details.php?id=<?php echo $session['session_id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No Upcoming Sessions</h3>
                            <p>You don't have any scheduled sessions.</p>
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
