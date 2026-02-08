<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

$student_id = $_SESSION['student_id'];

// Get all notifications
$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND user_type = 'student' ORDER BY created_at DESC");
$stmt->execute([$student_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mark all as read
$update = $conn->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND user_type = 'student' AND is_read = 0");
$update->execute([$student_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - WSU Booking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Notifications</h2>
                    </div>

                    <?php if (empty($notifications)): ?>
                        <div class="empty-state">
                            <i class="fas fa-bell-slash"></i>
                            <h3>No Notifications</h3>
                            <p>You don't have any notifications yet.</p>
                        </div>
                    <?php else: ?>
                        <div class="notifications-list">
                            <?php foreach ($notifications as $notif): 
                                $time_ago = time() - strtotime($notif['created_at']);
                                if ($time_ago < 60) {
                                    $time_text = 'Just now';
                                } elseif ($time_ago < 3600) {
                                    $time_text = floor($time_ago / 60) . ' minutes ago';
                                } elseif ($time_ago < 86400) {
                                    $time_text = floor($time_ago / 3600) . ' hours ago';
                                } else {
                                    $time_text = floor($time_ago / 86400) . ' days ago';
                                }
                                
                                $icon_class = 'fa-bell';
                                $icon_color = 'blue';
                                if (strpos($notif['notification_type'], 'confirmed') !== false) {
                                    $icon_class = 'fa-check-circle';
                                    $icon_color = 'green';
                                } elseif (strpos($notif['notification_type'], 'cancelled') !== false) {
                                    $icon_class = 'fa-times-circle';
                                    $icon_color = 'red';
                                } elseif (strpos($notif['notification_type'], 'reminder') !== false) {
                                    $icon_class = 'fa-clock';
                                    $icon_color = 'orange';
                                }
                            ?>
                                <div class="notification-item <?php echo $notif['is_read'] ? 'read' : 'unread'; ?>">
                                    <div class="notification-icon <?php echo $icon_color; ?>">
                                        <i class="fas <?php echo $icon_class; ?>"></i>
                                    </div>
                                    <div class="notification-content">
                                        <h4><?php echo htmlspecialchars($notif['title']); ?></h4>
                                        <p><?php echo htmlspecialchars($notif['message']); ?></p>
                                        <span class="notification-time">
                                            <i class="far fa-clock"></i> <?php echo $time_text; ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="js/dashboard.js"></script>
</body>
</html>
