<?php
session_start();
require_once 'config/database.php';

// Force login as PAL001 for testing
$_SESSION['staff_id'] = (new Database())->connect()->query("SELECT staff_id FROM staff WHERE staff_number = 'PAL001'")->fetch()['staff_id'];
$_SESSION['role'] = 'pal';

$db = new Database();
$conn = $db->connect();

echo "<h2>PAL001 Assignment Test</h2>";

$tutor_id = $_SESSION['staff_id'];

echo "<p>Logged in as Staff ID: {$tutor_id}</p>";

// Get assignments
$query = "
    SELECT 
        ta.*,
        m.subject_code, m.subject_name, m.faculty, m.campus,
        arm.at_risk_students
    FROM tutor_assignments ta
    INNER JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
    INNER JOIN modules m ON arm.module_id = m.module_id
    WHERE ta.tutor_id = ?
";

$stmt = $conn->prepare($query);
$stmt->execute([$tutor_id]);
$assignments = $stmt->fetchAll();

echo "<h3>Assignments Found: " . count($assignments) . "</h3>";

if(count($assignments) > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Assignment ID</th><th>Module</th><th>Type</th><th>Status</th><th>Action</th></tr>";
    foreach($assignments as $assignment) {
        echo "<tr>";
        echo "<td>{$assignment['assignment_id']}</td>";
        echo "<td>{$assignment['subject_code']} - {$assignment['subject_name']}</td>";
        echo "<td>{$assignment['tutor_type']}</td>";
        echo "<td>{$assignment['status']}</td>";
        echo "<td><a href='staff/create-session.php?assignment_id={$assignment['assignment_id']}'>Create Session</a></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h3>Test Links:</h3>";
    echo "<ul>";
    echo "<li><a href='staff/my-assignments.php'>Go to My Assignments Page</a></li>";
    foreach($assignments as $assignment) {
        echo "<li><a href='staff/create-session.php?assignment_id={$assignment['assignment_id']}'>Create Session for {$assignment['subject_code']}</a></li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>NO ASSIGNMENTS FOUND!</p>";
    echo "<p>Run this SQL: <code>database/create_sample_assignments.sql</code></p>";
}
?>
