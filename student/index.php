<?php
session_start();
if(!isset($_SESSION['student_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get student stats
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM bookings WHERE student_id = ?");
$stmt->execute([$_SESSION['student_id']]);
$total_bookings = $stmt->fetch()['total'];

$stmt = $conn->prepare("SELECT COUNT(*) as pending FROM bookings WHERE student_id = ? AND status = 'pending'");
$stmt->execute([$_SESSION['student_id']]);
$pending_bookings = $stmt->fetch()['pending'];

$stmt = $conn->prepare("SELECT COUNT(*) as confirmed FROM bookings WHERE student_id = ? AND status = 'confirmed'");
$stmt->execute([$_SESSION['student_id']]);
$confirmed_bookings = $stmt->fetch()['confirmed'];

$stmt = $conn->prepare("SELECT COUNT(*) as completed FROM bookings WHERE student_id = ? AND status = 'completed'");
$stmt->execute([$_SESSION['student_id']]);
$completed_bookings = $stmt->fetch()['completed'];

// Get upcoming bookings
$stmt = $conn->prepare("
    SELECT b.*, s.service_name, st.first_name, st.last_name 
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN staff st ON b.staff_id = st.staff_id
    WHERE b.student_id = ? AND b.booking_date >= CURDATE() AND b.status IN ('pending', 'confirmed')
    ORDER BY b.booking_date, b.start_time
    LIMIT 5
");
$stmt->execute([$_SESSION['student_id']]);
$upcoming_bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <!-- Welcome Section -->
                <div class="welcome-banner">
                    <div class="welcome-content">
                        <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>! 👋</h1>
                        <p>Manage your appointments and explore available services</p>
                    </div>
                    <a href="book-service.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Book New Service
                    </a>
                </div>

                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-calendar"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $total_bookings; ?></h3>
                            <p>Total Bookings</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $pending_bookings; ?></h3>
                            <p>Pending</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $confirmed_bookings; ?></h3>
                            <p>Confirmed</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon red">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3><?php echo $completed_bookings; ?></h3>
                            <p>Completed</p>
                        </div>
                    </div>
                </div>
                
                <!-- Upcoming Bookings -->
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title"><i class="fas fa-calendar-alt"></i> Upcoming Appointments</h2>
                        <a href="my-bookings.php" class="btn-link">View All <i class="fas fa-arrow-right"></i></a>
                    </div>
                    
                    <?php if(count($upcoming_bookings) > 0): ?>
                        <div class="bookings-list">
                            <?php foreach($upcoming_bookings as $booking): ?>
                            <div class="booking-card">
                                <div class="booking-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="booking-details">
                                    <h4><?php echo htmlspecialchars($booking['service_name']); ?></h4>
                                    <p><i class="fas fa-user"></i> <?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></p>
                                    <p><i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($booking['booking_date'])); ?> at <?php echo date('H:i', strtotime($booking['start_time'])); ?></p>
                                </div>
                                <div class="booking-status">
                                    <span class="badge badge-<?php echo $booking['status']; ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No Upcoming Appointments</h3>
                            <p>You don't have any scheduled appointments</p>
                            <a href="book-service.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Book Your First Service
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3>Quick Actions</h3>
                    <div class="actions-grid">
                        <a href="book-service.php" class="action-card">
                            <i class="fas fa-calendar-plus"></i>
                            <span>Book Service</span>
                        </a>
                        <a href="my-bookings.php" class="action-card">
                            <i class="fas fa-list"></i>
                            <span>My Bookings</span>
                        </a>
                        <a href="history.php" class="action-card">
                            <i class="fas fa-history"></i>
                            <span>History</span>
                        </a>
                        <a href="profile.php" class="action-card">
                            <i class="fas fa-user-edit"></i>
                            <span>Edit Profile</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Success Modal -->
    <?php if(isset($_GET['success'])): ?>
    <div class="modal-overlay" id="successModal">
        <div class="modal">
            <div class="modal-icon">
                <i class="fas fa-check"></i>
            </div>
            <h2>Booking Successful!</h2>
            <p><?php echo htmlspecialchars($_GET['success']); ?></p>
            <?php if(preg_match('/Reference: (BK\d+)/', $_GET['success'], $matches)): ?>
            <div class="booking-ref"><?php echo $matches[1]; ?></div>
            <?php endif; ?>
            <div class="modal-actions">
                <button class="modal-btn modal-btn-secondary" onclick="window.location.href='my-bookings.php'">
                    <i class="fas fa-list"></i> View Bookings
                </button>
                <button class="modal-btn modal-btn-primary" onclick="closeModal()">
                    <i class="fas fa-home"></i> Go to Dashboard
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.querySelector('.sidebar');
        const headerLogo = document.getElementById('headerLogo');
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userDropdown = document.getElementById('userDropdown');
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationsDropdown = document.getElementById('notificationsDropdown');
        
        // Sidebar toggle
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('closed');
            headerLogo.style.display = sidebar.classList.contains('closed') ? 'flex' : 'none';
        });
        
        // User menu toggle
        userMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
            notificationsDropdown.classList.remove('active');
        });
        
        // Notifications toggle
        notificationBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationsDropdown.classList.toggle('active');
            userDropdown.classList.remove('active');
        });
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function() {
            userDropdown.classList.remove('active');
            notificationsDropdown.classList.remove('active');
        });
        
        // Success modal
        function closeModal() {
            const modal = document.getElementById('successModal');
            if(modal) {
                modal.style.animation = 'fadeOut 0.3s';
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 300);
            }
        }
        
        // Auto close modal after 10 seconds
        const successModal = document.getElementById('successModal');
        if(successModal) {
            setTimeout(closeModal, 10000);
        }
    </script>
</body>
</html>
