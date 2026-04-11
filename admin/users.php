<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get all users (students and staff)
$stmt = $conn->query("
    SELECT 'student' as user_type, student_id as id, first_name, last_name, email, status, created_at
    FROM students
    UNION ALL
    SELECT 'staff' as user_type, staff_number as id, first_name, last_name, email, status, created_at
    FROM staff
    ORDER BY created_at DESC
");
$users = $stmt->fetchAll();

// Get counts
$stmt = $conn->query("SELECT COUNT(*) as total FROM students");
$total_students = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM staff");
$total_staff = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - WSU Booking</title>
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
                        <h1>User Management</h1>
                        <p>Manage all system users including students and staff members</p>
                        <div class="hero-stats">
                            <div class="hero-stat">
                                <i class="fas fa-users"></i>
                                <span><?php echo count($users); ?> Total Users</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-user-graduate"></i>
                                <span><?php echo $total_students; ?> Students</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-user-tie"></i>
                                <span><?php echo $total_staff; ?> Staff</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_students; ?></h3>
                            <p>Students</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_staff; ?></h3>
                            <p>Staff Members</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count(array_filter($users, function($u) { return $u['status'] == 'active'; })); ?></h3>
                            <p>Active Users</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="fas fa-user-times"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count(array_filter($users, function($u) { return $u['status'] != 'active'; })); ?></h3>
                            <p>Inactive Users</p>
                        </div>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title"><i class="fas fa-users"></i> All Users</h2>
                    </div>
                    
                    <div class="students-table-container">
                        <table class="students-table">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Type</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="student-info">
                                            <div class="student-avatar">
                                                <?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?>
                                            </div>
                                            <div class="student-details">
                                                <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                                                <p><?php echo htmlspecialchars($user['id']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['user_type'] == 'student' ? 'blue' : 'green'; ?>">
                                            <?php echo ucfirst($user['user_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $user['status']; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="<?php echo $user['user_type'] == 'student' ? 'student-details.php' : 'staff-details.php'; ?>?id=<?php echo $user['id']; ?>" class="btn-table btn-view">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
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
        function confirmDelete(userId) {
            showConfirmModal(
                'Delete User',
                'Are you sure you want to delete this user? This action cannot be undone.',
                function() {
                    showMessageModal('info', 'Coming Soon', 'Delete functionality coming soon for user: ' + userId);
                }
            );
        }
    </script>
</body>
</html>
