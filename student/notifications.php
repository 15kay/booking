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

$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND user_type = 'student' ORDER BY created_at DESC");
$stmt->execute([$student_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

$unread_count = count(array_filter($notifications, fn($n) => !$n['is_read']));

// Mark all as read
$conn->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND user_type = 'student' AND is_read = 0")->execute([$student_id]);

function time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return 'Just now';
    if ($diff < 3600)   return floor($diff / 60) . 'm ago';
    if ($diff < 86400)  return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('d M Y', strtotime($datetime));
}

function notif_meta($type) {
    if (strpos($type, 'confirmed') !== false) return ['fa-check-circle', '#10b981', 'rgba(16,185,129,0.1)', 'Booking'];
    if (strpos($type, 'cancelled') !== false) return ['fa-times-circle', '#ef4444', 'rgba(239,68,68,0.1)',  'Cancelled'];
    if (strpos($type, 'reminder')  !== false) return ['fa-clock',        '#f59e0b', 'rgba(245,158,11,0.1)', 'Reminder'];
    if (strpos($type, 'completed') !== false) return ['fa-star',         '#8b5cf6', 'rgba(139,92,246,0.1)', 'Completed'];
    return ['fa-bell', '#7A1C1C', 'rgba(122,28,28,0.1)', 'General'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - WSU Booking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .notif-hero {
            background: linear-gradient(135deg, #3D0A0A 0%, #7A1C1C 60%, #E8A020 100%);
            border-radius: 16px;
            padding: 32px 40px;
            color: #fff;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .notif-hero h1 { font-size: 24px; margin-bottom: 4px; }
        .notif-hero p  { opacity: 0.8; font-size: 13px; }
        .notif-hero-icon { font-size: 64px; opacity: 0.15; }
        .unread-pill {
            display: inline-flex; align-items: center; gap: 6px;
            background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3);
            padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 700;
            margin-top: 10px;
        }

        /* Filter bar */
        .notif-bar {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 18px; gap: 12px; flex-wrap: wrap;
        }
        .notif-filters { display: flex; gap: 6px; flex-wrap: wrap; }
        .notif-filter {
            padding: 8px 16px; border-radius: 20px; border: 1.5px solid #e5e7eb;
            font-size: 12px; font-weight: 600; color: #6b7280; cursor: pointer;
            background: #fff; transition: all 0.2s;
        }
        .notif-filter:hover { border-color: #7A1C1C; color: #7A1C1C; }
        .notif-filter.active { background: #7A1C1C; border-color: #7A1C1C; color: #fff; }
        .mark-all-btn {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 12px; font-weight: 600; color: #7A1C1C;
            background: none; border: none; cursor: pointer; padding: 8px 14px;
            border-radius: 8px; transition: background 0.2s;
        }
        .mark-all-btn:hover { background: rgba(122,28,28,0.06); }

        /* Notification list */
        .notif-list { display: flex; flex-direction: column; gap: 10px; }

        .notif-item {
            background: #fff;
            border-radius: 14px;
            padding: 18px 20px;
            display: flex;
            align-items: flex-start;
            gap: 16px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
            border: 1.5px solid #f3f4f6;
            transition: all 0.2s;
            position: relative;
        }
        .notif-item:hover { border-color: #e5e7eb; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .notif-item.unread { border-left: 4px solid #7A1C1C; background: #fffaf9; }

        .notif-item-icon {
            width: 44px; height: 44px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; flex-shrink: 0;
        }
        .notif-item-body { flex: 1; min-width: 0; }
        .notif-item-top {
            display: flex; align-items: flex-start;
            justify-content: space-between; gap: 10px; margin-bottom: 4px;
        }
        .notif-item-title { font-size: 14px; font-weight: 700; color: #1a1a1a; }
        .notif-item-time  { font-size: 11px; color: #9ca3af; white-space: nowrap; flex-shrink: 0; }
        .notif-item-msg   { font-size: 13px; color: #6b7280; line-height: 1.5; }
        .notif-item-tag {
            display: inline-block; margin-top: 8px;
            font-size: 10px; font-weight: 700; padding: 2px 10px;
            border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .unread-dot {
            width: 8px; height: 8px; background: #7A1C1C; border-radius: 50%;
            position: absolute; top: 18px; right: 18px;
        }

        /* Empty state */
        .notif-empty {
            text-align: center; padding: 80px 20px;
            background: #fff; border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .notif-empty-icon {
            width: 80px; height: 80px; background: #f3f4f6; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 32px; color: #d1d5db; margin: 0 auto 20px;
        }
        .notif-empty h3 { font-size: 18px; color: #1a1a1a; margin-bottom: 8px; }
        .notif-empty p  { font-size: 14px; color: #9ca3af; }

        @media (max-width: 768px) {
            .notif-hero { flex-direction: column; gap: 10px; padding: 24px 20px; }
            .notif-hero-icon { display: none; }
        }
    </style>
</head>
<body>
<div class="dashboard-layout">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        <div class="content">

            <!-- Hero -->
            <div class="notif-hero">
                <div>
                    <h1><i class="fas fa-bell"></i> Notifications</h1>
                    <p>Stay up to date with your bookings and alerts.</p>
                    <?php if ($unread_count > 0): ?>
                        <span class="unread-pill"><i class="fas fa-circle" style="font-size:7px"></i> <?php echo $unread_count; ?> unread</span>
                    <?php else: ?>
                        <span class="unread-pill"><i class="fas fa-check" style="font-size:9px"></i> All caught up</span>
                    <?php endif; ?>
                </div>
                <i class="fas fa-bell notif-hero-icon"></i>
            </div>

            <?php if (empty($notifications)): ?>
                <div class="notif-empty">
                    <div class="notif-empty-icon"><i class="fas fa-bell-slash"></i></div>
                    <h3>No Notifications Yet</h3>
                    <p>When you receive booking updates or alerts, they'll appear here.</p>
                </div>
            <?php else: ?>

                <!-- Filter bar -->
                <div class="notif-bar">
                    <div class="notif-filters">
                        <button class="notif-filter active" data-filter="all">All</button>
                        <button class="notif-filter" data-filter="booking">Bookings</button>
                        <button class="notif-filter" data-filter="reminder">Reminders</button>
                        <button class="notif-filter" data-filter="cancelled">Cancelled</button>
                    </div>
                    <button class="mark-all-btn"><i class="fas fa-check-double"></i> Mark all read</button>
                </div>

                <!-- List -->
                <div class="notif-list">
                    <?php foreach ($notifications as $notif):
                        [$icon, $color, $bg, $tag] = notif_meta($notif['notification_type']);
                        $filter_key = strtolower($tag);
                    ?>
                    <div class="notif-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>" data-type="<?php echo $filter_key; ?>">
                        <div class="notif-item-icon" style="background:<?php echo $bg; ?>;color:<?php echo $color; ?>;">
                            <i class="fas <?php echo $icon; ?>"></i>
                        </div>
                        <div class="notif-item-body">
                            <div class="notif-item-top">
                                <span class="notif-item-title"><?php echo htmlspecialchars($notif['title']); ?></span>
                                <span class="notif-item-time"><i class="far fa-clock"></i> <?php echo time_ago($notif['created_at']); ?></span>
                            </div>
                            <p class="notif-item-msg"><?php echo htmlspecialchars($notif['message']); ?></p>
                            <span class="notif-item-tag" style="background:<?php echo $bg; ?>;color:<?php echo $color; ?>;"><?php echo $tag; ?></span>
                        </div>
                        <?php if (!$notif['is_read']): ?>
                            <div class="unread-dot"></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<script src="js/dashboard.js"></script>
<script>
    document.querySelectorAll('.notif-filter').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.notif-filter').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            var filter = btn.dataset.filter;
            document.querySelectorAll('.notif-item').forEach(function(item) {
                item.style.display = (filter === 'all' || item.dataset.type === filter) ? '' : 'none';
            });
        });
    });
</script>
</body>
</html>
