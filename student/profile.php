<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

$student_id = $_SESSION['student_id'];

// Get student info
$stmt = $conn->prepare("SELECT s.*, f.faculty_name FROM students s LEFT JOIN faculties f ON s.faculty_id = f.faculty_id WHERE s.student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - WSU Booking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Profile updated successfully!
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="profile-container">
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div class="profile-info">
                            <h2><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h2>
                            <p><?php echo htmlspecialchars($student['student_id']); ?></p>
                            <span class="status-badge <?php echo $student['status']; ?>">
                                <?php echo ucfirst($student['status']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="profile-grid">
                        <div class="profile-section">
                            <h3><i class="fas fa-user"></i> Personal Information</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>First Name</label>
                                    <p><?php echo htmlspecialchars($student['first_name']); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Last Name</label>
                                    <p><?php echo htmlspecialchars($student['last_name']); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Email</label>
                                    <p><?php echo htmlspecialchars($student['email']); ?></p>
                                </div>
                            </div>
                            
                            <form action="update-profile.php" method="POST" class="profile-form" style="margin-top: 20px;">
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>" pattern="[0-9]{10}">
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Phone
                                </button>
                            </form>
                        </div>

                        <div class="profile-section">
                            <h3><i class="fas fa-graduation-cap"></i> Academic Information</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Student ID</label>
                                    <p><?php echo htmlspecialchars($student['student_id']); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Faculty</label>
                                    <p><?php echo htmlspecialchars($student['faculty_name'] ?? 'Not assigned'); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Year of Study</label>
                                    <p>Year <?php echo $student['year_of_study']; ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Student Type</label>
                                    <p><?php echo ucfirst(str_replace('_', ' ', $student['student_type'])); ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="profile-section">
                            <h3><i class="fas fa-lock"></i> Change Password</h3>
                            <form action="change-password.php" method="POST" class="profile-form">
                                <div class="form-group">
                                    <label>Current Password</label>
                                    <input type="password" name="current_password" required>
                                </div>
                                <div class="form-group">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" required minlength="6">
                                </div>
                                <div class="form-group">
                                    <label>Confirm New Password</label>
                                    <input type="password" name="confirm_password" required minlength="6">
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key"></i> Update Password
                                </button>
                            </form>
                        </div>

                        <div class="profile-section">
                            <h3><i class="fas fa-info-circle"></i> Account Details</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <label>Account Status</label>
                                    <p><span class="status-badge <?php echo $student['status']; ?>"><?php echo ucfirst($student['status']); ?></span></p>
                                </div>
                                <div class="info-item">
                                    <label>Member Since</label>
                                    <p><?php echo date('F Y', strtotime($student['created_at'])); ?></p>
                                </div>
                                <div class="info-item">
                                    <label>Last Login</label>
                                    <p><?php echo $student['last_login'] ? date('d M Y, H:i', strtotime($student['last_login'])) : 'Never'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/dashboard.js"></script>
</body>
</html>
