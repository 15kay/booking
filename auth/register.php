<?php
session_start();
require_once '../config/database.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.php');
    exit();
}

$db = new Database();
$conn = $db->connect();

$student_id = trim($_POST['student_id']);
$email = trim($_POST['email']);
$first_name = trim($_POST['first_name']);
$last_name = trim($_POST['last_name']);
$phone = trim($_POST['phone']);
$faculty_id = $_POST['faculty_id'];
$year_of_study = $_POST['year_of_study'];
$student_type = $_POST['student_type'];
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

// Validate passwords match
if($password !== $confirm_password) {
    header('Location: ../register.php?error=Passwords do not match');
    exit();
}

// Check if student ID already exists
$check = $conn->prepare("SELECT student_id FROM students WHERE student_id = ?");
$check->execute([$student_id]);
if($check->fetch()) {
    header('Location: ../register.php?error=Student ID already registered');
    exit();
}

// Check if email already exists
$check = $conn->prepare("SELECT email FROM students WHERE email = ?");
$check->execute([$email]);
if($check->fetch()) {
    header('Location: ../register.php?error=Email already registered');
    exit();
}

// Hash password
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// Insert student
$stmt = $conn->prepare("INSERT INTO students (student_id, first_name, last_name, email, password_hash, phone, faculty_id, year_of_study, student_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

if($stmt->execute([$student_id, $first_name, $last_name, $email, $password_hash, $phone, $faculty_id, $year_of_study, $student_type])) {
    // Auto login
    $_SESSION['student_id'] = $student_id;
    $_SESSION['student_name'] = $first_name . ' ' . $last_name;
    
    header('Location: ../student/index.php?registered=1');
} else {
    header('Location: ../register.php?error=Registration failed. Please try again');
}
exit();
