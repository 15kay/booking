<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['student_id'])) {
    header('Location: ../index.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

$booking_id = $_GET['id'] ?? null;
$student_id = $_SESSION['student_id'];

if (!$booking_id) {
    header('Location: my-bookings.php');
    exit();
}

// Get booking details
$stmt = $conn->prepare("
    SELECT b.*, s.service_name, s.description as service_desc, sc.category_name,
           st.first_name as staff_first, st.last_name as staff_last, st.email as staff_email, st.phone as staff_phone
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN service_categories sc ON s.category_id = sc.category_id
    JOIN staff st ON b.staff_id = st.staff_id
    WHERE b.booking_id = ? AND b.student_id = ?
");
$stmt->execute([$booking_id, $student_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header('Location: my-bookings.php');
    exit();
}

$can_cancel = in_array($booking['status'], ['pending', 'confirmed']) && strtotime($booking['booking_date'] . ' ' . $booking['start_time']) > time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Details - WSU Booking</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="dashboard-layout">
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <div class="booking-detail-container">
                    <div class="detail-header">
                        <a href="my-bookings.php" class="back-link">
                            <i class="fas fa-arrow-left"></i> Back to Bookings
                        </a>
                        <h2>Booking Details</h2>
                    </div>

                    <div class="detail-grid">
                        <div class="detail-main">
                            <div class="detail-card">
                                <div class="detail-card-header">
                                    <div>
                                        <h3><?php echo htmlspecialchars($booking['service_name']); ?></h3>
                                        <span class="badge badge-<?php echo $booking['status']; ?>">
                                            <?php echo ucfirst($booking['status']); ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="detail-info">
                                    <div class="info-row">
                                        <i class="fas fa-bookmark"></i>
                                        <div>
                                            <label>Booking Reference</label>
                                            <p><?php echo htmlspecialchars($booking['booking_reference']); ?></p>
                                        </div>
                                    </div>

                                    <div class="info-row">
                                        <i class="fas fa-tag"></i>
                                        <div>
                                            <label>Category</label>
                                            <p><?php echo htmlspecialchars($booking['category_name']); ?></p>
                                        </div>
                                    </div>

                                    <div class="info-row">
                                        <i class="fas fa-calendar"></i>
                                        <div>
                                            <label>Date</label>
                                            <p><?php echo date('l, F j, Y', strtotime($booking['booking_date'])); ?></p>
                                        </div>
                                    </div>

                                    <div class="info-row">
                                        <i class="fas fa-clock"></i>
                                        <div>
                                            <label>Time</label>
                                            <p><?php echo date('g:i A', strtotime($booking['start_time'])) . ' - ' . date('g:i A', strtotime($booking['end_time'])); ?></p>
                                        </div>
                                    </div>

                                    <div class="info-row">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <div>
                                            <label>Location</label>
                                            <p><?php echo htmlspecialchars($booking['location']); ?></p>
                                        </div>
                                    </div>

                                    <?php if ($booking['notes']): ?>
                                    <div class="info-row">
                                        <i class="fas fa-sticky-note"></i>
                                        <div>
                                            <label>Notes</label>
                                            <p><?php echo htmlspecialchars($booking['notes']); ?></p>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($booking['service_desc']): ?>
                            <div class="detail-card">
                                <h4><i class="fas fa-info-circle"></i> Service Description</h4>
                                <p class="service-description"><?php echo htmlspecialchars($booking['service_desc']); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="detail-sidebar">
                            <div class="detail-card">
                                <h4><i class="fas fa-user-tie"></i> Staff Information</h4>
                                <div class="staff-info">
                                    <div class="staff-avatar">
                                        <i class="fas fa-user-circle"></i>
                                    </div>
                                    <h5><?php echo htmlspecialchars($booking['staff_first'] . ' ' . $booking['staff_last']); ?></h5>
                                    <div class="staff-contact">
                                        <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($booking['staff_email']); ?></p>
                                        <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($booking['staff_phone']); ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="detail-card">
                                <h4><i class="fas fa-history"></i> Booking Timeline</h4>
                                <div class="timeline">
                                    <div class="timeline-item">
                                        <div class="timeline-dot"></div>
                                        <div>
                                            <p class="timeline-label">Created</p>
                                            <p class="timeline-date"><?php echo date('M j, Y g:i A', strtotime($booking['created_at'])); ?></p>
                                        </div>
                                    </div>
                                    <?php if ($booking['updated_at'] != $booking['created_at']): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-dot"></div>
                                        <div>
                                            <p class="timeline-label">Last Updated</p>
                                            <p class="timeline-date"><?php echo date('M j, Y g:i A', strtotime($booking['updated_at'])); ?></p>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if ($can_cancel): ?>
                            <div class="detail-actions">
                                <button onclick="confirmCancel()" class="btn btn-danger">
                                    <i class="fas fa-times-circle"></i> Cancel Booking
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/dashboard.js"></script>
    <script>
        function confirmCancel() {
            if (confirm('Are you sure you want to cancel this booking?')) {
                window.location.href = 'cancel-booking.php?id=<?php echo $booking_id; ?>';
            }
        }
    </script>
</body>
</html>
