<?php
session_start();
if(!isset($_SESSION['staff_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: schedule.php?error=Invalid request method');
    exit();
}

// Validate required fields
$required_fields = ['schedule_id', 'service_id', 'day_of_week', 'start_time', 'end_time', 'location', 'slot_duration', 'effective_from'];
foreach($required_fields as $field) {
    if(!isset($_POST[$field]) || empty($_POST[$field])) {
        header('Location: schedule.php?error=All required fields must be filled');
        exit();
    }
}

$schedule_id = $_POST['schedule_id'];
$service_id = $_POST['service_id'];
$day_of_week = $_POST['day_of_week'];
$start_time = $_POST['start_time'];
$end_time = $_POST['end_time'];
$location = trim($_POST['location']);
$slot_duration = $_POST['slot_duration'];
$effective_from = $_POST['effective_from'];
$effective_to = !empty($_POST['effective_to']) ? $_POST['effective_to'] : null;

// Validate day of week
if($day_of_week < 1 || $day_of_week > 5) {
    header('Location: schedule.php?error=Invalid day of week (Monday-Friday only)');
    exit();
}

// Validate time
if($start_time >= $end_time) {
    header('Location: schedule.php?error=End time must be after start time');
    exit();
}

// Validate slot duration
if($slot_duration < 15 || $slot_duration > 240) {
    header('Location: schedule.php?error=Slot duration must be between 15 and 240 minutes');
    exit();
}

// Validate dates
if($effective_to && $effective_to < $effective_from) {
    header('Location: schedule.php?error=Effective to date must be after effective from date');
    exit();
}

$db = new Database();
$conn = $db->connect();

try {
    // Verify schedule belongs to this staff member
    $stmt = $conn->prepare("SELECT staff_id FROM staff_schedules WHERE schedule_id = ?");
    $stmt->execute([$schedule_id]);
    $schedule = $stmt->fetch();
    
    if(!$schedule || $schedule['staff_id'] != $_SESSION['staff_id']) {
        header('Location: schedule.php?error=Schedule not found or unauthorized');
        exit();
    }
    
    // Check for conflicts with other schedules
    $conflict_query = "
        SELECT schedule_id FROM staff_schedules 
        WHERE staff_id = ? 
        AND day_of_week = ? 
        AND schedule_id != ?
        AND status = 'active'
        AND (
            (start_time < ? AND end_time > ?) OR
            (start_time < ? AND end_time > ?) OR
            (start_time >= ? AND end_time <= ?)
        )
    ";
    
    $stmt = $conn->prepare($conflict_query);
    $stmt->execute([
        $_SESSION['staff_id'],
        $day_of_week,
        $schedule_id,
        $end_time, $start_time,
        $end_time, $end_time,
        $start_time, $end_time
    ]);
    
    if($stmt->fetch()) {
        header('Location: schedule.php?error=Schedule conflicts with existing schedule on this day');
        exit();
    }
    
    // Update schedule
    $update_query = "
        UPDATE staff_schedules 
        SET service_id = ?,
            day_of_week = ?,
            start_time = ?,
            end_time = ?,
            location = ?,
            slot_duration = ?,
            effective_from = ?,
            effective_to = ?,
            updated_at = NOW()
        WHERE schedule_id = ? AND staff_id = ?
    ";
    
    $stmt = $conn->prepare($update_query);
    $stmt->execute([
        $service_id,
        $day_of_week,
        $start_time,
        $end_time,
        $location,
        $slot_duration,
        $effective_from,
        $effective_to,
        $schedule_id,
        $_SESSION['staff_id']
    ]);
    
    header('Location: schedule.php?success=Schedule updated successfully');
    exit();
    
} catch(PDOException $e) {
    error_log("Edit schedule error: " . $e->getMessage());
    header('Location: schedule.php?error=Database error occurred');
    exit();
}
