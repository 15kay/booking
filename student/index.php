<?php
session_start();
if (!isset($_SESSION['student_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';
$db   = new Database();
$conn = $db->connect();
$sid  = $_SESSION['student_id'];

// Stats
$stats = ['total'=>0,'pending'=>0,'confirmed'=>0,'completed'=>0,'cancelled'=>0,'no_show'=>0];
try {
    $stmt = $conn->prepare("SELECT
        COUNT(*) as total,
        COUNT(CASE WHEN status='pending'   THEN 1 END) as pending,
        COUNT(CASE WHEN status='confirmed' THEN 1 END) as confirmed,
        COUNT(CASE WHEN status='completed' THEN 1 END) as completed,
        COUNT(CASE WHEN status='cancelled' THEN 1 END) as cancelled,
        COUNT(CASE WHEN status='no_show'   THEN 1 END) as no_show
        FROM bookings WHERE student_id = ?");
    $stmt->execute([$sid]);
    $stats = $stmt->fetch() ?: $stats;
} catch(Exception $e) {}

// Student info
$student = ['first_name'=>$_SESSION['first_name']??'Student','last_name'=>$_SESSION['last_name']??'','faculty_name'=>'WSU Student','reading_score'=>0,'status'=>'active','year_of_study'=>1,'student_type'=>'undergraduate','created_at'=>date('Y-m-d'),'last_login'=>null,'phone'=>''];
try {
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$sid]);
    $row = $stmt->fetch();
    if ($row) $student = array_merge($student, $row);
} catch(Exception $e) {}

// Readiness score
$success_score = 0;
if (!empty($student['reading_score'])) {
    $success_score = round($student['reading_score']);
} elseif ($stats['total'] > 0) {
    $rate = ($stats['completed'] / $stats['total']) * 100;
    $eng  = min(100, ($stats['total'] / 10) * 100);
    $success_score = max(0, min(100, round(($rate * 0.5) + ($eng * 0.3) + 20 - ($stats['no_show'] * 10) - ($stats['cancelled'] * 5))));
}

if ($success_score >= 80)     { $sc = '#10b981'; $sb = '#d1fae5'; $sl = 'Excellent';      $si = 'fa-star'; }
elseif ($success_score >= 60) { $sc = '#2563eb'; $sb = '#dbeafe'; $sl = 'Good';           $si = 'fa-thumbs-up'; }
elseif ($success_score >= 40) { $sc = '#f59e0b'; $sb = '#fef3c7'; $sl = 'Fair';           $si = 'fa-hand-paper'; }
else                          { $sc = '#ef4444'; $sb = '#fee2e2'; $sl = 'Needs Outreach'; $si = 'fa-exclamation-triangle'; }

// Upcoming bookings
$stmt = $conn->prepare("
    SELECT b.*, s.service_name, st.first_name as staff_first, st.last_name as staff_last
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN staff st ON b.staff_id = st.staff_id
    WHERE b.student_id = ? AND b.booking_date >= CURDATE() AND b.status IN ('pending','confirmed')
    ORDER BY b.booking_date, b.start_time LIMIT 4
");
$stmt->execute([$sid]);
$upcoming = $stmt->fetchAll();

// Recent activity
$stmt = $conn->prepare("
    SELECT b.*, s.service_name FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    WHERE b.student_id = ? ORDER BY b.created_at DESC LIMIT 4
");
$stmt->execute([$sid]);
$recent = $stmt->fetchAll();

$first_name = $_SESSION['first_name'] ?? explode(' ', $student['first_name'])[0];
$initials   = strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1));

$hour = (int)date('H');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - WSU Booking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css?v=3">
    <link rel="stylesheet" href="../assets/css/modals.css">
    <style>
        /* Hero */
        .dash-hero {
            background: linear-gradient(135deg, #3D0A0A 0%, #7A1C1C 55%, #E8A020 100%);
            border-radius: 20px;
            padding: 36px 40px;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }
        .dash-hero::before {
            content: '';
            position: absolute;
            width: 300px; height: 300px;
            background: rgba(255,255,255,0.04);
            border-radius: 50%;
            top: -80px; right: 120px;
        }
        .dash-hero-avatar {
            width: 56px; height: 56px;
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.35);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; font-weight: 800; flex-shrink: 0;
        }
        .dash-hero-text h1 { font-size: 24px; margin-bottom: 4px; }
        .dash-hero-text p  { opacity: 0.8; font-size: 13px; }
        .dash-hero-actions { display: flex; gap: 10px; flex-shrink: 0; }
        .hero-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 11px 22px; border-radius: 10px; font-size: 13px;
            font-weight: 700; cursor: pointer; text-decoration: none;
            transition: all 0.2s; border: none;
        }
        .hero-btn-primary { background: #fff; color: #7A1C1C; }
        .hero-btn-primary:hover { background: #f3f4f6; transform: translateY(-2px); }
        .hero-btn-outline { background: rgba(255,255,255,0.15); color: #fff; border: 1.5px solid rgba(255,255,255,0.3); }
        .hero-btn-outline:hover { background: rgba(255,255,255,0.25); }

        /* Stat cards */
        .dash-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .dash-stat {
            background: #fff;
            border-radius: 14px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            border: 1.5px solid #f3f4f6;
            transition: all 0.2s;
        }
        .dash-stat:hover { border-color: #e5e7eb; box-shadow: 0 4px 16px rgba(0,0,0,0.08); transform: translateY(-2px); }
        .dash-stat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
        }
        .dash-stat h3 { font-size: 26px; font-weight: 800; color: #1a1a1a; line-height: 1; }
        .dash-stat p  { font-size: 12px; color: #9ca3af; margin-top: 3px; }

        /* Main grid */
        .dash-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 20px;
            margin-bottom: 20px;
        }

        /* Cards */
        .dash-card {
            background: #fff;
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .dash-card-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 18px; padding-bottom: 14px; border-bottom: 2px solid #f3f4f6;
        }
        .dash-card-title {
            font-size: 14px; font-weight: 700; color: #3D0A0A;
            display: flex; align-items: center; gap: 8px;
        }
        .dash-card-title i { color: #E8A020; }
        .dash-card-link {
            font-size: 12px; font-weight: 600; color: #7A1C1C;
            text-decoration: none; display: flex; align-items: center; gap: 4px;
        }
        .dash-card-link:hover { text-decoration: underline; }

        /* Booking items */
        .booking-row {
            display: flex; align-items: center; gap: 14px;
            padding: 12px 0; border-bottom: 1px solid #f9fafb;
        }
        .booking-row:last-child { border-bottom: none; padding-bottom: 0; }
        .booking-row-icon {
            width: 40px; height: 40px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px; flex-shrink: 0;
            background: rgba(122,28,28,0.08); color: #7A1C1C;
        }
        .booking-row-info { flex: 1; min-width: 0; }
        .booking-row-info h4 { font-size: 13px; font-weight: 700; color: #1a1a1a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .booking-row-info p  { font-size: 11px; color: #9ca3af; margin-top: 2px; }
        .booking-row-badge {
            font-size: 10px; font-weight: 700; padding: 3px 10px;
            border-radius: 20px; white-space: nowrap; flex-shrink: 0;
        }
        .badge-pending   { background: #fef3c7; color: #b45309; }
        .badge-confirmed { background: #d1fae5; color: #065f46; }
        .badge-completed { background: #dbeafe; color: #1e40af; }
        .badge-cancelled { background: #fee2e2; color: #7f1d1d; }

        /* Readiness card */
        .readiness-widget {
            border-left: 4px solid <?php echo $sc; ?>;
        }
        .readiness-top {
            display: flex; align-items: center; gap: 16px; margin-bottom: 16px;
        }
        .readiness-circle { position: relative; width: 80px; height: 80px; flex-shrink: 0; }
        .readiness-circle-text {
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%); text-align: center;
        }
        .readiness-circle-text .rnum { font-size: 20px; font-weight: 800; color: <?php echo $sc; ?>; line-height: 1; }
        .readiness-circle-text .rlbl { font-size: 9px; font-weight: 700; color: <?php echo $sc; ?>; }
        .readiness-label { font-size: 13px; font-weight: 700; color: #1a1a1a; margin-bottom: 3px; }
        .readiness-sub   { font-size: 11px; color: #9ca3af; }
        .readiness-bars  { display: flex; flex-direction: column; gap: 8px; }
        .rbar-row { display: flex; align-items: center; gap: 8px; }
        .rbar-label { font-size: 11px; color: #6b7280; width: 70px; flex-shrink: 0; }
        .rbar-track { flex: 1; height: 6px; background: #f3f4f6; border-radius: 6px; overflow: hidden; }
        .rbar-fill  { height: 100%; border-radius: 6px; }
        .rbar-val   { font-size: 11px; font-weight: 700; color: #1a1a1a; width: 24px; text-align: right; flex-shrink: 0; }

        /* Quick actions */
        .quick-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
            margin-bottom: 20px;
        }
        .quick-card {
            background: #fff;
            border-radius: 14px;
            padding: 20px 16px;
            text-align: center;
            text-decoration: none;
            border: 1.5px solid #f3f4f6;
            transition: all 0.2s;
            display: flex; flex-direction: column; align-items: center; gap: 10px;
        }
        .quick-card:hover { border-color: #7A1C1C; transform: translateY(-3px); box-shadow: 0 8px 20px rgba(122,28,28,0.1); }
        .quick-card-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 20px;
        }
        .quick-card span { font-size: 12px; font-weight: 700; color: #374151; }

        /* Empty state */
        .dash-empty { text-align: center; padding: 40px 20px; color: #9ca3af; }
        .dash-empty i { font-size: 36px; margin-bottom: 10px; display: block; color: #e5e7eb; }
        .dash-empty p { font-size: 13px; }

        @media (max-width: 1024px) {
            .dash-grid { grid-template-columns: 1fr; }
            .dash-stats { grid-template-columns: repeat(2, 1fr); }
            .quick-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 600px) {
            .dash-hero { flex-direction: column; align-items: flex-start; padding: 24px 20px; }
            .dash-hero::before { display: none; }
            .dash-stats { grid-template-columns: 1fr 1fr; }
            .dash-hero-actions { width: 100%; }
            .hero-btn { flex: 1; justify-content: center; }
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
            <div class="dash-hero">
                <div style="display:flex;align-items:center;gap:16px;flex:1;min-width:0;">
                    <div class="dash-hero-avatar"><?php echo $initials; ?></div>
                    <div class="dash-hero-text">
                        <h1><?php echo $greeting; ?>, <?php echo htmlspecialchars($first_name); ?>! 👋</h1>
                        <p><?php echo htmlspecialchars($student['faculty_name'] ?? 'WSU Student'); ?> &nbsp;·&nbsp; <?php echo date('l, d F Y'); ?></p>
                    </div>
                </div>
                <div class="dash-hero-actions">
                    <a href="book-service.php" class="hero-btn hero-btn-primary"><i class="fas fa-plus"></i> Book Service</a>
                    <a href="hub.php" class="hero-btn hero-btn-outline"><i class="fas fa-th-large"></i> WSU Hub</a>
                </div>
            </div>

            <!-- Stats -->
            <div class="dash-stats">
                <div class="dash-stat">
                    <div class="dash-stat-icon" style="background:rgba(122,28,28,0.1);color:#7A1C1C;"><i class="fas fa-calendar"></i></div>
                    <div><h3><?php echo $stats['total']; ?></h3><p>Total Bookings</p></div>
                </div>
                <div class="dash-stat">
                    <div class="dash-stat-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b;"><i class="fas fa-clock"></i></div>
                    <div><h3><?php echo $stats['pending']; ?></h3><p>Pending</p></div>
                </div>
                <div class="dash-stat">
                    <div class="dash-stat-icon" style="background:rgba(16,185,129,0.1);color:#10b981;"><i class="fas fa-check-circle"></i></div>
                    <div><h3><?php echo $stats['confirmed']; ?></h3><p>Confirmed</p></div>
                </div>
                <div class="dash-stat">
                    <div class="dash-stat-icon" style="background:rgba(37,99,235,0.1);color:#2563eb;"><i class="fas fa-calendar-check"></i></div>
                    <div><h3><?php echo $stats['completed']; ?></h3><p>Completed</p></div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="quick-grid">
                <a href="book-service.php" class="quick-card">
                    <div class="quick-card-icon" style="background:rgba(122,28,28,0.1);color:#7A1C1C;"><i class="fas fa-calendar-plus"></i></div>
                    <span>Book Service</span>
                </a>
                <a href="my-bookings.php" class="quick-card">
                    <div class="quick-card-icon" style="background:rgba(232,160,32,0.12);color:#b87a00;"><i class="fas fa-list-alt"></i></div>
                    <span>My Bookings</span>
                </a>
                <a href="hub.php" class="quick-card">
                    <div class="quick-card-icon" style="background:rgba(99,102,241,0.1);color:#6366f1;"><i class="fas fa-th-large"></i></div>
                    <span>WSU Hub</span>
                </a>
                <a href="profile.php" class="quick-card">
                    <div class="quick-card-icon" style="background:rgba(16,185,129,0.1);color:#10b981;"><i class="fas fa-user-edit"></i></div>
                    <span>My Profile</span>
                </a>
            </div>

            <!-- Main Grid -->
            <div class="dash-grid">

                <!-- Upcoming Bookings -->
                <div class="dash-card">
                    <div class="dash-card-header">
                        <span class="dash-card-title"><i class="fas fa-calendar-alt"></i> Upcoming Appointments</span>
                        <a href="my-bookings.php" class="dash-card-link">View all <i class="fas fa-arrow-right"></i></a>
                    </div>
                    <?php if (count($upcoming) > 0): ?>
                        <?php foreach ($upcoming as $b): ?>
                        <div class="booking-row">
                            <div class="booking-row-icon"><i class="fas fa-calendar-check"></i></div>
                            <div class="booking-row-info">
                                <h4><?php echo htmlspecialchars($b['service_name']); ?></h4>
                                <p><i class="fas fa-calendar" style="font-size:10px"></i> <?php echo date('d M Y', strtotime($b['booking_date'])); ?> &nbsp;·&nbsp; <i class="fas fa-clock" style="font-size:10px"></i> <?php echo date('H:i', strtotime($b['start_time'])); ?> &nbsp;·&nbsp; <?php echo htmlspecialchars($b['staff_first'] . ' ' . $b['staff_last']); ?></p>
                            </div>
                            <span class="booking-row-badge badge-<?php echo $b['status']; ?>"><?php echo ucfirst($b['status']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="dash-empty">
                            <i class="fas fa-calendar-times"></i>
                            <p>No upcoming appointments.<br><a href="book-service.php" style="color:#7A1C1C;font-weight:600;">Book one now</a></p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Right column -->
                <div style="display:flex;flex-direction:column;gap:20px;">

                    <!-- Readiness Score -->
                    <div class="dash-card readiness-widget">
                        <div class="dash-card-header">
                            <span class="dash-card-title"><i class="fas fa-chart-line"></i> Readiness Score</span>
                            <a href="profile.php" class="dash-card-link">Details <i class="fas fa-arrow-right"></i></a>
                        </div>
                        <div class="readiness-top">
                            <div class="readiness-circle">
                                <svg style="transform:rotate(-90deg)" width="80" height="80" viewBox="0 0 80 80">
                                    <circle cx="40" cy="40" r="32" fill="none" stroke="#f3f4f6" stroke-width="7"/>
                                    <circle cx="40" cy="40" r="32" fill="none" stroke="<?php echo $sc; ?>" stroke-width="7"
                                        stroke-dasharray="<?php echo 2*3.14159*32; ?>"
                                        stroke-dashoffset="<?php echo (2*3.14159*32)*(1-$success_score/100); ?>"
                                        stroke-linecap="round"/>
                                </svg>
                                <div class="readiness-circle-text">
                                    <div class="rnum"><?php echo $success_score; ?></div>
                                    <div class="rlbl">SCORE</div>
                                </div>
                            </div>
                            <div>
                                <p class="readiness-label"><i class="fas <?php echo $si; ?>" style="color:<?php echo $sc; ?>"></i> <?php echo $sl; ?></p>
                                <p class="readiness-sub">Engagement level</p>
                            </div>
                        </div>
                        <div class="readiness-bars">
                            <div class="rbar-row">
                                <span class="rbar-label">Completed</span>
                                <div class="rbar-track"><div class="rbar-fill" style="width:<?php echo $stats['total'] > 0 ? round(($stats['completed']/$stats['total'])*100) : 0; ?>%;background:#10b981;"></div></div>
                                <span class="rbar-val"><?php echo $stats['completed']; ?></span>
                            </div>
                            <div class="rbar-row">
                                <span class="rbar-label">Pending</span>
                                <div class="rbar-track"><div class="rbar-fill" style="width:<?php echo $stats['total'] > 0 ? round(($stats['pending']/$stats['total'])*100) : 0; ?>%;background:#f59e0b;"></div></div>
                                <span class="rbar-val"><?php echo $stats['pending']; ?></span>
                            </div>
                            <div class="rbar-row">
                                <span class="rbar-label">Cancelled</span>
                                <div class="rbar-track"><div class="rbar-fill" style="width:<?php echo $stats['total'] > 0 ? round(($stats['cancelled']/$stats['total'])*100) : 0; ?>%;background:#ef4444;"></div></div>
                                <span class="rbar-val"><?php echo $stats['cancelled']; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="dash-card">
                        <div class="dash-card-header">
                            <span class="dash-card-title"><i class="fas fa-history"></i> Recent Activity</span>
                            <a href="my-bookings.php" class="dash-card-link">View all <i class="fas fa-arrow-right"></i></a>
                        </div>
                        <?php if (count($recent) > 0): ?>
                            <?php foreach ($recent as $b): ?>
                            <div class="booking-row">
                                <div class="booking-row-icon" style="background:rgba(99,102,241,0.08);color:#6366f1;"><i class="fas fa-clock-rotate-left"></i></div>
                                <div class="booking-row-info">
                                    <h4><?php echo htmlspecialchars($b['service_name']); ?></h4>
                                    <p><?php echo date('d M Y', strtotime($b['booking_date'])); ?></p>
                                </div>
                                <span class="booking-row-badge badge-<?php echo $b['status']; ?>"><?php echo ucfirst($b['status']); ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="dash-empty"><i class="fas fa-history"></i><p>No activity yet.</p></div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
<div class="modal-overlay" id="successModal">
    <div class="modal">
        <div class="modal-icon"><i class="fas fa-check"></i></div>
        <h2>Booking Successful!</h2>
        <p><?php echo htmlspecialchars($_GET['success']); ?></p>
        <?php if (preg_match('/Reference: (BK\d+)/', $_GET['success'], $m)): ?>
            <div class="booking-ref"><?php echo $m[1]; ?></div>
        <?php endif; ?>
        <div class="modal-actions">
            <button class="modal-btn modal-btn-secondary" onclick="window.location.href='my-bookings.php'"><i class="fas fa-list"></i> View Bookings</button>
            <button class="modal-btn modal-btn-primary" onclick="closeModal()"><i class="fas fa-home"></i> Dashboard</button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include '../assets/includes/modals.php'; ?>
<script src="js/dashboard.js"></script>
<script src="../assets/js/modals.js"></script>
<script>
    function closeModal() {
        var m = document.getElementById('successModal');
        if (m) { m.style.animation = 'fadeOut 0.3s'; setTimeout(() => window.location.href = 'index.php', 300); }
    }
    var sm = document.getElementById('successModal');
    if (sm) setTimeout(closeModal, 10000);
</script>
</body>
</html>
