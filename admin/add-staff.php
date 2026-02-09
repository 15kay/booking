<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../admin-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get departments
$stmt = $conn->query("SELECT * FROM departments ORDER BY department_name");
$departments = $stmt->fetchAll();

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $staff_number = $_POST['staff_number'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $department_id = $_POST['department_id'];
    $role = $_POST['role'];
    $qualification = $_POST['qualification'];
    $specialization = $_POST['specialization'];
    $password = $_POST['password'];
    
    // Hash password
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    
    // Check if staff number or email already exists
    $stmt = $conn->prepare("SELECT * FROM staff WHERE staff_number = ? OR email = ?");
    $stmt->execute([$staff_number, $email]);
    
    if($stmt->fetch()) {
        $error = "Staff number or email already exists";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO staff (staff_number, first_name, last_name, email, password_hash, phone, department_id, role, qualification, specialization)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if($stmt->execute([$staff_number, $first_name, $last_name, $email, $password_hash, $phone, $department_id, $role, $qualification, $specialization])) {
            header('Location: staff-management.php?success=Staff added successfully');
            exit();
        } else {
            $error = "Failed to add staff";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Staff - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <!-- Back Button -->
                <div style="margin-bottom: 20px;">
                    <a href="staff-management.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Staff Management
                    </a>
                </div>

                <!-- Hero Section -->
                <div class="hero-section">
                    <div class="hero-content">
                        <h1>Add New Staff Member</h1>
                        <p>Create a new staff account in the system</p>
                    </div>
                </div>

                <?php if(isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Add Form -->
                <div class="section">
                    <form method="POST" action="">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                            <div class="form-group">
                                <label for="staff_number"><i class="fas fa-id-badge"></i> Staff Number *</label>
                                <input type="text" id="staff_number" name="staff_number" placeholder="e.g., STF001" required>
                                <small>Unique staff identifier</small>
                            </div>

                            <div class="form-group">
                                <label for="first_name"><i class="fas fa-user"></i> First Name *</label>
                                <input type="text" id="first_name" name="first_name" required>
                            </div>

                            <div class="form-group">
                                <label for="last_name"><i class="fas fa-user"></i> Last Name *</label>
                                <input type="text" id="last_name" name="last_name" required>
                            </div>

                            <div class="form-group">
                                <label for="email"><i class="fas fa-envelope"></i> Email *</label>
                                <input type="email" id="email" name="email" placeholder="staff@wsu.ac.za" required>
                            </div>

                            <div class="form-group">
                                <label for="phone"><i class="fas fa-phone"></i> Phone</label>
                                <input type="tel" id="phone" name="phone" placeholder="0821234567">
                            </div>

                            <div class="form-group">
                                <label for="password"><i class="fas fa-lock"></i> Password *</label>
                                <input type="password" id="password" name="password" required>
                                <small>Minimum 6 characters</small>
                            </div>

                            <div class="form-group">
                                <label for="department_id"><i class="fas fa-building"></i> Department</label>
                                <select id="department_id" name="department_id">
                                    <option value="">Select Department</option>
                                    <?php foreach($departments as $dept): ?>
                                        <option value="<?php echo $dept['department_id']; ?>">
                                            <?php echo htmlspecialchars($dept['department_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="role"><i class="fas fa-briefcase"></i> Role *</label>
                                <select id="role" name="role" required>
                                    <option value="">Select Role</option>
                                    <option value="counsellor">Counsellor</option>
                                    <option value="academic_advisor">Academic Advisor</option>
                                    <option value="career_counsellor">Career Counsellor</option>
                                    <option value="financial_advisor">Financial Advisor</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="qualification"><i class="fas fa-graduation-cap"></i> Qualification</label>
                                <input type="text" id="qualification" name="qualification" placeholder="e.g., PhD Psychology">
                            </div>

                            <div class="form-group">
                                <label for="specialization"><i class="fas fa-star"></i> Specialization</label>
                                <input type="text" id="specialization" name="specialization" placeholder="e.g., Student Wellness">
                            </div>
                        </div>

                        <div class="form-actions" style="margin-top: 30px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Staff Member
                            </button>
                            <a href="staff-management.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/dashboard.js"></script>
</body>
</html>
