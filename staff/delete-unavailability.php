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

$unavailability_id = $_GET['id'];

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Verify unavailability belongs to this staff member
    $stmt = $conn->prepare("SELECT * FROM staff_unavailability WHERE unavailability_id = ? AND staff_id = ?");
    $stmt->execute([$unavailability_id, $_SESSION['staff_id']]);
    $unavailability = $stmt->fetch();
    
    if(!$unavailability) {
        header('Location: schedule.php?error=Unavailability not found');
        exit();
    }
    
    // Delete unavailability
    $stmt = $conn->prepare("DELETE FROM staff_unavailability WHERE unavailability_id = ?");
    $stmt->execute([$unavailability_id]);
    
    header('Location: schedule.php?success=Unavailability deleted successfully');
    exit();
    
} catch(PDOException $e) {
    header('Location: schedule.php?error=' . urlencode($e->getMessage()));
    exit();
}
