<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['student_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

$student_id = $_SESSION['student_id'];
$phone = trim($_POST['phone']);

$stmt = $conn->prepare("UPDATE students SET phone = ? WHERE student_id = ?");
if ($stmt->execute([$phone, $student_id])) {
    header('Location: profile.php?success=1');
} else {
    header('Location: profile.php?error=Failed to update profile');
}
exit();
