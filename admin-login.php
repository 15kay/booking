<?php
session_start();
if(isset($_SESSION['admin_id'])) {
    header('Location: admin/index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WSU Booking System - Admin Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="logo-section">
                <img src="logo.png" alt="WSU Logo" class="logo-img">
                <h1>Walter Sisulu University</h1>
                <p>System Administration Portal</p>
            </div>
            <div class="features">
                <div class="feature-item">
                    <i class="fas fa-users-cog"></i>
                    <span>Manage Users</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-chart-line"></i>
                    <span>System Analytics</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-cogs"></i>
                    <span>System Settings</span>
                </div>
            </div>
        </div>
        
        <div class="login-right">
            <div class="login-box">
                <h2>Admin Login</h2>
                <p class="subtitle">Access system administration dashboard</p>
                
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
                
                <form action="auth/admin-login.php" method="POST" id="loginForm">
                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-user-shield"></i> Username
                        </label>
                        <input type="text" id="username" name="username" 
                               placeholder="Enter your admin username" required>
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
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>
                
                <div class="register-link">
                    Student? <a href="index.php">Login here</a> | 
                    Staff? <a href="staff-login.php">Login here</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>
