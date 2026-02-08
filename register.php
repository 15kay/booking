<?php
session_start();
if(isset($_SESSION['student_id'])) {
    header('Location: student/index.php');
    exit();
}

require_once 'config/database.php';
$db = new Database();
$conn = $db->connect();

// Get faculties for dropdown
$stmt = $conn->query("SELECT * FROM faculties WHERE status = 'active' ORDER BY faculty_name");
$faculties = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - WSU Booking System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <img src="logo.png" alt="WSU Logo" class="logo">
                <h1>Walter Sisulu University</h1>
                <p>Student Registration</p>
            </div>

            <?php if($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="auth/register.php" method="POST" class="login-form">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-id-card"></i> Student Number</label>
                        <input type="text" name="student_id" placeholder="e.g., 202401234" required pattern="[0-9]{9}">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="email" placeholder="studentnumber@mywsu.ac.za" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> First Name</label>
                        <input type="text" name="first_name" placeholder="First Name" required>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Last Name</label>
                        <input type="text" name="last_name" placeholder="Last Name" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="tel" name="phone" placeholder="0821234567" pattern="[0-9]{10}">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-graduation-cap"></i> Faculty</label>
                        <select name="faculty_id" required>
                            <option value="">Select Faculty</option>
                            <?php foreach($faculties as $faculty): ?>
                                <option value="<?php echo $faculty['faculty_id']; ?>">
                                    <?php echo htmlspecialchars($faculty['faculty_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Year of Study</label>
                        <select name="year_of_study" required>
                            <option value="">Select Year</option>
                            <option value="1">Year 1</option>
                            <option value="2">Year 2</option>
                            <option value="3">Year 3</option>
                            <option value="4">Year 4</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-user-graduate"></i> Student Type</label>
                        <select name="student_type" required>
                            <option value="">Select Type</option>
                            <option value="undergraduate">Undergraduate</option>
                            <option value="postgraduate">Postgraduate</option>
                            <option value="honours">Honours</option>
                            <option value="masters">Masters</option>
                            <option value="phd">PhD</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Password</label>
                        <input type="password" name="password" placeholder="Password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Confirm Password</label>
                        <input type="password" name="confirm_password" placeholder="Confirm Password" required minlength="6">
                    </div>
                </div>

                <button type="submit" class="login-btn">
                    <i class="fas fa-user-plus"></i> Register
                </button>
            </form>

            <div class="login-footer">
                <p>Already have an account? <a href="index.php">Login here</a></p>
            </div>
        </div>
    </div>
</body>
</html>
