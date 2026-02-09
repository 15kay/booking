<?php
session_start();
require_once '../config/database.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if(empty($username) || empty($password)) {
        header('Location: ../admin-login.php?error=Please fill in all fields');
        exit();
    }
    
    try {
        $db = new Database();
        $conn = $db->connect();
        
        if(!$conn) {
            header('Location: ../admin-login.php?error=Database connection failed');
            exit();
        }
        
        // Check if admins table exists, if not use a default admin
        $stmt = $conn->prepare("SHOW TABLES LIKE 'admins'");
        $stmt->execute();
        
        if($stmt->rowCount() > 0) {
            // Admins table exists
            $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();
            
            if(!$admin) {
                header('Location: ../admin-login.php?error=Invalid username or password');
                exit();
            }
            
            if($admin['status'] != 'active') {
                header('Location: ../admin-login.php?error=Account is not active');
                exit();
            }
            
            if(!password_verify($password, $admin['password_hash'])) {
                header('Location: ../admin-login.php?error=Invalid username or password');
                exit();
            }
            
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_role'] = $admin['role'];
            
            // Update last login
            $stmt = $conn->prepare("UPDATE admins SET last_login = NOW() WHERE admin_id = ?");
            $stmt->execute([$admin['admin_id']]);
        } else {
            // Default admin login (username: admin, password: admin123)
            if($username === 'admin' && $password === 'admin123') {
                $_SESSION['admin_id'] = 1;
                $_SESSION['admin_username'] = 'admin';
                $_SESSION['admin_name'] = 'System Administrator';
                $_SESSION['admin_email'] = 'admin@wsu.ac.za';
                $_SESSION['admin_role'] = 'super_admin';
            } else {
                header('Location: ../admin-login.php?error=Invalid username or password');
                exit();
            }
        }
        
        header('Location: ../admin/index.php');
        exit();
        
    } catch(PDOException $e) {
        header('Location: ../admin-login.php?error=' . urlencode($e->getMessage()));
        exit();
    }
} else {
    header('Location: ../admin-login.php');
    exit();
}
