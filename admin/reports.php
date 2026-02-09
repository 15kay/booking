<?php
session_start();
if(!isset($_SESSION['admin_id'])) {
    header('Location: ../admin-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get report statistics
$stmt = $conn->query("SELECT COUNT(*) as total FROM bookings");
$total_bookings = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM students WHERE status = 'active'");
$total_students = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM staff WHERE status = 'active'");
$total_staff = $stmt->fetch()['total'];

$stmt = $conn->query("SELECT COUNT(*) as total FROM services WHERE status = 'active'");
$total_services = $stmt->fetch()['total'];

// Booking statistics by status
$stmt = $conn->query("
    SELECT status, COUNT(*) as count
    FROM bookings
    GROUP BY status
");
$booking_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Top services
$stmt = $conn->query("
    SELECT s.service_name, COUNT(b.booking_id) as booking_count
    FROM services s
    LEFT JOIN bookings b ON s.service_id = b.service_id
    GROUP BY s.service_id
    ORDER BY booking_count DESC
    LIMIT 5
");
$top_services = $stmt->fetchAll();

// Top staff by bookings
$stmt = $conn->query("
    SELECT CONCAT(staff.first_name, ' ', staff.last_name) as staff_name, 
           staff.staff_number, COUNT(b.booking_id) as booking_count
    FROM staff
    LEFT JOIN bookings b ON staff.staff_id = b.staff_id
    GROUP BY staff.staff_id
    ORDER BY booking_count DESC
    LIMIT 5
");
$top_staff = $stmt->fetchAll();

// Monthly booking trends (last 6 months)
$stmt = $conn->query("
    SELECT DATE_FORMAT(booking_date, '%Y-%m') as month, COUNT(*) as count
    FROM bookings
    WHERE booking_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY month
    ORDER BY month
");
$monthly_trends = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - WSU Booking</title>
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
                        <h1>Reports & Analytics</h1>
                        <p>Generate comprehensive reports and view system analytics</p>
                        <div class="hero-stats">
                            <div class="hero-stat">
                                <i class="fas fa-calendar-check"></i>
                                <span><?php echo $total_bookings; ?> Total Bookings</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-user-graduate"></i>
                                <span><?php echo $total_students; ?> Active Students</span>
                            </div>
                            <div class="hero-stat">
                                <i class="fas fa-user-tie"></i>
                                <span><?php echo $total_staff; ?> Active Staff</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_bookings; ?></h3>
                            <p>Total Bookings</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_students; ?></h3>
                            <p>Active Students</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_staff; ?></h3>
                            <p>Active Staff</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="fas fa-concierge-bell"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_services; ?></h3>
                            <p>Active Services</p>
                        </div>
                    </div>
                </div>

                <!-- Report Generation -->
                <div class="section">
                    <h3><i class="fas fa-file-download"></i> Generate Reports</h3>
                    <div class="reports-grid">
                        <div class="report-card">
                            <div class="report-icon blue">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <h4>Bookings Report</h4>
                            <p>Generate detailed booking reports by date range, status, or service</p>
                            <button class="btn btn-primary" onclick="showMessageModal('info', 'Coming Soon', 'Bookings report generation coming soon')">
                                <i class="fas fa-download"></i> Generate
                            </button>
                        </div>

                        <div class="report-card">
                            <div class="report-icon green">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <h4>Student Activity Report</h4>
                            <p>View student engagement and service utilization statistics</p>
                            <button class="btn btn-primary" onclick="showMessageModal('info', 'Coming Soon', 'Student report generation coming soon')">
                                <i class="fas fa-download"></i> Generate
                            </button>
                        </div>

                        <div class="report-card">
                            <div class="report-icon orange">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <h4>Staff Performance Report</h4>
                            <p>Analyze staff workload, availability, and appointment completion rates</p>
                            <button class="btn btn-primary" onclick="showMessageModal('info', 'Coming Soon', 'Staff report generation coming soon')">
                                <i class="fas fa-download"></i> Generate
                            </button>
                        </div>

                        <div class="report-card">
                            <div class="report-icon red">
                                <i class="fas fa-concierge-bell"></i>
                            </div>
                            <h4>Service Utilization Report</h4>
                            <p>Track which services are most popular and identify trends</p>
                            <button class="btn btn-primary" onclick="showMessageModal('info', 'Coming Soon', 'Service report generation coming soon')">
                                <i class="fas fa-download"></i> Generate
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Booking Statistics -->
                <div class="section">
                    <h3><i class="fas fa-chart-pie"></i> Booking Statistics</h3>
                    <div class="stats-grid">
                        <?php
                        $status_config = [
                            'pending' => ['icon' => 'clock', 'color' => 'orange', 'label' => 'Pending'],
                            'confirmed' => ['icon' => 'check-circle', 'color' => 'green', 'label' => 'Confirmed'],
                            'completed' => ['icon' => 'calendar-check', 'color' => 'blue', 'label' => 'Completed'],
                            'cancelled' => ['icon' => 'times-circle', 'color' => 'red', 'label' => 'Cancelled']
                        ];
                        
                        foreach($status_config as $status => $config):
                            $count = isset($booking_stats[$status]) ? $booking_stats[$status] : 0;
                        ?>
                        <div class="stat-card">
                            <div class="stat-icon <?php echo $config['color']; ?>">
                                <i class="fas fa-<?php echo $config['icon']; ?>"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $count; ?></h3>
                                <p><?php echo $config['label']; ?> Bookings</p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Top Services -->
                <div class="section">
                    <h3><i class="fas fa-star"></i> Top Services</h3>
                    <div class="students-table-container">
                        <table class="students-table">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">Rank</th>
                                    <th>Service Name</th>
                                    <th style="width: 150px;">Total Bookings</th>
                                    <th style="width: 250px;">Popularity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rank = 1;
                                foreach($top_services as $service): 
                                    $percentage = $total_bookings > 0 ? round(($service['booking_count'] / $total_bookings) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; justify-content: center; width: 35px; height: 35px; background: linear-gradient(135deg, var(--blue) 0%, #2563eb 100%); color: white; border-radius: 50%; font-weight: 700;">
                                            #<?php echo $rank++; ?>
                                        </div>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($service['service_name']); ?></strong></td>
                                    <td><strong style="font-size: 16px; color: var(--blue);"><?php echo $service['booking_count']; ?></strong></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div class="progress-bar" style="flex: 1;">
                                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                            <span class="progress-text" style="min-width: 45px;"><?php echo $percentage; ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Top Staff -->
                <div class="section">
                    <h3><i class="fas fa-trophy"></i> Top Performing Staff</h3>
                    <div class="students-table-container">
                        <table class="students-table">
                            <thead>
                                <tr>
                                    <th style="width: 80px;">Rank</th>
                                    <th>Staff Member</th>
                                    <th style="width: 150px;">Total Appointments</th>
                                    <th style="width: 250px;">Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rank = 1;
                                $max_bookings = count($top_staff) > 0 ? $top_staff[0]['booking_count'] : 1;
                                foreach($top_staff as $staff): 
                                    $percentage = $max_bookings > 0 ? round(($staff['booking_count'] / $max_bookings) * 100, 1) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; justify-content: center; width: 35px; height: 35px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border-radius: 50%; font-weight: 700;">
                                            #<?php echo $rank++; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="student-info">
                                            <div class="student-avatar" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                                                <?php 
                                                $names = explode(' ', $staff['staff_name']);
                                                echo strtoupper(substr($names[0], 0, 1) . substr($names[count($names)-1], 0, 1)); 
                                                ?>
                                            </div>
                                            <div class="student-details">
                                                <h4><?php echo htmlspecialchars($staff['staff_name']); ?></h4>
                                                <p><?php echo htmlspecialchars($staff['staff_number']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td><strong style="font-size: 16px; color: var(--success);"><?php echo $staff['booking_count']; ?></strong></td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            <div class="progress-bar" style="flex: 1;">
                                                <div class="progress-fill" style="width: <?php echo $percentage; ?>%; background: linear-gradient(90deg, var(--success) 0%, #059669 100%);"></div>
                                            </div>
                                            <span class="progress-text" style="min-width: 45px;"><?php echo $percentage; ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Monthly Trends -->
                <div class="section">
                    <h3><i class="fas fa-chart-line"></i> Monthly Booking Trends (Last 6 Months)</h3>
                    <div class="students-table-container">
                        <table class="students-table">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th style="width: 200px;">Total Bookings</th>
                                    <th style="width: 200px;">Trend</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $prev_count = 0;
                                foreach($monthly_trends as $trend): 
                                    $change = $prev_count > 0 ? (($trend['count'] - $prev_count) / $prev_count) * 100 : 0;
                                    $prev_count = $trend['count'];
                                ?>
                                <tr>
                                    <td><strong><?php echo date('F Y', strtotime($trend['month'] . '-01')); ?></strong></td>
                                    <td><strong style="font-size: 18px; color: var(--blue);"><?php echo $trend['count']; ?></strong></td>
                                    <td>
                                        <?php if($change > 0): ?>
                                            <span class="badge badge-confirmed" style="font-size: 13px; padding: 8px 16px;">
                                                <i class="fas fa-arrow-up"></i> +<?php echo round($change, 1); ?>%
                                            </span>
                                        <?php elseif($change < 0): ?>
                                            <span class="badge badge-cancelled" style="font-size: 13px; padding: 8px 16px;">
                                                <i class="fas fa-arrow-down"></i> <?php echo round($change, 1); ?>%
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-pending" style="font-size: 13px; padding: 8px 16px;">
                                                <i class="fas fa-minus"></i> No change
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
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
