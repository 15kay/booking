<?php
session_start();
if(!isset($_SESSION['staff_id'])) {
    header('Location: ../staff-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

$staff_id = $_SESSION['staff_id'];
$role = $_SESSION['role'];

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Get notifications from database
$query = "
    SELECT * FROM notifications 
    WHERE user_id = ? AND user_type = 'staff'
";

$params = [$staff_id];

if($filter == 'unread') {
    $query .= " AND is_read = 0";
}

$query .= " ORDER BY created_at DESC OFFSET 0 ROWS FETCH NEXT 50 ROWS ONLY";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$notifications = $stmt->fetchAll();

// Get unread count
$unread_stmt = $conn->prepare("
    SELECT COUNT(*) as count FROM notifications 
    WHERE user_id = ? AND user_type = 'staff' AND is_read = 0
");
$unread_stmt->execute([$staff_id]);
$unread_count = $unread_stmt->fetch()['count'];

// Handle mark as read
if(isset($_GET['mark_read']) && isset($_GET['id'])) {
    $notif_id = intval($_GET['id']);
    $mark_stmt = $conn->prepare("
        UPDATE notifications 
        SET is_read = 1, read_at = GETDATE() 
        WHERE notification_id = ? AND user_id = ?
    ");
    $mark_stmt->execute([$notif_id, $staff_id]);
    header('Location: notifications.php');
    exit();
}

// Handle mark all as read
if(isset($_GET['mark_all_read'])) {
    $mark_all_stmt = $conn->prepare("
        UPDATE notifications 
        SET is_read = 1, read_at = GETDATE() 
        WHERE user_id = ? AND user_type = 'staff' AND is_read = 0
    ");
    $mark_all_stmt->execute([$staff_id]);
    header('Location: notifications.php?success=All notifications marked as read');
    exit();
}

// Handle clear all
if(isset($_GET['clear_all'])) {
    $clear_stmt = $conn->prepare("
        DELETE FROM notifications 
        WHERE user_id = ? AND user_type = 'staff'
    ");
    $clear_stmt->execute([$staff_id]);
    header('Location: notifications.php?success=All notifications cleared');
    exit();
}

// Format time ago
function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if($diff < 60) return 'Just now';
    if($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if($diff < 604800) return floor($diff / 86400) . ' days ago';
    if($diff < 2592000) return floor($diff / 604800) . ' weeks ago';
    return date('M j, Y', $time);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .notifications-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .notification-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #e5e7eb;
            display: flex;
            gap: 20px;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .notification-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transform: translateX(5px);
        }
        
        .notification-item.unread {
            background: #eff6ff;
            border-left-color: var(--blue);
        }
        
        .notification-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .notification-icon.blue {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .notification-icon.green {
            background: #d1fae5;
            color: #065f46;
        }
        
        .notification-icon.orange {
            background: #fef3c7;
            color: #92400e;
        }
        
        .notification-icon.red {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark);
            margin: 0 0 5px 0;
        }
        
        .notification-message {
            font-size: 14px;
            color: #6b7280;
            margin: 0 0 8px 0;
            line-height: 1.5;
        }
        
        .notification-time {
            font-size: 12px;
            color: #9ca3af;
        }
        
        .notification-badge {
            width: 10px;
            height: 10px;
            background: var(--blue);
            border-radius: 50%;
            flex-shrink: 0;
            margin-top: 5px;
        }
        
        .empty-notifications {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
        }
        
        .empty-notifications i {
            font-size: 64px;
            color: #e5e7eb;
            margin-bottom: 20px;
        }
        
        .empty-notifications h3 {
            margin: 0 0 10px 0;
            color: var(--dark);
        }
        
        .empty-notifications p {
            margin: 0;
            color: #6b7280;
        }
        
        .filter-buttons {
            display: flex;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <div class="page-header">
                    <h1><i class="fas fa-bell"></i> Notifications</h1>
                    <p>Stay updated with your latest activities</p>
                </div>

                <?php if(isset($_GET['success'])): ?>
                    <div style="margin-bottom: 20px; padding: 15px 20px; background: #d1fae5; border-radius: 8px; border-left: 4px solid var(--green);">
                        <i class="fas fa-check-circle" style="color: var(--green);"></i> 
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr);">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo count($notifications); ?></h3>
                            <p>Total Notifications</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $unread_count; ?></h3>
                            <p>Unread Notifications</p>
                        </div>
                    </div>
                </div>

                <!-- Notifications Header -->
                <div class="notifications-header">
                    <h2 class="section-title">
                        <i class="fas fa-list"></i> 
                        <?php echo $filter == 'unread' ? 'Unread' : 'All'; ?> Notifications
                        <?php if($unread_count > 0): ?>
                            <span style="background: var(--blue); color: white; padding: 4px 10px; border-radius: 12px; font-size: 12px; margin-left: 10px;">
                                <?php echo $unread_count; ?> New
                            </span>
                        <?php endif; ?>
                    </h2>
                    <div class="filter-buttons">
                        <a href="?filter=all" class="btn btn-secondary btn-sm <?php echo $filter == 'all' ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> All
                        </a>
                        <a href="?filter=unread" class="btn btn-secondary btn-sm <?php echo $filter == 'unread' ? 'active' : ''; ?>">
                            <i class="fas fa-envelope"></i> Unread
                        </a>
                        <?php if($unread_count > 0): ?>
                            <a href="?mark_all_read=1" class="btn btn-primary btn-sm" onclick="return confirm('Mark all notifications as read?')">
                                <i class="fas fa-check-double"></i> Mark All Read
                            </a>
                        <?php endif; ?>
                        <?php if(count($notifications) > 0): ?>
                            <a href="?clear_all=1" class="btn btn-danger btn-sm" onclick="return confirm('Clear all notifications? This cannot be undone.')">
                                <i class="fas fa-trash"></i> Clear All
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Notifications List -->
                <div class="section">
                    <?php if(count($notifications) > 0): ?>
                        <?php foreach($notifications as $notification): ?>
                            <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>" 
                                 onclick="window.location.href='?mark_read=1&id=<?php echo $notification['notification_id']; ?>'">
                                <div class="notification-icon <?php echo $notification['color']; ?>">
                                    <i class="fas <?php echo $notification['icon']; ?>"></i>
                                </div>
                                <div class="notification-content">
                                    <h3 class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></h3>
                                    <p class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></p>
                                    <span class="notification-time">
                                        <i class="fas fa-clock"></i> <?php echo timeAgo($notification['created_at']); ?>
                                    </span>
                                    <?php if($notification['link']): ?>
                                        <a href="<?php echo htmlspecialchars($notification['link']); ?>" 
                                           style="margin-left: 15px; color: var(--blue); text-decoration: none; font-size: 12px;"
                                           onclick="event.stopPropagation();">
                                            <i class="fas fa-arrow-right"></i> View Details
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <?php if(!$notification['is_read']): ?>
                                    <div class="notification-badge"></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-notifications">
                            <i class="fas fa-bell-slash"></i>
                            <h3>No Notifications</h3>
                            <p>
                                <?php if($filter == 'unread'): ?>
                                    You have no unread notifications. <a href="?filter=all">View all notifications</a>
                                <?php else: ?>
                                    You're all caught up! Check back later for updates.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Info Box -->
                <div style="margin-top: 30px; padding: 20px; background: #f9fafb; border-radius: 12px; border-left: 4px solid var(--blue);">
                    <h3 style="margin: 0 0 10px 0; color: var(--dark); font-size: 16px;">
                        <i class="fas fa-info-circle" style="color: var(--blue);"></i> About Notifications
                    </h3>
                    <p style="margin: 0; color: #6b7280; font-size: 14px; line-height: 1.6;">
                        You'll receive notifications for important events such as new assignments, student registrations, 
                        upcoming sessions, and system updates. Click on any notification to mark it as read.
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../assets/includes/modals.php'; ?>
    <link rel="stylesheet" href="../assets/css/modals.css">
    <script src="../assets/js/modals.js"></script>
    <script src="js/dashboard.js"></script>
</body>
</html>

