<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>Checking Tutors & PALs in Database</h2>";

// Check if tutors exist
$stmt = $conn->query("SELECT staff_number, first_name, last_name, role, email, status FROM staff WHERE role IN ('tutor', 'pal')");
$tutors = $stmt->fetchAll();

echo "<p><strong>Total Tutors/PALs found:</strong> " . count($tutors) . "</p>";

if(count($tutors) > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Staff Number</th><th>Name</th><th>Role</th><th>Email</th><th>Status</th></tr>";
    foreach($tutors as $tutor) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($tutor['staff_number']) . "</td>";
        echo "<td>" . htmlspecialchars($tutor['first_name'] . ' ' . $tutor['last_name']) . "</td>";
        echo "<td>" . htmlspecialchars($tutor['role']) . "</td>";
        echo "<td>" . htmlspecialchars($tutor['email']) . "</td>";
        echo "<td>" . htmlspecialchars($tutor['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'><strong>No tutors or PALs found in database!</strong></p>";
    echo "<p>You need to run the SQL script: <code>database/sample_modules_data.sql</code></p>";
    echo "<p>This script will insert sample tutors and PALs.</p>";
}

// Check all staff roles
echo "<h3>All Staff by Role:</h3>";
$stmt = $conn->query("SELECT role, COUNT(*) as count FROM staff GROUP BY role");
$roles = $stmt->fetchAll();

echo "<ul>";
foreach($roles as $role) {
    echo "<li><strong>" . htmlspecialchars($role['role']) . ":</strong> " . $role['count'] . "</li>";
}
echo "</ul>";
?>
