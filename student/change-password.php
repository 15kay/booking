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
$current_password = $_POST['current_password'];
$new_password = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];

// Validate new password match
if ($new_password !== $confirm_password) {
    header('Location: profile.php?error=New passwords do not match');
    exit();
}

// Get current password hash
$stmt = $conn->prepare("SELECT password_hash FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

// Verify current password
if (!password_verify($current_password, $student['password_hash'])) {
    header('Location: profile.php?error=Current password is incorrect');
    exit();
}

// Update password
$new_hash = password_hash($new_password, PASSWORD_BCRYPT);
$update = $conn->prepare("UPDATE students SET password_hash = ? WHERE student_id = ?");
if ($update->execute([$new_hash, $student_id])) {
    header('Location: profile.php?success=1');
} else {
    header('Location: profile.php?error=Failed to update password');
}
exit();
