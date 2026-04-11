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

$stmt = $conn->prepare("SELECT s.*, f.faculty_name FROM students s LEFT JOIN faculties f ON s.faculty_id = f.faculty_id WHERE s.student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_bookings,
        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_sessions,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_sessions,
        COUNT(CASE WHEN status = 'no_show' THEN 1 END) as no_show_sessions
    FROM bookings WHERE student_id = ?
");
$stmt->execute([$student_id]);
$stats = $stmt->fetch();

$success_score = 0;
if (isset($student['reading_score']) && $student['reading_score'] > 0) {
    $success_score = round($student['reading_score']);
} elseif ($stats['total_bookings'] > 0) {
    $completion_rate = ($stats['completed_sessions'] / $stats['total_bookings']) * 100;
    $engagement_level = min(100, ($stats['total_bookings'] / 10) * 100);
    $success_score = max(0, min(100, round(
        ($completion_rate * 0.5) + ($engagement_level * 0.3) + 20
        - ($stats['no_show_sessions'] * 10)
        - ($stats['cancelled_sessions'] * 5)
    )));
}

if ($success_score >= 80)      { $score_color = '#10b981'; $score_bg = '#d1fae5'; $score_label = 'Excellent';      $score_icon = 'fa-star'; }
elseif ($success_score >= 60)  { $score_color = '#2563eb'; $score_bg = '#dbeafe'; $score_label = 'Good';           $score_icon = 'fa-thumbs-up'; }
elseif ($success_score >= 40)  { $score_color = '#f59e0b'; $score_bg = '#fef3c7'; $score_label = 'Fair';           $score_icon = 'fa-hand-paper'; }
else                           { $score_color = '#ef4444'; $score_bg = '#fee2e2'; $score_label = 'Needs Outreach'; $score_icon = 'fa-exclamation-triangle'; }

$initials = strtoupper(substr($student['first_name'], 0, 1) . substr($student['last_name'], 0, 1));
$success = $_GET['success'] ?? '';
$error   = $_GET['error']   ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - WSU Booking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        /* Hero */
        .profile-hero {
            background: linear-gradient(135deg, #3D0A0A 0%, #7A1C1C 60%, #E8A020 100%);
            border-radius: 20px;
            padding: 36px 40px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 28px;
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
        }
        .profile-hero::after {
            content: '\f007';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 40px;
            font-size: 120px;
            opacity: 0.08;
            line-height: 1;
        }
        .profile-hero-avatar {
            width: 80px; height: 80px;
            background: rgba(255,255,255,0.2);
            border: 3px solid rgba(255,255,255,0.4);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px; font-weight: 800; color: #fff;
            flex-shrink: 0;
        }
        .profile-hero-info h1 { font-size: 24px; margin-bottom: 4px; }
        .profile-hero-info p  { opacity: 0.8; font-size: 14px; margin-bottom: 10px; }
        .profile-hero-badges { display: flex; gap: 8px; flex-wrap: wrap; }
        .hero-badge {
            font-size: 11px; font-weight: 700; padding: 4px 12px;
            border-radius: 20px; background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.25);
        }
        .hero-badge.active-badge { background: rgba(16,185,129,0.25); border-color: rgba(16,185,129,0.5); }

        /* Score strip */
        .score-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }
        .score-strip-card {
            background: #fff;
            border-radius: 14px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .score-strip-icon {
            width: 46px; height: 46px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
        }
        .score-strip-card h3 { font-size: 22px; font-weight: 800; color: #1a1a1a; line-height: 1; }
        .score-strip-card p  { font-size: 12px; color: #9ca3af; margin-top: 3px; }

        /* Readiness */
        .readiness-card {
            background: #fff;
            border-radius: 14px;
            padding: 24px 28px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 28px;
            border-left: 5px solid <?php echo $score_color; ?>;
        }
        .readiness-circle { position: relative; width: 100px; height: 100px; flex-shrink: 0; }
        .readiness-circle-text {
            position: absolute; top: 50%; left: 50%;
            transform: translate(-50%, -50%); text-align: center;
        }
        .readiness-circle-text .num { font-size: 26px; font-weight: 800; color: <?php echo $score_color; ?>; line-height: 1; }
        .readiness-circle-text .lbl { font-size: 10px; font-weight: 700; color: <?php echo $score_color; ?>; }
        .readiness-info { flex: 1; }
        .readiness-info h3 { font-size: 16px; font-weight: 700; color: #1a1a1a; margin-bottom: 4px; }
        .readiness-info p  { font-size: 13px; color: #6b7280; margin-bottom: 12px; }
        .readiness-tags { display: flex; gap: 8px; flex-wrap: wrap; }
        .readiness-tag {
            font-size: 12px; font-weight: 600; padding: 4px 12px;
            border-radius: 20px; display: flex; align-items: center; gap: 6px;
        }

        /* Tabs */
        .profile-tabs {
            display: flex; gap: 6px; margin-bottom: 22px;
            background: #fff; padding: 6px; border-radius: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            width: fit-content;
        }
        .profile-tab {
            padding: 10px 20px; border-radius: 10px; border: none;
            font-size: 13px; font-weight: 600; color: #6b7280;
            cursor: pointer; background: none; transition: all 0.2s;
            display: flex; align-items: center; gap: 8px;
        }
        .profile-tab:hover { background: #f3f4f6; color: #3D0A0A; }
        .profile-tab.active { background: linear-gradient(135deg, #7A1C1C, #3D0A0A); color: #fff; }
        .profile-tab.active i { color: #E8A020; }

        .profile-panel { display: none; }
        .profile-panel.active { display: block; }

        /* Cards */
        .pcard {
            background: #fff; border-radius: 14px; padding: 28px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 20px;
        }
        .pcard-title {
            font-size: 15px; font-weight: 700; color: #3D0A0A;
            margin-bottom: 20px; display: flex; align-items: center; gap: 10px;
            padding-bottom: 14px; border-bottom: 2px solid #f3f4f6;
        }
        .pcard-title i { color: #E8A020; }

        /* Info grid */
        .info-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .info-field label {
            font-size: 11px; font-weight: 700; color: #9ca3af;
            text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 5px;
        }
        .info-field p {
            font-size: 14px; font-weight: 600; color: #1a1a1a;
            background: #f9fafb; padding: 10px 14px; border-radius: 8px;
        }

        /* Form */
        .pform { display: flex; flex-direction: column; gap: 16px; }
        .pform-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .pform-group { display: flex; flex-direction: column; gap: 6px; }
        .pform-group label { font-size: 13px; font-weight: 600; color: #374151; }
        .pform-group input {
            padding: 11px 14px; border: 1.5px solid #e5e7eb; border-radius: 10px;
            font-size: 14px; transition: all 0.2s; outline: none;
        }
        .pform-group input:focus { border-color: #7A1C1C; box-shadow: 0 0 0 3px rgba(122,28,28,0.08); }
        .pform-group input:disabled { background: #f9fafb; color: #9ca3af; cursor: not-allowed; }
        .pform-group small { font-size: 11px; color: #9ca3af; }

        .save-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 11px 26px; background: linear-gradient(135deg, #7A1C1C, #3D0A0A);
            color: #fff; border: none; border-radius: 10px; font-size: 13px;
            font-weight: 700; cursor: pointer; transition: all 0.2s; margin-top: 6px;
            width: fit-content;
        }
        .save-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(122,28,28,0.3); }

        /* Password strength */
        .strength-bar { height: 4px; border-radius: 4px; background: #f3f4f6; margin-top: 6px; overflow: hidden; }
        .strength-fill { height: 100%; border-radius: 4px; transition: all 0.3s; width: 0; }

        /* Alert */
        .alert { padding: 14px 18px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-size: 14px; }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }
        .alert-danger  { background: #fee2e2; color: #7f1d1d; border-left: 4px solid #ef4444; }

        /* Status badge */
        .status-pill {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700;
        }
        .status-pill.active   { background: #d1fae5; color: #065f46; }
        .status-pill.inactive { background: #fee2e2; color: #7f1d1d; }
        .status-pill::before  { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }

        @media (max-width: 768px) {
            .profile-hero { flex-direction: column; text-align: center; padding: 28px 20px; }
            .profile-hero::after { display: none; }
            .score-strip { grid-template-columns: 1fr 1fr; }
            .readiness-card { flex-direction: column; text-align: center; }
            .readiness-tags { justify-content: center; }
            .info-grid-2, .pform-row { grid-template-columns: 1fr; }
            .profile-tabs { width: 100%; overflow-x: auto; }
        }
        @media (max-width: 480px) {
            .score-strip { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="dashboard-layout">
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <?php include 'includes/header.php'; ?>
        <div class="content">

            <?php if ($success): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> Profile updated successfully!</div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Hero -->
            <div class="profile-hero">
                <div class="profile-hero-avatar"><?php echo $initials; ?></div>
                <div class="profile-hero-info">
                    <h1><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h1>
                    <p><?php echo htmlspecialchars($student['student_id']); ?> &nbsp;·&nbsp; <?php echo htmlspecialchars($student['faculty_name'] ?? 'No Faculty'); ?></p>
                    <div class="profile-hero-badges">
                        <span class="hero-badge active-badge"><i class="fas fa-circle" style="font-size:7px"></i> <?php echo ucfirst($student['status']); ?></span>
                        <span class="hero-badge">Year <?php echo $student['year_of_study']; ?></span>
                        <span class="hero-badge"><?php echo ucfirst(str_replace('_', ' ', $student['student_type'])); ?></span>
                    </div>
                </div>
            </div>

            <!-- Stats Strip -->
            <div class="score-strip">
                <div class="score-strip-card">
                    <div class="score-strip-icon" style="background:rgba(122,28,28,0.1);color:#7A1C1C;"><i class="fas fa-calendar-check"></i></div>
                    <div><h3><?php echo $stats['total_bookings']; ?></h3><p>Total Bookings</p></div>
                </div>
                <div class="score-strip-card">
                    <div class="score-strip-icon" style="background:rgba(16,185,129,0.1);color:#10b981;"><i class="fas fa-check-circle"></i></div>
                    <div><h3><?php echo $stats['completed_sessions']; ?></h3><p>Completed</p></div>
                </div>
                <div class="score-strip-card">
                    <div class="score-strip-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b;"><i class="fas fa-calendar-times"></i></div>
                    <div><h3><?php echo $stats['cancelled_sessions']; ?></h3><p>Cancelled</p></div>
                </div>
                <div class="score-strip-card">
                    <div class="score-strip-icon" style="background:rgba(239,68,68,0.1);color:#ef4444;"><i class="fas fa-user-times"></i></div>
                    <div><h3><?php echo $stats['no_show_sessions']; ?></h3><p>No-shows</p></div>
                </div>
            </div>

            <!-- Readiness Score -->
            <div class="readiness-card">
                <div class="readiness-circle">
                    <svg style="transform:rotate(-90deg)" width="100" height="100" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="42" fill="none" stroke="#f3f4f6" stroke-width="9"/>
                        <circle cx="50" cy="50" r="42" fill="none" stroke="<?php echo $score_color; ?>" stroke-width="9"
                            stroke-dasharray="<?php echo 2 * 3.14159 * 42; ?>"
                            stroke-dashoffset="<?php echo (2 * 3.14159 * 42) * (1 - $success_score / 100); ?>"
                            stroke-linecap="round"/>
                    </svg>
                    <div class="readiness-circle-text">
                        <div class="num"><?php echo $success_score; ?></div>
                        <div class="lbl">SCORE</div>
                    </div>
                </div>
                <div class="readiness-info">
                    <h3><i class="fas <?php echo $score_icon; ?>" style="color:<?php echo $score_color; ?>"></i> Readiness Score — <?php echo $score_label; ?></h3>
                    <p>Tracks your engagement with support services. <?php echo $success_score < 60 ? 'Book more sessions to improve your score!' : 'Keep up the great work!'; ?></p>
                    <div class="readiness-tags">
                        <span class="readiness-tag" style="background:rgba(16,185,129,0.1);color:#065f46;"><i class="fas fa-calendar-check"></i> <?php echo $stats['completed_sessions']; ?> attended</span>
                        <span class="readiness-tag" style="background:rgba(122,28,28,0.08);color:#7A1C1C;"><i class="fas fa-calendar"></i> <?php echo $stats['total_bookings']; ?> total</span>
                        <?php if ($stats['cancelled_sessions'] > 0): ?>
                        <span class="readiness-tag" style="background:#fef3c7;color:#b45309;"><i class="fas fa-times"></i> <?php echo $stats['cancelled_sessions']; ?> cancelled</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="profile-tabs">
                <button class="profile-tab active" data-panel="info"><i class="fas fa-user"></i> Personal Info</button>
                <button class="profile-tab" data-panel="academic"><i class="fas fa-graduation-cap"></i> Academic</button>
                <button class="profile-tab" data-panel="password"><i class="fas fa-lock"></i> Password</button>
                <button class="profile-tab" data-panel="account"><i class="fas fa-shield-alt"></i> Account</button>
            </div>

            <!-- Panel: Personal Info -->
            <div class="profile-panel active" id="panel-info">
                <div class="pcard">
                    <p class="pcard-title"><i class="fas fa-user"></i> Personal Information</p>
                    <div class="info-grid-2" style="margin-bottom:20px;">
                        <div class="info-field"><label>First Name</label><p><?php echo htmlspecialchars($student['first_name']); ?></p></div>
                        <div class="info-field"><label>Last Name</label><p><?php echo htmlspecialchars($student['last_name']); ?></p></div>
                        <div class="info-field"><label>Email Address</label><p><?php echo htmlspecialchars($student['email']); ?></p></div>
                        <div class="info-field"><label>Phone Number</label><p><?php echo htmlspecialchars($student['phone'] ?: 'Not set'); ?></p></div>
                    </div>
                    <form action="update-profile.php" method="POST" class="pform">
                        <div class="pform-group">
                            <label>Update Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($student['phone']); ?>" placeholder="e.g. 0821234567" pattern="[0-9]{10}">
                            <small>10-digit South African number</small>
                        </div>
                        <button type="submit" class="save-btn"><i class="fas fa-save"></i> Save Changes</button>
                    </form>
                </div>
            </div>

            <!-- Panel: Academic -->
            <div class="profile-panel" id="panel-academic">
                <div class="pcard">
                    <p class="pcard-title"><i class="fas fa-graduation-cap"></i> Academic Information</p>
                    <div class="info-grid-2">
                        <div class="info-field"><label>Student ID</label><p><?php echo htmlspecialchars($student['student_id']); ?></p></div>
                        <div class="info-field"><label>Faculty</label><p><?php echo htmlspecialchars($student['faculty_name'] ?? 'Not assigned'); ?></p></div>
                        <div class="info-field"><label>Year of Study</label><p>Year <?php echo $student['year_of_study']; ?></p></div>
                        <div class="info-field"><label>Student Type</label><p><?php echo ucfirst(str_replace('_', ' ', $student['student_type'])); ?></p></div>
                    </div>
                </div>
            </div>

            <!-- Panel: Password -->
            <div class="profile-panel" id="panel-password">
                <div class="pcard">
                    <p class="pcard-title"><i class="fas fa-lock"></i> Change Password</p>
                    <form action="change-password.php" method="POST" class="pform">
                        <div class="pform-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required placeholder="Enter current password">
                        </div>
                        <div class="pform-row">
                            <div class="pform-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" id="newPass" required minlength="6" placeholder="Min. 6 characters">
                                <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
                            </div>
                            <div class="pform-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_password" required minlength="6" placeholder="Repeat new password">
                            </div>
                        </div>
                        <button type="submit" class="save-btn"><i class="fas fa-key"></i> Update Password</button>
                    </form>
                </div>
            </div>

            <!-- Panel: Account -->
            <div class="profile-panel" id="panel-account">
                <div class="pcard">
                    <p class="pcard-title"><i class="fas fa-shield-alt"></i> Account Details</p>
                    <div class="info-grid-2">
                        <div class="info-field">
                            <label>Account Status</label>
                            <p><span class="status-pill <?php echo $student['status']; ?>"><?php echo ucfirst($student['status']); ?></span></p>
                        </div>
                        <div class="info-field">
                            <label>Member Since</label>
                            <p><?php echo date('F Y', strtotime($student['created_at'])); ?></p>
                        </div>
                        <div class="info-field">
                            <label>Last Login</label>
                            <p><?php echo $student['last_login'] ? date('d M Y, H:i', strtotime($student['last_login'])) : 'Never'; ?></p>
                        </div>
                        <div class="info-field">
                            <label>Student ID</label>
                            <p><?php echo htmlspecialchars($student['student_id']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="js/dashboard.js"></script>
<script>
    // Tab switching
    document.querySelectorAll('.profile-tab').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.profile-tab').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.profile-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('panel-' + btn.dataset.panel).classList.add('active');
        });
    });

    // Password strength
    document.getElementById('newPass').addEventListener('input', function() {
        var val = this.value, score = 0;
        if (val.length >= 6) score++;
        if (val.length >= 10) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;
        var fill = document.getElementById('strengthFill');
        var colors = ['#ef4444','#f59e0b','#f59e0b','#10b981','#10b981'];
        fill.style.width = (score * 20) + '%';
        fill.style.background = colors[score - 1] || '#f3f4f6';
    });

    // Auto-open password tab if hash is #password
    if (window.location.hash === '#password') {
        document.querySelector('[data-panel="password"]').click();
    }
</script>
</body>
</html>
