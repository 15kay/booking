<?php
session_start();
if(!isset($_SESSION['student_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $service_id = $_POST['service_id'];
    $staff_id = $_POST['staff_id'];
    $booking_date = $_POST['booking_date'];
    $time_slot = $_POST['time_slot'];
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    if(empty($service_id) || empty($staff_id) || empty($booking_date) || empty($time_slot)) {
        header('Location: book-service.php?error=Please fill in all required fields');
        exit();
    }
    
    try {
        $db = new Database();
        $conn = $db->connect();
        
        // Get service details
        $stmt = $conn->prepare("SELECT duration_minutes FROM services WHERE service_id = ?");
        $stmt->execute([$service_id]);
        $service = $stmt->fetch();
        
        if(!$service) {
            header('Location: book-service.php?error=Invalid service');
            exit();
        }
        
        // Calculate end time
        $start_time = $time_slot;
        $end_time = date('H:i:s', strtotime($start_time) + ($service['duration_minutes'] * 60));
        
        // Generate booking reference
        $booking_reference = 'BK' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Check if slot is still available
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count FROM bookings 
            WHERE staff_id = ? AND booking_date = ? 
            AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))
            AND status IN ('pending', 'confirmed')
        ");
        $stmt->execute([$staff_id, $booking_date, $start_time, $start_time, $end_time, $end_time]);
        $result = $stmt->fetch();
        
        if($result['count'] > 0) {
            header('Location: book-service.php?error=This time slot is no longer available');
            exit();
        }
        
        // Insert booking
        $stmt = $conn->prepare("
            INSERT INTO bookings (booking_reference, student_id, service_id, staff_id, booking_date, start_time, end_time, notes, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $booking_reference,
            $_SESSION['student_id'],
            $service_id,
            $staff_id,
            $booking_date,
            $start_time,
            $end_time,
            $notes
        ]);
        
        $booking_id = $conn->lastInsertId();
        
        // Create notification
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, user_type, booking_id, notification_type, title, message)
            VALUES (?, 'student', ?, 'booking_confirmed', 'Booking Created', 'Your booking has been created successfully. Reference: {$booking_reference}')
        ");
        $stmt->execute([$_SESSION['student_id'], $booking_id]);
        
        header('Location: index.php?success=' . urlencode('Booking created successfully! Reference: ' . $booking_reference));
        exit();
        
    } catch(PDOException $e) {
        header('Location: book-service.php?error=' . urlencode('Error creating booking: ' . $e->getMessage()));
        exit();
    }
} else {
    header('Location: book-service.php');
    exit();
}
