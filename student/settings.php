<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: ../index.php');
    exit();
}

$success = $_GET['success'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - WSU Booking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <style>
        .settings-hero {
            background: linear-gradient(135deg, #3D0A0A 0%, #7A1C1C 60%, #E8A020 100%);
            border-radius: 16px;
            padding: 36px 40px;
            color: #fff;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .settings-hero h1 { font-size: 26px; margin-bottom: 6px; }
        .settings-hero p  { opacity: 0.85; font-size: 14px; }
        .settings-hero-icon { font-size: 72px; opacity: 0.15; }

        .settings-layout {
            display: grid;
            grid-template-columns: 220px 1fr;
            gap: 24px;
            align-items: start;
        }

        /* Sidebar nav */
        .settings-nav {
            background: #fff;
            border-radius: 14px;
            padding: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            position: sticky;
            top: 90px;
        }
        .settings-nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
        }
        .settings-nav-item i { width: 18px; text-align: center; font-size: 14px; }
        .settings-nav-item:hover { background: #f3f4f6; color: #3D0A0A; }
        .settings-nav-item.active { background: rgba(122,28,28,0.08); color: #7A1C1C; }
        .settings-nav-item.active i { color: #E8A020; }

        /* Panels */
        .settings-panel { display: none; flex-direction: column; gap: 20px; }
        .settings-panel.active { display: flex; }

        .settings-card {
            background: #fff;
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        .settings-card-title {
            font-size: 15px;
            font-weight: 700;
            color: #3D0A0A;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .settings-card-title i { color: #E8A020; }
        .settings-card-desc {
            font-size: 13px;
            color: #9ca3af;
            margin-bottom: 22px;
        }

        /* Toggle rows */
        .toggle-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .toggle-row:last-of-type { border-bottom: none; }
        .toggle-row-info h4 { font-size: 14px; font-weight: 600; color: #1a1a1a; margin-bottom: 3px; }
        .toggle-row-info p  { font-size: 12px; color: #9ca3af; }

        .toggle { position: relative; display: inline-block; width: 46px; height: 24px; flex-shrink: 0; }
        .toggle input { opacity: 0; width: 0; height: 0; }
        .slider {
            position: absolute; cursor: pointer; inset: 0;
            background: #d1d5db; border-radius: 24px; transition: 0.3s;
        }
        .slider:before {
            content: ""; position: absolute;
            width: 18px; height: 18px; left: 3px; bottom: 3px;
            background: #fff; border-radius: 50%; transition: 0.3s;
        }
        .toggle input:checked + .slider { background: #7A1C1C; }
        .toggle input:checked + .slider:before { transform: translateX(22px); }

        /* Action links */
        .action-link {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: #f9fafb;
            border-radius: 10px;
            text-decoration: none;
            color: #1a1a1a;
            border: 1px solid #f3f4f6;
            transition: all 0.2s;
            margin-bottom: 10px;
        }
        .action-link:last-child { margin-bottom: 0; }
        .action-link:hover { border-color: #7A1C1C; background: #fff; }
        .action-link-icon {
            width: 42px; height: 42px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;
        }
        .action-link-body { flex: 1; }
        .action-link-body h4 { font-size: 14px; font-weight: 600; margin-bottom: 2px; }
        .action-link-body p  { font-size: 12px; color: #9ca3af; }
        .action-link > .fa-chevron-right { color: #d1d5db; font-size: 12px; }

        /* Info grid */
        .info-row-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .info-box { background: #f9fafb; border-radius: 10px; padding: 16px; }
        .info-box label { font-size: 11px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 6px; }
        .info-box p { font-size: 14px; font-weight: 600; color: #1a1a1a; }

        /* Appearance */
        .theme-options { display: flex; gap: 12px; flex-wrap: wrap; }
        .theme-option {
            flex: 1; min-width: 100px;
            border: 2px solid #f3f4f6; border-radius: 12px; padding: 16px 12px;
            text-align: center; cursor: pointer; transition: all 0.2s;
        }
        .theme-option:hover { border-color: #7A1C1C; }
        .theme-option.selected { border-color: #7A1C1C; background: rgba(122,28,28,0.04); }
        .theme-option .theme-preview {
            width: 40px; height: 40px; border-radius: 10px; margin: 0 auto 8px;
            display: flex; align-items: center; justify-content: center; font-size: 18px;
        }
        .theme-option p { font-size: 12px; font-weight: 600; color: #374151; }

        /* Danger zone */
        .danger-zone { border: 1.5px solid #fee2e2; border-radius: 14px; padding: 24px; }
        .danger-zone .settings-card-title { color: #ef4444; }
        .danger-zone .settings-card-title i { color: #ef4444; }
        .btn-danger-outline {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 20px; border: 1.5px solid #ef4444; border-radius: 8px;
            color: #ef4444; background: none; font-size: 13px; font-weight: 600;
            cursor: pointer; transition: all 0.2s; margin-top: 14px;
        }
        .btn-danger-outline:hover { background: #ef4444; color: #fff; }

        .save-btn {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 11px 24px; background: linear-gradient(135deg, #7A1C1C, #3D0A0A);
            color: #fff; border: none; border-radius: 10px; font-size: 13px;
            font-weight: 700; cursor: pointer; transition: all 0.2s; margin-top: 20px;
        }
        .save-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(122,28,28,0.3); }

        .alert {
            padding: 14px 18px; border-radius: 10px; margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px; font-size: 14px;
        }
        .alert-success { background: #d1fae5; color: #065f46; border-left: 4px solid #10b981; }

        @media (max-width: 768px) {
            .settings-layout { grid-template-columns: 1fr; }
            .settings-nav { position: static; display: flex; flex-wrap: wrap; gap: 6px; padding: 10px; }
            .settings-nav-item { flex: 1; min-width: 120px; justify-content: center; }
            .settings-hero { flex-direction: column; gap: 10px; }
            .settings-hero-icon { display: none; }
            .info-row-grid { grid-template-columns: 1fr; }
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
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> Settings saved successfully!</div>
            <?php endif; ?>

            <!-- Hero -->
            <div class="settings-hero">
                <div>
                    <h1><i class="fas fa-cog"></i> Settings</h1>
                    <p>Manage your preferences, notifications, and account security.</p>
                </div>
                <i class="fas fa-sliders-h settings-hero-icon"></i>
            </div>

            <div class="settings-layout">

                <!-- Side Nav -->
                <nav class="settings-nav">
                    <button class="settings-nav-item active" data-panel="notifications">
                        <i class="fas fa-bell"></i> Notifications
                    </button>
                    <button class="settings-nav-item" data-panel="appearance">
                        <i class="fas fa-paint-brush"></i> Appearance
                    </button>
                    <button class="settings-nav-item" data-panel="booking">
                        <i class="fas fa-calendar-check"></i> Booking
                    </button>
                    <button class="settings-nav-item" data-panel="security">
                        <i class="fas fa-shield-alt"></i> Security
                    </button>
                    <button class="settings-nav-item" data-panel="about">
                        <i class="fas fa-info-circle"></i> About
                    </button>
                </nav>

                <!-- Panels -->
                <div class="settings-panels">

                    <!-- Notifications -->
                    <div class="settings-panel active" id="panel-notifications">
                        <div class="settings-card">
                            <p class="settings-card-title"><i class="fas fa-bell"></i> Notification Preferences</p>
                            <p class="settings-card-desc">Choose how and when you want to be notified.</p>
                            <form action="update-settings.php" method="POST">
                                <div class="toggle-row">
                                    <div class="toggle-row-info">
                                        <h4>Email Notifications</h4>
                                        <p>Booking confirmations and updates via email</p>
                                    </div>
                                    <label class="toggle"><input type="checkbox" name="email_notifications" checked><span class="slider"></span></label>
                                </div>
                                <div class="toggle-row">
                                    <div class="toggle-row-info">
                                        <h4>Booking Reminders</h4>
                                        <p>Reminder 24 hours before your appointment</p>
                                    </div>
                                    <label class="toggle"><input type="checkbox" name="booking_reminders" checked><span class="slider"></span></label>
                                </div>
                                <div class="toggle-row">
                                    <div class="toggle-row-info">
                                        <h4>Cancellation Alerts</h4>
                                        <p>Notify me when a booking is cancelled or rescheduled</p>
                                    </div>
                                    <label class="toggle"><input type="checkbox" name="cancellation_alerts" checked><span class="slider"></span></label>
                                </div>
                                <div class="toggle-row">
                                    <div class="toggle-row-info">
                                        <h4>System Announcements</h4>
                                        <p>Important updates from WSU Booking</p>
                                    </div>
                                    <label class="toggle"><input type="checkbox" name="system_announcements"><span class="slider"></span></label>
                                </div>
                                <button type="submit" class="save-btn"><i class="fas fa-save"></i> Save Preferences</button>
                            </form>
                        </div>
                    </div>

                    <!-- Appearance -->
                    <div class="settings-panel" id="panel-appearance">
                        <div class="settings-card">
                            <p class="settings-card-title"><i class="fas fa-paint-brush"></i> Theme</p>
                            <p class="settings-card-desc">Choose your preferred display theme.</p>
                            <div class="theme-options">
                                <div class="theme-option selected" onclick="selectTheme(this, 'light')">
                                    <div class="theme-preview" style="background:#f3f4f6;"><i class="fas fa-sun" style="color:#E8A020"></i></div>
                                    <p>Light</p>
                                </div>
                                <div class="theme-option" onclick="selectTheme(this, 'system')">
                                    <div class="theme-preview" style="background:#e5e7eb;"><i class="fas fa-laptop" style="color:#7A1C1C"></i></div>
                                    <p>System</p>
                                </div>
                            </div>
                        </div>
                        <div class="settings-card">
                            <p class="settings-card-title"><i class="fas fa-text-height"></i> Font Size</p>
                            <p class="settings-card-desc">Adjust the text size across the portal.</p>
                            <div class="toggle-row">
                                <div class="toggle-row-info"><h4>Compact Mode</h4><p>Reduce spacing for more content on screen</p></div>
                                <label class="toggle"><input type="checkbox" name="compact_mode"><span class="slider"></span></label>
                            </div>
                        </div>
                    </div>

                    <!-- Booking -->
                    <div class="settings-panel" id="panel-booking">
                        <div class="settings-card">
                            <p class="settings-card-title"><i class="fas fa-calendar-check"></i> Booking Limits</p>
                            <p class="settings-card-desc">Your current booking policy settings.</p>
                            <div class="info-row-grid">
                                <div class="info-box">
                                    <label>Max Active Bookings</label>
                                    <p>5 bookings</p>
                                </div>
                                <div class="info-box">
                                    <label>Advance Booking Period</label>
                                    <p>30 days</p>
                                </div>
                                <div class="info-box">
                                    <label>Cancellation Window</label>
                                    <p>24 hours before</p>
                                </div>
                                <div class="info-box">
                                    <label>No-show Policy</label>
                                    <p>3 strikes limit</p>
                                </div>
                            </div>
                        </div>
                        <div class="settings-card">
                            <p class="settings-card-title"><i class="fas fa-sliders-h"></i> Preferences</p>
                            <p class="settings-card-desc">Personalise your booking experience.</p>
                            <div class="toggle-row">
                                <div class="toggle-row-info"><h4>Auto-confirm Bookings</h4><p>Skip confirmation step when booking</p></div>
                                <label class="toggle"><input type="checkbox" name="auto_confirm"><span class="slider"></span></label>
                            </div>
                            <div class="toggle-row">
                                <div class="toggle-row-info"><h4>Calendar Sync</h4><p>Add bookings to your device calendar</p></div>
                                <label class="toggle"><input type="checkbox" name="calendar_sync" checked><span class="slider"></span></label>
                            </div>
                        </div>
                    </div>

                    <!-- Security -->
                    <div class="settings-panel" id="panel-security">
                        <div class="settings-card">
                            <p class="settings-card-title"><i class="fas fa-shield-alt"></i> Account Security</p>
                            <p class="settings-card-desc">Manage your password and account access.</p>
                            <a href="profile.php#password" class="action-link">
                                <div class="action-link-icon" style="background:rgba(122,28,28,0.1);color:#7A1C1C;"><i class="fas fa-key"></i></div>
                                <div class="action-link-body"><h4>Change Password</h4><p>Update your account password</p></div>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            <a href="profile.php" class="action-link">
                                <div class="action-link-icon" style="background:rgba(232,160,32,0.1);color:#b87a00;"><i class="fas fa-user-edit"></i></div>
                                <div class="action-link-body"><h4>Edit Profile</h4><p>Update your personal information</p></div>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                        <div class="settings-card">
                            <p class="settings-card-title"><i class="fas fa-lock"></i> Privacy</p>
                            <p class="settings-card-desc">Control what others can see about you.</p>
                            <div class="toggle-row">
                                <div class="toggle-row-info"><h4>Profile Visibility</h4><p>Allow staff to view your profile details</p></div>
                                <label class="toggle"><input type="checkbox" name="profile_visible" checked><span class="slider"></span></label>
                            </div>
                            <div class="toggle-row">
                                <div class="toggle-row-info"><h4>Activity Status</h4><p>Show when you were last active</p></div>
                                <label class="toggle"><input type="checkbox" name="activity_status"><span class="slider"></span></label>
                            </div>
                        </div>
                        <div class="danger-zone">
                            <p class="settings-card-title"><i class="fas fa-exclamation-triangle"></i> Danger Zone</p>
                            <p class="settings-card-desc">Irreversible actions — proceed with caution.</p>
                            <button class="btn-danger-outline" onclick="return confirm('Are you sure you want to deactivate your account?')">
                                <i class="fas fa-user-slash"></i> Deactivate Account
                            </button>
                        </div>
                    </div>

                    <!-- About -->
                    <div class="settings-panel" id="panel-about">
                        <div class="settings-card">
                            <p class="settings-card-title"><i class="fas fa-info-circle"></i> System Information</p>
                            <p class="settings-card-desc">Details about the WSU Booking platform.</p>
                            <div class="info-row-grid">
                                <div class="info-box"><label>System Version</label><p>1.0.0</p></div>
                                <div class="info-box"><label>Support Email</label><p>bookings@wsu.ac.za</p></div>
                                <div class="info-box"><label>University</label><p>Walter Sisulu University</p></div>
                                <div class="info-box"><label>Help Center</label><p><a href="#" style="color:#7A1C1C;font-weight:600;">Visit Help Center</a></p></div>
                            </div>
                        </div>
                        <div class="settings-card">
                            <p class="settings-card-title"><i class="fas fa-headset"></i> Support</p>
                            <p class="settings-card-desc">Get help with your account or bookings.</p>
                            <a href="mailto:bookings@wsu.ac.za" class="action-link">
                                <div class="action-link-icon" style="background:rgba(37,99,235,0.1);color:#2563eb;"><i class="fas fa-envelope"></i></div>
                                <div class="action-link-body"><h4>Email Support</h4><p>bookings@wsu.ac.za</p></div>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            <a href="tel:0437082000" class="action-link">
                                <div class="action-link-icon" style="background:rgba(16,185,129,0.1);color:#10b981;"><i class="fas fa-phone"></i></div>
                                <div class="action-link-body"><h4>Call Us</h4><p>043 708 2000</p></div>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<script src="js/dashboard.js"></script>
<script>
    document.querySelectorAll('.settings-nav-item').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.settings-nav-item').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.settings-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById('panel-' + btn.dataset.panel).classList.add('active');
        });
    });

    function selectTheme(el, theme) {
        document.querySelectorAll('.theme-option').forEach(o => o.classList.remove('selected'));
        el.classList.add('selected');
    }
</script>
</body>
</html>
