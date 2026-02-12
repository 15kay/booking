<?php
session_start();
if(!isset($_SESSION['staff_id']) || $_SESSION['role'] != 'coordinator') {
    header('Location: ../staff-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

$success = '';
$error = '';

// Get coordinator details
$stmt = $conn->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->execute([$_SESSION['staff_id']]);
$coordinator = $stmt->fetch();

// Handle profile update
if(isset($_POST['update_profile'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    
    try {
        $update_stmt = $conn->prepare("
            UPDATE staff 
            SET first_name = ?, last_name = ?, phone = ?
            WHERE staff_id = ?
        ");
        $update_stmt->execute([$first_name, $last_name, $phone, $_SESSION['staff_id']]);
        
        $success = 'Profile updated successfully!';
        
        // Refresh coordinator data
        $stmt->execute([$_SESSION['staff_id']]);
        $coordinator = $stmt->fetch();
        
    } catch(PDOException $e) {
        $error = 'Error updating profile: ' . $e->getMessage();
    }
}

// Handle password change
if(isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if($new_password !== $confirm_password) {
        $error = 'New passwords do not match!';
    } elseif(strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long!';
    } elseif(!password_verify($current_password, $coordinator['password_hash'])) {
        $error = 'Current password is incorrect!';
    } else {
        try {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $pwd_stmt = $conn->prepare("UPDATE staff SET password_hash = ? WHERE staff_id = ?");
            $pwd_stmt->execute([$new_hash, $_SESSION['staff_id']]);
            
            $success = 'Password changed successfully!';
            
        } catch(PDOException $e) {
            $error = 'Error changing password: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .settings-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .settings-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            border: 2px solid #e5e7eb;
        }
        
        .settings-card h2 {
            margin: 0 0 20px 0;
            font-size: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e5e7eb;
        }
        
        .settings-card h2 i {
            color: var(--blue);
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
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
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(29, 78, 216, 0.1);
        }
        
        .form-group input:disabled {
            background: #f3f4f6;
            cursor: not-allowed;
        }
        
        .info-box {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid var(--blue);
            margin-bottom: 20px;
        }
        
        .info-box p {
            margin: 0;
            font-size: 14px;
            color: #4b5563;
        }
        
        .info-box i {
            color: var(--blue);
            margin-right: 8px;
        }
        
        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #000000 0%, #1d4ed8 100%);
            border-radius: 12px;
            color: white;
        }
        
        .profile-avatar {
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
            flex-shrink: 0;
        }
        
        .profile-info h3 {
            margin: 0 0 5px 0;
            font-size: 24px;
        }
        
        .profile-info p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            background: rgba(255, 255, 255, 0.2);
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <div class="settings-container">
                    <div class="page-header">
                        <h1><i class="fas fa-cog"></i> Settings</h1>
                        <p>Manage your profile and account settings</p>
                    </div>

                    <?php if($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                        </div>
                    <?php endif; ?>

                    <?php if($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Profile Header -->
                    <div class="profile-header">
                        <div class="profile-avatar">
                            <?php echo strtoupper(substr($coordinator['first_name'], 0, 1) . substr($coordinator['last_name'], 0, 1)); ?>
                        </div>
                        <div class="profile-info">
                            <h3><?php echo htmlspecialchars($coordinator['first_name'] . ' ' . $coordinator['last_name']); ?></h3>
                            <p><?php echo htmlspecialchars($coordinator['email']); ?></p>
                            <span class="badge">
                                <i class="fas fa-user-tie"></i> Coordinator - <?php echo htmlspecialchars($coordinator['assigned_campus']); ?> Campus
                            </span>
                        </div>
                    </div>

                    <!-- Profile Information -->
                    <div class="settings-card">
                        <h2><i class="fas fa-user"></i> Profile Information</h2>
                        
                        <form method="POST" action="">
                            <div class="form-grid">
                                <div class="form-group">
                                    <label><i class="fas fa-id-card"></i> Staff Number</label>
                                    <input type="text" value="<?php echo htmlspecialchars($coordinator['staff_number']); ?>" disabled>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-building"></i> Campus</label>
                                    <input type="text" value="<?php echo htmlspecialchars($coordinator['assigned_campus']); ?>" disabled>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-user"></i> First Name</label>
                                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($coordinator['first_name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-user"></i> Last Name</label>
                                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($coordinator['last_name']); ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-envelope"></i> Email</label>
                                    <input type="email" value="<?php echo htmlspecialchars($coordinator['email']); ?>" disabled>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-phone"></i> Phone</label>
                                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($coordinator['phone']); ?>" required>
                                </div>
                            </div>
                            
                            <button type="submit" name="update_profile" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </form>
                    </div>

                    <!-- Change Password -->
                    <div class="settings-card">
                        <h2><i class="fas fa-lock"></i> Change Password</h2>
                        
                        <div class="info-box">
                            <p><i class="fas fa-info-circle"></i> Password must be at least 6 characters long</p>
                        </div>
                        
                        <form method="POST" action="">
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label><i class="fas fa-key"></i> Current Password</label>
                                    <input type="password" name="current_password" required>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-lock"></i> New Password</label>
                                    <input type="password" name="new_password" minlength="6" required>
                                </div>
                                
                                <div class="form-group">
                                    <label><i class="fas fa-lock"></i> Confirm New Password</label>
                                    <input type="password" name="confirm_password" minlength="6" required>
                                </div>
                            </div>
                            
                            <button type="submit" name="change_password" class="btn btn-primary">
                                <i class="fas fa-key"></i> Change Password
                            </button>
                        </form>
                    </div>

                    <!-- Account Information -->
                    <div class="settings-card">
                        <h2><i class="fas fa-info-circle"></i> Account Information</h2>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label><i class="fas fa-user-tag"></i> Role</label>
                                <input type="text" value="Coordinator" disabled>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-toggle-on"></i> Status</label>
                                <input type="text" value="<?php echo ucfirst($coordinator['status']); ?>" disabled>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-calendar-plus"></i> Account Created</label>
                                <input type="text" value="<?php echo date('F j, Y', strtotime($coordinator['created_at'])); ?>" disabled>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-clock"></i> Last Updated</label>
                                <input type="text" value="<?php echo date('F j, Y g:i A', strtotime($coordinator['updated_at'])); ?>" disabled>
                            </div>
                        </div>
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
