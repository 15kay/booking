<?php
require 'c:/xampp/htdocs/booking/config/database.php';
$db   = new Database();
$conn = $db->connect();

$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // "password"

// 1. Add Tutor/PAL service category
try {
    $exists = $conn->query("SELECT COUNT(*) FROM service_categories WHERE category_name = 'Tutor & PAL Sessions'")->fetchColumn();
    if (!$exists) {
        $conn->exec("INSERT INTO service_categories (category_name, description, icon, status)
            VALUES ('Tutor & PAL Sessions','Book a session with a Peer Assisted Learning tutor or module tutor','fa-chalkboard-teacher','active')");
        echo "Category created\n";
    } else {
        echo "Category already exists\n";
    }
} catch(Exception $e) { echo "Category ERR: ".$e->getMessage()."\n"; }

$cat_id = $conn->query("SELECT category_id FROM service_categories WHERE category_name = 'Tutor & PAL Sessions'")->fetchColumn();

// 2. Sync tutors into staff table
$tutors = $conn->query("SELECT DISTINCT STUDENT_NUMBER, FULL_NAME, CAMPUS, FACULTY, DEPARTMENT, [PAL/TUTOR] as role_type
    FROM StudentTrackingUnit.dbo.PalsTutors
    WHERE STUDENT_NUMBER IS NOT NULL AND STUDENT_NUMBER != 'nan' AND FULL_NAME IS NOT NULL AND FULL_NAME != 'nan'")->fetchAll();

$added = 0; $skipped = 0;
foreach ($tutors as $t) {
    $snum  = trim($t['STUDENT_NUMBER']);
    $name  = trim($t['FULL_NAME']);
    if (empty($snum) || empty($name)) { $skipped++; continue; }

    $parts = explode(' ', $name, 2);
    $first = $parts[0];
    $last  = $parts[1] ?? '';
    $email = $snum . '@wsu.ac.za';
    $role  = strtolower(trim($t['role_type'] ?? 'tutor'));
    if (!in_array($role, ['tutor','pal'])) $role = 'tutor';
    $campus = trim($t['CAMPUS'] ?? '');

    try {
        $check = $conn->prepare("SELECT staff_id FROM staff WHERE staff_number = ?");
        $check->execute([$snum]);
        if (!$check->fetch()) {
            $conn->prepare("INSERT INTO staff (staff_number, first_name, last_name, email, role, assigned_campus, status, password_hash)
                VALUES (?, ?, ?, ?, ?, ?, 'active', ?)")
                ->execute([$snum, $first, $last, $email, $role, $campus, $hash]);
            $added++;
        } else { $skipped++; }
    } catch(Exception $e) { $skipped++; }
}
echo "Tutors added: $added, skipped: $skipped\n";

// 3. Add schedule for all tutors (Mon-Fri 08:00-17:00)
$tutorStaff = $conn->query("SELECT staff_id FROM staff WHERE role IN ('tutor','pal')")->fetchAll(PDO::FETCH_COLUMN);
$sched_added = 0;
foreach ($tutorStaff as $sid) {
    for ($day = 1; $day <= 5; $day++) {
        $exists = $conn->prepare("SELECT 1 FROM staff_schedule WHERE staff_id = ? AND day_of_week = ?");
        $exists->execute([$sid, $day]);
        if (!$exists->fetch()) {
            try {
                $conn->prepare("INSERT INTO staff_schedule (staff_id, day_of_week, start_time, end_time, is_available) VALUES (?, ?, '08:00', '17:00', 1)")
                     ->execute([$sid, $day]);
                $sched_added++;
            } catch(Exception $e) {}
        }
    }
}
echo "Schedules added: $sched_added\n";

// 4. Add one service per unique module
$modules = $conn->query("SELECT DISTINCT MODULE, MODULE_CODE FROM StudentTrackingUnit.dbo.PalsTutors WHERE MODULE IS NOT NULL AND MODULE != 'nan' AND MODULE_CODE IS NOT NULL AND MODULE_CODE != 'nan'")->fetchAll();
$svc_added = 0;
foreach ($modules as $m) {
    $mod  = trim($m['MODULE']);
    $code = trim($m['MODULE_CODE']);
    if (empty($mod)) continue;

    $check = $conn->prepare("SELECT service_id FROM services WHERE service_name = ?");
    $check->execute([$mod]);
    if ($check->fetch()) continue;

    // Get a staff member for this module
    $staff = $conn->prepare("SELECT s.staff_id FROM staff s JOIN StudentTrackingUnit.dbo.PalsTutors pt ON pt.STUDENT_NUMBER = s.staff_number WHERE pt.MODULE_CODE = ?");
    $staff->execute([$code]);
    $staff_row = $staff->fetch();
    if (!$staff_row) continue;

    try {
        $conn->prepare("INSERT INTO services (category_id, service_name, description, duration_mins, staff_id, status)
            VALUES (?, ?, ?, 60, ?, 'active')")
            ->execute([$cat_id, $mod, "Tutor/PAL session for $mod (Code: $code)", $staff_row['staff_id']]);
        $svc_added++;
    } catch(Exception $e) {}
}
echo "Module services added: $svc_added\n";

// 5. Show sample login details
echo "\n--- SAMPLE TUTOR LOGINS (password: password) ---\n";
$samples = $conn->query("SELECT TOP 5 staff_number, first_name, last_name, role, assigned_campus FROM staff WHERE role IN ('tutor','pal') ORDER BY staff_id")->fetchAll();
foreach ($samples as $s) {
    echo "  {$s['staff_number']} | {$s['first_name']} {$s['last_name']} | {$s['role']} | {$s['assigned_campus']}\n";
}
echo "\nDone!\n";
