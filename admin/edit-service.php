<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../admin-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

$service_id = isset($_GET['id']) ? $_GET['id'] : null;

if(!$service_id) {
    header('Location: services.php');
    exit();
}

// Get service details
$stmt = $conn->prepare("SELECT * FROM services WHERE service_id = ?");
$stmt->execute([$service_id]);
$service = $stmt->fetch();

if(!$service) {
    header('Location: services.php?error=Service not found');
    exit();
}

// Get categories
$stmt = $conn->query("SELECT * FROM service_categories ORDER BY category_name");
$categories = $stmt->fetchAll();

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $service_name = $_POST['service_name'];
    $category_id = $_POST['category_id'];
    $description = $_POST['description'];
    $duration_minutes = $_POST['duration_minutes'];
    $buffer_time_minutes = $_POST['buffer_time_minutes'];
    $max_advance_booking_days = $_POST['max_advance_booking_days'];
    $cancellation_hours = $_POST['cancellation_hours'];
    $requires_approval = isset($_POST['requires_approval']) ? 1 : 0;
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("
        UPDATE services SET
            service_name = ?, category_id = ?, description = ?, duration_minutes = ?,
            buffer_time_minutes = ?, max_advance_booking_days = ?, cancellation_hours = ?,
            requires_approval = ?, status = ?
        WHERE service_id = ?
    ");
    
    if($stmt->execute([$service_name, $category_id, $description, $duration_minutes, $buffer_time_minutes, $max_advance_booking_days, $cancellation_hours, $requires_approval, $status, $service_id])) {
        header('Location: service-details.php?id=' . $service_id . '&success=Service updated successfully');
        exit();
    } else {
        $error = "Failed to update service";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Service - WSU Booking</title>
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
                    <a href="service-details.php?id=<?php echo $service_id; ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Service Details
                    </a>
                </div>

                <!-- Hero Section -->
                <div class="hero-section">
                    <div class="hero-content">
                        <h1>Edit Service</h1>
                        <p>Update service information and settings</p>
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
                                <label for="service_name"><i class="fas fa-concierge-bell"></i> Service Name</label>
                                <input type="text" id="service_name" name="service_name" value="<?php echo htmlspecialchars($service['service_name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="category_id"><i class="fas fa-th-large"></i> Category</label>
                                <select id="category_id" name="category_id" required>
                                    <?php foreach($categories as $cat): ?>
                                        <option value="<?php echo $cat['category_id']; ?>" <?php echo $service['category_id'] == $cat['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="duration_minutes"><i class="fas fa-clock"></i> Duration (minutes)</label>
                                <input type="number" id="duration_minutes" name="duration_minutes" value="<?php echo $service['duration_minutes']; ?>" min="5" max="480" required>
                            </div>

                            <div class="form-group">
                                <label for="buffer_time_minutes"><i class="fas fa-hourglass-half"></i> Buffer Time (minutes)</label>
                                <input type="number" id="buffer_time_minutes" name="buffer_time_minutes" value="<?php echo $service['buffer_time_minutes']; ?>" min="0" max="60">
                            </div>

                            <div class="form-group">
                                <label for="max_advance_booking_days"><i class="fas fa-calendar-plus"></i> Max Advance Booking (days)</label>
                                <input type="number" id="max_advance_booking_days" name="max_advance_booking_days" value="<?php echo $service['max_advance_booking_days']; ?>" min="1" max="365" required>
                            </div>

                            <div class="form-group">
                                <label for="cancellation_hours"><i class="fas fa-times-circle"></i> Cancellation Notice (hours)</label>
                                <input type="number" id="cancellation_hours" name="cancellation_hours" value="<?php echo $service['cancellation_hours']; ?>" min="1" max="168" required>
                            </div>

                            <div class="form-group">
                                <label for="status"><i class="fas fa-toggle-on"></i> Status</label>
                                <select id="status" name="status" required>
                                    <option value="active" <?php echo $service['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $service['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                    <input type="checkbox" name="requires_approval" <?php echo $service['requires_approval'] ? 'checked' : ''; ?> style="width: 20px; height: 20px;">
                                    <span><i class="fas fa-check-square"></i> Requires Approval</span>
                                </label>
                                <small>Check if bookings need staff approval</small>
                            </div>
                        </div>

                        <div class="form-group" style="margin-top: 20px;">
                            <label for="description"><i class="fas fa-align-left"></i> Description</label>
                            <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($service['description']); ?></textarea>
                        </div>

                        <div class="form-actions" style="margin-top: 30px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <a href="service-details.php?id=<?php echo $service_id; ?>" class="btn btn-secondary">
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
