<?php
session_start();
if(!isset($_SESSION['staff_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get staff details
$stmt = $conn->prepare("
    SELECT s.*, d.department_name, f.faculty_name
    FROM staff s
    LEFT JOIN departments d ON s.department_id = d.department_id
    LEFT JOIN faculties f ON d.faculty_id = f.faculty_id
    WHERE s.staff_id = ?
");
$stmt->execute([$_SESSION['staff_id']]);
$staff = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <!-- Hero Section -->
                <div class="hero-section">
                    <div class="hero-content">
                        <h1>My Profile</h1>
                        <p>View and manage your personal information and professional details</p>
                        <div class="hero-stats">
                            <div class="hero-stat">
                                <i class="fas fa-id-badge"></i>
                                <span><?php echo htmlspecialchars($staff['staff_number']); ?></span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-briefcase"></i>
                                <span><?php echo ucfirst(str_replace('_', ' ', $staff['role'])); ?></span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-check-circle"></i>
                                <span><?php echo ucfirst($staff['status']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if(isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if(isset($_GET['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>

                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?></h2>
                        <p><?php echo htmlspecialchars($staff['email']); ?></p>
                        <span class="status-badge <?php echo $staff['status']; ?>">
                            <?php echo ucfirst($staff['status']); ?>
                        </span>
                    </div>
                </div>

                <!-- Profile Grid -->
                <div class="profile-grid">
                    <!-- Personal Information -->
                    <div class="profile-section">
                        <h3><i class="fas fa-user"></i> Personal Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Staff Number</label>
                                <p><?php echo htmlspecialchars($staff['staff_number']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>First Name</label>
                                <p><?php echo htmlspecialchars($staff['first_name']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Last Name</label>
                                <p><?php echo htmlspecialchars($staff['last_name']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Email</label>
                                <p><?php echo htmlspecialchars($staff['email']); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Phone</label>
                                <p><?php echo htmlspecialchars($staff['phone'] ?? 'Not provided'); ?></p>
                            </div>
                            <div class="info-item">
                                <label>Status</label>
                                <p><?php echo ucfirst($staff['status']); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Professional Information -->
                    <div class="profile-section">
                        <h3><i class="fas fa-briefcase"></i> Professional Information</h3>
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Role</label>
                                <p><?php echo ucfirst(str_replace('_', ' ', $staff['role'])); ?></p>
                            </div>
                            <?php if(!empty($staff['department_name'])): ?>
                            <div class="info-item">
                                <label>Department</label>
                                <p><?php echo htmlspecialchars($staff['department_name']); ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if(!empty($staff['faculty_name'])): ?>
                            <div class="info-item">
                                <label>Faculty</label>
                                <p><?php echo htmlspecialchars($staff['faculty_name']); ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if(!empty($staff['qualification'])): ?>
                            <div class="info-item">
                                <label>Qualification</label>
                                <p><?php echo htmlspecialchars($staff['qualification']); ?></p>
                            </div>
                            <?php endif; ?>
                            <?php if(!empty($staff['specialization'])): ?>
                            <div class="info-item">
                                <label>Specialization</label>
                                <p><?php echo htmlspecialchars($staff['specialization']); ?></p>
                            </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <label>Member Since</label>
                                <p><?php echo date('d M Y', strtotime($staff['created_at'])); ?></p>
                            </div>
                            <?php if($staff['last_login']): ?>
                            <div class="info-item">
                                <label>Last Login</label>
                                <p><?php echo date('d M Y, H:i', strtotime($staff['last_login'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Edit Profile Button -->
                <div class="section">
                    <h3>Actions</h3>
                    <div class="form-actions">
                        <a href="edit-profile.php" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Profile
                        </a>
                        <a href="change-password.php" class="btn btn-secondary">
                            <i class="fas fa-key"></i> Change Password
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/dashboard.js"></script>
</body>
</html>
