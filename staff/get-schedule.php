<?php
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['staff_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once '../config/database.php';

if(!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Schedule ID required']);
    exit();
}

$schedule_id = $_GET['id'];

$db = new Database();
$conn = $db->connect();

// Get schedule details
$stmt = $conn->prepare("
    SELECT ss.*, s.service_name, s.service_code
    FROM staff_schedules ss
    JOIN services s ON ss.service_id = s.service_id
    WHERE ss.schedule_id = ? AND ss.staff_id = ?
");
$stmt->execute([$schedule_id, $_SESSION['staff_id']]);
$schedule = $stmt->fetch();

if(!$schedule) {
    echo json_encode(['success' => false, 'message' => 'Schedule not found']);
    exit();
}

echo json_encode([
    'success' => true,
    'schedule' => $schedule
]);
