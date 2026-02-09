<?php
session_start();
if(!isset($_SESSION['staff_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

if(!isset($_GET['id']) || !isset($_GET['status'])) {
    header('Location: appointments.php?error=Invalid request');
    exit();
}

$booking_id = $_GET['id'];
$new_status = $_GET['status'];

// Validate status
$valid_statuses = ['confirmed', 'cancelled', 'completed', 'no_show'];
if(!in_array($new_status, $valid_statuses)) {
    header('Location: appointments.php?error=Invalid status');
    exit();
}

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Verify booking belongs to this staff member
    $stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_id = ? AND staff_id = ?");
    $stmt->execute([$booking_id, $_SESSION['staff_id']]);
    $booking = $stmt->fetch();
    
    if(!$booking) {
        header('Location: appointments.php?error=Appointment not found');
        exit();
    }
    
    // Get old status for history
    $old_status = $booking['status'];
    
    // Update booking status
    $stmt = $conn->prepare("UPDATE bookings SET status = ?, updated_at = NOW() WHERE booking_id = ?");
    $stmt->execute([$new_status, $booking_id]);
    
    // Add to booking history
    $stmt = $conn->prepare("
        INSERT INTO booking_history (booking_id, action, old_status, new_status, changed_by, notes, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $booking_id,
        $new_status,
        $old_status,
        $new_status,
        'staff_' . $_SESSION['staff_id'],
        'Status updated by staff member'
    ]);
    
    // Create notification for student
    $notification_types = [
        'confirmed' => 'booking_confirmed',
        'cancelled' => 'booking_cancelled',
        'completed' => 'booking_confirmed',
        'no_show' => 'booking_cancelled'
    ];
    
    $notification_messages = [
        'confirmed' => 'Your appointment has been confirmed',
        'cancelled' => 'Your appointment has been cancelled',
        'completed' => 'Your appointment has been marked as completed',
        'no_show' => 'You were marked as no-show for your appointment'
    ];
    
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, user_type, booking_id, notification_type, title, message, created_at)
        VALUES (?, 'student', ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $booking['student_id'],
        $booking_id,
        $notification_types[$new_status],
        ucfirst($new_status) . ' - ' . $booking['booking_reference'],
        $notification_messages[$new_status]
    ]);
    
    $success_messages = [
        'confirmed' => 'Appointment confirmed successfully',
        'cancelled' => 'Appointment cancelled successfully',
        'completed' => 'Appointment marked as completed',
        'no_show' => 'Appointment marked as no-show'
    ];
    
    header('Location: appointments.php?success=' . urlencode($success_messages[$new_status]));
    exit();
    
} catch(PDOException $e) {
    header('Location: appointments.php?error=' . urlencode($e->getMessage()));
    exit();
}
