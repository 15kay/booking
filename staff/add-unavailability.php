<?php
session_start();
if(!isset($_SESSION['staff_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

if($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: schedule.php');
    exit();
}

$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
$start_time = !empty($_POST['start_time']) ? $_POST['start_time'] : null;
$end_time = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
$reason = $_POST['reason'];
$notes = trim($_POST['notes']);

// Validation
if(empty($start_date) || empty($end_date) || empty($reason)) {
    header('Location: schedule.php?error=Please fill in all required fields');
    exit();
}

if($start_date > $end_date) {
    header('Location: schedule.php?error=End date must be after start date');
    exit();
}

if($start_time && $end_time && $start_time >= $end_time) {
    header('Location: schedule.php?error=End time must be after start time');
    exit();
}

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Insert unavailability
    $stmt = $conn->prepare("
        INSERT INTO staff_unavailability 
        (staff_id, start_date, end_date, start_time, end_time, reason, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $_SESSION['staff_id'],
        $start_date,
        $end_date,
        $start_time,
        $end_time,
        $reason,
        $notes
    ]);
    
    // Check for conflicting bookings and notify
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count FROM bookings 
        WHERE staff_id = ? 
        AND booking_date BETWEEN ? AND ?
        AND status IN ('pending', 'confirmed')
    ");
    $stmt->execute([$_SESSION['staff_id'], $start_date, $end_date]);
    $result = $stmt->fetch();
    
    $message = 'Unavailability added successfully';
    if($result['count'] > 0) {
        $message .= '. Warning: You have ' . $result['count'] . ' booking(s) during this period.';
    }
    
    header('Location: schedule.php?success=' . urlencode($message));
    exit();
    
} catch(PDOException $e) {
    header('Location: schedule.php?error=' . urlencode($e->getMessage()));
    exit();
}
