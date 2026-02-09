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

$service_id = $_POST['service_id'];
$day_of_week = $_POST['day_of_week'];
$start_time = $_POST['start_time'];
$end_time = $_POST['end_time'];
$slot_duration = $_POST['slot_duration'];
$location = trim($_POST['location']);
$effective_from = $_POST['effective_from'];
$effective_to = !empty($_POST['effective_to']) ? $_POST['effective_to'] : null;

// Validation
if(empty($service_id) || empty($day_of_week) || empty($start_time) || empty($end_time) || empty($location) || empty($effective_from)) {
    header('Location: schedule.php?error=Please fill in all required fields');
    exit();
}

if($start_time >= $end_time) {
    header('Location: schedule.php?error=End time must be after start time');
    exit();
}

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Check for overlapping schedules
    $stmt = $conn->prepare("
        SELECT * FROM staff_schedules 
        WHERE staff_id = ? 
        AND day_of_week = ? 
        AND status = 'active'
        AND (
            (start_time <= ? AND end_time > ?) OR
            (start_time < ? AND end_time >= ?) OR
            (start_time >= ? AND end_time <= ?)
        )
    ");
    $stmt->execute([
        $_SESSION['staff_id'],
        $day_of_week,
        $start_time, $start_time,
        $end_time, $end_time,
        $start_time, $end_time
    ]);
    
    if($stmt->fetch()) {
        header('Location: schedule.php?error=Schedule overlaps with existing schedule');
        exit();
    }
    
    // Insert new schedule
    $stmt = $conn->prepare("
        INSERT INTO staff_schedules 
        (staff_id, service_id, day_of_week, start_time, end_time, slot_duration, location, effective_from, effective_to, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
    ");
    $stmt->execute([
        $_SESSION['staff_id'],
        $service_id,
        $day_of_week,
        $start_time,
        $end_time,
        $slot_duration,
        $location,
        $effective_from,
        $effective_to
    ]);
    
    header('Location: schedule.php?success=Schedule added successfully');
    exit();
    
} catch(PDOException $e) {
    header('Location: schedule.php?error=' . urlencode($e->getMessage()));
    exit();
}
