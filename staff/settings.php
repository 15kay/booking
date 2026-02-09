<?php
session_start();
if(!isset($_SESSION['staff_id'])) {
    header('Location: ../index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - WSU Booking</title>
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
                        <h1>Settings & Preferences</h1>
                        <p>Customize your account settings and notification preferences</p>
                        <div class="hero-stats">
                            <div class="hero-stat">
                                <i class="fas fa-cog"></i>
                                <span>Account Settings</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-bell"></i>
                                <span>Notifications</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-shield-alt"></i>
                                <span>Security</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Settings Container -->
                <div class="settings-container">
                    <!-- Notification Settings -->
                    <div class="settings-section">
                        <h3><i class="fas fa-bell"></i> Notification Preferences</h3>
                        <p class="section-desc">Manage how you receive notifications about appointments and updates</p>
                        
                        <div class="settings-form">
                            <div class="setting-item">
                                <div class="setting-info">
                                    <h4>Email Notifications</h4>
                                    <p>Receive email notifications for new bookings and updates</p>
                                </div>
                                <label class="toggle">
                                    <input type="checkbox" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <h4>Booking Reminders</h4>
                                    <p>Get reminded about upcoming appointments</p>
                                </div>
                                <label class="toggle">
                                    <input type="checkbox" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <h4>Cancellation Alerts</h4>
                                    <p>Notify when students cancel appointments</p>
                                </div>
                                <label class="toggle">
                                    <input type="checkbox" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <h4>System Updates</h4>
                                    <p>Receive notifications about system updates and maintenance</p>
                                </div>
                                <label class="toggle">
                                    <input type="checkbox">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Booking Settings -->
                    <div class="settings-section">
                        <h3><i class="fas fa-calendar-check"></i> Booking Preferences</h3>
                        <p class="section-desc">Configure your booking and appointment preferences</p>
                        
                        <div class="settings-form">
                            <div class="setting-item">
                                <div class="setting-info">
                                    <h4>Auto-Confirm Bookings</h4>
                                    <p>Automatically confirm new bookings without manual approval</p>
                                </div>
                                <label class="toggle">
                                    <input type="checkbox">
                                    <span class="slider"></span>
                                </label>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <h4>Allow Same-Day Bookings</h4>
                                    <p>Let students book appointments for the same day</p>
                                </div>
                                <label class="toggle">
                                    <input type="checkbox" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                            
                            <div class="setting-item">
                                <div class="setting-info">
                                    <h4>Buffer Time Between Appointments</h4>
                                    <p>Add break time between consecutive appointments</p>
                                </div>
                                <label class="toggle">
                                    <input type="checkbox" checked>
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Account Actions -->
                    <div class="settings-section">
                        <h3><i class="fas fa-user-cog"></i> Account Management</h3>
                        <p class="section-desc">Manage your account settings and security</p>
                        
                        <div class="settings-actions">
                            <a href="edit-profile.php" class="action-link">
                                <i class="fas fa-user-edit"></i>
                                <div>
                                    <h4>Edit Profile</h4>
                                    <p>Update your personal and professional information</p>
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            
                            <a href="change-password.php" class="action-link">
                                <i class="fas fa-key"></i>
                                <div>
                                    <h4>Change Password</h4>
                                    <p>Update your account password for security</p>
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            
                            <a href="schedule.php" class="action-link">
                                <i class="fas fa-clock"></i>
                                <div>
                                    <h4>Manage Schedule</h4>
                                    <p>Configure your availability and working hours</p>
                                </div>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Save Button -->
                    <div class="form-actions">
                        <button class="btn btn-primary" onclick="saveSettings()">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../assets/includes/modals.php'; ?>
    <link rel="stylesheet" href="../assets/css/modals.css">
    <script src="../assets/js/modals.js"></script>
    <script src="js/dashboard.js"></script>
    <script>
        function saveSettings() {
            showMessageModal('success', 'Settings Saved', 'Settings saved successfully!');
            // TODO: Implement actual save functionality
        }
    </script>
</body>
</html>
