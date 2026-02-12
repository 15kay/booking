<?php
require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>Database Enrollment Debug</h2>";

// Check if student_modules table exists
try {
    $check_table = $conn->query("SHOW TABLES LIKE 'student_modules'");
    if($check_table->rowCount() > 0) {
        echo "<p style='color: green;'>✓ student_modules table EXISTS</p>";
        
        // Count enrollments
        $count = $conn->query("SELECT COUNT(*) as total FROM student_modules")->fetch();
        echo "<p>Total enrollments: <strong>{$count['total']}</strong></p>";
        
        // Show enrollments by module
        $by_module = $conn->query("
            SELECT 
                m.subject_code,
                m.subject_name,
                COUNT(sm.enrollment_id) as enrolled_count
            FROM student_modules sm
            INNER JOIN modules m ON sm.module_id = m.module_id
            WHERE sm.status = 'active'
            GROUP BY m.module_id
        ")->fetchAll();
        
        echo "<h3>Enrollments by Module:</h3>";
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Module Code</th><th>Module Name</th><th>Enrolled Students</th></tr>";
        foreach($by_module as $row) {
            echo "<tr>";
            echo "<td>{$row['subject_code']}</td>";
            echo "<td>{$row['subject_name']}</td>";
            echo "<td>{$row['enrolled_count']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>✗ student_modules table DOES NOT EXIST</p>";
        echo "<p>You need to run: <code>database/student_modules_schema.sql</code></p>";
    }
} catch(PDOException $e) {
    echo "<p style='color: red;'>Error checking table: " . $e->getMessage() . "</p>";
}

// Check students table
echo "<hr><h3>Students Table:</h3>";
try {
    $students_count = $conn->query("SELECT COUNT(*) as total FROM students")->fetch();
    echo "<p>Total students in database: <strong>{$students_count['total']}</strong></p>";
    
    // Show first 10 students
    $students = $conn->query("SELECT student_id, first_name, last_name FROM students LIMIT 10")->fetchAll();
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Student ID</th><th>Name</th></tr>";
    foreach($students as $student) {
        echo "<tr>";
        echo "<td>{$student['student_id']}</td>";
        echo "<td>{$student['first_name']} {$student['last_name']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Check modules table
echo "<hr><h3>Modules Table:</h3>";
try {
    $modules = $conn->query("
        SELECT module_id, subject_code, subject_name, headcount 
        FROM modules 
        WHERE subject_code IN ('CS101', 'MATH101', 'IT102')
    ")->fetchAll();
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Module ID</th><th>Code</th><th>Name</th><th>Headcount</th></tr>";
    foreach($modules as $module) {
        echo "<tr>";
        echo "<td>{$module['module_id']}</td>";
        echo "<td>{$module['subject_code']}</td>";
        echo "<td>{$module['subject_name']}</td>";
        echo "<td>{$module['headcount']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Check at_risk_modules
echo "<hr><h3>At-Risk Modules:</h3>";
try {
    $at_risk = $conn->query("
        SELECT arm.risk_id, arm.module_id, m.subject_code, m.subject_name, arm.at_risk_students
        FROM at_risk_modules arm
        INNER JOIN modules m ON arm.module_id = m.module_id
        WHERE arm.status = 'active'
    ")->fetchAll();
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Risk ID</th><th>Module ID</th><th>Code</th><th>Name</th><th>At-Risk Students</th></tr>";
    foreach($at_risk as $risk) {
        echo "<tr>";
        echo "<td>{$risk['risk_id']}</td>";
        echo "<td>{$risk['module_id']}</td>";
        echo "<td>{$risk['subject_code']}</td>";
        echo "<td>{$risk['subject_name']}</td>";
        echo "<td>{$risk['at_risk_students']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Check tutor_sessions
echo "<hr><h3>Tutor Sessions:</h3>";
try {
    $sessions = $conn->query("
        SELECT 
            ts.session_id,
            ts.topic,
            ts.session_date,
            ta.assignment_id,
            m.subject_code
        FROM tutor_sessions ts
        INNER JOIN tutor_assignments ta ON ts.assignment_id = ta.assignment_id
        INNER JOIN at_risk_modules arm ON ta.risk_module_id = arm.risk_id
        INNER JOIN modules m ON arm.module_id = m.module_id
        LIMIT 5
    ")->fetchAll();
    
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Session ID</th><th>Module</th><th>Topic</th><th>Date</th><th>Assignment ID</th></tr>";
    foreach($sessions as $session) {
        echo "<tr>";
        echo "<td>{$session['session_id']}</td>";
        echo "<td>{$session['subject_code']}</td>";
        echo "<td>{$session['topic']}</td>";
        echo "<td>{$session['session_date']}</td>";
        echo "<td>{$session['assignment_id']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Test the actual query from mark-attendance.php
echo "<hr><h3>Test Mark Attendance Query (Session ID = 1):</h3>";
try {
    $test_session_id = 1;
    
    $students_stmt = $conn->prepare("
        SELECT 
            s.student_id,
            s.first_name, 
            s.last_name, 
            s.email, 
            s.year_of_study,
            sr.registration_id,
            sr.attended,
            sr.attendance_marked_at,
            sr.status as attendance_status,
            COALESCE(sr.status, 'not_marked') as status
        FROM student_modules sm
        INNER JOIN students s ON sm.student_id = s.student_id
        INNER JOIN at_risk_modules arm ON sm.module_id = arm.module_id
        INNER JOIN tutor_assignments ta ON arm.risk_id = ta.risk_module_id
        INNER JOIN tutor_sessions ts ON ta.assignment_id = ts.assignment_id
        LEFT JOIN session_registrations sr ON (
            sr.session_id = ts.session_id 
            AND sr.student_id = s.student_id
        )
        WHERE ts.session_id = ?
        AND sm.status = 'active'
        AND sm.academic_year = YEAR(ts.session_date)
        ORDER BY s.last_name, s.first_name
    ");
    $students_stmt->execute([$test_session_id]);
    $students = $students_stmt->fetchAll();
    
    echo "<p>Found <strong>" . count($students) . "</strong> students for session ID {$test_session_id}</p>";
    
    if(count($students) > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Student ID</th><th>Name</th><th>Year</th><th>Status</th></tr>";
        foreach($students as $student) {
            echo "<tr>";
            echo "<td>{$student['student_id']}</td>";
            echo "<td>{$student['first_name']} {$student['last_name']}</td>";
            echo "<td>{$student['year_of_study']}</td>";
            echo "<td>{$student['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>No students found. Possible reasons:</p>";
        echo "<ul>";
        echo "<li>student_modules table is empty</li>";
        echo "<li>No enrollments for this module</li>";
        echo "<li>Academic year doesn't match session date</li>";
        echo "<li>Session doesn't exist</li>";
        echo "</ul>";
    }
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>Query Error: " . $e->getMessage() . "</p>";
}

?>
