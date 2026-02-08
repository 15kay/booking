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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Settings updated successfully!
                    </div>
                <?php endif; ?>

                <div class="settings-container">
                    <div class="settings-section">
                        <h3><i class="fas fa-bell"></i> Notification Preferences</h3>
                        <p class="section-desc">Choose how you want to receive notifications</p>
                        
                        <form action="update-settings.php" method="POST" class="settings-form">
                            <div class="setting-item">
                                <div class="setting-info">
                                    <h4>Email Notifications</h4>
                                    <p>Receive booking confirmations and reminders via email</p>
                                </div>
                                <label class="toggle">
                                    <input type="checkbox" name="email_notifications" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="setting-item">
                                <div class="setting-info">
                                    <h4>Booking Reminders</h4>
                                    <p>Get reminded 24 hours before your appointment</p>
                                </div>
                                <label class="toggle">
                                    <input type="checkbox" name="booking_reminders" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <div class="setting-item">
                                <div class="setting-info">
                                    <h4>Cancellation Alerts</h4>
                                    <p>Notify me when a booking is cancelled or rescheduled</p>
                                </div>
                                <label class="toggle">
                                    <input type="checkbox" name="cancellation_alerts" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Preferences
                            </button>
                        </form>
                    </div>

                    <div class="settings-section">
                        <h3><i class="fas fa-calendar"></i> Booking Preferences</h3>
                        <p class="section-desc">Manage your booking settings</p>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Maximum Active Bookings</label>
                                <p>5 bookings</p>
                            </div>
                            <div class="info-item">
                                <label>Advance Booking Period</label>
                                <p>30 days</p>
                            </div>
                            <div class="info-item">
                                <label>Cancellation Window</label>
                                <p>24 hours before appointment</p>
                            </div>
                        </div>
                    </div>

                    <div class="settings-section">
                        <h3><i class="fas fa-shield-alt"></i> Privacy & Security</h3>
                        <p class="section-desc">Manage your account security</p>
                        
                        <div class="settings-actions">
                            <a href="profile.php" class="action-link">
                                <i class="fas fa-key"></i>
                                <div>
                                    <h4>Change Password</h4>
                                    <p>Update your account password</p>
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            
                            <a href="profile.php" class="action-link">
                                <i class="fas fa-user-edit"></i>
                                <div>
                                    <h4>Edit Profile</h4>
                                    <p>Update your personal information</p>
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>

                    <div class="settings-section">
                        <h3><i class="fas fa-info-circle"></i> About</h3>
                        <p class="section-desc">System information</p>
                        
                        <div class="info-grid">
                            <div class="info-item">
                                <label>System Version</label>
                                <p>1.0.0</p>
                            </div>
                            <div class="info-item">
                                <label>Support Email</label>
                                <p>bookings@wsu.ac.za</p>
                            </div>
                            <div class="info-item">
                                <label>Help Center</label>
                                <p><a href="#" style="color: var(--blue);">Visit Help Center</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/dashboard.js"></script>
</body>
</html>
