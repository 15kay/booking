<?php
session_start();
if(!isset($_SESSION['staff_id'])) {
    header('Location: ../staff-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

$staff_id = $_SESSION['staff_id'];
$role = $_SESSION['role'];

// Get staff profile
$stmt = $conn->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->execute([$staff_id]);
$profile = $stmt->fetch();

// Get statistics based on role
$statistics = ['total_bookings'=>0,'completed_bookings'=>0,'total_assignments'=>0,'tutors_managed'=>0,'modules_managed'=>0,'sessions_overseen'=>0,'total_sessions'=>0,'completed_sessions'=>0,'students_helped'=>0,'avg_attendance_rate'=>0];
try {
    $stats = $conn->prepare("SELECT COUNT(*) as total_bookings, COUNT(CASE WHEN status='completed' THEN 1 END) as completed_bookings FROM bookings WHERE staff_id = ?");
    $stats->execute([$staff_id]);
    $row = $stats->fetch();
    if ($row) $statistics = array_merge($statistics, $row);
} catch(Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-container {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #000000 0%, #1d4ed8 100%);
            border-radius: 12px;
            padding: 40px;
            color: white;
            margin-bottom: 30px;
            display: flex;
            gap: 30px;
            align-items: center;
        }
        
        .profile-avatar-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: white;
            color: var(--blue);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: 700;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        
        .profile-header-info {
            flex: 1;
        }
        
        .profile-header-info h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
        }
        
        .profile-header-info p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
        }
        
        .role-badge-large {
            display: inline-block;
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 10px;
        }
        
        .profile-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
            border: 2px solid #e5e7eb;
        }
        
        .profile-card h2 {
            margin: 0 0 20px 0;
            font-size: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .profile-card h2 i {
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
        
        .gpa-display {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            background: #eff6ff;
            border-radius: 8px;
            margin-top: 10px;
        }
        
        .gpa-number {
            font-size: 32px;
            font-weight: 700;
            color: var(--blue);
        }
        
        .gpa-scale {
            font-size: 14px;
            color: #6b7280;
        }
        
        .modules-tutored {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }
        
        .module-badge {
            padding: 6px 12px;
            background: #eff6ff;
            color: var(--blue);
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <div class="profile-container">
                    <!-- Profile Header -->
                    <div class="profile-header">
                        <div class="profile-avatar-large">
                            <?php echo strtoupper(substr($profile['first_name'], 0, 1) . substr($profile['last_name'], 0, 1)); ?>
                        </div>
                        <div class="profile-header-info">
                            <h1><?php echo htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']); ?></h1>
                            <p><?php echo htmlspecialchars($profile['email']); ?></p>
                            <span class="role-badge-large">
                                <i class="fas fa-user-tie"></i> <?php echo ucfirst($role); ?>
                            </span>
                        </div>
                    </div>

                    <!-- Performance Statistics -->
                    <?php if($role == 'tutor' || $role == 'pal'): ?>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon blue">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $statistics['total_assignments']; ?></h3>
                                    <p>Total Assignments</p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon green">
                                    <i class="fas fa-chalkboard-teacher"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $statistics['completed_sessions']; ?></h3>
                                    <p>Sessions Completed</p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon orange">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $statistics['students_helped']; ?></h3>
                                    <p>Students Helped</p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon blue">
                                    <i class="fas fa-percentage"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo number_format($statistics['avg_attendance_rate'] ?? 0, 1); ?>%</h3>
                                    <p>Avg Attendance</p>
                                </div>
                            </div>
                        </div>
                    <?php elseif($role == 'coordinator'): ?>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-icon blue">
                                    <i class="fas fa-clipboard-list"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $statistics['total_assignments']; ?></h3>
                                    <p>Assignments Made</p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon green">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $statistics['tutors_managed']; ?></h3>
                                    <p>Tutors Managed</p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon orange">
                                    <i class="fas fa-book"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $statistics['modules_managed']; ?></h3>
                                    <p>Modules Managed</p>
                                </div>
                            </div>
                            
                            <div class="stat-card">
                                <div class="stat-icon blue">
                                    <i class="fas fa-calendar"></i>
                                </div>
                                <div class="stat-info">
                                    <h3><?php echo $statistics['sessions_overseen']; ?></h3>
                                    <p>Sessions Overseen</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Personal Information -->
                    <div class="profile-card">
                        <h2><i class="fas fa-user"></i> Personal Information</h2>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Staff Number</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['staff_number']); ?></span>
                            </div>
                            
                            <?php if(isset($profile['student_number']) && $profile['student_number']): ?>
                                <div class="info-item">
                                    <span class="info-label">Student Number</span>
                                    <span class="info-value"><?php echo htmlspecialchars($profile['student_number']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <div class="info-item">
                                <span class="info-label">Email</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['email']); ?></span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Phone</span>
                                <span class="info-value"><?php echo htmlspecialchars($profile['phone']); ?></span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Role</span>
                                <span class="info-value"><?php echo ucfirst($profile['role']); ?></span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Status</span>
                                <span class="info-value">
                                    <span class="badge badge-<?php echo $profile['status']; ?>">
                                        <?php echo ucfirst($profile['status']); ?>
                                    </span>
                                </span>
                            </div>
                            
                            <?php if(isset($profile['assigned_campus']) && $profile['assigned_campus']): ?>
                                <div class="info-item">
                                    <span class="info-label">Campus</span>
                                    <span class="info-value"><?php echo htmlspecialchars($profile['assigned_campus']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Academic Information (for tutors/PALs) -->
                    <?php if(($role == 'tutor' || $role == 'pal') && isset($profile['gpa'])): ?>
                        <div class="profile-card">
                            <h2><i class="fas fa-graduation-cap"></i> Academic Information</h2>
                            
                            <div class="info-grid">
                                <div class="info-item">
                                    <span class="info-label">Qualification</span>
                                    <span class="info-value"><?php echo htmlspecialchars($profile['qualification']); ?></span>
                                </div>
                                
                                <div class="info-item">
                                    <span class="info-label">Academic Year Level</span>
                                    <span class="info-value"><?php echo htmlspecialchars($profile['academic_year_level'] ?? 'N/A'); ?></span>
                                </div>
                                
                                <div class="info-item">
                                    <span class="info-label">Specialization</span>
                                    <span class="info-value"><?php echo htmlspecialchars($profile['specialization']); ?></span>
                                </div>
                            </div>
                            
                            <?php if($profile['gpa'] > 0): ?>
                                <div style="margin-top: 20px;">
                                    <span class="info-label">Grade Point Average (GPA)</span>
                                    <div class="gpa-display">
                                        <span class="gpa-number"><?php echo number_format($profile['gpa'], 2); ?></span>
                                        <span class="gpa-scale">/ 4.00</span>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if(isset($profile['modules_tutored']) && $profile['modules_tutored']): ?>
                                <div style="margin-top: 20px;">
                                    <span class="info-label">Modules Tutored</span>
                                    <div class="modules-tutored">
                                        <?php 
                                        $modules = explode(', ', $profile['modules_tutored']);
                                        foreach($modules as $module): 
                                        ?>
                                            <span class="module-badge"><?php echo htmlspecialchars($module); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Account Information -->
                    <div class="profile-card">
                        <h2><i class="fas fa-info-circle"></i> Account Information</h2>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">Member Since</span>
                                <span class="info-value"><?php echo date('F j, Y', strtotime($profile['created_at'])); ?></span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Last Login</span>
                                <span class="info-value">
                                    <?php echo $profile['last_login'] ? date('F j, Y g:i A', strtotime($profile['last_login'])) : 'Never'; ?>
                                </span>
                            </div>
                            
                            <div class="info-item">
                                <span class="info-label">Profile Updated</span>
                                <span class="info-value"><?php echo isset($profile['updated_at']) ? date('F j, Y', strtotime($profile['updated_at'])) : 'N/A'; ?></span>
                            </div>
                            
                            <?php if(isset($profile['application_date']) && $profile['application_date']): ?>
                                <div class="info-item">
                                    <span class="info-label">Application Date</span>
                                    <span class="info-value"><?php echo date('F j, Y', strtotime($profile['application_date'])); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if(isset($profile['approval_date']) && $profile['approval_date']): ?>
                                <div class="info-item">
                                    <span class="info-label">Approval Date</span>
                                    <span class="info-value"><?php echo date('F j, Y', strtotime($profile['approval_date'])); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div style="display: flex; gap: 15px;">
                        <a href="settings.php" class="btn btn-primary">
                            <i class="fas fa-cog"></i> Edit Profile
                        </a>
                        <a href="settings.php" class="btn btn-secondary">
                            <i class="fas fa-key"></i> Change Password
                        </a>
                    </div>
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
