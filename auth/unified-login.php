<?php
session_start();
require_once '../config/database.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit();
}

$identifier = trim($_POST['identifier'] ?? '');
$password   = $_POST['password'] ?? '';

if(empty($identifier) || empty($password)) {
    header('Location: ../index.php?error=Please fill in all fields');
    exit();
}

function redirectError($msg) {
    header('Location: ../index.php?error=' . urlencode($msg));
    exit();
}

try {
    $db   = new Database();
    $conn = $db->connect();

    if(!$conn) redirectError('Database connection failed');

    // ── 1. Try Admin (hardcoded fallback) ──────────────────────────────
    $adminTable = $conn->prepare("SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'admins'");
    $adminTable->execute();

    if($adminTable->rowCount() > 0) {
        $stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? AND status = 'active'");
        $stmt->execute([$identifier]);
        $admin = $stmt->fetch();
        if($admin && password_verify($password, $admin['password_hash'])) {
            $_SESSION['admin_id']       = $admin['admin_id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_name']     = $admin['full_name'];
            $_SESSION['admin_email']    = $admin['email'];
            $_SESSION['admin_role']     = $admin['role'];
            $conn->prepare("UPDATE admins SET last_login = NOW() WHERE admin_id = ?")->execute([$admin['admin_id']]);
            header('Location: ../admin/index.php');
            exit();
        }
    } else {
        // Fallback hardcoded admin
        if($identifier === 'admin' && $password === 'admin123') {
            $_SESSION['admin_id']       = 1;
            $_SESSION['admin_username'] = 'admin';
            $_SESSION['admin_name']     = 'System Administrator';
            $_SESSION['admin_email']    = 'admin@wsu.ac.za';
            $_SESSION['admin_role']     = 'super_admin';
            header('Location: ../admin/index.php');
            exit();
        }
    }

    // ── 2. Try Staff ───────────────────────────────────────────────────
    $stmt = $conn->prepare("SELECT * FROM staff WHERE staff_number = ?");
    $stmt->execute([$identifier]);
    $staff = $stmt->fetch();

    if($staff) {
        if($staff['status'] !== 'active') redirectError('Staff account is not active');
        if(!password_verify($password, $staff['password_hash'])) redirectError('Incorrect password');

        $_SESSION['staff_id']       = $staff['staff_id'];
        $_SESSION['staff_number']   = $staff['staff_number'];
        $_SESSION['first_name']     = $staff['first_name'];
        $_SESSION['last_name']      = $staff['last_name'];
        $_SESSION['email']          = $staff['email'];
        $_SESSION['department_id']  = $staff['department_id'];
        $_SESSION['role']           = $staff['role'];
        $_SESSION['assigned_campus']= $staff['assigned_campus'] ?? '';
        $conn->prepare("UPDATE staff SET last_login = NOW() WHERE staff_id = ?")->execute([$staff['staff_id']]);
        header('Location: ../staff/index.php');
        exit();
    }

    // ── 3. Try Student ─────────────────────────────────────────────────
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$identifier]);
    $student = $stmt->fetch();

    if($student) {
        if($student['status'] !== 'active') redirectError('Student account is not active');
        if(!password_verify($password, $student['password_hash'])) redirectError('Incorrect password');

        $_SESSION['student_id'] = $student['student_id'];
        $_SESSION['first_name'] = $student['first_name'];
        $_SESSION['last_name']  = $student['last_name'];
        $_SESSION['email']      = $student['email'];
        $_SESSION['faculty_id'] = $student['faculty_id'];
        $conn->prepare("UPDATE students SET last_login = NOW() WHERE student_id = ?")->execute([$student['student_id']]);
        header('Location: ../student/index.php');
        exit();
    }

    // ── Nothing matched ────────────────────────────────────────────────
    redirectError('Invalid ID or password');

} catch(PDOException $e) {
    redirectError('System error: ' . $e->getMessage());
}
