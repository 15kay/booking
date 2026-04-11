<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get all services with category information
$stmt = $conn->prepare("
    SELECT s.*, sc.category_name, sc.icon
    FROM services s
    JOIN service_categories sc ON s.category_id = sc.category_id
    ORDER BY sc.display_order, s.service_name
");
$stmt->execute();
$services = $stmt->fetchAll();

// Get statistics
$stmt = $conn->query("SELECT COUNT(*) as total FROM services WHERE status = 'active'");
$active_services = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM services WHERE status = 'inactive'");
$inactive_services = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM service_categories WHERE status = 'active'");
$total_categories = $stmt->fetch()['total'];

// Get service categories
$stmt = $conn->query("SELECT * FROM service_categories ORDER BY display_order");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services Management - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <!-- Hero Section -->
                <div class="hero-section">
                    <div class="hero-content">
                        <h1>Services Management</h1>
                        <p>Manage all services and categories offered to students</p>
                        <div class="hero-stats">
                            <div class="hero-stat">
                                <i class="fas fa-concierge-bell"></i>
                                <span><?php echo $active_services; ?> Active Services</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-ban"></i>
                                <span><?php echo $inactive_services; ?> Inactive</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-th-large"></i>
                                <span><?php echo $total_categories; ?> Categories</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-concierge-bell"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $active_services; ?></h3>
                            <p>Active Services</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-ban"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $inactive_services; ?></h3>
                            <p>Inactive</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-th-large"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_categories; ?></h3>
                            <p>Categories</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="fas fa-list"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $active_services + $inactive_services; ?></h3>
                            <p>Total Services</p>
                        </div>
                    </div>
                </div>

                <!-- Page Header -->
                <div class="section-header">
                    <h2 class="section-title"><i class="fas fa-concierge-bell"></i> All Services</h2>
                    <div>
                        <button class="btn btn-secondary" onclick="showMessageModal('info', 'Coming Soon', 'Manage categories coming soon')" style="margin-right: 10px;">
                            <i class="fas fa-th-large"></i> Manage Categories
                        </button>
                        <button class="btn btn-primary" onclick="showMessageModal('info', 'Coming Soon', 'Add service functionality coming soon')">
                            <i class="fas fa-plus"></i> Add Service
                        </button>
                    </div>
                </div>

                <!-- Services by Category -->
                <?php foreach($categories as $category): ?>
                    <?php
                    // Get services for this category
                    $stmt = $conn->prepare("SELECT * FROM services WHERE category_id = ? ORDER BY service_name");
                    $stmt->execute([$category['category_id']]);
                    $category_services = $stmt->fetchAll();
                    
                    if(count($category_services) == 0) continue;
                    ?>
                    
                    <div class="section">
                        <h3 style="margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas <?php echo htmlspecialchars($category['icon']); ?>" style="color: var(--blue);"></i>
                            <?php echo htmlspecialchars($category['category_name']); ?>
                            <span class="badge badge-blue" style="margin-left: 10px;"><?php echo count($category_services); ?> services</span>
                        </h3>
                        
                        <div class="students-table-container">
                            <table class="students-table">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Description</th>
                                        <th style="width: 120px;">Duration</th>
                                        <th style="width: 150px;">Booking Rules</th>
                                        <th style="width: 100px;">Status</th>
                                        <th style="width: 150px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($category_services as $service): ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                                <strong style="font-size: 15px;"><?php echo htmlspecialchars($service['service_name']); ?></strong>
                                                <span style="font-size: 12px; color: #6b7280; font-family: monospace;">
                                                    <i class="fas fa-hashtag"></i> <?php echo htmlspecialchars($service['service_code']); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <p style="font-size: 13px; color: #6b7280; line-height: 1.5; margin: 0;">
                                                <?php echo htmlspecialchars($service['description']); ?>
                                            </p>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 6px; color: var(--blue); font-weight: 600;">
                                                <i class="fas fa-clock"></i>
                                                <?php echo $service['duration_minutes']; ?> min
                                            </div>
                                        </td>
                                        <td>
                                            <div style="display: flex; flex-direction: column; gap: 6px; font-size: 12px;">
                                                <span><i class="fas fa-calendar-alt" style="color: var(--blue); width: 14px;"></i> <?php echo $service['max_advance_booking_days']; ?> days</span>
                                                <span><i class="fas fa-times-circle" style="color: var(--warning); width: 14px;"></i> <?php echo $service['cancellation_hours']; ?>h cancel</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?php echo $service['status']; ?>">
                                                <?php echo ucfirst($service['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="service-details.php?id=<?php echo $service['service_id']; ?>" class="btn-table btn-view">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit-service.php?id=<?php echo $service['service_id']; ?>" class="btn-table btn-edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <?php include '../assets/includes/modals.php'; ?>
    <link rel="stylesheet" href="../assets/css/modals.css">
    <script src="../assets/js/modals.js"></script>
    <script src="js/dashboard.js"></script>
    <script>
        function confirmDelete(serviceId) {
            showConfirmModal(
                'Delete Service',
                'Are you sure you want to delete this service? This action cannot be undone.',
                function() {
                    showMessageModal('info', 'Coming Soon', 'Delete functionality coming soon for service: ' + serviceId);
                }
            );
        }
    </script>
</body>
</html>
