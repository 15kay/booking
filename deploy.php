<?php
// GitHub Webhook - Auto Deploy
// Place this file on the SERVER at: C:\xampp\htdocs\booking\deploy.php
// Add this URL as a webhook in GitHub: http://your-server/booking/deploy.php

define('WEBHOOK_SECRET', 'wsu_booking_deploy_2024');
define('BRANCH', 'main');
define('LOG_FILE', __DIR__ . '/deploy.log');

function log_msg($msg) {
    file_put_contents(LOG_FILE, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, FILE_APPEND);
}

// Verify GitHub signature
$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
$expected  = 'sha256=' . hash_hmac('sha256', $payload, WEBHOOK_SECRET);

if (!hash_equals($expected, $signature)) {
    http_response_code(403);
    log_msg('FAILED: Invalid signature');
    die('Forbidden');
}

$data   = json_decode($payload, true);
$branch = str_replace('refs/heads/', '', $data['ref'] ?? '');

if ($branch !== BRANCH) {
    log_msg("SKIPPED: Push was to branch '$branch', not '" . BRANCH . "'");
    die("Not target branch");
}

// Run git pull
$output = shell_exec('cd ' . escapeshellarg(__DIR__) . ' && git pull origin ' . BRANCH . ' 2>&1');
log_msg("DEPLOYED branch '$branch': " . trim($output));

http_response_code(200);
echo json_encode(['status' => 'deployed', 'output' => $output]);
