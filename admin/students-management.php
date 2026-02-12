<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../admin-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get all students with faculty information
$stmt = $conn->prepare("
    SELECT s.*, f.faculty_name, f.faculty_code
    FROM students s
    LEFT JOIN faculties f ON s.faculty_id = f.faculty_id
    ORDER BY s.created_at DESC
");
$stmt->execute();
$students = $stmt->fetchAll();

// Get statistics
$stmt = $conn->query("SELECT COUNT(*) as total FROM students WHERE status = 'active'");
$active_students = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM students WHERE status = 'inactive'");
$inactive_students = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM students WHERE status = 'suspended'");
$suspended_students = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM students WHERE status = 'graduated'");
$graduated_students = $stmt->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management - WSU Booking</title>
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
                        <h1>Student Management</h1>
                        <p>Manage all student accounts and information</p>
                        <div class="hero-stats">
                            <div class="hero-stat">
                                <i class="fas fa-user-check"></i>
                                <span><?php echo $active_students; ?> Active</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-user-slash"></i>
                                <span><?php echo $inactive_students; ?> Inactive</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-ban"></i>
                                <span><?php echo $suspended_students; ?> Suspended</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-graduation-cap"></i>
                                <span><?php echo $graduated_students; ?> Graduated</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $active_students; ?></h3>
                            <p>Active Students</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-user-slash"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $inactive_students; ?></h3>
                            <p>Inactive</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-ban"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $suspended_students; ?></h3>
                            <p>Suspended</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $graduated_students; ?></h3>
                            <p>Graduated</p>
                        </div>
                    </div>
                </div>

                <!-- Page Header -->
                <div class="section-header">
                    <h2 class="section-title"><i class="fas fa-user-graduate"></i> All Students (<?php echo count($students); ?>)</h2>
                    <div style="display: flex; gap: 10px; align-items: center;">
                        <div class="view-toggle">
                            <button class="view-btn active" data-view="table" onclick="toggleView('table')">
                                <i class="fas fa-table"></i> Table
                            </button>
                            <button class="view-btn" data-view="cards" onclick="toggleView('cards')">
                                <i class="fas fa-th-large"></i> Cards
                            </button>
                        </div>
                        <button class="btn btn-primary" onclick="showMessageModal('info', 'Coming Soon', 'Add student functionality coming soon')">
                            <i class="fas fa-plus"></i> Add Student
                        </button>
                    </div>
                </div>

                <!-- Students Table View -->
                <div class="section" id="tableView">
                    <?php if(count($students) > 0): ?>
                        <div class="students-table-container">
                            <table class="students-table">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Faculty</th>
                                        <th>Year & Type</th>
                                        <th>Status</th>
                                        <th>Last Login</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($students as $student): ?>
                                    <tr>
                                        <td>
                                            <div class="student-info">
                                                <div class="student-avatar">
                                                    <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                                </div>
                                                <div class="student-details">
                                                    <h4><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h4>
                                                    <p><?php echo htmlspecialchars($student['student_id']); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if($student['faculty_name']): ?>
                                                <span class="badge badge-blue"><?php echo htmlspecialchars($student['faculty_code']); ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">Not assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                                <span style="font-weight: 600;">Year <?php echo $student['year_of_study']; ?></span>
                                                <span style="font-size: 12px; color: #6b7280;"><?php echo ucfirst($student['student_type']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $student['status']; ?>">
                                                <?php echo ucfirst($student['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php 
                                            if($student['last_login']) {
                                                echo date('d M Y', strtotime($student['last_login']));
                                            } else {
                                                echo '<span class="text-muted">Never</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="student-details.php?id=<?php echo urlencode($student['student_id']); ?>" class="btn-table btn-view">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-user-graduate"></i>
                            <h3>No Students Found</h3>
                            <p>No students have been registered yet</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Students Card View -->
                <div class="section" id="cardsView" style="display: none;">
                    <?php if(count($students) > 0): ?>
                        <div class="students-grid">
                            <?php foreach($students as $student): ?>
                            <div class="student-card">
                                <div class="student-card-header">
                                    <div class="student-avatar-large">
                                        <?php echo strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1)); ?>
                                    </div>
                                    <span class="badge badge-<?php echo $student['status']; ?>">
                                        <?php echo ucfirst($student['status']); ?>
                                    </span>
                                </div>
                                <div class="student-card-body">
                                    <h3><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h3>
                                    <p class="student-id"><?php echo htmlspecialchars($student['student_id']); ?></p>
                                    
                                    <div class="student-info-grid">
                                        <div class="info-row">
                                            <i class="fas fa-envelope"></i>
                                            <span><?php echo htmlspecialchars($student['email']); ?></span>
                                        </div>
                                        <div class="info-row">
                                            <i class="fas fa-phone"></i>
                                            <span><?php echo htmlspecialchars($student['phone'] ?? 'N/A'); ?></span>
                                        </div>
                                        <div class="info-row">
                                            <i class="fas fa-university"></i>
                                            <span><?php echo htmlspecialchars($student['faculty_code'] ?? 'Not assigned'); ?></span>
                                        </div>
                                        <div class="info-row">
                                            <i class="fas fa-graduation-cap"></i>
                                            <span>Year <?php echo $student['year_of_study']; ?> - <?php echo ucfirst($student['student_type']); ?></span>
                                        </div>
                                        <div class="info-row">
                                            <i class="fas fa-clock"></i>
                                            <span>Last login: <?php echo $student['last_login'] ? date('d M Y', strtotime($student['last_login'])) : 'Never'; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="student-card-footer">
                                    <a href="student-details.php?id=<?php echo urlencode($student['student_id']); ?>" class="btn btn-primary btn-block">
                                        <i class="fas fa-eye"></i> View Profile
                                    </a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-user-graduate"></i>
                            <h3>No Students Found</h3>
                            <p>No students have been registered yet</p>
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
    <script>
        function confirmDelete(studentId) {
            showConfirmModal(
                'Delete Student',
                'Are you sure you want to delete this student? This action cannot be undone.',
                function() {
                    showMessageModal('info', 'Coming Soon', 'Delete functionality coming soon for student: ' + studentId);
                }
            );
        }
        
        // View toggle functionality
        function toggleView(view) {
            const tableView = document.getElementById('tableView');
            const cardsView = document.getElementById('cardsView');
            const tableBtnBtn = document.querySelector('[data-view="table"]');
            const cardBtn = document.querySelector('[data-view="cards"]');
            
            if(view === 'table') {
                tableView.style.display = 'block';
                cardsView.style.display = 'none';
                tableBtn.classList.add('active');
                cardBtn.classList.remove('active');
                localStorage.setItem('adminStudentsView', 'table');
            } else {
                tableView.style.display = 'none';
                cardsView.style.display = 'block';
                tableBtn.classList.remove('active');
                cardBtn.classList.add('active');
                localStorage.setItem('adminStudentsView', 'cards');
            }
        }
        
        // Restore saved view preference
        document.addEventListener('DOMContentLoaded', function() {
            const savedView = localStorage.getItem('adminStudentsView') || 'table';
            toggleView(savedView);
        });
    </script>
</body>
</html>
