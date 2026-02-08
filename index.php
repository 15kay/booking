<?php
session_start();
if(isset($_SESSION['student_id'])) {
    header('Location: student/index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WSU Booking System - Student Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="logo-section">
                <img src="logo.png" alt="WSU Logo" class="logo-img">
                <h1>Walter Sisulu University</h1>
                <p>Student Services Booking System</p>
            </div>
            <div class="features">
                <div class="feature-item">
                    <i class="fas fa-calendar-check"></i>
                    <span>Book Appointments</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-clock"></i>
                    <span>Manage Your Schedule</span>
                </div>
                <div class="feature-item">
                    <i class="fas fa-bell"></i>
                    <span>Get Reminders</span>
                </div>
            </div>
        </div>
        
        <div class="login-right">
            <div class="login-box">
                <h2>Student Login</h2>
                <p class="subtitle">Access your booking dashboard</p>
                
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
                
                <form action="auth/login.php" method="POST" id="loginForm">
                    <div class="form-group">
                        <label for="student_id">
                            <i class="fas fa-id-card"></i> Student ID
                        </label>
                        <input type="text" id="student_id" name="student_id" 
                               placeholder="Enter your student ID" required>
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
                    Don't have an account? <a href="register.php">Register here</a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>
