<?php
require_once 'config/database.php';

echo "<style>
body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #e5e7eb; }
h2   { color: #E8A020; border-bottom: 1px solid #333; padding-bottom: 8px; }
.ok  { color: #10b981; }
.fail{ color: #ef4444; }
.info{ color: #60a5fa; }
table{ border-collapse: collapse; width: 100%; margin-bottom: 20px; }
th   { background: #2d2d2d; padding: 10px; text-align: left; color: #E8A020; }
td   { padding: 8px 10px; border-bottom: 1px solid #2d2d2d; }
</style>";

// ── Connection ────────────────────────────────────────────────
echo "<h2>1. Database Connection</h2>";
try {
    $db   = new Database();
    $conn = $db->connect();
    if ($conn) {
        echo "<span class='ok'>✓ Connected to " . DB_HOST . " / " . DB_NAME . "</span><br>";
    } else {
        die("<span class='fail'>✗ Connection returned null</span>");
    }
} catch (Exception $e) {
    die("<span class='fail'>✗ " . $e->getMessage() . "</span>");
}

// ── Password hash check ───────────────────────────────────────
echo "<h2>2. Password Hash</h2>";
$stmt = $conn->query("SELECT TOP 1 password_hash FROM admins");
$row  = $stmt->fetch();
$valid = password_verify('admin123', $row['password_hash']);
echo $valid
    ? "<span class='ok'>✓ Password hash is valid (admin123 works)</span><br>"
    : "<span class='fail'>✗ Password hash mismatch — re-run fix_seeds.php</span><br>";

// ── Table counts ──────────────────────────────────────────────
echo "<h2>3. Table Record Counts</h2>";
$tables = ['admins','staff','students','faculties','departments','services','service_categories','bookings'];
echo "<table><tr><th>Table</th><th>Records</th><th>Status</th></tr>";
foreach ($tables as $t) {
    $count = $conn->query("SELECT COUNT(*) FROM $t")->fetchColumn();
    $ok    = $count > 0;
    echo "<tr><td>$t</td><td>$count</td><td class='" . ($ok ? 'ok' : 'fail') . "'>" . ($ok ? '✓' : '✗ Empty') . "</td></tr>";
}
echo "</table>";

// ── Admin login test ──────────────────────────────────────────
echo "<h2>4. Admin Login</h2>";
$stmt = $conn->prepare("SELECT * FROM admins WHERE username = ? AND status = 'active'");
$stmt->execute(['admin']);
$admin = $stmt->fetch();
echo $admin && password_verify('admin123', $admin['password_hash'])
    ? "<span class='ok'>✓ admin / admin123 — OK → redirects to /admin/index.php</span><br>"
    : "<span class='fail'>✗ Admin login failed</span><br>";

// ── Staff login tests ─────────────────────────────────────────
echo "<h2>5. Staff Logins</h2>";
echo "<table><tr><th>Staff No</th><th>Name</th><th>Role</th><th>Login</th></tr>";
$stmt = $conn->query("SELECT * FROM staff ORDER BY staff_id");
foreach ($stmt->fetchAll() as $s) {
    $ok = password_verify('admin123', $s['password_hash']) && $s['status'] === 'active';
    echo "<tr>
        <td>{$s['staff_number']}</td>
        <td>{$s['first_name']} {$s['last_name']}</td>
        <td>{$s['role']}</td>
        <td class='" . ($ok ? 'ok' : 'fail') . "'>" . ($ok ? '✓ OK' : '✗ FAIL') . "</td>
    </tr>";
}
echo "</table>";

// ── Student login tests ───────────────────────────────────────
echo "<h2>6. Student Logins</h2>";
echo "<table><tr><th>Student ID</th><th>Name</th><th>Type</th><th>Login</th></tr>";
$stmt = $conn->query("SELECT * FROM students ORDER BY student_id");
foreach ($stmt->fetchAll() as $s) {
    $ok = password_verify('admin123', $s['password_hash']) && $s['status'] === 'active';
    echo "<tr>
        <td>{$s['student_id']}</td>
        <td>{$s['first_name']} {$s['last_name']}</td>
        <td>{$s['student_type']}</td>
        <td class='" . ($ok ? 'ok' : 'fail') . "'>" . ($ok ? '✓ OK' : '✗ FAIL') . "</td>
    </tr>";
}
echo "</table>";

echo "<h2 class='ok'>✓ All tests complete</h2>";
echo "<p class='info'>Login at: <a href='index.php' style='color:#E8A020'>index.php</a></p>";
