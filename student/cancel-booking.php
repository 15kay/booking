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

// Verify booking belongs to student and can be cancelled
$stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_id = ? AND student_id = ? AND status IN ('pending', 'confirmed')");
$stmt->execute([$booking_id, $student_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    header('Location: my-bookings.php');
    exit();
}

// Cancel booking
$update = $conn->prepare("UPDATE bookings SET status = 'cancelled', cancelled_by = 'student', cancelled_at = NOW() WHERE booking_id = ?");
$update->execute([$booking_id]);

// Create notification for staff
$notif = $conn->prepare("INSERT INTO notifications (user_id, user_type, booking_id, notification_type, title, message) VALUES (?, 'staff', ?, 'booking_cancelled', 'Booking Cancelled', ?)");
$notif->execute([$booking['staff_id'], $booking_id, 'Student has cancelled booking ' . $booking['booking_reference']]);

header('Location: my-bookings.php?cancelled=1');
exit();
