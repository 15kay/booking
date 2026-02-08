<?php
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['student_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

require_once '../config/database.php';

$staff_id = isset($_GET['staff_id']) ? $_GET['staff_id'] : null;
$service_id = isset($_GET['service_id']) ? $_GET['service_id'] : null;
$date = isset($_GET['date']) ? $_GET['date'] : null;

if(!$staff_id || !$service_id || !$date) {
    echo json_encode(['error' => 'Missing parameters']);
    exit();
}

$db = new Database();
$conn = $db->connect();

// Get day of week (1=Monday, 7=Sunday)
$day_of_week = date('N', strtotime($date));

// Get staff schedule for this day and service
$stmt = $conn->prepare("
    SELECT * FROM staff_schedules 
    WHERE staff_id = ? AND service_id = ? AND day_of_week = ? AND status = 'active'
    AND (effective_from <= ? AND (effective_to IS NULL OR effective_to >= ?))
");
$stmt->execute([$staff_id, $service_id, $day_of_week, $date, $date]);
$schedule = $stmt->fetch();

if(!$schedule) {
    echo json_encode(['slots' => []]);
    exit();
}

// Get service duration
$stmt = $conn->prepare("SELECT duration_minutes FROM services WHERE service_id = ?");
$stmt->execute([$service_id]);
$service = $stmt->fetch();
$duration = $service['duration_minutes'];

// Get existing bookings for this staff on this date
$stmt = $conn->prepare("
    SELECT start_time, end_time FROM bookings 
    WHERE staff_id = ? AND booking_date = ? AND status IN ('pending', 'confirmed')
");
$stmt->execute([$staff_id, $date]);
$booked_slots = $stmt->fetchAll();

// Generate available time slots
$slots = [];
$start = strtotime($schedule['start_time']);
$end = strtotime($schedule['end_time']);
$slot_duration = $schedule['slot_duration'] * 60; // Convert to seconds

while($start < $end) {
    $slot_start = date('H:i:00', $start);
    $slot_end = date('H:i:00', $start + ($duration * 60));
    
    // Check if slot is available
    $is_available = true;
    foreach($booked_slots as $booked) {
        $booked_start = strtotime($booked['start_time']);
        $booked_end = strtotime($booked['end_time']);
        
        if(($start >= $booked_start && $start < $booked_end) || 
           ($start + ($duration * 60) > $booked_start && $start + ($duration * 60) <= $booked_end)) {
            $is_available = false;
            break;
        }
    }
    
    if($is_available && ($start + ($duration * 60)) <= $end) {
        $slots[] = [
            'value' => $slot_start,
            'label' => date('h:i A', $start) . ' - ' . date('h:i A', $start + ($duration * 60))
        ];
    }
    
    $start += $slot_duration;
}

echo json_encode(['slots' => $slots]);
