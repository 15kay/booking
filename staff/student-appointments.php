<?php
session_start();
if(!isset($_SESSION['staff_id'])) {
    header('Location: ../staff-login.php');
    exit();
}

$role = $_SESSION['role'] ?? 'staff';

// Only counselors and advisors can access this page
$is_counselor = in_array($role, ['counsellor', 'academic_advisor', 'career_counsellor', 'financial_advisor']);

if(!$is_counselor) {
    header('Location: index.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

$staff_id = $_SESSION['staff_id'];
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

// Get all appointments for this student with this counselor/advisor
$appointments_query = $conn->prepare("
    SELECT 
        b.*,
        s.service_name, s.duration_minutes
    FROM bookings b
    INNER JOIN services s ON b.service_id = s.service_id
    WHERE b.staff_id = ? AND b.student_id = ?
    ORDER BY b.booking_date DESC, b.start_time DESC
");
$appointments_query->execute([$staff_id, $student_id]);
$appointments = $appointments_query->fetchAll();

// Calculate statistics
$total_appointments = count($appointments);
$completed = 0;
$cancelled = 0;
$upcoming = 0;
$pending = 0;

foreach($appointments as $appointment) {
    switch($appointment['status']) {
        case 'completed':
            $completed++;
            break;
        case 'cancelled':
            $cancelled++;
            break;
        case 'confirmed':
            if($appointment['booking_date'] >= date('Y-m-d')) {
                $upcoming++;
            }
            break;
        case 'pending':
            $pending++;
            break;
    }
}

$completion_rate = $total_appointments > 0 ? ($completed / $total_appointments) * 100 : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Appointments - WSU Booking</title>
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
        
        .appointment-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 15px;
            border-left: 4px solid var(--blue);
            transition: all 0.3s;
        }
        
        .appointment-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .appointment-card.completed {
            border-left-color: var(--green);
        }
        
        .appointment-card.cancelled {
            border-left-color: #dc2626;
        }
        
        .appointment-card.upcoming {
            border-left-color: #f59e0b;
        }
        
        .appointment-card.pending {
            border-left-color: #8b5cf6;
        }
        
        .appointment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 15px;
        }
        
        .appointment-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--dark);
            margin: 0 0 5px 0;
        }
        
        .appointment-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 14px;
            color: #6b7280;
        }
        
        .appointment-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .appointment-meta-item i {
            color: var(--blue);
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .badge-completed {
            background: #d1fae5;
            color: #10b981;
        }
        
        .badge-cancelled {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .badge-confirmed {
            background: #dbeafe;
            color: #1d4ed8;
        }
        
        .badge-pending {
            background: #ede9fe;
            color: #8b5cf6;
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
                        <h3><?php echo $total_appointments; ?></h3>
                        <p>Total Appointments</p>
                    </div>
                    <div class="stat-card" style="border-left-color: var(--green);">
                        <h3><?php echo $completed; ?></h3>
                        <p>Completed</p>
                    </div>
                    <div class="stat-card" style="border-left-color: #f59e0b;">
                        <h3><?php echo $upcoming; ?></h3>
                        <p>Upcoming</p>
                    </div>
                    <div class="stat-card" style="border-left-color: #8b5cf6;">
                        <h3><?php echo $pending; ?></h3>
                        <p>Pending</p>
                    </div>
                    <div class="stat-card" style="border-left-color: #dc2626;">
                        <h3><?php echo $cancelled; ?></h3>
                        <p>Cancelled</p>
                    </div>
                    <div class="stat-card" style="border-left-color: #10b981;">
                        <h3><?php echo number_format($completion_rate, 0); ?>%</h3>
                        <p>Completion Rate</p>
                    </div>
                </div>

                <!-- Appointments List -->
                <div class="section-card">
                    <div class="section-header">
                        <h2><i class="fas fa-calendar-check"></i> Appointment History</h2>
                    </div>

                    <?php if(count($appointments) > 0): ?>
                        <?php foreach($appointments as $appointment): 
                            $appointment_class = 'appointment-card ' . $appointment['status'];
                            $badge_class = 'badge-' . $appointment['status'];
                            $badge_text = ucfirst($appointment['status']);
                        ?>
                            <div class="<?php echo $appointment_class; ?>">
                                <div class="appointment-header">
                                    <div>
                                        <h3 class="appointment-title"><?php echo htmlspecialchars($appointment['service_name']); ?></h3>
                                        <div class="appointment-meta">
                                            <div class="appointment-meta-item">
                                                <i class="fas fa-calendar"></i>
                                                <span><?php echo date('M j, Y', strtotime($appointment['booking_date'])); ?></span>
                                            </div>
                                            <div class="appointment-meta-item">
                                                <i class="fas fa-clock"></i>
                                                <span><?php echo date('g:i A', strtotime($appointment['start_time'])); ?> - <?php echo date('g:i A', strtotime($appointment['end_time'])); ?></span>
                                            </div>
                                            <div class="appointment-meta-item">
                                                <i class="fas fa-hourglass-half"></i>
                                                <span><?php echo $appointment['duration_minutes']; ?> minutes</span>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                                </div>
                                
                                <?php if($appointment['notes']): ?>
                                    <p style="margin: 10px 0 0 0; color: #6b7280; font-size: 14px;">
                                        <strong>Notes:</strong> <?php echo htmlspecialchars($appointment['notes']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div style="margin-top: 15px;">
                                    <a href="appointment-details.php?id=<?php echo $appointment['booking_id']; ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <p>No appointments found for this student</p>
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
