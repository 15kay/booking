<?php
session_start();
if(!isset($_SESSION['student_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build query based on filter
$where_clause = "b.student_id = ?";
$params = [$_SESSION['student_id']];

switch($filter) {
    case 'upcoming':
        $where_clause .= " AND b.booking_date >= CURDATE() AND b.status IN ('pending', 'confirmed')";
        break;
    case 'pending':
        $where_clause .= " AND b.status = 'pending'";
        break;
    case 'confirmed':
        $where_clause .= " AND b.status = 'confirmed'";
        break;
    case 'completed':
        $where_clause .= " AND b.status = 'completed'";
        break;
    case 'cancelled':
        $where_clause .= " AND b.status = 'cancelled'";
        break;
}

// Get bookings
$stmt = $conn->prepare("
    SELECT b.*, s.service_name, sc.category_name, st.first_name, st.last_name, st.specialization
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN service_categories sc ON s.category_id = sc.category_id
    JOIN staff st ON b.staff_id = st.staff_id
    WHERE {$where_clause}
    ORDER BY b.booking_date DESC, b.start_time DESC
");
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - WSU Booking</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <!-- Filter Tabs -->
                <div class="filter-tabs">
                    <a href="?filter=all" class="filter-tab <?php echo $filter == 'all' ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i> All Bookings
                    </a>
                    <a href="?filter=upcoming" class="filter-tab <?php echo $filter == 'upcoming' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-day"></i> Upcoming
                    </a>
                    <a href="?filter=pending" class="filter-tab <?php echo $filter == 'pending' ? 'active' : ''; ?>">
                        <i class="fas fa-clock"></i> Pending
                    </a>
                    <a href="?filter=confirmed" class="filter-tab <?php echo $filter == 'confirmed' ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle"></i> Confirmed
                    </a>
                    <a href="?filter=completed" class="filter-tab <?php echo $filter == 'completed' ? 'active' : ''; ?>">
                        <i class="fas fa-calendar-check"></i> Completed
                    </a>
                    <a href="?filter=cancelled" class="filter-tab <?php echo $filter == 'cancelled' ? 'active' : ''; ?>">
                        <i class="fas fa-times-circle"></i> Cancelled
                    </a>
                </div>

                <!-- Bookings List -->
                <div class="section">
                    <?php if(count($bookings) > 0): ?>
                        <div class="bookings-grid">
                            <?php foreach($bookings as $booking): ?>
                            <div class="booking-item">
                                <div class="booking-item-header">
                                    <div>
                                        <h3><?php echo htmlspecialchars($booking['service_name']); ?></h3>
                                        <span class="category-badge"><?php echo htmlspecialchars($booking['category_name']); ?></span>
                                    </div>
                                    <span class="badge badge-<?php echo $booking['status']; ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </div>
                                
                                <div class="booking-item-body">
                                    <div class="booking-info-row">
                                        <i class="fas fa-calendar"></i>
                                        <span><?php echo date('l, d F Y', strtotime($booking['booking_date'])); ?></span>
                                    </div>
                                    <div class="booking-info-row">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo date('h:i A', strtotime($booking['start_time'])); ?> - <?php echo date('h:i A', strtotime($booking['end_time'])); ?></span>
                                    </div>
                                    <div class="booking-info-row">
                                        <i class="fas fa-user-md"></i>
                                        <span><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?>
                                        <?php if($booking['specialization']): ?>
                                            <small>(<?php echo htmlspecialchars($booking['specialization']); ?>)</small>
                                        <?php endif; ?>
                                        </span>
                                    </div>
                                    <div class="booking-info-row">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($booking['location'] ?: 'TBA'); ?></span>
                                    </div>
                                    <div class="booking-info-row">
                                        <i class="fas fa-hashtag"></i>
                                        <span><?php echo htmlspecialchars($booking['booking_reference']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="booking-item-footer">
                                    <?php if($booking['status'] == 'pending' || $booking['status'] == 'confirmed'): ?>
                                        <?php if(strtotime($booking['booking_date']) > time()): ?>
                                        <button class="btn-action btn-danger" onclick="cancelBooking(<?php echo $booking['booking_id']; ?>)">
                                            <i class="fas fa-times"></i> Cancel
                                        </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <button class="btn-action btn-secondary" onclick="viewDetails(<?php echo $booking['booking_id']; ?>)">
                                        <i class="fas fa-eye"></i> Details
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No Bookings Found</h3>
                            <p>You don't have any bookings in this category</p>
                            <a href="book-service.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Book a Service
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.querySelector('.sidebar');
        const headerLogo = document.getElementById('headerLogo');
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userDropdown = document.getElementById('userDropdown');
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationsDropdown = document.getElementById('notificationsDropdown');
        
        menuToggle.addEventListener('click', function() {
            sidebar.classList.toggle('closed');
            headerLogo.style.display = sidebar.classList.contains('closed') ? 'flex' : 'none';
        });
        
        userMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('active');
            notificationsDropdown.classList.remove('active');
        });
        
        notificationBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            notificationsDropdown.classList.toggle('active');
            userDropdown.classList.remove('active');
        });
        
        document.addEventListener('click', function() {
            userDropdown.classList.remove('active');
            notificationsDropdown.classList.remove('active');
        });
        
        function cancelBooking(bookingId) {
            if(confirm('Are you sure you want to cancel this booking?')) {
                window.location.href = 'cancel-booking.php?id=' + bookingId;
            }
        }
        
        function viewDetails(bookingId) {
            window.location.href = 'booking-details.php?id=' + bookingId;
        }
    </script>
</body>
</html>
