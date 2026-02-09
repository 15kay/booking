<?php
session_start();
if(!isset($_SESSION['staff_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get notifications
$stmt = $conn->prepare("
    SELECT n.*, b.booking_reference, b.booking_date, b.start_time
    FROM notifications n
    LEFT JOIN bookings b ON n.booking_id = b.booking_id
    WHERE n.user_id = ? AND n.user_type = 'staff'
    ORDER BY n.created_at DESC
    LIMIT 50
");
$stmt->execute([$_SESSION['staff_id']]);
$notifications = $stmt->fetchAll();

// Get unread count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND user_type = 'staff' AND is_read = 0");
$stmt->execute([$_SESSION['staff_id']]);
$unread_count = $stmt->fetch()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - WSU Booking</title>
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
                        <h1>Notifications Center</h1>
                        <p>Stay updated with all your appointment notifications and system alerts</p>
                        <div class="hero-stats">
                            <div class="hero-stat">
                                <i class="fas fa-bell"></i>
                                <span><?php echo count($notifications); ?> Total Notifications</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-envelope"></i>
                                <span><?php echo $unread_count; ?> Unread</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-check-circle"></i>
                                <span><?php echo count($notifications) - $unread_count; ?> Read</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <?php if($unread_count > 0): ?>
                <div class="form-actions" style="margin-bottom: 20px;">
                    <button class="btn btn-primary" onclick="markAllRead()">
                        <i class="fas fa-check-double"></i> Mark All as Read
                    </button>
                </div>
                <?php endif; ?>

                <!-- Notifications List -->
                <?php if(count($notifications) > 0): ?>
                    <div class="section">
                        <div class="notifications-list">
                            <?php foreach($notifications as $notification): ?>
                            <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                                <div class="notification-icon <?php 
                                    echo strpos($notification['notification_type'], 'confirmed') !== false ? 'green' : 
                                        (strpos($notification['notification_type'], 'cancelled') !== false ? 'red' : 'blue'); 
                                ?>">
                                    <i class="fas fa-<?php 
                                        echo strpos($notification['notification_type'], 'confirmed') !== false ? 'check-circle' : 
                                            (strpos($notification['notification_type'], 'cancelled') !== false ? 'times-circle' : 'bell'); 
                                    ?>"></i>
                                </div>
                                <div class="notification-content">
                                    <h4><?php echo htmlspecialchars($notification['title']); ?></h4>
                                    <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <?php if($notification['booking_reference']): ?>
                                        <p><i class="fas fa-hashtag"></i> <?php echo htmlspecialchars($notification['booking_reference']); ?></p>
                                    <?php endif; ?>
                                    <span class="notification-time">
                                        <i class="fas fa-clock"></i>
                                        <?php 
                                            $time_diff = time() - strtotime($notification['created_at']);
                                            if($time_diff < 3600) {
                                                echo floor($time_diff / 60) . ' minutes ago';
                                            } elseif($time_diff < 86400) {
                                                echo floor($time_diff / 3600) . ' hours ago';
                                            } else {
                                                echo date('d M Y, H:i', strtotime($notification['created_at']));
                                            }
                                        ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="section">
                        <div class="empty-state">
                            <i class="fas fa-bell-slash"></i>
                            <h3>No Notifications</h3>
                            <p>You don't have any notifications yet</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include '../assets/includes/modals.php'; ?>
    <link rel="stylesheet" href="../assets/css/modals.css">
    <script src="../assets/js/modals.js"></script>
    <script src="js/dashboard.js"></script>
    <script>
        function markAllRead() {
            showConfirmModal(
                'Mark All as Read',
                'Mark all notifications as read?',
                function() {
                    window.location.href = 'mark-notifications-read.php';
                }
            );
        }
    </script>
</body>
</html>
