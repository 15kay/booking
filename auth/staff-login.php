<?php
session_start();
require_once '../config/database.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $staff_number = trim($_POST['staff_id']);
    $password = $_POST['password'];
    
    if(empty($staff_number) || empty($password)) {
        header('Location: ../staff-login.php?error=Please fill in all fields');
        exit();
    }
    
    try {
        $db = new Database();
        $conn = $db->connect();
        
        if(!$conn) {
            header('Location: ../staff-login.php?error=Database connection failed');
            exit();
        }
        
        $stmt = $conn->prepare("SELECT * FROM staff WHERE staff_number = ?");
        $stmt->execute([$staff_number]);
        $staff = $stmt->fetch();
        
        if(!$staff) {
            header('Location: ../staff-login.php?error=Staff number not found');
            exit();
        }
        
        if($staff['status'] != 'active') {
            header('Location: ../staff-login.php?error=Account is not active');
            exit();
        }
        
        if(!password_verify($password, $staff['password_hash'])) {
            header('Location: ../staff-login.php?error=Incorrect password');
            exit();
        }
        
        $_SESSION['staff_id'] = $staff['staff_id'];
        $_SESSION['staff_number'] = $staff['staff_number'];
        $_SESSION['first_name'] = $staff['first_name'];
        $_SESSION['last_name'] = $staff['last_name'];
        $_SESSION['email'] = $staff['email'];
        $_SESSION['department_id'] = $staff['department_id'];
        $_SESSION['role'] = $staff['role'];
        
        // Update last login
        $stmt = $conn->prepare("UPDATE staff SET last_login = NOW() WHERE staff_id = ?");
        $stmt->execute([$staff['staff_id']]);
        
        header('Location: ../staff/index.php');
        exit();
        
    } catch(PDOException $e) {
        header('Location: ../staff-login.php?error=' . urlencode($e->getMessage()));
        exit();
    }
} else {
    header('Location: ../staff-login.php');
    exit();
}
