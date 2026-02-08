<?php
session_start();

if (!isset($_SESSION['student_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: settings.php');
    exit();
}

// In a real application, you would save these preferences to the database
// For now, we'll just redirect with success message

header('Location: settings.php?success=1');
exit();
