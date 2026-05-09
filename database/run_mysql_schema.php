<?php
// Run once to set up the MySQL database on the server
// Visit: http://your-server/booking/database/run_mysql_schema.php

$host = 'localhost';
$user = 'root';
$pass = '';

echo "<style>body{font-family:monospace;padding:20px;background:#1a1a1a;color:#e5e7eb;}h2{color:#E8A020;}.ok{color:#10b981;}.fail{color:#ef4444;}</style>";

try {
    // Connect without DB first to create it
    $pdo = new PDO("mysql:host=$host;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<span class='ok'>✓ Connected to MySQL</span><br><br>";

    $sql = file_get_contents(__DIR__ . '/schema_mysql.sql');

    // Split on semicolons
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($s) => !empty($s)
    );

    echo "<h2>Running " . count($statements) . " statements...</h2>";

    $ok = $fail = 0;
    foreach ($statements as $i => $stmt) {
        try {
            $pdo->exec($stmt);
            $ok++;
            echo "<span class='ok'>✓ Statement " . ($i+1) . "</span><br>";
        } catch (PDOException $e) {
            $fail++;
            echo "<span class='fail'>✗ Statement " . ($i+1) . ": " . $e->getMessage() . "</span><br>";
        }
    }

    echo "<h2>Done — ✓ $ok succeeded, ✗ $fail failed</h2>";
    echo "<br><a href='../test_login.php' style='color:#E8A020'>→ Run Login Tests</a>";

} catch (PDOException $e) {
    echo "<span class='fail'>✗ MySQL connection failed: " . $e->getMessage() . "</span>";
}
