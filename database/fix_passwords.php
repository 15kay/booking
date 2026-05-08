<?php
require 'c:/xampp/htdocs/booking/config/database.php';
$db   = new Database();
$conn = $db->connect();
$hash = password_hash('password', PASSWORD_BCRYPT);

// Fix all staff passwords
$conn->prepare("UPDATE staff SET password_hash = ?")->execute([$hash]);
echo "Staff passwords reset: " . $conn->query("SELECT COUNT(*) FROM staff")->fetchColumn() . " staff\n";

// Add/update students
$students = [
    ['230461255','Asive','Mtalaliso','230461255@wsu.ac.za','0821234567',1,2,'undergraduate',72],
    ['222610476','Yongama','Mkizwana','222610476@wsu.ac.za','0831234567',2,3,'undergraduate',58],
    ['219034521','Thabo','Dlamini','219034521@wsu.ac.za','0841234567',3,1,'undergraduate',45],
    ['215678901','Nomsa','Khumalo','215678901@wsu.ac.za','0851234567',1,4,'undergraduate',85],
    ['211234567','Sipho','Nkosi','211234567@wsu.ac.za','0861234567',4,2,'postgraduate',91],
];
foreach ($students as $st) {
    $check = $conn->prepare("SELECT 1 FROM students WHERE student_id = ?");
    $check->execute([$st[0]]);
    if (!$check->fetch()) {
        $conn->prepare("INSERT INTO students (student_id,first_name,last_name,email,phone,faculty_id,year_of_study,student_type,password_hash,status) VALUES (?,?,?,?,?,?,?,?,?,'active')")
             ->execute([$st[0],$st[1],$st[2],$st[3],$st[4],$st[5],$st[6],$st[7],$hash]);
        echo "Added: {$st[0]} {$st[1]} {$st[2]}\n";
    } else {
        $conn->prepare("UPDATE students SET password_hash = ? WHERE student_id = ?")->execute([$hash,$st[0]]);
        echo "Updated: {$st[0]}\n";
    }
}
echo "Done\n";
