<?php
session_start();
require_once '../config/database.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = trim($_POST['student_id']);
    $password = $_POST['password'];
    
    if(empty($student_id) || empty($password)) {
        header('Location: ../index.php?error=Please fill in all fields');
        exit();
    }
    
    try {
        $db = new Database();
        $conn = $db->connect();
        
        if(!$conn) {
            header('Location: ../index.php?error=Database connection failed');
            exit();
        }
        
        $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        
        if(!$student) {
            header('Location: ../index.php?error=Student ID not found');
            exit();
        }
        
        if($student['status'] != 'active') {
            header('Location: ../index.php?error=Account is not active');
            exit();
        }
        
        if(!password_verify($password, $student['password_hash'])) {
            header('Location: ../index.php?error=Incorrect password');
            exit();
        }
        
        $_SESSION['student_id'] = $student['student_id'];
        $_SESSION['first_name'] = $student['first_name'];
        $_SESSION['last_name'] = $student['last_name'];
        $_SESSION['email'] = $student['email'];
        $_SESSION['faculty_id'] = $student['faculty_id'];
        
        // Update last login
        $stmt = $conn->prepare("UPDATE students SET last_login = NOW() WHERE student_id = ?");
        $stmt->execute([$student_id]);
        
        header('Location: ../student/index.php');
        exit();
        
    } catch(PDOException $e) {
        header('Location: ../index.php?error=' . urlencode($e->getMessage()));
        exit();
    }
} else {
    header('Location: ../index.php');
    exit();
}
