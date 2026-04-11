<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

$staff_id = isset($_GET['id']) ? $_GET['id'] : null;

if(!$staff_id) {
    header('Location: staff-management.php');
    exit();
}

// Get staff details
$stmt = $conn->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch();

if(!$staff) {
    header('Location: staff-management.php?error=Staff not found');
    exit();
}

// Get departments
$stmt = $conn->query("SELECT * FROM departments ORDER BY department_name");
$departments = $stmt->fetchAll();

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $department_id = $_POST['department_id'];
    $role = $_POST['role'];
    $qualification = $_POST['qualification'];
    $specialization = $_POST['specialization'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("
        UPDATE staff SET
            first_name = ?, last_name = ?, email = ?, phone = ?,
            department_id = ?, role = ?, qualification = ?, specialization = ?, status = ?
        WHERE staff_id = ?
    ");
    
    if($stmt->execute([$first_name, $last_name, $email, $phone, $department_id, $role, $qualification, $specialization, $status, $staff_id])) {
        header('Location: staff-details.php?id=' . $staff_id . '&success=Staff updated successfully');
        exit();
    } else {
        $error = "Failed to update staff";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Staff - WSU Booking</title>
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
                    <a href="staff-details.php?id=<?php echo $staff_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Staff Details
                    </a>
                </div>

                <!-- Hero Section -->
                <div class="hero-section">
                    <div class="hero-content">
                        <h1>Edit Staff Member</h1>
                        <p>Update staff information and settings</p>
                    </div>
                </div>

                <?php if(isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Edit Form -->
                <div class="section">
                    <form method="POST" action="">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                            <div class="form-group">
                                <label for="first_name"><i class="fas fa-user"></i> First Name</label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($staff['first_name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="last_name"><i class="fas fa-user"></i> Last Name</label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($staff['last_name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="email"><i class="fas fa-envelope"></i> Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($staff['email']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="phone"><i class="fas fa-phone"></i> Phone</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($staff['phone']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="department_id"><i class="fas fa-building"></i> Department</label>
                                <select id="department_id" name="department_id">
                                    <option value="">Select Department</option>
                                    <?php foreach($departments as $dept): ?>
                                        <option value="<?php echo $dept['department_id']; ?>" <?php echo $staff['department_id'] == $dept['department_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['department_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="role"><i class="fas fa-briefcase"></i> Role</label>
                                <select id="role" name="role" required>
                                    <option value="counsellor" <?php echo $staff['role'] == 'counsellor' ? 'selected' : ''; ?>>Counsellor</option>
                                    <option value="academic_advisor" <?php echo $staff['role'] == 'academic_advisor' ? 'selected' : ''; ?>>Academic Advisor</option>
                                    <option value="career_counsellor" <?php echo $staff['role'] == 'career_counsellor' ? 'selected' : ''; ?>>Career Counsellor</option>
                                    <option value="financial_advisor" <?php echo $staff['role'] == 'financial_advisor' ? 'selected' : ''; ?>>Financial Advisor</option>
                                    <option value="admin" <?php echo $staff['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="qualification"><i class="fas fa-graduation-cap"></i> Qualification</label>
                                <input type="text" id="qualification" name="qualification" value="<?php echo htmlspecialchars($staff['qualification']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="specialization"><i class="fas fa-star"></i> Specialization</label>
                                <input type="text" id="specialization" name="specialization" value="<?php echo htmlspecialchars($staff['specialization']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="status"><i class="fas fa-toggle-on"></i> Status</label>
                                <select id="status" name="status" required>
                                    <option value="active" <?php echo $staff['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $staff['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="on_leave" <?php echo $staff['status'] == 'on_leave' ? 'selected' : ''; ?>>On Leave</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-actions" style="margin-top: 30px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <a href="staff-details.php?id=<?php echo $staff_id; ?>" class="btn btn-secondary">
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
