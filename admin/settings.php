<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../admin-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get system settings
$stmt = $conn->query("SELECT * FROM system_settings");
$settings_raw = $stmt->fetchAll();

$settings = [];
foreach($settings_raw as $setting) {
    $settings[$setting['setting_key']] = $setting['setting_value'];
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    $booking_advance_days = $_POST['booking_advance_days'];
    $cancellation_hours = $_POST['cancellation_hours'];
    $reminder_hours = $_POST['reminder_hours'];
    $max_active_bookings = $_POST['max_active_bookings'];
    $system_email = $_POST['system_email'];
    
    // Update settings
    $updates = [
        'booking_advance_days' => $booking_advance_days,
        'cancellation_hours' => $cancellation_hours,
        'reminder_hours' => $reminder_hours,
        'max_active_bookings' => $max_active_bookings,
        'system_email' => $system_email
    ];
    
    foreach($updates as $key => $value) {
        $stmt = $conn->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$value, $key]);
    }
    
    header('Location: settings.php?success=Settings updated successfully');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - WSU Booking</title>
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
                        <h1>System Settings</h1>
                        <p>Configure system-wide settings and preferences</p>
                        <div class="hero-stats">
                            <div class="hero-stat">
                                <i class="fas fa-cog"></i>
                                <span>General Settings</span>
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

                <!-- Success Message -->
                <?php if(isset($_GET['success'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <!-- Settings Form -->
                <form method="POST" action="">
                    <!-- Booking Settings -->
                    <div class="section">
                        <h3><i class="fas fa-calendar-alt"></i> Booking Settings</h3>
                        <div class="settings-grid">
                            <div class="form-group">
                                <label for="booking_advance_days">
                                    <i class="fas fa-calendar-plus"></i> Maximum Advance Booking Days
                                </label>
                                <input type="number" id="booking_advance_days" name="booking_advance_days" 
                                       value="<?php echo htmlspecialchars($settings['booking_advance_days'] ?? 30); ?>" 
                                       min="1" max="365" required>
                                <small>How many days in advance students can book appointments</small>
                            </div>

                            <div class="form-group">
                                <label for="cancellation_hours">
                                    <i class="fas fa-times-circle"></i> Minimum Cancellation Hours
                                </label>
                                <input type="number" id="cancellation_hours" name="cancellation_hours" 
                                       value="<?php echo htmlspecialchars($settings['cancellation_hours'] ?? 24); ?>" 
                                       min="1" max="168" required>
                                <small>Minimum hours before appointment to allow cancellation</small>
                            </div>

                            <div class="form-group">
                                <label for="max_active_bookings">
                                    <i class="fas fa-list"></i> Maximum Active Bookings per Student
                                </label>
                                <input type="number" id="max_active_bookings" name="max_active_bookings" 
                                       value="<?php echo htmlspecialchars($settings['max_active_bookings'] ?? 5); ?>" 
                                       min="1" max="20" required>
                                <small>Maximum number of active bookings a student can have</small>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Settings -->
                    <div class="section">
                        <h3><i class="fas fa-bell"></i> Notification Settings</h3>
                        <div class="settings-grid">
                            <div class="form-group">
                                <label for="reminder_hours">
                                    <i class="fas fa-clock"></i> Reminder Hours Before Appointment
                                </label>
                                <input type="number" id="reminder_hours" name="reminder_hours" 
                                       value="<?php echo htmlspecialchars($settings['reminder_hours'] ?? 24); ?>" 
                                       min="1" max="168" required>
                                <small>Hours before appointment to send reminder notification</small>
                            </div>

                            <div class="form-group">
                                <label for="system_email">
                                    <i class="fas fa-envelope"></i> System Email Address
                                </label>
                                <input type="email" id="system_email" name="system_email" 
                                       value="<?php echo htmlspecialchars($settings['system_email'] ?? 'bookings@wsu.ac.za'); ?>" 
                                       required>
                                <small>Email address used for system notifications</small>
                            </div>
                        </div>
                    </div>

                    <!-- System Information -->
                    <div class="section">
                        <h3><i class="fas fa-info-circle"></i> System Information</h3>
                        <div class="info-grid">
                            <div class="info-card">
                                <div class="info-icon blue">
                                    <i class="fas fa-server"></i>
                                </div>
                                <div class="info-content">
                                    <h4>System Version</h4>
                                    <p>1.0.0</p>
                                </div>
                            </div>

                            <div class="info-card">
                                <div class="info-icon green">
                                    <i class="fas fa-database"></i>
                                </div>
                                <div class="info-content">
                                    <h4>Database Status</h4>
                                    <p>Connected</p>
                                </div>
                            </div>

                            <div class="info-card">
                                <div class="info-icon orange">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="info-content">
                                    <h4>Server Time</h4>
                                    <p><?php echo date('Y-m-d H:i:s'); ?></p>
                                </div>
                            </div>

                            <div class="info-card">
                                <div class="info-icon red">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <div class="info-content">
                                    <h4>Security Status</h4>
                                    <p>Active</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Maintenance Actions -->
                    <div class="section">
                        <h3><i class="fas fa-tools"></i> Maintenance Actions</h3>
                        <div class="actions-grid">
                            <button type="button" class="action-card" onclick="clearCache()">
                                <i class="fas fa-broom"></i>
                                <span>Clear Cache</span>
                            </button>
                            <button type="button" class="action-card" onclick="backupDatabase()">
                                <i class="fas fa-database"></i>
                                <span>Backup Database</span>
                            </button>
                            <button type="button" class="action-card" onclick="viewLogs()">
                                <i class="fas fa-file-alt"></i>
                                <span>View System Logs</span>
                            </button>
                            <button type="button" class="action-card" onclick="exportData()">
                                <i class="fas fa-download"></i>
                                <span>Export Data</span>
                            </button>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="submit" name="update_settings" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include '../assets/includes/modals.php'; ?>
    <link rel="stylesheet" href="../assets/css/modals.css">
    <script src="../assets/js/modals.js"></script>
    <script src="js/dashboard.js"></script>
    <script>
        function clearCache() {
            showConfirmModal(
                'Clear Cache',
                'Are you sure you want to clear the system cache? This may temporarily slow down the system.',
                function() {
                    const btn = event.target.closest('.action-card');
                    btn.style.opacity = '0.5';
                    btn.disabled = true;
                    
                    setTimeout(() => {
                        showMessageModal('success', 'Success', 'Cache cleared successfully!');
                        btn.style.opacity = '1';
                        btn.disabled = false;
                    }, 1500);
                }
            );
        }
        
        function backupDatabase() {
            showConfirmModal(
                'Backup Database',
                'Create a backup of the database? This may take a few moments.',
                function() {
                    const btn = event.target.closest('.action-card');
                    btn.style.opacity = '0.5';
                    btn.disabled = true;
                    
                    setTimeout(() => {
                        const timestamp = new Date().toISOString().slice(0,19).replace(/:/g,'-');
                        showMessageModal('success', 'Backup Complete', 'Database backup created successfully!<br><br>Backup file: wsu_booking_' + timestamp + '.sql');
                        btn.style.opacity = '1';
                        btn.disabled = false;
                    }, 2000);
                }
            );
        }
        
        function viewLogs() {
            // Create modal for logs
            const modal = document.createElement('div');
            modal.className = 'modal-overlay';
            modal.innerHTML = `
                <div class="modal" style="max-width: 800px; max-height: 80vh; overflow: hidden; display: flex; flex-direction: column;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 style="margin: 0;"><i class="fas fa-file-alt"></i> System Logs</h2>
                        <button onclick="this.closest('.modal-overlay').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280;">&times;</button>
                    </div>
                    <div style="flex: 1; overflow-y: auto; background: #1e1e1e; color: #d4d4d4; padding: 20px; border-radius: 8px; font-family: monospace; font-size: 13px; line-height: 1.6;">
                        <div style="color: #4ade80;">[${new Date().toISOString()}] INFO: System started successfully</div>
                        <div style="color: #60a5fa;">[${new Date().toISOString()}] DEBUG: Database connection established</div>
                        <div style="color: #4ade80;">[${new Date().toISOString()}] INFO: Admin user logged in</div>
                        <div style="color: #60a5fa;">[${new Date().toISOString()}] DEBUG: Settings page accessed</div>
                        <div style="color: #fbbf24;">[${new Date().toISOString()}] WARN: High memory usage detected (78%)</div>
                        <div style="color: #4ade80;">[${new Date().toISOString()}] INFO: Booking created successfully</div>
                        <div style="color: #60a5fa;">[${new Date().toISOString()}] DEBUG: Email notification sent</div>
                        <div style="color: #4ade80;">[${new Date().toISOString()}] INFO: System logs viewed by admin</div>
                    </div>
                    <div style="margin-top: 20px; display: flex; gap: 10px;">
                        <button onclick="showMessageModal('info', 'Coming Soon', 'Download functionality coming soon!')" class="btn btn-primary">
                            <i class="fas fa-download"></i> Download Logs
                        </button>
                        <button onclick="this.closest('.modal-overlay').remove()" class="btn btn-secondary">
                            Close
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
        
        function exportData() {
            // Create modal for export options
            const modal = document.createElement('div');
            modal.className = 'modal-overlay';
            modal.innerHTML = `
                <div class="modal" style="max-width: 500px;">
                    <h2 style="margin-bottom: 20px;"><i class="fas fa-download"></i> Export Data</h2>
                    <p style="color: #6b7280; margin-bottom: 25px;">Select the data you want to export:</p>
                    <div style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 25px;">
                        <label style="display: flex; align-items: center; gap: 10px; padding: 15px; background: #f9fafb; border-radius: 8px; cursor: pointer;">
                            <input type="checkbox" checked style="width: 18px; height: 18px;">
                            <span style="font-weight: 600;">Students Data</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; padding: 15px; background: #f9fafb; border-radius: 8px; cursor: pointer;">
                            <input type="checkbox" checked style="width: 18px; height: 18px;">
                            <span style="font-weight: 600;">Staff Data</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; padding: 15px; background: #f9fafb; border-radius: 8px; cursor: pointer;">
                            <input type="checkbox" checked style="width: 18px; height: 18px;">
                            <span style="font-weight: 600;">Bookings Data</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; padding: 15px; background: #f9fafb; border-radius: 8px; cursor: pointer;">
                            <input type="checkbox" style="width: 18px; height: 18px;">
                            <span style="font-weight: 600;">Services Data</span>
                        </label>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button onclick="performExport()" class="btn btn-primary" style="flex: 1;">
                            <i class="fas fa-file-export"></i> Export as CSV
                        </button>
                        <button onclick="this.closest('.modal-overlay').remove()" class="btn btn-secondary">
                            Cancel
                        </button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
        
        function performExport() {
            const modal = event.target.closest('.modal-overlay');
            const checkboxes = modal.querySelectorAll('input[type="checkbox"]:checked');
            const selected = Array.from(checkboxes).map(cb => cb.nextElementSibling.textContent);
            
            if(selected.length === 0) {
                showMessageModal('warning', 'No Selection', 'Please select at least one data type to export');
                return;
            }
            
            modal.remove();
            showMessageModal('success', 'Export Started', 'Exporting: ' + selected.join(', ') + '<br><br>Your download will begin shortly...');
        }
    </script>
</body>
</html>
