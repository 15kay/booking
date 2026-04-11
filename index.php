<?php
session_start();
if(isset($_SESSION['student_id'])) { header('Location: student/index.php'); exit(); }
if(isset($_SESSION['staff_id']))   { header('Location: staff/index.php');   exit(); }
if(isset($_SESSION['admin_id']))   { header('Location: admin/index.php');   exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WSU Booking System - Login</title>
    <link rel="stylesheet" href="assets/css/style.css?v=5">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="login-left">
            <div class="logo-section">
                <img src="wsu-new-logo.gif" alt="WSU Logo" class="logo-img">
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
                <h2>Welcome Back</h2>
                <p class="subtitle">Sign in to continue</p>

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

                <form action="auth/unified-login.php" method="POST">
                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> ID / Staff Number / Username</label>
                        <input type="text" name="identifier" placeholder="e.g. 202401234 or STF001 or admin" required autofocus>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Password</label>
                        <div class="password-wrap">
                            <input type="password" name="password" id="passwordInput" placeholder="Enter your password" required>
                            <button type="button" class="toggle-pw" onclick="togglePassword()">
                                <i class="fas fa-eye" id="pwIcon"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </form>
            </div>
        </div>
    </div>

    <style>
        .password-wrap {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-wrap input {
            flex: 1;
            padding-right: 44px !important;
        }
        .toggle-pw {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            font-size: 15px;
            padding: 0;
            transition: color 0.2s;
        }
        .toggle-pw:hover { color: var(--primary); }
    </style>

    <script>
        function togglePassword() {
            var input  = document.getElementById('passwordInput');
            var icon   = document.getElementById('pwIcon');
            var isText = input.type === 'text';
            input.type = isText ? 'password' : 'text';
            icon.className = isText ? 'fas fa-eye' : 'fas fa-eye-slash';
        }
    </script>
</body>
</html>
