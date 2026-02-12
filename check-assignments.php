<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>Check Tutor Assignments</h2>";

// Check all tutors/PALs
$tutors = $conn->query("
    SELECT staff_id, staff_number, first_name, last_name, role 
    FROM staff 
    WHERE role IN ('tutor', 'pal')
")->fetchAll();

echo "<h3>Tutors/PALs in System:</h3>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Staff ID</th><th>Staff Number</th><th>Name</th><th>Role</th></tr>";
foreach($tutors as $tutor) {
    echo "<tr>";
    echo "<td>{$tutor['staff_id']}</td>";
    echo "<td>{$tutor['staff_number']}</td>";
    echo "<td>{$tutor['first_name']} {$tutor['last_name']}</td>";
    echo "<td>{$tutor['role']}</td>";
    echo "</tr>";
}
echo "</table>";

// Check assignments
echo "<h3>Tutor Assignments:</h3>";
$assignments = $conn->query("
    SELECT 
        ta.assignment_id,
        ta.tutor_id,
        s.staff_number,
        s.first_name,
        s.last_name,
        m.subject_code,
        m.subject_name,
        ta.tutor_type,
        ta.status,
        ta.start_date,
        ta.end_date
    FROM tutor_assignments ta
    INNER JOIN staff s ON ta.tutor_id = s.staff_id
    INNER JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
    INNER JOIN modules m ON arm.module_id = m.module_id
    ORDER BY ta.assignment_id
")->fetchAll();

if(count($assignments) > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Assignment ID</th><th>Staff Number</th><th>Name</th><th>Module</th><th>Type</th><th>Status</th><th>Start Date</th></tr>";
    foreach($assignments as $assignment) {
        echo "<tr>";
        echo "<td>{$assignment['assignment_id']}</td>";
        echo "<td>{$assignment['staff_number']}</td>";
        echo "<td>{$assignment['first_name']} {$assignment['last_name']}</td>";
        echo "<td>{$assignment['subject_code']} - {$assignment['subject_name']}</td>";
        echo "<td>{$assignment['tutor_type']}</td>";
        echo "<td>{$assignment['status']}</td>";
        echo "<td>{$assignment['start_date']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No assignments found! You need to run: <code>database/create_sample_assignments.sql</code></p>";
}

// Check who is logged in
session_start();
if(isset($_SESSION['staff_id'])) {
    echo "<hr><h3>Currently Logged In:</h3>";
    echo "<p>Staff ID: {$_SESSION['staff_id']}</p>";
    echo "<p>Role: {$_SESSION['role']}</p>";
    
    // Check this user's assignments
    $my_assignments = $conn->prepare("
        SELECT 
            ta.assignment_id,
            m.subject_code,
            m.subject_name,
            ta.tutor_type,
            ta.status
        FROM tutor_assignments ta
        INNER JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
        INNER JOIN modules m ON arm.module_id = m.module_id
        WHERE ta.tutor_id = ? AND ta.status = 'active'
    ");
    $my_assignments->execute([$_SESSION['staff_id']]);
    $my_list = $my_assignments->fetchAll();
    
    echo "<h4>Your Assignments:</h4>";
    if(count($my_list) > 0) {
        echo "<ul>";
        foreach($my_list as $assignment) {
            echo "<li><strong>{$assignment['subject_code']}</strong> - {$assignment['subject_name']} (Assignment ID: {$assignment['assignment_id']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>You have no active assignments!</p>";
    }
}
?>
