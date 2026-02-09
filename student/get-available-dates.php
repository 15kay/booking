<?php
session_start();
header('Content-Type: application/json');

try {
    if(!isset($_SESSION['student_id'])) {
        echo json_encode(['error' => 'Unauthorized']);
        exit();
    }

    require_once '../config/database.php';

    $staff_id = isset($_GET['staff_id']) ? intval($_GET['staff_id']) : null;
    $service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : null;
    $month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

    if(!$staff_id || !$service_id) {
        echo json_encode(['error' => 'Missing parameters', 'available_dates' => []]);
        exit();
    }

    $db = new Database();
    $conn = $db->connect();

    // Get service details
    $stmt = $conn->prepare("SELECT max_advance_booking_days, duration_minutes FROM services WHERE service_id = ?");
    $stmt->execute([$service_id]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);

    if(!$service) {
        echo json_encode(['error' => 'Service not found', 'available_dates' => []]);
        exit();
    }

    // Get staff schedules
    $stmt = $conn->prepare("
        SELECT day_of_week, start_time, end_time, slot_duration
        FROM staff_schedules 
        WHERE staff_id = ? AND service_id = ? AND status = 'active'
    ");
    $stmt->execute([$staff_id, $service_id]);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if(empty($schedules)) {
        echo json_encode(['available_dates' => [], 'message' => 'No schedules found']);
        exit();
    }

    // Create array of available days of week
    $schedule_by_day = [];
    foreach($schedules as $schedule) {
        $schedule_by_day[$schedule['day_of_week']] = $schedule;
    }

    // Get staff unavailability periods
    $stmt = $conn->prepare("
        SELECT start_date, end_date 
        FROM staff_unavailability 
        WHERE staff_id = ? AND end_date >= CURDATE()
    ");
    $stmt->execute([$staff_id]);
    $unavailable_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $unavailable_dates = [];
    
    // Expand date ranges into individual dates
    foreach($unavailable_result as $row) {
        $start = strtotime($row['start_date']);
        $end = strtotime($row['end_date']);
        for($date = $start; $date <= $end; $date = strtotime('+1 day', $date)) {
            $unavailable_dates[] = date('Y-m-d', $date);
        }
    }

    // Generate available dates for the month
    $available_dates = [];
    $start_date = max(strtotime($month . '-01'), strtotime('today'));
    $end_date = min(
        strtotime(date('Y-m-t', strtotime($month . '-01'))),
        strtotime('+' . $service['max_advance_booking_days'] . ' days')
    );

    for($date = $start_date; $date <= $end_date; $date = strtotime('+1 day', $date)) {
        $date_str = date('Y-m-d', $date);
        $day_of_week = date('N', $date); // 1=Monday, 7=Sunday
        
        // Check if this day of week has schedule and is not unavailable
        if(isset($schedule_by_day[$day_of_week]) && !in_array($date_str, $unavailable_dates)) {
            $schedule = $schedule_by_day[$day_of_week];
            
            // Calculate max possible slots for this day
            $start_time = strtotime($schedule['start_time']);
            $end_time = strtotime($schedule['end_time']);
            $slot_duration = $schedule['slot_duration'] * 60; // Convert to seconds
            $service_duration = $service['duration_minutes'] * 60;
            
            $max_slots = 0;
            $current_time = $start_time;
            while($current_time + $service_duration <= $end_time) {
                $max_slots++;
                $current_time += $slot_duration;
            }
            
            // Check how many slots are already booked
            $stmt = $conn->prepare("
                SELECT COUNT(*) as booked_count
                FROM bookings 
                WHERE staff_id = ? AND booking_date = ? AND status IN ('pending', 'confirmed')
            ");
            $stmt->execute([$staff_id, $date_str]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If there are available slots, add to available dates
            if($result['booked_count'] < $max_slots) {
                $available_dates[] = $date_str;
            }
        }
    }

    echo json_encode([
        'available_dates' => $available_dates,
        'month' => $month,
        'success' => true
    ]);

} catch(Exception $e) {
    echo json_encode([
        'error' => 'Server error: ' . $e->getMessage(),
        'available_dates' => []
    ]);
}
