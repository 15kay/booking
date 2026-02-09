<?php
session_start();
if(isset($_SESSION['staff_id'])) {
    header('Location: staff/index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WSU Booking System - Staff Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="logo-section">
                <img src="logo.png" alt="WSU Logo" class="logo-img">
                <h1>Walter Sisulu University</h1>
                <p>Staff Services Booking System</p>
            </div>
            <div class="features">
                <div class="feature-item">
                    <i class="fas fa-calendar-check"></i>
                    <span>Manage Appointments</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-clock"></i>
                    <span>Schedule Management</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-users"></i>
                    <span>Student Management</span>
                </div>
            </div>
        </div>
        
        <div class="login-right">
            <div class="login-box">
                <h2>Staff Login</h2>
                <p class="subtitle">Access your staff dashboard</p>
                
                <?php if(isset($_GET['error'])): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($_GET['error']); ?>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>
                
                <form action="auth/staff-login.php" method="POST" id="loginForm">
                    <div class="form-group">
                        <label for="staff_id">
                            <i class="fas fa-id-card"></i> Staff ID
                        </label>
                        <input type="text" id="staff_id" name="staff_id" 
                               placeholder="Enter your staff ID" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <input type="password" id="password" name="password" 
                               placeholder="Enter your password" required>
                    </div>
                    
                    <div class="form-options">
                        <label class="checkbox">
                            <input type="checkbox" name="remember">
                            <span>Remember me</span>
                        </label>
                        <a href="forgot-password.php" class="forgot-link">Forgot Password?</a>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>
                
                <div class="register-link">
                    Student? <a href="index.php">Login here</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>
