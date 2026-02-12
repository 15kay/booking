<?php
session_start();
if(!isset($_SESSION['staff_id']) || $_SESSION['role'] != 'coordinator') {
    header('Location: ../staff-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

$tutor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get tutor details
$stmt = $conn->prepare("
    SELECT s.*,
           COUNT(DISTINCT ta.assignment_id) as total_assignments,
           COUNT(DISTINCT CASE WHEN ta.status = 'active' THEN ta.assignment_id END) as active_assignments,
           COUNT(DISTINCT ts.session_id) as total_sessions
    FROM staff s
    LEFT JOIN tutor_assignments ta ON s.staff_id = ta.tutor_id
    LEFT JOIN tutor_sessions ts ON ta.assignment_id = ts.assignment_id
    WHERE s.staff_id = ? AND s.role IN ('tutor', 'pal')
    GROUP BY s.staff_id
");
$stmt->execute([$tutor_id]);
$tutor = $stmt->fetch();

if(!$tutor) {
    header('Location: tutors.php?error=Tutor not found');
    exit();
}

// Get assigned modules
$assignments_stmt = $conn->prepare("
    SELECT ta.*, arm.*, m.subject_code, m.subject_name, m.faculty, m.campus
    FROM tutor_assignments ta
    JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
    JOIN modules m ON arm.module_id = m.module_id
    WHERE ta.tutor_id = ? AND ta.status = 'active'
    ORDER BY ta.assignment_date DESC
");
$assignments_stmt->execute([$tutor_id]);
$assignments = $assignments_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']); ?> - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tutor-profile-header {
            background: linear-gradient(135deg, #000000 0%, #1d4ed8 100%);
            color: white;
            padding: 40px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .profile-top {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: white;
            color: var(--blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            font-weight: 700;
        }
        
        .profile-info h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
        }
        
        .profile-info p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
        }
        
        .profile-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .profile-stat {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .profile-stat-value {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .profile-stat-label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .info-card {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 25px;
        }
        
        .info-card h3 {
            margin: 0 0 20px 0;
            font-size: 18px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-card h3 i {
            color: var(--blue);
        }
        
        .info-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .info-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .info-item i {
            width: 20px;
            color: var(--blue);
            margin-top: 2px;
        }
        
        .info-item-content {
            flex: 1;
        }
        
        .info-item-label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 3px;
        }
        
        .info-item-value {
            font-size: 15px;
            color: var(--dark);
            font-weight: 500;
        }
        
        .assignments-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .assignments-table th,
        .assignments-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .assignments-table th {
            background: #f9fafb;
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }
        
        .assignments-table td {
            font-size: 14px;
            color: #4b5563;
        }
        
        .type-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .type-tutor {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .type-pal {
            background: #d1fae5;
            color: #10b981;
        }
        
        .workload-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .workload-available {
            background: #d1fae5;
            color: #10b981;
        }
        
        .workload-good {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .workload-moderate {
            background: #fef3c7;
            color: #f59e0b;
        }
        
        .workload-high {
            background: #fee2e2;
            color: #dc2626;
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
                    <a href="tutors.php" class="btn-link">
                        <i class="fas fa-arrow-left"></i> Back to Tutors & PALs
                    </a>
                </div>

                <!-- Profile Header -->
                <div class="tutor-profile-header">
                    <div class="profile-top">
                        <div class="profile-avatar">
                            <?php echo strtoupper(substr($tutor['first_name'], 0, 1) . substr($tutor['last_name'], 0, 1)); ?>
                        </div>
                        <div class="profile-info">
                            <h1><?php echo htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']); ?></h1>
                            <p>
                                <span class="type-badge type-<?php echo $tutor['role']; ?>">
                                    <?php echo strtoupper($tutor['role']); ?>
                                </span>
                                <span style="margin-left: 15px;"><?php echo htmlspecialchars($tutor['staff_number']); ?></span>
                            </p>
                        </div>
                    </div>
                    
                    <div class="profile-stats">
                        <div class="profile-stat">
                            <div class="profile-stat-value"><?php echo $tutor['active_assignments']; ?></div>
                            <div class="profile-stat-label">Active Assignments</div>
                        </div>
                        <div class="profile-stat">
                            <div class="profile-stat-value"><?php echo $tutor['total_sessions']; ?></div>
                            <div class="profile-stat-label">Total Sessions</div>
                        </div>
                        <div class="profile-stat">
                            <div class="profile-stat-value">
                                <?php 
                                if($tutor['active_assignments'] == 0) {
                                    echo '<span style="color: #10b981;">Available</span>';
                                } elseif($tutor['active_assignments'] <= 2) {
                                    echo '<span style="color: #3b82f6;">Good</span>';
                                } elseif($tutor['active_assignments'] <= 4) {
                                    echo '<span style="color: #f59e0b;">Moderate</span>';
                                } else {
                                    echo '<span style="color: #ef4444;">High</span>';
                                }
                                ?>
                            </div>
                            <div class="profile-stat-label">Workload Status</div>
                        </div>
                    </div>
                </div>

                <!-- Information Cards -->
                <div class="info-grid">
                    <div class="info-card">
                        <h3><i class="fas fa-user"></i> Personal Information</h3>
                        <div class="info-item">
                            <i class="fas fa-id-card"></i>
                            <div class="info-item-content">
                                <div class="info-item-label">Staff Number</div>
                                <div class="info-item-value"><?php echo htmlspecialchars($tutor['staff_number']); ?></div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-id-badge"></i>
                            <div class="info-item-content">
                                <div class="info-item-label">Student Number</div>
                                <div class="info-item-value"><?php echo htmlspecialchars($tutor['student_number'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-envelope"></i>
                            <div class="info-item-content">
                                <div class="info-item-label">Email</div>
                                <div class="info-item-value"><?php echo htmlspecialchars($tutor['email']); ?></div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-phone"></i>
                            <div class="info-item-content">
                                <div class="info-item-label">Phone</div>
                                <div class="info-item-value"><?php echo htmlspecialchars($tutor['phone']); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <h3><i class="fas fa-chart-line"></i> Academic Performance</h3>
                        <div class="info-item">
                            <i class="fas fa-trophy"></i>
                            <div class="info-item-content">
                                <div class="info-item-label">GPA</div>
                                <div class="info-item-value">
                                    <strong style="color: <?php echo ($tutor['gpa'] ?? 0) >= 3.7 ? 'var(--green)' : (($tutor['gpa'] ?? 0) >= 3.5 ? 'var(--blue)' : '#f59e0b'); ?>; font-size: 18px;">
                                        <?php echo number_format($tutor['gpa'] ?? 0, 2); ?>/4.00
                                    </strong>
                                    <?php if(($tutor['gpa'] ?? 0) >= 3.7): ?>
                                        <span style="color: var(--green); font-size: 12px; margin-left: 10px;">
                                            <i class="fas fa-star"></i> Excellent
                                        </span>
                                    <?php elseif(($tutor['gpa'] ?? 0) >= 3.5): ?>
                                        <span style="color: var(--blue); font-size: 12px; margin-left: 10px;">
                                            <i class="fas fa-check-circle"></i> Very Good
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-user-graduate"></i>
                            <div class="info-item-content">
                                <div class="info-item-label">Academic Level</div>
                                <div class="info-item-value"><?php echo htmlspecialchars($tutor['academic_year_level'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-book"></i>
                            <div class="info-item-content">
                                <div class="info-item-label">Modules Can Tutor</div>
                                <div class="info-item-value"><?php echo htmlspecialchars($tutor['modules_tutored'] ?? 'N/A'); ?></div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-calendar-check"></i>
                            <div class="info-item-content">
                                <div class="info-item-label">Application Date</div>
                                <div class="info-item-value">
                                    <?php echo $tutor['application_date'] ? date('M d, Y', strtotime($tutor['application_date'])) : 'N/A'; ?>
                                </div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-check-circle"></i>
                            <div class="info-item-content">
                                <div class="info-item-label">Approval Date</div>
                                <div class="info-item-value">
                                    <?php echo $tutor['approval_date'] ? date('M d, Y', strtotime($tutor['approval_date'])) : 'N/A'; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="info-card">
                        <h3><i class="fas fa-graduation-cap"></i> Qualifications</h3>
                        <div class="info-item">
                            <i class="fas fa-certificate"></i>
                            <div class="info-item-content">
                                <div class="info-item-label">Qualification</div>
                                <div class="info-item-value"><?php echo htmlspecialchars($tutor['qualification']); ?></div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-star"></i>
                            <div class="info-item-content">
                                <div class="info-item-label">Specialization</div>
                                <div class="info-item-value"><?php echo htmlspecialchars($tutor['specialization']); ?></div>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-tag"></i>
                            <div class="info-item-content">
                                <div class="info-item-label">Role</div>
                                <div class="info-item-value">
                                    <?php echo ucfirst($tutor['role']); ?>
                                    <?php if($tutor['role'] == 'tutor'): ?>
                                        (Expert/Lecturer)
                                    <?php else: ?>
                                        (Peer Assisted Learning)
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Assigned Modules -->
                <div class="section">
                    <h2 class="section-title">
                        <i class="fas fa-clipboard-list"></i> Assigned Modules
                        <span style="font-weight: normal; font-size: 14px; color: #6b7280;">
                            (<?php echo count($assignments); ?> active)
                        </span>
                    </h2>
                    
                    <?php if(count($assignments) > 0): ?>
                        <div style="overflow-x: auto;">
                            <table class="assignments-table">
                                <thead>
                                    <tr>
                                        <th>Module</th>
                                        <th>Faculty</th>
                                        <th>Campus</th>
                                        <th>Academic Year</th>
                                        <th>Assigned Date</th>
                                        <th>Max Students</th>
                                        <th>Frequency</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($assignments as $assignment): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($assignment['subject_code']); ?></strong><br>
                                            <span style="font-size: 12px; color: #6b7280;">
                                                <?php echo htmlspecialchars($assignment['subject_name']); ?>
                                            </span>
                                        </td>
                                        <td style="font-size: 12px;"><?php echo htmlspecialchars($assignment['faculty']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['campus']); ?></td>
                                        <td><?php echo htmlspecialchars($assignment['academic_year']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($assignment['assignment_date'])); ?></td>
                                        <td><?php echo $assignment['max_students']; ?></td>
                                        <td><?php echo htmlspecialchars($assignment['session_frequency']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard"></i>
                            <h3>No Active Assignments</h3>
                            <p>This tutor/PAL is not currently assigned to any modules.</p>
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
