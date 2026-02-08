<?php
// Test Login Script
require_once 'config/database.php';

echo "<h2>Testing Student Login</h2>";

$test_student_id = '202401234';
$test_password = 'demo123';

try {
    $db = new Database();
    $conn = $db->connect();
    
    // Check if student exists
    $stmt = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$test_student_id]);
    $student = $stmt->fetch();
    
    if($student) {
        echo "<p>✓ Student found in database</p>";
        echo "<p>Student ID: " . htmlspecialchars($student['student_id']) . "</p>";
        echo "<p>Name: " . htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) . "</p>";
        echo "<p>Email: " . htmlspecialchars($student['email']) . "</p>";
        echo "<p>Status: " . htmlspecialchars($student['status']) . "</p>";
        
        // Test password verification
        if(password_verify($test_password, $student['password_hash'])) {
            echo "<p style='color: green;'>✓ Password verification SUCCESSFUL</p>";
            echo "<p style='color: green;'><strong>Login would work!</strong></p>";
        } else {
            echo "<p style='color: red;'>✗ Password verification FAILED</p>";
            echo "<p>Hash in DB: " . $student['password_hash'] . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Student NOT found in database</p>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>";
}
?>
