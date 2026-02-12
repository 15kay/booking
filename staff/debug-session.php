<?php
session_start();
if(!isset($_SESSION['staff_id']) || $_SESSION['role'] != 'coordinator') {
    header('Location: ../staff-login.php');
    exit();
}

require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h1>Debug Session Information</h1>";
echo "<h2>Session Variables:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Coordinator Campus:</h2>";
echo "<p><strong>assigned_campus:</strong> " . (isset($_SESSION['assigned_campus']) ? htmlspecialchars($_SESSION['assigned_campus']) : 'NOT SET') . "</p>";

echo "<h2>Faculties Query Test:</h2>";
$faculties_query = "
    SELECT DISTINCT faculty FROM modules 
    WHERE faculty IS NOT NULL AND faculty != '' AND status = 'active'
";
$faculties_params = [];

if(isset($_SESSION['assigned_campus']) && $_SESSION['assigned_campus']) {
    $faculties_query .= " AND campus = ?";
    $faculties_params[] = $_SESSION['assigned_campus'];
    echo "<p><strong>Filtering by campus:</strong> " . htmlspecialchars($_SESSION['assigned_campus']) . "</p>";
} else {
    echo "<p><strong>WARNING:</strong> No campus filter applied!</p>";
}

$faculties_query .= " ORDER BY faculty";
echo "<p><strong>Query:</strong> " . htmlspecialchars($faculties_query) . "</p>";
echo "<p><strong>Parameters:</strong> " . htmlspecialchars(json_encode($faculties_params)) . "</p>";

$faculties_stmt = $conn->prepare($faculties_query);
$faculties_stmt->execute($faculties_params);
$faculties = $faculties_stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<h3>Faculties Found:</h3>";
echo "<ul>";
foreach($faculties as $faculty) {
    echo "<li>" . htmlspecialchars($faculty) . "</li>";
}
echo "</ul>";

echo "<h2>All Modules by Campus:</h2>";
$campus_query = "SELECT campus, COUNT(*) as count FROM modules WHERE status = 'active' GROUP BY campus ORDER BY campus";
$campus_stmt = $conn->prepare($campus_query);
$campus_stmt->execute();
$campuses = $campus_stmt->fetchAll();

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Campus</th><th>Module Count</th></tr>";
foreach($campuses as $campus) {
    echo "<tr><td>" . htmlspecialchars($campus['campus']) . "</td><td>" . $campus['count'] . "</td></tr>";
}
echo "</table>";

echo "<h2>Staff Record from Database:</h2>";
$staff_query = "SELECT staff_number, first_name, last_name, role, assigned_campus FROM staff WHERE staff_id = ?";
$staff_stmt = $conn->prepare($staff_query);
$staff_stmt->execute([$_SESSION['staff_id']]);
$staff = $staff_stmt->fetch();

echo "<pre>";
print_r($staff);
echo "</pre>";

echo "<p><a href='browse-modules.php'>Back to Browse Modules</a></p>";
