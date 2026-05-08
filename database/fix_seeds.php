<?php
require_once '../config/database.php';

$db = new Database();
$conn = $db->connect();
if(!$conn) die("Connection failed\n");

$hash = password_hash('admin123', PASSWORD_BCRYPT);
echo "New hash: $hash\n\n";

// Update all existing password hashes
$tables = [
    "UPDATE admins  SET password_hash = ?",
    "UPDATE staff   SET password_hash = ?",
    "UPDATE students SET password_hash = ?",
];
foreach($tables as $sql) {
    $conn->prepare($sql)->execute([$hash]);
}
echo "✓ All existing passwords updated\n\n";

// Insert missing staff
$missingStaff = [
    ['STF006','Lungelo','Nkosi',  'lungelo.nkosi@wsu.ac.za',   '0811234567',2,'admin',       'BCom Administration',  'Office Management',     'Mthatha'],
    ['STF007','Ayanda', 'Cele',   'ayanda.cele@wsu.ac.za',     '0812345678',1,'tutor',        'BSc Computer Science', 'Mathematics and CS',    'Butterworth'],
    ['STF008','Zanele', 'Mokoena','zanele.mokoena@wsu.ac.za',  '0813456789',2,'tutor',        'BCom Accounting',      'Accounting and Finance','Mthatha'],
    ['STF009','Siyanda','Dube',   'siyanda.dube@wsu.ac.za',    '0814567890',1,'pal',          'BSc 3rd Year',         'Physics and Maths',     'Butterworth'],
    ['STF010','Nokwanda','Sithole','nokwanda.sithole@wsu.ac.za','0815678901',3,'pal',          'BCom 3rd Year',        'Business Studies',      'Mthatha'],
    ['STF011','Mandla', 'Ntuli',  'mandla.ntuli@wsu.ac.za',    '0816789012',1,'coordinator',  'MEd Higher Education', 'Academic Coordination', 'Butterworth'],
    ['STF012','Bongiwe','Mthembu','bongiwe.mthembu@wsu.ac.za', '0817890123',2,'coordinator',  'MBA',                  'Student Coordination',  'Mthatha'],
];

$stmtCheck  = $conn->prepare("SELECT 1 FROM staff WHERE staff_number = ?");
$stmtInsert = $conn->prepare("
    INSERT INTO staff (staff_number, first_name, last_name, email, password_hash, phone, department_id, role, qualification, specialization, assigned_campus)
    VALUES (?,?,?,?,?,?,?,?,?,?,?)
");

foreach($missingStaff as $s) {
    $stmtCheck->execute([$s[0]]);
    if(!$stmtCheck->fetch()) {
        $stmtInsert->execute([$s[0],$s[1],$s[2],$s[3],$hash,$s[4],$s[5],$s[6],$s[7],$s[8],$s[9]]);
        echo "✓ Inserted staff {$s[0]} | {$s[1]} {$s[2]} | {$s[6]}\n";
    } else {
        echo "- Skipped staff {$s[0]} (already exists)\n";
    }
}

// Insert missing students
$missingStudents = [
    ['202401237','Nompilo',   'Dlamini','202401237@mywsu.ac.za','0835678901',4,2,'undergraduate'],
    ['202401238','Lethiwe',   'Zulu',   '202401238@mywsu.ac.za','0836789012',5,1,'undergraduate'],
    ['202401239','Mthokozisi','Ndlovu', '202401239@mywsu.ac.za','0837890123',1,4,'postgraduate'],
    ['202401240','Ayabonga',  'Cele',   '202401240@mywsu.ac.za','0838901234',2,3,'honours'],
];

$stmtCheckStu  = $conn->prepare("SELECT 1 FROM students WHERE student_id = ?");
$stmtInsertStu = $conn->prepare("
    INSERT INTO students (student_id, first_name, last_name, email, password_hash, phone, faculty_id, year_of_study, student_type)
    VALUES (?,?,?,?,?,?,?,?,?)
");

foreach($missingStudents as $s) {
    $stmtCheckStu->execute([$s[0]]);
    if(!$stmtCheckStu->fetch()) {
        $stmtInsertStu->execute([$s[0],$s[1],$s[2],$s[3],$hash,$s[4],$s[5],$s[6],$s[7]]);
        echo "✓ Inserted student {$s[0]} | {$s[1]} {$s[2]} | {$s[7]}\n";
    } else {
        echo "- Skipped student {$s[0]} (already exists)\n";
    }
}

echo "\n=== FINAL VERIFICATION ===\n";
echo "Admin:    " . $conn->query("SELECT COUNT(*) FROM admins")->fetchColumn()   . " record(s)\n";
echo "Staff:    " . $conn->query("SELECT COUNT(*) FROM staff")->fetchColumn()    . " record(s)\n";
echo "Students: " . $conn->query("SELECT COUNT(*) FROM students")->fetchColumn() . " record(s)\n";

echo "\nAll accounts use password: admin123\n";
