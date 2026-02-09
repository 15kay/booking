<?php
session_start();
if(!isset($_SESSION['staff_id'])) {
    header('Location: ../index.php');
    exit();
}

require_once '../config/database.php';

if(!isset($_GET['id'])) {
    header('Location: schedule.php?error=Invalid request');
    exit();
}

$schedule_id = $_GET['id'];

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Verify schedule belongs to this staff member
    $stmt = $conn->prepare("SELECT * FROM staff_schedules WHERE schedule_id = ? AND staff_id = ?");
    $stmt->execute([$schedule_id, $_SESSION['staff_id']]);
    $schedule = $stmt->fetch();
    
    if(!$schedule) {
        header('Location: schedule.php?error=Schedule not found');
        exit();
    }
    
    // Check if there are any future bookings using this schedule
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count FROM bookings 
        WHERE staff_id = ? 
        AND service_id = ? 
        AND booking_date >= CURDATE() 
        AND status IN ('pending', 'confirmed')
    ");
    $stmt->execute([$_SESSION['staff_id'], $schedule['service_id']]);
    $result = $stmt->fetch();
    
    if($result['count'] > 0) {
        header('Location: schedule.php?error=Cannot delete schedule with active bookings. Please cancel bookings first.');
        exit();
    }
    
    // Soft delete by setting status to inactive
    $stmt = $conn->prepare("UPDATE staff_schedules SET status = 'inactive' WHERE schedule_id = ?");
    $stmt->execute([$schedule_id]);
    
    header('Location: schedule.php?success=Schedule deleted successfully');
    exit();
    
} catch(PDOException $e) {
    header('Location: schedule.php?error=' . urlencode($e->getMessage()));
    exit();
}
