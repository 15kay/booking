<?php
/**
 * WSU Booking System - SQL Server Migration Runner
 * Runs database/schema_sqlserver.sql against clestudtrack02.wsu.ac.za
 */

// ── Only allow CLI or localhost access ────────────────────────────────
if (php_sapi_name() !== 'cli' && $_SERVER['REMOTE_ADDR'] !== '127.0.0.1') {
    http_response_code(403);
    die('Access denied.');
}

define('DB_HOST',        'clestudtrack02.wsu.ac.za');
define('DB_USER',        'smmakola');
define('DB_PASS',        'Kgau123@M');
define('DB_NAME',        'wsu_booking');
define('SCHEMA_FILE',    __DIR__ . '/schema_sqlserver.sql');

echo "=================================================\n";
echo " WSU Booking - SQL Server Migration\n";
echo " Server : " . DB_HOST . "\n";
echo " DB     : " . DB_NAME . "\n";
echo "=================================================\n\n";

// ── Connect ───────────────────────────────────────────────────────────
try {
    $dsn  = "sqlsrv:Server=" . DB_HOST . ";Database=" . DB_NAME . ";TrustServerCertificate=1;Encrypt=no";
    $pdo  = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "[OK] Connected to " . DB_HOST . "/" . DB_NAME . "\n\n";
} catch (PDOException $e) {
    die("[FAIL] Connection failed: " . $e->getMessage() . "\n");
}

// ── Read schema file ──────────────────────────────────────────────────
if (!file_exists(SCHEMA_FILE)) {
    die("[FAIL] Schema file not found: " . SCHEMA_FILE . "\n");
}

$sql = file_get_contents(SCHEMA_FILE);

// Split on GO statements (T-SQL batch separator)
$batches = preg_split('/^\s*GO\s*$/im', $sql);
$batches = array_filter(array_map('trim', $batches));

$total   = count($batches);
$success = 0;
$failed  = 0;

echo "Running $total SQL batches...\n\n";

foreach ($batches as $i => $batch) {
    if (empty($batch)) continue;

    // Show first 80 chars as preview
    $preview = preg_replace('/\s+/', ' ', substr($batch, 0, 80));
    try {
        $pdo->exec($batch);
        echo "[" . ($i + 1) . "/$total] OK    » $preview...\n";
        $success++;
    } catch (PDOException $e) {
        // Skip "already exists" errors gracefully
        $msg = $e->getMessage();
        if (
            stripos($msg, 'already an object') !== false ||
            stripos($msg, 'already exists')    !== false ||
            stripos($msg, 'duplicate key')     !== false
        ) {
            echo "[" . ($i + 1) . "/$total] SKIP  » $preview... (already exists)\n";
            $success++;
        } else {
            echo "[" . ($i + 1) . "/$total] ERROR » $preview...\n";
            echo "         └─ " . $msg . "\n";
            $failed++;
        }
    }
}

echo "\n=================================================\n";
echo " Done: $success succeeded, $failed failed\n";
echo "=================================================\n";

if ($failed === 0) {
    echo "\n[SUCCESS] Schema applied to " . DB_NAME . " on " . DB_HOST . "\n";
} else {
    echo "\n[WARNING] $failed batch(es) failed. Review errors above.\n";
}
