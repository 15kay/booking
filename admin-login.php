<?php
session_start();
if(isset($_SESSION['admin_id']))   { header('Location: admin/index.php');   exit(); }
if(isset($_SESSION['staff_id']))   { header('Location: staff/index.php');   exit(); }
if(isset($_SESSION['student_id'])) { header('Location: student/index.php'); exit(); }
header('Location: index.php' . (isset($_GET['error']) ? '?error=' . urlencode($_GET['error']) : ''));
exit();
