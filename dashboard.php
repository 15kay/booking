<?php
session_start();
if(!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - WSU Booking</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h1>
    <p>Dashboard coming soon...</p>
    <a href="auth/logout.php">Logout</a>
</body>
</html>
