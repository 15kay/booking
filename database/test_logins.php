<?php
require 'c:/xampp/htdocs/booking/config/database.php';
$db   = new Database();
$conn = $db->connect();

echo "=== ADMIN ===" . PHP_EOL;
$s = $conn->prepare("SELECT * FROM admins WHERE username = ?");
$s->execute(['admin']);
$r = $s->fetch();
if ($r) {
    echo "  admin | " . (password_verify('admin123', $r['password_hash']) ? 'PASS' : 'FAIL') . PHP_EOL;
} else {
    echo "  NOT FOUND" . PHP_EOL;
}

echo PHP_EOL . "=== STUDENTS (password: password) ===" . PHP_EOL;
foreach (['230461255','222610476','219034521','215678901','211234567'] as $id) {
    $s = $conn->prepare("SELECT * FROM students WHERE student_id = ?");
    $s->execute([$id]);
    $r = $s->fetch();
    if ($r) {
        $ok = password_verify('password', $r['password_hash']) ? 'PASS' : 'FAIL';
        echo "  $id | {$r['first_name']} {$r['last_name']} | $ok" . PHP_EOL;
    } else {
        echo "  $id | NOT FOUND" . PHP_EOL;
    }
}

echo PHP_EOL . "=== STAFF (password: password) ===" . PHP_EOL;
foreach (['STF001','STF002','STF003','STF004','STF005'] as $id) {
    $s = $conn->prepare("SELECT * FROM staff WHERE staff_number = ?");
    $s->execute([$id]);
    $r = $s->fetch();
    if ($r) {
        $ok = password_verify('password', $r['password_hash']) ? 'PASS' : 'FAIL';
        echo "  $id | {$r['first_name']} {$r['last_name']} | {$r['role']} | $ok" . PHP_EOL;
    } else {
        echo "  $id | NOT FOUND" . PHP_EOL;
    }
}

echo PHP_EOL . "=== SAMPLE TUTORS/PALs (password: password) ===" . PHP_EOL;
$s = $conn->query("SELECT TOP 5 staff_number, first_name, last_name, role, password_hash FROM staff WHERE role IN ('tutor','pal') ORDER BY staff_id");
foreach ($s->fetchAll() as $r) {
    $ok = password_verify('password', $r['password_hash']) ? 'PASS' : 'FAIL';
    echo "  {$r['staff_number']} | {$r['first_name']} {$r['last_name']} | {$r['role']} | $ok" . PHP_EOL;
}

echo PHP_EOL . "=== TUTOR COUNT ===" . PHP_EOL;
$cnt = $conn->query("SELECT COUNT(*) FROM staff WHERE role IN ('tutor','pal')")->fetchColumn();
echo "  Total tutors/PALs in system: $cnt" . PHP_EOL;
